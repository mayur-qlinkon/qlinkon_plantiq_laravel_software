<?php

namespace App\Services\Hrm\Payroll\Data;

use App\Models\Hrm\SalaryComponent;

/**
 * One calculated line of a payslip: the final rupee figure after percentage
 * resolution and pro-rata, plus the working that produced it.
 */
final class ComponentLine
{
    public function __construct(
        /** Null for a manual adjustment, which has no component behind it. */
        public readonly ?int $componentId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly float $amount,
        /** Human-readable working, e.g. "12% of Basic Salary". */
        public readonly ?string $detail,
        public readonly int $sortOrder,
    ) {}

    public function isEarning(): bool
    {
        return $this->type === SalaryComponent::TYPE_EARNING;
    }

    public function toArray(): array
    {
        return [
            'component_id' => $this->componentId,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'detail' => $this->detail,
        ];
    }
}