<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',

        // Relational IDs (Nullable for historical fallback)
        'product_id',
        'product_sku_id',
        'unit_id',

        // 📸 Text/Code Snapshots
        'product_name',
        'sku_code',
        'hsn_code',

        // 🔢 Core Metrics
        'quantity',
        'unit_price',
        'tax_type',

        // 💰 Discounts
        'discount_type',
        'discount_value',
        'discount_amount',

        // 🇮🇳 Tax & GST Breakups
        'taxable_value',
        'tax_percent',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',

        // 💵 Final Line Total
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     * 🌟 CRITICAL: Cast decimals to floats so frontend frameworks handle the math properly
     */
    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',

        'discount_value' => 'float',
        'discount_amount' => 'float',
        'taxable_value' => 'float',
        'tax_percent' => 'float',
        'cgst_amount' => 'float',
        'sgst_amount' => 'float',
        'igst_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
    ];

    // ─────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS & ACCESSORS
    // ─────────────────────────────────────────────────────────

    /**
     * Safely get the display name, appending the SKU if it exists in the snapshot.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->product_name.($this->sku_code ? " ({$this->sku_code})" : '');
    }
}
