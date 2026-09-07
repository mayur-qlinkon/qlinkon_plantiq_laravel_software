<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryProduct extends Model
{
    protected $table = 'category_products';

    protected $fillable = [
        'category_id',
        'product_id',
        'is_active',
        'is_featured',
        'sort_order',
        'added_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  BOOT — Auto-set added_by
    // ════════════════════════════════════════════════════

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CategoryProduct $pivot) {
            if (empty($pivot->added_by) && Auth::check()) {
                $pivot->added_by = Auth::id();
            }

            // Auto sort_order: append to end of this category
            if (empty($pivot->sort_order) && $pivot->sort_order !== 0) {
                $max = static::where('category_id', $pivot->category_id)->max('sort_order');
                $pivot->sort_order = ($max ?? -1) + 1;
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    /**
     * Only active entries in this pivot.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Only featured products in this pivot.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Sort by featured first, then sort_order.
     * Featured products always appear at top of category.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order', 'asc');
    }

    /**
     * Filter by a specific category.
     */
    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Filter by a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    // ════════════════════════════════════════════════════
    //  STATIC HELPERS
    // ════════════════════════════════════════════════════

    /**
     * Attach a product to a category safely.
     * Ignores duplicate — won't throw on re-attach.
     */
    public static function attachProduct(
        int $categoryId,
        int $productId,
        bool $isActive = true,
        bool $isFeatured = false,
        ?int $sortOrder = null
    ): static {
        return static::firstOrCreate(
            ['category_id' => $categoryId, 'product_id' => $productId],
            [
                'is_active' => $isActive,
                'is_featured' => $isFeatured,
                'sort_order' => $sortOrder ?? (static::where('category_id', $categoryId)->max('sort_order') + 1),
                'added_by' => Auth::id(),
            ]
        );
    }

    /**
     * Detach a product from a category.
     */
    public static function detachProduct(int $categoryId, int $productId): bool
    {
        return (bool) static::where('category_id', $categoryId)
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * Reorder products within a category using a single CASE WHEN query.
     * Pass array of product IDs in desired order.
     */
    public static function reorderInCategory(int $categoryId, array $productIds): bool
    {
        if (empty($productIds)) {
            return true;
        }

        $cases = '';
        $ids = implode(',', array_map('intval', $productIds));

        foreach ($productIds as $sortOrder => $productId) {
            $cases .= "WHEN {$productId} THEN {$sortOrder} ";
        }

        DB::statement("
            UPDATE category_products
            SET sort_order = CASE product_id {$cases} END,
                updated_at = NOW()
            WHERE category_id = {$categoryId}
              AND product_id IN ({$ids})
        ");

        return true;
    }

    /**
     * Sync products for a category — replaces entire list.
     * Preserves existing sort_order and settings for products that stay.
     */
    public static function syncCategory(int $categoryId, array $productIds): void
    {
        $existing = static::where('category_id', $categoryId)
            ->pluck('product_id')
            ->toArray();

        $toAdd = array_diff($productIds, $existing);
        $toRemove = array_diff($existing, $productIds);

        // Remove products no longer in category
        if (! empty($toRemove)) {
            static::where('category_id', $categoryId)
                ->whereIn('product_id', $toRemove)
                ->delete();
        }

        // Add new products
        foreach ($toAdd as $productId) {
            static::attachProduct($categoryId, $productId);
        }
    }
}
