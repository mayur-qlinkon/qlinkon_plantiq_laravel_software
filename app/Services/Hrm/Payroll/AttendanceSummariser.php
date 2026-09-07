<?php

namespace App\Services\Hrm\Payroll;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Leave;
use App\Services\Hrm\Payroll\Data\AttendanceSummary;
use Illuminate\Support\Carbon;

/**
 * Turns attendance and leave rows into the figures payroll needs.
 *
 * Split out of SalaryService because it is entirely database work and shares
 * nothing with the arithmetic in PayrollCalculator. Keeping it separate also
 * gives Phase 2's LOP divisor policy somewhere to live that is not the
 * calculator.
 */
class AttendanceSummariser
{
    /**
     * An Employee is taken rather than an id so the company is known from the
     * argument itself. The tenant scope is registered only for authenticated
     * requests, so on a console or scheduled run an id alone carried no
     * company at all — and under a web request an id from another company
     * simply returned nothing, which the calendar fallback then read as
     * "attendance not in use" and paid a full month.
     */
    public function summarise(Employee $employee, int $month, int $year): AttendanceSummary
    {
        $employeeId = $employee->id;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $today = now();

        // If the month hasn't ended yet, count up to today.
        if ($endDate->gt($today)) {
            $endDate = $today;
        }

        // Someone who joined or left mid-month is only owed the part of the
        // month they were employed for. Counting the whole month paid a joiner
        // for the weeks before their first day.
        if ($employee->date_of_joining && $employee->date_of_joining->gt($startDate)) {
            $startDate = $employee->date_of_joining->copy()->startOfDay();
        }

        if ($employee->date_of_leaving && $employee->date_of_leaving->lt($endDate)) {
            $endDate = $employee->date_of_leaving->copy()->endOfDay();
        }

        $attendances = Attendance::where('employee_id', $employeeId)
            ->forDateRange($startDate->toDateString(), $endDate->toDateString())
            ->get();

        // Statuses that mean the employee actually turned up, and so is owed
        // the day. Each of these is a record-keeping gap, not an absence:
        //
        //  - PENDING: worked on a company holiday under the 'approval' policy.
        //    They were there; only HR's sign-off is outstanding.
        //  - MISSING_CHECKOUT: checked in, never checked out. handleMissingCheckouts()
        //    deliberately records this rather than inventing a checkout time.
        //  - EARLY_LEAVE / LATE: a timekeeping matter for the shift rules,
        //    not a reason to withhold a day's pay.
        $workedStatuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_EARLY_LEAVE,
            Attendance::STATUS_LATE_AND_EARLY,
            Attendance::STATUS_MISSING_CHECKOUT,
            Attendance::STATUS_PENDING,
        ];

        // Days the company never expected work on.
        $nonWorkingStatuses = [
            Attendance::STATUS_WEEK_OFF,
            Attendance::STATUS_HOLIDAY,
        ];

        $hasAttendanceData = $attendances->isNotEmpty();

        // How many days were owed is a question about the roster, not about
        // how many attendance rows happen to exist. Counting rows meant
        // deleting a fortnight of attendance shortened the month instead of
        // marking the employee absent — pay went up when data went missing.
        $rosterWorkingDays = $this->countRosterWorkingDays($employee, $startDate, $endDate, $attendances);

        if ($hasAttendanceData) {
            // attendance:mark-absent writes one row per employee per day, so
            // the table already knows which days were week offs and holidays.
            // Deriving working days from it keeps payroll in step with the
            // roster, instead of re-deciding with isWeekend() — which disagreed
            // with that command about Saturdays and inflated the pro-rata
            // factor past 1 for any six-day working week.
            $workingDays = $rosterWorkingDays;

            $presentDays = (float) $attendances->whereIn('status', $workedStatuses)->count();
            $presentDays += $attendances->where('status', Attendance::STATUS_HALF_DAY)->count() * 0.5;

            $leaveDays = (float) $attendances->where('status', Attendance::STATUS_ON_LEAVE)->count();

            // Absence is what the roster expected minus what was accounted
            // for. Reading only rows marked 'absent' meant a working day with
            // no row at all cost the employee nothing and the company a full
            // day's pay.
            $absentDays = max(0.0, $rosterWorkingDays - $presentDays - $leaveDays);

            $paidLeaveDays = min($this->countPaidLeaveDays($employeeId, $startDate, $endDate), $leaveDays);
        } elseif ($this->companyTracksAttendance($employee, $startDate, $endDate)) {
            // Colleagues have attendance for this period but this employee has
            // none. That is missing data about one person, not a company that
            // has yet to adopt attendance — so it is counted as absence rather
            // than presence.
            //
            // Treating it as full presence is what let a profile created today
            // draw a complete month's pay for a month it never worked.
            $workingDays = $rosterWorkingDays;
            $presentDays = 0.0;
            $leaveDays = 0.0;
            $absentDays = (float) $workingDays;
            $paidLeaveDays = 0.0;
        } else {
            // No one in the company has attendance for this period, so the
            // module is not in use. Everyone is treated as present, because the
            // alternative is paying an entire company nothing.
            $workingDays = $rosterWorkingDays;
            $presentDays = (float) $workingDays;
            $leaveDays = 0.0;
            $absentDays = 0.0;
            $paidLeaveDays = 0.0;
        }

