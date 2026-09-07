<?php

namespace App\Services;

use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\ChallanStatusHistory;
use App\Models\StockMovement;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;

class ChallanService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    // ──────────────────────────────────────────────────────────────
    // 🚀 CORE API
    // ──────────────────────────────────────────────────────────────

    /**
     * Safely creates a new Challan, its items, computes totals, and sets initial history.
     *
     * @param  array  $data  Validated data from StoreChallanRequest
     *
     * @throws \Exception
     */
    public function createChallan(array $data): Challan
    {
        return DB::transaction(function () use ($data) {
            $this->validateStockAvailability($data['direction'] ?? 'outward', $data['warehouse_id'] ?? null, $data['items'] ?? []);

            $companyId = Auth::user()->company_id;
            $userId = Auth::id();

            // 1. Generate guaranteed unique Challan Number
            $data['challan_number'] = Challan::generateNumber($companyId);
            $data['company_id'] = $companyId;
            $data['created_by'] = $userId;

            // 2. Extract items (Preventing mass-assignment bleed)
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            // 3. Create Header
            $challan = Challan::create($data);
            $challan->refresh();

            // 4. Model Computations (Rely on the Model's single source of truth)
            $challan->snapshotParty();
            $challan->setReturnDueDate(); // Will only set if is_returnable is true
            $challan->computeInterState();
            $challan->save(); // Save the computed snapshots

            // 5. Sync Line Items
            $this->syncLineItems($challan, $itemsData);

            // 6. Recalculate Header Totals based on strictly saved items
            $challan->syncTotals();

            // 7. Record Initial Status History
            ChallanStatusHistory::create([
                'challan_id' => $challan->id,
                'from_status' => null,
                'to_status' => $challan->status,
                'notes' => 'Challan created.',
                'changed_by_type' => 'admin',
                'changed_by' => $userId,
            ]);

            // 2. 🚀 The Conditional Movement Logic
            if ($challan->status === Challan::STATUS_DISPATCHED) {
                // Set on both the create and the update path so the field is
                // never null on a challan that has actually been dispatched.
                // It was previously written only when the status changed via
                // edit, so anything created straight as dispatched had no
                // record of who sent it.
                $challan->dispatched_by = $userId;
                $challan->save();
            }

            if ($challan->challan_type === Challan::TYPE_BRANCH_TRANSFER &&
                $challan->status === Challan::STATUS_DISPATCHED) {

                $this->processInternalBranchTransfer($challan);
            }

            return $challan->load('items');
        });
    }

    /**
     * Safely updates an existing Challan.
     * Includes an Iron Guard to prevent mutating items if it's already dispatched.
     *
     * @param  array  $data  Validated data from UpdateChallanRequest
     *
     * @throws \Exception
     */
    public function updateChallan(Challan $challan, array $data): Challan
    {
        return DB::transaction(function () use ($challan, $data) {

            $isEditingItems = isset($data['items']);
            
            // Extract items safely FIRST so we can validate them
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            // 🛡️ FIX: Validate stock before updating if items are being changed
            if (! empty($itemsData)) {
                // Use the new direction/warehouse if provided in request, otherwise use existing challan values
                $direction = $data['direction'] ?? $challan->direction;
                $warehouseId = $data['warehouse_id'] ?? $challan->warehouse_id;

                $this->validateStockAvailability($direction, $warehouseId, $itemsData);
            }

            // 🛡️ IRON GUARD: Prevent editing critical financial/inventory data if locked
            if (! $challan->is_editable && $isEditingItems) {
                throw new LogicException("Cannot modify items on a challan that is already {$challan->status}.");
            }

            // 1. Update Header (status transitions should ideally be handled via transitionTo(),
            // but we allow basic updates here if it's still a draft).
            $oldStatus = $challan->status;
            $data['updated_by'] = Auth::id();

            $challan->update($data);

            // 2. Re-compute Model Snapshots in case Party or States changed
            $challan->snapshotParty();
            $challan->computeInterState();
            $challan->save();

            // 3. Safely Sync Items (Only if it's editable)
            if ($challan->is_editable && ! empty($itemsData)) {
                $this->syncLineItems($challan, $itemsData);
                $challan->syncTotals();
            }

            // 4. Handle Status Transition & Logging if Status Changed from UI
            if (isset($data['status']) && $data['status'] !== $oldStatus) {

                // Reject impossible jumps. Challan::STATUS_TRANSITIONS has
                // always described the legal moves, but this path bypassed it
                // and wrote whatever the request asked for — a delivered
                // challan could be pushed back to draft.
                $allowed = Challan::STATUS_TRANSITIONS[$oldStatus] ?? [];

                if (! in_array($data['status'], $allowed, true)) {
                    throw new LogicException(
                        "Cannot move challan #{$challan->challan_number} from [{$oldStatus}] "
                        .'to ['.$data['status'].']. Allowed: ['.implode(', ', $allowed).'].'
                    );
                }

                if ($data['status'] === Challan::STATUS_DISPATCHED) {
                    $challan->dispatched_by = Auth::id();
                    $challan->save();

                    // The actual stock movement. Only createChallan() used to
                    // run this, so a challan saved as draft and dispatched
                    // later — from the edit form or the status dropdown —
                    // changed status without ever moving a single unit.
                    //
                    // Scope is deliberate: only branch transfers touch stock.
                    // Every other challan type stays document-only.
                    if ($challan->challan_type === Challan::TYPE_BRANCH_TRANSFER) {
                        $this->processInternalBranchTransfer($challan);
                    }
                }

                ChallanStatusHistory::create([
                    'challan_id' => $challan->id,
                    'from_status' => $oldStatus,
                    'to_status' => $data['status'],
                    'notes' => 'Status updated via edit form.',
                    'changed_by_type' => 'admin',
                    'changed_by' => Auth::id(),
                ]);
            }

            return $challan->load('items');
        });
    }

    // ──────────────────────────────────────────────────────────────
    // 🛠️ INTERNAL SYNC & MATH ENGINES
    // ──────────────────────────────────────────────────────────────

    /**
     * Engine for creating, updating, and safely deleting line items.
     * Calculates line values automatically to prevent frontend spoofing.
     */
    private function syncLineItems(Challan $challan, array $itemsData): void
    {
        $existingItemIds = $challan->items()->pluck('id')->toArray();
        $providedItemIds = array_filter(array_column($itemsData, 'id'));

        // 1. Safely Delete removed items
        $itemsToDelete = array_diff($existingItemIds, $providedItemIds);
        if (! empty($itemsToDelete)) {
            ChallanItem::whereIn('id', $itemsToDelete)->delete();
        }

        // 2. Upsert Items
        foreach ($itemsData as $itemData) {

            // 🛡️ Fallback safe defaults (Never trust the frontend entirely)
            $qtySent = (float) ($itemData['qty_sent'] ?? 0);
            $unitPrice = (float) ($itemData['unit_price'] ?? 0);

            // Auto-calculate line value on the backend
            $itemData['line_value'] = round($qtySent * $unitPrice, 2);

            // Snapshot the unit from the SKU when the form does not send one.
            // Invoice conversion requires a non-null unit_id on every line.
            if (empty($itemData['unit_id']) && ! empty($itemData['product_sku_id'])) {
                $itemData['unit_id'] = ProductSku::where('id', $itemData['product_sku_id'])
                    ->value('unit_id');
            }

            // 🔵 BATCH: Resolve batch_id from batch_number when batch tracking is enabled.
            // STRICT: No stock logic here — challan is document-only.
            if (batch_enabled() && ! empty($itemData['batch_number'])) {
                $batch = ProductBatch::where('batch_number', $itemData['batch_number'])
                    ->where('product_sku_id', $itemData['product_sku_id'] ?? null)
                    ->first();

                $itemData['batch_id'] = $batch?->id;

                // Prefer the authoritative expiry_date from the batch record
                if ($batch?->expiry_date) {
                    $itemData['expiry_date'] = $batch->expiry_date->format('Y-m-d');
                }
            } elseif (! batch_enabled()) {
                // Clear batch fields when feature is off to keep data clean
                $itemData['batch_id'] = null;
                $itemData['batch_number'] = null;
                $itemData['expiry_date'] = null;
            }

            if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                // Update existing
                ChallanItem::find($itemData['id'])->update($itemData);
            } else {
                // Create new
                $challan->items()->create($itemData);
            }
        }
    }

    /**
     * 🛡️ The Master Gatekeeper: Prevents overselling or adding zero-stock items.
     */
    private function validateStockAvailability(string $direction, ?int $warehouseId, array $items): void
    {
        // 1. Only validate stock if we are SENDING goods out.
        if ($direction !== Challan::DIRECTION_OUTWARD) {
            return;
        }

        if (! $warehouseId) {
            throw new InvalidArgumentException('A valid warehouse must be selected for outward dispatches.');
        }

        foreach ($items as $item) {
            $skuId = $item['product_sku_id'] ?? $item['sku_id'] ?? null;
            if (! $skuId) {
                continue;
            }

            $requestedQty = (float) ($item['qty_sent'] ?? 0);
            $productName = $item['product_name'] ?? 'Unknown Item';

            // 2. Query the physical stock
            $currentStock = (float) (ProductStock::where('warehouse_id', $warehouseId)
                ->where('product_sku_id', $skuId)
                ->value('qty') ?? 0);

            // 3. The Big Block: 10 > 7 = BOOM.
            if ($requestedQty > $currentStock) {
                throw new LogicException(
                    "Insufficient Stock! You are trying to send {$requestedQty} units of '{$productName}', but only {$currentStock} are available in the selected warehouse."
                );
            }
        }
    }

    /**
     * 🛰️ Branch Transfer Engine: Moves stock from Warehouse A to Warehouse B.
     */
    /**
     * 🛰️ Branch Transfer Engine: Moves stock from Warehouse A to Warehouse B.
     */
    private function processInternalBranchTransfer(Challan $challan): void
    {
        if (! $challan->to_warehouse_id) {
            throw new LogicException('Destination Warehouse is required for Branch Transfers.');
        }

        $challan->loadMissing(['items.productSku']);

        // Idempotency guard. Stock movements cannot be undone by re-running
        // this, so we ask the ledger itself whether this challan has already
        // moved goods rather than trusting the status field. Cheap insurance
        // against a retried request or a future code path calling this twice.
        $alreadyMoved = StockMovement::where('reference_type', Challan::class)
            ->where('reference_id', $challan->id)
            ->exists();

        if ($alreadyMoved) {
            Log::warning("Branch transfer skipped — stock already moved for Challan: {$challan->challan_number}");

            return;
        }

        Log::info("🚀 Starting Branch Transfer for Challan: {$challan->challan_number}");

        foreach ($challan->items as $item) {

            // 🌟 ROOT FIX: Use the actual database column name!
            if (! $item->product_sku_id) {
                Log::warning('Skipped item because product_sku_id is missing', ['item_id' => $item->id]);

                continue;
            }

            Log::info("📦 Moving SKU: {$item->product_sku_id} | QTY: {$item->qty_sent}");

            // A. DEDUCT from Source Warehouse (Store 1)
            $this->inventoryService->deductStock(
                sku: $item->productSku,
                warehouseId: $challan->warehouse_id,
                qty: $item->qty_sent,
                movementType: 'transfer_out',
                reference: $challan,
                batchId: null,
                batchNumber: null,
                unitCost: $item->unit_price ?? 0
            );

            // B. ADD to Destination Warehouse (Store 2)
            $this->inventoryService->addStock(
                sku: $item->productSku,
                warehouseId: $challan->to_warehouse_id,
                qty: $item->qty_sent,
                movementType: 'transfer_in',
                reference: $challan,
                batchId: null,
                batchNumber: null,
                unitCost: $item->unit_price ?? 0
            );

            Log::info("✅ Successfully moved {$item->qty_sent} units to Warehouse {$challan->to_warehouse_id}");
        }
    }
}
