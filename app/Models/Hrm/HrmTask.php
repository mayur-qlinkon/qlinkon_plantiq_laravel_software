<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmTask extends Model
{
    use  SoftDeletes, Tenantable;

    protected $table = 'hrm_tasks';

    // ── Constants ──

    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_IN_REVIEW = 'in_review';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_IN_REVIEW => 'In Review',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_ON_HOLD => 'On Hold',
    ];

    const STATUS_COLORS = [
        self::STATUS_PENDING => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        self::STATUS_IN_PROGRESS => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
        self::STATUS_IN_REVIEW => ['bg' => '#fffbeb', 'text' => '#92400e', 'dot' => '#f59e0b'],
        self::STATUS_COMPLETED => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
        self::STATUS_CANCELLED => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        self::STATUS_ON_HOLD => ['bg' => '#f5f3ff', 'text' => '#5b21b6', 'dot' => '#8b5cf6'],
    ];

    const PRIORITY_LABELS = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    const PRIORITY_COLORS = [
        self::PRIORITY_LOW => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
        self::PRIORITY_MEDIUM => ['bg' => '#eff6ff', 'text' => '#1e40af', 'dot' => '#3b82f6'],
        self::PRIORITY_HIGH => ['bg' => '#fffbeb', 'text' => '#92400e', 'dot' => '#f59e0b'],
        self::PRIORITY_URGENT => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
    ];

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_IN_REVIEW, self::STATUS_COMPLETED, self::STATUS_ON_HOLD, self::STATUS_CANCELLED],
        self::STATUS_IN_REVIEW => [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_ON_HOLD => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
    ];

    // ── Fillable ──

    protected $fillable = [
        'company_id', 'created_by',
        'title', 'description', 'project', 'category',
        'priority', 'status',
        'start_date', 'due_date', 'started_at','completed_at', 'progress_percent',
        'completion_note', 'is_recurring', 'recurrence_pattern',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'integer',
        'is_recurring' => 'boolean',
    ];
 

    // ── Relationships ──

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(HrmTaskAssignment::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hrm_task_assignments')
            ->withPivot('assigned_by', 'assigned_at', 'is_primary')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(HrmTaskAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HrmTaskComment::class)->whereNull('parent_id')->orderBy('created_at');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(HrmTaskComment::class)->orderBy('created_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(HrmTaskActivity::class)->orderByDesc('created_at');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    // ── Scopes ──

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('due_date', today())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    // ── Methods ──

    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        return in_array($newStatus, $allowed);
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

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? $this->priority;
    }

    public function getPriorityColorAttribute(): array
    {
        return self::PRIORITY_COLORS[$this->priority] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'];
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }
}
