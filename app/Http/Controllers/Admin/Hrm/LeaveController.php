<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Events\Hrm\LeaveRequested;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Leave;
use App\Models\Hrm\LeaveType;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['employee_name', 'status', 'leave_type_id', 'from_date', 'to_date', 'per_page']);
        $leaves = $this->leaveService->getList($filters);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $leaves]);
        }

        // Same boundary as the list, otherwise the filter offers people whose
        // records can never appear in the results.
        $employees = Employee::active()
            ->with('user')
            ->when(active_store(), fn ($q, $s) => $q->where('store_id', $s->id))
            ->get();

        $leaveTypes = LeaveType::active()->ordered()->get();

        return view('admin.hrm.leaves.index', compact('leaves', 'employees', 'leaveTypes', 'filters'));
    }

    public function create()
    {
        // A leave cannot be filed for someone outside the store being viewed.
        $employees = Employee::active()
            ->with('user')
            ->when(active_store(), fn ($q, $s) => $q->where('store_id', $s->id))
            ->get();

        $leaveTypes = LeaveType::active()->ordered()->get();

        return view('admin.hrm.leaves.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'total_days' => ['required', 'numeric', 'min:0.5'],
            'day_type' => ['required', Rule::in(['full_day', 'first_half', 'second_half'])],
            'reason' => ['required', 'string'],
            'document' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('document')) {
            $validated['document'] = $request->file('document')->store('hrm/leave-documents', 'local');
        }

        try {
            $leave = $this->leaveService->apply($validated);

            // ✅ Fire event OUTSIDE wantsJson — fires for BOTH form POST and JSON
            Log::info('[LeaveRequested] Event fired', [
                'leave_id' => $leave->id,
                'company_id' => $leave->company_id,
                'employee_id' => $leave->employee_id,
            ]);
            event(new LeaveRequested($leave));

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Leave request submitted.', 'data' => $leave]);
            }

            return redirect()->route('admin.hrm.leaves.index')
                ->with('success', 'Leave request submitted successfully.');

        } catch (\Exception $e) {
            Log::error('[LeaveRequested] Store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Leave $leave)
    {
        $this->guardActiveStore($leave);
        $this->authorize('view', $leave);
        $leave->load(['employee.user', 'leaveType', 'approvedByUser']);

        return view('admin.hrm.leaves.show', compact('leave'));
    }

    /**
     * Stream a leave document for an HR admin reviewing the request.
     *
     * guardActiveStore() is the same check show() applies — the reviewer must
     * be scoped to the store the leave belongs to. The file itself sits on the
     * private disk, so this action is the only way to reach it.
     */
    public function downloadDocument(Leave $leave)
    {
        $this->guardActiveStore($leave);
        // The document is a medical or personal certificate — same visibility
        // rule as the request it belongs to.
        $this->authorize('view', $leave);

        abort_if(! $leave->document, 404, 'No document attached to this leave request.');
        abort_if(! Storage::disk('local')->exists($leave->document), 404, 'File not found.');

        return response()->file(Storage::disk('local')->path($leave->document));
    }

    public function approve(Request $request, Leave $leave)
    {
        // Outside the try block on purpose: an AuthorizationException caught
        // below would surface as a 422 business error instead of a 403.
        $this->guardActiveStore($leave);
        $this->authorize('approve', $leave);

        try {
            $leave = $this->leaveService->approve($leave, $request->input('remarks'));

            return response()->json(['success' => true, 'message' => 'Leave approved.', 'data' => $leave]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function reject(Request $request, Leave $leave)
    {
        $this->guardActiveStore($leave);
        $this->authorize('reject', $leave);

        try {
            $leave = $this->leaveService->reject($leave, $request->input('remarks'));

            return response()->json(['success' => true, 'message' => 'Leave rejected.', 'data' => $leave]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, Leave $leave)
    {
        $request->validate(['reason' => ['required', 'string']]);

        $this->guardActiveStore($leave);
        $this->authorize('cancel', $leave);

        try {
            $leave = $this->leaveService->cancel($leave, $request->input('reason'));

            return response()->json(['success' => true, 'message' => 'Leave cancelled.', 'data' => $leave]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function balances(Employee $employee, Request $request)
    {
        $year = $request->input('year', date('Y'));
        $balances = $this->leaveService->getBalances($employee->id, (int) $year);

        return response()->json(['success' => true, 'data' => $balances]);
    }
    /**
     * Block any record whose employee sits outside the active store.
     *
     * List scoping only hides rows; without this a guessable URL still lets
     * one branch approve another branch's leave.
     */
    protected function guardActiveStore(Leave $leave): void
    {
        $storeId = active_store()?->id;

        if ($storeId && $leave->employee?->store_id !== $storeId) {
            abort(404);
        }
    }
}
