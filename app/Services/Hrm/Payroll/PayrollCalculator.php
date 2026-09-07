<?php

namespace App\Services\Hrm\Payroll;

use App\Models\Hrm\SalaryComponent;
use App\Services\Hrm\Payroll\Data\AttendanceSummary;
use App\Services\Hrm\Payroll\Data\ComponentLine;
use App\Services\Hrm\Payroll\Data\ManualAdjustment;
use App\Services\Hrm\Payroll\Data\PayrollBreakdown;
use App\Services\Hrm\Payroll\Data\StructureLine;
use App\Services\Hrm\Payroll\Data\WageType;
use InvalidArgumentException;

/**
 * The single source of payroll arithmetic.
 *
 * Pure and stateless: no database, no Auth, no facades, no Eloquent. Every
 * input arrives as a value object and the result is returned rather than
 * written, which is what allows the frontend preview and the actual payroll
 * run to share one implementation instead of the three that existed before —
 * SalaryService::generateSlip(), SalarySlipController::update() and the
 * estimatedNet() formula in Blade, each of which answered differently.
 */
class PayrollCalculator
{
    /**
     * @param  array<int, StructureLine>  $lines
     * @param  array<int, ManualAdjustment>  $adjustments
     * @param  float|null  $roundOffOverride  Round-off is derived by default:
     *         net is rounded to the rupee and the difference recorded. Pass a
     *         value only where an administrator has set one explicitly, in
     *         which case it is added to the unrounded net instead.
     */
    public function calculate(
        array $lines,
        AttendanceSummary $attendance,
        string $wageType = WageType::MONTHLY,
        array $adjustments = [],
        ?float $roundOffOverride = null,
    ): PayrollBreakdown {
        $multiplier = $this->multiplierFor($wageType, $attendance);

        $resolved = $this->resolveAmounts($lines);
        $components = $this->buildComponentLines($lines, $resolved, $attendance, $wageType, $multiplier);

        foreach ($adjustments as $adjustment) {
            $components[] = new ComponentLine(
                componentId: $adjustment->componentId,
                name: $adjustment->name,
                code: $adjustment->code,
                type: $adjustment->type,
                amount: round($adjustment->amount, 2),
                detail: $adjustment->detail ?? 'Manually added',
                sortOrder: PHP_INT_MAX,
            );
        }

        return $this->summarise($components, $wageType, $multiplier, $roundOffOverride);
    }

    /**
     * What a full-period earning is multiplied by, per wage type.
     *
     * Each branch is written out rather than expressed as a variation on the
     * monthly one, so adding hourly later means adding an arm here instead of
     * bending a pro-rata factor into meaning something else.
     */
    protected function multiplierFor(string $wageType, AttendanceSummary $attendance): float
    {
        return match ($wageType) {
            // A monthly figure covers the whole roster, so it is scaled by the
            // share of it the employee was present for.
            WageType::MONTHLY => $attendance->proRataFactor(),

            // A daily rate is already one day's pay. It is multiplied by the
            // days worked and the roster plays no part — twenty days at ₹500
            // is ₹10,000 whether the month held 26 working days or 22.
            WageType::DAILY => $attendance->paidDays(),

            WageType::HOURLY => throw new InvalidArgumentException(
                'Hourly payroll is not available yet: attendance records check-in and check-out times but does not total hours worked.'
            ),

            default => throw new InvalidArgumentException("Unknown wage type \"{$wageType}\"."),
        };
    }

    /**
     * The structure as it would be paid over a full period, with nothing
     * pro-rated away. Used by the salary structure screen so the figure HR
     * sees is produced by this engine rather than a formula in JavaScript.
     *
     * There is no separate arithmetic here — only a different attendance
     * input handed to calculate().
     *
     * @param  array<int, StructureLine>  $lines
     */
    public function preview(array $lines, string $wageType = WageType::MONTHLY): PayrollBreakdown
    {
        // fullMonth() has no roster, so the monthly multiplier is 1. A daily
        // rate has nothing to preview against — a day count would have to be
        // invented — so it is previewed as one day's pay.
        $attendance = $wageType === WageType::DAILY
            ? AttendanceSummary::singleDay()
            : AttendanceSummary::fullMonth();

        return $this->calculate($lines, $attendance, $wageType);
    }

