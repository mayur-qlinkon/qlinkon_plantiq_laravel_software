<?php

namespace App\Exceptions\Project;

use RuntimeException;

/**
 * Domain-level billing failure — a rule was violated, not a system fault.
 *
 * Controllers can catch this and surface getMessage() straight to the user,
 * because every message thrown here is written to be read by a shopkeeper.
 */
class ProjectBillingException extends RuntimeException
{
    public static function invalidAmount(): self
    {
        return new self('Allocation amount must be greater than zero.');
    }

    public static function exceedsPayment(float $requested, float $available): self
    {
        return new self(sprintf(
            'Cannot allocate %s. Only %s of this payment is still unallocated.',
            number_format($requested, 2),
            number_format($available, 2)
        ));
    }

    public static function exceedsCharge(float $requested, float $balance): self
    {
        return new self(sprintf(
            'Cannot allocate %s. This charge only has a balance of %s.',
            number_format($requested, 2),
            number_format($balance, 2)
        ));
    }

    public static function chargeNotOpen(string $status): self
    {
        return new self("This charge is {$status} and cannot receive further allocation.");
    }

    public static function clientMismatch(): self
    {
        return new self('The payment and the charge belong to different clients.');
    }

    public static function companyMismatch(): self
    {
        return new self('The payment and the charge belong to different companies.');
    }

    public static function alreadyReversed(): self
    {
        return new self('This allocation has already been reversed.');
    }

    public static function missingReason(): self
    {
        return new self('A reason is required for this action.');
    }
}