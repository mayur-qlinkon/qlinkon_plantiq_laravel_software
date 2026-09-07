<?php

namespace App\Enums;

/**
 * How alive a tenant's account looks, from days since their last action.
 *
 * The thresholds are a sales instrument, not a technical one: they exist to
 * sort a list into "leave alone", "keep an eye on" and "ring today".
 */
enum UsageHealth: string
{
    case Healthy      = 'healthy';
    case Normal       = 'normal';
    case Slipping     = 'slipping';
    case Dormant      = 'dormant';
    case NeverStarted = 'never_started';

    /**
     * Classify from days since last activity.
     *
     * Null means nothing was ever recorded — a company that was onboarded and
     * then never touched. Kept separate from Dormant because the two need
     * different conversations: one is a lapsed customer, the other never
     * became one.
     */
    public static function fromIdleDays(?int $days): self
    {
        return match (true) {
            $days === null => self::NeverStarted,
            $days <= 2     => self::Healthy,
            $days <= 7     => self::Normal,
            $days <= 21    => self::Slipping,
            default        => self::Dormant,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy      => 'Healthy',
            self::Normal       => 'Normal',
            self::Slipping     => 'Slipping',
            self::Dormant      => 'Dormant',
            self::NeverStarted => 'Never started',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Healthy      => 'Used within the last 2 days',
            self::Normal       => 'Used within the last week',
            self::Slipping     => 'Quiet for over a week — worth a check-in',
            self::Dormant      => 'Quiet for over three weeks — call them',
            self::NeverStarted => 'Onboarded but never used',
        };
    }

    /** Tailwind classes for the badge, so the blade stays free of colour logic. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Healthy      => 'bg-green-50 text-green-700 border-green-200',
            self::Normal       => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Slipping     => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Dormant      => 'bg-red-50 text-red-700 border-red-200',
            self::NeverStarted => 'bg-gray-100 text-gray-500 border-gray-200',
        };
    }

    /** Font Awesome class — the platform panel uses FA, not Lucide. */
    public function icon(): string
    {
        return match ($this) {
            self::Healthy      => 'fa-arrow-trend-up',
            self::Normal       => 'fa-wave-square',
            self::Slipping     => 'fa-arrow-trend-down',
            self::Dormant      => 'fa-phone-volume',
            self::NeverStarted => 'fa-ban',
        };
    }

    /** True when this tenant deserves a call. Drives the platform list filter. */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Slipping, self::Dormant, self::NeverStarted], true);
    }
}