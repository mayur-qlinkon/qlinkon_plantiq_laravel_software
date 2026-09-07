<?php

namespace App\Models\Production;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchPlacement extends Model
{
    use Tenantable;

    // No SoftDeletes — insert-only ledger. Deleting a placement row
    // would corrupt the movement history. Records are closed via ended_at.

    protected $table = 'production_batch_placements';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'growing_space_id',
        'placed_at',
        'ended_at',
        'placed_by',
        'notes',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'ended_at'  => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function growingSpace(): BelongsTo
    {
        return $this->belongsTo(GrowingSpace::class, 'growing_space_id');
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    // A placement row with no ended_at is the CURRENT active placement.
    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    // Close this placement row — call this before inserting a new placement.
    // Save separately; service layer owns the transaction.
    public function close(): void
    {
        $this->ended_at = now();
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    // Current active placements only (ended_at = null).
    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('ended_at');
    }

    // Historical placements only (ended_at is set).
    public function scopeHistory(Builder $q): Builder
    {
        return $q->whereNotNull('ended_at');
    }

    // All placements for a given batch — active + historical.
    public function scopeForBatch(Builder $q, int $batchId): Builder
    {
        return $q->where('plant_batch_id', $batchId);
    }

    // All placements currently occupying a specific growing space.
    public function scopeInSpace(Builder $q, int $growingSpaceId): Builder
    {
        return $q->where('growing_space_id', $growingSpaceId)->whereNull('ended_at');
    }
}