<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\WorkLog;
use App\Notifications\Hrm\WorkLogStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WorkLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', WorkLog::class);

        $query = WorkLog::with(['employee.user', 'task', 'approvedByUser'])->where('status', '!=', 'draft');

        // Store awareness: work logs have no store_id — they belong to an
        // employee, and the employee belongs to a store. Scoping through that
        // relation matches Leaves and Salary Slips, and also drops logs whose
        // employee has been archived.
        $storeId = active_store()?->id;

        if ($storeId) {
            $query->whereHas('employee', fn ($q) => $q->where('store_id', $storeId));
        }

        // Company-wide visibility is the HR-level grant. Everyone else sees
        // their own logs plus their direct reportees' — mirrors
        // LeaveService::getList(). Gating the approve action alone would still
        // have exposed every colleague's description text through this list.
        if (! has_permission('work_logs.approve_all')) {
            $myEmployeeId = Auth::user()?->employee?->id;

            $query->where(function ($q) use ($myEmployeeId) {
                $q->where('employee_id', $myEmployeeId)
                    ->orWhereHas('employee', fn ($e) => $e->where('reporting_to', $myEmployeeId));
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->forDateRange($request->date_from, $request->date_to);
        }
        if ($request->filled('hrm_task_id')) {
            $query->where('hrm_task_id', $request->hrm_task_id);
        }

        $logs = $query->orderBy('log_date', 'desc')
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $logs]);
        }

        $employees = Employee::active()
            ->with('user')
            ->when(active_store(), fn ($q, $s) => $q->where('store_id', $s->id))
            ->get();

        return view('admin.hrm.work-logs.index', compact('logs', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Company-scoped: a bare exists rule accepted another tenant's id,
            // producing a log stamped with this company_id but pointing at a
            // foreign employee — the relation then resolves to null everywhere.
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id)],
            'hrm_task_id' => ['nullable', 'integer', Rule::exists('hrm_tasks', 'id')->where('company_id', Auth::user()->company_id)],
            'log_date' => ['required', 'date'],
            'hours_worked' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        // Checked after validation so the employee id is known to be ours.
        $this->authorize('create', [WorkLog::class, (int) $validated['employee_id']]);

        $log = WorkLog::create($validated);

        return response()->json(['success' => true, 'message' => 'Work log created.', 'data' => $log]);
    }

    public function update(Request $request, WorkLog $workLog)
    {
        $this->authorize('update', $workLog);

        $validated = $request->validate([
            'hrm_task_id' => ['nullable', 'integer', Rule::exists('hrm_tasks', 'id')->where('company_id', Auth::user()->company_id)],

            'log_date' => ['required', 'date'],
            'hours_worked' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $workLog->update($validated);

        return response()->json(['success' => true, 'message' => 'Work log updated.', 'data' => $workLog]);
    }

    public function destroy(WorkLog $workLog)
    {
        $this->authorize('delete', $workLog);

        $workLog->delete();

        return response()->json(['success' => true, 'message' => 'Work log deleted.']);
    }

    public function approve(Request $request, WorkLog $workLog)
    {
        // Outside any try block: an AuthorizationException must surface as a
        // 403, not be flattened into a business error.
        $this->authorize('approve', $workLog);

        $action = $request->input('action', 'approve');

        if ($action === 'reject') {
            $request->validate(['remarks' => ['required', 'string']]);
            $workLog->update([
                'status' => WorkLog::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_remarks' => $request->input('remarks'),
            ]);
        } else {
            $workLog->update([
                'status' => WorkLog::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_remarks' => $request->input('remarks'),
            ]);
        }

        // Mirrors LeaveService::approve()/reject() — without this the employee
        // only discovers the outcome by opening the page themselves.
        $workLog = $workLog->fresh(['employee.user']);

        if ($workLog->employee?->user) {
            $workLog->employee->user->notify(new WorkLogStatusNotification($workLog));
        }

        return response()->json(['success' => true, 'message' => "Work log {$action}d.", 'data' => $workLog]);
    }
}
