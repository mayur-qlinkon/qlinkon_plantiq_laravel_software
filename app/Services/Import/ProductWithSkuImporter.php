<?php

namespace App\Services\Import;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Import;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductSkuValue;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Combined Product + SKU Importer (single CSV).
 *
 * Each CSV row = one SKU. Product-level columns are repeated across rows
 * that share the same slug — the product is upserted once per unique slug;
 * each row then creates/updates its corresponding SKU.
 *
 * Required columns : name, slug, category_slug, price, cost
 * Product columns  : unit, product_type, description
 * Guide columns    : title1, value1, title2, value2 … up to title10, value10
 * SKU columns      : sku, mrp, barcode, stock_alert, warehouse_name, stock_qty
 * Attribute cols   : attribute_1_name, attribute_1_value … attribute_5_name, attribute_5_value
 */
class ProductWithSkuImporter
{
    private const MAX_ATTRIBUTES = 5;

    private const MAX_GUIDE_PAIRS   = 16;

    private const MAX_VARIANTS = 100;

    private const VALID_TYPES = ['sellable', 'catalog'];

    // ── In-memory lookups (populated per chunk) ────────────────────────────
    private array $categorySlugs         = [];
    private array $unitShortNames        = [];
    private array $productBySlug         = [];  // lower(slug) => ['id', 'unit_id', 'is_variable']
    private array $warehousesByName      = [];
    private array $existingSkuCodes      = [];  // lower(code) => sku_id
    private array $attributeIdByName     = [];
    private array $attributeValueIdByKey = [];
    private array $existingCombos        = [];  // productId => [comboKey => skuId]
    private array $variantCount          = [];  // productId => int
    private ?array $defaultWarehouse     = null;

    /**
     * Names auto-created while processing this chunk, reported back so the
     * user can spot a typo that quietly became a new record. Without this,
     * relaxing validation would trade a loud error for silent junk data.
     */
    private array $createdRefs = ['categories' => [], 'units' => []];

    // ── Duplicate-detection key ────────────────────────────────────────────

    public function extractUniqueKey(array $row): ?string
    {
        $slug = strtolower(trim($row['slug'] ?? ''));
        if ($slug === '') {
            $name = trim($row['name'] ?? '');
            $slug = $name !== '' ? strtolower(Str::slug($name)) : '';
        }
        if ($slug === '') {
            return null;
        }

        $pairs = $this->extractAttributePairs($row);
        if (empty($pairs)) {
            $sku = strtolower(trim($row['sku'] ?? ''));

            return $sku !== '' ? "sku:{$sku}" : "product:{$slug}";
        }

        $tokens = [];
        foreach ($pairs as $p) {
            $tokens[] = strtolower($p['name']).'='.strtolower($p['value']);
        }
        sort($tokens);

        return 'var:'.$slug.'|'.implode(',', $tokens);
    }

    // ── Main chunk processor ───────────────────────────────────────────────

    /**
     * @param  int  $remainingSlots  Max new products allowed; PHP_INT_MAX = unlimited.
     * @return array{success:int, failed:int, skipped:int, created:int, updated:int, limit_skipped:int}
     */
    public function processChunk(
        Import $import,
        array $rows,
        int $startRow,
        int $companyId,
        int $remainingSlots = PHP_INT_MAX
    ): array {
        $success      = 0;
        $failed       = 0;
        $skipped      = 0;
        $created      = 0;
        $updated      = 0;
        $limitSkipped = 0;

        $importMode = $import->import_mode ?? 'create_or_update';
        $isDryRun   = (bool) ($import->is_dry_run ?? false);

        $this->preloadLookups($companyId);

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['_row_number'] ?? ($startRow + $index));
            $row       = $this->sanitizeRowToUtf8($row);

