<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    // =========================================================================
    //  NUMBER GENERATION — 3-layer fallback
    // =========================================================================

    /**
     * Generate the next Quotation Number with a 3-layer priority fallback:
     *
     *   Layer 1 — Store-level  : store.quotation_prefix  (most specific)
     *   Layer 2 — Settings-level: settings.quotation_prefix  (company-wide)
     *   Layer 3 — Legacy        : QT-YYMM-XXXX format  (backward-compatible default)
     *
     * The "floor" (minimum sequence number) also follows the same fallback:
     *   store.next_invoice_number → settings.invoice_start_number → 1
     *
     * Uses lockForUpdate() to prevent duplicate numbers under concurrent requests.
     */
    public function generateQuotationNumber(int $companyId, ?int $storeId = null): string
    {
        // --- Layer 1: Store-level prefix (raw DB value, bypasses accessor fallback) ---
        $store          = $storeId ? Store::find($storeId) : null;
        $storePrefix    = $store?->getRawOriginal('quotation_prefix');
        $storeFloor     = $store?->getRawOriginal('next_invoice_number'); // shared floor column

        // --- Layer 2: Company settings table ---
        $settingsPrefix = get_setting('quotation_prefix', null, $companyId) ?: null;
        $settingsStart  = (int) get_setting('invoice_start_number', 1, $companyId);

        // --- Resolve final prefix + floor ---
        if ($storePrefix) {
            // Store has its own prefix → sequence is scoped to this store
            $resolvedPrefix = $storePrefix;
            $floor          = ($storeFloor !== null) ? (int) $storeFloor : $settingsStart;
            $scopeByStore   = true;
        } elseif ($settingsPrefix) {
            // No store prefix, but company settings has one → company-wide sequence
            $resolvedPrefix = $settingsPrefix;
            $floor          = $settingsStart;
            $scopeByStore   = false;
        } else {
            // Layer 3: Nothing configured → keep the legacy format unchanged
            return $this->generateLegacyQuotationNumber($companyId, $storeId);
        }

        // --- Find the latest existing number for this prefix in DB ---
        $query = Quotation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('quotation_number', 'like', $resolvedPrefix . '%')
            ->withTrashed()
            ->lockForUpdate()
            ->orderByDesc('quotation_number');

        if ($scopeByStore && $storeId) {
            $query->where('store_id', $storeId);
        }

        $latest  = $query->value('quotation_number');
        $lastSeq = $latest ? (int) preg_replace('/.*?(\d+)$/', '$1', $latest) : 0;
        $next    = max($lastSeq + 1, $floor);

        return $resolvedPrefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Legacy month-based quotation number: QT-YYMM-XXXX
     * Kept for backward compatibility when no prefix is configured anywhere.
     */
    protected function generateLegacyQuotationNumber(int $companyId, ?int $storeId): string
    {
        $prefix = 'QT-' . date('ym') . '-';

        $query = Quotation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('quotation_number', 'like', $prefix . '%')
            ->withTrashed()
            ->lockForUpdate()
            ->orderByDesc('quotation_number');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $latest  = $query->value('quotation_number');
        $lastSeq = $latest ? (int) substr($latest, -4) : 0;
        $next    = $lastSeq + 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    //  CRUD — CREATE
    // =========================================================================

    /**
     * Create a new quotation inside a DB transaction.
     *
     * @param  array{
     *   store_id: int,
     *   customer_id: int|null,
     *   customer_name: string|null,
     *   customer_phone: string|null,
     *   customer_email: string|null,
     *   customer_gstin: string|null,
     *   billing_address: array|null,
     *   shipping_address: array|null,
     *   reference_number: string|null,
     *   quotation_date: string,
     *   valid_until: string|null,
     *   supply_state: string|null,
     *   gst_treatment: string,
     *   currency_code: string|null,
     *   exchange_rate: float|null,
     *   discount_type: string,
     *   discount_value: float|null,
     *   shipping_charge: float|null,
     *   other_charges: float|null,
     *   notes: string|null,
     *   terms_conditions: string|null,
     *   status: string|null,
     *   items: array,
     * } $data
     */
    public function createQuotation(array $data, int $companyId): Quotation
    {
        return DB::transaction(function () use ($data, $companyId) {

            $company      = Auth::user()->company;
            $client       = isset($data['customer_id']) ? Client::find($data['customer_id']) : null;
            $customerName = $client?->name ?? $data['customer_name'] ?? null;
            $supplyState  = $data['supply_state']
                ?? ($client?->state?->name ?? $company->state?->name ?? 'Gujarat');

            $quotationNumber = $this->generateQuotationNumber($companyId, $data['store_id'] ?? null);

            $status = in_array($data['status'] ?? '', ['draft', 'sent'], true)
                ? $data['status']
                : 'draft';
            $isSent = ($status === 'sent');

            $totals = $this->calculateTotals($data, $supplyState, $company->state?->name ?? '');

            $quotation = Quotation::create(array_merge([
                'company_id'       => $companyId,
                'store_id'         => $data['store_id'],
                'warehouse_id'     => $data['warehouse_id'],
                'customer_id'      => $client?->id,
                'customer_name'    => $customerName,
                'customer_phone'   => $data['customer_phone'] ?? $client?->phone,
                'customer_email'   => $data['customer_email'] ?? $client?->email,
                'customer_gstin'   => $data['customer_gstin'] ?? $client?->gst_number,
                'billing_address'  => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'created_by'       => Auth::id(),
                'quotation_number' => $quotationNumber,
                'reference_number' => $data['reference_number'] ?? null,
                'quotation_date'   => $data['quotation_date'],
                'valid_until'      => $data['valid_until'] ?? null,
                'supply_state'     => $supplyState,
                'gst_treatment'    => $data['gst_treatment'],
                'currency_code'    => $data['currency_code'] ?? 'INR',
                'exchange_rate'    => $data['exchange_rate'] ?? 1.0,
                'notes'            => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'status'           => $status,
                'is_sent'          => $isSent,
                'sent_at'          => $isSent ? now() : null,
                'sent_by'          => $isSent ? Auth::id() : null,
            ], $totals['header']));

            $this->insertItems($quotation, $totals['line_items']);

            return $quotation;
        });
    }

    // =========================================================================
    //  CRUD — UPDATE
    // =========================================================================

    /**
     * Update an existing quotation: wipes old items, recalculates, re-inserts.
     */
    public function updateQuotation(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {

            $company      = Auth::user()->company;
            $client       = isset($data['customer_id']) ? Client::find($data['customer_id']) : null;
            $customerName = $client?->name ?? $data['customer_name'] ?? null;
            $supplyState  = $data['supply_state']
                ?? ($client?->state?->name ?? $company->state?->name ?? 'Gujarat');

            $totals = $this->calculateTotals($data, $supplyState, $company->state?->name ?? '');

            // Status: accept 'draft'|'sent' from request, otherwise keep existing
            $submittedStatus = $data['status'] ?? null;
            $status = in_array($submittedStatus, ['draft', 'sent'], true)
                ? $submittedStatus
                : $quotation->status;

            $headerUpdate = array_merge([
                'store_id'         => $data['store_id'],
                'warehouse_id'     => $data['warehouse_id'],
                'customer_id'      => $client?->id,
                'customer_name'    => $customerName,
                'customer_phone'   => $data['customer_phone'] ?? $client?->phone,
                'customer_email'   => $data['customer_email'] ?? $client?->email,
                'customer_gstin'   => $data['customer_gstin'] ?? $client?->gst_number,
                'billing_address'  => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'quotation_date'   => $data['quotation_date'],
                'valid_until'      => $data['valid_until'] ?? null,
                'supply_state'     => $supplyState,
                'gst_treatment'    => $data['gst_treatment'],
                'currency_code'    => $data['currency_code'] ?? 'INR',
                'exchange_rate'    => $data['exchange_rate'] ?? 1.0,
                'notes'            => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'status'           => $status,
            ], $totals['header']);

            // Stamp sent_at only on the draft→sent transition
            if ($status === 'sent' && $quotation->status !== 'sent') {
                $headerUpdate['is_sent'] = true;
                $headerUpdate['sent_at'] = now();
                $headerUpdate['sent_by'] = Auth::id();
            }

            $quotation->update($headerUpdate);

            // Wipe old items and re-insert recalculated ones
            $quotation->items()->delete();
            $this->insertItems($quotation, $totals['line_items']);

            return $quotation->fresh();
        });
    }

    // =========================================================================
    //  CONVERT QUOTATION → INVOICE
    // =========================================================================

    /**
     * Convert an accepted quotation into a Draft Invoice.
     *
     * The quotation is locked immediately after conversion to prevent double-conversion.
     * Stock is NOT deducted here — that happens when the draft is confirmed by the user.
     *
     * @throws \RuntimeException if no warehouse is configured for the company.
     */
    public function convertToInvoice(Quotation $quotation): \App\Models\Invoice
    {
        return DB::transaction(function () use ($quotation) {

            $companyId = $quotation->company_id;

            $warehouse = Warehouse::where('company_id', $companyId)->first()
                ?? throw new \RuntimeException('No warehouse configured for this company. Please create a warehouse first.');

            $itemsPayload = $quotation->items->map(fn ($item) => [
                'product_sku_id' => $item->product_sku_id,
                'unit_id'        => $item->unit_id,
                'quantity'       => $item->quantity,
                'unit_price'     => $item->unit_price,
                'tax_percent'    => $item->tax_percent,
                'tax_type'       => $item->tax_type ?? 'exclusive',
                'discount_type'  => $item->discount_type,
                'discount_value' => $item->discount_value,
                'batch_id'       => null,
                'batch_number'   => null,
            ])->toArray();

            $invoice = $this->invoiceService->createInvoice([
                'store_id'         => $quotation->store_id,
                'warehouse_id'     => $warehouse->id,
                'customer_id'      => $quotation->customer_id,
                'customer_name'    => $quotation->customer_name,
                'billing_address'  => $quotation->billing_address,
                'shipping_address' => $quotation->shipping_address,
                'source'           => 'direct',
                'invoice_date'     => now()->toDateString(),
                'due_date'         => now()->addDays(7)->toDateString(),
                'supply_state'     => $quotation->supply_state,
                'gst_treatment'    => $quotation->gst_treatment,
                'currency_code'    => $quotation->currency_code,
                'exchange_rate'    => $quotation->exchange_rate,
                'discount_type'    => $quotation->discount_type,
                'discount_value'   => $quotation->discount_value,
                'shipping_charge'  => $quotation->shipping_charge ?? 0,
                'notes'            => $quotation->notes,
                'terms_conditions' => $quotation->terms_conditions,
                'status'           => 'draft',
                'items'            => $itemsPayload,
            ], $companyId);

            // Lock the quotation immediately to prevent double-conversion
            $quotation->update([
                'status'                  => 'converted',
                'converted_to_invoice_id' => $invoice->id,
                'converted_at'            => now(),
            ]);

            return $invoice;
        });
    }

    // =========================================================================
    //  MATH ENGINE — in-memory GST calculation
    // =========================================================================

    /**
     * Calculate all line-item and header totals in memory.
     *
     * Returns:
     *   'header'     — flat array ready for Quotation::create() / update()
     *   'line_items' — array of processed item rows ready for QuotationItem::create()
     *
     * @param  array  $data          Validated request data (items, discount_*, shipping_charge …)
     * @param  string $supplyState   Buyer's state name
     * @param  string $companyState  Seller's state name
     */
    public function calculateTotals(array $data, string $supplyState, string $companyState): array
    {
        $isInterState  = strtolower(trim($supplyState)) !== strtolower(trim($companyState));
        $totalTaxable  = 0.0;
        $totalTax      = 0.0;
        $lineItems     = [];

        foreach ($data['items'] as $item) {
            $qty        = (float) $item['quantity'];
            $price      = (float) $item['unit_price'];
            $taxPct     = (float) ($item['tax_percent'] ?? 0);
            $taxType    = $item['tax_type'] ?? 'exclusive';
            $discType   = ($item['discount_type'] ?? 'fixed') === 'fixed' ? 'fixed' : 'percentage';
            $discValue  = (float) ($item['discount_value'] ?? 0);

            $baseAmount = $qty * $price;

            // 1. Line discount
            $discountAmt = match ($discType) {
                'percentage' => $baseAmount * ($discValue / 100),
                default      => $discValue,
            };
            $afterDiscount = max(0.0, $baseAmount - $discountAmt);

            // 2. GST split
            if ($taxType === 'inclusive') {
                $taxableValue = $afterDiscount / (1 + ($taxPct / 100));
                $taxAmount    = $afterDiscount - $taxableValue;
            } else {
                $taxableValue = $afterDiscount;
                $taxAmount    = $taxableValue * ($taxPct / 100);
            }

            $totalTaxable += $taxableValue;
            $totalTax     += $taxAmount;

            $lineItems[] = [
                'product_id'     => $item['product_id'] ?? null,
                'product_sku_id' => $item['product_sku_id'] ?? null,
                'unit_id'        => $item['unit_id'] ?? null,
                'product_name'   => $item['product_name'],
                'sku_code'       => $item['sku_code'] ?? null,
                // Per-line override wins, then the product master. The same
                // item can be quoted under a different HSN depending on how it
                // is supplied, and many older product records are blank.
                'hsn_code'       => $this->resolveHsnCode($item),
                'quantity'       => $qty,
                'unit_price'     => $price,
                'tax_type'       => $taxType,
                'discount_type'  => $discType,
                'discount_value' => $discValue,
                'discount_amount'=> round($discountAmt, 4),
                'taxable_value'  => $taxableValue,
                'tax_percent'    => $taxPct,
                'igst_amount'    => $isInterState ? $taxAmount : 0,
                'cgst_amount'    => ! $isInterState ? ($taxAmount / 2) : 0,
                'sgst_amount'    => ! $isInterState ? ($taxAmount / 2) : 0,
                'tax_amount'     => $taxAmount,
                'total_amount'   => $taxableValue + $taxAmount,
            ];
        }

        // 3. Global discount (applied on sum of taxable values)
        $globalDiscType  = ($data['discount_type'] ?? 'fixed') === 'fixed' ? 'fixed' : 'percentage';
        $globalDiscValue = (float) ($data['discount_value'] ?? 0);
        $globalDiscAmt   = match ($globalDiscType) {
            'percentage' => $totalTaxable * ($globalDiscValue / 100),
            default      => $globalDiscValue,
        };

        $shipping  = (float) ($data['shipping_charge'] ?? 0);
        $other     = (float) ($data['other_charges'] ?? 0);
        $rawTotal  = ($totalTaxable - $globalDiscAmt) + $totalTax + $shipping + $other;
        $grandTotal = round($rawTotal);
        $roundOff   = round($grandTotal - $rawTotal, 2);

        return [
            'header' => [
                'subtotal'       => $totalTaxable,
                'discount_type'  => $globalDiscType,
                'discount_value' => $globalDiscValue,
                'discount_amount'=> round($globalDiscAmt, 4),
                'taxable_amount' => $totalTaxable - $globalDiscAmt,
                'tax_amount'     => $totalTax,
                'igst_amount'    => $isInterState ? $totalTax : 0,
                'cgst_amount'    => ! $isInterState ? ($totalTax / 2) : 0,
                'sgst_amount'    => ! $isInterState ? ($totalTax / 2) : 0,
                'shipping_charge'=> $shipping,
                'other_charges'  => $other,
                'round_off'      => $roundOff,
                'grand_total'    => $grandTotal,
            ],
            'line_items' => $lineItems,
        ];
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    /**
     * Bulk-insert processed line items for a quotation.
     *
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    private function insertItems(Quotation $quotation, array $lineItems): void
    {
        foreach ($lineItems as $itemData) {
            $itemData['quotation_id'] = $quotation->id;
            QuotationItem::create($itemData);
        }
    }
    /**
     * Decide the HSN code stored on a quotation line.
     *
     * Falls back to the product record so a user who leaves the field alone
     * still gets the catalogue value, without a query when nothing is needed.
     */
    protected function resolveHsnCode(array $item): ?string
    {
        $submitted = trim((string) ($item['hsn_code'] ?? ''));

        if ($submitted !== '') {
            return $submitted;
        }

        // SKU first, then the product it belongs to — the same precedence the
        // search endpoint and the invoice service use, so a quotation and the
        // invoice it converts into never disagree.
        if (! empty($item['product_sku_id'])) {
            $sku = \App\Models\ProductSku::with('product:id,hsn_code')
                ->find($item['product_sku_id']);

            if ($sku) {
                return $sku->hsn_code ?: ($sku->product->hsn_code ?? null);
            }
        }

        if (empty($item['product_id'])) {
            return null;
        }

        return \App\Models\Product::whereKey($item['product_id'])->value('hsn_code');
    }
}