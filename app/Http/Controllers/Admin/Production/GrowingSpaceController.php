<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreGrowingSpaceRequest;
use App\Http\Requests\Admin\Production\UpdateGrowingSpaceRequest;
use App\Models\Production\GrowingSpace;
use App\Services\Production\BatchPlacementService;
use App\Services\Production\GrowingSpaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GrowingSpaceController extends Controller
{
    public function __construct(
        protected GrowingSpaceService $service,
        protected BatchPlacementService $placementService,
    ) {
    }

    // ════════════════════════════════════════════════════
    //  SHOW — full detail for the Inspector panel
    //  GET /admin/production/growing-spaces/{growing_space}
    // ════════════════════════════════════════════════════
    public function show(GrowingSpace $growingSpace): JsonResponse
    {
        $this->authorizeSpace($growingSpace);

        $growingSpace->load('type:id,name,capacity_unit,custom_unit_label');

        // IMPORTANT: never expose the `type` relation under the key "type" —
        // the frontend tree node already uses `type` to mean the node kind
        // ("site" | "zone" | "growing_space"). Object.assign() would silently
        // clobber that string with this object. Use `space_type` instead.
        $space = $growingSpace->toArray();
        $space['space_type'] = $space['type'] ?? null;
        unset($space['type']);

        // Embed batch-level occupancy so the canvas can render placed
        // batches without a second round-trip.
        $summary = $this->placementService->spaceOccupancySummary($growingSpace);

        return response()->json([
            'success' => true,
            'space' => $space,
            'occupancy' => [
                'batch_count'       => $summary['batch_count'],
                'quantity_occupied' => $summary['quantity_occupied'],
                'is_over_capacity'  => $summary['is_over_capacity'],
                'batches'           => $summary['placements']->map(fn ($placement) => [
                    'placement_id'     => $placement->id,
                    'batch_id'         => $placement->batch->id,
                    'batch_code'       => $placement->batch->batch_code,
                    'product_name'     => $placement->batch->product->name,
                    'current_quantity' => $placement->batch->current_quantity,
                ])->values(),
            ],
        ]);
    }
    // ════════════════════════════════════════════════════
    //  STORE
    //  POST /admin/production/growing-spaces
    // ════════════════════════════════════════════════════

    public function store(StoreGrowingSpaceRequest $request): JsonResponse
    {
        try {
            $space = $this->service->create(array_merge(
                $request->validated(),
                ['company_id' => Auth::user()->company_id]
            ));

            Log::info('[GrowingSpace] Created', ['space_id' => $space->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space \"{$space->name}\" created.",
                'space' => $space,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[GrowingSpace] Store failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to create growing space.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE
    //  PUT /admin/production/growing-spaces/{growing_space}
    // ════════════════════════════════════════════════════

    public function update(UpdateGrowingSpaceRequest $request, GrowingSpace $growingSpace): JsonResponse
    {
        $this->authorizeSpace($growingSpace);

        try {
            $updated = $this->service->update($growingSpace, $request->validated());

            Log::info('[GrowingSpace] Updated', ['space_id' => $growingSpace->id, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space \"{$updated->name}\" updated.",
                'space' => $updated,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[GrowingSpace] Update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to update growing space.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  DESTROY
    //  DELETE /admin/production/growing-spaces/{growing_space}
    // ════════════════════════════════════════════════════

    public function destroy(GrowingSpace $growingSpace): JsonResponse
    {
        $this->authorizeSpace($growingSpace);

        try {
            $name = $growingSpace->name;
            $this->service->delete($growingSpace);

            Log::info('[GrowingSpace] Deleted', ['name' => $name, 'by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => "Growing space \"{$name}\" deleted.",
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[GrowingSpace] Delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete growing space.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Tenant isolation
    // ════════════════════════════════════════════════════

    private function authorizeSpace(GrowingSpace $growingSpace): void
    {
        if ($growingSpace->company_id !== Auth::user()->company_id) {
            abort(403, 'Access denied.');
        }
    }
}