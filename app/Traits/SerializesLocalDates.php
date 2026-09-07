<?php

namespace App\Traits;

use DateTimeInterface;

/**
 * Serialize dates in the application timezone instead of UTC.
 *
 * Laravel's default serializeDate() calls toJSON(), which converts to UTC.
 * With app.timezone set to Asia/Kolkata (+05:30), midnight local becomes
 * 18:30 on the PREVIOUS day in UTC:
 *
 *     2026-08-01 00:00:00 +05:30  ->  2026-07-31T18:30:00.000000Z
 *
 * Any front-end that reads the date portion of that string — every edit form
 * that does .split('T')[0] or .substring(0, 10) to feed an <input type="date">
 * — therefore shows every date exactly one day early.
 *
 * Keeping the offset makes the value unambiguous and leaves the date portion
 * correct for the timezone the record was actually created in.
 */
trait SerializesLocalDates
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:sP');
    }
}