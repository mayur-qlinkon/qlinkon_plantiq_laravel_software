<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Production\StoreBatchPlacementRequest;
use App\Models\Production\GrowingSpace;
use App\Models\Production\PlantBatch;
use App\Services\Production\BatchPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BatchPlacementController extends Controller
{
    public function __construct(
        protected BatchPlacementService $placementService
    ) {}

    // ════════════════════════════════════════════════════
    //  PLACE / MOVE — AJAX
    //
    //  Single endpoint handles both:
    //  - Initial placement (no previous active placement)
    //  - Movement (has an active placement → auto-closed + new row inserted)
    //
    //  POST admin/production/plant-batches/{plantBatch}/place
    // ════════════════════════════════════════════════════

    public function place(StoreBatchPlacementRequest $request, PlantBatch $plantBatch): JsonResponse
    {
        $validated = $request->validated();

        $space = GrowingSpace::findOrFail($validated['growing_space_id']);

        try {
            $placement = $this->placementService->place(
                batch:    $plantBatch,
                space:    $space,
                userId:   Auth::id(),
                notes:    $validated['notes'] ?? null,
                placedAt: isset($validated['placed_at'])
                    ? new \DateTime($validated['placed_at'])
                    : null,
            );

            // Reload with relations for the response
            $placement->load(['growingSpace.zone', 'placedBy']);

            $isMove = $placement->wasRecentlyCreated
                && $plantBatch->placements()->whereNotNull('ended_at')->exists();

            Log::info('[BatchPlacement] ' . ($isMove ? 'Moved' : 'Placed'), [
                'batch_id'         => $plantBatch->id,
                'batch_code'       => $plantBatch->batch_code,
                'growing_space_id' => $space->id,
                'placement_id'     => $placement->id,
                'by'               => Auth::id(),
            ]);

            return response()->json([
                'success'   => true,
                'message'   => $isMove
                    ? "Batch {$plantBatch->batch_code} moved to {$space->name}."
                    : "Batch {$plantBatch->batch_code} placed in {$space->name}.",
                'placement' => $this->formatPlacement($placement),
            ]);

        } catch (InvalidArgumentException|RuntimeException $e) {
            // Domain guards from BatchPlacementService — these messages are
            // written for the user ("Only Active batches can be placed").
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            // Anything else is a QueryException or similar, whose message
            // carries SQL, table names and connection details.
            Log::error('[BatchPlacement] Place failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not place this batch. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  RELEASE — AJAX
    //  Remove batch from its current space (no new destination).
    //
    //  DELETE admin/production/plant-batches/{plantBatch}/release
    // ════════════════════════════════════════════════════

    public function release(Request $request, PlantBatch $plantBatch): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->placementService->release(
                batch:  $plantBatch,
                userId: Auth::id(),
                notes:  $request->notes,
            );

            Log::info('[BatchPlacement] Released', [
                'batch_id'   => $plantBatch->id,
                'batch_code' => $plantBatch->batch_code,
                'by'         => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$plantBatch->batch_code} removed from its current space.",
            ]);

        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {
            Log::error('[BatchPlacement] Release failed', [
                'batch_id' => $plantBatch->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not release this batch. Please try again.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  CURRENT — AJAX
    //  Return the active placement for a batch.
    //  Used by the batch show page to display current location.
    //
    //  GET admin/production/plant-batches/{plantBatch}/placement/current
    // ════════════════════════════════════════════════════

    public function current(PlantBatch $plantBatch): JsonResponse
    {
        $placement = $this->placementService->currentPlacementFor($plantBatch);

        return response()->json([
            'success'   => true,
            'placement' => $placement ? $this->formatPlacement($placement) : null,
        ]);
    }

    // ════════════════════════════════════════════════════
    //  HISTORY — AJAX
    //  Full movement history for a batch.
    //
    //  GET admin/production/plant-batches/{plantBatch}/placements
    // ════════════════════════════════════════════════════

    public function history(PlantBatch $plantBatch): JsonResponse
    {
        $history = $this->placementService->historyFor($plantBatch);

        return response()->json([
            'success' => true,
            'history' => $history->map(fn($p) => $this->formatPlacement($p))->values(),
        ]);
    }

    // ════════════════════════════════════════════════════
    //  SPACE OCCUPANCY — AJAX
    //  Used by the Layout Engine view to show what's in a space.
    //
    //  GET admin/production/growing-spaces/{growingSpace}/occupancy
    // ════════════════════════════════════════════════════

    public function spaceOccupancy(GrowingSpace $growingSpace): JsonResponse
    {
        try {
            $summary = $this->placementService->spaceOccupancySummary($growingSpace);

            return response()->json([
                'success' => true,
                'summary' => [
                    'batch_count'        => $summary['batch_count'],
                    'quantity_occupied'  => $summary['quantity_occupied'],
                    'capacity'           => $summary['capacity'],
                    'capacity_remaining' => $summary['capacity_remaining'],
                    'is_over_capacity'   => $summary['is_over_capacity'],
                    'placements'         => $summary['placements']->map(fn($p) => $this->formatPlacement($p))->values(),
                ],
            ]);

        } catch (Throwable $e) {
            // Read-only endpoint — there is no domain exception to surface,
            // so nothing here is ever safe to echo back.
            Log::error('[BatchPlacement] Space occupancy failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not load occupancy for this space.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — consistent JSON shape for placement rows
    // ════════════════════════════════════════════════════

    private function formatPlacement($placement): array
    {
        return [
            'id'           => $placement->id,
            'is_active'    => $placement->isActive(),
            'placed_at'    => $placement->placed_at?->toIso8601String(),
            'ended_at'     => $placement->ended_at?->toIso8601String(),
            'notes'        => $placement->notes,
            'placed_by'    => $placement->placedBy?->name,
            'growing_space' => $placement->growingSpace ? [
                'id'   => $placement->growingSpace->id,
                'name' => $placement->growingSpace->name,
                'zone' => $placement->growingSpace->zone?->name,
            ] : null,
            'batch' => $placement->batch ? [
                'id'               => $placement->batch->id,
                'batch_code'       => $placement->batch->batch_code,
                'current_quantity' => $placement->batch->current_quantity,
                'product_name'     => $placement->batch->product?->name,
            ] : null,
        ];
    }
}