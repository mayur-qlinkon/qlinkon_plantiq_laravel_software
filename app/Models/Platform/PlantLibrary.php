<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlantLibrary extends Model
{
    use HasFactory;

    protected $table = 'plant_library';

    /**
     * Platform-level master data — intentionally NOT Tenantable.
     * Super Admin curates this once; tenants clone from it into
     * their own company-scoped Product + ProductSku records.
     */
    protected $fillable = [
        'name',
        'slug',
        'category_name',
        'type',
        'product_type',
        'unit_short_name',
        'description',
        'product_guide',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'product_guide' => 'array',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
        ];
    }

    /**
     * Boot function to handle automatic slug generation.
     * Same convention as Category::boot().
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $plant) {
            if (empty($plant->slug)) {
                $plant->slug = Str::slug($plant->name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function media(): HasMany
    {
        return $this->hasMany(PlantLibraryMedia::class)->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPrimaryMediaAttribute(): ?PlantLibraryMedia
    {
        if (! $this->relationLoaded('media')) {
            $this->load('media');
        }

        return $this->media->firstWhere('is_primary', true) ?? $this->media->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}