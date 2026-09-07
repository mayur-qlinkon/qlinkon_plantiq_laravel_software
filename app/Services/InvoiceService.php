<?php

namespace App\Services;

use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Store;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    protected InventoryService $inventory;
    protected PaymentService $paymentService;

    public function __construct(InventoryService $inventory, PaymentService $paymentService)
    {
        $this->inventory = $inventory;
        $this->paymentService = $paymentService;
    }

    /**
     * Create a unified Invoice (POS or Direct)
     */
    public function createInvoice(array $data, int $companyId): Invoice
    {
        return DB::transaction(function () use ($data, $companyId) {

            // Draft invoices are fully editable working copies — no stock / counter side effects yet.
            $status = $data['status'] ?? 'confirmed';
            $isDraft = $status === 'draft';

            // 1. Generate Invoice Number (Logic to be customized per company)
            // $invoiceNumber = $this->generateInvoiceNumber($companyId, $data['source']); backup
            $invoiceNumber = $this->generateInvoiceNumber($companyId, $data['source'], $data['store_id'] ?? null);

            // 2. Prepare Tax Calculations
            $isInterState = $this->isInterStateSale($data['supply_state'], (int) $data['store_id']);

            // 3. Create the Header
            $invoice = Invoice::create([
                'company_id' => $companyId,
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'created_by' => Auth::id(),
                'salesperson_id' => $data['salesperson_id'] ?? Auth::id(),
                'invoice_number' => $invoiceNumber,
                // Null for every caller that doesn't send one — only POS does
                // today, but the column is generic so any double-submit-prone
                // entry point can opt in later.
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'source' => $data['source'] ?? 'direct',
                'invoice_date' => $data['invoice_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'supply_state' => $data['supply_state'],
                'gst_treatment' => $data['gst_treatment'] ?? 'unregistered',
                'currency_code' => $data['currency_code'] ?? 'INR',
                'status' => $status,
                'payment_status' => 'unpaid',
                'notes' => $data['notes'] ?? null,           // 🌟 ADD THIS
                'terms_conditions' => $data['terms_conditions'] ?? null, // 🌟 ADD THIS
            ]);

            // Maps challan_item.id => newly created invoice_item.id, so each
            // dispatched line can point at the exact line that billed it.
            $challanItemMap = [];

            $totals = [
                'subtotal' => 0,
                'taxable' => 0,
                'cgst' => 0,
                'sgst' => 0,
                'igst' => 0,
                'tax' => 0,
            ];

            // 4. Process Items & Inventory
            foreach ($data['items'] as $item) {
                $sku = ProductSku::with('product')->findOrFail($item['product_sku_id']);

                // 🌟 Execute the GST Math Engine
                $itemTax = $this->calculateItemTax(
                    $item['quantity'],
                    $item['unit_price'],
                    $item['discount_type'] ?? 'fixed',
                    $item['discount_value'] ?? 0,
                    $item['tax_percent'] ?? 0,
                    $item['tax_type'] ?? 'exclusive',
                    $isInterState
                );



                // Create Item Snapshot
                $invoiceItem = $invoice->items()->create([
                    'product_id' => $sku->product_id,
                    'product_sku_id' => $sku->id,
                    'unit_id' => $item['unit_id'],
                    'product_name' => $sku->product->name,
                    // Per-line override wins. The same product can be billed
                    // under a different HSN depending on how it is supplied,
                    // and the master record is often blank on older catalogues.
                    'hsn_code' => $this->resolveHsnCode($item, $sku),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],

                    // Discounts — all three fields come from the clamped result.
                    // NEVER trust a client-supplied discount_amount or an
                    // unclamped discount_value; both are derived here.
                    'discount_type' => $itemTax['discount_type'],
                    'discount_value' => $itemTax['discount_value'],
                    'discount_amount' => $itemTax['discount_amount'],

                    // Tax & Totals
                    'taxable_value' => $itemTax['taxable'],
                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'tax_type' => $item['tax_type'] ?? 'exclusive',
                    'cgst_amount' => $itemTax['cgst'],
                    'sgst_amount' => $itemTax['sgst'],
                    'igst_amount' => $itemTax['igst'],
                    'tax_amount' => $itemTax['total_tax'],
                    'total_amount' => $itemTax['taxable'] + $itemTax['total_tax'],
                ]);

                if (! empty($item['challan_item_id'])) {
                    $challanItemMap[(int) $item['challan_item_id']] = $invoiceItem->id;
                }

                // Update Running Totals
                $totals['subtotal'] += $itemTax['taxable']; // Subtotal is technically the sum of Taxable Values in GST
                $totals['taxable'] += $itemTax['taxable'];
                $totals['cgst'] += $itemTax['cgst'];
                $totals['sgst'] += $itemTax['sgst'];
                $totals['igst'] += $itemTax['igst'];
                $totals['tax'] += $itemTax['total_tax'];

                // 5. Deduct Inventory — pass batch info when converting from a challan.
                //    Skipped for drafts; stock is only touched when the invoice is confirmed.
                if (! $isDraft) {
                    $this->inventory->deductStock(
                        sku: $sku,
                        warehouseId: $data['warehouse_id'],
                        qty: $item['quantity'],
                        movementType: 'sale',
                        reference: $invoice,
                        batchId: $item['batch_id'] ?? null,
                        batchNumber: $item['batch_number'] ?? null,
                    );
                }
            }

            // 6. Consume the source challan.
            //
            // This runs for DRAFT invoices as well, and that is deliberate.
            // qty_invoiced tracks a DOCUMENT commitment, not a stock movement —
            // once a quantity has been placed on an invoice (even an unconfirmed
            // one) it must not be billable a second time. Stock deduction stays
            // gated on confirmation; only the paperwork is reserved here.
            // Releasing happens in cancelInvoice().
            if (! empty($data['challan_id'])) {
                $invoice->update(['challan_id' => $data['challan_id']]);

                foreach ($data['items'] as $item) {
                    $challanItemId = (int) ($item['challan_item_id'] ?? 0);
                    if (! $challanItemId) {
                        continue;
                    }

                    ChallanItem::where('id', $challanItemId)
                        ->increment('qty_invoiced', $item['quantity']);

                    // Line-level trail: which invoice line billed this dispatch line
                    if (isset($challanItemMap[$challanItemId])) {
                        ChallanItem::where('id', $challanItemId)
                            ->update(['invoice_item_id' => $challanItemMap[$challanItemId]]);
                    }
                }

                // Recalculate challan status (partially_converted vs converted_to_invoice)
                $challan = Challan::find($data['challan_id']);
                $challan?->recalculateStatus();
            }

            // 7. Finalize Header Totals & Apply Global Discount + Shipping Tax
            $globalDiscountType  = $data['discount_type']  ?? 'fixed';
            $globalDiscountValue = (float) ($data['discount_value'] ?? 0);
            $shippingCharge      = (float) ($data['shipping_charge'] ?? 0);
            $shippingTaxRate     = (float) ($data['shipping_tax_rate'] ?? 0);   // NEW

            /*
            |--------------------------------------------------------------------------
            | CORRECTED GST FLOW  (v2 — shipping decoupled from item tax base)
            |--------------------------------------------------------------------------
            | 1. Per-item: discount → taxable → GST  (done above in loop)
            | 2. Global discount  → applied to ITEM taxable base ONLY (not shipping)
            | 3. Item GST  → proportionally reduced by the same discount ratio
            | 4. Shipping GST → computed independently at its own configured rate
            | 5. Totals reassembled: items + shipping (each with own GST)
            |--------------------------------------------------------------------------
            |
            | Why separate shipping?
            | Shipping is a supply of service (SAC 996511/996512) with its own GST
            | rate (usually 0% or 18%). Mixing it into the item effective-rate pool
            | was producing legally incorrect CGST/SGST amounts on mixed-rate invoices
            | (e.g. 0% plants + 12% pots). Now each supply has its own tax line.
            |--------------------------------------------------------------------------
            */

            // ── Step A: Global discount on items only ──────────────────────────────
            $itemTaxableBase = $totals['taxable'];  // items only — shipping excluded

            $globalDiscount       = $this->normalizeDiscount($globalDiscountType, $globalDiscountValue, $itemTaxableBase);
            $globalDiscountType   = $globalDiscount['type'];
            $globalDiscountValue  = $globalDiscount['value'];
            $globalDiscountAmount = $globalDiscount['amount'];

            $taxableAfterDiscount = max(0, $itemTaxableBase - $globalDiscountAmount);

            // ── Step B: Proportionally reduce item GST by the discount ratio ───────
            // When global discount reduces taxable value by X%, the GST also reduces
            // by X% (same ratio). This is correct for uniform-rate invoices and
            // approximately correct for mixed-rate invoices (exact fix requires
            // per-HSN breakdown — planned as a future improvement).
            $discountRatio        = ($itemTaxableBase > 0) ? ($taxableAfterDiscount / $itemTaxableBase) : 0;
            $itemTaxAfterDiscount = $totals['tax'] * $discountRatio;

            // ── Step C: Shipping GST — independent, at its own rate ───────────────
            $shippingTaxAmount = round($shippingCharge * ($shippingTaxRate / 100), 4);

            // ── Step D: Rebuild CGST/SGST/IGST split for items + shipping ─────────
            if ($isInterState) {
                $totals['igst'] = $itemTaxAfterDiscount + $shippingTaxAmount;
                $totals['cgst'] = 0;
                $totals['sgst'] = 0;
            } else {
                $totals['igst'] = 0;
                $totals['cgst'] = ($itemTaxAfterDiscount + $shippingTaxAmount) / 2;
                $totals['sgst'] = ($itemTaxAfterDiscount + $shippingTaxAmount) / 2;
            }

            $totals['tax'] = $itemTaxAfterDiscount + $shippingTaxAmount;

            // ── Step E: Grand total ────────────────────────────────────────────────
            // taxable_amount (items) + item_gst + shipping_net + shipping_gst
            $grandTotal   = $taxableAfterDiscount + $totals['tax'] + $shippingCharge;
            $roundedTotal = round($grandTotal);
            $roundOff     = $roundedTotal - $grandTotal;

            $invoice->update([
                'subtotal'             => $totals['subtotal'],
                'taxable_amount'       => $taxableAfterDiscount,
                'cgst_amount'          => $totals['cgst'],
                'sgst_amount'          => $totals['sgst'],
                'igst_amount'          => $totals['igst'],
                'tax_amount'           => $totals['tax'],

                'discount_type'        => $globalDiscountType,
                'discount_value'       => $globalDiscountValue,
                'discount_amount'      => $globalDiscountAmount,

                'shipping_charge'      => $shippingCharge,
                'shipping_tax_rate'    => $shippingTaxRate,    // NEW
                'shipping_tax_amount'  => $shippingTaxAmount,  // NEW

                'round_off'            => $roundOff,
                'grand_total'          => $roundedTotal,
            ]);

            // Best-seller counters — only for finalized sales
            if ($invoice->status === 'confirmed') {
                self::applySaleCounters($invoice->items()->get(['product_id', 'product_sku_id', 'quantity'])->toArray(), 1);
            }

            return $invoice;
        });
    }

    /**
     * Update an Invoice (Reverse stock, wipe items, recreate, deduct new stock)
     */
    public function updateInvoice(Invoice $invoice, array $data, int $companyId): Invoice
    {
        // Guard: once any line on this invoice has a confirmed return against it,
        // the item set is sealed. Editing would orphan the return rows (which reference
        // invoice_item.id) and could reduce qty below what's already been returned.
        $hasReturns = $invoice->items()->where('return_quantity', '>', 0)->exists();
        if ($hasReturns) {
            throw new \RuntimeException(
                'This invoice has confirmed returns against it and its items can no longer be edited. Cancel the related Credit Notes first if you need to change the invoice.'
            );
        }

        // Status transition map:
        //   draft  -> draft      : no stock touch
        //   draft  -> confirmed  : no reverse, deduct new
        //   confirmed -> confirmed: reverse old, deduct new (existing behavior)
        //   confirmed -> draft   : reverse old only (downgrade)
        $wasConfirmed = $invoice->status === 'confirmed';
        $newStatus = $data['status'] ?? $invoice->status;
        $isNowConfirmed = $newStatus === 'confirmed';

        // Reverse best-seller counters for old items if the invoice was already counted
        if ($wasConfirmed) {
            self::applySaleCounters(
                $invoice->items()->get(['product_id', 'product_sku_id', 'quantity', 'return_quantity'])
                    ->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'product_sku_id' => $i->product_sku_id,
                        'quantity' => max(0, (float) $i->quantity - (float) ($i->return_quantity ?? 0)),
                    ])->toArray(),
                -1
            );
        }

        // 1. Reverse old stock (Add it back to the OLD warehouse) — only if old was confirmed.
        //    Draft invoices never deducted stock, so there's nothing to reverse.
        if ($wasConfirmed) {
            foreach ($invoice->items as $oldItem) {
                $sku = ProductSku::find($oldItem->product_sku_id);
                if ($sku) {
                    $this->inventory->addStock(
                        $sku,
                        $invoice->warehouse_id,
                        $oldItem->quantity,
                        'sale_return',
                        $invoice
                    );
                }
            }
        }

        // 2. Wipe old items clean
        $invoice->items()->delete();

        // 3. Prepare new tax calculations
        $isInterState = $this->isInterStateSale($data['supply_state'], (int) $data['store_id']);

        // 4. Update Header (Basic info only, totals come later)
        $invoice->update([
            'store_id'         => $data['store_id'],
            'warehouse_id'     => $data['warehouse_id'],
            'customer_id'      => $data['customer_id'] ?? null,
            'customer_name'    => $data['customer_name'] ?? null,
            'invoice_date'     => $data['invoice_date'],
            'due_date'         => $data['due_date'] ?? null,
            'supply_state'     => $data['supply_state'],
            'gst_treatment'    => $data['gst_treatment'] ?? 'unregistered',
            'status'           => $newStatus,
            'notes'            => $data['notes'] ?? null,
            'terms_conditions' => $data['terms_conditions'] ?? null,
        ]);

        $totals = [
            'subtotal' => 0, 'taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'tax' => 0,
        ];

        // 5. Process New Items & Deduct New Stock
        foreach ($data['items'] as $item) {
            $sku = ProductSku::with('product')->findOrFail($item['product_sku_id']);

            $itemTax = $this->calculateItemTax(
                $item['quantity'],
                $item['unit_price'],
                $item['discount_type'] ?? 'fixed',
                $item['discount_value'] ?? 0,
                $item['tax_percent'] ?? 0,
                $item['tax_type'] ?? 'exclusive',
                $isInterState
            );



            $invoice->items()->create([
                'product_id' => $sku->product_id,
                'product_sku_id' => $sku->id,
                'unit_id' => $item['unit_id'],
                'product_name' => $sku->product->name,
                'hsn_code' => $this->resolveHsnCode($item, $sku),
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount_type' => $itemTax['discount_type'],
                'discount_value' => $itemTax['discount_value'],
                'discount_amount' => $itemTax['discount_amount'],
                'taxable_value' => $itemTax['taxable'],
                'tax_percent' => $item['tax_percent'] ?? 0,
                'tax_type' => $item['tax_type'] ?? 'exclusive',
                'cgst_amount' => $itemTax['cgst'],
                'sgst_amount' => $itemTax['sgst'],
                'igst_amount' => $itemTax['igst'],
                'tax_amount' => $itemTax['total_tax'],
                'total_amount' => $itemTax['taxable'] + $itemTax['total_tax'],
            ]);

            $totals['subtotal'] += $itemTax['taxable'];
            $totals['taxable'] += $itemTax['taxable'];
            $totals['cgst'] += $itemTax['cgst'];
            $totals['sgst'] += $itemTax['sgst'];
            $totals['igst'] += $itemTax['igst'];
            $totals['tax'] += $itemTax['total_tax'];

            // Deduct from the NEW warehouse selection — only when the invoice is confirmed.
            if ($isNowConfirmed) {
                $this->inventory->deductStock(
                    $sku,
                    $data['warehouse_id'],
                    $item['quantity'],
                    'sale',
                    $invoice
                );
            }
        }

        // 6. Finalize Header Totals & Apply Global Discount + Shipping Tax
        $globalDiscountType  = $data['discount_type']  ?? 'fixed';
        $globalDiscountValue = (float) ($data['discount_value'] ?? 0);
        $shippingCharge      = (float) ($data['shipping_charge'] ?? 0);
        $shippingTaxRate     = (float) ($data['shipping_tax_rate'] ?? 0);   // NEW

        // See createInvoice() for full explanation of this corrected flow.
        // ── Step A: Global discount on items only ──────────────────────────────
        $itemTaxableBase = $totals['taxable'];

        $globalDiscount       = $this->normalizeDiscount($globalDiscountType, $globalDiscountValue, $itemTaxableBase);
        $globalDiscountType   = $globalDiscount['type'];
        $globalDiscountValue  = $globalDiscount['value'];
        $globalDiscountAmount = $globalDiscount['amount'];

        $taxableAfterDiscount = max(0, $itemTaxableBase - $globalDiscountAmount);

        // ── Step B: Proportionally reduce item GST by the discount ratio ───────
        $discountRatio        = ($itemTaxableBase > 0) ? ($taxableAfterDiscount / $itemTaxableBase) : 0;
        $itemTaxAfterDiscount = $totals['tax'] * $discountRatio;

        // ── Step C: Shipping GST — independent, at its own rate ───────────────
        $shippingTaxAmount = round($shippingCharge * ($shippingTaxRate / 100), 4);

        // ── Step D: Rebuild CGST/SGST/IGST split ──────────────────────────────
        if ($isInterState) {
            $totals['igst'] = $itemTaxAfterDiscount + $shippingTaxAmount;
            $totals['cgst'] = 0;
            $totals['sgst'] = 0;
        } else {
            $totals['igst'] = 0;
            $totals['cgst'] = ($itemTaxAfterDiscount + $shippingTaxAmount) / 2;
            $totals['sgst'] = ($itemTaxAfterDiscount + $shippingTaxAmount) / 2;
        }

        $totals['tax'] = $itemTaxAfterDiscount + $shippingTaxAmount;

        // ── Step E: Grand total ────────────────────────────────────────────────
        $grandTotal   = $taxableAfterDiscount + $totals['tax'] + $shippingCharge;
        $roundedTotal = round($grandTotal);
        $roundOff     = $roundedTotal - $grandTotal;

        $invoice->update([
            'subtotal'            => $totals['subtotal'],
            'taxable_amount'      => $taxableAfterDiscount,
            'cgst_amount'         => $totals['cgst'],
            'sgst_amount'         => $totals['sgst'],
            'igst_amount'         => $totals['igst'],
            'tax_amount'          => $totals['tax'],
            'discount_type'       => $globalDiscountType,
            'discount_value'      => $globalDiscountValue,
            'discount_amount'     => $globalDiscountAmount,
            'shipping_charge'     => $shippingCharge,
            'shipping_tax_rate'   => $shippingTaxRate,    // NEW
            'shipping_tax_amount' => $shippingTaxAmount,  // NEW
            'round_off'           => $roundOff,
            'grand_total'         => $roundedTotal,
        ]);

        // Re-apply counters for the new item set if the invoice is finalized
        if ($invoice->fresh()->status === 'confirmed') {
            self::applySaleCounters(
                $invoice->items()->get(['product_id', 'product_sku_id', 'quantity'])->toArray(),
                1
            );
        }

        return $invoice;
    }

    /**
     * Determine if CGST/SGST or IGST applies
     */
    protected function isInterStateSale(string $supplyState, int $storeId): bool
    {
        // 1. Fetch the store and its related state
        $store = Store::with('state')->find($storeId);

        // 2. Safely extract the store's state name (fallback to global setting if missing)
        $sellerState = ($store && $store->state)
            ? $store->state->name
            : get_setting('company_state', '');

        // 3. Compare the strings carefully (ignoring case and extra spaces)
        return strtolower(trim($sellerState)) !== strtolower(trim($supplyState));
    }

    /**
     * Single authority for turning a raw discount input into a safe,
     * applied discount. Every discount in this service — line level and
     * global — passes through here.
     *
     * A discount can never exceed the base it is applied to. Anything higher
     * is clamped down to the base, so the worst case is a 100% discount and
     * never a negative total. The clamped VALUE is returned alongside the
     * amount so the stored discount_value matches what was actually applied
     * instead of preserving an impossible figure like 999999 on a 200 rupee bill.
     *
     * @param  float  $base  The amount the discount is deducted from.
     * @return array{type:string, value:float, amount:float}
     */
    protected function normalizeDiscount(?string $type, $value, float $base): array
    {
        $isPercentage = in_array($type, ['percent', 'percentage'], true);

        $base  = max(0, $base);
        $value = max(0, (float) $value);

        if ($isPercentage) {
            // A percentage above 100 would wipe out more than the base.
            $value  = min($value, 100);
            $amount = $base * ($value / 100);
        } else {
            // A fixed discount is capped at the base itself.
            $value  = min($value, $base);
            $amount = $value;
        }

        return [
            'type'   => $isPercentage ? 'percentage' : 'fixed',
            'value'  => round($value, 4),
            'amount' => round($amount, 4),
        ];
    }

    /**
     * Indian Tax Splitter & Math Engine
     * Flow: Subtotal -> Discount -> Taxable -> GST Calculation
     */
    protected function calculateItemTax($qty, $price, $discountType, $discountValue, $taxPercent, $taxType, $isInterState): array
    {
        $baseSubtotal = $qty * $price;

        // 1. Calculate and Deduct Line Discount FIRST — clamped to the line base.
        $discount       = $this->normalizeDiscount($discountType, $discountValue, $baseSubtotal);
        $discountAmount = $discount['amount'];

        // Prevent negative values
        $afterDiscount = max(0, $baseSubtotal - $discountAmount);

        // 2. Determine Taxable Value & Total Tax
        $taxable = 0;
        $totalTax = 0;

        if ($taxType === 'inclusive') {
            // Extract tax backwards
            $taxable = $afterDiscount / (1 + ($taxPercent / 100));
            $totalTax = $afterDiscount - $taxable;
        } else {
            // Apply tax forwards
            $taxable = $afterDiscount;
            $totalTax = $taxable * ($taxPercent / 100);
        }

        // 3. Split GST based on State Match
        if ($isInterState) {
            return [
                'discount_type' => $discount['type'],
                'discount_value' => $discount['value'],
                'discount_amount' => $discountAmount,
                'taxable' => $taxable,
                'igst' => $totalTax,
                'cgst' => 0,
                'sgst' => 0,
                'total_tax' => $totalTax,
            ];
        }

        // Intra-state (Split equally)
        return [
            'discount_type' => $discount['type'],
            'discount_value' => $discount['value'],
            'discount_amount' => $discountAmount,
            'taxable' => $taxable,
            'igst' => 0,
            'cgst' => $totalTax / 2,
            'sgst' => $totalTax / 2,
            'total_tax' => $totalTax,
        ];
    }

    // public function generateInvoiceNumber($companyId, $source): string
    // {
    //     $prefix = ($source === 'pos') ? 'POS' : 'INV';
    //     $yearPrefix = $prefix.'/'.date('y').'-'.(date('y') + 1).'/';

    //     $latest = Invoice::withoutGlobalScopes()
    //         ->where('company_id', $companyId)
    //         ->where('invoice_number', 'like', $yearPrefix.'%')
    //         ->withTrashed()
    //         ->lockForUpdate()
    //         ->orderByDesc('invoice_number')
    //         ->value('invoice_number');

    //     $next = $latest ? ((int) substr($latest, -4)) + 1 : 1;

    //     return $yearPrefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    // }
      /**
     * Generate the next Invoice Number using a 3-layer fallback:
     *   1. Store-level prefix / next_invoice_number (most specific)
     *   2. Company settings table (invoice_prefix / invoice_start_number)
     *   3. Legacy year-based format (backward-compatible default)
     *
     * POS invoices always use the legacy POS/YY-YY/ format regardless of settings.
     */
    public function generateInvoiceNumber($companyId, $source, ?int $storeId = null): string
    {
        // POS invoices keep their own dedicated sequence — never use configured prefix.
        if ($source === 'pos') {
            return $this->generateLegacyNumber('POS', $companyId, null);
        }

        // --- Layer 1: Store-level prefix ---
        $store          = $storeId ? Store::find($storeId) : null;
        $storePrefix    = $store ? $store->getRawOriginal('invoice_prefix') : null;
        $storeFloor     = $store ? $store->getRawOriginal('next_invoice_number') : null;

        // --- Layer 2: Settings-level prefix ---
        $settingsPrefix = get_setting('invoice_prefix', null, $companyId) ?: null;
        $settingsStart  = (int) get_setting('invoice_start_number', 1, $companyId);

        // --- Resolve final prefix and floor ---
        if ($storePrefix) {
            // Store has its own prefix → use store-scoped sequence
            $resolvedPrefix = $storePrefix;
            $floor          = ($storeFloor !== null) ? (int) $storeFloor : $settingsStart;
            $scopeByStore   = true;
        } elseif ($settingsPrefix) {
            // No store prefix, but company settings has one → company-wide sequence
            $resolvedPrefix = $settingsPrefix;
            $floor          = $settingsStart;
            $scopeByStore   = false;
        } else {
            // Layer 3: Nothing configured → keep existing legacy format unchanged
            return $this->generateLegacyNumber('INV', $companyId, $storeId);
        }

        // --- Find latest number in DB for this prefix ---
        $query = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('invoice_number', 'like', $resolvedPrefix.'%')
            ->withTrashed()
            ->lockForUpdate()
            ->orderByDesc('invoice_number');

        if ($scopeByStore && $storeId) {
            $query->where('store_id', $storeId);
        }

        $latest   = $query->value('invoice_number');
        $lastSeq  = $latest ? (int) preg_replace('/.*?(\d+)$/', '$1', $latest) : 0;
        $next     = max($lastSeq + 1, $floor);

        return $resolvedPrefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
    /**
     * Legacy year-based number format: PREFIX/YY-YY/XXXX
     * Used for POS and as the backward-compatible fallback when no prefix is configured.
     */
    protected function generateLegacyNumber(string $type, int $companyId, ?int $storeId): string
    {
        $yearPrefix = $type.'/'.date('y').'-'.(date('y') + 1).'/';

        $query = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('invoice_number', 'like', $yearPrefix.'%')
            ->withTrashed()
            ->lockForUpdate()
            ->orderByDesc('invoice_number');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $latest = $query->value('invoice_number');
        $next   = $latest ? ((int) substr($latest, -4)) + 1 : 1;

        return $yearPrefix.str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Cancel an Invoice and reverse all stock deductions
     */
    public function cancelInvoice(Invoice $invoice): void
    {
        $wasConfirmed = $invoice->status === 'confirmed';

        // 1. Mark as cancelled
        $invoice->update(['status' => 'cancelled']);

         // 2. Reverse stock — only if invoice was confirmed (drafts never deducted stock)
        if ($wasConfirmed) {
            foreach ($invoice->items as $item) {
                $sku = ProductSku::find($item->product_sku_id);
                if ($sku) {
                    $this->inventory->addStock(
                        $sku,
                        $invoice->warehouse_id,
                        $item->quantity,
                        'sale_return',
                        $invoice
                    );
                }
            }
        }

        // 3. Reverse best-seller counters, net of already-returned qty to avoid double-count
        if ($wasConfirmed) {
            self::applySaleCounters(
                $invoice->items->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'product_sku_id' => $i->product_sku_id,
                    'quantity' => max(0, (float) $i->quantity - (float) ($i->return_quantity ?? 0)),
                ])->toArray(),
                -1
            );
        }

        // 4. Release the quantities reserved on the source challan so the
        //    remaining stock becomes billable again.
        if ($invoice->challan_id) {
            foreach ($invoice->items as $item) {
                $challanItem = ChallanItem::where('invoice_item_id', $item->id)->first();
                if (! $challanItem) {
                    continue;
                }

                // Clamped in PHP, not SQL — qty_invoiced is decimal(10,2) and
                // decrementClamped() is hardcoded to the integer total_sold column.
                $challanItem->update([
                    'qty_invoiced' => max(0, (float) $challanItem->qty_invoiced - (float) $item->quantity),
                    'invoice_item_id' => null,
                ]);
            }

            $invoice->challan?->recalculateStatus();
        }

        // Void associated payments, then resync the invoice's own
        // payment_status against what actually remains — a document
        // marked "paid" with zero live payments is a contradictory
        // state that reports keyed on payment_status would misread.
        foreach ($invoice->payments as $payment) {
            $payment->update(['status' => 'cancelled']);
        }

        $this->paymentService->syncDocumentPaymentStatus($invoice);
    }

    /**
     * Apply best-seller counters to SKU + Product atomically.
     *
     * @param  array<int, array{product_id:int, product_sku_id:int, quantity:float|int}>  $items
     * @param  int  $sign  +1 to add (sale), -1 to subtract (cancel/return)
     */
    public static function applySaleCounters(array $items, int $sign): void
    {
        if (empty($items) || ($sign !== 1 && $sign !== -1)) {
            return;
        }

        $skuTotals = [];
        $productTotals = [];

        foreach ($items as $row) {
            $skuId = (int) ($row['product_sku_id'] ?? 0);
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = (int) round((float) ($row['quantity'] ?? 0));

            if ($qty <= 0 || $skuId === 0 || $productId === 0) {
                continue;
            }

            $skuTotals[$skuId] = ($skuTotals[$skuId] ?? 0) + $qty;
            $productTotals[$productId] = ($productTotals[$productId] ?? 0) + $qty;
        }

        foreach ($skuTotals as $skuId => $qty) {
            if ($sign === 1) {
                ProductSku::withoutGlobalScopes()->where('id', $skuId)->increment('total_sold', $qty);
            } else {
                self::decrementClamped(ProductSku::withoutGlobalScopes()->where('id', $skuId), $qty);
            }
        }

        foreach ($productTotals as $productId => $qty) {
            if ($sign === 1) {
                Product::withoutGlobalScopes()->where('id', $productId)->increment('total_sold', $qty);
            } else {
                self::decrementClamped(Product::withoutGlobalScopes()->where('id', $productId), $qty);
            }
        }
    }

    /**
     * Portable decrement that clamps at 0 — avoids GREATEST()/unsigned pitfalls.
     */
    private static function decrementClamped($query, int $qty): void
    {
        $rows = (clone $query)->get(['id', 'total_sold']);
        foreach ($rows as $row) {
            $next = max(0, (int) $row->total_sold - $qty);
            (clone $query)->where('id', $row->id)->update(['total_sold' => $next]);
        }
    }

    /**
     * Decide the HSN code stored on an invoice line.
     *
     * The submitted value takes priority so a user can correct or supply a
     * code straight from the invoice form, which matters because many product
     * records were created before HSN was captured. Falls back to the SKU and
     * then the product so existing behaviour is unchanged when nothing is sent.
     */
    protected function resolveHsnCode(array $item, $sku): ?string
    {
        $submitted = trim((string) ($item['hsn_code'] ?? ''));

        if ($submitted !== '') {
            return $submitted;
        }

        // SKU code overrides the product default; empty string counts as unset.
        return $sku->hsn_code ?: ($sku->product->hsn_code ?? null);
    }
}