    /**
     * Resolve every line to its full (pre-pro-rata) rupee value, keyed by
     * component id.
     *
     * Keying by id rather than by code is what removes the duplicate Basic
     * line: the old engine matched on the literal string 'BASIC' and, failing
     * to find it, synthesised a second Basic row from employees.basic_salary
     * for any company that coded its basic differently.
     *
     * @param  array<int, StructureLine>  $lines
     * @return array<int, float>
     */
    protected function resolveAmounts(array $lines): array
    {
        $resolved = [];
        $pending = [];

        // Fixed amounts first — they are the only components that can be
        // resolved without reference to another.
        foreach ($lines as $line) {
            if ($line->isPercentage()) {
                $pending[] = $line;
            } else {
                $resolved[$line->componentId] = $line->amount;
            }
        }

        // Then repeat until a pass resolves nothing further.
        //
        // A single pass appeared to handle a chain — DA on Basic, HRA on DA —
        // but only because it wrote into $resolved as it went, so the answer
        // depended on the order the rows came back from the database. The same
        // structure could calculate correctly one day and fail the next after
        // an unrelated row was added.
        while ($pending) {
            $progressed = false;
            $stillPending = [];

            foreach ($pending as $line) {
                $baseId = $line->percentageOfComponentId;

                if ($baseId && array_key_exists($baseId, $resolved)) {
                    $resolved[$line->componentId] = round($resolved[$baseId] * $line->amount / 100, 2);
                    $progressed = true;
                } else {
                    $stillPending[] = $line;
                }
            }

            // Nothing moved, so what is left either points at a component that
            // is not here or forms a loop among themselves. Both are structures
            // that cannot be calculated, and neither should reach a payslip.
            if (! $progressed) {
                $names = implode(', ', array_map(fn (StructureLine $l) => "\"{$l->name}\"", $stillPending));

                throw new InvalidArgumentException(
                    "Cannot calculate {$names}: the component each is a percentage of is missing from "
                    .'this salary structure, or these components depend on one another in a loop.'
                );
            }

            $pending = $stillPending;
        }

        return $resolved;
    }

    /**
     * @param  array<int, StructureLine>  $lines
     * @param  array<int, float>  $resolved
     * @return array<int, ComponentLine>
     */
    protected function buildComponentLines(array $lines, array $resolved, AttendanceSummary $attendance, string $wageType, float $multiplier): array
    {
        $names = [];
        foreach ($lines as $line) {
            $names[$line->componentId] = $line->name;
        }

        $components = [];

        foreach ($lines as $line) {
            // Components hidden from the payslip still resolve above, because
            // a percentage component may legitimately be based on one.
            if (! $line->appearsOnPayslip) {
                continue;
            }

            $amount = $resolved[$line->componentId] ?? 0.0;

            // Pro-rata applies to every earning, and to percentage deductions
            // whose base is an earning that has itself been pro-rated — PF at
            // 12% of Basic must follow the basic actually paid. Fixed
            // deductions (loan EMI, professional tax, insurance premium) are
            // flat monthly obligations and stay whole.
            if ($line->isEarning() || $line->isPercentage()) {
                $amount = round($amount * $multiplier, 2);
            }

            // The working, written so the figure on the payslip can be checked
            // without asking anyone. A pro-rated line that showed only its
            // final amount looked like the wrong salary had been paid.
            $parts = [];

            if ($line->isPercentage()) {
                $baseName = $names[$line->percentageOfComponentId] ?? 'base';
                $parts[] = "{$line->amount}% of {$baseName}";
            }

            $full = $resolved[$line->componentId] ?? 0.0;

            if ($amount !== $full) {
                $paid = rtrim(rtrim(number_format($attendance->paidDays(), 1, '.', ''), '0'), '.');

                if ($wageType === WageType::DAILY) {
                    $parts[] = '₹'.number_format($full, 2)." × {$paid} days";
                } elseif ($attendance->workingDays > 0) {
                    $parts[] = '₹'.number_format($full, 2)." × {$paid}/{$attendance->workingDays} days";
                }
            }

            $detail = $parts ? implode(' · ', $parts) : null;

            $components[] = new ComponentLine(
                componentId: $line->componentId,
                name: $line->name,
                code: $line->code,
                type: $line->type,
                amount: $amount,
                detail: $detail,
                sortOrder: $line->sortOrder,
            );
        }

        // Payslip order follows the component's configured sort order. The
        // previous engine used the order rows came back from the database,
        // so the same structure could print differently between runs.
        usort($components, fn (ComponentLine $a, ComponentLine $b) => $a->sortOrder <=> $b->sortOrder);

        return $components;
    }

    /**
     * @param  array<int, ComponentLine>  $components
     */
    protected function summarise(array $components, string $wageType, float $multiplier, ?float $roundOffOverride): PayrollBreakdown
    {
        $gross = 0.0;
        $deductions = 0.0;

        foreach ($components as $component) {
            if ($component->type === SalaryComponent::TYPE_EARNING) {
                $gross += $component->amount;
            } else {
                $deductions += $component->amount;
            }
        }

        $gross = round($gross, 2);
        $deductions = round($deductions, 2);
        $raw = $gross - $deductions;

        if ($roundOffOverride !== null) {
            $roundOff = round($roundOffOverride, 2);
            $net = round($raw + $roundOff, 2);
        } else {
            $net = round($raw);
            $roundOff = round($net - $raw, 2);
        }

        return new PayrollBreakdown(
            lines: $components,
            grossEarnings: $gross,
            totalDeductions: $deductions,
            netSalary: $net,
            roundOff: $roundOff,
            wageType: $wageType,
            multiplier: $multiplier,
        );
    }
}