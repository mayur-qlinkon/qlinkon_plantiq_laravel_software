<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'product_sku_id', 'unit_id',
        'product_name', 'hsn_code', 'quantity', 'unit_price',
        'tax_type', 'discount_type', 'discount_value', 'discount_amount', 'taxable_value',
        'tax_percent', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'tax_amount', 'total_amount', 'return_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'taxable_value' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'return_quantity' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'discount_amount' => 'decimal:4',
    ];

    /**
     * How many more units can still be returned against this invoice line.
     * Only confirmed returns are reflected in `return_quantity`.
     */
    protected function remainingReturnableQty(): Attribute
    {
        return Attribute::get(
            fn (): float => max(0.0, (float) $this->quantity - (float) ($this->return_quantity ?? 0))
        );
    }

    /**
     * Convenience flag — true when no further returns are allowed on this line.
     */
    protected function isFullyReturned(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->remaining_returnable_qty <= 0
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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
