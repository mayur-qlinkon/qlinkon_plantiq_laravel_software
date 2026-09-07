<?php

namespace App\Models\Production;

use App\Enums\Production\LossReason;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchLoss extends Model
{
    use Tenantable;

    // No SoftDeletes — ledger integrity must be preserved.
    // Each row has already decremented batch.current_quantity at write time.
    // Deleting a loss row without compensating the batch quantity = data corruption.

    protected $table = 'production_batch_losses';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'quantity_lost',
        'loss_date',
        'reason',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'loss_date'     => 'date',
        'quantity_lost' => 'integer',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function reasonEnum(): LossReason
    {
        return LossReason::from($this->reason);
    }

    public function getReasonLabelAttribute(): string
    {
        return LossReason::from($this->reason)->label();
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForBatch(Builder $q, int $batchId): Builder
    {
        return $q->where('plant_batch_id', $batchId);
    }

    public function scopeByReason(Builder $q, string $reason): Builder
    {
        return $q->where('reason', $reason);
    }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->where('loss_date', $date);
    }

    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('loss_date', [$from, $to]);
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderBy('loss_date', 'desc')->orderBy('id', 'desc');
    }
}