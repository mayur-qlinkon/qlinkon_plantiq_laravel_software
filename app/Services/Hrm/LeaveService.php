<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Leave;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;

use App\Notifications\Hrm\LeaveStatusNotification;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveService
{
    /**
     * Apply for leave.
     */
    public function apply(array $data): Leave
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::findOrFail($data['employee_id']);

            // Never trust the client's day count. It arrives from a readonly
            // input, but the request can be replayed with any value — and both
            // the consecutive-day check and the balance check below read it,
            // so a forged 0.5 defeated every guard at once.
            $data['total_days'] = $this->calculateLeaveDays(
                $data['from_date'],
                $data['to_date'],
                $data['day_type'] ?? Leave::DAY_FULL,
                $employee->company_id
            );

            $this->assertApplicable($data);

            return Leave::create($data);
        });
    }

    /**
     * Every rule a leave request must satisfy, whether it is being created or
     * edited.
     *
     * These lived inline in apply(), so MyLeaveController::update() — which
     * writes the row directly — bypassed all of them: a one-day request could
     * be edited into a thirty-day one with no balance or overlap check.
     *
     * $data must already carry a trusted total_days from calculateLeaveDays().
     * $ignoreLeaveId excludes the row being edited from the overlap test.
     */
    public function assertApplicable(array $data, ?int $ignoreLeaveId = null): void
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        // Validate advance notice
        if ($leaveType->min_days_before_apply > 0) {
            $daysAhead = now()->diffInDays($data['from_date'], false);
            if ($daysAhead < $leaveType->min_days_before_apply) {
                throw new InvalidArgumentException(
                    "This leave type requires {$leaveType->min_days_before_apply} days advance notice."
                );
            }
        }

        // Validate max consecutive days
        if ($leaveType->max_consecutive_days > 0 && $data['total_days'] > $leaveType->max_consecutive_days) {
            throw new InvalidArgumentException(
                "Maximum {$leaveType->max_consecutive_days} consecutive days allowed for this leave type."
            );
        }

        // Check balance. A pending request does not consume balance — used is
        // only incremented on approve — so the row being edited needs no
        // add-back here.
        $year = date('Y', strtotime($data['from_date']));
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        $available = $balance ? $balance->available : 0;
        if ($available < $data['total_days']) {
            throw new InvalidArgumentException(
                "Insufficient leave balance. Available: {$available} days, Requested: {$data['total_days']} days."
            );
        }

        // Check overlap with existing approved/pending leaves
        $overlap = Leave::where('employee_id', $employee->id)
            ->whereIn('status', [Leave::STATUS_PENDING, Leave::STATUS_APPROVED])
            ->when($ignoreLeaveId, fn ($q) => $q->where('id', '!=', $ignoreLeaveId))
            ->where('from_date', '<=', $data['to_date'])
            ->where('to_date', '>=', $data['from_date'])
            ->exists();

        if ($overlap) {
            throw new InvalidArgumentException('You already have a leave request for the selected dates.');
        }

        // Validate document requirement
        if ($leaveType->requires_document && empty($data['document'])) {
            throw new InvalidArgumentException('This leave type requires a supporting document.');
        }
    }

    /**
     * Compute how many days a leave request actually consumes.
     *
     * Weekends and company holidays are excluded, matching the working-day
     * convention AttendanceSummariser already uses so leave and payroll agree.
     */
    public function calculateLeaveDays($fromDate, $toDate, string $dayType, int $companyId): float
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            throw new InvalidArgumentException('End date cannot be before start date.');
        }

        // A half-day leave is a single calendar day by definition.
        if ($dayType !== Leave::DAY_FULL) {
            if (! $from->isSameDay($to)) {
                throw new InvalidArgumentException('A half-day leave must start and end on the same date.');
            }

            return 0.5;
        }

        // Tenantable does not register its scope on every path, so the company
        // condition is stated explicitly here.
        $holidays = Holiday::withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->whereNotNull('end_date')
                            ->where('date', '<=', $to->toDateString())
                            ->where('end_date', '>=', $from->toDateString());
                    });
            })
            ->get();

        // A holiday may span several days via end_date, so expand each one.
        $holidayDates = [];
        foreach ($holidays as $holiday) {
            $cursor = $holiday->date->copy();
            $last = $holiday->end_date ? $holiday->end_date->copy() : $holiday->date->copy();

            while ($cursor->lte($last)) {
                $holidayDates[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        $days = 0.0;
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            if (! $cursor->isWeekend() && ! isset($holidayDates[$cursor->toDateString()])) {
                $days++;
            }
            $cursor->addDay();
        }

        if ($days <= 0) {
            throw new InvalidArgumentException('The selected dates contain no working days.');
        }

        return $days;
    }

    /**
     * Approve a leave request.
     */
    public function approve(Leave $leave, ?string $remarks = null): Leave
    {
        if ($leave->status !== Leave::STATUS_PENDING) {
            throw new InvalidArgumentException('Only pending leave requests can be approved.');
        }

        return DB::transaction(function () use ($leave, $remarks) {
            $leave->update([
                'status' => Leave::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_remarks' => $remarks,
            ]);

            // Update leave balance
            $year = $leave->from_date->year;
            $balance = LeaveBalance::firstOrCreate(
                [
                    'company_id' => $leave->company_id,
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year' => $year,
                ],
                ['allocated' => 0, 'used' => 0, 'carried_forward' => 0, 'adjustment' => 0]
            );

            $balance->increment('used', $leave->total_days);

            $updatedLeave = $leave->fresh(['employee.user']);
            if ($updatedLeave->employee?->user) {
                $updatedLeave->employee->user->notify(new LeaveStatusNotification($updatedLeave));
            }

            return $updatedLeave;
        });
    }

    /**
     * Reject a leave request.
     */
    public function reject(Leave $leave, ?string $remarks = null): Leave
    {
        if ($leave->status !== Leave::STATUS_PENDING) {
            throw new InvalidArgumentException('Only pending leave requests can be rejected.');
        }

        $leave->update([
            'status' => Leave::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_remarks' => $remarks,
        ]);

        $updatedLeave = $leave->fresh(['employee.user']);
        if ($updatedLeave->employee?->user) {
            $updatedLeave->employee->user->notify(new LeaveStatusNotification($updatedLeave));
        }

        return $updatedLeave;
    }

    /**
     * Cancel a leave request.
     */
    public function cancel(Leave $leave, string $reason): Leave
    {
        if (! in_array($leave->status, [Leave::STATUS_PENDING, Leave::STATUS_APPROVED])) {
            throw new InvalidArgumentException('This leave cannot be cancelled.');
        }

        return DB::transaction(function () use ($leave, $reason) {
            $wasApproved = $leave->status === Leave::STATUS_APPROVED;

            $leave->update([
                'status' => Leave::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            // Restore balance if was approved
            if ($wasApproved) {
                $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                    ->where('leave_type_id', $leave->leave_type_id)
                    ->where('year', $leave->from_date->year)
                    ->first();

                if ($balance) {
                    $balance->decrement('used', $leave->total_days);
                }
            }

            return $leave->fresh();
        });
    }

    /**
     * Get leave balances for an employee.
     */
    public function getBalances(int $employeeId, int $year): array
    {
        $employee = Employee::findOrFail($employeeId);
        $leaveTypes = LeaveType::active()->ordered()->get();

        $balances = LeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        return $leaveTypes->map(function ($type) use ($balances) {
            $balance = $balances->get($type->id);

            return [
                'leave_type' => $type,
                'allocated' => $balance?->allocated ?? $type->default_days_per_year,
                'used' => $balance?->used ?? 0,
                'carried_forward' => $balance?->carried_forward ?? 0,
                'adjustment' => $balance?->adjustment ?? 0,
                'available' => $balance?->available ?? $type->default_days_per_year,
            ];
        })->toArray();
    }

    /**
     * Bulk-initialize leave balances for all active employees × all active leave types.
     * Skips rows that already exist. Returns count of rows created.
     */
    public function initializeBalances(int $year): int
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->get();
        $created = 0;

        DB::transaction(function () use ($employees, $leaveTypes, $year, &$created) {
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $type) {
                    $exists = LeaveBalance::where([
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ])->exists();

                    if (! $exists) {
                        LeaveBalance::create([
                            'company_id' => $employee->company_id,
                            'employee_id' => $employee->id,
                            'leave_type_id' => $type->id,
                            'year' => $year,
                            'allocated' => $type->default_days_per_year,
                            'used' => 0,
                            'carried_forward' => 0,
                            'adjustment' => 0,
                        ]);
                        $created++;
                    }
                }
            }
        });

        return $created;
    }

    /**
     * Carry forward unused leave balances from a previous year to the new year.
     * Only for leave types that have is_carry_forward = true.
     * Skips employees who already have a balance record for the new year.
     */
    public function carryForward(int $fromYear, int $toYear): int
    {
        $employees = Employee::active()->get();
        $leaveTypes = LeaveType::active()->where('is_carry_forward', true)->get();
        $created = 0;

        DB::transaction(function () use ($employees, $leaveTypes, $fromYear, $toYear, &$created) {
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $type) {
                    $prevBalance = LeaveBalance::where([
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $fromYear,
                    ])->first();

                    if (! $prevBalance) {
                        continue;
                    }

                    $unused = max(0, $prevBalance->available);
                    $maxCarry = $type->max_carry_forward_days > 0
                        ? min($unused, $type->max_carry_forward_days)
                        : $unused;

                    if ($maxCarry <= 0) {
                        continue;
                    }

                    $existing = LeaveBalance::where([
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $toYear,
                    ])->first();

                    if ($existing) {
                        $existing->increment('carried_forward', $maxCarry);
                    } else {
                        LeaveBalance::create([
                            'company_id' => $employee->company_id,
                            'employee_id' => $employee->id,
                            'leave_type_id' => $type->id,
                            'year' => $toYear,
                            'allocated' => $type->default_days_per_year,
                            'used' => 0,
                            'carried_forward' => $maxCarry,
                            'adjustment' => 0,
                        ]);
                    }

                    $created++;
                }
            }
        });

        return $created;
    }

    /**
     * Update allocated days and adjustment for a single balance record.
     */
    public function updateBalance(LeaveBalance $balance, float $allocated, float $adjustment): LeaveBalance
    {
        $balance->update([
            'allocated' => $allocated,
            'adjustment' => $adjustment,
        ]);

        return $balance->fresh();
    }

    /**
     * Get all leave balances for a given year (for the balance management page).
     */
    public function getAllBalances(int $year): Collection
    {
        return LeaveBalance::with(['employee.user', 'leaveType'])
            ->where('year', $year)
            ->orderBy('employee_id')
            ->orderBy('leave_type_id')
            ->get();
    }

    /**
     * Get filtered leave list.
     */
    public function getList(array $filters): LengthAwarePaginator
    {
        $query = Leave::with(['employee.user', 'leaveType', 'approvedByUser']);

        // Store awareness: leaves have no store_id — scope via the employee's
        // store to the active store (switcher). Multi-store tenants only see
        // leave requests from the store they're currently viewing.
        $storeId = active_store()?->id;
        if ($storeId) {
            $query->whereHas('employee', fn ($q) => $q->where('store_id', $storeId));
        }

        // Company-wide visibility is the HR-level grant. Everyone else sees
        // their own requests plus their direct reportees' — blocking the
        // approve action alone would still have exposed every colleague's
        // reason text through this list.
        if (! has_permission('leaves.approve_all')) {
            $myEmployeeId = Auth::user()?->employee?->id;

            $query->where(function ($q) use ($myEmployeeId) {
                $q->where('employee_id', $myEmployeeId)
                    ->orWhereHas('employee', fn ($e) => $e->where('reporting_to', $myEmployeeId));
            });
        }

        if (! empty($filters['employee_name'])) {
            $searchTerm = $filters['employee_name'];
            $query->whereHas('employee.user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (! empty($filters['from_date'])) {
            $query->where('from_date', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->where('to_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }
}
