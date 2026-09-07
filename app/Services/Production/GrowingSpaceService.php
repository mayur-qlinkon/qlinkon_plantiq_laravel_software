<?php

namespace App\Services\Production;

use App\Models\Production\BatchPlacement;
use App\Models\Production\GrowingSpace;
use App\Models\Production\Zone;
use Illuminate\Support\Facades\DB;

class GrowingSpaceService
{
    /**
     * @throws \RuntimeException  When the given zone belongs to a different Site
     */
    public function create(array $data): GrowingSpace
    {
        $this->assertZoneBelongsToSameSite($data['zone_id'] ?? null, $data['production_site_id']);

        return DB::transaction(fn () => GrowingSpace::create($data));
    }

    /**
     * @throws \RuntimeException  When the given zone belongs to a different Site
     */
    public function update(GrowingSpace $space, array $data): GrowingSpace
    {
        $zoneId = array_key_exists('zone_id', $data) ? $data['zone_id'] : $space->zone_id;
        $siteId = $data['production_site_id'] ?? $space->production_site_id;

        $this->assertZoneBelongsToSameSite($zoneId, $siteId);

        return DB::transaction(function () use ($space, $data) {
            $space->update($data);

            return $space->fresh();
        });
    }

    /**
     * @throws \RuntimeException  When plants are still placed here
     *
     * production_batch_placements is an insert-only ledger with no soft
     * deletes, so a space deleted underneath a live placement left the row
     * open (ended_at = NULL) pointing at a location that no longer exists —
     * the batch stayed "actively placed" nowhere.
     */
    public function delete(GrowingSpace $space): void
    {
        $this->assertNoActivePlacements([$space->id]);

        DB::transaction(fn () => $space->delete());
    }

    /**
     * Shared by Zone and Site deletion, which cascade to spaces.
     *
     * @param  array<int>  $spaceIds
     *
     * @throws \RuntimeException
     */
    public static function assertNoActivePlacements(array $spaceIds): void
    {
        if (empty($spaceIds)) {
            return;
        }

        $summary = BatchPlacement::query()
            ->whereIn('growing_space_id', $spaceIds)
            ->whereNull('ended_at')
            ->join('production_plant_batches as b', 'b.id', '=', 'production_batch_placements.plant_batch_id')
            ->whereNull('b.deleted_at')
            ->selectRaw('COUNT(*) as placement_count, COALESCE(SUM(b.current_quantity), 0) as plant_count')
            ->first();

        if ($summary && (int) $summary->placement_count > 0) {
            throw new \RuntimeException(
                "Cannot delete: {$summary->placement_count} batch(es) holding {$summary->plant_count} plant(s) are still placed here. Move or release them first."
            );
        }
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    protected function assertZoneBelongsToSameSite(?int $zoneId, int $siteId): void
    {
        if ($zoneId === null) {
            return;
        }

        $zoneSiteId = Zone::whereKey($zoneId)->value('production_site_id');

        if ($zoneSiteId !== $siteId) {
            throw new \RuntimeException("Growing Space's zone must belong to the same Production Site.");
        }
    }
}