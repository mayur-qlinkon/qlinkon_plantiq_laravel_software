<?php

namespace App\Services;

use App\Exceptions\ExcessReturnQuantityException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReturn;
use App\Models\InvoiceReturnItem;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceReturnService
{
    protected InventoryService $inventoryService;
    protected InvoiceWriteOffService $writeOffService;

    public function __construct(InventoryService $inventoryService, InvoiceWriteOffService $writeOffService)
    {
        $this->inventoryService = $inventoryService;
        $this->writeOffService = $writeOffService;
    }

    /**
     * 🟢 STEP 1: CREATE DRAFT RETURN
     * Calculates all math and creates the Credit Note header and items.
     * Drafts do NOT touch stock or invoice_items.return_quantity — that happens only on confirm.
     */
    public function createReturn(Invoice $invoice, array $data): InvoiceReturn
    {
        return DB::transaction(function () use ($invoice, $data) {
            $companyId = $invoice->company_id;

            // 1. Crunch the numbers in memory — enforces the return-qty ceiling.
            $mathEngine = $this->calculateReturnMath($data, $invoice);

            // 2. Generate Credit Note Number safely
            $returnNumber = $this->generateReturnNumber($companyId);

            // 3. Create the Header
            $invoiceReturn = InvoiceReturn::create(array_merge([
                'company_id' => $companyId,
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'invoice_id' => $invoice->id,

                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer_name,
                'created_by' => Auth::id(),
                'salesperson_id' => $invoice->salesperson_id,
                'pos_terminal_id' => $invoice->pos_terminal_id,

                'credit_note_number' => $returnNumber,
                'source' => $invoice->source,
                'return_date' => $data['return_date'],

                'return_type' => $data['return_type'],
                'return_reason' => $data['return_reason'] ?? 'other',
                'restock' => $data['restock'] ?? false, // Explicit — checkbox semantics handled upstream.
                'stock_updated' => false, // Stock only updates upon Confirmation

                'supply_state' => $data['supply_state'],
                'gst_treatment' => $data['gst_treatment'],
                'currency_code' => $data['currency_code'] ?? 'INR',
                'exchange_rate' => $data['exchange_rate'] ?? 1.0000,

                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'status' => 'draft',
                'refund_status' => 'unrefunded',
            ], $mathEngine['header_totals']));

            // 4. Insert the Line Items
            foreach ($mathEngine['line_items'] as $itemData) {
                $itemData['invoice_return_id'] = $invoiceReturn->id;
                InvoiceReturnItem::create($itemData);
            }

            return $invoiceReturn;
        });
    }

    /**
     * 🟢 STEP 2: UPDATE DRAFT RETURN
     * Wipes old items, recalculates, and updates the header.
     * Edits are only allowed while the return is still a draft.
     */
    public function updateReturn(InvoiceReturn $return, array $data): InvoiceReturn
    {
        if ($return->status === 'confirmed') {
            throw new Exception('This Credit Note has already been confirmed and cannot be edited.');
        }

        return DB::transaction(function () use ($return, $data) {

            // Recalculate — pass the current return so its own line quantities
            // (which are NOT yet reflected in return_quantity since it's a draft)
            // are not double-counted against itself.
            $mathEngine = $this->calculateReturnMath($data, $return->invoice, $return);

            $return->update(array_merge([
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'return_date' => $data['return_date'],
                'return_type' => $data['return_type'],
                'return_reason' => $data['return_reason'] ?? 'other',
                'restock' => $data['restock'] ?? false,
                'supply_state' => $data['supply_state'],
                'gst_treatment' => $data['gst_treatment'],
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
            ], $mathEngine['header_totals']));

            // Wipe and replace items safely
            $return->items()->delete();
            foreach ($mathEngine['line_items'] as $itemData) {
                $itemData['invoice_return_id'] = $return->id;
                InvoiceReturnItem::create($itemData);
            }

            return $return;
        });
    }

    /**
     * 🟢 STEP 3: CONFIRM RETURN & UPDATE STOCK
     * The critical ERP barrier. Re-checks return quantities against live data under a row lock,
     * increments invoice_items.return_quantity atomically, and optionally restocks.
     */
    public function confirmReturn(InvoiceReturn $return): InvoiceReturn
    {
        // Idempotent: already confirmed -> no-op. (Prevents double stock-add or double counter.)
        if ($return->status === 'confirmed') {
            return $return;
        }

        return DB::transaction(function () use ($return) {

            // 1. Lock every affected invoice_item row and re-verify remaining capacity.
            //    This closes the race where two drafts were created against the same capacity.
            $return->load('items');

            // Aggregate requested qty per invoice_item_id from the return we're about to confirm.
            $requestedByItem = [];
            foreach ($return->items as $ri) {
                $key = (int) $ri->invoice_item_id;
                $requestedByItem[$key] = ($requestedByItem[$key] ?? 0) + (float) $ri->quantity;
            }

            if (empty($requestedByItem)) {
                throw new Exception('This Credit Note has no items and cannot be confirmed.');
            }

            $lockedItems = InvoiceItem::whereIn('id', array_keys($requestedByItem))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requestedByItem as $invoiceItemId => $requestedQty) {
                $invoiceItem = $lockedItems->get($invoiceItemId);
                if (! $invoiceItem) {
                    throw new Exception('Original invoice line could not be found while confirming the return.');
                }

                $original = (float) $invoiceItem->quantity;
                $alreadyReturned = (float) ($invoiceItem->return_quantity ?? 0);
                $remaining = max(0.0, $original - $alreadyReturned);

                if ($requestedQty > $remaining + 0.00005) { // tiny epsilon for float noise
                    throw new ExcessReturnQuantityException(
                        productLabel: $this->buildProductLabel($invoiceItem),
                        originalQty: $original,
                        alreadyReturnedQty: $alreadyReturned,
                        remainingQty: $remaining,
                        requestedQty: $requestedQty,
                    );
                }
            }

            // 2. Atomically bump return_quantity on each invoice line.
            foreach ($requestedByItem as $invoiceItemId => $requestedQty) {
                InvoiceItem::where('id', $invoiceItemId)->increment('return_quantity', $requestedQty);
            }

            // 3. Optionally put stock back on the shelf (respect the toggle).
            if ($return->restock && ! $return->stock_updated) {
                $items = $return->items()->with('sku')->get();

                foreach ($items as $item) {
                    if (! $item->sku) {
                        continue; // non-inventory line, skip
                    }

                    $this->inventoryService->addStock(
                        sku: $item->sku,
                        warehouseId: $return->warehouse_id,
                        qty: $item->quantity,
                        movementType: 'sale_return',
                        reference: $return
                    );

                    $item->update(['is_restocked' => true]);
                }

                $return->update(['stock_updated' => true]);
            }

            // 4. Reverse best-seller counters — sales are being undone.
            $counterItems = $return->items()->get(['product_id', 'product_sku_id', 'quantity']);
            InvoiceService::applySaleCounters($counterItems->toArray(), -1);

            // 5. Lock the document.
            $return->update([
                'status' => 'confirmed',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Confirmed credit note reduces outstanding — refresh settlement_status.
            $this->writeOffService->syncInvoiceSettlementStatus($return->invoice);

            return $return;
        });
    }

    /**
     * Undo a confirmed (or draft) return cleanly.
     * - Confirmed: decrements invoice_items.return_quantity, reverses stock if it was restocked,
     *   re-applies best-seller counters.
     * - Draft: just cascades delete via the model's softDelete (caller handles).
     *
     * Callers should delete the return themselves after this runs.
     */
    public function reverseReturnEffects(InvoiceReturn $return): void
    {
        if ($return->status !== 'confirmed') {
            return; // drafts had no side effects
        }

        DB::transaction(function () use ($return) {
            $return->load('items.sku');

            // 1. Decrement return_quantity on each parent invoice line.
            foreach ($return->items as $ri) {
                $qty = (float) $ri->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $locked = InvoiceItem::where('id', $ri->invoice_item_id)->lockForUpdate()->first();
                if (! $locked) {
                    continue;
                }

                $next = max(0.0, (float) ($locked->return_quantity ?? 0) - $qty);
                $locked->update(['return_quantity' => $next]);
            }

            // 2. Reverse the stock restock if it ever happened.
            if ($return->stock_updated) {
                foreach ($return->items as $ri) {
                    if (! $ri->sku) {
                        continue;
                    }

                    $this->inventoryService->deductStock(
                        sku: $ri->sku,
                        warehouseId: $return->warehouse_id,
                        qty: (float) $ri->quantity,
                        movementType: 'adjustment',
                        reference: $return
                    );
                }
                $return->update(['stock_updated' => false]);
            }

            // 3. Re-apply best-seller counters (return is being undone → sales count comes back).
            $counterItems = $return->items()->get(['product_id', 'product_sku_id', 'quantity']);
            InvoiceService::applySaleCounters($counterItems->toArray(), 1);

            // Reversing a confirmed return increases outstanding again — refresh settlement_status.
            $this->writeOffService->syncInvoiceSettlementStatus($return->invoice);
        });
    }

    // ─────────────────────────────────────────────────────────
    // PRIVATE INTERNAL ENGINE
    // ─────────────────────────────────────────────────────────

    /**
     * Calculates the exact financial values of the return.
     * Maps inputs to the original Invoice Items to ensure data integrity AND enforces
     * the ceiling of (original_qty - already_returned_qty) per line.
     *
     * @param  InvoiceReturn|null  $currentReturn  When updating an existing draft, we pass the draft itself
     *                                             so its own previously-saved quantities are NOT counted as
     *                                             "already returned" capacity that's been eaten.
     */
    private function calculateReturnMath(array $data, Invoice $originalInvoice, ?InvoiceReturn $currentReturn = null): array
    {
        $totalSubtotal = 0;
        $totalTax = 0;

        $companyState = Auth::user()->company->state->name ?? '';
        $isInterState = strtolower(trim($data['supply_state'])) !== strtolower(trim($companyState));

        $processedItems = [];

        // Aggregate requested qty per invoice_item_id across ALL lines in this submission.
        // Multiple rows for the same line still share one capacity bucket.
        $requestedByItem = [];
        foreach ($data['items'] as $itemData) {
            $key = (int) $itemData['invoice_item_id'];
            $requestedByItem[$key] = ($requestedByItem[$key] ?? 0) + (float) $itemData['quantity'];
        }

        // Preload the parent invoice lines for capacity math.
        $invoiceItems = InvoiceItem::whereIn('id', array_keys($requestedByItem))->get()->keyBy('id');

        foreach ($requestedByItem as $invoiceItemId => $requestedQty) {
            $originalLine = $invoiceItems->get($invoiceItemId);
            if (! $originalLine || $originalLine->invoice_id !== $originalInvoice->id) {
                throw new Exception('Invalid invoice item referenced on this return.');
            }

            $original = (float) $originalLine->quantity;
            $alreadyReturned = (float) ($originalLine->return_quantity ?? 0);

            // When editing an existing draft, re-credit its own saved quantities — they will be
            // replaced in this same transaction, so they should not consume capacity twice.
            // NB: a *draft* never wrote to return_quantity, but this keeps the math safe if
            //     someone later re-purposes this for confirmed edits.
            if ($currentReturn && $currentReturn->status === 'confirmed') {
                $selfReturned = (float) $currentReturn->items()
                    ->where('invoice_item_id', $invoiceItemId)
                    ->sum('quantity');
                $alreadyReturned = max(0.0, $alreadyReturned - $selfReturned);
            }

            $remaining = max(0.0, $original - $alreadyReturned);

            if ($requestedQty > $remaining + 0.00005) {
                throw new ExcessReturnQuantityException(
                    productLabel: $this->buildProductLabel($originalLine),
                    originalQty: $original,
                    alreadyReturnedQty: $alreadyReturned,
                    remainingQty: $remaining,
                    requestedQty: $requestedQty,
                );
            }
        }

        // Now do the per-row math.
        foreach ($data['items'] as $itemData) {
            $originalLine = $invoiceItems->get((int) $itemData['invoice_item_id']);

            $qty = (float) $itemData['quantity'];
            $price = (float) $itemData['unit_price'];
            $taxPct = (float) $itemData['tax_percent'];

            // Base Value
            $baseAmount = $qty * $price;

            // Line Discount
            $lineDiscountAmt = 0;
            if (in_array($itemData['discount_type'], ['percentage', 'percent'])) {
                $lineDiscountAmt = $baseAmount * ((float) $itemData['discount_amount'] / 100);
            } else {
                $lineDiscountAmt = (float) $itemData['discount_amount'];
            }
            $afterDiscount = max(0, $baseAmount - $lineDiscountAmt);

            // Taxes
            $taxableValue = 0;
            $taxAmount = 0;

            if ($itemData['tax_type'] === 'inclusive') {
                $taxableValue = $afterDiscount / (1 + ($taxPct / 100));
                $taxAmount = $afterDiscount - $taxableValue;
            } else {
                $taxableValue = $afterDiscount;
                $taxAmount = $taxableValue * ($taxPct / 100);
            }

            $lineTotal = $taxableValue + $taxAmount;

            $totalSubtotal += $taxableValue;
            $totalTax += $taxAmount;

            $processedItems[] = [
                'invoice_item_id' => $originalLine->id,
                'product_id' => $itemData['product_id'] ?? $originalLine->product_id,
                'product_sku_id' => $itemData['product_sku_id'] ?? $originalLine->product_sku_id,
                'unit_id' => $itemData['unit_id'] ?? $originalLine->unit_id,
                'product_name' => $itemData['product_name'],
                'hsn_code' => $itemData['hsn_code'] ?? $originalLine->hsn_code,

                'quantity' => $qty,
                'unit_price' => $price,
                'is_restocked' => false, // Updated later during confirmation

                'tax_type' => $itemData['tax_type'],
                'discount_type' => $itemData['discount_type'],
                'discount_amount' => $lineDiscountAmt,

                'taxable_value' => $taxableValue,
                'tax_percent' => $taxPct,

                'igst_amount' => $isInterState ? $taxAmount : 0,
                'cgst_amount' => ! $isInterState ? ($taxAmount / 2) : 0,
                'sgst_amount' => ! $isInterState ? ($taxAmount / 2) : 0,
                'tax_amount' => $taxAmount,

                'total_amount' => $lineTotal,
            ];
        }

        // Global Financials
        $globalDiscountAmt = 0;
        if (in_array($data['discount_type'], ['percentage', 'percent'])) {
            $globalDiscountAmt = $totalSubtotal * ((float) ($data['discount_amount'] ?? 0) / 100);
        } else {
            $globalDiscountAmt = (float) ($data['discount_amount'] ?? 0);
        }

        $shipping = (float) ($data['shipping_charge'] ?? 0);
        $other = (float) ($data['other_charges'] ?? 0);

        $rawGrandTotal = ($totalSubtotal - $globalDiscountAmt) + $totalTax + $shipping + $other;
        $grandTotal = round($rawGrandTotal);
        $roundOff = round($grandTotal - $rawGrandTotal, 2);

        return [
            'header_totals' => [
                'subtotal' => $totalSubtotal,
                'discount_type' => $data['discount_type'] === 'percent' ? 'percentage' : $data['discount_type'],
                'discount_amount' => $globalDiscountAmt,
                'taxable_amount' => max(0, $totalSubtotal - $globalDiscountAmt),

                'tax_amount' => $totalTax,
                'igst_amount' => $isInterState ? $totalTax : 0,
                'cgst_amount' => ! $isInterState ? ($totalTax / 2) : 0,
                'sgst_amount' => ! $isInterState ? ($totalTax / 2) : 0,

                'shipping_charge' => $shipping,
                'other_charges' => $other,
                'round_off' => $roundOff,
                'grand_total' => $grandTotal,
            ],
            'line_items' => $processedItems,
        ];
    }

    /**
     * Builds a readable product label for error messages — prefers "Name (SKU)"
     * and falls back gracefully when either piece is missing.
     */
    private function buildProductLabel(InvoiceItem $invoiceItem): string
    {
        $name = trim((string) ($invoiceItem->product_name ?? ''));
        $sku = $invoiceItem->sku?->sku
            ?? $invoiceItem->sku?->sku_code
            ?? null;

        if ($name !== '' && $sku) {
            return "{$name} ({$sku})";
        }

        if ($name !== '') {
            return $name;
        }

        return $sku ?: 'this item';
    }

    /**
     * Generates a sequential Credit Note number (e.g., CN-2603-0001)
     */
    protected function generateReturnNumber(int $companyId): string
    {
        $prefix = 'CN-'.date('ym');

        $latest = InvoiceReturn::withTrashed()
            ->where('company_id', $companyId)
            ->where('credit_note_number', 'like', "{$prefix}-%")
            ->orderBy('credit_note_number', 'desc')
            ->first();

        $nextSequence = $latest ? ((int) substr($latest->credit_note_number, -4)) + 1 : 1;

        return $prefix.'-'.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
