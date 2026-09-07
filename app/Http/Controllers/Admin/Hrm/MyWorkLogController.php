<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Events\Hrm\WorkLogSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Hrm\HrmTask;
use App\Models\Hrm\WorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MyWorkLogController extends Controller
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

        $query = WorkLog::where('employee_id', $employee->id)->with('task');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->forDateRange($request->date_from, $request->date_to);
        }

        $logs = $query->orderByDesc('log_date')->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // My assigned tasks for the task dropdown
        $tasks = HrmTask::whereHas('assignments', fn ($q) => $q->where('employee_id', $employee->id))
            ->whereIn('status', ['pending', 'in_progress', 'in_review'])
            ->orderBy('title')
            ->get();

        // Summary stats
        $totalHours = WorkLog::where('employee_id', $employee->id)->where('status', 'approved')->sum('hours_worked');
        $pendingCount = WorkLog::where('employee_id', $employee->id)->where('status', 'submitted')->count();
        $rejectedCount = WorkLog::where('employee_id', $employee->id)->where('status', 'rejected')->count();

        return view('admin.hrm.my-work-logs.index', compact(
            'employee', 'logs', 'tasks', 'totalHours', 'pendingCount', 'rejectedCount'
        ));
    }

    public function store(Request $request)
    {
        $employee = $this->myEmployee();

        $validated = $request->validate([
            'log_date'    => ['required', 'date', 'before_or_equal:today'],
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i', 'after:start_time'],
            'description' => ['required', 'string', 'max:2000'],
            'status'      => ['required', 'in:draft,submitted'],
            // Optional link to a task. Restricted to tasks actually assigned to
            // this employee, so the id cannot be used to probe other people's
            // work or another tenant's tasks.
            'hrm_task_id' => [
                'nullable',
                Rule::exists('hrm_task_assignments', 'hrm_task_id')
                    ->where('employee_id', $employee->id),
            ],
        ]);

        // 🌟 AUTOMATIC HOURS CALCULATION
        $hoursWorked = 0.00;
        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $start = \Carbon\Carbon::parse($validated['start_time']);
            $end = \Carbon\Carbon::parse($validated['end_time']);
            $hoursWorked = round($start->diffInMinutes($end) / 60, 2);
        }

        $validated['hours_worked'] = $hoursWorked;
        $validated['employee_id']   = $employee->id;
        $validated['company_id']    = Auth::user()->company_id;
        $validated['hrm_task_id']   = $validated['hrm_task_id'] ?? null;
        $validated['category']      = null; // Clean fallback for schema integrity

        try {
            $log = WorkLog::create($validated);

            // Only on submission — a draft is private to the employee and must
            // not reach an approver's inbox.
            if ($log->status === WorkLog::STATUS_SUBMITTED) {
                event(new WorkLogSubmitted($log->fresh('employee')));
            }

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'submitted'
                    ? 'Work log submitted for approval.'
                    : 'Work log saved as draft.',
                'data' => $log,
            ]);
        } catch (\Throwable $e) {
            // 🌟 ANTI-SILENT FAILURE LOGGING
            Log::error('WorkLog Store Exception Raised: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'payload'     => $validated,
                'trace'       => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to write work log entry. Check system logs for full diagnostics.',
            ], 500);
        }
    }

    public function update(Request $request, WorkLog $workLog)
    {
        $this->authorizeLog($workLog);

        if ($workLog->status !== WorkLog::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft logs can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'log_date'    => ['required', 'date', 'before_or_equal:today'],
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i', 'after:start_time'],
            'description' => ['required', 'string', 'max:2000'],
            'status'      => ['required', 'in:draft,submitted'],
            'hrm_task_id' => [
                'nullable',
                Rule::exists('hrm_task_assignments', 'hrm_task_id')
                    ->where('employee_id', $workLog->employee_id),
            ],
        ]);

        // 🌟 AUTOMATIC HOURS RE-CALCULATION
        $hoursWorked = 0.00;
        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            $start = \Carbon\Carbon::parse($validated['start_time']);
            $end = \Carbon\Carbon::parse($validated['end_time']);
            $hoursWorked = round($start->diffInMinutes($end) / 60, 2);
        }

        $validated['hours_worked'] = $hoursWorked;
        $validated['hrm_task_id']   = $validated['hrm_task_id'] ?? null;
        $validated['category']      = null; // Ensure unused keys remain reset

        try {
            // Captured before the update — the guard above already restricts
            // this method to drafts, so a submitted status here is always a
            // draft-to-submitted transition, never a re-submission.
            $wasDraft = $workLog->status === WorkLog::STATUS_DRAFT;

            $workLog->update($validated);

            if ($wasDraft && $workLog->status === WorkLog::STATUS_SUBMITTED) {
                event(new WorkLogSubmitted($workLog->fresh('employee')));
            }

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'submitted'
                    ? 'Work log submitted for approval.'
                    : 'Work log updated as draft.',
                'data' => $workLog->fresh('task'),
            ]);
        } catch (\Throwable $e) {
            // 🌟 ANTI-SILENT FAILURE LOGGING
            Log::error('WorkLog Update Exception Raised: ' . $e->getMessage(), [
                'log_id'  => $workLog->id,
                'payload' => $validated,
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply work log changes. Check system logs for full diagnostics.',
            ], 500);
        }
    }

    public function destroy(WorkLog $workLog)
    {
        $this->authorizeLog($workLog);

        if ($workLog->status !== WorkLog::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft logs can be deleted.',
            ], 422);
        }

        $workLog->delete();

        return response()->json(['success' => true, 'message' => 'Work log deleted.']);
    }

    protected function authorizeLog(WorkLog $workLog): void
    {
        $employee = $this->myEmployee();
        abort_if($workLog->employee_id !== $employee->id, 403, 'Unauthorized.');
    }
}
