<?php

namespace App\Models\Production;

use App\Enums\Production\ActivityType;
use App\Enums\Production\TaskStatus;
use App\Models\Hrm\Employee;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTask extends Model
{
    use Tenantable;

    protected $table = 'production_daily_tasks';

    protected $fillable = [
        'company_id',
        'plant_batch_id',
        'batch_placement_id',
        'batch_activity_id',
        'zone_id',
        'activity_template_id',
        'activity_type',
        'is_required',
        'due_date',
        'status',
        'completed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'activity_type' => ActivityType::class,
        'status' => TaskStatus::class,
    ];

    // ════════════════════════════════════════════════════
    //  RELATIONSHIPS
    // ════════════════════════════════════════════════════

    public function plantBatch(): BelongsTo
    {
        return $this->belongsTo(PlantBatch::class, 'plant_batch_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(BatchPlacement::class, 'batch_placement_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplate::class, 'activity_template_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'completed_by');
    }

    // ════════════════════════════════════════════════════
    //  SCOPES
    // ════════════════════════════════════════════════════

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('due_date', $date);
    }

    public function scopeForZone(Builder $q, int $zoneId): Builder
    {
        return $q->where('zone_id', $zoneId);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', TaskStatus::Pending->value);
    }
}