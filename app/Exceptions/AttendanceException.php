<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Attendance-scan failure carrying a machine-readable error code and optional
 * context (e.g. shift timings), so the frontend can render a specific,
 * user-friendly message/UI instead of a generic "Scan Failed" state.
 *
 * Extends InvalidArgumentException so it is still caught by the existing
 * `catch (InvalidArgumentException|DomainException $e)` block in
 * AttendanceService::scan() for attempt logging — no change needed there.
 */
class AttendanceException extends InvalidArgumentException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'attendance_error',
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }
}