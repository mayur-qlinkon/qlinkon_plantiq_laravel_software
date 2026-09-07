<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MyAttendanceController extends Controller
{
    protected function myEmployee()
    {
        $emp = Auth::user()->employee;
        abort_if(! $emp, 403, 'No employee record linked to your account.');

        return $emp;
    }

    public function index(Request $request)
    {
        if (! Auth::user()->employee) {
            return view('admin.hrm.employee.no-profile');
        }
        $employee = $this->myEmployee();

        // Default to last 30 days if no date filter provided
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        $baseQuery = Attendance::where('employee_id', $employee->id)
            ->forDateRange($from->toDateString(), $to->toDateString());

        $query = (clone $baseQuery)->orderByDesc('date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate records (adjust items per page as needed)
        $attendances = $query->paginate(15)->withQueryString();

        // Summary calculations
        $all = (clone $baseQuery)->get();

        $summary = [
            'present' => $all->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])->count(),
            'absent' => $all->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $all->where('status', Attendance::STATUS_LATE)->count(),
            'half_day' => $all->where('status', Attendance::STATUS_HALF_DAY)->count(),
            'on_leave' => $all->where('status', Attendance::STATUS_ON_LEAVE)->count(),
            'holiday' => $all->whereIn('status', [Attendance::STATUS_HOLIDAY, Attendance::STATUS_WEEK_OFF])->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $attendances, 'summary' => $summary]);
        }

        return view('admin.hrm.my-attendance.index', compact(
            'employee', 'attendances', 'summary', 'from', 'to'
        ));
    }
}
