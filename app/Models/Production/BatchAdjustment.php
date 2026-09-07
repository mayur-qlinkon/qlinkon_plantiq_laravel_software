<?php

namespace App\Models\Production;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchAdjustment extends Model
{
    use Tenantable;

    // No SoftDeletes — insert-only ledger, same convention as BatchLoss/BatchHarvest.
    // Each row has already applied its delta to batch.current_quantity at write time —
    // deleting a row without compensating the batch would corrupt the ledger.

    public $timestamps = false;

    protected $table = 'production_batch_adjustments';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'old_quantity',
        'new_quantity',
        'delta',
        'notes',
        'adjusted_by',
        'created_at',
    ];

    protected $casts = [
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
        'delta'        => 'integer',
        'created_at'   => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BatchAdjustment $adjustment) {
            if (empty($adjustment->created_at)) {
                $adjustment->created_at = now();
            }
        });
    }

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForBatch(Builder $q, int $batchId): Builder
    {
        return $q->where('plant_batch_id', $batchId);
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }
}