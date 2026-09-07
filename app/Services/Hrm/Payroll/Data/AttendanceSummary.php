<?php

namespace App\Services\Hrm\Payroll\Data;

/**
 * Immutable attendance figures for one employee over one payroll period.
 *
 * This is a plain value object: it holds no Eloquent models and performs no
 * queries, so PayrollCalculator can be exercised in tests without a database.
 * AttendanceSummariser is responsible for building it from attendance rows.
 */
final class AttendanceSummary
{
    public function __construct(
        public readonly int $workingDays,
        public readonly float $presentDays,
        public readonly float $paidLeaveDays,
        public readonly float $absentDays,
        public readonly float $leaveDays,
        public readonly float $overtimeHours,
        public readonly bool $attendanceTracked,
    ) {}

    /**
     * A period with nothing to pro-rate, used by PayrollCalculator::preview().
     *
     * workingDays is zero, which proRataFactor() reads as "no roster to
     * measure against" and answers 1.0 — the same fallback the engine already
     * applies to a company that has not started using attendance. Preview
     * therefore runs the identical code path as a real payroll run rather than
     * a parallel one.
     */
    public static function fullMonth(): self
    {
        return new self(
            workingDays: 0,
            presentDays: 0.0,
            paidLeaveDays: 0.0,
            absentDays: 0.0,
            leaveDays: 0.0,
            overtimeHours: 0.0,
            attendanceTracked: false,
        );
    }

    /**
     * One paid day, used to preview a daily rate. A daily structure has no
     * meaningful full-period total — that depends on days actually worked — so
     * the preview shows what one day pays rather than inventing a month.
     */
    public static function singleDay(): self
    {
        return new self(
            workingDays: 1,
            presentDays: 1.0,
            paidLeaveDays: 0.0,
            absentDays: 0.0,
            leaveDays: 0.0,
            overtimeHours: 0.0,
            attendanceTracked: false,
        );
    }

    /**
     * Days the employee is paid for: days actually worked plus approved leave
     * whose type is marked paid. Unpaid leave is deliberately excluded so it
     * reduces pay.
     */
    public function paidDays(): float
    {
        return $this->presentDays + $this->paidLeaveDays;
    }

    /**
     * Capped at 1 — days worked beyond the roster are overtime, never a salary
     * multiplier. Zero working days means there is no roster to measure
     * against, so pay is not reduced.
     */
    public function proRataFactor(): float
    {
        if ($this->workingDays <= 0) {
            return 1.0;
        }

        return min(1.0, $this->paidDays() / $this->workingDays);
    }
}