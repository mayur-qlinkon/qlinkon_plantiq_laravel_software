<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes,Tenantable;

    protected $fillable = [
        'store_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'state_id',
        'zip_code',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to the Store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Stock physically sitting in this warehouse right now.
     *
     * Rows with a zero quantity are ignored: product_stocks keeps a row per
     * SKU per warehouse whether or not anything is there, so counting rows
     * would report an empty warehouse as full.
     */
    public function hasStockOnHand(): bool
    {
        return $this->stocks()->where('qty', '>', 0)->exists();
    }

    public function stockOnHandCount(): int
    {
        return $this->stocks()->where('qty', '>', 0)->count();
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
