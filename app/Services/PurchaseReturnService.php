<?php

namespace App\Services;

use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseReturnService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Create a new Purchase Return and process stock if status is 'returned'.
     */
    public function createPurchaseReturn(array $data): PurchaseReturn
    {
        return DB::transaction(function () use ($data) {
            $companyId = Auth::user()->company_id;

            // 0. Nothing may be returned beyond what the purchase actually holds
            $this->assertReturnableQuantities($data['items'], (int) $data['purchase_id']);

            // 1. Perform Financial Math
            $financials = $this->calculateFinancials($data['items'], $data['tax_type'], (float) ($data['discount_amount'] ?? 0));

            // 2. Generate Unique Return Number
            $returnNumber = $this->generateReturnNumber($companyId);

            // 3. Create the Header Record
            $purchaseReturn = PurchaseReturn::create([
                'company_id' => $companyId,
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'purchase_id' => $data['purchase_id'],
                'created_by' => Auth::id(),
                'return_number' => $returnNumber,
                'supplier_credit_note_number' => $data['supplier_credit_note_number'] ?? null,
                'return_date' => $data['return_date'],
                'status' => $data['status'],
                'tax_type' => $data['tax_type'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,

                // Inject calculated financials
                'subtotal' => $financials['subtotal'],
                'discount_amount' => $financials['discount_amount'],
                'taxable_amount' => $financials['taxable_amount'],
                'cgst_amount' => $financials['cgst_amount'],
                'sgst_amount' => $financials['sgst_amount'],
                'igst_amount' => $financials['igst_amount'],
                'tax_amount' => $financials['tax_amount'],
                'total_amount' => $financials['total_amount'],
            ]);

            // 4. Create Line Items
            $this->createLineItems($purchaseReturn, $financials['items']);

            // 5. If status is 'returned', immediately deduct from inventory
            if ($purchaseReturn->status === 'returned') {
                $this->processStockDeduction($purchaseReturn);
            }

            return $purchaseReturn;
        });
    }

    /**
     * Update an existing Purchase Return.
     */
    public function updatePurchaseReturn(PurchaseReturn $purchaseReturn, array $data): PurchaseReturn
    {
        // 🛡️ ERP GUARD: Service-level protection against modifying finalized records
        if ($purchaseReturn->status === 'returned') {
            throw new \Exception('Cannot modify a Purchase Return that has already been dispatched to the supplier.');
        }

        return DB::transaction(function () use ($purchaseReturn, $data) {

            // 0. Same cap as on create. This draft's own lines are excluded, or
            // it would be measured against a reservation it made itself.
            $this->assertReturnableQuantities(
                $data['items'],
                (int) $purchaseReturn->purchase_id,
                $purchaseReturn->id
            );

            // 1. Perform Financial Math
            $financials = $this->calculateFinancials($data['items'], $data['tax_type'], (float) ($data['discount_amount'] ?? 0));

            // 2. Update Header
            $purchaseReturn->update([
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'return_date' => $data['return_date'],
                'supplier_credit_note_number' => $data['supplier_credit_note_number'] ?? null,
                'status' => $data['status'],
                'tax_type' => $data['tax_type'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,

                'subtotal' => $financials['subtotal'],
                'discount_amount' => $financials['discount_amount'],
                'taxable_amount' => $financials['taxable_amount'],
                'cgst_amount' => $financials['cgst_amount'],
                'sgst_amount' => $financials['sgst_amount'],
                'igst_amount' => $financials['igst_amount'],
                'tax_amount' => $financials['tax_amount'],
                'total_amount' => $financials['total_amount'],
            ]);

            // 3. Sync Line Items (Delete missing, update existing, create new)
            $this->syncLineItems($purchaseReturn, $financials['items']);

            // 4. If status CHANGED to 'returned', deduct stock
            if ($purchaseReturn->status === 'returned') {
                $this->processStockDeduction($purchaseReturn);
            }

            return $purchaseReturn;
        });
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────
        /**
     * Returnable quantity per purchase line, keyed by purchase_item_id.
     *
     * The single definition of "available", shared by the return form, the edit
     * screen and the write guard below. Those three each carried their own
     * arithmetic and disagreed: two counted ordered quantity and swept drafts in
     * with finalised returns, while the guard counted only what had shipped.
     *
     *   quantity_received  — only goods that actually arrived can go back
     *   quantity_returned  — finalised returns, the ones that moved stock
     *   draft holds        — not yet shipped, but reserved so a user cannot
     *                        build a second return they could never finalise
     *
     * $ignoreReturnId excludes the draft being edited from its own reservation.
     */
    public function availableQuantities(int $purchaseId, ?int $ignoreReturnId = null): array
    {
        $items = PurchaseItem::where('purchase_id', $purchaseId)
            ->get(['id', 'quantity_received', 'quantity_returned']);

        if ($items->isEmpty()) {
            return [];
        }

        // Cancelled returns never reserve anything; finalised ones are already
        // counted in quantity_returned, so only drafts are added here.
        $draftHolds = PurchaseReturnItem::whereIn('purchase_item_id', $items->pluck('id'))
            ->whereHas('purchaseReturn', function ($q) use ($ignoreReturnId) {
                $q->where('status', 'draft');

                if ($ignoreReturnId) {
                    $q->where('id', '!=', $ignoreReturnId);
                }
            })
            ->selectRaw('purchase_item_id, SUM(quantity) as held')
            ->groupBy('purchase_item_id')
            ->pluck('held', 'purchase_item_id');

        return $items->mapWithKeys(function ($item) use ($draftHolds) {
            $available = (float) $item->quantity_received
                - (float) $item->quantity_returned
                - (float) $draftHolds->get($item->id, 0);

            return [$item->id => round(max(0, $available), 4)];
        })->all();
    }

    /**
     * Reject lines that return more than the purchase can still give back.
     *
     * quantity was checked only for being above zero — nothing compared it to
     * the original purchase and nothing tracked cumulative returns, so the same
     * line could be returned repeatedly, each pass deducting stock again.
     */
    private function assertReturnableQuantities(array $items, int $purchaseId, ?int $ignoreReturnId = null): void
    {
        $available = $this->availableQuantities($purchaseId, $ignoreReturnId);

        foreach ($items as $item) {
            $purchaseItemId = (int) ($item['purchase_item_id'] ?? 0);
            $qty            = (float) ($item['quantity'] ?? 0);

            if (! $purchaseItemId || $qty <= 0) {
                continue;
            }

            if (! array_key_exists($purchaseItemId, $available)) {
                throw new InvalidArgumentException(
                    'One of the returned lines does not belong to the selected purchase order.'
                );
            }

            if ($qty > $available[$purchaseItemId]) {
                $label = PurchaseItem::with('productSku')->find($purchaseItemId)?->productSku?->sku
                    ?? "line #{$purchaseItemId}";

                throw new InvalidArgumentException(
                    "Cannot return {$qty} of {$label}. Only {$available[$purchaseItemId]} remain available — " .
                    'the rest was never received, already returned, or reserved by another draft return.'
                );
            }
        }
    }
    /**
     * Deduct items from the warehouse using the InventoryService
     */
    private function processStockDeduction(PurchaseReturn $purchaseReturn): void
    {
        // Eager load items and SKUs to prevent N+1 queries
        $purchaseReturn->load('items.productSku');

        foreach ($purchaseReturn->items as $item) {
            $this->inventoryService->deductStock(
                sku: $item->productSku,
                warehouseId: $purchaseReturn->warehouse_id,
                qty: (float) $item->quantity,
                movementType: 'purchase_return',
                reference: $purchaseReturn
            );

            // Only a finalised return consumes the source line, which is why
            // this sits here and not in createLineItems(). Drafts stay free to
            // be edited or abandoned.
            PurchaseItem::whereKey($item->purchase_item_id)
                ->increment('quantity_returned', (float) $item->quantity);
        }
    }

    /**
     * Create completely new line items
     */
    private function createLineItems(PurchaseReturn $purchaseReturn, array $processedItems): void
    {
        $itemsToInsert = array_map(function ($item) use ($purchaseReturn) {
            $item['purchase_return_id'] = $purchaseReturn->id;

            return $item;
        }, $processedItems);

        PurchaseReturnItem::insert($itemsToInsert);
    }

    /**
     * Sync line items for updates (diffing IDs)
     */
    private function syncLineItems(PurchaseReturn $purchaseReturn, array $processedItems): void
    {
        $existingItemIds = $purchaseReturn->items()->pluck('id')->toArray();
        $submittedItemIds = array_filter(array_column($processedItems, 'id'));

        // 1. Delete items removed from the UI
        $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
        if (! empty($itemsToDelete)) {
            PurchaseReturnItem::whereIn('id', $itemsToDelete)->delete();
        }

        // 2. Process updates and creations
        foreach ($processedItems as $itemData) {
            $itemId = $itemData['id'] ?? null;
            unset($itemData['id']); // Remove ID from array before insert/update

            if ($itemId && in_array($itemId, $existingItemIds)) {
                PurchaseReturnItem::where('id', $itemId)->update($itemData);
            } else {
                $itemData['purchase_return_id'] = $purchaseReturn->id;
                PurchaseReturnItem::create($itemData);
            }
        }
    }

    /**
     * Calculate line item totals, taxes, and global aggregate totals.
     */
    private function calculateFinancials(array $rawItems, string $taxType, float $globalDiscountAmount): array
    {
        $subtotal = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;

        $processedItems = [];

        foreach ($rawItems as $item) {
            $qty = (float) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $taxPercent = (float) ($item['tax_percent'] ?? 0);

            // Base gross
            $grossAmount = $qty * $unitCost;
            $taxableAmount = $grossAmount; // In returns, line discounts are rare, but we base it on original unit cost

            // Calculate Tax based on Header Tax Type (Intra-state vs Inter-state)
            $taxAmount = $taxableAmount * ($taxPercent / 100);
            $cgstAmt = 0;
            $sgstAmt = 0;
            $igstAmt = 0;
            $cgstPct = 0;
            $sgstPct = 0;
            $igstPct = 0;

            if ($taxType === 'cgst_sgst') {
                $cgstPct = $taxPercent / 2;
                $sgstPct = $taxPercent / 2;
                $cgstAmt = $taxAmount / 2;
                $sgstAmt = $taxAmount / 2;
            } elseif ($taxType === 'igst') {
                $igstPct = $taxPercent;
                $igstAmt = $taxAmount;
            }

            $lineTotal = $taxableAmount + $taxAmount;

            // Fetch HSN code from original purchase item for compliance
            $originalItem = PurchaseItem::find($item['purchase_item_id']);

            $processedItems[] = [
                'id' => $item['id'] ?? null,
                'purchase_item_id' => $item['purchase_item_id'],
                'product_id' => $item['product_id'],
                'product_sku_id' => $item['product_sku_id'],
                'unit_id' => $item['unit_id'],
                'hsn_code' => $originalItem?->hsn_code,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'tax_percent' => $taxPercent,
                'cgst_percent' => $cgstPct,
                'sgst_percent' => $sgstPct,
                'igst_percent' => $igstPct,
                'taxable_amount' => round($taxableAmount, 4),
                'cgst_amount' => round($cgstAmt, 4),
                'sgst_amount' => round($sgstAmt, 4),
                'igst_amount' => round($igstAmt, 4),
                'tax_amount' => round($taxAmount, 4),
                'total_price' => round($lineTotal, 4),
                'batch_number' => $item['batch_number'] ?? null,
                'return_reason' => $item['return_reason'] ?? null,
                'notes' => $item['notes'] ?? null,
            ];

            $subtotal += $taxableAmount;
            $totalCgst += $cgstAmt;
            $totalSgst += $sgstAmt;
            $totalIgst += $igstAmt;
        }

        $totalTax = $totalCgst + $totalSgst + $totalIgst;

        // Global calculations
        $taxableAfterGlobalDiscount = max(0, $subtotal - $globalDiscountAmount);

        // Note: For exact Indian Accounting, if global discount is applied, tax must be re-proportioned.
        // For simplicity in standard ERPs, Returns usually map exactly to original line item taxes.
        // We subtract the global discount from the final total.
        $grandTotal = $subtotal + $totalTax - $globalDiscountAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($globalDiscountAmount, 2),
            'taxable_amount' => round($taxableAfterGlobalDiscount, 2),
            'cgst_amount' => round($totalCgst, 2),
            'sgst_amount' => round($totalSgst, 2),
            'igst_amount' => round($totalIgst, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($grandTotal, 2),
            'items' => $processedItems,
        ];
    }

    /**
     * Generate unique Return Number
     */
    private function generateReturnNumber(int $companyId): string
    {
        $year = date('Y');

        // withTrashed is required: PurchaseReturn soft-deletes, but the unique
        // index on return_number ignores deleted_at. A deleted number stays
        // reserved in the index while vanishing from ordinary queries, so
        // without this the generator hands back a number already taken.
        $lastReturn = PurchaseReturn::withTrashed()
            ->where('company_id', $companyId)
            ->where('return_number', 'like', "PR-RTN-{$year}-%")
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastReturn) {
            $parts = explode('-', $lastReturn->return_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('PR-RTN-%s-%05d', $year, $sequence);
    }
}
