<?php

namespace App\Services\Hrm\Payroll\Data;

use App\Models\Hrm\EmployeeSalaryStructure;
use App\Models\Hrm\SalaryComponent;

/**
 * One component of an employee's salary structure, flattened into a plain
 * value object.
 *
 * fromModel() is the only place that touches Eloquent. PayrollCalculator
 * receives these objects and never sees a model, so no lazy load can fire
 * mid-calculation and a test can build a structure with a constructor call.
 */
final class StructureLine
{
    public function __construct(
        public readonly int $componentId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly string $role,
        public readonly string $calculationType,
        /** Rupees when calculationType is fixed, a percentage when not. */
        public readonly float $amount,
        public readonly ?int $percentageOfComponentId,
        public readonly bool $appearsOnPayslip,
        public readonly int $sortOrder,
    ) {}

    /**
     * Requires salaryComponent to be loaded. A structure row whose component
     * is missing or inactive is not a line at all and is filtered out by
     * fromModels() rather than represented as a zero.
     */
    public static function fromModel(EmployeeSalaryStructure $structure): self
    {
        $component = $structure->salaryComponent;

        return new self(
            componentId: $component->id,
            name: $component->name,
            code: $component->code,
            type: $component->type,
            role: $component->role ?? SalaryComponent::ROLE_OTHER,
            calculationType: $structure->calculation_type,
            amount: (float) $structure->amount,
            percentageOfComponentId: $structure->percentage_of_component_id,
            appearsOnPayslip: (bool) $component->appears_on_payslip,
            sortOrder: (int) ($component->sort_order ?? 0),
        );
    }

    /**
     * @param  iterable<EmployeeSalaryStructure>  $structures
     * @return array<int, self>
     */
    public static function fromModels(iterable $structures): array
    {
        $lines = [];

        foreach ($structures as $structure) {
            $component = $structure->salaryComponent;

            if (! $component || ! $component->is_active) {
                continue;
            }

            $lines[] = self::fromModel($structure);
        }

        return $lines;
    }

    public function isEarning(): bool
    {
        return $this->type === SalaryComponent::TYPE_EARNING;
    }

    public function isPercentage(): bool
    {
        return $this->calculationType === SalaryComponent::CALC_PERCENTAGE;
    }
}