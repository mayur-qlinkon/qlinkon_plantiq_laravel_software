<?php

namespace App\Models\Hrm;

use App\Models\User;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use  SoftDeletes, Tenantable;

    // ── Constants ──

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    const DAY_FULL = 'full_day';

    const DAY_FIRST_HALF = 'first_half';

    const DAY_SECOND_HALF = 'second_half';

    const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const STATUS_COLORS = [
        self::STATUS_PENDING => ['bg' => '#fffbeb', 'text' => '#92400e', 'dot' => '#f59e0b'],
        self::STATUS_APPROVED => ['bg' => '#ecfdf5', 'text' => '#065f46', 'dot' => '#10b981'],
        self::STATUS_REJECTED => ['bg' => '#fef2f2', 'text' => '#991b1b', 'dot' => '#ef4444'],
        self::STATUS_CANCELLED => ['bg' => '#f3f4f6', 'text' => '#374151', 'dot' => '#9ca3af'],
    ];

    // ── Fillable ──

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id',
        'from_date', 'to_date', 'total_days', 'day_type',
        'reason', 'document',
        'status', 'approved_by', 'approved_at', 'admin_remarks',
        'cancellation_reason', 'cancelled_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'total_days' => 'decimal:1',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
   

    // ── Relationships ──

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ──

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeForDateRange(Builder $query, $from, $to): Builder
    {
        return $query->where('from_date', '<=', $to)->where('to_date', '>=', $from);
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
