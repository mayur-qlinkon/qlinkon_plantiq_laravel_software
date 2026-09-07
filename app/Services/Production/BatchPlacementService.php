<?php

namespace App\Services\Production;

use App\Enums\Production\BatchStatus;
use App\Models\Production\BatchPlacement;
use App\Models\Production\GrowingSpace;
use App\Models\Production\PlantBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BatchPlacementService
{
    // ════════════════════════════════════════════════════
    //  PLACE  (initial placement + movement — same operation)
    //
    //  Logic:
    //  1. Run all guards
    //  2. If batch already has an active placement → close it (movement)
    //  3. Insert new placement row with ended_at = null (active)
    //
    //  This single method replaces separate "place" and "move" actions
    //  because from the ledger's perspective, they are identical.
    // ════════════════════════════════════════════════════

    public function place(
        PlantBatch   $batch,
        GrowingSpace $space,
        int          $userId,
        ?string      $notes    = null,
        ?\DateTime   $placedAt = null,
    ): BatchPlacement {
        return DB::transaction(function () use ($batch, $space, $userId, $notes, $placedAt) {

            $this->assertBatchIsActive($batch);
            $this->assertSpaceIsActive($space);

            // Lock existing active placement row for the batch (if any) to
            // prevent concurrent moves from leaving two active rows.
            $current = BatchPlacement::where('plant_batch_id', $batch->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            $this->assertNotSameSpace($current, $space->id);

            // Close the current placement (movement case)
            if ($current !== null) {
                $current->update(['ended_at' => now()]);
            }

            // Insert new active placement
            return BatchPlacement::create([
                'company_id'      => $batch->company_id,
                'plant_batch_id'  => $batch->id,
                'growing_space_id'=> $space->id,
                'placed_at'       => $placedAt ?? now(),
                'ended_at'        => null,
                'placed_by'       => $userId,
                'notes'           => $notes,
            ]);
        });
    }

    // ════════════════════════════════════════════════════
    //  RELEASE — remove batch from its current space
    //
    //  No new placement is created. Use when:
    //  - Batch is being harvested/cancelled after placement
    //  - Batch needs to be un-placed before moving to a space
    //    that requires manual confirmation
    //
    //  Silent no-op if batch has no active placement, because
    //  callers (e.g. harvest service) should not have to pre-check.
    // ════════════════════════════════════════════════════

    public function release(PlantBatch $batch, int $userId, ?string $notes = null): void
    {
        DB::transaction(function () use ($batch, $userId, $notes) {
            $current = BatchPlacement::where('plant_batch_id', $batch->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                return; // No active placement — nothing to release
            }

            $update = ['ended_at' => now()];

            // Append release note to the placement row if provided
            if ($notes !== null) {
                $existing = $current->notes ? rtrim($current->notes) . "\n" : '';
                $update['notes'] = $existing . "[Released] {$notes}";
            }

            $current->update($update);
        });
    }

    // ════════════════════════════════════════════════════
    //  READ — current active placement for a batch
    //  Returns null if batch has not been placed yet.
    // ════════════════════════════════════════════════════

    public function currentPlacementFor(PlantBatch $batch): ?BatchPlacement
    {
        return BatchPlacement::with(['growingSpace.zone.site', 'placedBy'])
            ->where('plant_batch_id', $batch->id)
            ->whereNull('ended_at')
            ->first();
    }

    // ════════════════════════════════════════════════════
    //  READ — full movement history for a batch
    //  Ordered most-recent first. Includes the current active row.
    // ════════════════════════════════════════════════════

    public function historyFor(PlantBatch $batch): Collection
    {
        return BatchPlacement::with(['growingSpace.zone', 'placedBy'])
            ->where('plant_batch_id', $batch->id)
            ->orderBy('placed_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    // ════════════════════════════════════════════════════
    //  READ — all active placements currently in a growing space
    //  Includes the batch + product for display in occupancy panels.
    // ════════════════════════════════════════════════════

    public function activePlacementsInSpace(GrowingSpace $space): Collection
    {
        return BatchPlacement::with(['batch.product', 'placedBy'])
            ->where('growing_space_id', $space->id)
            ->whereNull('ended_at')
            ->orderBy('placed_at', 'asc')
            ->get();
    }

    // ════════════════════════════════════════════════════
    //  READ — occupancy summary for a single growing space
    //
    //  Returns:
    //  [
    //    'batch_count'        => int,   // number of distinct batches in space
    //    'quantity_occupied'  => int,   // sum of current_quantity of those batches
    //    'capacity'           => float, // from growing_space.capacity
    //    'capacity_remaining' => float, // capacity - quantity_occupied (can be negative if over-placed)
    //    'is_over_capacity'   => bool,
    //  ]
    //
    //  NOTE: capacity and quantity are in different logical units for some
    //  space types (e.g. area_sqm vs plant count). This calculation is
    //  meaningful for pots/tray_cells. Callers should check the space type's
    //  capacity_unit and show a warning when units differ.
    // ════════════════════════════════════════════════════

    public function spaceOccupancySummary(GrowingSpace $space): array
    {
        $activePlacements = $this->activePlacementsInSpace($space);

        $batchCount       = $activePlacements->count();
        $quantityOccupied = $activePlacements->sum(fn($p) => $p->batch?->current_quantity ?? 0);
        $capacity         = (float) $space->capacity;
        $remaining        = $capacity - $quantityOccupied;

        return [
            'batch_count'        => $batchCount,
            'quantity_occupied'  => $quantityOccupied,
            'capacity'           => $capacity,
            'capacity_remaining' => $remaining,
            'is_over_capacity'   => $remaining < 0,
            'placements'         => $activePlacements,
        ];
    }

    // ════════════════════════════════════════════════════
    //  READ — occupancy summary for multiple spaces
    //  Useful for the Layout Engine's space-grid view.
    //  Returns [growing_space_id => summary_array]
    // ════════════════════════════════════════════════════

    public function occupancyMapForSpaces(array $spaceIds, int $companyId): array
    {
        // Single query: aggregate active placements for all requested spaces
        $rows = BatchPlacement::query()
            ->join('production_plant_batches as b', 'b.id', '=', 'production_batch_placements.plant_batch_id')
            ->whereIn('production_batch_placements.growing_space_id', $spaceIds)
            ->whereNull('production_batch_placements.ended_at')
            ->whereNull('b.deleted_at')
            ->where('production_batch_placements.company_id', $companyId)
            ->groupBy('production_batch_placements.growing_space_id')
            ->selectRaw('
                production_batch_placements.growing_space_id,
                COUNT(production_batch_placements.id) as batch_count,
                SUM(b.current_quantity)               as quantity_occupied
            ')
            ->get()
            ->keyBy('growing_space_id');

        // Build a map keyed by space ID — spaces with no placements get zero values
        $map = [];
        foreach ($spaceIds as $spaceId) {
            $row       = $rows->get($spaceId);
            $map[$spaceId] = [
                'batch_count'       => $row ? (int) $row->batch_count       : 0,
                'quantity_occupied' => $row ? (int) $row->quantity_occupied  : 0,
            ];
        }

        return $map;
    }

    // ════════════════════════════════════════════════════
    //  GUARDS
    // ════════════════════════════════════════════════════

    private function assertBatchIsActive(PlantBatch $batch): void
    {
        if (!$batch->isActive()) {
            throw new InvalidArgumentException(
                "Batch '{$batch->batch_code}' is {$batch->status_label} and cannot be placed. Only Active batches can be placed."
            );
        }
    }

    private function assertSpaceIsActive(GrowingSpace $space): void
    {
        if (!$space->is_active) {
            throw new RuntimeException(
                "Growing space '{$space->name}' is inactive and cannot accept placements."
            );
        }
    }

    private function assertNotSameSpace(?BatchPlacement $current, int $newSpaceId): void
    {
        if ($current !== null && $current->growing_space_id === $newSpaceId) {
            throw new InvalidArgumentException(
                'Batch is already placed in this growing space. No movement needed.'
            );
        }
    }
}