<?php

namespace App\Services\Production;

use App\Models\Production\GrowingSpace;
use App\Models\Production\GrowingSpaceType;
use App\Models\Production\ProductionSite;
use App\Models\Production\Zone;
use Illuminate\Support\Collection;

class OccupancyService
{
    public function __construct(
        protected BatchPlacementService $placementService,
        protected ZoneService $zoneService,
    ) {}

    // ════════════════════════════════════════════════════
    //  SITE ROLLUP — every space under the site, any zone depth.
    //  production_site_id is denormalized on every growing space,
    //  so this is a single flat query, no recursion needed.
    // ════════════════════════════════════════════════════

    public function siteRollup(ProductionSite $site): array
    {
        $spaces = GrowingSpace::where('production_site_id', $site->id)
            ->get(['id', 'capacity', 'growing_space_type_id']);

        return $this->buildRollup($spaces, $site->company_id);
    }

    // ════════════════════════════════════════════════════
    //  ZONE ROLLUP — this zone + every nested sub-zone, any depth.
    // ════════════════════════════════════════════════════

    public function zoneRollup(Zone $zone): array
    {
        $zoneIds = $this->zoneService->subtreeZoneIds($zone);

        $spaces = GrowingSpace::whereIn('zone_id', $zoneIds)
            ->get(['id', 'capacity', 'growing_space_type_id']);

        return $this->buildRollup($spaces, $zone->company_id);
    }

    // ════════════════════════════════════════════════════
    //  SHARED — business-metric rollup + capacity breakdown by unit.
    //  No mixed-unit aggregation: capacity is only ever summed within
    //  a single capacity_unit group, never across groups.
    // ════════════════════════════════════════════════════

    protected function buildRollup(Collection $spaces, int $companyId): array
    {
        $totalSpaces = $spaces->count();

        if ($totalSpaces === 0) {
            return $this->emptyRollup();
        }

        $spaceIds = $spaces->pluck('id')->all();
        $occupancyMap = $this->placementService->occupancyMapForSpaces($spaceIds, $companyId);

        $types = GrowingSpaceType::whereIn('id', $spaces->pluck('growing_space_type_id')->unique())
            ->get(['id', 'capacity_unit', 'custom_unit_label'])
            ->keyBy('id');

        $occupiedSpaces = 0;
        $activeBatches  = 0;
        $totalPlants    = 0;
        $byUnit         = [];

        foreach ($spaces as $space) {
            $occ = $occupancyMap[$space->id] ?? ['batch_count' => 0, 'quantity_occupied' => 0];
            $isOccupied = $occ['batch_count'] > 0;

            if ($isOccupied) {
                $occupiedSpaces++;
            }
            $activeBatches += $occ['batch_count'];
            $totalPlants   += $occ['quantity_occupied'];

            $type     = $types[$space->growing_space_type_id] ?? null;
            $unit     = $type?->capacity_unit ?? 'unknown';
            $isCount  = in_array($unit, GrowingSpaceType::COUNT_BASED_UNITS, true);

            if (!isset($byUnit[$unit])) {
                $byUnit[$unit] = [
                    'unit'                 => $unit,
                    'unit_label'           => $type?->capacity_unit === GrowingSpaceType::CAPACITY_UNIT_CUSTOM
                        ? ($type->custom_unit_label ?? 'Custom')
                        : (GrowingSpaceType::CAPACITY_UNIT_LABELS[$unit] ?? $unit),
                    'is_count_based'       => $isCount,
                    'space_count'          => 0,
                    'occupied_space_count' => 0,
                    'total_capacity'       => 0.0,
                    // Only meaningful (and only populated) for count-based units.
                    'quantity_occupied'    => $isCount ? 0 : null,
                ];
            }

            $byUnit[$unit]['space_count']++;
            $byUnit[$unit]['total_capacity'] += (float) $space->capacity;

            if ($isOccupied) {
                $byUnit[$unit]['occupied_space_count']++;
            }
            if ($isCount) {
                $byUnit[$unit]['quantity_occupied'] += $occ['quantity_occupied'];
            }
        }

        return [
            'total_spaces'       => $totalSpaces,
            'occupied_spaces'    => $occupiedSpaces,
            'empty_spaces'       => $totalSpaces - $occupiedSpaces,
            'active_batches'     => $activeBatches,
            'total_plants'       => $totalPlants,
            'occupancy_pct'      => (int) round(($occupiedSpaces / $totalSpaces) * 100),
            'capacity_breakdown' => array_values($byUnit),
        ];
    }

 /**
     * Thin pass-through — used by the Canvas children grid to badge
     * multiple growing spaces in one request instead of N+1 calls.
     */
    public function spacesMap(array $spaceIds, int $companyId): array
    {
        return $this->placementService->occupancyMapForSpaces($spaceIds, $companyId);
    }

    protected function emptyRollup(): array    {
        return [
            'total_spaces'       => 0,
            'occupied_spaces'    => 0,
            'empty_spaces'       => 0,
            'active_batches'     => 0,
            'total_plants'       => 0,
            'occupancy_pct'      => 0,
            'capacity_breakdown' => [],
        ];
    }
}