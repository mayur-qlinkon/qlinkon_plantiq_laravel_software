<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Ownership
        'purchase_return_id',
        'purchase_item_id',

        // Product References
        'product_id',
        'product_sku_id',
        'unit_id',

        // GST / HSN
        'hsn_code',
        'tax_percent',
        'cgst_percent',
        'sgst_percent',
        'igst_percent',

        // Quantities & Pricing
        'quantity',
        'unit_cost',

        'discount_type',
        'discount_value',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'total_price',

        // Batch Tracking & Reason
        'batch_number',
        'return_reason',

        // Extra
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Percentages (5,2)
        'tax_percent' => 'decimal:2',
        'cgst_percent' => 'decimal:2',
        'sgst_percent' => 'decimal:2',
        'igst_percent' => 'decimal:2',

        // High Precision Amounts (15,4)
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',

        'discount_value' => 'decimal:4',
        'discount_amount' => 'decimal:4',
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

    // Link back to the parent Purchase Return document
    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    // Link to the original Purchase Item (Fixes your error!)
    public function originalPurchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    // Link to the Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Link to the specific SKU
    public function productSku()
    {
        return $this->belongsTo(ProductSku::class);
    }

    // Link to the Unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
