<?php

namespace App\Services\Production;

use App\Enums\Production\TaskStatus;
use App\Models\Production\DailyTask;
use App\Models\Production\GrowingSpace;
use App\Models\Production\Zone;
use App\Models\Production\ZoneAssignment;
use Illuminate\Support\Facades\DB;

class ZoneService
{
    /**
     * @throws \RuntimeException  When the parent zone belongs to a different Site
     */
    public function create(array $data): Zone
    {
        $this->assertParentBelongsToSameSite($data['parent_id'] ?? null, $data['production_site_id']);

        return DB::transaction(fn () => Zone::create($data));
    }

    /**
     * @throws \RuntimeException  When reparenting would create a cycle,
     *                            or move the zone to a different Site
     */
    public function update(Zone $zone, array $data): Zone
    {
        $newParentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $zone->parent_id;
        $siteId = $data['production_site_id'] ?? $zone->production_site_id;

        $this->assertParentBelongsToSameSite($newParentId, $siteId);

        if ($newParentId !== null) {
            $this->assertNotOwnDescendant($zone, (int) $newParentId);
        }

        return DB::transaction(function () use ($zone, $data) {
            $zone->update($data);

            return $zone->fresh();
        });
    }

    /**
     * Delete a Zone along with every descendant Zone (any depth) and every
     * Growing Space under it, in a single transaction. One query resolves
     * the whole subtree — no recursive per-node deletes.
     */
    public function delete(Zone $zone): void
    {
        $zoneIds = $this->subtreeZoneIds($zone);

        // Guard runs before the transaction opens — nothing has been touched
        // yet, so the caller gets a clean 422 with no partial cascade.
        GrowingSpaceService::assertNoActivePlacements(
            GrowingSpace::whereIn('zone_id', $zoneIds)->pluck('id')->all()
        );

        DB::transaction(function () use ($zone, $zoneIds) {
            $descendantIds = array_values(array_diff($zoneIds, [$zone->id]));

            if (! empty($descendantIds)) {
                Zone::whereIn('id', $descendantIds)->delete();
            }

            GrowingSpace::whereIn('zone_id', $zoneIds)->delete();

            // Workers assigned to a zone that no longer exists kept an active
            // assignment row, and their task list silently went empty because
            // My Tasks queries by zone. Close the assignments and retire the
            // still-pending tasks so nothing points at a deleted zone.
            ZoneAssignment::whereIn('zone_id', $zoneIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            DailyTask::whereIn('zone_id', $zoneIds)
                ->where('status', TaskStatus::Pending->value)
                ->update(['status' => TaskStatus::Skipped->value]);

            $zone->delete();
        });
    }

   /**
     * All zone IDs in the subtree rooted at $zone, including itself.
     * Public wrapper around descendantIds() — same one-query walk used
     * by delete()/update(), reused here for occupancy rollups.
     */
    public function subtreeZoneIds(Zone $zone): array
    {
        return array_merge([$zone->id], $this->descendantIds($zone));
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    protected function assertParentBelongsToSameSite(?int $parentId, int $siteId): void
    {
        if ($parentId === null) {
            return;
        }

        $parentSiteId = Zone::whereKey($parentId)->value('production_site_id');

        if ($parentSiteId !== $siteId) {
            throw new \RuntimeException('Parent zone must belong to the same Production Site.');
        }
    }

    protected function assertNotOwnDescendant(Zone $zone, int $newParentId): void
    {
        if ($newParentId === $zone->id) {
            throw new \RuntimeException('A zone cannot be its own parent.');
        }

        if (in_array($newParentId, $this->descendantIds($zone), true)) {
            throw new \RuntimeException('Cannot move a zone under its own descendant.');
        }
    }

    // ════════════════════════════════════════════════════
    //  INTERNALS
    // ════════════════════════════════════════════════════

    /**
     * All descendant zone IDs under $zone, at any depth — one query.
     * Loads every zone under the same Production Site (cheap; a site's
     * zone count is physically bounded), builds a parent_id -> children
     * map in memory, then walks it.
     */
    protected function descendantIds(Zone $zone): array
    {
        $rows = Zone::query()
            ->where('production_site_id', $zone->production_site_id)
            ->get(['id', 'parent_id']);

        $childrenMap = [];
        foreach ($rows as $row) {
            $childrenMap[$row->parent_id][] = $row->id;
        }

        $descendants = [];
        $stack = $childrenMap[$zone->id] ?? [];

        while ($stack) {
            $id = array_pop($stack);
            $descendants[] = $id;

            if (! empty($childrenMap[$id])) {
                array_push($stack, ...$childrenMap[$id]);
            }
        }

        return $descendants;
    }
}