<?php

namespace App\Enums\Project;

/**
 * ALWAYS derived from settled amounts versus total. Never set by hand.
 *
 * Business rule 3: the moment a human can set this directly, the system holds
 * two versions of the truth and both eventually become wrong.
 */
enum ChargeStatus: string
{
    case Pending       = 'pending';
    case PartiallyPaid = 'partially_paid';
    case Paid          = 'paid';
    case WrittenOff    = 'written_off';
    case Cancelled     = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending       => 'Pending',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid          => 'Paid',
            self::WrittenOff    => 'Written Off',
            self::Cancelled     => 'Cancelled',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending       => 'circle-dashed',
            self::PartiallyPaid => 'circle-dot',
            self::Paid          => 'check-circle-2',
            self::WrittenOff    => 'eraser',
            self::Cancelled     => 'x-circle',
        };
    }

    public function color(): array
    {
        return match ($this) {
            self::Pending       => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#dc2626'],
            self::PartiallyPaid => ['bg' => '#fef3c7', 'text' => '#b45309', 'dot' => '#d97706'],
            self::Paid          => ['bg' => '#f0fdf4', 'text' => '#15803d', 'dot' => '#16a34a'],
            self::WrittenOff    => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#64748b'],
            self::Cancelled     => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#94a3b8'],
        };
    }

    /** Statuses that still contribute to a client's outstanding balance. */
    public static function outstandingValues(): array
    {
        return [self::Pending->value, self::PartiallyPaid->value];
    }

    /**
     * Statuses whose amounts still count toward a project's money totals.
     *
     * A cancelled charge is money that was never owed. Its row is kept for the
     * audit trail, but it must not appear in any charged / paid / written off /
     * outstanding figure anywhere in the module.
     */
    public static function countableValues(): array
    {
        return [
            self::Pending->value,
            self::PartiallyPaid->value,
            self::Paid->value,
            self::WrittenOff->value,
        ];
    }

    /** Statuses closed to further allocation. */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::WrittenOff, self::Cancelled], true);
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases()
        );
    }
}