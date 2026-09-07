<?php

namespace App\Services\Admin;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSku;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PosCheckoutException;
use Illuminate\Database\QueryException;

class PosService
{
    protected InventoryService $inventoryService;
    protected InvoiceService $invoiceService;
    protected PaymentService $paymentService;

    public function __construct(
        InventoryService $inventoryService,
        InvoiceService $invoiceService,
        PaymentService $paymentService
    ) {
        $this->inventoryService = $inventoryService;
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
    }

    /**
     * Get paginated products for the POS Grid with strict eager loading.
     * Hostinger Optimization: N+1 eradicated by eager loading the specific warehouse stock.
     */
    public function getGridProducts(array $filters, int $companyId): array
    {
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 50)));
        $search = trim($filters['search'] ?? '');
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $warehouseId = (int) ($filters['warehouse_id'] ?? 1);

        $query = ProductSku::with([
            'product.category',
            'skuValues.attributeValue',
            'unit',
            'product.saleUnit',
            'product.productUnit',
            // Load only primary image
            'product.media' => fn ($q) => $q->where('is_primary', true)->where('media_type', 'image'),
            // CRITICAL OPTIMIZATION: Preload exact warehouse stock to prevent N+1 loop
            'stocks' => fn ($q) => $q->where('warehouse_id', $warehouseId)
        ])
            ->where('product_skus.company_id', $companyId)
            ->where('product_skus.is_active', true)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)->where('product_type', 'sellable');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($categoryId > 0) {
            $query->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
        }

        $paginator = $query
            ->join('products as _pos_p', '_pos_p.id', '=', 'product_skus.product_id')
            ->select('product_skus.*')
            ->orderBy('_pos_p.name', 'asc')
            ->orderBy('product_skus.sku', 'asc')
            ->paginate($perPage);

        $formattedData = $paginator->getCollection()->map(fn ($sku) => $this->formatSkuForCart($sku, $warehouseId));

        return [
            'data' => $formattedData,
            'meta' => [
                'total_pages' => $paginator->lastPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ];
    }

    /**
     * Lightning fast barcode or fuzzy search.
     */
    public function scanItem(string $term, int $warehouseId, int $companyId): array
    {
        $relations = [
            'product.category', 
            'unit', 
            'product.saleUnit', 
            'product.productUnit',
            'stocks' => fn ($q) => $q->where('warehouse_id', $warehouseId)
        ];

        // 1. Exact Match (Lightning)
        $exactSku = ProductSku::with($relations)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('product_type', 'sellable'))
            ->where(fn ($q) => $q->where('barcode', $term)->orWhere('sku', $term))
            ->first();

        if ($exactSku) {
            return [
                'status' => 'exact',
                'data' => $this->formatSkuForCart($exactSku, $warehouseId),
            ];
        }

        // 2. Fuzzy Match (Fallback)
        $fuzzySkus = ProductSku::with($relations)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('product', function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->where('is_active', true)
                    ->where('product_type', 'sellable');
            })
            ->limit(15)
            ->get();

        if ($fuzzySkus->isEmpty()) {
            return ['status' => 'empty', 'message' => 'No product found.'];
        }

        return [
            'status' => 'fuzzy',
            'data' => $fuzzySkus->map(fn ($sku) => $this->formatSkuForCart($sku, $warehouseId)),
        ];
    }

    /**
     * Process the entire checkout flow.
     *
     * Financial values are re-derived from the SKU master; the invoice and its
     * payment commit together or not at all.
     */
    public function processCheckout(array $payload, int $companyId, int $storeId): array
    {
        // 1. Resolve Customer & Supply State
        $client = !empty($payload['customer_id']) ? Client::with('state')->find($payload['customer_id']) : null;
        $store = Store::with('state')->find($storeId);
        $storeState = $store?->state?->name ?? get_setting('company_state', '');
        $supplyState = $client?->state?->name ?? $storeState;

        // 2. Re-price every line from the SKU master.
        //
        // The browser sends unit_price / tax_percent / tax_type, but they are
        // advisory only — a tampered or replayed request could set any of them.
        // The POS UI has no price-edit field, so the master is the only
        // legitimate source. (InvoiceService deliberately still honours the
        // caller's price: manual invoicing has negotiated rates. That freedom
        // is exactly what must not exist here.)
        //
        // Tenantable scopes this query, so a foreign SKU simply does not come
        // back — a clean 422 instead of a 500 from findOrFail deeper down.
        $skus = ProductSku::with('product')
            ->whereIn('id', collect($payload['items'])->pluck('product_sku_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $mismatched = [];
        $serviceItems = [];

        foreach ($payload['items'] as $item) {
            $sku = $skus->get((int) $item['product_sku_id']);

            if (! $sku) {
                throw PosCheckoutException::unavailableProduct();
            }

            $masterPrice = (float) $sku->price;
            $masterTaxPercent = (float) ($sku->order_tax ?? 0);
            $masterTaxType = $sku->tax_type ?? 'exclusive';

            // Compared at paisa resolution. A stale cart must not silently
            // change what the customer was already quoted, so this is a hard
            // stop rather than a quiet correction.
            $priceDrifted = abs($masterPrice - (float) $item['unit_price']) > 0.009;
            $taxDrifted = abs($masterTaxPercent - (float) ($item['tax_percent'] ?? 0)) > 0.009;
            $taxTypeDrifted = $masterTaxType !== ($item['tax_type'] ?? 'exclusive');

            if ($priceDrifted || $taxDrifted || $taxTypeDrifted) {
                $mismatched[] = $sku->product->name ?? $sku->sku;

                continue;
            }

            $serviceItems[] = [
                'product_sku_id' => $sku->id,
                'unit_id' => $item['unit_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => $masterPrice,
                'tax_percent' => $masterTaxPercent,
                'tax_type' => $masterTaxType,
                'discount_type' => 'fixed',
                'discount_value' => 0,
            ];
        }

        if ($mismatched !== []) {
            throw PosCheckoutException::priceChanged(array_unique($mismatched));
        }

        // 3. Prepare Invoice DTO
        $invoiceData = [
            'source' => 'pos',
            'store_id' => $storeId,
            'warehouse_id' => $payload['warehouse_id'],
            'customer_id' => $client?->id,
            'customer_name' => $client ? null : ($payload['customer_name'] ?? 'Walk-in Customer'),
            'invoice_date' => now()->toDateString(),
            'supply_state' => $supplyState,
            'gst_treatment' => $this->mapGstTreatment($client?->registration_type),
            'status' => 'confirmed',
            'discount_type' => $payload['discount_type'] ?? 'fixed',
            'discount_value' => (float) ($payload['discount_value'] ?? 0),
            // Tax comes from the line items alone. Nothing the client sends can
            // add to it, so a request that posts an order-level tax directly
            // still cannot collect money the invoice does not show.
            'shipping_charge' => 0,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'items' => $serviceItems,
        ];

        $idempotencyKey = $payload['idempotency_key'] ?? null;

        // 4 + 5. Invoice and payment in ONE transaction.
        //
        // createInvoice() and recordPayment() each open their own transaction;
        // without this wrapper a failure in the payment step left a committed,
        // unpaid invoice with stock already deducted while the cashier saw an
        // error. Nested transactions become savepoints, so the inner ones are
        // unaffected.
        try {
            $invoice = DB::transaction(function () use ($invoiceData, $companyId, $payload) {
                $invoice = $this->invoiceService->createInvoice($invoiceData, $companyId);

                $amountReceived = (float) ($payload['amount_received'] ?? 0);

                if ($amountReceived > 0 && ! empty($payload['payment_method_id'])) {
                    $this->paymentService->recordPayment($invoice, [
                        'amount' => $amountReceived,
                        'payment_method_id' => $payload['payment_method_id'],
                        'payment_date' => now(),
                        'status' => 'completed',
                        'notes' => 'POS Checkout',
                    ]);
                }

                return $invoice;
            });
        } catch (QueryException $e) {
            // 23000 is an integrity constraint violation. With an idempotency
            // key present and an invoice already carrying it, this is the second
            // half of a double-submit, not a failure — hand back the invoice the
            // first request created so the cashier sees one sale, one receipt.
            //
            // Looking the row up (rather than parsing the index name out of the
            // driver message) also confirms it was this index that fired.
            if ($idempotencyKey !== null && (string) $e->getCode() === '23000') {
                $existing = Invoice::where('company_id', $companyId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return [
                        'invoice_id' => $existing->id,
                        'share_url' => route('pos.receipt.public', [$existing->id, $this->receiptToken($existing)]),
                    ];
                }
            }

            throw $e;
        }

        return [
            'invoice_id' => $invoice->id,
            'share_url' => route('pos.receipt.public', [$invoice->id, $this->receiptToken($invoice)])
        ];
    }

    /**
     * Create a product instantly from the POS modal.
     */
    public function createQuickProduct(array $data, int $companyId, mixed $imageFile): array
    {
        return DB::transaction(function () use ($data, $companyId, $imageFile) {
            $product = Product::create([
                'company_id' => $companyId,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'type' => 'single',
                'product_unit_id' => $data['unit_id'],
                'sale_unit_id' => $data['unit_id'],
                'purchase_unit_id' => $data['unit_id'],
                'is_active' => true,
            ]);

            $skuCode = !empty($data['sku']) ? $data['sku'] : strtoupper(Str::random(8));

            $sku = ProductSku::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'unit_id' => $data['unit_id'],
                'sku' => $skuCode,
                'barcode' => $data['barcode'] ?? null,
                'cost' => $data['cost'],
                'price' => $data['price'],
                'order_tax' => $data['tax_percent'] ?? 0,
                'tax_type' => $data['tax_type'] ?? 'exclusive',
                'is_active' => true,
            ]);

            if ($imageFile) {
                $path = $imageFile->store('products', 'public');
                ProductMedia::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'product_sku_id' => $sku->id,
                    'media_type' => 'image',
                    'media_path' => $path,
                    'is_primary' => true,
                ]);
            }

            $openingStockQty = (float) ($data['opening_stock'] ?? 0);
            if ($openingStockQty > 0) {
                $this->inventoryService->addStock(
                    sku: $sku,
                    warehouseId: $data['warehouse_id'],
                    qty: $openingStockQty,
                    movementType: 'adjustment'
                );
            }

            $sku->load([
                'product.category', 'unit', 'product.saleUnit', 'product.productUnit', 'product.media',
                'stocks' => fn ($q) => $q->where('warehouse_id', $data['warehouse_id'])
            ]);

            return $this->formatSkuForCart($sku, (int) $data['warehouse_id']);
        });
    }

    /**
     * Formats SKU specifically for Alpine.js Cart.
     * Uses preloaded relationships to strictly avoid N+1 queries.
     */
    private function formatSkuForCart(ProductSku $sku, int $warehouseId): array
    {
        $resolvedUnitId = $sku->unit_id ?? $sku->product?->sale_unit_id ?? $sku->product?->product_unit_id;
        $resolvedUnitName = $sku->unit?->name ?? $sku->product?->saleUnit?->name ?? $sku->product?->productUnit?->name ?? 'Unit';
        
        $variantName = $sku->relationLoaded('skuValues') 
            ? $sku->skuValues->map(fn ($val) => $val->attributeValue->value)->implode(' / ') 
            : '';
            
        $imagePath = $sku->product->media->first()?->media_path;

        // Extracts stock from preloaded relationship instead of a new DB query
        $stock = $sku->relationLoaded('stocks') ? (float) ($sku->stocks->first()?->qty ?? 0) : 0;

        return [
            'product_sku_id' => $sku->id,
            'product_id' => $sku->product_id,
            'product_name' => $sku->product->name,
            'display_name' => $variantName ? $sku->product->name . ' – ' . $variantName : $sku->product->name,
            'category_name' => $sku->product->category->name ?? 'Uncategorized',
            'sku_code' => $sku->sku,
            'barcode' => $sku->display_barcode,
            'actual_barcode' => $sku->barcode,
            'unit_id' => $resolvedUnitId,
            'unit_name' => $resolvedUnitName,
            'hsn_code' => $sku->product->hsn_code,
            'display_price' => (float) $sku->price,
            'unit_price' => (float) $sku->price,
            'price' => (float) $sku->price,
            'tax_percent' => (float) ($sku->order_tax ?? 0),
            'order_tax' => (float) ($sku->order_tax ?? 0),
            'tax_type' => $sku->tax_type,
            'variant_name' => $variantName,
            'image_url' => $imagePath ? asset('storage/'.$imagePath) : '',
            'stock' => $stock,
        ];
    }

    /**
     * The single source of truth for what a receipt contains.
     *
     * Both the web POS (browser print / AndroidPrinter bridge) and the
     * Flutter app read this, so a field added here reaches every printer
     * at once and the two can never drift apart.
     *
     * Relations are loaded defensively rather than assumed — callers reach
     * this from a fresh checkout, from history, and from a direct lookup.
     */
    public function receiptPayload(Invoice $invoice): array
    {
        $invoice->loadMissing(['items', 'customer', 'store', 'creator', 'company', 'payments.paymentMethod']);

        $payment = $invoice->payments->first();

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'date' => $invoice->created_at->format('d M Y, h:i A'),
            'cashier' => $invoice->creator->name ?? 'Admin',
            'customer' => [
                'name' => $invoice->customer_name ?: ($invoice->customer->name ?? 'Walk-in'),
                'phone' => $invoice->customer->phone ?? null,
                'gstin' => $invoice->customer->gst_number ?? null,
            ],
            'shop' => [
                'company_name' => $invoice->company->name ?? null,
                'store_name' => $invoice->store->name ?? null,
                'address' => $invoice->store->address ?? null,
                'phone' => $invoice->store->phone ?? null,
                'gstin' => $invoice->store->gst_number ?? null,
                'upi_id' => $invoice->store->upi_id ?? null,
            ],
            'items' => $invoice->items->map(fn ($item) => [
                'name' => $item->product_name,
                'qty' => (float) $item->quantity,
                'hsn_code' => $item->hsn_code,
                'unit_price' => (float) $item->unit_price,
                'taxable_value' => (float) $item->taxable_value,
                'tax_percent' => (float) $item->tax_percent,
                'tax_type' => $item->tax_type,
                'tax_amount' => (float) $item->tax_amount,
                'total' => (float) $item->total_amount,
            ])->values(),
            'totals' => [
                'taxable_subtotal' => (float) $invoice->items->sum('taxable_value'),
                'discount_amount' => (float) $invoice->discount_amount,
                'cgst_amount' => (float) $invoice->cgst_amount,
                'sgst_amount' => (float) $invoice->sgst_amount,
                'igst_amount' => (float) $invoice->igst_amount,
                'round_off' => (float) $invoice->round_off,
                'grand_total' => (float) $invoice->grand_total,
            ],
            'payment' => $payment ? [
                'method' => $payment->paymentMethod->name ?? 'Cash',
                'amount_received' => (float) $payment->amount_received,
                'change_returned' => (float) $payment->change_returned,
            ] : null,
            'share_url' => route('pos.receipt.public', [$invoice->id, $this->receiptToken($invoice)]),
        ];
    }

    /**
     * Signature for the public (unauthenticated) receipt share link.
     *
     * This token is the ONLY authorization on pos.receipt.public — there is no
     * session and no tenant scope on that route, so the token has to be
     * unforgeable on its own.
     *
     * Keyed with app.key, not app.name: the application name is printed in page
     * titles, email footers and PDFs, so anyone could recompute every token and
     * walk the invoice table by id.
     *
     * Bound to the record's identity rather than the URL id, so a token stays
     * valid only for the exact invoice it was minted for.
     */
    public function receiptToken(Invoice $invoice): string
    {
        return hash_hmac('sha256', implode('|', [
            $invoice->id,
            $invoice->company_id,
            $invoice->invoice_number,
        ]), config('app.key'));
    }

    private function mapGstTreatment(?string $type): string
    {
        $map = [
            'registered' => 'registered',
            'unregistered' => 'unregistered',
            'composition' => 'composition',
            'overseas' => 'overseas',
            'sez' => 'sez',
        ];
        return $map[strtolower($type ?? '')] ?? 'unregistered';
    }
}