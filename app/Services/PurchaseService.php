<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseService
{
    /**
     * Allowed status transitions.
     *
     * Only the move away from 'received' was guarded, so a cancelled order
     * could be flipped to 'received' and receiveStock() would push its goods
     * into the warehouse. Both 'received' and 'cancelled' are terminal.
     *
     * 'partially_received' appears only as a from-state, for rows that already
     * hold it: nothing in the codebase implements partial receiving.
     */
    private const STATUS_TRANSITIONS = [
        'draft'              => ['ordered', 'received', 'cancelled'],
        'ordered'            => ['received', 'cancelled'],
        'partially_received' => ['received', 'cancelled'],
        'received'           => [],
        'cancelled'          => [],
    ];

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    // ──────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────

    /**
     * Create a new purchase order and calculate all financial lines.
     * If status is 'received', it automatically triggers inventory addition.
     */
    public function createPurchase(array $data): Purchase
    {
        $companyId = Auth::user()->company_id;

        // The lock has to outlive the transaction. Held inside it, the lock is
        // released at the end of the closure but before COMMIT, so the next
        // request reads a MAX() that still cannot see this row and generates
        // the same number.
        return Cache::lock("purchase_number_{$companyId}", 20)
            ->block(10, fn () => $this->persistPurchase($data, $companyId));
    }

    /**
     * The transactional half of createPurchase(), always called while the
     * company's numbering lock is held.
     */
    private function persistPurchase(array $data, int $companyId): Purchase
    {
        return DB::transaction(function () use ($data, $companyId) {

            // 1. Determine Tax Type (Intra-state vs Inter-state)
            $taxType = $this->determineTaxType($companyId, (int) $data['supplier_id']);
            $data['tax_type'] = $taxType;

            // 2. Snapshots for compliance
            $data['company_gst_number'] = Company::find($companyId)->gst_number ?? null;
            $data['supplier_gst_number'] = Supplier::find($data['supplier_id'])->gstin ?? null;

            // 4. Calculate all math (Items + Global)
            $calculatedData = $this->calculateFinancials($data);

            // 🌟 FIX: Extract items array so Eloquent doesn't try to save it to the purchases table
            $items = $calculatedData['items'];
            unset($calculatedData['items']);

            // 5. Create Purchase Header
            // Safe under the caller's lock. lockForUpdate() alone cannot cover
            // the first order of a year: there is no row to lock, so concurrent
            // requests both read nothing and both produce sequence 00001.
            $purchaseNumber = $this->generatePurchaseNumber($companyId);

            $purchase = Purchase::create(array_merge($calculatedData, [
                'purchase_number' => $purchaseNumber,
                'company_id' => $companyId,
                'created_by' => Auth::id(),
                // Make sure company_gst_number and supplier_gst_number are included if calculateFinancials dropped them
                'company_gst_number' => $data['company_gst_number'],
                'supplier_gst_number' => $data['supplier_gst_number'],
            ]));

            // 6. Create Line Items
            foreach ($items as $itemData) { // 👈 Use the extracted $items array
                $itemData['company_id'] = $companyId;
                // If the whole PO is received, mark line items as fully received
                $itemData['quantity_received'] = ($purchase->status === 'received') ? $itemData['quantity'] : 0;

                $purchase->items()->create($itemData);
            }

            // 7. Handle Stock if Received immediately
            if ($purchase->status === 'received') {
                $this->receiveStock($purchase, $items); // 👈 Use extracted $items here too
            }

            return $purchase->load('items');
        });
    }

    /**
     * Update an existing purchase order.
     * Enforces strict accounting guards to prevent modifying received stock.
     */
    public function updatePurchase(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {

            // 🛡️ GUARD: Do not allow modifying items or status if already received.
            // Standard ERP practice: Requires a Purchase Return to reverse.
            if ($purchase->status === 'received') {
                if (isset($data['status']) && $data['status'] !== 'received') {
                    throw new InvalidArgumentException('Cannot revert a fully received purchase. Please create a Purchase Return instead.');
                }

                // Allow updating notes/references, but NOT items or financial amounts
                $purchase->update([
                    'supplier_invoice_number' => $data['supplier_invoice_number'] ?? $purchase->supplier_invoice_number,
                    'supplier_invoice_date' => $data['supplier_invoice_date'] ?? $purchase->supplier_invoice_date,
                    'notes' => $data['notes'] ?? $purchase->notes,
                    'updated_by' => Auth::id(),
                ]);

                return $purchase;
            }

            // 1. Capture old status BEFORE update (getOriginal is reset by Eloquent after save)
            $oldStatus = $purchase->status;

            $this->assertStatusTransition($oldStatus, $data['status'] ?? $oldStatus, $purchase);

            // 2. Recalculate Tax Type in case Supplier changed
            $data['tax_type'] = $this->determineTaxType($purchase->company_id, (int) $data['supplier_id']);

            // 3. Recalculate all financials
            $calculatedData = $this->calculateFinancials($data);

            // Prevent wiping out previous payments on edit.
            // paid_amount is the stored truth; deriving it from total minus
            // balance broke as soon as the total changed. It is also written
            // back below, because recalculating balance_amount alone left
            // paid_amount + balance_amount no longer equal to total_amount.
            $amountPreviouslyPaid = (float) $purchase->paid_amount;
            $newBalance = max(0, $calculatedData['total_amount'] - $amountPreviouslyPaid);

            $calculatedData['paid_amount']    = $amountPreviouslyPaid;
            $calculatedData['balance_amount'] = $newBalance;

            if ($newBalance <= 0 && $calculatedData['total_amount'] > 0) {
                $calculatedData['payment_status'] = 'paid';
            } elseif ($newBalance < $calculatedData['total_amount']) {
                $calculatedData['payment_status'] = 'partial';
            } else {
                $calculatedData['payment_status'] = 'unpaid';
            }

            // 🌟 PRODUCTION FIX 2: Safely extract items before updating header
            $items = $calculatedData['items'];
            unset($calculatedData['items']);

            // 4. Update Header
            $purchase->update(array_merge($calculatedData, [
                'updated_by' => Auth::id(),
            ]));

            // 5. Sync Line Items (Create, Update, Delete)
            $this->syncLineItems($purchase, $items);

            // 6. Handle Stock if status transitioned to Received
            if ($oldStatus !== 'received' && $purchase->status === 'received') {
                $this->receiveStock($purchase, $items);
            }

            return $purchase->load('items');
        });
    }

    // ──────────────────────────────────────────────────────────────
    // CORE BUSINESS LOGIC & MATH
    // ──────────────────────────────────────────────────────────────

    /**
     * Processes inventory addition using the robust InventoryService
     */
    private function receiveStock(Purchase $purchase, array $itemsData): void
    {
        $batchEnabled = batch_enabled();

        $skus = ProductSku::whereIn('id', array_column($itemsData, 'product_sku_id'))
            ->get()
            ->keyBy('id');
        foreach ($itemsData as $item) {

            $batchId = null;
            $batchNumber = null;

            if ($batchEnabled) {

                $secureBatchNumber = ! empty($item['batch_number'])
                    ? $item['batch_number']
                    : 'B-'.now()->format('ym').'-'.strtoupper(Str::random(5));

                // A supplier-supplied batch number can legitimately arrive twice
                // — a second delivery of the same lot. Creating a fresh row each
                // time split one physical batch across duplicates that expiry
                // and traceability lookups then reported separately.
                $batch = ProductBatch::firstOrNew([
                    'company_id' => $purchase->company_id,
                    'product_sku_id' => $item['product_sku_id'],
                    'warehouse_id' => $purchase->warehouse_id,
                    'batch_number' => $secureBatchNumber,
                ]);

                if ($batch->exists) {
                    $batch->qty += $item['quantity'];
                    $batch->remaining_qty += $item['quantity'];
                    $batch->save();
                } else {
                    $batch->fill([
                        'supplier_id' => $purchase->supplier_id,
                        'manufacturing_date' => $item['manufacturing_date'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'purchase_price' => $item['unit_cost'],
                        'qty' => $item['quantity'],
                        'remaining_qty' => $item['quantity'],
                    ])->save();
                }

                $batchId = $batch->id;
                $batchNumber = $batch->batch_number;
            }

            $sku = $skus[$item['product_sku_id']];

            // Read before the stock lands, so the average weighs what was
            // already on hand against what is arriving.
            $qtyOnHandBefore = (float) ProductStock::where('product_sku_id', $sku->id)->sum('qty');

            $this->inventoryService->addStock(
                sku: $sku,
                warehouseId: $purchase->warehouse_id,
                qty: $item['quantity'],
                movementType: 'purchase',
                reference: $purchase,
                batchId: $batchId,
                batchNumber: $batchNumber,
                unitCost: $item['unit_cost']
            );

            // $item->update([
            //     'quantity_received' => $item->quantity
            // ]);

            // Weighted average, not last-purchase-price. Overwriting outright
            // meant a single unit bought at an odd price repriced the entire
            // shelf and skewed every valuation and margin report.
            $incomingQty  = (float) $item['quantity'];
            $incomingCost = (float) $item['unit_cost'];
            $existingCost = (float) $sku->cost;

            // Averaging needs a real cost on both sides. A SKU that has never
            // been purchased carries null or 0, and weighing stock at 0 against
            // the incoming price drags the result to near zero instead of
            // raising it. In that case the incoming price is the only figure
            // that means anything.
            $sku->update([
                'cost' => ($qtyOnHandBefore > 0 && $existingCost > 0)
                    ? round(
                        (($qtyOnHandBefore * $existingCost) + ($incomingQty * $incomingCost))
                        / ($qtyOnHandBefore + $incomingQty),
                        4
                    )
                    : $incomingCost,
            ]);

            logger()->info('Purchase Stock Added', [
                'sku_id' => $sku->id,
                'warehouse' => $purchase->warehouse_id,
                'qty' => $item['quantity'],
                'batch_id' => $batchId,
            ]);
        }
    }

    /**
     * Handles creating, updating, and deleting items on an existing Purchase.
     */
    /**
     * Handles creating, updating, and deleting items on an existing Purchase.
     */
    private function syncLineItems(Purchase $purchase, array $itemsData): void
    {
        $existingItemIds = $purchase->items()->pluck('id')->toArray();
        $providedItemIds = array_filter(array_column($itemsData, 'id'));

        // 1. Delete items removed from the form
        $itemsToDelete = array_diff($existingItemIds, $providedItemIds);
        if (! empty($itemsToDelete)) {
            PurchaseItem::whereIn('id', $itemsToDelete)->delete();
        }

        $batchEnabled = batch_enabled();
        $isReceived = ($purchase->status === 'received');

        // 2. Update or Create
        foreach ($itemsData as $itemData) {
            $itemData['company_id'] = $purchase->company_id;

            // 🌟 FIX 1: Properly assign quantity_received like we do in createPurchase!
            $itemData['quantity_received'] = $isReceived ? $itemData['quantity'] : 0;

            // 🌟 FIX 2: "if batch enabled then..." -> clear them out if disabled!
            if (! $batchEnabled) {
                $itemData['batch_number'] = null;
                $itemData['manufacturing_date'] = null;
                $itemData['expiry_date'] = null;
            }

            if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                // 🌟 FIX 3: Use Eloquent's find()->update() instead of where()->update()
                // This forces Laravel to use $fillable, automatically throwing away
                // the extra frontend UI fields (like product_name) so it doesn't crash MySQL.
                PurchaseItem::find($itemData['id'])->update($itemData);
            } else {
                // Create
                $purchase->items()->create($itemData);
            }
        }
    }

    /**
     * Engine for calculating all line item subtotals, inclusive/exclusive taxes, and global totals.
     */
    private function calculateFinancials(array $data): array
    {
        $globalSubtotal = 0;
        $globalTaxable = 0;
        $globalCgst = 0;
        $globalSgst = 0;
        $globalIgst = 0;
        $globalTaxAmt = 0;

        $taxType = $data['tax_type']; // 'cgst_sgst' or 'igst'

        foreach ($data['items'] as &$item) {
            $qty = (float) $item['quantity'];
            $cost = (float) $item['unit_cost'];
            $taxPct = (float) ($item['tax_percent'] ?? 0);
            $isInclusive = ($item['tax_type'] ?? 'exclusive') === 'inclusive';

            // Extract new discount fields
            $discountType = $item['discount_type'] ?? 'percent';
            $discountValueInput = (float) ($item['discount_value'] ?? 0);

            // 🌟 ROOT FIX: Match ENUM and retain the UI input values
            $item['discount_type'] = ($discountType === 'fixed') ? 'fixed' : 'percentage';
            $item['discount_value'] = $discountValueInput;

            // Base Subtotal
            $itemSubtotal = $qty * $cost;
            $item['subtotal'] = round($itemSubtotal, 4);

            // Calculate Item-Level Line Discount
            $discountAmt = 0.0;
            if ($item['discount_type'] === 'percentage') {
                $discountAmt = $itemSubtotal * ($discountValueInput / 100);
            } else {
                $discountAmt = $discountValueInput;
            }

            // Prevent negative totals and apply discount
            $afterDiscount = max(0, $itemSubtotal - $discountAmt);
            $item['discount_amount'] = round($discountAmt, 4);

            // Inclusive / Exclusive Tax Math
            $taxableAmt = 0.0;
            $taxAmt = 0.0;

            if ($isInclusive) {
                // Price includes tax -> extract it backward
                $taxableAmt = $afterDiscount / (1 + ($taxPct / 100));
                $taxAmt = $afterDiscount - $taxableAmt;
            } else {
                // Price excludes tax -> add it on top
                $taxableAmt = $afterDiscount;
                $taxAmt = $taxableAmt * ($taxPct / 100);
            }

            // purchase_items columns — NOT the invoice_items names. taxable_value
            // and total_amount are not columns here, so $fillable dropped them
            // silently and every line was stored with 0 in both.
            $item['taxable_amount'] = round($taxableAmt, 4);
            $item['tax_amount'] = round($taxAmt, 4);
            $item['total_price'] = round($taxableAmt + $taxAmt, 4);

            // Split Indian GST
            $item['cgst_amount'] = 0;
            $item['sgst_amount'] = 0;
            $item['igst_amount'] = 0;
            $item['cgst_percent'] = 0;
            $item['sgst_percent'] = 0;
            $item['igst_percent'] = 0;

            if ($taxType === 'cgst_sgst') {
                $item['cgst_percent'] = $taxPct / 2;
                $item['sgst_percent'] = $taxPct / 2;
                $item['cgst_amount'] = round($taxAmt / 2, 4);
                $item['sgst_amount'] = round($taxAmt / 2, 4);
            } elseif ($taxType === 'igst') {
                $item['igst_percent'] = $taxPct;
                $item['igst_amount'] = round($taxAmt, 4);
            }

            // Aggregate up to global totals
            $globalSubtotal += $item['subtotal'];
            $globalTaxable += $item['taxable_amount'];
            $globalTaxAmt += $item['tax_amount'];
            $globalCgst += $item['cgst_amount'];
            $globalSgst += $item['sgst_amount'];
            $globalIgst += $item['igst_amount'];
        }
        unset($item);

        // Apply Global Extra Charges & Rounding
        // 🌟 ROOT FIX: Normalize global discount type to match database ENUM and retain value
        $data['discount_type'] = (isset($data['discount_type']) && $data['discount_type'] === 'fixed') ? 'fixed' : 'percentage';
        $data['discount_value'] = (float) ($data['discount_value'] ?? 0);
        
        $globalDiscount = (float) ($data['discount_amount'] ?? 0); // Flat bill cash discount
        $shipping = (float) ($data['shipping_cost'] ?? 0);
        $other = (float) ($data['other_charges'] ?? 0);

        // 🌟 ROOT FIX: Global Discount reduces the GRAND TOTAL, not the Taxable Value.
        $totalBeforeRound = $globalTaxable + $globalTaxAmt - $globalDiscount + $shipping + $other;

        // Indian accounting standard: Round to nearest Rupee
        $roundedTotal = round($totalBeforeRound);
        $roundOff = $roundedTotal - $totalBeforeRound;

        // Assign to header data
        $data['subtotal'] = round($globalSubtotal, 2);
        $data['taxable_amount'] = round($globalTaxable, 2); // Header retains taxable_amount
        $data['cgst_amount'] = round($globalCgst, 2);
        $data['sgst_amount'] = round($globalSgst, 2);
        $data['igst_amount'] = round($globalIgst, 2);
        $data['tax_amount'] = round($globalTaxAmt, 2);
        $data['round_off'] = round($roundOff, 2);
        $data['total_amount'] = $roundedTotal;
        $data['balance_amount'] = $roundedTotal; // Default, overridden in updatePurchase if needed

        return $data;
    }
        /**
     * Reject a status move that is not on the allowed path.
     */
    private function assertStatusTransition(string $from, string $to, Purchase $purchase): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true)) {
            $readableFrom = str_replace('_', ' ', $from);
            $readableTo   = str_replace('_', ' ', $to);

            throw new InvalidArgumentException(
                "A purchase cannot move from {$readableFrom} to {$readableTo}."
            );
        }

        // Cancelling an order that has money against it would strand those
        // payments on a document nobody can act on.
        if ($to === 'cancelled' && $purchase->payments()->where('status', 'completed')->exists()) {
            throw new InvalidArgumentException(
                'This purchase has recorded payments and cannot be cancelled. Reverse the payments first.'
            );
        }
    }
    /**
     * Determines whether to apply IGST or CGST/SGST based on States
     */
    private function determineTaxType(int $companyId, int $supplierId): string
    {
        $company = Company::find($companyId);
        $supplier = Supplier::find($supplierId);

        // Fallback if missing data
        if (! $company || ! $supplier || ! $company->state_id || ! $supplier->state_id) {
            return 'none';
        }

        return ($company->state_id === $supplier->state_id) ? 'cgst_sgst' : 'igst';
    }

    /**
     * Auto-generates the next Purchase Order Number
     */
    private function generatePurchaseNumber(int $companyId): string
    {
        $year = now()->year;

        $lastPurchase = Purchase::where('company_id', $companyId)
            ->withTrashed() // 🌟 THE FIX: Look in the trash bin too!
            ->where('purchase_number', 'like', "PO-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastPurchase) {
            $parts = explode('-', $lastPurchase->purchase_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('PO-%s-%05d', $year, $sequence);
    }
}
