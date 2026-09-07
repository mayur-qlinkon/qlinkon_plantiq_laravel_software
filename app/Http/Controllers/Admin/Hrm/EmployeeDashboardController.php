<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\HrmTask;
use App\Models\Hrm\Leave;
use Illuminate\Support\Facades\Auth;
use App\Services\Hrm\TodoService;


class EmployeeDashboardController extends Controller
{
    public function __construct(protected TodoService $todos) {}
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return view('admin.hrm.employee.no-profile');
        }

        $employee->load(['department', 'designation', 'shift', 'store']);

        // ── Today's attendance ──
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->forDate(today())
            ->first();

        // ── Assigned tasks count ──
        $assignedTaskCount = HrmTask::whereHas('assignments', fn ($q) => $q->where('employee_id', $employee->id))
            ->count();

        // ── Pending leave count ──
        $pendingLeaveCount = Leave::where('employee_id', $employee->id)
            ->where('status', Leave::STATUS_PENDING)->count();

        // ── Present this month ──
        $monthStart = now()->startOfMonth();
        $presentThisMonth = Attendance::where('employee_id', $employee->id)
            ->forDateRange($monthStart->toDateString(), now()->toDateString())
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])
            ->count();

        // ── Recent attendance (last 7 records) ──
        $recentAttendance = Attendance::where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->limit(7)
            ->get();

        // ── Recent leaves (last 4) ──
        $recentLeaves = Leave::where('employee_id', $employee->id)
            ->with('leaveType')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

       // ── Todos for dashboard widget ──
        $todoData = $this->todos->getDashboardTodos();

        return view('admin.hrm.employee.dashboard', compact(
            'employee',
            'todayAttendance',
            'assignedTaskCount',
            'pendingLeaveCount',
            'presentThisMonth',
            'recentAttendance',
            'recentLeaves',
            'todoData'
        ));
    }
}
