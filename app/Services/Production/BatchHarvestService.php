<?php

namespace App\Services\Production;

use App\Enums\Production\BatchHarvestLogAction;
use App\Enums\Production\HarvestStatus;
use App\Models\Production\BatchHarvest;
use App\Models\Production\BatchHarvestLog;
use App\Models\Production\PlantBatch;
use App\Models\ProductBatch;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BatchHarvestService
{
    public function __construct(
        protected PlantBatchService $plantBatchService,
        protected InventoryService $inventoryService
    ) {
    }

    // ════════════════════════════════════════════════════
    //  RECORD — worker "Report Harvest" action ka core write path
    //  Decrement + ledger row ek hi transaction me — dono ya to
    //  saath honge ya bilkul nahi honge. Partial harvest allowed —
    //  batch sirf tabhi close hota hai jab current_quantity 0 ho jaye
    //  (handled inside decrementQuantity()).
    // ════════════════════════════════════════════════════

    public function record(
        PlantBatch $batch,
        int $quantityHarvested,
        ?int $warehouseId,
        ?string $notes,
        int $companyId,
        int $userId
    ): BatchHarvest {
        return DB::transaction(function () use ($batch, $quantityHarvested, $warehouseId, $notes, $companyId, $userId) {
            // Row-locks the batch, validates against current_quantity, auto-closes
            // the batch at zero. Throws RuntimeException on insufficient quantity —
            // let it bubble up so the controller can turn it into a friendly 422.
            $this->plantBatchService->adjustQuantity($batch, -$quantityHarvested);

            return BatchHarvest::create([
                'company_id' => $companyId,
                'plant_batch_id' => $batch->id,
                'quantity_harvested' => $quantityHarvested,
                'harvested_on' => now(),
                'harvested_by' => $userId,
                'warehouse_id' => $warehouseId,
                'notes' => $notes,
                // Reported by worker — NOT yet in sellable inventory.
                // Stock only moves once an admin/warehouse user approves via receive().
                'status' => HarvestStatus::Pending->value,
            ]);
        });
    }

    // ════════════════════════════════════════════════════
    //  RECEIVE — warehouse/admin "Approve" action on a pending harvest lot.
    //  This is the ONLY place a harvest touches POS-side inventory.
    //  quantity_harvested (worker-reported) stays untouched — received_quantity
    //  is stored separately so any variance is preserved as data, not overwritten.
    // ════════════════════════════════════════════════════

    public function receive(
        BatchHarvest $harvest,
        int $receivedQuantity,
        int $warehouseId,
        int $userId
    ): BatchHarvest {
        if (! $harvest->isPending()) {
            throw new RuntimeException('This harvest lot has already been received.');
        }

        $sku = $harvest->batch->sku;

        if (! $sku) {
            throw new RuntimeException('This batch has no linked product SKU — cannot receive into inventory.');
        }

        return DB::transaction(function () use ($harvest, $receivedQuantity, $warehouseId, $userId, $sku) {
            // Claim the lot before any stock moves. The isPending() check above
            // runs outside this transaction, so two concurrent requests both
            // passed it and both reached addStock() — one lot, stock added
            // twice. This UPDATE only matches while the row is still pending,
            // so exactly one caller can claim it; the loser sees 0 rows.
            //
            // Claiming first also means a rollback below undoes the claim.
            $claimed = BatchHarvest::whereKey($harvest->getKey())
                ->where('status', HarvestStatus::Pending->value)
                ->update([
                    'status' => HarvestStatus::Received->value,
                    'received_quantity' => $receivedQuantity,
                    'warehouse_id' => $warehouseId,
                    'received_by' => $userId,
                    'received_at' => now(),
                ]);

            if ($claimed === 0) {
                throw new RuntimeException('This harvest lot has already been received.');
            }

            $batchId = null;
            $batchNumber = null;

            // Mirrors PurchaseService::receiveStock() — only create a ProductBatch
            // (FEFO lot tracking) when the company has batch tracking enabled.
            if (batch_enabled()) {
                $productBatch = ProductBatch::create([
                    'company_id' => $harvest->company_id,
                    'product_sku_id' => $sku->id,
                    'warehouse_id' => $warehouseId,
                    'supplier_id' => null,
                    'batch_number' => 'H-' . now()->format('ym') . '-' . strtoupper(Str::random(5)),
                    'manufacturing_date' => $harvest->harvested_on,
                    'expiry_date' => null,
                    'purchase_price' => $sku->cost,
                    'qty' => $receivedQuantity,
                    'remaining_qty' => $receivedQuantity,
                ]);

                $batchId = $productBatch->id;
                $batchNumber = $productBatch->batch_number;
            }

            $this->inventoryService->addStock(
                sku: $sku,
                warehouseId: $warehouseId,
                qty: $receivedQuantity,
                movementType: 'production_harvest',
                reference: $harvest,
                batchId: $batchId,
                batchNumber: $batchNumber,
                unitCost: $sku->cost,
            );

            // Status and receiving details were already written by the claim.

            return $harvest->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  CORRECT QUANTITY — Admin-only correction on a still-Pending lot.
    //  Delta is applied to the batch in the OPPOSITE direction of the
    //  harvest change: reducing the harvest qty restores stock to the
    //  batch; increasing it takes more stock from the batch (and can
    //  fail with RuntimeException if the batch doesn't have enough left).
    // ════════════════════════════════════════════════════

    public function correctQuantity(
        BatchHarvest $harvest,
        int $newQuantity,
        ?string $remarks,
        int $userId
    ): BatchHarvest {
        if (! $harvest->isPending()) {
            throw new RuntimeException('Only pending harvest lots can be corrected.');
        }

        if ($newQuantity <= 0) {
            throw new InvalidArgumentException('New quantity must be greater than zero — use Cancel to zero out a lot.');
        }

        $oldQuantity = $harvest->quantity_harvested;

        if ($newQuantity === $oldQuantity) {
            throw new InvalidArgumentException('New quantity is the same as the current quantity.');
        }

        return DB::transaction(function () use ($harvest, $oldQuantity, $newQuantity, $remarks, $userId) {
            // Correcting a lot that another request is receiving or cancelling
            // would apply a batch delta against a lot that is no longer
            // pending. Re-assert the status under the row lock; unlike
            // receive() and cancel() this is not a claim, since the lot must
            // stay pending afterwards.
            $stillPending = BatchHarvest::whereKey($harvest->getKey())
                ->where('status', HarvestStatus::Pending->value)
                ->lockForUpdate()
                ->exists();

            if (! $stillPending) {
                throw new RuntimeException('Only pending harvest lots can be corrected.');
            }

            // Batch delta is the inverse of the harvest change:
            // qty reduced (200→20)  → batch delta = +180 (restore)
            // qty increased (20→200) → batch delta = -180 (take more; may throw if insufficient)
            $this->plantBatchService->adjustQuantity($harvest->batch, $oldQuantity - $newQuantity);

            $harvest->update(['quantity_harvested' => $newQuantity]);

            BatchHarvestLog::create([
                'harvest_id' => $harvest->id,
                'action' => BatchHarvestLogAction::QuantityCorrected->value,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'remarks' => $remarks,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            return $harvest->fresh();
        });
    }

    // ════════════════════════════════════════════════════
    //  CANCEL — Admin-only. Full quantity_harvested goes back to the
    //  batch, lot moves to Cancelled and can never be received.
    // ════════════════════════════════════════════════════

    public function cancel(BatchHarvest $harvest, ?string $remarks, int $userId): BatchHarvest
    {
        if (! $harvest->isPending()) {
            throw new RuntimeException('Only pending harvest lots can be cancelled.');
        }

        return DB::transaction(function () use ($harvest, $remarks, $userId) {
            // Same claim as receive(), and for the same reason. Cancelling
            // returns the plants to the batch while receiving pushes them into
            // inventory — a race between the two counted the same plants in
            // both places and left the lot marked cancelled after its stock
            // had already moved.
            $claimed = BatchHarvest::whereKey($harvest->getKey())
                ->where('status', HarvestStatus::Pending->value)
                ->update(['status' => HarvestStatus::Cancelled->value]);

            if ($claimed === 0) {
                throw new RuntimeException('Only pending harvest lots can be cancelled.');
            }

            $oldQuantity = $harvest->quantity_harvested;

            // Full restore — batch gets back everything this lot took.
            $this->plantBatchService->adjustQuantity($harvest->batch, $oldQuantity);

            BatchHarvestLog::create([
                'harvest_id' => $harvest->id,
                'action' => BatchHarvestLogAction::Cancelled->value,
                'old_quantity' => $oldQuantity,
                'new_quantity' => 0,
                'remarks' => $remarks,
                'performed_by' => $userId,
                'performed_at' => now(),
            ]);

            return $harvest->fresh();
        });
    }
}