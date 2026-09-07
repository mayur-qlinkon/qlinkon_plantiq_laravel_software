<?php

namespace App\Http\Controllers\Admin\Production;

use App\Enums\Production\BatchSourceType;
use App\Enums\Production\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreBatchAdjustmentRequest;
use App\Http\Requests\Admin\Production\StoreBatchHarvestRequest;
use App\Http\Requests\Admin\Production\StoreBatchLossRequest;
use App\Models\Production\GrowingSpace;
use App\Models\Production\PlantBatch;
use App\Services\Production\BatchHarvestService;
use App\Services\Production\BatchLossService;
use App\Services\Production\PlantBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

// ════════════════════════════════════════════════════
//  Admin-wide (company-scoped) versions of the dashboard's 4 quick
//  actions. Deliberately separate from MyTasksController — those are
//  worker self-service endpoints with a zone-assignment restriction
//  (an employee can only act on batches in their assigned zone); an
//  admin from the dashboard can act on any active batch in the company.
//  Move Batch has no method here — it reuses the existing
//  BatchPlacementController::place() (POST plant-batches/{plantBatch}/place),
//  which already returns JSON and needs no request-body changes.
// ════════════════════════════════════════════════════

class ProductionQuickActionController extends Controller
{
    // ════════════════════════════════════════════════════
    //  DROPDOWN DATA — feeds all 4 quick-action modals.
    //  GET admin/production/quick-actions/batches
    // ════════════════════════════════════════════════════

    public function batches(): JsonResponse
    {
        $batches = PlantBatch::where('status', BatchStatus::Active->value)
            ->with(['product', 'currentPlacement.growingSpace.zone'])
            ->orderBy('batch_code')
            ->get()
            ->map(function (PlantBatch $batch) {
                $placement = $batch->currentPlacement->first();

                return [
                    'id'                 => $batch->id,
                    'batch_code'         => $batch->batch_code,
                    'product_name'       => $batch->product->name ?? 'Unknown species',
                    'current_quantity'   => $batch->current_quantity,
                    // Lets the frontend restrict the Adjustment picker to
                    // Opening Stock batches only — mirrors the same rule
                    // this controller enforces server-side in adjustment().
                    'source_type'        => $batch->source_type,
                    'growing_space_id'   => $placement?->growing_space_id,
                    'growing_space_name' => $placement?->growingSpace?->name,
                    'zone_name'          => $placement?->growingSpace?->zone?->name,
                ];
            })
            ->values();

        return response()->json(['success' => true, 'batches' => $batches]);
    }

    // ════════════════════════════════════════════════════
    //  GET admin/production/quick-actions/spaces
    // ════════════════════════════════════════════════════

    public function spaces(): JsonResponse
    {
        $spaces = GrowingSpace::where('is_active', true)
            ->with('zone')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (GrowingSpace $space) => [
                'id'        => $space->id,
                'name'      => $space->name,
                'zone_name' => $space->zone->name ?? null,
            ])
            ->values();

        return response()->json(['success' => true, 'spaces' => $spaces]);
    }

    // ════════════════════════════════════════════════════
    //  HARVEST — AJAX
    //  POST admin/production/quick-actions/harvest
    // ════════════════════════════════════════════════════

    public function harvest(StoreBatchHarvestRequest $request, BatchHarvestService $batchHarvestService): JsonResponse
    {
        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);

        try {
            $batchHarvestService->record(
                batch: $plantBatch,
                quantityHarvested: (int) $request->quantity_harvested,
                warehouseId: $request->warehouse_id,
                notes: $request->notes,
                companyId: $plantBatch->company_id,
                userId: Auth::id(),
            );

            Log::info('[QuickAction] Harvest recorded', [
                'batch_id' => $plantBatch->id,
                'by'       => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Harvest recorded for {$plantBatch->batch_code}.",
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            // "Cannot harvest 500. Current quantity is only 72" — written for
            // the user by BatchHarvestService.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[QuickAction] Harvest failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not record this harvest. Please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  LOSS — AJAX
    //  POST admin/production/quick-actions/loss
    // ════════════════════════════════════════════════════

    public function loss(StoreBatchLossRequest $request, BatchLossService $batchLossService): JsonResponse
    {
        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);

        try {
            $batchLossService->record(
                batch: $plantBatch,
                quantityLost: (int) $request->quantity_lost,
                reason: $request->reason,
                notes: $request->notes,
                companyId: $plantBatch->company_id,
                userId: Auth::id(),
            );

            Log::info('[QuickAction] Loss recorded', [
                'batch_id' => $plantBatch->id,
                'by'       => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Loss recorded for {$plantBatch->batch_code}.",
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[QuickAction] Loss failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not record this loss. Please try again.'], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  ADJUSTMENT — AJAX
    //  Body-driven twin of PlantBatchController::adjustQuantity(), which
    //  stays URL-bound (PATCH plant-batches/{plantBatch}/adjust-quantity)
    //  for the batch edit page's own form. This one resolves the batch
    //  from plant_batch_id since the dashboard modal has no batch in its URL.
    //  POST admin/production/quick-actions/adjustment
    // ════════════════════════════════════════════════════

    public function adjustment(StoreBatchAdjustmentRequest $request, PlantBatchService $plantBatchService): JsonResponse
    {
        if (! $request->filled('plant_batch_id')) {
            return response()->json(['success' => false, 'message' => 'Please select a batch.'], 422);
        }

        $plantBatch = PlantBatch::findOrFail($request->plant_batch_id);

        if ($plantBatch->source_type !== BatchSourceType::OpeningStock->value) {
            return response()->json([
                'success' => false,
                'message' => 'Direct quantity adjustment is only available for Opening Stock batches.',
            ], 422);
        }

        try {
            $plantBatchService->adjustQuantityWithLedger(
                $plantBatch,
                (int) $request->current_quantity,
                $request->notes,
                Auth::id()
            );

            Log::info('[QuickAction] Quantity adjusted', [
                'batch_id' => $plantBatch->id,
                'by'       => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$plantBatch->batch_code} quantity adjusted.",
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);

        } catch (Throwable $e) {
            Log::error('[QuickAction] Adjustment failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not adjust this batch. Please try again.'], 500);
        }
    }
}