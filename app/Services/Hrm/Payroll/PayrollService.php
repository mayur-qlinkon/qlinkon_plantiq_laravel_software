<?php

namespace App\Services\Hrm\Payroll;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeSalaryStructure;
use App\Models\Hrm\SalarySlip;
use App\Models\Hrm\SalarySlipItem;
use App\Models\PaymentMethod;
use App\Services\Hrm\Payroll\Data\ManualAdjustment;
use App\Services\Hrm\Payroll\Data\PayrollBreakdown;
use App\Services\Hrm\Payroll\Data\StructureLine;
use App\Services\Hrm\Payroll\Data\WageType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates payroll: fetches inputs, hands them to PayrollCalculator, and
 * persists the result.
 *
 * Contains no arithmetic. Every rupee figure written here came out of the
 * calculator, which is what keeps the payroll run, the manual edit and the
 * structure preview agreeing with one another.
 */
class PayrollService
{
    public function __construct(
        protected PayrollCalculator $calculator,
        protected AttendanceSummariser $summariser,
    ) {}

    /**
     * Generate a salary slip for an employee for a given month/year.
     */
    public function generateSlip(Employee $employee, int $month, int $year): SalarySlip
    {
        // withTrashed matters: (employee_id, month, year) is unique at the
        // database level, and a soft-deleted row still occupies that index.
        // Querying without it found nothing, let generation proceed, and the
        // insert then failed with a raw duplicate-key error.
        $existing = SalarySlip::withTrashed()
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing && ! $existing->trashed() && $existing->status !== SalarySlip::STATUS_CANCELLED) {
            throw new InvalidArgumentException("Salary slip already exists for {$employee->employee_code} - {$month}/{$year}.");
        }

        // Payroll runs once the period has closed. Generating mid-month
        // narrowed the attendance window to the days elapsed so far, which
        // shrank the divisor as well as the numerator: an employee five days
        // into the month showed five working days and five present days, a
        // pro-rata factor of 1, and was paid the full monthly amount.
        //
        // Mid-period payouts (final settlement) are a separate flow with their
        // own rules, not a normal monthly run.
        $periodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        if ($periodEnd->isFuture()) {
            throw new InvalidArgumentException(
                'Payroll can only be generated once the month has ended. '
                .$periodEnd->format('F Y').' closes on '.$periodEnd->format('d M Y').'.'
            );
        }

        // Payroll for a period the employee was not employed in is not a
        // smaller payslip — it is not a payslip at all. Without this, a
        // profile created today could be given a slip for any past month, and
        // a leaver kept earning after their last day.
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        if ($employee->date_of_joining && $employee->date_of_joining->gt($periodEnd)) {
            throw new InvalidArgumentException(
                "{$employee->employee_code} had not joined in ".$periodStart->format('F Y')
                .' (joined '.$employee->date_of_joining->format('d M Y').').'
            );
        }

        if ($employee->date_of_leaving && $employee->date_of_leaving->lt($periodStart)) {
            throw new InvalidArgumentException(
                "{$employee->employee_code} had already left before ".$periodStart->format('F Y')
                .' (left '.$employee->date_of_leaving->format('d M Y').').'
            );
        }

