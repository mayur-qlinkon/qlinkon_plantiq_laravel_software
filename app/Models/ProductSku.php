<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // 🛡️ Added the Iron Wall

class ProductSku extends Model
{
    use HasFactory, SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',        
        'product_id',
        'unit_id',
        'sku',
        'hsn_code',
        'barcode',
        'cost',
        'price',
        'mrp',
        'order_tax',
        'tax_type',
        'stock_alert',
        'is_active',
        'total_sold',        
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_sold' => 'integer',
        'low_stock_notified_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withDefault([
            'name' => 'Deleted/Unknown Product',
            'category_id' => null,
        ]);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function skuValues(): HasMany
    {
        return $this->hasMany(ProductSkuValue::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // 🌟 UPDATED: Now points to the correct ProductStock model
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_sku_id');
    }

    // 🌟 UPDATED: Uses the correct 'product_stocks' table and new pivot fields
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_stocks')
            ->withPivot('qty', 'rack_number')
            ->withTimestamps();
    }

    // Helper to get total live stock for this specific SKU across all warehouses
    public function getTotalStockAttribute()
    {
        // Sums up the 'qty' column directly from the product_stocks table
        return $this->stocks()->sum('qty');
    }

    /**
     * CORE RULE: If barcode exists -> use barcode. If empty -> use SKU.
     */
    public function getDisplayBarcodeAttribute(): string
    {
        return ! empty($this->barcode) ? $this->barcode : $this->sku;
    }

    /**
     * Quick "is this variant purchasable right now" flag for the storefront.
     *
     * Uses the loaded `stocks` relation when present (preferred — avoids N+1
     * on variant grids) and falls back to a single SUM query otherwise.
     * Returns false for soft-inactive SKUs regardless of stock qty so we
     * never surface a disabled variant as "in stock".
     */
    public function getIsInStockAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $qty = $this->relationLoaded('stocks')
            ? (int) $this->stocks->sum('qty')
            : (int) $this->stocks()->sum('qty');

        return $qty > 0;
    }

    /**
     * Relationship: A SKU can have many batches.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_sku_id');
    }
}
