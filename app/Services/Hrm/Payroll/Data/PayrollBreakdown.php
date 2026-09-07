<?php

namespace App\Services\Hrm\Payroll\Data;

use App\Models\Hrm\SalaryComponent;

/**
 * The complete result of a payroll calculation.
 *
 * Deliberately says nothing about whether the result may be paid. A negative
 * net is computed and reported here; refusing to persist it is a policy
 * decision that belongs to PayrollService, so that preview can surface the
 * problem to HR instead of throwing at them.
 */
final class PayrollBreakdown
{
    public function __construct(
        /** @var array<int, ComponentLine> */
        public readonly array $lines,
        public readonly float $grossEarnings,
        public readonly float $totalDeductions,
        public readonly float $netSalary,
        public readonly float $roundOff,
        public readonly string $wageType,
        /**
         * What each full-period earning was multiplied by. Its meaning depends
         * on the wage type — a fraction of the roster for monthly pay, a count
         * of days for daily pay — which is why the wage type travels with it.
         */
        public readonly float $multiplier,
    ) {}

    /** @return array<int, ComponentLine> */
    public function earnings(): array
    {
        return array_values(array_filter(
            $this->lines,
            fn (ComponentLine $line) => $line->type === SalaryComponent::TYPE_EARNING
        ));
    }

    /** @return array<int, ComponentLine> */
    public function deductions(): array
    {
        return array_values(array_filter(
            $this->lines,
            fn (ComponentLine $line) => $line->type === SalaryComponent::TYPE_DEDUCTION
        ));
    }

    public function isPayable(): bool
    {
        return $this->netSalary >= 0;
    }

    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (ComponentLine $line) => $line->toArray(), $this->lines),
            'gross_earnings' => $this->grossEarnings,
            'total_deductions' => $this->totalDeductions,
            'net_salary' => $this->netSalary,
            'round_off' => $this->roundOff,
            'wage_type' => $this->wageType,
            'multiplier' => $this->multiplier,
            'is_payable' => $this->isPayable(),
        ];
    }
}