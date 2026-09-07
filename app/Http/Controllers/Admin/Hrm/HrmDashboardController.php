<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use App\Models\Hrm\HrmTask;
use App\Models\Hrm\Leave;
use App\Models\Hrm\SalarySlip;
use Illuminate\Support\Facades\Auth;

/**
 * HrmDashboardController — company-admin HRM overview.
 *
 * Read-only landing dashboard for the HR/owner. Every card is informational;
 * all interactive actions live on their own module pages (this view only links
 * out to them). Company-scoped throughout (no store filter — HR is org-wide).
 */
class HrmDashboardController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Store awareness: scope every HRM metric to the active store (switcher).
        // Multi-store tenants see only the active store's people; switching
        // stores swaps all the numbers. $storeId null = no active store (rare).
        $storeId = active_store()?->id;

        // ════════════════════════════════════════════════════
        //  1. EMPLOYEE HEADCOUNT
        // ════════════════════════════════════════════════════
        $employeeBase = Employee::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId));

        $employeeStats = [
            'total'      => (clone $employeeBase)->count(),
            'active'     => (clone $employeeBase)->where('status', Employee::STATUS_ACTIVE)->count(),
            'terminated' => (clone $employeeBase)->where('status', Employee::STATUS_TERMINATED)->count(),
        ];

        // ════════════════════════════════════════════════════
        //  2. TODAY'S ATTENDANCE
        // ════════════════════════════════════════════════════
        $attendanceToday = Attendance::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereDate('date', today());

        $attendanceStats = [
            'present' => (clone $attendanceToday)->where('status', Attendance::STATUS_PRESENT)->count(),
            'late'    => (clone $attendanceToday)->where('status', Attendance::STATUS_LATE)->count(),
            'absent'  => (clone $attendanceToday)->where('status', Attendance::STATUS_ABSENT)->count(),
        ];

        // ════════════════════════════════════════════════════
        //  3. TASK OVERVIEW (company-wide)
        // ════════════════════════════════════════════════════
        // Tasks have no store_id — a task belongs to a store if any of its
        // assignees (employees) belong to that store.
        $taskBase = HrmTask::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->whereHas(
                'assignees',
                fn ($a) => $a->where('store_id', $storeId)
            ));

        $taskStats = [
            'total'       => (clone $taskBase)->count(),
            'in_progress' => (clone $taskBase)->where('status', HrmTask::STATUS_IN_PROGRESS)->count(),
            'overdue'     => (clone $taskBase)
                ->whereNotNull('due_date')
                ->where('due_date', '<', today())
                ->whereNotIn('status', [HrmTask::STATUS_COMPLETED, HrmTask::STATUS_CANCELLED])
                ->count(),
            'completed'   => (clone $taskBase)->where('status', HrmTask::STATUS_COMPLETED)->count(),
        ];

        // ════════════════════════════════════════════════════
        //  4. PRIORITY TASKS — only HIGH & URGENT, still open (top 6)
        // ════════════════════════════════════════════════════
        $priorityTasks = HrmTask::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->whereHas(
                'assignees',
                fn ($a) => $a->where('store_id', $storeId)
            ))
            ->whereIn('priority', [HrmTask::PRIORITY_HIGH, HrmTask::PRIORITY_URGENT])
            ->whereNotIn('status', [HrmTask::STATUS_COMPLETED, HrmTask::STATUS_CANCELLED])
            ->with(['assignees:id,user_id'])
            ->orderByRaw("FIELD(priority, '".HrmTask::PRIORITY_URGENT."', '".HrmTask::PRIORITY_HIGH."')")
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(fn (HrmTask $task) => [
                'id'        => $task->id,
                'title'     => $task->title,
                'priority'  => $task->priority,
                'status'    => $task->status,
                'due_date'  => $task->due_date?->format('d M Y'),
                'is_overdue' => $task->due_date && $task->due_date->isPast()
                    && ! in_array($task->status, [HrmTask::STATUS_COMPLETED, HrmTask::STATUS_CANCELLED]),
                'assignees' => $task->assignees->map(fn ($e) => $e->full_name)->filter()->values(),
            ]);

        // ════════════════════════════════════════════════════
        //  5. RECENT LEAVE REQUESTS (latest 5)
        // ════════════════════════════════════════════════════
        $leaveRequests = Leave::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where('store_id', $storeId)
            ))
            ->with([
                'employee:id,user_id',
                'leaveType:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Leave $leave) => [
                'id'         => $leave->id,
                'employee'   => $leave->employee?->full_name ?? '—',
                'type'       => $leave->leaveType?->name ?? '—',
                'from_date'  => $leave->from_date?->format('d M Y'),
                'to_date'    => $leave->to_date?->format('d M Y'),
                'total_days' => $leave->total_days,
                'status'     => $leave->status,
            ]);

        // ════════════════════════════════════════════════════
        //  6. RECENT PAYROLL (latest 5 salary slips)
        // ════════════════════════════════════════════════════
        $recentPayroll = SalarySlip::where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where('store_id', $storeId)
            ))
            ->with(['employee:id,user_id'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (SalarySlip $slip) => [
                'id'          => $slip->id,
                'slip_number' => $slip->slip_number,
                'employee'    => $slip->employee?->full_name ?? '—',
                'period'      => \DateTime::createFromFormat('!m', (string) $slip->month)->format('M').' '.$slip->year,
                'net_salary'  => (float) $slip->net_salary,
                'status'      => $slip->status,
            ]);

        return view('admin.hrm.dashboard', compact(
            'employeeStats',
            'attendanceStats',
            'taskStats',
            'priorityTasks',
            'leaveRequests',
            'recentPayroll'
        ));
    }
}