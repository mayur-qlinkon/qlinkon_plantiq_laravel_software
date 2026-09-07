<?php

namespace App\Models\Hrm;

use App\Traits\Tenantable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use  SoftDeletes, Tenantable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'start_time',
        'end_time',
        'late_mark_after',
        'early_leave_before',
        'half_day_after',
        'break_duration_minutes',
        'min_working_hours_minutes',
        'overtime_after_minutes',
        'is_night_shift',
        'weekly_off_days',
        'alternate_saturday_offs',
        'is_default',
        'is_active',
        'sort_order',
    ];

    /**
     * Whether this shift rosters the given date as a weekly off.
     *
     * Both the payroll divisor and MarkAbsentEmployeesCommand ask this, so the
     * roster cannot drift between what is paid and what is recorded.
     */
    public function isWeeklyOff(\Carbon\CarbonInterface $date): bool
    {
        $offDays = $this->weekly_off_days ?? Attendance::WEEKLY_OFF_DAYS;

        if (in_array($date->dayOfWeek, $offDays, true)) {
            return true;
        }

        $alternates = $this->alternate_saturday_offs ?? [];

        if (empty($alternates) || $date->dayOfWeek !== 6) {
            return false;
        }

        // Which Saturday of the month this is: the 1st through the 5th.
        $nth = (int) ceil($date->day / 7);

        return in_array($nth, $alternates, true);
    }

    /**
     * Exactly one default shift per company.
     *
     * Enforced here rather than in the controller because Shift rows are also
     * created by TenantBootstrapper during onboarding, which never runs through
     * ShiftController. Two defaults would make the employee form's preselected
     * shift arbitrary.
     *
     * Runs on saved(), so the demotion happens only after this row is safely
     * written — a failed insert can no longer leave the company with no
     * default at all.
     */
    protected static function booted(): void
    {
        static::saved(function (self $shift) {
            if ($shift->is_default) {
                static::withoutEvents(fn () => static::where('company_id', $shift->company_id)
                    ->whereKeyNot($shift->id)
                    ->update(['is_default' => false]));
            }
        });
    }

    protected $casts = [
        'is_night_shift' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'break_duration_minutes' => 'integer',
        'min_working_hours_minutes' => 'integer',
        'overtime_after_minutes' => 'integer',
        'weekly_off_days' => 'array',
        'alternate_saturday_offs' => 'array',
    ];
  

    // ── Relationships ──

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Accessors ──

    public function getFormattedTimingAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('h:i A');
        $end = Carbon::parse($this->end_time)->format('h:i A');

        return "{$start} - {$end}";
    }

    public function getMinWorkingHoursAttribute(): float
    {
        return round($this->min_working_hours_minutes / 60, 1);
    }
}
