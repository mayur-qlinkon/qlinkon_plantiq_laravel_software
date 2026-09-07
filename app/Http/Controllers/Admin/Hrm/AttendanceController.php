<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Exceptions\AttendanceException;
use App\Exports\AttendanceCalendarExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hrm\ScanAttendanceRequest;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\Department;
use App\Models\Hrm\Employee;
use App\Models\Store;
use App\Services\Hrm\AnnouncementService;
use App\Services\Hrm\AttendanceExportService;
use App\Services\Hrm\AttendanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected AnnouncementService $announcementService,
        protected AttendanceExportService $exportService,
    ) {}

    public function scan(ScanAttendanceRequest $request): JsonResponse
    {
        if ($this->announcementService->hasPendingMandatory($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Please acknowledge all mandatory announcements before marking attendance.',
            ], 422);
        }

        try {
            $result = $this->attendanceService->scan($request->validated(), $request);

            return response()->json([
                'success' => true,
                'requires_confirmation' => $result['requires_confirmation'] ?? false,
                'requires_gps_fallback' => $result['requires_gps_fallback'] ?? false,
                'action' => $result['action'] ?? null,
                'message' => $result['message'],
                'type' => $result['type'] ?? 'success',
                'context' => $result['context'] ?? null,
                'data' => $result['attendance'] ?? null,
            ]);
        } catch (AttendanceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'type' => 'error',
                'error_code' => $e->errorCode,
                'context' => $e->context,
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Today is a holiday. Attendance is not required.',
                'type' => 'info',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'type' => 'error'], 422);
        }
    }

    public function today(Request $request): JsonResponse|View
    {
        $companyId = Auth::user()->company_id;
        $todayDate = now()->toDateString();

        // Store awareness: scope today's attendance to the active store (switcher).
        $storeId = active_store()?->id;

        $attendances = Attendance::with(['employee.user', 'employee.department', 'store'])
            ->where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereDate('date', $todayDate)
            ->orderBy('check_in_time', 'desc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $attendances]);
        }

        return view('admin.hrm.attendance.today', compact('attendances', 'todayDate'));
    }

    public function report(Request $request): JsonResponse|View
    {
        // Added 'q' (and 'search' just in case) so the backend actually accepts the search input
        $filters = $request->only([
            'q', 'search', 'date', 'date_from', 'date_to', 'employee_id',
            'store_id', 'status', 'department_id', 'per_page',
        ]);

        $report = $this->attendanceService->getReport($filters);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $report]);
        }

        $companyId = $request->user()->company_id;
        $storeId = active_store()?->id;
        $employees = Employee::active()
            ->where('company_id', $companyId)
            ->when($storeId, fn ($q) => $q->forStore($storeId))
            ->with('user')
            ->get();
        $departments = Department::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        $stores = Store::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('admin.hrm.attendance.report', compact('report', 'employees', 'departments', 'stores', 'filters'));
    }

    // ── Export Excel ──

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $period = $request->input('period', 'today');
        $filters = $request->only(['department_id', 'store_id', 'status', 'employee_id']);
        $resolved = $this->exportService->resolvePeriod($period, $request->input('date_from'), $request->input('date_to'));
        $filters = array_merge($filters, [
            'date_from' => $resolved['date_from'],
            'date_to' => $resolved['date_to'],
        ]);

        $companyId = $request->user()->company_id;
        $company = $request->user()->company;
        $dates = $this->exportService->buildDateRange($resolved['date_from'], $resolved['date_to']);
        $data = $this->exportService->buildCalendarData($companyId, $filters);
        $filename = 'attendance-'.$period.'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new AttendanceCalendarExport(
                company: $company,
                employees: $data['employees'],
                attendanceLookup: $data['lookup'],
                dates: $dates,
                periodLabel: $resolved['label'],
                periodType: $period,
            ),
            $filename
        );
    }

    // ── Export PDF ──

    public function exportPdf(Request $request): Response
    {
        $period = $request->input('period', 'today');
        $filters = $request->only(['department_id', 'store_id', 'status', 'employee_id']);
        $resolved = $this->exportService->resolvePeriod($period, $request->input('date_from'), $request->input('date_to'));
        $filters = array_merge($filters, [
            'date_from' => $resolved['date_from'],
            'date_to' => $resolved['date_to'],
        ]);

        $records = $this->exportService->buildQuery($request->user()->company_id, $filters)->get();

        $summary = [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
        ];

        $pdf = Pdf::loadView('admin.hrm.attendance.pdf-export', [
            'records' => $records,
            'summary' => $summary,
            'periodLabel' => $resolved['label'],
            'company' => $request->user()->company,
            'generatedAt' => now()->format('d M Y, h:i A'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('attendance-'.$period.'-'.now()->format('Y-m-d').'.pdf');
    }

    // ── Location Review (approve/reject GPS-fallback flagged check-in/check-out) ──

    public function reviewLocation(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'in:check_in,check_out'],
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ]);

        try {
            $attendance = $this->attendanceService->reviewLocation(
                $attendance->id,
                $validated['field'],
                $validated['decision'],
                $validated['reason'] ?? null,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => $validated['decision'] === 'approve' ? 'Location approved.' : 'Attendance marked Absent.',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Override ──

    public function override(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:present,absent,late,half_day,on_leave,holiday,week_off'],
            'check_in_time' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'check_out_time' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $attendance = $this->attendanceService->override($attendance->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Attendance overridden.',
                'data' => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
