<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRule extends Model
{
    use SoftDeletes, Tenantable;

    const TYPE_LATE_TO_HALF_DAY = 'late_to_half_day';

    const TYPE_LATE_TO_ABSENT = 'late_to_absent';

    const TYPE_ABSENT_TO_LEAVE = 'absent_to_leave';

    const TYPE_EARLY_LEAVE_PENALTY = 'early_leave_penalty';

    const TYPE_CONTINUOUS_ABSENT = 'continuous_absent_action';

    const TYPE_OVERTIME_RULE = 'overtime_rule';

    const TYPE_CUSTOM = 'custom';

    const ACTION_MARK_HALF_DAY = 'mark_half_day';

    const ACTION_MARK_ABSENT = 'mark_absent';

    const ACTION_DEDUCT_LEAVE = 'deduct_leave';

    const ACTION_SEND_WARNING = 'send_warning';

    const ACTION_NOTIFY_MANAGER = 'notify_manager';

    const TYPE_LABELS = [
        self::TYPE_LATE_TO_HALF_DAY => 'Late → Half Day',
        self::TYPE_LATE_TO_ABSENT => 'Late → Absent',
        self::TYPE_ABSENT_TO_LEAVE => 'Absent → Leave Deduction',
        self::TYPE_EARLY_LEAVE_PENALTY => 'Early Leave Penalty',
        self::TYPE_CONTINUOUS_ABSENT => 'Continuous Absent Action',
        self::TYPE_OVERTIME_RULE => 'Overtime Rule',
        self::TYPE_CUSTOM => 'Custom Rule',
    ];

    const ACTION_LABELS = [
        self::ACTION_MARK_HALF_DAY => 'Mark Half Day',
        self::ACTION_MARK_ABSENT => 'Mark Absent',
        self::ACTION_DEDUCT_LEAVE => 'Deduct Leave',
        self::ACTION_SEND_WARNING => 'Send Warning',
        self::ACTION_NOTIFY_MANAGER => 'Notify Manager',
    ];

    const PERIOD_LABELS = [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'rule_type',
        'threshold_count',
        'threshold_period',
        'action',
        'deduction_days',
        'leave_type_code',
        'auto_apply',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'threshold_count' => 'integer',
        'deduction_days' => 'decimal:1',
        'auto_apply' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoApply(Builder $query): Builder
    {
        return $query->where('auto_apply', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    // ── Accessors ──

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->rule_type] ?? $this->rule_type;
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function getPeriodLabelAttribute(): string
    {
        return self::PERIOD_LABELS[$this->threshold_period] ?? $this->threshold_period;
    }
}
