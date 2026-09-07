<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_return_id',
        'invoice_item_id',
        'product_id',
        'product_sku_id',
        'unit_id',
        'product_name',
        'hsn_code',
        'quantity',
        'unit_price',
        'is_restocked',
        'tax_type',
        'discount_type',
        'discount_value',
        'discount_amount',
        'taxable_value',
        'tax_percent',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'tax_amount',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_restocked' => 'boolean',
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

    public function invoiceReturn(): BelongsTo
    {
        return $this->belongsTo(InvoiceReturn::class, 'invoice_return_id');
    }

    // 🌟 Links directly back to the exact line item on the original invoice
    public function originalInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
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
}
