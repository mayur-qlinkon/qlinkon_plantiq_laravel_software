<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, Tenantable;

    /**
     * store_id is derived from the warehouse, never from the operator.
     *
     * Every caller was working this out for itself and each got it slightly
     * differently — one read $sku->store_id, an attribute that does not exist
     * on ProductSku, so it resolved to null and the insert failed the NOT NULL
     * constraint. Others read Auth::user()->store_id, a column the users table
     * does not have, and only worked because the fallback behind it did.
     *
     * Stock physically sits in a warehouse and a warehouse belongs to exactly
     * one store, so that is the only correct answer. Deriving it here means a
     * caller cannot get it wrong, and cannot record a movement against the
     * store the operator happens to be switched to while the goods leave a
     * warehouse belonging to another.
     */
    protected static function booted(): void
    {
        static::creating(function (self $movement) {
            if ($movement->warehouse_id) {
                $movement->store_id = Warehouse::whereKey($movement->warehouse_id)->value('store_id');
            }
        });
    }

    protected $fillable = [
        'store_id',
        'product_sku_id',
        'warehouse_id',
        'batch_id',
        'batch_number',
        'unit_id',
        'unit_cost',
        'direction',
        'user_id',
        'quantity',
        'balance_after',
        'movement_type',  // 'purchase', 'sale', 'purchase_return', 'sale_return',  'adjustment', 'transfer_in', 'transfer_out', 'opening_stock'
        'reference_type',
        'reference_id',
        'note',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'quantity' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to link back to the exact action that caused this movement.
     * e.g., $movement->reference might return an App\Models\Invoice instance.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
