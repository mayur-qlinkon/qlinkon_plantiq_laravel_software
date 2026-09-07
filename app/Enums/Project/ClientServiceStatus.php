<?php

namespace App\Enums\Project;

/**
 * Lifecycle of an ENTITLEMENT — is this client's hosting currently valid?
 * Says nothing about whether it has been paid for.
 */
enum ClientServiceStatus: string
{
    case Active    = 'active';
    case Expired   = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Active',
            self::Expired   => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active    => 'shield-check',
            self::Expired   => 'clock-alert',
            self::Cancelled => 'x-circle',
        };
    }

    public function color(): array
    {
        return match ($this) {
            self::Active    => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#16a34a'],
            self::Expired   => ['bg' => '#fff7ed', 'text' => '#c2410c', 'dot' => '#ea580c'],
            self::Cancelled => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#dc2626'],
        };
    }

    /**
     * Can this service be renewed into a new period?
     *
     * Expired counts: a client returning after a lapse is renewed forward, not
     * recreated from scratch.
     */
    public function isRenewable(): bool
    {
        return in_array($this, [self::Active, self::Expired], true);
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}