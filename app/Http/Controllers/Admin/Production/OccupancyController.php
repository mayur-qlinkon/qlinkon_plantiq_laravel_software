<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\GrowingSpace;
use App\Models\Production\ProductionSite;
use App\Models\Production\Zone;
use App\Services\Production\OccupancyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OccupancyController extends Controller
{
    public function __construct(protected OccupancyService $occupancyService)
    {
    }

    // GET /admin/production/sites/{site}/occupancy
    public function forSite(ProductionSite $site): JsonResponse
    {
        $this->authorizeSite($site);

        return response()->json([
            'success'   => true,
            'occupancy' => $this->occupancyService->siteRollup($site),
        ]);
    }

    // GET /admin/production/zones/{zone}/occupancy
    public function forZone(Zone $zone): JsonResponse
    {
        $this->authorizeZone($zone);

        return response()->json([
            'success'   => true,
            'occupancy' => $this->occupancyService->zoneRollup($zone),
        ]);
    }

// GET /admin/production/growing-spaces/occupancy-map?ids[]=1&ids[]=2
    public function map(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json(['success' => true, 'occupancy' => []]);
        }

        // Tenant isolation — only IDs this company actually owns are queried.
        $ownedIds = GrowingSpace::whereIn('id', $ids)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->all();

        return response()->json([
            'success'   => true,
            'occupancy' => $this->occupancyService->spacesMap($ownedIds, $companyId),
        ]);
    }

    private function authorizeSite(ProductionSite $site): void    {
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