        return DB::transaction(function () use ($employee, $month, $year, $existing) {
            // A cancelled or soft-deleted slip is superseded, not amended, so
            // it is removed outright to release the unique index. Its line
            // items go first — they are hard-deleted and would otherwise be
            // orphaned. This happens inside the transaction so a failure
            // further down leaves the old slip intact.
            if ($existing) {
                $existing->items()->delete();
                $existing->forceDelete();
            }

            // The summariser reads the shift's roster for every day of the
            // period, so it is loaded once here rather than lazily per day.
            $employee->loadMissing('shift');

            $attendance = $this->summariser->summarise($employee, $month, $year);

            $effectiveDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
            $structures = EmployeeSalaryStructure::where('employee_id', $employee->id)
                ->active()
                ->effectiveFor($effectiveDate)
                ->with(['salaryComponent', 'percentageOfComponent'])
                ->get();

            $lines = StructureLine::fromModels($structures);

            // Basic pay is a structure row now, not a column on the employee.
            // An empty structure is therefore a setup that HR has not finished
            // — not something payroll should invent a figure for.
            //
            // "None at all" and "none yet in force" look identical here but are
            // fixed in completely different ways, so the message distinguishes
            // them: a component added today defaults to the start of the
            // current month and is correctly absent from an earlier payroll.
            if (empty($lines)) {
                $earliest = EmployeeSalaryStructure::where('employee_id', $employee->id)
                    ->active()
                    ->min('effective_from');

                if ($earliest && Carbon::parse($earliest)->gt($effectiveDate)) {
                    throw new InvalidArgumentException(
                        "{$employee->employee_code} has a salary structure, but none of it applies to "
                        .$effectiveDate->format('F Y').' — the earliest component starts '
                        .Carbon::parse($earliest)->format('d M Y').'.'
                    );
                }

                throw new InvalidArgumentException("No salary structure defined for employee {$employee->employee_code}.");
            }

            $breakdown = $this->calculator->calculate($lines, $attendance, $this->wageTypeFor($employee));

            // A negative net is never a payable slip. Failing here rather than
            // in the calculator is deliberate: preview needs to show HR the
            // problem, while a payroll run must refuse to store it.
            if (! $breakdown->isPayable()) {
                throw new InvalidArgumentException(
                    "Deductions exceed earnings for {$employee->employee_code}. Review the salary structure before generating this slip."
                );
            }

            $slip = SalarySlip::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'month' => $month,
                'year' => $year,
                'working_days' => $attendance->workingDays,
                // The column is an unsigned smallint, so the stored value
                // rounds; the exact figure was already used for the pro-rata
                // maths, otherwise every half day was silently truncated away.
                'present_days' => (int) round($attendance->presentDays),
                'absent_days' => (int) $attendance->absentDays,
                'leave_days' => $attendance->leaveDays,
                'overtime_hours' => $attendance->overtimeHours,
                'gross_earnings' => $breakdown->grossEarnings,
                'total_deductions' => $breakdown->totalDeductions,
                'net_salary' => $breakdown->netSalary,
                'round_off' => $breakdown->roundOff,
                'status' => SalarySlip::STATUS_GENERATED,
                'generated_by' => Auth::id(),
            ]);

            $this->persistLines($slip, $breakdown);

