<?php

namespace App\Enums\Project;

use Carbon\CarbonImmutable;

/**
 * Single source of truth for "is this renewable?". Anything that is not
 * OneTime renews. The legacy module stored that fact in a separate
 * is_renewable flag in two tables, they drifted, and renew buttons silently
 * stopped working. There is no such flag here by design.
 */
enum BillingCycle: string
{
    case OneTime    = 'one_time';
    case Monthly    = 'monthly';
    case Quarterly  = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly     = 'yearly';
    case Custom     = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::OneTime    => 'One Time',
            self::Monthly    => 'Monthly',
            self::Quarterly  => 'Quarterly',
            self::HalfYearly => 'Half Yearly',
            self::Yearly     => 'Yearly',
            self::Custom     => 'Custom',
        };
    }

    public function isRenewable(): bool
    {
        return $this !== self::OneTime;
    }

    /** Months added per period. Null for OneTime and Custom. */
    public function months(): ?int
    {
        return match ($this) {
            self::Monthly    => 1,
            self::Quarterly  => 3,
            self::HalfYearly => 6,
            self::Yearly     => 12,
            default          => null,
        };
    }

    /**
     * End date of a period starting on $start.
     *
     * $durationDays is only consulted for Custom. Returns null for OneTime,
     * which has no period at all.
     *
     * The end date is inclusive: a yearly period starting 01-May-2026 ends
     * 30-Apr-2027, so the next period starts cleanly on 01-May-2027 with no
     * overlapping day.
     */
    public function periodEnd(CarbonImmutable $start, ?int $durationDays = null): ?CarbonImmutable
    {
        if ($this === self::OneTime) {
            return null;
        }

        if ($this === self::Custom) {
            return $durationDays > 0
                ? $start->addDays($durationDays - 1)
                : null;
        }

        return $start->addMonths($this->months())->subDay();
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}