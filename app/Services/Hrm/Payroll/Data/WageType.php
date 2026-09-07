<?php

namespace App\Services\Hrm\Payroll\Data;

/**
 * How an employee's pay is expressed, which decides the arithmetic the engine
 * uses — not a label on top of one shared monthly calculation.
 *
 * Monthly and daily are different formulas, not different pro-rata policies:
 * a monthly figure is divided by the roster and multiplied by the days worked,
 * while a daily rate is simply multiplied by them. Folding daily into the
 * monthly pro-rata path would pay a ₹500-a-day worker ₹385 for twenty days.
 */
final class WageType
{
    const MONTHLY = 'monthly';

    const DAILY = 'daily';

    /**
     * Recognised but not payable. Hourly pay needs the hours actually worked,
     * and attendance records check-in and check-out times without ever
     * totalling them. Blocking is deliberate: treating an hourly employee as
     * daily or monthly would pay a plausible but invented figure.
     */
    const HOURLY = 'hourly';

    const SUPPORTED = [self::MONTHLY, self::DAILY];

    const ALL = [self::MONTHLY, self::DAILY, self::HOURLY];
}