            return $slip->load('items');
        });
    }

    /**
     * How this employee's pay is expressed.
     *
     * Read from the employee record for now. Wage type is a property of the
     * salary agreement rather than of the person — someone moved from daily to
     * monthly should leave the earlier payslips computed the old way — so this
     * moves onto the salary structure header once that exists. Keeping the
     * read in one place makes that a one-line change.
     */
    public function wageTypeFor(Employee $employee): string
    {
        return $employee->salary_type ?: WageType::MONTHLY;
    }

    /**
     * Recalculate a slip from administrator-supplied figures.
     *
     * Manual adjustments carry no component or rule, so they are passed to the
     * calculator as adjustments rather than structure lines and are totalled
     * without being calculated. Round-off is supplied explicitly here because
     * the administrator has set it; a generated slip derives it instead.
     *
     * @param  array<int, ManualAdjustment>  $adjustments
     */
    public function recalculateManual(SalarySlip $slip, array $adjustments, ?float $roundOff = null): SalarySlip
    {
        if (! $slip->isEditable()) {
            throw new InvalidArgumentException('This salary slip is locked and can no longer be edited.');
        }

        return DB::transaction(function () use ($slip, $adjustments, $roundOff) {
            $breakdown = $this->calculator->calculate(
                lines: [],
                attendance: \App\Services\Hrm\Payroll\Data\AttendanceSummary::fullMonth(),
                adjustments: $adjustments,
                // Passed through as-is. Coalescing null to 0.0 here would mean
                // the override branch always ran, so a slip saved unchanged
                // lost its derived round-off and came back with a different
                // net — the exact inconsistency this endpoint was moved onto
                // the shared engine to remove.
                roundOffOverride: $roundOff,
            );

            if (! $breakdown->isPayable()) {
                throw new InvalidArgumentException('Deductions exceed earnings. The net salary cannot be negative.');
            }

            $slip->items()->delete();
            $this->persistLines($slip, $breakdown);

            $slip->update([
                'gross_earnings' => $breakdown->grossEarnings,
                'total_deductions' => $breakdown->totalDeductions,
                'round_off' => $breakdown->roundOff,
                'net_salary' => $breakdown->netSalary,
            ]);

            return $slip->fresh(['items']);
        });
    }

    /**
     * Write the calculated lines as the slip's immutable snapshot. Names and
     * codes are copied rather than referenced so a later rename or deletion
     * cannot change what a past payslip says.
     */
    protected function persistLines(SalarySlip $slip, PayrollBreakdown $breakdown): void
    {
        foreach ($breakdown->lines as $index => $line) {
            SalarySlipItem::create([
                'salary_slip_id' => $slip->id,
                'salary_component_id' => $line->componentId,
                'component_name' => $line->name,
                'component_code' => $line->code,
                'type' => $line->type,
                'amount' => $line->amount,
                // The column is varchar(100); a long component name in a
                // percentage working can exceed it, and strict mode would
                // fail the whole payslip over a label.
                'calculation_detail' => $line->detail ? mb_substr($line->detail, 0, 100) : null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Generate slips for all active employees in a company.
     */
    public function generateBulk(int $month, int $year): array
    {
        // Bulk payroll runs only for the store currently being viewed. Without
        // this, one HR user pressing "Generate All" would create slips for
        // every branch in the company — including ones they cannot even see.
        $activeStore = active_store();

        $employees = Employee::active()
            ->when($activeStore, fn ($q) => $q->where('store_id', $activeStore->id))
            ->get();

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($employees as $employee) {
            try {
                $this->generateSlip($employee, $month, $year);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "{$employee->employee_code}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    public function approve(SalarySlip $slip): SalarySlip
    {
        if (! in_array($slip->status, [SalarySlip::STATUS_GENERATED, SalarySlip::STATUS_DRAFT])) {
            throw new InvalidArgumentException('Only generated/draft slips can be approved.');
        }

        $slip->update([
            'status' => SalarySlip::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $slip->fresh();
    }

    public function markPaid(SalarySlip $slip, array $paymentData): SalarySlip
    {
        // Idempotent: an already-paid slip is returned untouched instead of
        // throwing, so a double-click or a retried request is not an error.
        if ($slip->status === SalarySlip::STATUS_PAID) {
            return $slip;
        }

        if ($slip->status !== SalarySlip::STATUS_APPROVED) {
            throw new InvalidArgumentException('Only approved slips can be marked as paid.');
        }

        $updates = [
            'status' => SalarySlip::STATUS_PAID,
            'payment_reference' => $paymentData['payment_reference'] ?? null,
            'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
        ];

        // Resolve via PaymentMethod model and snapshot the label. The lookup is
        // tenant-scoped, so a foreign id resolves to null and must be rejected
        // here — otherwise the slip is left paid with a blank payment method.
        if (! empty($paymentData['payment_method_id'])) {
            $method = PaymentMethod::find($paymentData['payment_method_id']);

            if (! $method) {
                throw new InvalidArgumentException('Invalid payment method selected.');
            }

            $updates['payment_method_id'] = $method->id;
            $updates['payment_method_name'] = $method->label;
        }

        // Legacy fallback: if old payment_mode was provided (e.g. direct API
        // calls), keep storing it so existing slips and reports are unaffected.
        if (! empty($paymentData['payment_mode'])) {
            $updates['payment_mode'] = $paymentData['payment_mode'];
        }

        // Atomic compare-and-swap: only the request that still sees the slip as
        // approved performs the write, so two concurrent requests cannot both
        // stamp a payment reference onto the same slip.
        SalarySlip::whereKey($slip->getKey())
            ->where('status', SalarySlip::STATUS_APPROVED)
            ->update($updates);

        return $slip->fresh();
    }
}