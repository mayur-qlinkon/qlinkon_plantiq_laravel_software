<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\GrowingSpace;
use App\Models\Production\GrowingSpaceType;
use App\Models\Production\ProductionSite;
use App\Models\Production\Zone;
use App\Services\Production\BatchPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LayoutWorkspaceController extends Controller
{
    public function __construct(
        protected BatchPlacementService $placementService
    ) {}

    // ════════════════════════════════════════════════════
    //  PAGE
    //  GET /admin/production/layout
    // ════════════════════════════════════════════════════

    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Loaded once for the Inspector's "Growing Space Type" dropdown —
        // this list is small (tenant-curated master data), unlike the tree.
        $growingSpaceTypes = GrowingSpaceType::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'capacity_unit', 'custom_unit_label']);

        return view('admin.production.layout.index', compact('growingSpaceTypes'));
    }

    // ════════════════════════════════════════════════════
    //  ROOT — Production Sites, each with a children_summary
    //  GET /admin/production/layout/sites
    // ════════════════════════════════════════════════════

    public function sites(): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $sites = ProductionSite::where('company_id', $companyId)
            ->ordered()
            ->get(['id', 'name', 'is_active', 'sort_order']);

        $zoneCounts = Zone::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->selectRaw('production_site_id, count(*) as aggregate_count')
            ->groupBy('production_site_id')
            ->pluck('aggregate_count', 'production_site_id');

        $spaceCounts = GrowingSpace::where('company_id', $companyId)
            ->whereNull('zone_id')
            ->selectRaw('production_site_id, count(*) as aggregate_count')
            ->groupBy('production_site_id')
            ->pluck('aggregate_count', 'production_site_id');

        $nodes = $sites->map(function (ProductionSite $site) use ($zoneCounts, $spaceCounts) {
            $zoneCount = (int) ($zoneCounts[$site->id] ?? 0);
            $spaceCount = (int) ($spaceCounts[$site->id] ?? 0);

            return $this->formatNode(
                id: $site->id,
                type: 'site',
                name: $site->name,
                sortOrder: $site->sort_order,
                isActive: $site->is_active,
                hasZones: $zoneCount > 0,
                hasSpaces: $spaceCount > 0,
                totalChildren: $zoneCount + $spaceCount,
            );
        });

        return response()->json(['success' => true, 'nodes' => $nodes]);
    }

    // ════════════════════════════════════════════════════
    //  Root-level zones under a Site (parent_id null)
    //  GET /admin/production/layout/sites/{site}/zones
    // ════════════════════════════════════════════════════

    public function siteZones(ProductionSite $site): JsonResponse
    {
        $this->authorizeSite($site);

        // FIX: Eager load active assignments and their employees
        $zones = $site->rootZones()
            ->with(['assignments' => fn($q) => $q->active()->with('employee')])
            ->get(['id', 'name', 'is_active', 'sort_order']);

        return response()->json([
            'success' => true,
            'nodes' => $this->formatZoneNodes($zones),
        ]);
    }

    // ════════════════════════════════════════════════════
    //  Growing Spaces directly under a Site (zone_id null)
    //  GET /admin/production/layout/sites/{site}/direct-spaces
    // ════════════════════════════════════════════════════

    public function siteDirectSpaces(ProductionSite $site): JsonResponse
    {
        $this->authorizeSite($site);

        $spaces = $site->directGrowingSpaces()->get(['id', 'name', 'is_active', 'sort_order', 'capacity', 'growing_space_type_id']);

        return response()->json([
            'success' => true,
            'nodes' => $this->formatSpaceNodes($spaces, Auth::user()->company_id),
        ]);
    }

    // ════════════════════════════════════════════════════
    //  Everything directly under a Zone: sub-zones + spaces, merged
    //  GET /admin/production/layout/zones/{zone}/children
    // ════════════════════════════════════════════════════

    public function zoneChildren(Zone $zone): JsonResponse
    {
        $this->authorizeZone($zone);

        // FIX: Eager load active assignments and their employees
        $subZones = $zone->children()
            ->with(['assignments' => fn($q) => $q->active()->with('employee')])
            ->get(['id', 'name', 'is_active', 'sort_order']);
            
        $spaces = $zone->growingSpaces()->orderBy('sort_order')->get(['id', 'name', 'is_active', 'sort_order', 'capacity', 'growing_space_type_id']);

        $nodes = $this->formatZoneNodes($subZones)
            ->merge($this->formatSpaceNodes($spaces, Auth::user()->company_id))
            ->sortBy('sort_order')
            ->values();

        return response()->json(['success' => true, 'nodes' => $nodes]);
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — node formatting
    // ════════════════════════════════════════════════════

    /**
     * Zone nodes need their own children_summary (sub-zones + spaces
     * under each), computed with two grouped counts rather than N+1
     * queries per zone.
     */
    private function formatZoneNodes($zones)
    {
        if ($zones->isEmpty()) {
            return collect();
        }

        $companyId = Auth::user()->company_id;
        $zoneIds = $zones->pluck('id');

        $subZoneCounts = Zone::whereIn('parent_id', $zoneIds)
            ->selectRaw('parent_id, count(*) as aggregate_count')
            ->groupBy('parent_id')
            ->pluck('aggregate_count', 'parent_id');

        $spaceCounts = GrowingSpace::whereIn('zone_id', $zoneIds)
            ->selectRaw('zone_id, count(*) as aggregate_count')
            ->groupBy('zone_id')
            ->pluck('aggregate_count', 'zone_id');

        // FIX: Calculate Zone Occupancy dynamically by checking their inner spaces
        $zoneSpaces = GrowingSpace::whereIn('zone_id', $zoneIds)->get(['id', 'zone_id']);
        $spaceOccupancy = $companyId ? $this->placementService->occupancyMapForSpaces($zoneSpaces->pluck('id')->all(), $companyId) : [];
        
        $zoneOccupancyMap = [];
        foreach ($zoneSpaces as $space) {
            $zId = $space->zone_id;
            if (!isset($zoneOccupancyMap[$zId])) {
                $zoneOccupancyMap[$zId] = 0;
            }
            if (isset($spaceOccupancy[$space->id])) {
                $zoneOccupancyMap[$zId] += $spaceOccupancy[$space->id]['quantity_occupied'] ?? 0;
            }
        }

        return $zones->map(function (Zone $zone) use ($subZoneCounts, $spaceCounts, $zoneOccupancyMap) {
            $subZoneCount = (int) ($subZoneCounts[$zone->id] ?? 0);
            $spaceCount = (int) ($spaceCounts[$zone->id] ?? 0);

            $node = $this->formatNode(
                id: $zone->id,
                type: 'zone',
                name: $zone->name,
                sortOrder: $zone->sort_order,
                isActive: $zone->is_active,
                hasZones: $subZoneCount > 0,
                hasSpaces: $spaceCount > 0,
                totalChildren: $subZoneCount + $spaceCount,
            );

            // Append Calculated Occupancy
            $node['occupancy'] = [
                'quantity_occupied' => $zoneOccupancyMap[$zone->id] ?? 0,
            ];

            // Append Assigned Employees Names
            if ($zone->relationLoaded('assignments')) {
                $node['assignments'] = $zone->assignments->map(function($a) {
                    return $a->employee->full_name ?? $a->employee->name ?? ($a->employee->first_name . ' ' . $a->employee->last_name) ?? 'Worker';
                })->filter()->values();
            } else {
                $node['assignments'] = [];
            }

            return $node;
        });
    }

    /**
     * Growing Space nodes are always leaves — children_summary is a
     * fixed empty shape rather than omitted, so the frontend never has
     * to special-case "field missing" vs "field present but zero".
     */
    private function formatSpaceNodes($spaces, ?int $companyId = null)
    {
        if ($spaces->isEmpty()) {
            return collect();
        }

        // Single grouped query for occupancy across all spaces in this batch —
        // avoids N+1 when the grid renders many growing_space cards at once.
        $occupancy = $companyId
            ? $this->placementService->occupancyMapForSpaces($spaces->pluck('id')->all(), $companyId)
            : [];

        return $spaces->map(function (GrowingSpace $space) use ($occupancy) {
            $spaceOccupancy = $occupancy[$space->id] ?? ['batch_count' => 0, 'quantity_occupied' => 0];

            return [
                'id' => $space->id,
                'type' => 'growing_space',
                'name' => $space->name,
                'sort_order' => $space->sort_order,
                'is_active' => $space->is_active,
                'capacity' => $space->capacity,
                'growing_space_type_id' => $space->growing_space_type_id,
                'occupancy' => [
                    'batch_count' => $spaceOccupancy['batch_count'],
                    'quantity_occupied' => $spaceOccupancy['quantity_occupied'],
                ],
                'children_summary' => [
                    'has_zones' => false,
                    'has_spaces' => false,
                    'total_children' => 0,
                ],
            ];
        });
    }

    private function formatNode(
        int $id,
        string $type,
        string $name,
        int $sortOrder,
        bool $isActive,
        bool $hasZones,
        bool $hasSpaces,
        int $totalChildren,
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
            'children_summary' => [
                'has_zones' => $hasZones,
                'has_spaces' => $hasSpaces,
                'total_children' => $totalChildren,
            ],
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeSite(ProductionSite $site): void
    {
        if ($site->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }

    private function authorizeZone(Zone $zone): void
    {
        if ($zone->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}