<?php

namespace App\Http\Middleware\Production;

use App\Services\Hrm\AttendanceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCheckedInToday
{
    public function __construct(protected AttendanceService $attendanceService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $employee = Auth::user()?->employee;

        if (! $employee) {
            abort(403, 'Ye page sirf employees ke liye hai.');
        }

        $today = $this->attendanceService->getTodayStatus($employee->id);

        if (! $today || ! $today->check_in_time) {
            return redirect()
                ->route('admin.production.my-tasks.not-checked-in');
        }

        return $next($request);
    }
}