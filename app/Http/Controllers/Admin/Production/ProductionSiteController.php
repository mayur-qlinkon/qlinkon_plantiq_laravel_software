<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreProductionSiteRequest;
use App\Http\Requests\Admin\Production\UpdateProductionSiteRequest;
use App\Models\Production\ProductionSite;
use App\Services\Production\ProductionSiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProductionSiteController extends Controller
{
    public function __construct(protected ProductionSiteService $service)
    {
    }

    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/sites
    // ════════════════════════════════════════════════════

    public function store(StoreProductionSiteRequest $request): JsonResponse
    {
        try {
            $site = $this->service->create(array_merge(
                $request->validated(),
                ['company_id' => Auth::user()->company_id]
            ));

            Log::info('[ProductionSite] Created', ['site_id' => $site->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production site \"{$site->name}\" created.",
                'site' => $site,
            ]);

        } catch (Throwable $e) {
            Log::error('[ProductionSite] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create production site.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/production/sites/{site}
    // ════════════════════════════════════════════════════

    public function update(UpdateProductionSiteRequest $request, ProductionSite $site): JsonResponse
    {
        $this->authorizeSite($site);

        try {
            $updated = $this->service->update($site, $request->validated());

            Log::info('[ProductionSite] Updated', ['site_id' => $site->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production site \"{$updated->name}\" updated.",
                'site' => $updated,
            ]);

        } catch (Throwable $e) {
            Log::error('[ProductionSite] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update production site.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/production/sites/{site}
    // ════════════════════════════════════════════════════

    public function destroy(ProductionSite $site): JsonResponse
    {
        $this->authorizeSite($site);

        try {
            $name = $site->name;
            $this->service->delete($site);

            Log::info('[ProductionSite] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Production site \"{$name}\" and everything under it has been deleted.",
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[ProductionSite] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete production site.'], 500);
        }
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
}