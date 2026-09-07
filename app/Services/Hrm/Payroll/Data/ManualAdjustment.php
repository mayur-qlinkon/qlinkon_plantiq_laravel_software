<?php

namespace App\Services\Hrm\Payroll\Data;

/**
 * A figure an administrator typed directly onto a slip, with no component or
 * calculation rule behind it.
 *
 * The calculator adds these to the totals but never calculates them: there is
 * no base to take a percentage of and no roster to pro-rate against, so
 * putting them through StructureLine would mean carrying fields that are
 * always empty.
 */
final class ManualAdjustment
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly float $amount,
        /**
         * Set when the administrator edited a line that came from a salary
         * component. The link is kept so an edited slip still reports which
         * component the figure belongs to; only the amount is manual.
         */
        public readonly ?int $componentId = null,
        public readonly ?string $detail = null,
    ) {}
}