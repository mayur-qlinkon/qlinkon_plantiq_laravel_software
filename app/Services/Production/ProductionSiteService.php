<?php

namespace App\Services\Production;

use App\Enums\Production\TaskStatus;
use App\Models\Production\DailyTask;
use App\Models\Production\ProductionSite;
use App\Models\Production\ZoneAssignment;
use Illuminate\Support\Facades\DB;

class ProductionSiteService
{
    public function create(array $data): ProductionSite
    {
        return DB::transaction(fn () => ProductionSite::create($data));
    }

    public function update(ProductionSite $site, array $data): ProductionSite
    {
        return DB::transaction(function () use ($site, $data) {
            $site->update($data);

            return $site->fresh();
        });
    }

    /**
     * Delete a Production Site along with every Zone and Growing Space
     * under it (soft delete), in one transaction.
     *
     * zones()/growingSpaces() already match every row under this site
     * regardless of zone depth, since production_site_id is denormalized
     * onto every Zone and Growing Space row — no recursion needed here.
     */
    public function delete(ProductionSite $site): void
    {
        $spaceIds = $site->growingSpaces()->pluck('id')->all();
        $zoneIds = $site->zones()->pluck('id')->all();

        GrowingSpaceService::assertNoActivePlacements($spaceIds);

        DB::transaction(function () use ($site, $zoneIds) {
            $site->zones()->delete();
            $site->growingSpaces()->delete();

            // Same cleanup ZoneService::delete() performs — a site cascade
            // reaches the same assignment and task rows.
            if (! empty($zoneIds)) {
                ZoneAssignment::whereIn('zone_id', $zoneIds)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                DailyTask::whereIn('zone_id', $zoneIds)
                    ->where('status', TaskStatus::Pending->value)
                    ->update(['status' => TaskStatus::Skipped->value]);
            }

            $site->delete();
        });
    }
}