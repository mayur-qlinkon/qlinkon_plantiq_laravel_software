<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Product extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    protected $fillable = [
        'company_id',        
        'category_id',
        'supplier_id',
        'name',
        'slug',
        'type',
        'product_type',
        'barcode_symbology',
        'hsn_code',
        'product_unit_id',
        'sale_unit_id',
        'purchase_unit_id',
        'quantity_limitation',
        'note',
        'description',
        'specifications',
        'product_guide',
        'is_active',
        'show_in_storefront',
        'show_as_addon',
        'total_sold',
    ];

    protected $casts = [
        'specifications' => 'array',
        'product_guide' => 'array',
        'is_active' => 'boolean',
        'show_in_storefront' => 'boolean',
        'show_as_addon' => 'boolean',
        'total_sold' => 'integer',
    ];

    // In Product model — add this property
    protected $appends = ['primary_image_url'];
  

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {

            if (empty($product->slug)) {

                do {
                    $slug = Str::upper(Str::random(6)); // 6-char alphanumeric
                } while (
                    static::where('slug', $slug)->exists()
                );

                $product->slug = $slug;
            }
        });
    }

    // --- Helpers ---

    public function isCatalog(): bool
    {
        return $this->product_type === 'catalog';
    }

    // --- Core Relationships ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Categories this product belongs to (via pivot).
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_products'
        )
            ->withPivot(['is_active', 'is_featured', 'sort_order', 'added_by'])
            ->withTimestamps()
            ->orderByPivot('sort_order', 'asc');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Pivot relationship — products through category_products table.
     * Gives access to per-category sort_order, is_featured, is_active.
     */
    public function categoryPivots(): HasMany
    {
        return $this->hasMany(CategoryProduct::class);
    }

    public function sectionPivots(): HasMany
    {
        return $this->hasMany(StorefrontSectionProduct::class);
    }

    // --- Unit Relationships ---

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'product_unit_id');
    }

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    // --- Architecture Relationships ---

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order', 'asc');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // --- Helper Methods ---

    /**
     * Safely get the main thumbnail URL for the product.
     */
    public function getPrimaryImageUrlAttribute()
    {
        // First, explicitly look for an 'image' that is marked as primary
        $primaryMedia = $this->media->where('media_type', 'image')->where('is_primary', true)->first();

        // If no primary is set, fallback to the first available image
        if (! $primaryMedia) {
            $primaryMedia = $this->media->where('media_type', 'image')->first();
        }

        // Fallback to a default placeholder if no images exist at all
        return $primaryMedia ? asset('storage/'.$primaryMedia->media_path) : asset('assets/defaults/product.svg');
    }

    /**
     * Scope — only products visible on storefront.
     * Apply this on EVERY public-facing query.
     */
    public function scopeStorefrontVisible(Builder $query): Builder
    {
        return $query->where('show_in_storefront', true)->where('is_active', true);
    }
}
