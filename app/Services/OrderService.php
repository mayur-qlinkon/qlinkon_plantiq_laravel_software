<?php

namespace App\Services;

use App\Events\Orders\OrderPlaced;
use App\Models\Company;
use App\Models\Store;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}
    // ════════════════════════════════════════════════════
    //  PLACE ORDER (storefront guest checkout)
    //  - Validates cart items against DB (never trust frontend prices)
    //  - Calculates GST: CGST+SGST (intra) or IGST (inter)
    //  - Applies round-off (Indian accounting)
    //  - Saves order + items in one transaction
    //  - Logs initial status history
    //  - Fires WhatsApp notification (fire-and-forget)
    // ════════════════════════════════════════════════════

    public function placeOrder(array $data, int $companyId): Order
    {
        return DB::transaction(function () use ($data, $companyId) {

            Log::info('[OrderService] Placing order', [
                'company_id' => $companyId,
                'customer_phone' => $data['customer_phone'] ?? null,
                'items_count' => count($data['items'] ?? []),
                'source' => $data['source'] ?? 'storefront',
            ]);

            // ── Step 1: Resolve and validate all cart items from DB ──
            $resolvedItems = $this->resolveCartItems($data['items'], $companyId);

            // ── Step 2: Determine GST type ──
            // Intra-state (same state) → CGST + SGST
            // Inter-state (different state) → IGST
            $supplyState = $data['supply_state'] ?? $data['delivery_state'] ?? null;
            $companyState = $this->getCompanyState($companyId);
            $isInterState = $this->isInterState($supplyState, $companyState);

            Log::info('[OrderService] GST determination', [
                'supply_state' => $supplyState,
                'company_state' => $companyState,
                'is_inter_state' => $isInterState,
            ]);

            // ── Step 3: Calculate all amounts ──
            $calculation = $this->calculateTotals($resolvedItems, $isInterState);

            $shippingAmount = round((float) ($data['shipping_amount'] ?? 0), 2);

            // ── Step 4: Build order payload ──
            $orderData = [
                'company_id' => $companyId,
                'order_type' => $data['order_type'] ?? 'retail',
                'source' => $data['source'] ?? 'storefront',
                'status' => 'inquiry',

                // Customer
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,

                // Address
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_city' => $data['delivery_city'] ?? null,
                'delivery_state' => $data['delivery_state'] ?? null,
                'delivery_pincode' => $data['delivery_pincode'] ?? null,
                'delivery_country' => $data['delivery_country'] ?? 'India',
                'supply_state' => $supplyState,

                // Pricing
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'cgst_amount' => $calculation['cgst_amount'],
                'sgst_amount' => $calculation['sgst_amount'],
                'igst_amount' => $calculation['igst_amount'],
                'tax_amount' => $calculation['tax_amount'],
                'shipping_amount' => $shippingAmount,
                'round_off' => $calculation['round_off'],
                // Shipping was stored on the order but never added to the
                // total, so a customer shown ₹800 + ₹150 was charged ₹800.
                // The admin path already did this; only this one did not.
                'total_amount' => round($calculation['total_amount'] + $shippingAmount, 2),
                'currency' => 'INR',

                // Coupon
                'coupon_code' => $data['coupon_code'] ?? null,
                'coupon_discount' => $data['coupon_discount'] ?? 0,

                // Payment
                'payment_method' => $data['payment_method'] ?? 'cod',
                'payment_status' => 'pending',

                // Fulfillment
                'delivery_type' => $data['delivery_type'] ?? 'delivery',
                'store_id' => get_setting('default_storefront_store_id', null, $companyId)
                    ?? Store::where('company_id', $companyId)
                                        ->where('is_active', true)
                                        ->value('id'),
                'warehouse_id' => $data['warehouse_id'] ?? null,

                // Notes
                'customer_notes' => $data['customer_notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,

                // Denormalized counts
                'items_count' => count($resolvedItems),
                'items_qty' => array_sum(array_column($resolvedItems, 'qty')),

                // Audit — a storefront order is never created BY a staff member,
                // even when one happens to be logged in on the same browser.
                // Auth::id() here would be whoever is signed into the backend,
                // which corrupts every "created by" report and filter.
                // This matches the status history below, which already records
                // this path as changed_by_type = 'system', changed_by = null.
                'created_by' => null,
            ];

            // ── Step 5: Create order ──
            $order = Order::create($orderData);

            Log::info('[OrderService] Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total_amount,
            ]);

            // ── Step 6: Create order items ──
            foreach ($resolvedItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            // ── Step 7: Log initial status history ──
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'inquiry',
                'notes' => 'Order placed via '.($data['source'] ?? 'storefront'),
                'changed_by_type' => 'system',
                'changed_by' => null,
            ]);

            // ── Step 8: Fire WhatsApp notification to owner (fire-and-forget) ──
            $this->notifyOwnerWhatsApp($order);

            // ── Step 9: Auto-create/update CRM lead (fire-and-forget) ──
            app(CrmLeadService::class)
                ->createOrUpdateFromOrder($order);

            event(new OrderPlaced($order));

            return $order->load('items');
        });
    }

    // ════════════════════════════════════════════════════
    //  CREATE OFFLINE ORDER (Admin / POS)
    //  - Bypasses storefront hardcodes (inquiry/pending)
    //  - Allows instant confirmation and payment marking
    //  - Defaults to Walk-in customer if no data provided
    //  - Safely calculates GST while allowing manual discounts
    // ════════════════════════════════════════════════════

    public function createOfflineOrder(array $data, int $companyId): Order
    {
        return DB::transaction(function () use ($data, $companyId) {

            Log::info('[OrderService] Creating offline order', [
                'company_id' => $companyId,
                'source' => $data['source'] ?? 'admin',
                'admin_id' => Auth::id(),
            ]);

            // ── Step 1: Resolve Items & Calculate GST ──
            $resolvedItems = $this->resolveCartItems($data['items'], $companyId);

            $supplyState = $data['supply_state'] ?? $data['delivery_state'] ?? null;
            $companyState = $this->getCompanyState($companyId);
            $isInterState = $this->isInterState($supplyState, $companyState);

            $calculation = $this->calculateTotals($resolvedItems, $isInterState);

            // ── Step 2: Admin Overrides & Defaults ──
            $orderStatus = $data['status'] ?? 'confirmed'; // Offline orders usually skip 'inquiry'
            $paymentStatus = $data['payment_status'] ?? 'paid'; // Assumed paid if at counter

            // Process manual admin discount and shipping
            $manualDiscount = (float) ($data['discount_amount'] ?? 0);
            $shippingAmount = (float) ($data['shipping_amount'] ?? 0);

            // Apply to grand total (ensuring it never drops below 0)
            $finalTotal = max(0, $calculation['total_amount'] - $manualDiscount + $shippingAmount);

            // ── Step 3: Build Order Payload ──
            $orderData = [
                'company_id' => $companyId,
                'order_type' => $data['order_type'] ?? 'retail',
                'source' => $data['source'] ?? 'admin',
                'status' => $orderStatus,

                // Walk-in Customer Fallbacks (Prevents DB crash if left blank)
                'customer_name' => ! empty($data['customer_name']) ? $data['customer_name'] : 'Walk-in Customer',
                'customer_phone' => ! empty($data['customer_phone']) ? $data['customer_phone'] : '0000000000',
                'customer_email' => $data['customer_email'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,

                // Delivery Data
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_city' => $data['delivery_city'] ?? null,
                'delivery_state' => $data['delivery_state'] ?? null,
                'delivery_pincode' => $data['delivery_pincode'] ?? null,
                'delivery_country' => $data['delivery_country'] ?? 'India',
                'supply_state' => $supplyState,

                // Pricing (Merged calculated GST with manual discount/shipping)
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $manualDiscount,
                'cgst_amount' => $calculation['cgst_amount'],
                'sgst_amount' => $calculation['sgst_amount'],
                'igst_amount' => $calculation['igst_amount'],
                'tax_amount' => $calculation['tax_amount'],
                'shipping_amount' => $shippingAmount,
                'round_off' => $calculation['round_off'],
                'total_amount' => $finalTotal,
                'currency' => 'INR',

                // Payment
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $paymentStatus,
                'paid_at' => $paymentStatus === 'paid' ? now() : null,

                // Fulfillment (Offline usually defaults to pickup/POS)
                'delivery_type' => $data['delivery_type'] ?? 'pickup',
                'store_id' => $data['store_id'] ?? active_store()?->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,

                // Notes
                'customer_notes' => $data['customer_notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,

                // Counts
                'items_count' => count($resolvedItems),
                'items_qty' => array_sum(array_column($resolvedItems, 'qty')),

                // Audit
                'created_by' => Auth::id(),
                'confirmed_by' => $orderStatus === 'confirmed' ? Auth::id() : null,
                'confirmed_at' => $orderStatus === 'confirmed' ? now() : null,
            ];

            // ── Step 4: Insert Data ──
            $order = Order::create($orderData);

            foreach ($resolvedItems as $item) {
                OrderItem::create(array_merge($item, ['order_id' => $order->id]));
            }

            // ── Step 5: Log History securely attributed to Admin ──
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => $orderStatus,
                'notes' => 'Order created manually by Admin/Staff.',
                'changed_by_type' => 'admin',
                'changed_by' => Auth::id(),
            ]);

            Log::info('[OrderService] Offline order successfully generated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total_amount,
            ]);

            return $order->load('items');
        });
    }

    // ════════════════════════════════════════════════════
    //  UPDATE ORDER STATUS
    // ════════════════════════════════════════════════════

    public function updateStatus(
        Order $order,
        string $newStatus,
        ?string $notes = null,
        string $changedByType = 'admin'
    ): Order {
        try {
            $order->transitionTo($newStatus, $notes, $changedByType);

            Log::info('[OrderService] Status updated', [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'by' => Auth::id(),
            ]);

            return $order->fresh();

        } catch (Throwable $e) {
            Log::error('[OrderService] Status update failed', [
                'order_id' => $order->id,
                'to' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  UPDATE ORDER DETAILS (admin edits)
    // ════════════════════════════════════════════════════

    // ════════════════════════════════════════════════════
    //  UPDATE ORDER DETAILS (admin edits)
    // ════════════════════════════════════════════════════

    public function updateOrder(Order $order, array $data): Order
    {
        DB::beginTransaction();

        try {
            // 1. Separate the items array from the main order data
            $items = $data['items'] ?? null;
            unset($data['items']);

            // 2. Update the main order record (status, customer info, notes).
            //    Totals are NOT taken from here — they are derived below.
            $order->update($data);

            // 3. Rebuild the items and re-derive every total from them.
            //
            //    This used to write the items and stop, leaving subtotal,
            //    total_amount, items_count and items_qty holding the values
            //    from before the edit — an order showing 3 units on the line
            //    and a 2-unit total. It also trusted the browser's unit_price.
            //
            //    resolveCartItems() is the same authority createOfflineOrder()
            //    uses: it re-reads price from the SKU, scoped to this company,
            //    and rejects inactive or foreign SKUs.
            if ($items !== null) {

                // ⚠️ NOTE: If you are tracking inventory, you need to restore stock
                // for the old items here before deleting them!

                $resolvedItems = $this->resolveCartItems($items, $order->company_id);

                $supplyState = $order->supply_state ?? $order->delivery_state ?? null;
                $isInterState = $this->isInterState($supplyState, $this->getCompanyState($order->company_id));

                $calculation = $this->calculateTotals($resolvedItems, $isInterState);

                // The edit form may change either of these, so read them from
                // the freshly updated order rather than the incoming payload.
                $manualDiscount = (float) $order->discount_amount;
                $shippingAmount = (float) $order->shipping_amount;

                $order->items()->delete();

                foreach ($resolvedItems as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }

                $order->update([
                    'subtotal' => $calculation['subtotal'],
                    'cgst_amount' => $calculation['cgst_amount'],
                    'sgst_amount' => $calculation['sgst_amount'],
                    'igst_amount' => $calculation['igst_amount'],
                    'tax_amount' => $calculation['tax_amount'],
                    'round_off' => $calculation['round_off'],
                    'total_amount' => max(0, $calculation['total_amount'] - $manualDiscount + $shippingAmount),

                    'items_count' => count($resolvedItems),
                    'items_qty' => array_sum(array_column($resolvedItems, 'qty')),
                ]);
            }

            DB::commit();

            Log::info('[OrderService] Order updated', [
                'order_id' => $order->id,
                'fields' => array_keys($data),
                'by' => Auth::id(),
            ]);

            // Return fresh order with the newly attached items
            return $order->fresh(['items']);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('[OrderService] Update failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ════════════════════════════════════════════════════
    //  CANCEL ORDER
    // ════════════════════════════════════════════════════

    public function cancelOrder(Order $order, string $reason, string $cancelledByType = 'admin'): Order
    {
        if (! $order->is_cancellable) {
            throw new \InvalidArgumentException(
                "Order #{$order->order_number} cannot be cancelled at status: {$order->status}"
            );
        }

        $order->cancellation_reason = $reason;
        $order->save();

        $order->transitionTo('cancelled', $reason, $cancelledByType);

        Log::info('[OrderService] Order cancelled', [
            'order_id' => $order->id,
            'reason' => $reason,
            'by' => Auth::id(),
        ]);

        return $order->fresh();
    }

    // ════════════════════════════════════════════════════
    //  RAZORPAY PAYMENT CONFIRMATION (future hook)
    //  Call this from Razorpay webhook controller
    //  Zero changes needed to placeOrder() flow
    // ════════════════════════════════════════════════════

    public function confirmRazorpayPayment(
        Order $order,
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): Order {
        DB::transaction(function () use ($order, $razorpayOrderId, $razorpayPaymentId, $razorpaySignature) {
            $order->update([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            // Auto-confirm on successful payment
            $order->transitionTo('confirmed', 'Payment confirmed via Razorpay', 'razorpay');

            Log::info('[OrderService] Razorpay payment confirmed', [
                'order_id' => $order->id,
                'razorpay_payment_id' => $razorpayPaymentId,
            ]);
        });

        return $order->fresh();
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Resolve cart items from DB
    //  Never trust prices from frontend
    // ════════════════════════════════════════════════════

    private function resolveCartItems(array $cartItems, int $companyId): array
    {
        $resolved = [];

        foreach ($cartItems as $index => $item) {

            // Fetch SKU from DB — validates it belongs to this company
            $sku = ProductSku::whereHas('product', fn ($q) => $q->where('company_id', $companyId)
                ->where('is_active', true)
            )
                ->where('id', $item['sku_id'])
                ->where('is_active', true)
                ->with('product')
                ->first();

            if (! $sku) {
                Log::warning('[OrderService] SKU not found or inactive', [
                    'sku_id' => $item['sku_id'],
                    'company_id' => $companyId,
                    'index' => $index,
                ]);
                throw new \InvalidArgumentException(
                    "Product variant (SKU ID: {$item['sku_id']}) is not available."
                );
            }

            $product = $sku->product;
            $qty = (int) $item['qty'];
            $unitPrice = (float) $sku->price;   // always from DB
            $costPrice = (float) $sku->cost;    // always from DB
            $lineTotal = $unitPrice * $qty;

            // Build variant label from sku values if available
            $skuLabel = $item['variant'] ?? null;
            if (! $skuLabel && $sku->relationLoaded('skuValues')) {
                $skuLabel = $sku->skuValues
                    ->map(fn ($sv) => $sv->attributeValue?->value)
                    ->filter()
                    ->join(' / ');
            }

            $resolved[] = [
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'product_name' => $product->name,
                'sku_label' => $skuLabel,
                'sku_code' => $sku->sku ?? null,
                'product_image' => $item['image'] ?? $product->primary_image_url ?? null,
                'hsn_code' => $sku->hsn_code ?? $product->hsn_code ?? null,
                'unit_price' => $unitPrice,
                'cost_price' => $costPrice,
                'qty' => $qty,
                'discount_amount' => 0,
                // order_tax is the SKU's GST percentage — the field ProductService
                // writes and PosService reads. This used to read
                // products.tax_rate, a column that exists on no table, so it
                // resolved to null and every storefront order was placed with no
                // tax at all. product_skus.gst_rate looks like the right field
                // but is never written to by anything and is always null.
                'tax_rate' => (float) ($sku->order_tax ?? 0),
                'tax_type' => $sku->tax_type ?? 'exclusive',
                'cgst_rate' => 0,
                'sgst_rate' => 0,
                'igst_rate' => 0,
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'igst_amount' => 0,
                'tax_amount' => 0,
                'line_total' => $lineTotal,
                'status' => 'pending',
            ];

            Log::info('[OrderService] Item resolved', [
                'product' => $product->name,
                'sku_id' => $sku->id,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        return $resolved;
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Calculate totals + GST + round-off
    // ════════════════════════════════════════════════════

    /**
     * What a cart would total if it were placed right now.
     *
     * The storefront cart lives in the browser and its total was worked out
     * there as price × qty, so a customer was quoted a figure with neither GST
     * nor shipping in it and then charged a different one. Prices, tax rates
     * and tax types are all re-read from the database here — the browser sends
     * only SKU ids and quantities, and is never trusted with money.
     *
     * @param  array  $items  [['sku_id' => int, 'qty' => int], ...]
     */
    public function previewTotals(array $items, int $companyId, ?string $supplyState = null, float $shippingAmount = 0): array
    {
        $resolved = $this->resolveCartItems($items, $companyId);

        if ($resolved === []) {
            return ['subtotal' => 0, 'tax_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0,
                'igst_amount' => 0, 'shipping_amount' => 0, 'round_off' => 0, 'total_amount' => 0];
        }

        $isInterState = $this->isInterState($supplyState, $this->getCompanyState($companyId));
        $calculation = $this->calculateTotals($resolved, $isInterState);

        $shippingAmount = round($shippingAmount, 2);

        return [
            'subtotal' => $calculation['subtotal'],
            'cgst_amount' => $calculation['cgst_amount'],
            'sgst_amount' => $calculation['sgst_amount'],
            'igst_amount' => $calculation['igst_amount'],
            'tax_amount' => $calculation['tax_amount'],
            'shipping_amount' => $shippingAmount,
            'round_off' => $calculation['round_off'],
            'total_amount' => round($calculation['total_amount'] + $shippingAmount, 2),
        ];
    }

    private function calculateTotals(array &$items, bool $isInterState): array
    {
        $subtotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;
        $igstTotal = 0;

        foreach ($items as &$item) {
            $gross = $item['unit_price'] * $item['qty'] - $item['discount_amount'];
            $taxRate = $item['tax_rate'];

            // An inclusive price already contains its GST, so the tax is
            // extracted backwards out of it rather than added on top. This was
            // the only place in the codebase that ignored tax_type — invoices,
            // quotations, purchases and POS all honour it — so an inclusive SKU
            // sold through the storefront would have been taxed twice.
            if (($item['tax_type'] ?? 'exclusive') === 'inclusive') {
                $lineBase = $taxRate > 0 ? $gross / (1 + ($taxRate / 100)) : $gross;
            } else {
                $lineBase = $gross;
            }

            $lineBase = round($lineBase, 2);

            if ($isInterState) {
                // Inter-state: full tax as IGST
                $igstRate = $taxRate;
                $igstAmt = round($lineBase * $igstRate / 100, 2);
                $item['igst_rate'] = $igstRate;
                $item['igst_amount'] = $igstAmt;
                $item['tax_amount'] = $igstAmt;
                $igstTotal += $igstAmt;
            } else {
                // Intra-state: split tax as CGST + SGST (equal halves)
                $halfRate = $taxRate / 2;
                $cgstAmt = round($lineBase * $halfRate / 100, 2);
                $sgstAmt = round($lineBase * $halfRate / 100, 2);
                $item['cgst_rate'] = $halfRate;
                $item['sgst_rate'] = $halfRate;
                $item['cgst_amount'] = $cgstAmt;
                $item['sgst_amount'] = $sgstAmt;
                $item['tax_amount'] = $cgstAmt + $sgstAmt;
                $cgstTotal += $cgstAmt;
                $sgstTotal += $sgstAmt;
            }

            $item['line_total'] = round($lineBase + $item['tax_amount'], 2);
            $subtotal += $lineBase;
        }
        unset($item); // break reference

        $taxAmount = round($cgstTotal + $sgstTotal + $igstTotal, 2);
        $rawTotal = round($subtotal + $taxAmount, 2);

        // Round-off — nearest rupee (Indian accounting)
        $roundedTotal = round($rawTotal);
        $roundOff = round($roundedTotal - $rawTotal, 2);

        Log::info('[OrderService] Totals calculated', [
            'subtotal' => $subtotal,
            'cgst' => $cgstTotal,
            'sgst' => $sgstTotal,
            'igst' => $igstTotal,
            'tax_total' => $taxAmount,
            'raw_total' => $rawTotal,
            'round_off' => $roundOff,
            'final_total' => $roundedTotal,
            'inter_state' => $isInterState,
        ]);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => 0,
            'cgst_amount' => round($cgstTotal, 2),
            'sgst_amount' => round($sgstTotal, 2),
            'igst_amount' => round($igstTotal, 2),
            'tax_amount' => $taxAmount,
            'round_off' => $roundOff,
            'total_amount' => (float) $roundedTotal,
        ];
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Get company's registered state for GST
    // ════════════════════════════════════════════════════

    private function getCompanyState(int $companyId): ?string
    {
        try {
            return Company::find($companyId)?->state?->name
                ?? Company::find($companyId)?->city
                ?? null;
        } catch (Throwable $e) {
            Log::warning('[OrderService] Could not fetch company state', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — Inter-state check
    // ════════════════════════════════════════════════════

    private function isInterState(?string $supplyState, ?string $companyState): bool
    {
        // If either state is unknown, default to intra-state (safer for small businesses)
        if (! $supplyState || ! $companyState) {
            Log::warning('[OrderService] State unknown — defaulting to intra-state GST', [
                'supply_state' => $supplyState,
                'company_state' => $companyState,
            ]);

            return false;
        }

        return strtolower(trim($supplyState)) !== strtolower(trim($companyState));
    }

    // ════════════════════════════════════════════════════
    //  PRIVATE — WhatsApp notification to owner
    //  Fire-and-forget — never crashes the order
    // ════════════════════════════════════════════════════

    private function notifyOwnerWhatsApp(Order $order): void
    {
        try {
            $whatsapp = get_setting('whatsapp', null, $order->company_id);

            if (! $whatsapp) {
                Log::info('[OrderService] WhatsApp skipped — not configured', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            // Mark as sent — actual wa.me link is triggered from frontend
            // confirmation page. Server-side we just flag it.
            $order->update(['whatsapp_sent' => true, 'last_notified_at' => now()]);

            Log::info('[OrderService] WhatsApp notification flagged', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

        } catch (Throwable $e) {
            // Never crash an order because of notification failure
            Log::warning('[OrderService] WhatsApp notify failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
}
