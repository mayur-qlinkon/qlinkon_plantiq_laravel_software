<?php

namespace App\Models\Production;

use App\Models\User;
use App\Models\Warehouse;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchHarvest extends Model
{
    use Tenantable;

    // No SoftDeletes — each row has already decremented batch.current_quantity.
    // warehouse_id is a soft reference (no enforced FK) — warehouse may not exist
    // at query time. Stock push integration is V2.

    protected $table = 'production_batch_harvests';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'quantity_harvested',
        'harvested_on',
        'harvested_by',
        'warehouse_id',
        'notes',
        'status',
        'received_quantity',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'harvested_on'       => 'datetime',
        'quantity_harvested' => 'integer',
        'warehouse_id'       => 'integer',
        'status'             => \App\Enums\Production\HarvestStatus::class,
        'received_quantity'  => 'integer',
        'received_at'        => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function harvestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'harvested_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Soft reference — warehouse may not exist. Always guard with null check.
    // e.g. $harvest->warehouse?->name
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Audit trail — quantity corrections and cancellations. Insert-only, latest first at query time.
    public function logs(): HasMany
    {
        return $this->hasMany(BatchHarvestLog::class, 'harvest_id')->latest('performed_at');
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    // Whether this harvest has been assigned a destination warehouse.
    public function hasWarehouse(): bool
    {
        return !is_null($this->warehouse_id);
    }

    public function isPending(): bool
    {
        return $this->status === \App\Enums\Production\HarvestStatus::Pending;
    }

    public function isReceived(): bool
    {
        return $this->status === \App\Enums\Production\HarvestStatus::Received;
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForBatch(Builder $q, int $batchId): Builder
    {
        return $q->where('plant_batch_id', $batchId);
    }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('harvested_on', $date);
    }

    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereDate('harvested_on', '>=', $from)
            ->whereDate('harvested_on', '<=', $to);
    }

    public function scopeForWarehouse(Builder $q, int $warehouseId): Builder
    {
        return $q->where('warehouse_id', $warehouseId);
    }

    public function scopeUnassigned(Builder $q): Builder
    {
        return $q->whereNull('warehouse_id');
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderBy('harvested_on', 'desc')->orderBy('id', 'desc');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', \App\Enums\Production\HarvestStatus::Pending->value);
    }

    public function scopeReceived(Builder $q): Builder
    {
        return $q->where('status', \App\Enums\Production\HarvestStatus::Received->value);
    }
}