<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    // Purchase soft-deletes, so its lines must too — otherwise destroy() wiped
    // the items permanently and restoring the header gave an empty order.
    use HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Ownership
        'purchase_id',
        'company_id',

        // Product References
        'product_id',
        'product_sku_id',
        'unit_id',

        // GST / HSN
        'hsn_code',
        'tax_percent',
        'tax_type',
        'cgst_percent',
        'sgst_percent',
        'igst_percent',

        // Quantities
        'quantity',
        'quantity_received',
        'quantity_returned',

        // Pricing
        'unit_cost',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'total_price',

        // Batch Tracking
        'batch_number',
        'manufacturing_date',
        'expiry_date',

        // Extra
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Dates
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',

        // Percentages (5,2 in DB)
        'tax_percent' => 'decimal:2',
        'cgst_percent' => 'decimal:2',
        'sgst_percent' => 'decimal:2',
        'igst_percent' => 'decimal:2',

        // Quantities and Amounts (15,4 in DB for high precision in ERPs)
        'quantity' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'quantity_returned' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'taxable_amount' => 'decimal:4',
        'cgst_amount' => 'decimal:4',
        'sgst_amount' => 'decimal:4',
        'igst_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_price' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
