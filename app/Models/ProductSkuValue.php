<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSkuValue extends Model
{
    protected $fillable = [
        'product_sku_id',
        'attribute_id',
        'attribute_value_id',
    ];

    public $timestamps = false;

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
