<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveBalance;
use App\Models\Hrm\LeaveType;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected LeaveService $leaveService
    ) {}

    /**
     * Show all leave balances for a given year.
     */
    public function index(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));
        $employeeId = $request->input('employee_id');
        $leaveTypeId = $request->input('leave_type_id');

        // Store awareness: leave balances have no store_id — scope via the
        // employee's store to the active store (switcher).
        $storeId = active_store()?->id;

        $query = LeaveBalance::with(['employee.user', 'leaveType'])
            ->where('year', $year)
            ->when($storeId, fn ($q) => $q->whereHas(
                'employee',
                fn ($e) => $e->where('store_id', $storeId)
            ));

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        $balances = $query->orderBy('employee_id')->orderBy('leave_type_id')->get();
        $employees = Employee::active()->with('user')->get();
        $leaveTypes = LeaveType::active()->ordered()->get();

        return view('admin.hrm.leave-balances.index', compact(
            'balances', 'employees', 'leaveTypes', 'year', 'employeeId', 'leaveTypeId'
        ));
    }

    /**
     * Bulk-initialize leave balances for all active employees for a year.
     */
    public function initialize(Request $request)
    {
        $year = (int) $request->input('year', date('Y'));

        $count = $this->leaveService->initializeBalances($year);

        return response()->json([
            'success' => true,
            'message' => $count > 0
                ? "{$count} leave balance record(s) created for {$year}."
                : "All employees already have balances initialized for {$year}.",
            'created' => $count,
        ]);
    }

    /**
     * Carry forward unused balances from previous year.
     */
    public function carryForward(Request $request)
    {
        $toYear = (int) $request->input('to_year', date('Y'));
        $fromYear = $toYear - 1;

        $count = $this->leaveService->carryForward($fromYear, $toYear);

        return response()->json([
            'success' => true,
            'message' => $count > 0
                ? "Carried forward {$count} balance(s) from {$fromYear} to {$toYear}."
                : "Nothing to carry forward from {$fromYear} (no carry-forward leave types or no unused balance).",
            'carried' => $count,
        ]);
    }

    /**
     * Update a single leave balance (allocated + adjustment).
     */
    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        $validated = $request->validate([
            'allocated' => ['required', 'numeric', 'min:0', 'max:365'],
            'adjustment' => ['required', 'numeric', 'min:-365', 'max:365'],
        ]);

        $balance = $this->leaveService->updateBalance(
            $leaveBalance,
            (float) $validated['allocated'],
            (float) $validated['adjustment']
        );

        return response()->json([
            'success' => true,
            'message' => 'Balance updated successfully.',
            'balance' => [
                'id' => $balance->id,
                'allocated' => $balance->allocated,
                'used' => $balance->used,
                'carried_forward' => $balance->carried_forward,
                'adjustment' => $balance->adjustment,
                'available' => $balance->available,
            ],
        ]);
    }
}
