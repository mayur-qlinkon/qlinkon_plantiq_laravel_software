<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLog extends Model
{
    use SoftDeletes, Tenantable;

    // ── Constants ──

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    const STATUS_COLORS = [
        self::STATUS_DRAFT => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        self::STATUS_SUBMITTED => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
        self::STATUS_APPROVED => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
        self::STATUS_REJECTED => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];

    // ── Fillable ──

    protected $fillable = [
        'company_id', 'employee_id', 'hrm_task_id',
        'log_date', 'hours_worked', 'start_time', 'end_time',
        'description', 'category', 'status',
        'approved_by', 'approved_at', 'admin_remarks',
    ];

    protected $casts = [
        'log_date' => 'date',
        'hours_worked' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // ── Relationships ──

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(HrmTask::class, 'hrm_task_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ──

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('log_date', $date);
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('log_date', [$from, $to]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): array
    {
        return self::STATUS_COLORS[$this->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'];
    }
}
