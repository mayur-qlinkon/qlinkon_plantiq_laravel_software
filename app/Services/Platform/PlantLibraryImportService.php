<?php

namespace App\Services\Platform;

use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Platform\PlantLibrary;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSku;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PlantLibraryImportService
{
    /**
     * Import one master library plant into a tenant's own Product catalog.
     *
     * Runs from the Platform (Super Admin) context, whose user has no
     * company_id — so Tenantable's auto-assign-on-creating never fires here.
     * Every tenant-scoped model touched below has its company_id set
     * explicitly instead of relying on that fallback.
     *
     * Media is referenced by path, never re-uploaded — the same "no
     * re-upload" pattern ProductService::duplicateProduct() already uses.
     *
     * $overrides:
     *   'product_type' => 'catalog' (default) | 'sellable'
     *   'price'        => required if sellable
     *   'cost'         => required if sellable
     *   'mrp'          => optional, sellable only
     */
    public function importToCompany(PlantLibrary $plant, int $companyId, array $overrides = []): Product
    {
        $productType = $overrides['product_type'] ?? 'catalog';

        if (! in_array($productType, ['catalog', 'sellable'], true)) {
            throw new InvalidArgumentException("Invalid product_type '{$productType}'.");
        }

        if ($productType === 'sellable' && (! isset($overrides['price']) || ! isset($overrides['cost']))) {
            throw new InvalidArgumentException('Price and cost are required for a sellable import.');
        }

        return DB::transaction(function () use ($plant, $companyId, $productType, $overrides) {

            $categoryId = $this->resolveCategory($plant->category_name, $companyId);
            $unitId     = $this->resolveUnit($plant->unit_short_name, $companyId);

            // Product & ProductSku both have company_id in $fillable — safe via create().
            $product = Product::create([
                'company_id'         => $companyId,
                'category_id'        => $categoryId,
                'product_unit_id'    => $unitId,
                'sale_unit_id'       => $unitId,
                'purchase_unit_id'   => $unitId,
                'name'               => $plant->name,
                'type'               => $productType === 'catalog' ? 'single' : $plant->type,
                'product_type'       => $productType,
                'description'        => $plant->description,
                'product_guide'      => $plant->product_guide,
                'is_active'          => true,
                'show_in_storefront' => true,
            ]);

            if ($productType === 'sellable') {
                ProductSku::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'unit_id'    => $unitId,
                    'sku'        => $this->generateSkuCode($plant->slug, $companyId),
                    'cost'       => $overrides['cost'],
                    'price'      => $overrides['price'],
                    'mrp'        => $overrides['mrp'] ?? $overrides['price'],
                    'is_active'  => true,
                ]);
            }

            $this->copyMedia($plant, $product, $companyId);

            if ($categoryId) {
                CategoryProduct::attachProduct(categoryId: $categoryId, productId: $product->id);
            }

            return $product;
        });
    }

    /**
     * Batch import — all-or-nothing across the whole selection.
     * $overridesByPlantId is keyed by plant_library.id.
     */
    public function importManyToCompany(iterable $plants, int $companyId, array $overridesByPlantId = []): Collection
    {
        return DB::transaction(function () use ($plants, $companyId, $overridesByPlantId) {
            $created = collect();

            foreach ($plants as $plant) {
                $overrides = $overridesByPlantId[$plant->id] ?? [];
                $created->push($this->importToCompany($plant, $companyId, $overrides));
            }

            return $created;
        });
    }

    /**
     * Copy library media onto the new product by referencing the SAME
     * stored file path — no re-upload, no duplicated disk usage.
     * ProductMedia.company_id is NOT fillable, so it's set directly.
     */
    private function copyMedia(PlantLibrary $plant, Product $product, int $companyId): void
    {
        foreach ($plant->media as $media) {
            $newMedia = new ProductMedia([
                'product_id' => $product->id,
                'media_type' => $media->media_type,
                'media_path' => $media->media_path,
                'is_primary' => $media->is_primary,
                'sort_order' => $media->sort_order,
            ]);
            $newMedia->company_id = $companyId;
            $newMedia->save();
        }
    }

    /**
     * Find or create the tenant's own category matching the library's
     * suggested label. Looked up by slug (not raw name) so it lines up
     * exactly with the categories table's (company_id, slug) unique
     * constraint and avoids case/whitespace collisions.
     *
     * Tenant can rename their copy afterwards — this never touches the
     * master library or any category already created from a prior import.
     * Category.company_id is NOT fillable, so it's set directly.
     */
    private function resolveCategory(?string $categoryName, int $companyId): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $slug = Str::slug($categoryName);

        $category = Category::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            $category = new Category([
                'name'      => $categoryName,
                'is_active' => true,
            ]);
            $category->company_id = $companyId;
            $category->save();
        }

        return $category->id;
    }

    /**
     * Find or create the tenant's own unit matching the library's
     * suggested short name (e.g. "pcs"). Falls back to the tenant's
     * first existing unit if the library entry has none set.
     * Unit.company_id is NOT fillable, so it's set directly.
     */
    private function resolveUnit(?string $unitShortName, int $companyId): ?int
    {
        if (empty($unitShortName)) {
            return Unit::withoutGlobalScopes()->where('company_id', $companyId)->value('id');
        }

        $unit = Unit::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('short_name', $unitShortName)
            ->first();

        if (! $unit) {
            $unit = new Unit([
                'name'       => ucfirst($unitShortName),
                'short_name' => $unitShortName,
                'is_active'  => true,
            ]);
            $unit->company_id = $companyId;
            $unit->save();
        }

        return $unit->id;
    }

    /**
     * Same generation convention as ProductWithSkuImporter / SkuImporter —
     * slug-based base, random suffix on collision, unique per company.
     */
    private function generateSkuCode(string $plantSlug, int $companyId): string
    {
        $base = Str::upper(Str::slug($plantSlug));
        $sku  = $base;

        while (
            ProductSku::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('sku', $sku)
                ->exists()
        ) {
            $sku = $base.'-'.Str::upper(Str::random(4));
        }

        return $sku;
    }
}