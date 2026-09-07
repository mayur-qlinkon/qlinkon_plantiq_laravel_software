<?php

namespace App\Services;

use App\Enums\SkuSearchContext;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductMedia;
use App\Models\ProductSku;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Unit;
use InvalidArgumentException;

use App\Services\Platform\ImageUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductService
{
    protected ImageUploadService $imageService;

    public function __construct(ImageUploadService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Search transactable SKUs for a document line-item picker.
     *
     * Shared by invoices, quotations, orders, challans and purchases. Results
     * are SKU-grained rather than product-grained because each document line
     * references one SKU — a variable product with five variants must offer
     * five distinct choices.
     *
     * The context decides the filtering and the response shape; see
     * SkuSearchContext. Nothing about those rules is taken from the request.
     *
     * @param  string  $term        Raw search input.
     * @param  int     $companyId   Tenant scope.
     * @param  int|null $warehouseId Required unless the context is Procurement.
     * @param  int     $limit       Maximum rows returned.
     * @return array<int, array<string, mixed>>
     *
     * @throws InvalidArgumentException When the warehouse is missing or not
     *         accessible from the user's active stores.
     */
    public function searchSellableSkus(
        string $term,
        int $companyId,
        SkuSearchContext $context,
        ?int $warehouseId = null,
        int $limit = 20
    ): array {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

       // Validation returns the id only when the context mandates one. For
        // Procurement the warehouse is optional, so an authorised id is kept
        // for reporting stock levels while an unusable one is simply dropped.
        $warehouseId = $context->requiresWarehouse()
            ? $this->resolveSearchWarehouse($context, $warehouseId)
            : $this->resolveOptionalWarehouse($warehouseId);

        $skus = $this->buildSkuSearchQuery($term, $companyId, $context, $warehouseId)
            ->take($limit)
            ->get();

        if ($skus->isEmpty()) {
            return [];
        }

        $stocks = $this->fetchStockLevels($skus, $context, $warehouseId);

        return $skus->map(
            fn (ProductSku $sku) => $this->mapSkuForSearch($sku, $context, $stocks)
        )->all();
    }

    /**
     * Validate the warehouse against the stores the current user may act on.
     *
     * The previous implementation passed the client-supplied warehouse id
     * straight into the stock query. Any authenticated user could therefore
     * read stock levels for a warehouse belonging to a store they have no
     * access to, simply by changing the id in the URL.
     */
    private function resolveSearchWarehouse(SkuSearchContext $context, ?int $warehouseId): ?int
    {
        if (! $context->requiresWarehouse()) {
            return null;
        }

        if (! $warehouseId) {
            throw new InvalidArgumentException('A warehouse must be selected before searching for items.');
        }

        $isAuthorised = Warehouse::where('id', $warehouseId)
            ->whereIn('store_id', active_store_ids())
            ->exists();

        if (! $isAuthorised) {
            throw new InvalidArgumentException('The selected warehouse is not available to you.');
        }

        return $warehouseId;
    }

   /**
     * Resolve a warehouse the caller may omit.
     *
     * Used by contexts where the warehouse only enriches the response rather
     * than constraining it. An id the user cannot access is discarded silently
     * instead of raising, because the search itself remains valid without it —
     * the user simply sees zero stock rather than another store's figures.
     */
    private function resolveOptionalWarehouse(?int $warehouseId): ?int
    {
        if (! $warehouseId) {
            return null;
        }

        $isAuthorised = Warehouse::where('id', $warehouseId)
            ->whereIn('store_id', active_store_ids())
            ->exists();

        return $isAuthorised ? $warehouseId : null;
    }

    /**
     * Build the SKU query for a search.
     */
    private function buildSkuSearchQuery(
        string $term,
        int $companyId,
        SkuSearchContext $context,
        ?int $warehouseId
    ) {
        // Eager loads cover every relation touched by mapSkuForSearch, so the
        // mapping stage issues no further queries regardless of result size.
        $query = ProductSku::with([
                'product',
                'unit',
                'product.productUnit',
                'product.saleUnit',
                'skuValues.attributeValue',
            ])
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($context->sellableOnly()) {
            // Catalog products exist for display only and must never appear in
            // a transactional document.
            $query->whereHas('product', fn ($q) => $q->where('product_type', 'sellable'));
        }

        $query->where(function ($q) use ($term) {
            $q->where('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhereHas('product', fn ($sq) => $sq->where('name', 'like', "%{$term}%"))
                // Lets a user find a variant by typing its attribute value,
                // e.g. "Red" or "Large", not just the parent product name.
                ->orWhereHas('skuValues.attributeValue', fn ($vq) => $vq->where('value', 'like', "%{$term}%"));
        });

        if ($context->inStockOnly() && $warehouseId) {
            $query->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->where('qty', '>', 0);
            });
        }

        return $query;
    }

    /**
     * Fetch current stock for the matched SKUs, keyed by SKU id.
     */
    private function fetchStockLevels($skus, SkuSearchContext $context, ?int $warehouseId)
    {
        if (! $context->includesStock() || ! $warehouseId) {
            return collect();
        }

        return ProductStock::whereIn('product_sku_id', $skus->pluck('id'))
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->keyBy('product_sku_id');
    }

    /**
     * Shape one SKU into the payload the line-item pickers expect.
     */
    private function mapSkuForSearch(ProductSku $sku, SkuSearchContext $context, $stocks): array
    {
        $productName = $sku->product?->name ?? 'Unknown Product';

        $variantValues = $sku->skuValues
            ->map(fn ($skuValue) => $skuValue->attributeValue?->value)
            ->filter()
            ->implode(' - ');

        /*
        | Units cascade: the SKU may carry its own, otherwise the product's
        | sale unit, otherwise its base unit. Resolving here keeps every
        | document consistent with what POS displays.
        */
        $unitId = $sku->unit_id
            ?? $sku->product?->sale_unit_id
            ?? $sku->product?->product_unit_id;

        $unitName = $sku->unit?->name
            ?? $sku->product?->saleUnit?->name
            ?? $sku->product?->productUnit?->name
            ?? 'Unit';

        $payload = [
            'product_sku_id' => $sku->id,
            'product_id'     => $sku->product_id,
            'product_name'   => $productName,
            'variant_values' => $variantValues,
            'display_name'   => $variantValues ? "{$productName} – {$variantValues}" : $productName,
            // The SKU's own code overrides the product default — a variant can
            // be classified differently from its parent.
            'hsn_code'       => $sku->hsn_code ?: ($sku->product->hsn_code ?? null),
            'sku_code'       => $sku->sku,
            'barcode'        => $sku->display_barcode,
            'actual_barcode' => $sku->barcode,
            'price'          => (float) $sku->price,
            'unit_price'     => (float) $sku->price,
            'tax_percent'    => (float) ($sku->order_tax ?? 0),
            'order_tax'      => (float) ($sku->order_tax ?? 0),
            'tax_type'       => $sku->tax_type ?? 'exclusive',
            'unit_id'        => $unitId,
            'unit_name'      => $unitName,
            'stock'          => (float) ($stocks->get($sku->id)?->qty ?? 0),
        ];

        // Supplier cost is withheld from sales-facing screens.
        if ($context->includesCost()) {
            $payload['cost'] = (float) ($sku->cost ?? 0);
        }

        return $payload;
    }

    /**
     * Get paginated products with advanced filtering and eager loading.
     */
    public function getProductsList(array $filters = [], int $perPage = 50)
    {
        // Eager load relationships to prevent N+1 performance issues
        $storeWarehouseIds = Warehouse::where('store_id', optional(active_store())->id)->pluck('id');
        $query = Product::with(['category', 'media',
            'skus.stocks' => fn ($q) => $q->whereIn('warehouse_id', $storeWarehouseIds),
        ]);
        
        // Products are company-level — all staff see all company products
        // 1. Search Filter (Search by Product Name, SKU, or Barcode)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('skus', function ($skuQuery) use ($search) {
                        $skuQuery->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Category Filter — now uses pivot (supports multi-category)
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
            // $query->whereHas('categoryPivots', function ($q) use ($filters) {
            // });
        }

        // 3. Status Filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /**
     * Toggle the active status of a product.
     */
    public function toggleStatus(Product $product): bool
    {
        $product->is_active = ! $product->is_active;
        $saved = $product->save();

        // Mirror active state to all pivot rows
        // So storefront queries stay consistent
        if ($saved) {
            CategoryProduct::where('product_id', $product->id)
                ->update(['is_active' => $product->is_active]);
        }

        return $saved;
    }

    /**
     * Soft delete the product.
     */
    public function deleteProduct(Product $product): bool
    {
        // Because of SoftDeletes, this just sets deleted_at.
        // It keeps the history intact for past invoices!
        return $product->delete();
    }

    public function duplicateProduct(Product $product): Product
    {
        $product->loadMissing(['skus.skuValues', 'media', 'categoryPivots']);

        return DB::transaction(function () use ($product) {

            // ── Clone core product ──
            $newProduct = $product->replicate([
                'created_at', 'updated_at', 'deleted_at',
            ]);
            
            $newProduct->name = 'Copy of '.$product->name;
            $newProduct->slug = null; // let model boot regenerate
            $newProduct->is_active = false;
            $newProduct->show_in_storefront = false;
            $newProduct->save();

            // ── Clone SKUs ──
            foreach ($product->skus as $sku) {
                $newSku = $sku->replicate(['created_at', 'updated_at', 'deleted_at']);                
                $newSku->product_id = $newProduct->id;

                // Make SKU unique and immune to double-click race conditions
                $baseSku = $sku->sku . '-COPY';
                $newSkuString = $baseSku;
                
                // Use a while loop to guarantee absolute uniqueness
                while (ProductSku::where('company_id', $product->company_id)->where('sku', $newSkuString)->exists()) {
                    $newSkuString = $baseSku . '-' . strtoupper(Str::random(4));
                }
                
                $newSku->sku = $newSkuString;
                $newSku->barcode = null;
                $newSku->total_sold = 0; // CRITICAL: Reset sales metrics for the new clone!
                $newSku->save();

                // ── Clone SKU attribute values ──
                foreach ($sku->skuValues as $skuValue) {
                    $newSku->skuValues()->create([
                        'attribute_id' => $skuValue->attribute_id,
                        'attribute_value_id' => $skuValue->attribute_value_id,
                    ]);
                }
                // Note: stock is NOT cloned — new product starts at zero stock
            }

            // ── Clone media records (same file paths, no re-upload) ──
            foreach ($product->media as $media) {
                $newProduct->media()->create([                    
                    'media_type' => $media->media_type,
                    'media_path' => $media->media_path,
                    'is_primary' => $media->is_primary,
                    'sort_order' => $media->sort_order,
                ]);
            }

            // ── Clone category pivots ──
            foreach ($product->categoryPivots as $pivot) {
                CategoryProduct::attachProduct(
                    categoryId: $pivot->category_id,
                    productId: $newProduct->id,
                    isActive: false, // always inactive on duplicate
                );
            }

            Log::info('[ProductService] Duplicated', [
                'original_id' => $product->id,
                'new_id' => $newProduct->id,
            ]);

            return $newProduct;
        });
    }

    public function createProduct(array $data, int $companyId,?int $storeId): Product
    {
        return DB::transaction(function () use ($data, $companyId, $storeId) {

            // 1. Create the Parent Product
            $primaryCategoryId = ! empty($data['categories'])
                ? (int) $data['categories'][0]
                : ($data['category_id'] ?? null);

            $isCatalog = ($data['product_type'] ?? 'sellable') === 'catalog';

            // For catalog products, units come from the form (required by DB NOT NULL).
            // Fall back to the first available unit only when not submitted.
            $fallbackUnitId = $isCatalog && empty($data['product_unit_id'])
                ? Unit::value('id')
                : null;

            $product = Product::create([                
                'category_id' => $primaryCategoryId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'product_unit_id' => $data['product_unit_id'] ?? $fallbackUnitId,
                'sale_unit_id' => $data['sale_unit_id'] ?? null,
                'purchase_unit_id' => $data['purchase_unit_id'] ?? null,
                'quantity_limitation' => ! empty($data['quantity_limitation']) ? $data['quantity_limitation'] : null,
                'name' => $data['name'],
                'type' => $isCatalog ? 'single' : $data['type'],
                'product_type' => $isCatalog ? 'catalog' : 'sellable',
                'barcode_symbology' => $data['barcode_symbology'] ?? 'CODE128',
                'hsn_code' => $data['hsn_code'] ?? null,
                'description' => $data['description'] ?? null,
                'product_guide' => $data['product_guide'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'show_in_storefront' => $data['show_in_storefront'] ?? true,
                'show_as_addon' => $data['show_as_addon'] ?? false,
            ]);

            $skuMap = [];

            // ── Catalog products: no SKUs — skip directly to media ──
            if ($isCatalog) {
                // Jump to media & category sync (step 4+)
            }

            // 2. Handle Single Product Setup
            elseif ($data['type'] === 'single') {
                $sku = $product->skus()->create([                    
                    'sku' => $data['single_sku'],
                    'barcode' => $data['single_barcode'] ?? null,
                    'price' => $data['single_price'],
                    'cost' => $data['single_cost'],
                    'mrp' => $data['single_mrp'] ?? 0,
                    'stock_alert' => $data['single_stock_alert'] ?? 0,
                    'order_tax' => $data['single_order_tax'] ?? 0,
                    'tax_type' => $data['single_tax_type'] ?? 'exclusive',
                    'hsn_code' => ($data['single_hsn_code'] ?? '') !== '' ? $data['single_hsn_code'] : null,
                ]);

                if (! empty($data['single_stock'])) {
                    $this->processInitialStock($sku, $data['single_stock']);
                }
            }

            // 3. Handle Variable Product Setup
            elseif (($data['type'] ?? '') === 'variable') {
                // 🌟 Added $varIndex to map the frontend array position to the DB ID
                foreach ($data['variations'] as $varIndex => $varData) {

                    $selectedAttrValIds = array_filter(array_values($varData['attrs'] ?? []));

                    $skuString = ! empty($varData['sku'])
                        ? $varData['sku']
                        : $this->generateVariableSku($data['name'], $selectedAttrValIds, $companyId);

                    $sku = $product->skus()->create([                        
                        'sku' => $skuString,
                        'barcode' => $varData['barcode'] ?? null,
                        'price' => $varData['price'],
                        'cost' => $varData['cost'],
                        'mrp' => $varData['mrp'] ?? 0,
                        'stock_alert' => $varData['stock_alert'] ?? 0,
                        'order_tax' => $varData['order_tax'] ?? 0,
                        'tax_type' => $varData['tax_type'] ?? 'exclusive',
                        'hsn_code' => ($varData['hsn_code'] ?? '') !== '' ? $varData['hsn_code'] : null,
                    ]);

                    // 🌟 Map the frontend array index to the actual Database ID
                    $skuMap[$varIndex] = $sku->id;

                    if (isset($varData['attrs'])) {
                        foreach ($varData['attrs'] as $attrId => $attrValId) {
                            if (! empty($attrValId)) {
                                $sku->skuValues()->create([
                                    'attribute_id' => $attrId,
                                    'attribute_value_id' => $attrValId,
                                ]);
                            }
                        }
                    }

                    if (! empty($varData['stock'])) {
                        $this->processInitialStock($sku, $varData['stock']);
                    }
                }
            }

            // 4. 🌟 MOVED & UPDATED: Handle Dynamic Media (Images & YouTube)
            // It now runs AFTER SKUs exist so we can link them properly.
            if (! empty($data['media'])) {
                foreach ($data['media'] as $index => $mediaItem) {

                    // 🌟 Resolve the actual SKU ID from our map
                    $productSkuId = (isset($mediaItem['sku_index']) && $mediaItem['sku_index'] !== '')
                        ? ($skuMap[$mediaItem['sku_index']] ?? null)
                        : null;

                    if ($mediaItem['type'] === 'image' && isset($mediaItem['file'])) {
                        $path = $this->imageService->upload($mediaItem['file'], 'products', [
                            'width' => 800,
                            'height' => 800,
                            'crop' => true,
                            'format' => 'webp',
                            'quality' => 80,
                        ]);

                        $product->media()->create([                            
                            'product_sku_id' => $productSkuId, // 🌟 Assign SKU
                            'media_type' => 'image',
                            'media_path' => $path,
                            'is_primary' => (isset($data['primary_media_index']) && (int) $data['primary_media_index'] === $index),
                            'sort_order' => $index,
                        ]);

                    } elseif ($mediaItem['type'] === 'youtube' && ! empty($mediaItem['url'])) {
                        $product->media()->create([                            
                            'product_sku_id' => $productSkuId, // 🌟 Assign SKU
                            'media_type' => 'youtube',
                            'media_path' => $mediaItem['url'],
                            'is_primary' => false,
                            'sort_order' => $index,
                        ]);
                    }
                }
            }

            // 5. ── Sync categories to pivot table ──
            $categoryIds = $data['categories']
                ?? ($data['category_id'] ? [$data['category_id']] : []);

            if (! empty($categoryIds)) {
                $this->syncCategories($product, $categoryIds);
            }

            return $product;
        });
    }

    /**
     * Update an existing product, syncing its SKUs, Attributes, and Media.
     */
    public function updateProduct(Product $product, array $data, int $companyId, ?int $storeId): Product
    {
        return DB::transaction(function () use ($product, $data, $companyId, $storeId) {

            // 1. Update Core Product Details
            $primaryCategoryId = ! empty($data['categories'])
                ? (int) $data['categories'][0]
                : ($data['category_id'] ?? $product->category_id);

            $isCatalog = ($data['product_type'] ?? $product->product_type ?? 'sellable') === 'catalog';

            $product->update([                
                'category_id' => $primaryCategoryId,
                'supplier_id' => $data['supplier_id'] ?? null,
                
                // 🌟 Production-Ready Fix: Prioritize incoming request data directly, fallback to old DB values if empty
                'product_unit_id' => $data['product_unit_id'] ?? $product->product_unit_id,
                'sale_unit_id' => $data['sale_unit_id'] ?? $product->sale_unit_id,
                'purchase_unit_id' => $data['purchase_unit_id'] ?? $product->purchase_unit_id,
                
                'name' => $data['name'],
                'type' => $isCatalog ? 'single' : $data['type'],
                'product_type' => $isCatalog ? 'catalog' : 'sellable',
                'barcode_symbology' => $data['barcode_symbology'] ?? 'CODE128',
                'hsn_code' => $data['hsn_code'] ?? null,
                'quantity_limitation' => $data['quantity_limitation'] ?? null,
                'description' => $data['description'] ?? null,
                'product_guide' => $data['product_guide'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'show_in_storefront' => $data['show_in_storefront'] ?? $product->show_in_storefront,
                'show_as_addon' => $data['show_as_addon'] ?? $product->show_as_addon,
            ]);

            $skuMap = [];

            // ── Catalog: skip SKU sync entirely — preserve existing SKUs so
            //    switching back to sellable restores them without data loss. ──
            if ($isCatalog) {
                // intentionally do nothing — jump to media & category sync below
            }

            // 2. Handle Single Product Sync
            elseif ($data['type'] === 'single') {
                // If they changed from Variable to Single, delete old variations
                $product->skus()->where('sku', '!=', $data['single_sku'])->delete();

                $sku = $product->skus()->updateOrCreate(
                    ['product_id' => $product->id], // Find existing
                    [                        
                        'sku' => $data['single_sku'],
                        'barcode' => $data['single_barcode'] ?? null,
                        'price' => $data['single_price'],
                        'cost' => $data['single_cost'],
                        'mrp' => $data['single_mrp'] ?? 0,
                        'stock_alert' => $data['single_stock_alert'] ?? 0,
                        'order_tax' => $data['single_order_tax'] ?? 0, // 🌟 FIX: Added Missing Tax
                        'tax_type' => $data['single_tax_type'] ?? 'exclusive', // 🌟 FIX: Added Missing Tax Type
                        'hsn_code' => ($data['single_hsn_code'] ?? '') !== '' ? $data['single_hsn_code'] : null,
                    ]
                );

                if (!empty($data['single_stock'])) {
                    $this->processInitialStock($sku, $data['single_stock']);
                }
            }

            // 3. Handle Variable Product Sync
            elseif (($data['type'] ?? '') === 'variable') {

                $keptSkuIds = [];

                // 🌟 Added $varIndex to map the frontend array position to the DB ID
                foreach ($data['variations'] as $varIndex => $varData) {

                    $selectedAttrValIds = array_filter(array_values($varData['attrs'] ?? []));

                    $skuString = ! empty($varData['sku'])
                        ? $varData['sku']
                        : $this->generateVariableSku($data['name'], $selectedAttrValIds, $companyId);

                    // Update existing variation, or create a new one
                    // Prevent Alpine's Date.now() from crashing the MySQL INT column
                    $dbId = (isset($varData['id']) && is_numeric($varData['id']) && $varData['id'] < 2000000000)
                        ? $varData['id']
                        : null;

                    // Update existing variation, or create a new one
                    $sku = $product->skus()->updateOrCreate(
                        [
                            'id' => $dbId,
                            'product_id' => $product->id,
                        ],
                        [                            
                            'sku' => $skuString,
                            'barcode' => $varData['barcode'] ?? null,
                            'price' => $varData['price'],
                            'cost' => $varData['cost'],
                            'mrp' => $varData['mrp'] ?? 0,
                            'stock_alert' => $varData['stock_alert'] ?? 0,
                            'order_tax' => $varData['order_tax'] ?? 0, // 🌟 FIX: Added Missing Tax
                            'tax_type' => $varData['tax_type'] ?? 'exclusive', // 🌟 FIX: Added Missing Tax Type
                            'hsn_code' => ($varData['hsn_code'] ?? '') !== '' ? $varData['hsn_code'] : null,
                        ]
                    );

                    $keptSkuIds[] = $sku->id;
                    // Map BOTH the array index and the Alpine temp ID to the real database ID
                    $skuMap[$varIndex] = $sku->id;
                    if (isset($varData['id'])) {
                        $skuMap[$varData['id']] = $sku->id;
                    }

                    // ONLY add stock if this is a brand new variation being added during the edit
                    if ($sku->wasRecentlyCreated && ! empty($varData['stock'])) {
                        $this->processInitialStock($sku, $varData['stock']);
                    }

                    // Sync Attributes (Delete old ones, recreate new ones)
                    $sku->skuValues()->delete();
                    foreach ($varData['attrs'] ?? [] as $attrId => $attrValId) {
                        if (! empty($attrValId)) {
                            $sku->skuValues()->create([
                                'attribute_id' => $attrId,
                                'attribute_value_id' => $attrValId,
                            ]);
                        }
                    }
                }

                // Delete any SKUs that were removed from the frontend UI
                $product->skus()->whereNotIn('id', $keptSkuIds)->delete();
            }

            // 4. Handle Media Sync (Update, Delete, Reorder)
            $keptMediaIds = [];

            if (! empty($data['media'])) {
                foreach ($data['media'] as $index => $mediaItem) {
                    $isPrimary = (isset($data['primary_media_index']) && (int) $data['primary_media_index'] === $index);

                    // 🌟 Resolve the actual SKU ID from our map
                    $productSkuId = (isset($mediaItem['sku_index']) && $mediaItem['sku_index'] !== '')
                        ? ($skuMap[$mediaItem['sku_index']] ?? null)
                        : null;

                    if (! empty($mediaItem['id'])) {
                        // A. EXISTING MEDIA: Update sort order, primary status, and SKU assignment
                        $existingMedia = $product->media()->find($mediaItem['id']);
                        if ($existingMedia) {
                            $existingMedia->update([                                
                                'product_sku_id' => $productSkuId, // 🌟 Assign/Update SKU mapping
                                'sort_order' => $index,
                                'is_primary' => $isPrimary,
                            ]);
                            $keptMediaIds[] = $existingMedia->id;
                        }
                    } else {
                        // B. NEW MEDIA
                        if ($mediaItem['type'] === 'image' && isset($mediaItem['file'])) {
                            // Upload new image with WebP Compression & Resizing
                            $path = $this->imageService->upload($mediaItem['file'], 'products', [
                                'width' => 800,
                                'height' => 800,
                                'crop' => true,
                                'format' => 'webp',
                                'quality' => 80,
                            ]);
                            $newMedia = $product->media()->create([                                
                                'product_sku_id' => $productSkuId, // 🌟 Assign SKU
                                'media_type' => 'image',
                                'media_path' => $path,
                                'is_primary' => $isPrimary,
                                'sort_order' => $index,
                            ]);
                            $keptMediaIds[] = $newMedia->id;

                        } elseif ($mediaItem['type'] === 'youtube' && ! empty($mediaItem['url'])) {
                            // Save new YouTube link
                            $newMedia = $product->media()->create([                                
                                'product_sku_id' => $productSkuId, // 🌟 Assign SKU
                                'media_type' => 'youtube',
                                'media_path' => $mediaItem['url'],
                                'is_primary' => false,
                                'sort_order' => $index,
                            ]);
                            $keptMediaIds[] = $newMedia->id;
                        }
                    }
                }
            }

            // C. CLEANUP: Delete any media that was removed from the UI
            $mediaToDelete = $product->media()->whereNotIn('id', $keptMediaIds)->get();
            foreach ($mediaToDelete as $oldMedia) {
                // Delete the physical file from the server if it's an image
                if ($oldMedia->media_type === 'image') {
                    $isShared = ProductMedia::where('media_path', $oldMedia->media_path)
                        ->where('id', '!=', $oldMedia->id)
                        ->exists();

                    if (! $isShared && method_exists($this->imageService, 'delete')) {
                        $this->imageService->delete($oldMedia->media_path);
                    }
                }
                // Delete the database record
                $oldMedia->delete();
            }

            // ── Sync categories to pivot table ──
            $categoryIds = $data['categories']
                ?? ($data['category_id'] ? [$data['category_id']] : []);

            if (! empty($categoryIds)) {
                $this->syncCategories($product, $categoryIds);
            }

            return $product;
        });
    }

    /**
     * 🌟 NEW REUSABLE FUNCTION: Generate a smart SKU based on Product Name and Attributes
     */
    public function generateVariableSku(string $productName, array $attributeValueIds, int $companyId): string
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Base SKU from Product Name
        |--------------------------------------------------------------------------
        */
        $baseSku = Str::upper(Str::slug($productName));
        // Example: "Tshirt" -> "TSHIRT"
        /*
        |--------------------------------------------------------------------------
        | 2. Fetch attribute values with their attribute names
        |--------------------------------------------------------------------------
        */
        $attrValues = AttributeValue::with('attribute')
            ->whereIn('id', $attributeValueIds)
            ->get();
        /*
        |--------------------------------------------------------------------------
        | 3. Sort attributes so SIZE always comes last
        |--------------------------------------------------------------------------
        */

        $sorted = $attrValues->sortBy(function ($item) {
            return strtolower($item->attribute->name) === 'size' ? 999 : 1;
        });
        /*
        |--------------------------------------------------------------------------
        | 4. Build SKU segments
        |--------------------------------------------------------------------------
        */
        $segments = [];
        foreach ($sorted as $attr) {
            $value = Str::upper(Str::slug($attr->value));
            // If attribute is size → shorten to 1 character
            if (strtolower($attr->attribute->name) === 'size') {
                // Example: LARGE -> L
                $value = substr($value, 0, 1);
            }
            $segments[] = $value;
        }
        /*
        |--------------------------------------------------------------------------
        | 5. Combine all parts
        |--------------------------------------------------------------------------
        */
        $baseSku = $baseSku.'-'.implode('-', $segments);
        /*
        |--------------------------------------------------------------------------
        | 6. Ensure uniqueness inside company
        |--------------------------------------------------------------------------
        */
        $finalSku = $baseSku;
        if (ProductSku::where('company_id', $companyId)
            ->where('sku', $finalSku)
            ->exists()) {
            $finalSku .= '-'.strtoupper(Str::random(3));
        }

        return $finalSku;
    }

    /**
     * Private helper to cleanly manage the Ledger and Stock creation
     */
    private function processInitialStock($sku, array $stockData): void
    {        
        foreach ($stockData as $stock) {
            $qty = (int) $stock['qty'];

            if ($qty > 0) {
                // Safely add or increment physical stock
                $existingStock = $sku->stocks()->where('warehouse_id', $stock['warehouse_id'])->first();
                
                if ($existingStock) {
                    $existingStock->increment('qty', $qty);
                    $balanceAfter = $existingStock->fresh()->qty;
                } else {
                    // product_stocks has no store_id column — stock is keyed by
                    // warehouse. The value passed here previously came from
                    // $sku->store_id, an attribute ProductSku does not have.
                    $newStock = $sku->stocks()->create([
                        'warehouse_id' => $stock['warehouse_id'],
                        'qty' => $qty,
                    ]);
                    $balanceAfter = $newStock->qty;
                }

                // store_id is filled from the warehouse by the model.
                StockMovement::create([
                    'product_sku_id' => $sku->id,
                    'unit_id' => $sku->product->product_unit_id,
                    'warehouse_id' => $stock['warehouse_id'],
                    'quantity' => $qty,
                    'direction' => 'in',
                    'unit_cost' => $sku->cost,
                    'movement_type' => 'opening_stock',
                    'reference_type' => Product::class,
                    'reference_id' => $sku->product_id,
                    'balance_after' => $balanceAfter,
                    'user_id' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Sync product categories to pivot table.
     * Preserves existing sort_order and is_featured for unchanged categories.
     * Removes from old, adds to new, ignores unchanged.
     */
    private function syncCategories(Product $product, array $categoryIds): void
    {
        if (empty($categoryIds)) {
            return;
        }

        // Clean input
        $categoryIds = array_values(array_unique(
            array_filter(array_map('intval', $categoryIds))
        ));

        // Validate — only keep IDs that exist in this company's categories
        $validCategoryIds = Category::whereIn('id', $categoryIds)
            ->where('company_id', $product->company_id)
            ->pluck('id')
            ->toArray();

        if (empty($validCategoryIds)) {
            Log::warning('[ProductService] No valid category IDs', [
                'product_id' => $product->id,
                'sent_ids' => $categoryIds,
                'company_id' => $product->company_id,
            ]);

            return;
        }

        // Update primary category for backward compat
        $product->update(['category_id' => $validCategoryIds[0]]);

        // ✅ Correct direction: product → many categories
        // Get current category IDs this product belongs to
        $existingCategoryIds = CategoryProduct::where('product_id', $product->id)
            ->pluck('category_id')
            ->toArray();

        $toAdd = array_diff($validCategoryIds, $existingCategoryIds);
        $toRemove = array_diff($existingCategoryIds, $validCategoryIds);

        // Remove from categories no longer selected
        if (! empty($toRemove)) {
            CategoryProduct::where('product_id', $product->id)
                ->whereIn('category_id', $toRemove)
                ->delete();
        }

        // Add to new categories
        foreach ($toAdd as $categoryId) {
            CategoryProduct::attachProduct(
                categoryId: $categoryId,
                productId: $product->id,
                isActive: $product->is_active,
            );
        }

        Log::info('[ProductService] Categories synced', [
            'product_id' => $product->id,
            'added' => $toAdd,
            'removed' => $toRemove,
            'final' => $validCategoryIds,
        ]);
    }
}
