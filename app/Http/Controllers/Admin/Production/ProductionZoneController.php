<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreZoneRequest;
use App\Http\Requests\Admin\Production\UpdateZoneRequest;
use App\Models\Production\Zone;
use App\Services\Production\ZoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProductionZoneController extends Controller
{
    public function __construct(protected ZoneService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/zones
    // ════════════════════════════════════════════════════

    public function store(StoreZoneRequest $request): JsonResponse
    {
        try {
            $zone = $this->service->create(array_merge(
                $request->validated(),
                ['company_id' => Auth::user()->company_id]
            ));

            Log::info('[Zone] Created', ['zone_id' => $zone->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Zone \"{$zone->name}\" created.",
                'zone' => $zone,
            ]);

        } catch (RuntimeException $e) {
            // Guard-clause failures inside the service (cross-site parent, etc.)
            // are user-facing validation problems, not server errors.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[Zone] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create zone.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/production/zones/{zone}
    // ════════════════════════════════════════════════════

    public function update(UpdateZoneRequest $request, Zone $zone): JsonResponse
    {
        $this->authorizeZone($zone);

        try {
            $updated = $this->service->update($zone, $request->validated());

            Log::info('[Zone] Updated', ['zone_id' => $zone->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Zone \"{$updated->name}\" updated.",
                'zone' => $updated,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[Zone] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update zone.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/production/zones/{zone}
    // ════════════════════════════════════════════════════

    public function destroy(Zone $zone): JsonResponse
    {
        $this->authorizeZone($zone);

        try {
            $name = $zone->name;
            $this->service->delete($zone);

            Log::info('[Zone] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Zone \"{$name}\" and everything under it has been deleted.",
            ]);

        } catch (Throwable $e) {
            Log::error('[Zone] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete zone.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeZone(Zone $zone): void
    {
        if ($zone->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}