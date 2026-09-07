<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 🛡️ Added the Iron Wall

class ProductMedia extends Model
{
    use HasFactory, Tenantable;

    protected $table = 'product_media';

    protected $fillable = [        
        'product_id',
        'product_sku_id', // 🌟 Added for the Pro Feature (Variation Images)
        'media_type',
        'media_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // 🌟 Added relationship to link image to a specific SKU
    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function getMediaUrlAttribute()
    {
        if ($this->media_type === 'youtube') {
            return $this->media_path;
        }

        return asset('storage/'.$this->media_path);
    }
}
