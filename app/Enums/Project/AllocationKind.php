<?php

namespace App\Enums\Project;

/**
 * How a charge got settled. Only Payment and WriteOff are used today; Tds and
 * Credit are reserved so a future tenant needing TDS (194J/194C) does not
 * require a migration on a live financial table.
 */
enum AllocationKind: string
{
    case Payment  = 'payment';
    case Tds      = 'tds';
    case WriteOff = 'write_off';

    public function label(): string
    {
        return match ($this) {
            self::Payment  => 'Payment',
            self::Tds      => 'TDS Deducted',
            self::WriteOff => 'Write Off (Kasar)',
        };
    }

    /**
     * Kinds that require a linked payments row. TDS and write-offs settle a
     * charge without any money arriving, so payment_id stays null for them.
     */
    public function requiresPayment(): bool
    {
        return $this === self::Payment;
    }

    /**
     * Whether this kind counts towards paid_amount. Write-offs accumulate into
     * written_off_amount instead, so the two are never confused in reporting —
     * money actually collected must stay distinguishable from money forgiven.
     */
    public function countsAsPaid(): bool
    {
        return $this !== self::WriteOff;
    }

    /** Kinds available in the UI right now. */
    public static function activeCases(): array
    {
        return [self::Payment, self::WriteOff];
    }
}