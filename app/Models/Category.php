<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    protected $fillable = [
        'name',
        'image',
        'slug',
        'parent_id',
        'is_active',        
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot function to handle automatic slug generation
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Helper to get image URL
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/'.$this->image) : asset('assets/defaults/category.svg');
    }

    /**
     * Products in this category via pivot.
     * Ordered by featured first, then sort_order.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'category_products'
        )
            ->withPivot(['is_active', 'is_featured', 'sort_order', 'added_by'])
            ->withTimestamps()
            ->orderByPivot('is_featured', 'desc')
            ->orderByPivot('sort_order', 'asc');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Only active products in this category (storefront use).
     */
    public function activeProducts(): BelongsToMany
    {
        return $this->products()
            ->wherePivot('is_active', true)
            ->where('show_in_storefront', true)
            ->where('is_active', true);
    }

    /**
     * Storefront sections that reference this category.
     */
    public function storefrontSections(): HasMany
    {
        return $this->hasMany(StorefrontSection::class);
    }
}