        return new AttendanceSummary(
            workingDays: $workingDays,
            presentDays: $presentDays,
            paidLeaveDays: $paidLeaveDays,
            absentDays: $absentDays,
            leaveDays: $leaveDays,
            overtimeHours: round($attendances->sum('overtime_hours'), 2),
            attendanceTracked: $hasAttendanceData,
        );
    }

    /**
     * Working days from the calendar, used only when no attendance exists yet.
     * Weekends and company holidays are excluded.
     */
    /**
     * Whether anyone in this company has attendance for the period.
     *
     * This is what separates "the company does not use attendance" from "this
     * one employee has no rows". The two need opposite answers, and the old
     * code could not tell them apart because it only ever looked at the one
     * employee.
     */
    protected function companyTracksAttendance(Employee $employee, Carbon $startDate, Carbon $endDate): bool
    {
        return Attendance::withoutGlobalScope('tenant')
            ->where('company_id', $employee->company_id)
            ->forDateRange($startDate->toDateString(), $endDate->toDateString())
            ->exists();
    }

    /**
     * Days the employee was rostered to work in this window.
     *
     * Walks the calendar rather than the attendance table, so a missing row is
     * a day the employee owed and did not account for — not a day that never
     * existed. Where an attendance row does say week_off or holiday it wins,
     * because that reflects the roster actually applied on the day.
     *
     * @param  \Illuminate\Support\Collection  $attendances
     */
    protected function countRosterWorkingDays(Employee $employee, Carbon $startDate, Carbon $endDate, $attendances): int
    {
        $nonWorkingByDate = $attendances
            ->whereIn('status', [Attendance::STATUS_WEEK_OFF, Attendance::STATUS_HOLIDAY])
            ->keyBy(fn ($a) => $a->date->toDateString());

        $holidayDates = $this->holidayDates($employee->company_id, $startDate, $endDate);

        $count = 0;
        $cursor = $startDate->copy()->startOfDay();
        $last = $endDate->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $date = $cursor->toDateString();

            $isOff = $nonWorkingByDate->has($date)
                || $this->isWeeklyOff($employee, $cursor)
                || isset($holidayDates[$date]);

            if (! $isOff) {
                $count++;
            }

            $cursor->addDay();
        }

        return $count;
    }

    /**
     * An employee with no shift assigned falls back to the company-wide
     * default, so payroll never silently treats every day as workable.
     */
    protected function isWeeklyOff(Employee $employee, Carbon $date): bool
    {
        $shift = $employee->shift;

        if (! $shift) {
            return in_array($date->dayOfWeek, Attendance::WEEKLY_OFF_DAYS, true);
        }

        return $shift->isWeeklyOff($date);
    }

    protected function countCalendarWorkingDays(Employee $employee, Carbon $startDate, Carbon $endDate): int
    {
        // Read off the model instead of a lookup. The old query went through
        // the tenant scope, so on a console run it resolved to null and every
        // company holiday was silently counted as a working day.
        $holidayDates = $this->holidayDates($employee->company_id, $startDate, $endDate);

        $workingDays = 0;
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            if (! $this->isWeeklyOff($employee, $cursor) && ! isset($holidayDates[$cursor->toDateString()])) {
                $workingDays++;
            }
            $cursor->addDay();
        }

        return $workingDays;
    }

    /**
     * Company holiday dates in the window, expanded across multi-day holidays.
     *
     * @return array<string, true>
     */
    protected function holidayDates(?int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $holidayDates = [];

        if ($companyId) {
            // Tenantable does not register its scope on every path, so the
            // company condition is stated explicitly here.
            $holidays = Holiday::withoutGlobalScope('tenant')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->where('date', '<=', $endDate->toDateString())
                ->where(function ($q) use ($startDate) {
                    $q->where('date', '>=', $startDate->toDateString())
                        ->orWhere('end_date', '>=', $startDate->toDateString());
                })
                ->get();

            // A holiday may span several days via end_date, so expand each one.
            foreach ($holidays as $holiday) {
                $cursor = $holiday->date->copy();
                $last = $holiday->end_date ? $holiday->end_date->copy() : $holiday->date->copy();

                while ($cursor->lte($last)) {
                    $holidayDates[$cursor->toDateString()] = true;
                    $cursor->addDay();
                }
            }
        }

        return $holidayDates;
    }

    /**
     * Approved leave in this period that its leave type marks as paid.
     * leave_types.is_paid existed but payroll never read it, so paid leave
     * was being docked exactly like unpaid leave.
     */
    protected function countPaidLeaveDays(int $employeeId, Carbon $startDate, Carbon $endDate): float
    {
        $leaves = Leave::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('status', Leave::STATUS_APPROVED)
            ->where('from_date', '<=', $endDate->toDateString())
            ->where('to_date', '>=', $startDate->toDateString())
            ->get();

        $paidDates = [];

        foreach ($leaves as $leave) {
            if (! $leave->leaveType?->is_paid) {
                continue;
            }

            $cursor = $leave->from_date->copy()->max($startDate);
            $last = $leave->to_date->copy()->min($endDate);

            while ($cursor->lte($last)) {
                $paidDates[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        return (float) count($paidDates);
    }
}