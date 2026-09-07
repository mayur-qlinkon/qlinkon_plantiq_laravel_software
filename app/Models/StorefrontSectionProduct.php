<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class StorefrontSectionProduct extends Model
{
    protected $table = 'storefront_section_products';

    protected $fillable = [
        'storefront_section_id',
        'product_id',
        'sort_order',
        'added_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function section(): BelongsTo
    {
        return $this->belongsTo(StorefrontSection::class, 'storefront_section_id');
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

    public function scopeForSection(Builder $q, int $sectionId): Builder
    {
        return $q->where('storefront_section_id', $sectionId);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order');
    }

    // ════════════════════════════════════════════════════
    //  STATIC HELPERS
    // ════════════════════════════════════════════════════

    public static function addProduct(int $sectionId, int $productId): self
    {
        $maxOrder = static::forSection($sectionId)->max('sort_order') ?? -1;

        return static::firstOrCreate(
            ['storefront_section_id' => $sectionId, 'product_id' => $productId],
            ['sort_order' => $maxOrder + 1, 'added_by' => Auth::id()]
        );
    }

    public static function removeProduct(int $sectionId, int $productId): bool
    {
        return (bool) static::where('storefront_section_id', $sectionId)
            ->where('product_id', $productId)
            ->delete();
    }

    public static function reorderInSection(int $sectionId, array $productIds): void
    {
        foreach ($productIds as $sortOrder => $productId) {
            static::where('storefront_section_id', $sectionId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $sortOrder]);
        }
    }
}
