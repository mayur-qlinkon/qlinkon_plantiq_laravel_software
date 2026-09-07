<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Store;
use App\Services\Hrm\AttendanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class MobileScanController extends Controller
{
    public function __construct(protected AttendanceService $attendanceService) {}

    public function show(Store $store): View
    {
        abort_if($store->company_id !== Auth::user()->company_id, 403);

        $employee = Employee::query()
            ->with(['user', 'department', 'shift'])
            ->where('user_id', Auth::id())
            ->active()
            ->first();
        $todayAttendance = $employee
            ? $this->attendanceService->getTodayStatus($employee->id)
            : null;

        $action = 'check-in';

        if ($todayAttendance?->check_in_time && ! $todayAttendance?->check_out_time) {
            $action = 'check-out';
        } elseif ($todayAttendance?->check_out_time) {
            $action = 'done';
        }

        return view('admin.hrm.attendance.mobile-scan', compact('store', 'employee', 'todayAttendance', 'action'));
    }
}
