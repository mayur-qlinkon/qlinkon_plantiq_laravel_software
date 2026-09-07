<?php

namespace App\Services\Hrm;

use App\Exceptions\AttendanceException;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\AttendanceLog;
use App\Models\Hrm\AttendanceRule;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Shift;
use App\Models\Store;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceService
{
    /** Fixed buffer (meters) always added to a store's configured radius, regardless of GPS accuracy. */
    protected const GPS_BASE_BUFFER_METERS = 20;

    /** Max extra allowance (meters) granted for poor device GPS accuracy — capped so a very bad fix can't bypass the geofence entirely. */
    protected const GPS_MAX_ACCURACY_BONUS_METERS = 100;

    /**
     * Process a store QR scan using the single attendance engine.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function scan(array $data, Request $request): array
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $now = now();
        $forceCheckout = $request->boolean('force_checkout');
        $forceCheckoutGps = $request->boolean('force_checkout_gps');
        $forceCheckinGps = $request->boolean('force_checkin_gps');
        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;

        $lock = Cache::lock("attendance_scan_user_{$user->id}", 10);

        if (! $lock->get()) {
            throw new \Exception('Your attendance is currently processing. Please wait a moment.');
        }

        $attendance = null;
        $action = AttendanceLog::ACTION_CHECK_IN;

        $logBase = [
            'company_id' => $companyId,
            'method' => Attendance::METHOD_QR,
            'punched_at' => $now,
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'accuracy_meters' => $accuracy,
            'device_info' => $request->header('X-Device-Info'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        try {
            $employee = $this->resolveEmployee($user->id);
            $logBase['employee_id'] = $employee->id;

            $store = Store::query()
                ->whereKey($data['store_id'])
                ->where('company_id', $companyId)
                ->firstOrFail();

            $this->ensureEmployeeHasStoreAccess($employee, $store->id);

            $shift = $employee->shift;
            if (! $shift) {
                throw new InvalidArgumentException('No shift is assigned to your employee profile.');
            }

            $shiftWindow = $this->buildShiftWindowForMoment($shift, $now);

            $attendance = Attendance::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->whereDate('date', $shiftWindow['attendance_date'])
                ->first();

            if (! $attendance) {
                // Checkout-style fallback: GPS out-of-range/invalid still lets the employee
                // request a check-in, flagged for manager review, instead of a hard block.
                $locationVerified = true;
                $fallbackEligibleCodes = ['gps_out_of_range', 'gps_invalid_coordinates'];

                try {
                    $gpsResult = $this->validateGps($store, (float) $data['latitude'], (float) $data['longitude'], $accuracy);
                    $logBase['distance_meters'] = $gpsResult['distance_meters'];
                } catch (AttendanceException $e) {
                    $logBase['distance_meters'] = $e->context['distance_meters'] ?? null;

                    if (! in_array($e->errorCode, $fallbackEligibleCodes, true)) {
                        throw $e;
                    }

                    if (! $forceCheckinGps) {
                        return [
                            'requires_gps_fallback' => true,
                            'action' => AttendanceLog::ACTION_CHECK_IN,
                            'message' => $e->getMessage().' You can request a check-in — your manager will review it.',
                            'type' => 'warning',
                            'context' => $e->context,
                        ];
                    }

                    $locationVerified = false;
                }

                $holidayResult = $this->evaluateHolidayPolicy($employee, $shiftWindow['attendance_date']);

                $attendanceData = [
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'store_id' => $store->id,
                    'date' => $shiftWindow['attendance_date'],
                    'check_in_time' => $now,
                    'check_in_lat' => $data['latitude'],
                    'check_in_lng' => $data['longitude'],
                    'check_in_method' => Attendance::METHOD_QR,
                    'check_in_location_verified' => $locationVerified,
                    'check_in_review_status' => $locationVerified ? null : 'pending',
                ];

                if ($holidayResult !== null) {
                    $attendanceData = array_merge($attendanceData, $holidayResult);
                    $message = $holidayResult['status'] === Attendance::STATUS_PENDING
                        ? 'Holiday attendance recorded. Pending manager approval.'
                        : 'Holiday attendance recorded. Marked as working on holiday.';
                    $type = $holidayResult['status'] === Attendance::STATUS_PENDING ? 'warning' : 'success';
                } else {
                    [$status, $message, $type] = $this->determineCheckInStatus($now, $shiftWindow);
                    [$status, $message, $type] = $this->applyAttendanceRules(
                        $companyId,
                        $now,
                        $shiftWindow['shift_start'],
                        $status,
                        $message,
                        $type
                    );
                    $attendanceData['status'] = $status;
                }

                if (! $locationVerified) {
                    $message .= ' Your location could not be verified — this will be reviewed by your manager.';
                    $type = 'warning';
                }

                $attendance = DB::transaction(fn () => Attendance::create($attendanceData));

                $this->logAttempt(
                    array_merge($logBase, [
                        'action' => AttendanceLog::ACTION_CHECK_IN,
                        'attendance_id' => $attendance->id,
                    ]),
                    true,
                    $message
                );

                return [
                    'attendance' => $attendance->load('employee.user'),
                    'action' => AttendanceLog::ACTION_CHECK_IN,
                    'message' => $message,
                    'type' => $type,
                ];
            }

            $action = AttendanceLog::ACTION_CHECK_OUT;

            if ($attendance->check_out_time) {
                throw new InvalidArgumentException('Attendance already completed for this shift.');
            }

            $shiftWindow = $this->buildShiftWindowForDate($shift, $attendance->date->copy());

            // Checkout GPS check has a fallback: if it fails specifically because the
            // employee appears out of range (or GPS gave unusable coordinates), let them
            // request a checkout anyway — flagged for manager review — instead of a hard block.
            $locationVerified = true;
            $fallbackEligibleCodes = ['gps_out_of_range', 'gps_invalid_coordinates'];

            try {
                $gpsResult = $this->validateGps($store, (float) $data['latitude'], (float) $data['longitude'], $accuracy);
                $logBase['distance_meters'] = $gpsResult['distance_meters'];
            } catch (AttendanceException $e) {
                $logBase['distance_meters'] = $e->context['distance_meters'] ?? null;

                if (! in_array($e->errorCode, $fallbackEligibleCodes, true)) {
                    throw $e;
                }

                if (! $forceCheckoutGps) {
                    return [
                        'requires_gps_fallback' => true,
                        'action' => $action,
                        'message' => $e->getMessage().' You can request a checkout — your manager will review it.',
                        'type' => 'warning',
                        'context' => $e->context,
                    ];
                }

                $locationVerified = false;
            }

            if (! $forceCheckout && $now->lt($shiftWindow['early_leave_before'])) {
                return [
                    'requires_confirmation' => true,
                    'action' => $action,
                    'message' => 'You are checking out before the allowed early-leave threshold. Do you want to continue?',
                    'type' => 'warning',
                ];
            }

            $workedMinutes = $attendance->check_in_time->diffInMinutes($now);
            $workedHours = round($workedMinutes / 60, 2);

            // Calculate half-day threshold (50% of the shift's total scheduled minutes)
            $halfDayThreshold = $shiftWindow['min_working_minutes'] / 2;

            // If already marked as absent by strict check-in rules, keep it absent
            if ($attendance->status === Attendance::STATUS_ABSENT) {
                $status = Attendance::STATUS_ABSENT;
            } elseif ($workedMinutes < $halfDayThreshold) {
                // Below Laxman Rekha: Straight Half Day
                $status = Attendance::STATUS_HALF_DAY;
            } else {
                // Above Laxman Rekha: Safe Zone, evaluate punctuality
                $isLate = $attendance->check_in_time->gte($shiftWindow['late_mark_after']);
                $isEarlyLeave = $now->lt($shiftWindow['early_leave_before']);

                if ($isLate && $isEarlyLeave) {
                    $status = Attendance::STATUS_LATE_AND_EARLY;
                } elseif ($isLate) {
                    $status = Attendance::STATUS_LATE;
                } elseif ($isEarlyLeave) {
                    $status = Attendance::STATUS_EARLY_LEAVE;
                } else {
                    $status = Attendance::STATUS_PRESENT;
                }
            }

            $message = $forceCheckout && $now->lt($shiftWindow['early_leave_before'])
                ? 'Check-out recorded before the early-leave threshold.'
                : 'Check-out recorded successfully.';
            $type = $forceCheckout && $now->lt($shiftWindow['early_leave_before'])
                ? 'warning'
                : 'success';

            if (! $locationVerified) {
                $message .= ' Your location could not be verified — this will be reviewed by your manager.';
                $type = 'warning';
            }

            $attendance->update([
                'check_out_time' => $now,
                'check_out_lat' => $data['latitude'],
                'check_out_lng' => $data['longitude'],
                'check_out_method' => Attendance::METHOD_QR,
                'worked_hours' => $workedHours,
                'overtime_hours' => $this->calculateOvertimeHours($workedMinutes, $shiftWindow),
                'status' => $status,
                'check_out_location_verified' => $locationVerified,
                'check_out_review_status' => $locationVerified ? null : 'pending',
            ]);

            $this->logAttempt(
                array_merge($logBase, [
                    'action' => $action,
                    'attendance_id' => $attendance->id,
                ]),
                true,
                $message
            );

            return [
                'attendance' => $attendance->fresh()->load('employee.user'),
                'action' => $action,
                'message' => $message,
                'type' => $type,
            ];
        } catch (InvalidArgumentException|DomainException $e) {
            $this->logAttempt(
                array_merge($logBase ?? [], [
                    'action' => $action,
                    'attendance_id' => $attendance?->id,
                    'distance_meters' => $logBase['distance_meters'] ?? ($e instanceof AttendanceException ? ($e->context['distance_meters'] ?? null) : null),
                ]),
                false,
                $e->getMessage()
            );

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function getTodayStatus(int $employeeId): ?Attendance
    {
        $employee = Employee::query()
            ->with('shift')
            ->find($employeeId);

        if (! $employee) {
            return null;
        }

        if (! $employee->shift) {
            return Attendance::query()
                ->where('employee_id', $employeeId)
                ->whereDate('date', today()->toDateString())
                ->first();
        }

        $shiftWindow = $this->buildShiftWindowForMoment($employee->shift, now());

        return Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $shiftWindow['attendance_date'])
            ->first();
    }

    public function override(int $attendanceId, array $data): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $data) {
            $attendance = Attendance::findOrFail($attendanceId);

            $updateData = [
                'is_overridden' => true,
                'overridden_by' => $data['overridden_by'] ?? Auth::id(),
                'override_reason' => $data['reason'] ?? null,
            ];

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (isset($data['check_in_time'])) {
                $updateData['check_in_time'] = $data['check_in_time'];
                $updateData['check_in_method'] = Attendance::METHOD_MANUAL;
            }

            if (isset($data['check_out_time'])) {
                $updateData['check_out_time'] = $data['check_out_time'];
                $updateData['check_out_method'] = Attendance::METHOD_MANUAL;
            }

            $checkIn = $data['check_in_time'] ?? $attendance->check_in_time;
            $checkOut = $data['check_out_time'] ?? $attendance->check_out_time;

            if ($checkIn && $checkOut) {
                $checkIn = $checkIn instanceof Carbon ? $checkIn : Carbon::parse($checkIn);
                $checkOut = $checkOut instanceof Carbon ? $checkOut : Carbon::parse($checkOut);
                $updateData['worked_hours'] = round($checkIn->diffInMinutes($checkOut) / 60, 2);
            }

            $attendance->update($updateData);

            return $attendance->fresh();
        });
    }

    /**
     * Approve or reject a GPS-fallback flagged check-in/check-out.
     * Approve: marks that side as location-verified. Reject: marks the whole day Absent.
     *
     * @param  'check_in'|'check_out'  $field
     * @param  'approve'|'reject'  $decision
     */
    public function reviewLocation(int $attendanceId, string $field, string $decision, ?string $reason, int $reviewerId): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $field, $decision, $reason, $reviewerId) {
            $attendance = Attendance::findOrFail($attendanceId);

            $updateData = [
                "{$field}_review_status" => $decision === 'approve' ? 'approved' : 'rejected',
                "{$field}_reviewed_by" => $reviewerId,
                "{$field}_reviewed_at" => now(),
            ];

            if ($decision === 'approve') {
                $updateData["{$field}_location_verified"] = true;
            } else {
                $updateData['status'] = Attendance::STATUS_ABSENT;
                $updateData['is_overridden'] = true;
                $updateData['overridden_by'] = $reviewerId;
                $updateData['override_reason'] = $reason;
            }

            $attendance->update($updateData);

            return $attendance->fresh();
        });
    }

    /**
     * Evaluate the configured holiday attendance policy for a given employee and date.
     *
     * Detection covers: single-day holidays, multi-day ranges (date→end_date), and
     * recurring holidays (match by month/day, ignoring year) including recurring
     * multi-day ranges (safely handling cross-year ranges like Dec 25 - Jan 5).
     *
     * @return array{is_holiday: bool, working_on_holiday: bool, status: string}|null
     *
     * @throws DomainException when policy is `block`.
     */
    public function evaluateHolidayPolicy(Employee $employee, $date): ?array
    {
        // 1. Determine correct timezone (Using App default directly)
        $timezone = config('app.timezone');

        // 2. Safely normalize the incoming date to the business timezone
        if ($date instanceof \Carbon\Carbon) {
            $target = $date->copy()->setTimezone($timezone);
        } else {
            $target = \Carbon\Carbon::parse((string) $date, $timezone);
        }

        // Now these strings reflect the exact local date, regardless of server UTC time
        $targetDate = $target->toDateString();
        $targetMonthDay = $target->format('m-d');

        $driver = DB::connection()->getDriverName();

        // Database dialect formatting
        $dateMd = match ($driver) {
            'sqlite' => "strftime('%m-%d', date)",
            'pgsql' => "to_char(date, 'MM-DD')",
            default => "DATE_FORMAT(date, '%m-%d')", // MySQL/MariaDB
        };

        $endOrDateMd = match ($driver) {
            'sqlite' => "strftime('%m-%d', COALESCE(end_date, date))",
            'pgsql' => "to_char(COALESCE(end_date, date), 'MM-DD')",
            default => "DATE_FORMAT(COALESCE(end_date, date), '%m-%d')", // MySQL/MariaDB
        };

        // Existing robust holiday detection logic remains untouched
        $isHoliday = Holiday::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->where(function ($query) use ($targetDate, $targetMonthDay, $dateMd, $endOrDateMd) {

                // Scenario A: Non-recurring (exact date match)
                $query->where(function ($q) use ($targetDate) {
                    $q->where('is_recurring', false)
                        ->whereDate('date', '<=', $targetDate)
                        ->where(function ($q2) use ($targetDate) {
                            $q2->whereDate('date', $targetDate)
                                ->orWhereDate('end_date', '>=', $targetDate);
                        });
                })

                // Scenario B: Recurring holidays (annual match ignoring year)
                    ->orWhere(function ($q) use ($targetMonthDay, $dateMd, $endOrDateMd) {
                        $q->where('is_recurring', true)
                            ->where(function ($sub) use ($targetMonthDay, $dateMd, $endOrDateMd) {

                                // B1: Normal range (e.g., Mar 1 to Mar 5) -> Start <= End
                                $sub->where(function ($normal) use ($targetMonthDay, $dateMd, $endOrDateMd) {
                                    $normal->whereRaw("{$dateMd} <= {$endOrDateMd}")
                                        ->whereRaw("{$dateMd} <= ?", [$targetMonthDay])
                                        ->whereRaw("{$endOrDateMd} >= ?", [$targetMonthDay]);
                                })

                                // B2: Cross-year range (e.g., Dec 25 to Jan 5) -> Start > End
                                    ->orWhere(function ($crossYear) use ($targetMonthDay, $dateMd, $endOrDateMd) {
                                        $crossYear->whereRaw("{$dateMd} > {$endOrDateMd}")
                                            ->where(function ($orTarget) use ($targetMonthDay, $dateMd, $endOrDateMd) {
                                                $orTarget->whereRaw("{$dateMd} <= ?", [$targetMonthDay])
                                                    ->orWhereRaw("{$endOrDateMd} >= ?", [$targetMonthDay]);
                                            });
                                    });

                            });
                    });
            })
            ->exists();

        if (! $isHoliday) {
            return null;
        }

        $policy = (string) get_setting('attendance.holiday_policy', 'block', $employee->company_id);

        if ($policy === 'block') {
            throw new DomainException('Today is a holiday. Attendance is not allowed.');
        }

        if ($policy === 'approval') {
            return [
                'is_holiday' => true,
                'working_on_holiday' => true,
                'status' => Attendance::STATUS_PENDING,
            ];
        }

        return [
            'is_holiday' => true,
            'working_on_holiday' => true,
            'status' => Attendance::STATUS_PRESENT,
        ];
    }

    public function getReport(array $filters): LengthAwarePaginator
    {
        $query = Attendance::with(['employee.user', 'employee.department', 'store']);

        // Store awareness: hard-scope the report to the active store (switcher),
        // regardless of any store_id filter passed in. Multi-store tenants only
        // see the store they're currently viewing.
        $storeId = active_store()?->id;
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $query->forDateRange($filters['date_from'], $filters['date_to']);
        } elseif (! empty($filters['date'])) {
            $query->forDate($filters['date']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }        

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($builder) => $builder->where('department_id', $filters['department_id']));
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }

   /**
     * Handle attendances where employees forgot to check-out.
     * Marks status as missing_checkout instead of inserting fake checkout times.
     */
    public function handleMissingCheckouts(): int
    {
        $attendances = Attendance::query()
            ->pendingCheckout()
            ->where('status', '!=', 'missing_checkout') // Skip already processed missing records
            ->with('employee.shift')
            ->whereDate('date', '>=', today()->subDays(2)->toDateString())
            ->get();

        $count = 0;
        $now = now();

        foreach ($attendances as $attendance) {
            if (! $attendance->employee?->shift) {
                continue;
            }

            $shiftWindow = $this->buildShiftWindowForDate(
                $attendance->employee->shift,
                $attendance->date->copy()
            );

            // Do not process if the shift has not ended yet
            if ($now->lt($shiftWindow['shift_end'])) {
                continue;
            }

            // Mark as missing checkout and update notes without filling fake check_out_time
            $attendance->update([
                'status' => 'missing_checkout',
                'notes' => trim(($attendance->notes ?? '') . ' [System: Automatically marked due to missing check-out]'),
            ]);

            $count++;
        }

        return $count;
    }

    protected function resolveEmployee(int $userId): Employee
    {
        $employee = Employee::query()
            ->with(['shift', 'user.stores'])
            ->where('user_id', $userId)
            ->active()
            ->first();

        if (! $employee) {
            throw new InvalidArgumentException('You are not registered as an active employee.');
        }

        return $employee;
    }

    protected function ensureEmployeeHasStoreAccess(Employee $employee, int $storeId): void
    {
        $hasStoreAccess = (int) $employee->store_id === $storeId;

        if (! $hasStoreAccess && $employee->user) {
            $hasStoreAccess = $employee->user->stores->contains('id', $storeId);
        }

        if (! $hasStoreAccess) {
            throw new InvalidArgumentException('You are not assigned to this store.');
        }
    }

    /**
     * @return array{distance_meters: float, accuracy_meters: ?float}
     */
    protected function validateGps(Store $store, float $lat, float $lng, ?float $accuracy = null): array
    {
        if (abs($lat) < 0.001 && abs($lng) < 0.001) {
            throw new AttendanceException(
                'Invalid GPS coordinates. Please enable location services.',
                'gps_invalid_coordinates'
            );
        }

        if ($store->office_lat === null || $store->office_lng === null || $store->gps_radius_meters === null) {
            throw new AttendanceException(
                'This store does not have attendance GPS settings configured.',
                'gps_not_configured'
            );
        }

        $distance = $this->haversineDistance($lat, $lng, (float) $store->office_lat, (float) $store->office_lng);

        // Poor device GPS accuracy widens the tolerance (capped) instead of causing a
        // false rejection when the employee is genuinely at the store.
        $accuracyBonus = $accuracy !== null ? min(max($accuracy, 0), self::GPS_MAX_ACCURACY_BONUS_METERS) : 0;
        $allowedRadius = (int) $store->gps_radius_meters + self::GPS_BASE_BUFFER_METERS + $accuracyBonus;

        if ($distance > $allowedRadius) {
            throw new AttendanceException(
                'You are outside the allowed attendance radius for this store.',
                'gps_out_of_range',
                [
                    'distance_meters' => $distance,
                    'allowed_radius_meters' => $allowedRadius,
                    'accuracy_meters' => $accuracy,
                ]
            );
        }

        return [
            'distance_meters' => $distance,
            'accuracy_meters' => $accuracy,
        ];
    }

    /**
     * @return array{
     *     attendance_date: string,
     *     shift_start: Carbon,
     *     shift_end: Carbon,
     *     late_mark_after: Carbon,
     *     half_day_after: ?Carbon,
     *     early_leave_before: Carbon,
     *     min_working_minutes: int,
     *     overtime_after_minutes: int
     * }
     */
    protected function buildShiftWindowForMoment(Shift $shift, Carbon $moment): array
    {
        $attendanceDate = $moment->toDateString();
        $shiftStartToday = Carbon::parse($moment->toDateString().' '.$shift->start_time);
        $shiftEndToday = Carbon::parse($moment->toDateString().' '.$shift->end_time);

        if ($shiftEndToday->lessThanOrEqualTo($shiftStartToday) && $moment->lt($shiftEndToday)) {
            $attendanceDate = $moment->copy()->subDay()->toDateString();
        }

        return $this->buildShiftWindowForDate($shift, Carbon::parse($attendanceDate));
    }

    /**
     * @return array{
     *     attendance_date: string,
     *     shift_start: Carbon,
     *     shift_end: Carbon,
     *     late_mark_after: Carbon,
     *     half_day_after: ?Carbon,
     *     early_leave_before: Carbon,
     *     min_working_minutes: int,
     *     overtime_after_minutes: int
     * }
     */
    protected function buildShiftWindowForDate(Shift $shift, Carbon $attendanceDate): array
    {
        $shiftStart = Carbon::parse($attendanceDate->toDateString().' '.$shift->start_time);
        $shiftEnd = Carbon::parse($attendanceDate->toDateString().' '.$shift->end_time);

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        $scheduledMinutes = max($shiftStart->diffInMinutes($shiftEnd), 1);
        $lateMarkAfter = $shift->late_mark_after
            ? $this->buildShiftThreshold($attendanceDate, $shift->late_mark_after, $shiftStart, $shiftEnd)
            : $shiftStart->copy();
        $halfDayAfter = $shift->half_day_after
            ? $this->buildShiftThreshold($attendanceDate, $shift->half_day_after, $shiftStart, $shiftEnd)
            : null;
        $earlyLeaveBefore = $shift->early_leave_before
            ? $this->buildShiftThreshold($attendanceDate, $shift->early_leave_before, $shiftStart, $shiftEnd)
            : $shiftEnd->copy();

        return [
            'attendance_date' => $attendanceDate->toDateString(),
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
            'late_mark_after' => $lateMarkAfter,
            'half_day_after' => $halfDayAfter,
            'early_leave_before' => $earlyLeaveBefore,
            'min_working_minutes' => (int) ($shift->min_working_hours_minutes ?: $scheduledMinutes),
            'overtime_after_minutes' => (int) ($shift->overtime_after_minutes ?? $scheduledMinutes),
        ];
    }

    protected function buildShiftThreshold(Carbon $attendanceDate, string $time, Carbon $shiftStart, Carbon $shiftEnd): Carbon
    {
        $threshold = Carbon::parse($attendanceDate->toDateString().' '.$time);

        if ($shiftEnd->toDateString() !== $shiftStart->toDateString() && $threshold->lt($shiftStart)) {
            $threshold->addDay();
        }

        return $threshold;
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    protected function determineCheckInStatus(Carbon $now, array $shiftWindow): array
    {
        if ($now->gte($shiftWindow['shift_end'])) {
            throw new AttendanceException(
                sprintf(
                    "Your shift ended at %s — check-in window is closed. Please ask your manager to add today's attendance manually.",
                    $shiftWindow['shift_end']->format('g:i A')
                ),
                'shift_ended',
                [
                    'shift_start' => $shiftWindow['shift_start']->format('g:i A'),
                    'shift_end' => $shiftWindow['shift_end']->format('g:i A'),
                ]
            );
        }

        if ($shiftWindow['half_day_after'] && $now->gte($shiftWindow['half_day_after'])) {
            return [Attendance::STATUS_HALF_DAY, 'Check-in recorded. You have been marked half day.', 'warning'];
        }

        if ($now->gte($shiftWindow['late_mark_after'])) {
            return [Attendance::STATUS_LATE, 'Check-in recorded. You have been marked late.', 'warning'];
        }

        return [Attendance::STATUS_PRESENT, 'Check-in recorded successfully.', 'success'];
    }

    /**
     * Apply auto-applied attendance rules that can override the per-shift check-in status
     * based on minutes-late from the shift start.
     *
     * Rule types evaluated:
     *   - late_to_half_day → ACTION_MARK_HALF_DAY when minutes_late ≥ threshold_count
     *   - late_to_absent   → ACTION_MARK_ABSENT  when minutes_late ≥ threshold_count
     *
     * The most severe outcome wins (absent > half_day > late > present), so multiple
     * matching rules are safe. Cumulative-period rules (weekly/monthly counts) are
     * not handled here and remain a separate concern.
     *
     * @return array{0:string,1:string,2:string} [status, message, type]
     */
    public function applyAttendanceRules(
        int $companyId,
        Carbon $checkInTime,
        Carbon $shiftStart,
        string $defaultStatus,
        string $defaultMessage,
        string $defaultType,
    ): array {
        $rules = AttendanceRule::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('auto_apply', true)
            ->whereIn('rule_type', [
                AttendanceRule::TYPE_LATE_TO_HALF_DAY,
                AttendanceRule::TYPE_LATE_TO_ABSENT,
            ])
            ->orderBy('threshold_count')
            ->get();

        if ($rules->isEmpty()) {
            return [$defaultStatus, $defaultMessage, $defaultType];
        }

        $minutesLate = (int) max(0, $shiftStart->diffInMinutes($checkInTime, false));

        if ($minutesLate <= 0) {
            return [$defaultStatus, $defaultMessage, $defaultType];
        }

        $severity = [
            Attendance::STATUS_PRESENT => 0,
            Attendance::STATUS_LATE => 1,
            Attendance::STATUS_HALF_DAY => 2,
            Attendance::STATUS_ABSENT => 3,
        ];

        $resolvedStatus = $defaultStatus;
        $matchedRule = null;

        foreach ($rules as $rule) {
            if ($minutesLate < (int) $rule->threshold_count) {
                continue;
            }

            $candidate = match ($rule->action) {
                AttendanceRule::ACTION_MARK_HALF_DAY => Attendance::STATUS_HALF_DAY,
                AttendanceRule::ACTION_MARK_ABSENT => Attendance::STATUS_ABSENT,
                default => null,
            };

            if ($candidate === null) {
                continue;
            }

            if (($severity[$candidate] ?? 0) > ($severity[$resolvedStatus] ?? 0)) {
                $resolvedStatus = $candidate;
                $matchedRule = $rule;
            }
        }

        if ($resolvedStatus === $defaultStatus || $matchedRule === null) {
            return [$defaultStatus, $defaultMessage, $defaultType];
        }

        $statusLabel = Attendance::STATUS_LABELS[$resolvedStatus] ?? $resolvedStatus;
        $message = "Check-in recorded. Marked as {$statusLabel} by rule \"{$matchedRule->name}\" ({$minutesLate} min late).";
        $type = $resolvedStatus === Attendance::STATUS_ABSENT ? 'error' : 'warning';

        return [$resolvedStatus, $message, $type];
    }

    protected function calculateOvertimeHours(int $workedMinutes, array $shiftWindow): float
    {
        $overtimeMinutes = max($workedMinutes - $shiftWindow['overtime_after_minutes'], 0);

        return round($overtimeMinutes / 60, 2);
    }

    protected function logAttempt(array $data, bool $isValid, string $remarkOrReason): void
    {
        if (empty($data['employee_id'])) {
            return;
        }

        try {
            AttendanceLog::create(array_merge(array_filter($data, fn ($value) => $value !== null), [
                'is_valid' => $isValid,
                'remarks' => $isValid ? $remarkOrReason : null,
                'rejection_reason' => ! $isValid ? $remarkOrReason : null,
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