            try {
                $errors = $this->validateRow($row);
                if (! empty($errors)) {
                    $this->logError($import, $rowNumber, $row, implode('; ', $errors));
                    $failed++;

                    continue;
                }

                $rowSkipped      = false;
                $rowLimitBlocked = false;
                $rowAction       = null;
                $rowError        = null;

                try {
                    DB::transaction(function () use (
                        $import, $row, $rowNumber, $companyId, $importMode, $isDryRun,
                        &$remainingSlots, &$rowSkipped, &$rowLimitBlocked, &$rowAction, &$rowError
                    ) {
                        // ── 1. Resolve / upsert product ────────────────────────
                        $name    = trim($row['name']);
                        $rawSlug = trim($row['slug'] ?? '');
                        $slug    = $rawSlug !== ''
                            ? Str::slug($rawSlug)
                            : Str::slug($name).'-'.Str::random(5);

                        $categoryId = $this->resolveCategoryId(trim($row['category_slug']), $companyId);

                        $unitId = null;
                        if (! empty($row['unit'])) {
                            $unitId = $this->resolveUnitId(trim($row['unit']), $companyId);
                        }

                        $productType = 'sellable';
                        if (! empty($row['product_type'])) {
                            $pt = strtolower(trim($row['product_type']));
                            if (in_array($pt, self::VALID_TYPES, true)) {
                                $productType = $pt;
                            }
                        }

                        $productGuide = $this->parseProductGuide($row);
                        $existing     = $this->productBySlug[strtolower($slug)] ?? null;

                        if ($existing) {
                            $productId = $existing['id'];
                            // Always update product fields unless we're in create_only mode
                            if ($importMode !== 'create_only') {
                                $updateData = [
                                    'name'             => $name,
                                    'category_id'      => $categoryId,
                                    'product_unit_id'  => $unitId,
                                    'sale_unit_id'     => $unitId,
                                    'purchase_unit_id' => $unitId,
                                    'product_type'     => $productType,
                                ];
                                if (! empty(trim($row['description'] ?? ''))) {
                                    $updateData['description'] = trim($row['description']);
                                }
                                if ($productGuide !== null) {
                                    $updateData['product_guide'] = $productGuide;
                                }
                                Product::withoutGlobalScopes()->where('id', $productId)->update($updateData);

                                // Sync category_products pivot: if category changed, swap it out.
                                if ($categoryId !== null) {
                                    $alreadyLinked = CategoryProduct::where('product_id', $productId)
                                        ->where('category_id', $categoryId)
                                        ->exists();

                                    if (! $alreadyLinked) {
                                        // Remove stale pivot rows for this product before adding new one.
                                        CategoryProduct::where('product_id', $productId)->delete();
                                        CategoryProduct::attachProduct(
                                            categoryId: $categoryId,
                                            productId:  $productId,
                                            isActive:   true,
                                        );
                                    }
                                }
                            }
                        } else {
                            if ($importMode === 'update_only') {
                                $rowSkipped = true;

                                return;
                            }
                            if ($remainingSlots <= 0) {
                                $rowLimitBlocked = true;

                                return;
                            }

                            $product                    = new Product;
                            $product->company_id        = $companyId;
                            $product->name              = $name;
                            $product->slug              = $slug;
                            $product->category_id       = $categoryId;
                            $product->product_unit_id   = $unitId;
                            $product->sale_unit_id      = $unitId;
                            $product->purchase_unit_id  = $unitId;
                            $product->type              = 'single';
                            $product->product_type      = $productType;
                            $product->description       = trim($row['description'] ?? '') ?: null;
                            $product->is_active         = true;
                            $product->show_in_storefront = true;
                            $product->product_guide     = $productGuide;
                            $product->save();

                            $productId = $product->id;
                            $this->productBySlug[strtolower($slug)] = [
                                'id'          => $productId,
                                'unit_id'     => $unitId,
                                'is_variable' => false,
                            ];
                            $remainingSlots--;

                            // Link to category_products pivot on new product creation.
                            if ($categoryId !== null) {
                                CategoryProduct::attachProduct(
                                    categoryId: $categoryId,
                                    productId:  $productId,
                                    isActive:   true,
                                );
                            }
                        }

                        // ── 2. Resolve / upsert SKU ────────────────────────────
                        $pairs             = $this->extractAttributePairs($row);
                        $attributeValueIds = [];
                        $valueLabels       = [];
                        foreach ($pairs as $pair) {
                            $attrId                        = $this->resolveAttributeId($companyId, $pair['name']);
                            $valueId                       = $this->resolveAttributeValueId($companyId, $attrId, $pair['value']);
                            $attributeValueIds[$attrId]    = $valueId;
                            $valueLabels[]                 = $pair['value'];
                        }

                        $sortedIds = array_values($attributeValueIds);
                        sort($sortedIds);
                        $comboKey      = empty($sortedIds) ? 'default' : implode('-', $sortedIds);
                        $existingSkuId = $this->existingCombos[$productId][$comboKey] ?? null;

                        // 100-variant hard cap (only for new variants)
                        if (! $existingSkuId) {
                            $count = $this->variantCount[$productId] ?? 0;
                            if ($count >= self::MAX_VARIANTS) {
                                $rowError = 'Maximum of '.self::MAX_VARIANTS." variants per product reached for '{$slug}'.";

                                return;
                            }
                        }

                        $price       = (float) $row['price'];
                        $cost        = (float) $row['cost'];
                        $mrp         = ! empty($row['mrp'])         ? (float) $row['mrp']       : null;
                        $barcode     = ! empty($row['barcode'])      ? trim($row['barcode'])      : null;
                        $stockAlert  = ! empty($row['stock_alert'])  ? (int) $row['stock_alert'] : 0;
                        $providedSku = ! empty($row['sku'])          ? trim($row['sku'])          : null;

                        if ($existingSkuId && $importMode !== 'create_only') {
                            // ── Update existing SKU ────────────────────────────
                            $sku = ProductSku::withoutGlobalScopes()->find($existingSkuId);
                            if (! $sku) {
                                $rowError = 'Existing variant disappeared mid-import. Please retry.';

                                return;
                            }
                            if ($providedSku && strtolower($providedSku) !== strtolower($sku->sku)) {
                                $codeKey = strtolower($providedSku);
                                $owner   = $this->existingSkuCodes[$codeKey] ?? null;
                                if ($owner !== null && $owner !== $sku->id) {
                                    $rowError = "SKU code '{$providedSku}' is already used by another variant.";

                                    return;
                                }
                                unset($this->existingSkuCodes[strtolower($sku->sku)]);
                                $sku->sku = $providedSku;
                                $this->existingSkuCodes[$codeKey] = $sku->id;
                            }
                            $sku->price       = $price;
                            $sku->cost        = $cost;
                            if ($mrp !== null) {
                                $sku->mrp = $mrp;
                            }
                            if ($barcode !== null) {
                                $sku->barcode = $barcode;
                            }
                            $sku->stock_alert = $stockAlert;
                            $sku->save();
                            $rowAction = 'updated';

                        } elseif (! $existingSkuId) {
                            if ($importMode === 'update_only') {
                                $rowSkipped = true;

                                return;
                            }

                            // ── Create new SKU ─────────────────────────────────
                            $skuCode = $providedSku ?: $this->generateSkuCode($slug, $valueLabels);
                            $skuCode = $this->ensureUniqueSkuCode($skuCode);

                            $sku               = new ProductSku;
                            $sku->company_id   = $companyId;
                            $sku->product_id   = $productId;
                            $sku->sku          = $skuCode;
                            $sku->price        = $price;
                            $sku->cost         = $cost;
                            $sku->mrp          = $mrp;
                            $sku->barcode      = $barcode;
                            $sku->stock_alert  = $stockAlert;
                            $sku->tax_type     = 'exclusive';
                            $sku->order_tax    = 0;
                            $sku->is_active    = true;
                            $sku->save();

                            foreach ($attributeValueIds as $attrId => $valueId) {
                                ProductSkuValue::create([
                                    'product_sku_id'     => $sku->id,
                                    'attribute_id'       => $attrId,
                                    'attribute_value_id' => $valueId,
                                ]);
                            }

                            // Optional warehouse stock
                            $warehouseName = trim($row['warehouse_name'] ?? '');
                            $stockQty      = (int) ($row['stock_qty'] ?? 0);
                            if ($warehouseName !== '' && $stockQty > 0) {
                                $warehouseKey = strtolower($warehouseName);

                                // A warehouse is a physical location, so a typo
                                // must not conjure one that then holds real
                                // stock. Fall back to the company default
                                // instead, and say so in the report.
                                $warehouse = $this->warehousesByName[$warehouseKey]
                                    ?? $this->defaultWarehouse($companyId);

                                if ($warehouse !== null) {
                                    $warehouseId = $warehouse['id'];
                                    if (! isset($this->warehousesByName[$warehouseKey])) {
                                        $this->logError($import, $rowNumber, $row, "Warning: Warehouse '{$warehouseName}' not found. Stock placed in the default warehouse.");
                                    }

                                    $stockRecord = $sku->stocks()->firstOrCreate(
                                        ['warehouse_id' => $warehouseId],
                                        ['company_id' => $companyId, 'qty' => 0]
                                    );
                                    $stockRecord->increment('qty', $stockQty);
                                    $unitIdForMovement = $this->productBySlug[strtolower($slug)]['unit_id'] ?? null;
                                    StockMovement::create([
                                        // Comes from the warehouse, not the session.
                                        'store_id'        => $warehouse['store_id'],
                                        'product_sku_id'  => $sku->id,
                                        'unit_id'         => $unitIdForMovement,
                                        'warehouse_id'    => $warehouseId,
                                        'quantity'        => $stockQty,
                                        'movement_type'   => 'adjustment',
                                        'reference_type'  => Import::class,
                                        'reference_id'    => $import->id,
                                        'balance_after'   => $stockRecord->fresh()->qty,
                                    ]);
                                } else {
                                    // No default warehouse configured at all —
                                    // the product and SKU still stand, only the
                                    // stock line is dropped.
                                    $this->logError($import, $rowNumber, $row, "Warning: No warehouse available. Product and SKU created without stock.");
                                }
                            }

                            $this->existingSkuCodes[strtolower($skuCode)]    = $sku->id;
                            $this->existingCombos[$productId][$comboKey]     = $sku->id;
                            $this->variantCount[$productId] = ($this->variantCount[$productId] ?? 0) + 1;

                            // Remove bare/default SKUs created when the product was first saved
                            $totalSkus = ProductSku::withoutGlobalScopes()->where('product_id', $productId)->count();
                            if ($totalSkus > 1) {
                                $bareSkuIds = ProductSku::withoutGlobalScopes()
                                    ->where('product_id', $productId)
                                    ->where('id', '!=', $sku->id)
                                    ->whereNotIn('id', fn ($q) => $q->select('product_sku_id')->from('product_sku_values'))
                                    ->pluck('id')->all();

                                if (! empty($bareSkuIds)) {
                                    ProductSku::withoutGlobalScopes()->whereIn('id', $bareSkuIds)->delete();
                                    foreach ($bareSkuIds as $deletedId) {
                                        $codeKey = array_search($deletedId, $this->existingSkuCodes, true);
                                        if ($codeKey !== false) {
                                            unset($this->existingSkuCodes[$codeKey]);
                                        }
                                    }
                                    $this->variantCount[$productId] = max(
                                        0,
                                        ($this->variantCount[$productId] ?? 0) - count($bareSkuIds)
                                    );
                                }
                            }

                            // Set product type (single vs variable) based on final SKU state
                            $finalSkuCount = ProductSku::withoutGlobalScopes()->where('product_id', $productId)->count();
                            $correctType   = ($finalSkuCount > 1 || ! empty($attributeValueIds)) ? 'variable' : 'single';
                            Product::withoutGlobalScopes()
                                ->where('id', $productId)
                                ->where('type', '!=', $correctType)
                                ->update(['type' => $correctType]);
                            if (isset($this->productBySlug[strtolower($slug)])) {
                                $this->productBySlug[strtolower($slug)]['is_variable'] = ($correctType === 'variable');
                            }

                            $rowAction = 'created';
                        } else {
                            // existingSkuId is set but importMode === 'create_only' → skip the SKU
                            $rowSkipped = true;

                            return;
                        }

                        if ($isDryRun && $rowAction !== null) {
                            throw DryRunRollback::for($rowAction);
                        }
                    });
                } catch (DryRunRollback $e) {
                    $rowAction = $e->action;
                }

                if ($rowError !== null) {
                    $this->logError($import, $rowNumber, $row, $rowError);
                    $failed++;

                    continue;
                }

                if ($rowLimitBlocked) {
                    $this->logError($import, $rowNumber, $row, 'Product limit exceeded. Upgrade your plan to import more products.');
                    $limitSkipped++;
                } elseif ($rowSkipped) {
                    $skipped++;
                } else {
                    $success++;
                    if ($rowAction === 'created') {
                        $created++;
                    } elseif ($rowAction === 'updated') {
                        $updated++;
                    }
                }
            } catch (\Throwable $e) {
                $this->logError($import, $rowNumber, $row, 'Unexpected error: '.$e->getMessage());
                $failed++;
            }
        }

        return [
            ...compact('success', 'failed', 'skipped', 'created', 'updated'),
            'limit_skipped' => $limitSkipped,
            'created_refs'  => $this->createdRefs,
        ];
    }

    // ── Reference resolution (auto-creating) ───────────────────────────────

    /**
     * Find the category by slug, creating it when absent.
     *
     * The lookup map is updated in place so repeated rows in the same file
     * reuse the row just created rather than inserting duplicates.
     */
    private function resolveCategoryId(string $rawSlug, int $companyId): ?int
    {
        $slug = Str::slug($rawSlug);

        if ($slug === '') {
            return null;
        }

        if (isset($this->categorySlugs[$slug])) {
            return $this->categorySlugs[$slug];
        }

        // company_id is assigned on the instance: Category omits it from
        // $fillable because Tenantable normally supplies it, and that does not
        // fire for every context this importer runs in.
        $category = new Category([
            'name'      => $this->titleFromSlug($rawSlug),
            'slug'      => $slug,
            'is_active' => true,
        ]);
        $category->company_id = $companyId;
        $category->save();

        $this->categorySlugs[$slug]  = $category->id;
        $this->createdRefs['categories'][] = $category->name;

        return $category->id;
    }

    /**
     * Find the unit by short name, creating it when absent.
     *
     * name is set to the short name because the CSV carries no separate label.
     * A unit called "pcs / pcs" is not pretty, but it is one rename away and
     * an extra CSV column would cost every user clarity to serve a few.
     */
    private function resolveUnitId(string $shortName, int $companyId): ?int
    {
        $shortName = trim($shortName);

        if ($shortName === '') {
            return null;
        }

        if (isset($this->unitShortNames[$shortName])) {
            return $this->unitShortNames[$shortName];
        }

        $unit = new Unit([
            'name'       => $shortName,
            'short_name' => $shortName,
            'is_active'  => true,
        ]);
        $unit->company_id = $companyId;
        $unit->save();

        $this->unitShortNames[$shortName] = $unit->id;
        $this->createdRefs['units'][]     = $unit->short_name;

        return $unit->id;
    }

    /**
     * Resolved once per chunk; null when the company has no warehouse yet.
     *
     * @return array{id: int, store_id: int|null}|null
     */
    private function defaultWarehouse(int $companyId): ?array
    {
        if ($this->defaultWarehouse !== null) {
            return $this->defaultWarehouse;
        }

        $warehouse = Warehouse::where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first(['id', 'store_id']);

        return $this->defaultWarehouse = $warehouse
            ? ['id' => $warehouse->id, 'store_id' => $warehouse->store_id]
            : null;
    }

    /** "indoor-plants" → "Indoor Plants", so an auto-created name reads well. */
    private function titleFromSlug(string $raw): string
    {
        return Str::title(str_replace(['-', '_'], ' ', trim($raw)));
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function validateRow(array $row): array
    {
        $errors = [];

        if (empty(trim($row['name'] ?? ''))) {
            $errors[] = 'Name is required';
        }
        if (empty(trim($row['slug'] ?? ''))) {
            $errors[] = 'Slug is required';
        }
        // Categories and units are auto-created when missing, so their absence
        // is no longer an error — only a blank category is. Rejecting a row
        // because a lookup table has not been populated yet is a dead end for
        // the person uploading, who cannot fix it from here.
        if (empty(trim($row['category_slug'] ?? ''))) {
            $errors[] = 'Category slug is required';
        }
        if (! empty($row['product_type'])) {
            $pt = strtolower(trim($row['product_type']));
            if (! in_array($pt, self::VALID_TYPES, true)) {
                $errors[] = "Invalid product_type '{$row['product_type']}'. Must be: sellable or catalog.";
            }
        }
        if (empty(trim((string) ($row['price'] ?? '')))) {
            $errors[] = 'Price is required';
        } elseif (! is_numeric($row['price']) || (float) $row['price'] < 0) {
            $errors[] = "Invalid price: '{$row['price']}'";
        }
        if (empty(trim((string) ($row['cost'] ?? '')))) {
            $errors[] = 'Cost is required';
        } elseif (! is_numeric($row['cost']) || (float) $row['cost'] < 0) {
            $errors[] = "Invalid cost: '{$row['cost']}'";
        }
        if (! empty($row['mrp']) && (! is_numeric($row['mrp']) || (float) $row['mrp'] < 0)) {
            $errors[] = "Invalid MRP: '{$row['mrp']}'";
        }
        if (! empty($row['stock_qty']) && ! is_numeric($row['stock_qty'])) {
            $errors[] = "Stock quantity must be a number: '{$row['stock_qty']}'";
        }
        for ($i = 1; $i <= self::MAX_ATTRIBUTES; $i++) {
            $name  = trim((string) ($row["attribute_{$i}_name"]  ?? ''));
            $value = trim((string) ($row["attribute_{$i}_value"] ?? ''));
            if (($name === '') !== ($value === '')) {
                $errors[] = "attribute_{$i}_name and attribute_{$i}_value must both be filled or both be empty.";
            }
        }

        return $errors;
    }

    /**
     * Parse title1/value1 … title10/value10 column pairs into product_guide array.
     */
    private function parseProductGuide(array $row): ?array
    {
        $guide = [];
        for ($i = 1; $i <= self::MAX_GUIDE_PAIRS; $i++) {
            $title = trim($row["title{$i}"] ?? '');
            $value = trim($row["value{$i}"] ?? '');
            if ($title !== '' && $value !== '') {
                $guide[] = ['title' => $title, 'description' => $value];
            }
        }

        return ! empty($guide) ? $guide : null;
    }

    private function extractAttributePairs(array $row): array
    {
        $pairs = [];
        for ($i = 1; $i <= self::MAX_ATTRIBUTES; $i++) {
            $name  = trim((string) ($row["attribute_{$i}_name"]  ?? ''));
            $value = trim((string) ($row["attribute_{$i}_value"] ?? ''));
            if ($name !== '' && $value !== '') {
                $pairs[] = ['name' => $name, 'value' => $value];
            }
        }

        return $pairs;
    }

    private function resolveAttributeId(int $companyId, string $name): int
    {
        $key = strtolower($name);
        if (isset($this->attributeIdByName[$key])) {
            return $this->attributeIdByName[$key];
        }
        $attribute = Attribute::withoutGlobalScopes()
            ->where('company_id', $companyId)->whereRaw('LOWER(name) = ?', [$key])->first();
        if (! $attribute) {
            $attribute              = new Attribute;
            $attribute->company_id  = $companyId;
            $attribute->name        = $name;
            $attribute->type        = 'text';
            $attribute->is_active   = true;
            $attribute->save();
        }

        return $this->attributeIdByName[$key] = $attribute->id;
    }

    private function resolveAttributeValueId(int $companyId, int $attributeId, string $value): int
    {
        $key = $attributeId.'|'.strtolower($value);
        if (isset($this->attributeValueIdByKey[$key])) {
            return $this->attributeValueIdByKey[$key];
        }
        $attrValue = AttributeValue::withoutGlobalScopes()
            ->where('company_id', $companyId)->where('attribute_id', $attributeId)
            ->whereRaw('LOWER(value) = ?', [strtolower($value)])->first();
        if (! $attrValue) {
            $attrValue                  = new AttributeValue;
            $attrValue->company_id      = $companyId;
            $attrValue->attribute_id    = $attributeId;
            $attrValue->value           = $value;
            $attrValue->position        = 0;
            $attrValue->is_active       = true;
            $attrValue->save();
        }

        return $this->attributeValueIdByKey[$key] = $attrValue->id;
    }

    private function generateSkuCode(string $productSlug, array $valueLabels): string
    {
        $parts = [Str::slug($productSlug)];
        foreach ($valueLabels as $label) {
            $clean = Str::slug($label);
            if ($clean !== '') {
                $parts[] = $clean;
            }
        }

        return strtoupper(implode('-', $parts));
    }

    private function ensureUniqueSkuCode(string $code): string
    {
        $candidate = $code;
        $attempts  = 0;
        while (isset($this->existingSkuCodes[strtolower($candidate)])) {
            $candidate = $code.'-'.strtoupper(Str::random(4));
            if (++$attempts > 10) {
                $candidate = $code.'-'.strtoupper(Str::random(8)).'-'.time();
                break;
            }
        }

        return $candidate;
    }

    private function preloadLookups(int $companyId): void
    {
        // Per-chunk, so the report only lists what this chunk created.
        $this->createdRefs = ['categories' => [], 'units' => []];

        $this->categorySlugs = Category::withoutGlobalScopes()
            ->where('company_id', $companyId)->pluck('id', 'slug')->toArray();

        $this->unitShortNames = Unit::withoutGlobalScopes()
            ->where('company_id', $companyId)->whereNotNull('short_name')
            ->pluck('id', 'short_name')->toArray();

        // store_id travels with the warehouse: a stock movement belongs to the
        // store that physically holds the stock, which the warehouse already
        // knows. Reading it from the session instead would be wrong whenever
        // the importing user has no store selected, or has a different one
        // selected than the warehouse named in the CSV.
        $this->warehousesByName = Warehouse::where('company_id', $companyId)
            ->get(['id', 'name', 'store_id'])
            ->mapWithKeys(fn ($w) => [
                strtolower(trim($w->name)) => ['id' => $w->id, 'store_id' => $w->store_id],
            ])->toArray();

        $this->productBySlug = [];
        Product::withoutGlobalScopes()->where('company_id', $companyId)
            ->get(['id', 'slug', 'type', 'product_unit_id'])
            ->each(function ($p) {
                $this->productBySlug[strtolower($p->slug)] = [
                    'id'          => $p->id,
                    'unit_id'     => $p->product_unit_id,
                    'is_variable' => $p->type === 'variable',
                ];
            });

        $this->existingSkuCodes = [];
        $this->variantCount     = [];
        ProductSku::withoutGlobalScopes()->where('company_id', $companyId)
            ->get(['id', 'sku', 'product_id'])->each(function ($s) {
                $this->existingSkuCodes[strtolower($s->sku)] = $s->id;
                $this->variantCount[$s->product_id]          = ($this->variantCount[$s->product_id] ?? 0) + 1;
            });

        $this->attributeIdByName = [];
        Attribute::withoutGlobalScopes()->where('company_id', $companyId)
            ->get(['id', 'name'])
            ->each(fn ($a) => $this->attributeIdByName[strtolower($a->name)] = $a->id);

        $this->attributeValueIdByKey = [];
        AttributeValue::withoutGlobalScopes()->where('company_id', $companyId)
            ->get(['id', 'attribute_id', 'value'])
            ->each(function ($v) {
                $this->attributeValueIdByKey[$v->attribute_id.'|'.strtolower($v->value)] = $v->id;
            });

        $this->existingCombos = [];
        $companySkuIds        = ProductSku::withoutGlobalScopes()->where('company_id', $companyId)->pluck('id');
        if ($companySkuIds->isNotEmpty()) {
            $values       = ProductSkuValue::whereIn('product_sku_id', $companySkuIds)
                ->get(['product_sku_id', 'attribute_value_id'])->groupBy('product_sku_id');
            $skuProductMap = ProductSku::withoutGlobalScopes()
                ->whereIn('id', $companySkuIds)->pluck('product_id', 'id');
            foreach ($values as $skuId => $valueRows) {
                $productId = $skuProductMap[$skuId] ?? null;
                if ($productId === null) {
                    continue;
                }
                $ids      = $valueRows->pluck('attribute_value_id')->map(fn ($v) => (int) $v)->sort()->values()->all();
                if (empty($ids)) {
                    continue;
                }
                $this->existingCombos[$productId][implode('-', $ids)] = $skuId;
            }
        }
    }

    private function sanitizeRowToUtf8(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $safeKey   = is_string($key)   ? mb_convert_encoding($key,   'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252') : $key;
            $safeValue = is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252') : $value;
            $clean[$safeKey] = $safeValue;
        }

        return $clean;
    }

    private function logError(Import $import, int $rowNumber, array $rowData, string $message): void
    {
        ImportLog::create([
            'import_id'     => $import->id,
            'row_number'    => $rowNumber,
            'row_data'      => array_filter($rowData, fn ($v, $k) => ! str_starts_with((string) $k, '_'), ARRAY_FILTER_USE_BOTH),
            'error_message' => $message,
        ]);
    }
}