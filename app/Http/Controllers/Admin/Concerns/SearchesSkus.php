<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\SkuSearchContext;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Shared HTTP plumbing for a module's SKU search endpoint.
 *
 * Each module exposes its own route so the endpoint sits behind that module's
 * `module:` gate and permission middleware. The plumbing — reading the request,
 * turning a rejected warehouse into a 422 — is identical everywhere, so it
 * lives here; only the context differs per module, and that is declared by the
 * controller rather than accepted from the request.
 */
trait SearchesSkus
{
    protected function respondWithSkuSearch(
        Request $request,
        ProductService $productService,
        SkuSearchContext $context
    ): JsonResponse {

        $term = (string) $request->query('term', '');

        // Warehouse is read here but never trusted — the service validates it
        // against the stores the user may act on before it reaches a query.
        $warehouseId = $request->query('warehouse_id');
        $warehouseId = $warehouseId !== null ? (int) $warehouseId : null;

        try {
            $results = $productService->searchSellableSkus(
                term:        $term,
                companyId:   Auth::user()->company_id,
                context:     $context,
                warehouseId: $warehouseId,
            );
        } catch (InvalidArgumentException $e) {
            // Caller error: no warehouse chosen, or one they cannot access.
            // Safe to surface, and the pickers display it directly.
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('SKU search failed', [
                'context'      => $context->value,
                'company_id'   => Auth::user()->company_id,
                'user_id'      => Auth::id(),
                'warehouse_id' => $warehouseId,
                'message'      => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Could not search items. Please try again.',
            ], 500);
        }

        // A bare array keeps the response shape identical to the endpoint this
        // replaces, so the existing pickers need no JavaScript changes.
        return response()->json($results);
    }
}