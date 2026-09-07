<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Leave;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkAbsentEmployeesCommand extends Command
{
    protected $signature = 'attendance:mark-absent
                            {--date= : Date to process (Y-m-d). Default: yesterday}
                            {--dry-run : Preview without saving}';

    protected $description = 'Mark absent for employees who never checked in on a working day';



    public function handle(): int
    {
        // ── 1. Determine which date to process ──
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $dryRun = $this->option('dry-run');

        $this->info("Processing attendance for: {$targetDate->toDateString()}" . ($dryRun ? ' [DRY RUN]' : ''));

        $totalMarked = 0;
        $totalSkipped = 0;

        // ── 2. Loop each active company ──
        // NOTE: Cron runs without Auth, so Tenantable global scope is NOT applied.
        // We must manually scope every query by company_id.
        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            [$marked, $skipped] = $this->processCompany($company, $targetDate, $dryRun);
            $totalMarked  += $marked;
            $totalSkipped += $skipped;
        }

        $this->info("Done. Absent marked: {$totalMarked} | Skipped: {$totalSkipped}");

        return self::SUCCESS;
    }

    private function processCompany(Company $company, Carbon $date, bool $dryRun): array
    {
        $marked  = 0;
        $skipped = 0;

        // ── 3. Get all active employees with shift for this company ──
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', Employee::STATUS_ACTIVE)
            ->whereNotNull('shift_id')
            ->with('shift')
            ->get();

        // ── 4. Get employee IDs that already have an attendance record for this date ──
        $existingAttendanceEmployeeIds = Attendance::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereDate('date', $date)
            ->pluck('employee_id')
            ->toArray();

        // ── 5. Get employee IDs on approved leave for this date ──
        $onLeaveEmployeeIds = Leave::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', Leave::STATUS_APPROVED)
            ->where('from_date', '<=', $date->toDateString())
            ->where('to_date', '>=', $date->toDateString())
            ->pluck('employee_id')
            ->toArray();

        // ── 6. Check if this date is a company holiday ──
        $isHoliday = Holiday::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->where(function ($q2) use ($date) {
                    // Non-recurring: exact date range
                    $q2->where('is_recurring', false)
                        ->whereDate('date', '<=', $date)
                        ->where(function ($q3) use ($date) {
                            $q3->whereDate('date', $date)
                                ->orWhereDate('end_date', '>=', $date);
                        });
                })->orWhere(function ($q2) use ($date) {
                    // Recurring: month-day match
                    $monthDay = $date->format('m-d');
                    $q2->where('is_recurring', true)
                        ->whereRaw("DATE_FORMAT(date, '%m-%d') <= ?", [$monthDay])
                        ->whereRaw("DATE_FORMAT(COALESCE(end_date, date), '%m-%d') >= ?", [$monthDay]);
                });
            })
            ->exists();

        foreach ($employees as $employee) {

            // Skip if already has attendance record
            if (in_array($employee->id, $existingAttendanceEmployeeIds)) {
                $skipped++;
                continue;
            }

            // Skip if employee joined after this date
            if ($employee->date_of_joining && Carbon::parse($employee->date_of_joining)->gt($date)) {
                $skipped++;
                continue;
            }

            // Weekly off (e.g. Sunday) → create week_off record, never absent.
            // Checked before leave/holiday: a scheduled off day isn't something
            // an employee needs leave approval for.
            // The employee's own shift decides this now. Payroll reads the same
            // roster, so a week_off row written here and the divisor used at
            // month end can no longer disagree.
            $isWeeklyOff = $employee->shift
                ? $employee->shift->isWeeklyOff($date)
                : in_array($date->dayOfWeek, Attendance::WEEKLY_OFF_DAYS, true);

            if ($isWeeklyOff) {
                if (! $dryRun) {
                    $this->createAttendanceRecord($company->id, $employee->id, $date, Attendance::STATUS_WEEK_OFF, true);
                }
                $this->line("  [WEEK_OFF] Employee #{$employee->id} — {$date->toDateString()}");
                $marked++;
                continue;
            }

            // On leave → create on_leave record instead of absent
            if (in_array($employee->id, $onLeaveEmployeeIds)) {
                if (! $dryRun) {
                    $this->createAttendanceRecord($company->id, $employee->id, $date, Attendance::STATUS_ON_LEAVE);
                }
                $this->line("  [ON_LEAVE] Employee #{$employee->id} — {$date->toDateString()}");
                $marked++;
                continue;
            }

            // Company holiday → create holiday record
            if ($isHoliday) {
                if (! $dryRun) {
                    $this->createAttendanceRecord($company->id, $employee->id, $date, Attendance::STATUS_HOLIDAY, true);
                }
                $this->line("  [HOLIDAY]  Employee #{$employee->id} — {$date->toDateString()}");
                $marked++;
                continue;
            }

            // No check-in, no leave, no holiday → ABSENT
            if (! $dryRun) {
                $this->createAttendanceRecord($company->id, $employee->id, $date, Attendance::STATUS_ABSENT);
            }
            $this->line("  [ABSENT]   Employee #{$employee->id} — {$date->toDateString()}");
            $marked++;
        }

        return [$marked, $skipped];
    }

    private function createAttendanceRecord(
        int $companyId,
        int $employeeId,
        Carbon $date,
        string $status,
        bool $isHoliday = false
    ): void {
        try {
            DB::table('attendances')->insertOrIgnore([
                'company_id'  => $companyId,
                'employee_id' => $employeeId,
                'date'        => $date->toDateString(),
                'status'      => $status,
                'is_holiday'  => $isHoliday,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[MarkAbsent] Failed to insert', [
                'employee_id' => $employeeId,
                'date'        => $date->toDateString(),
                'error'       => $e->getMessage(),
            ]);
        }
    }
}