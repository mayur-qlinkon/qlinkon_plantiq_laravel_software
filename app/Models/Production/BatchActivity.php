<?php

namespace App\Models\Production;

use App\Enums\Production\ActivityType;
use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchActivity extends Model
{
    use Tenantable;

    // No SoftDeletes — pure insert-only activity log.
    // Deleting an activity would misrepresent what work was done on a batch.

    protected $table = 'production_batch_activities';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'activity_type',
        'performed_on',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'performed_on' => 'datetime',
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════

    public function activityTypeEnum(): ActivityType
    {
        return ActivityType::from($this->activity_type);
    }

    public function getActivityTypeLabelAttribute(): string
    {
        return ActivityType::from($this->activity_type)->label();
    }

    public function getActivityTypeIconAttribute(): string
    {
        return ActivityType::from($this->activity_type)->icon();
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForBatch(Builder $q, int $batchId): Builder
    {
        return $q->where('plant_batch_id', $batchId);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('activity_type', $type);
    }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('performed_on', $date);
    }

    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereDate('performed_on', '>=', $from)
            ->whereDate('performed_on', '<=', $to);
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderBy('performed_on', 'desc')->orderBy('id', 'desc');
    }
}