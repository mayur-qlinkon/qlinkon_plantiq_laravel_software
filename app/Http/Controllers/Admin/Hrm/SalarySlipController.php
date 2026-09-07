<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryComponent;
use App\Models\Hrm\SalarySlip;
use App\Services\Hrm\Payroll\Data\ManualAdjustment;
use App\Services\Hrm\Payroll\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalarySlipController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', SalarySlip::class);

        $query = SalarySlip::with(['employee.user', 'employee.department']);

        // Store awareness: a salary slip has no store of its own — it inherits
        // the store of the employee it belongs to. Scoping through that relation
        // keeps payroll consistent with the HRM Employees list.
        $activeStore = active_store();

        if ($activeStore) {
            $query->whereHas('employee', fn ($q) => $q->where('store_id', $activeStore->id));
        }

        // Month and year are independent filters. Requiring both meant that
        // picking just a month, or just a year, silently returned everything.
        if ($request->filled('month')) {
            $query->where('month', (int) $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Status counts for the stat cards. Cloned before pagination so the
        // cards reflect the same filtered set the table shows, and taken to the
        // base query so the eager loads above don't run for an aggregate.
        $stats = (clone $query)->toBase()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $slips = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $slips]);
        }

        // The filter/generate dropdown must offer the same employees the list
        // shows, otherwise HR can pick someone who is not in the active store.
        $employees = Employee::active()
            ->with('user:id,name')
            ->when($activeStore, fn ($q) => $q->where('store_id', $activeStore->id))
            ->get();

        // Flat shape for the searchable picker — the combobox filters this
        // client side, so it must stay small. Same array feeds the filter bar
        // and the generate modal, which keeps the two lists in sync.
        $employeeOptions = $employees->map(fn (Employee $emp) => [
            'id'   => $emp->id,
            'name' => $emp->user?->name ?? 'Unknown',
            'code' => $emp->employee_code ?? 'N/A',
        ])->values();

        return view('admin.hrm.salary-slips.index', compact('slips', 'employees', 'employeeOptions', 'stats'));
    }

    public function show(SalarySlip $salarySlip)
    {
        $this->guardActiveStore($salarySlip);
        $this->authorize('view', $salarySlip);

        $salarySlip->load(['employee.user', 'employee.department', 'employee.designation', 'items', 'generatedByUser', 'approvedByUser']);

        return view('admin.hrm.salary-slips.show', compact('salarySlip'));
    }

    /**
     * Block any slip that belongs to an employee outside the active store.
     * Salary figures are sensitive enough that a guessable URL is not an
     * acceptable way in, even for an otherwise authorised HR user.
     */
    protected function guardActiveStore(SalarySlip $slip): void
    {
        $activeStore = active_store();

        if ($activeStore && $slip->employee?->store_id !== $activeStore->id) {
            abort(404);
        }
    }

    /**
     * Generate salary slip for a single employee or bulk.
     */
    public function generate(Request $request)
    {
        $this->authorize('generate', SalarySlip::class);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            // Company-scoped like the rest of the codebase — a bare
            // exists:employees,id would accept another tenant's employee id.
            'employee_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', Auth::user()->company_id)],
        ]);

        try {
            if (! empty($validated['employee_id'])) {
                $activeStore = active_store();

                $employee = Employee::when(
                    $activeStore,
                    fn ($q) => $q->where('store_id', $activeStore->id)
                )->findOrFail($validated['employee_id']);

                $slip = $this->payrollService->generateSlip($employee, $validated['month'], $validated['year']);

                return response()->json(['success' => true, 'message' => 'Salary slip generated.', 'data' => $slip]);
            }

            // Bulk generation
            $results = $this->payrollService->generateBulk($validated['month'], $validated['year']);

            return response()->json([
                'success' => true,
                'message' => "Generated: {$results['success']}, Failed: {$results['failed']}",
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Manually edit a salary slip's line items while it is still editable
     * (draft / generated). Once approved or paid the slip is locked and
     * this endpoint rejects the request.
     *
     * Expected payload:
     *   items[]: { id?: int, component_name: string, type: earning|deduction, amount: numeric }
     *   round_off?: numeric
     */
    public function update(Request $request, SalarySlip $salarySlip)
    {
        $this->guardActiveStore($salarySlip);
        $this->authorize('update', $salarySlip);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            // SalarySlipItem carries no tenant scope, so a bare exists rule
            // accepted an item id belonging to another slip — or another
            // company. Constraining it to this slip is what makes the rule
            // mean what it reads as.
            'items.*.id' => [
                'nullable',
                'integer',
                Rule::exists('salary_slip_items', 'id')->where('salary_slip_id', $salarySlip->id),
            ],
            'items.*.component_name' => ['required', 'string', 'max:100'],
            'items.*.type' => ['required', Rule::in([SalaryComponent::TYPE_EARNING, SalaryComponent::TYPE_DEDUCTION])],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'round_off' => ['nullable', 'numeric'],
        ]);

        try {
            // Existing rows are read first so an edited line keeps the
            // component it came from. Only the amount is manual; losing the
            // link would make the slip unable to say which component a figure
            // belongs to.
            $existingItems = $salarySlip->items()->get()->keyBy('id');

            $adjustments = [];

            foreach ($validated['items'] as $row) {
                $existing = ! empty($row['id']) ? $existingItems->get($row['id']) : null;
                $amount = round((float) $row['amount'], 2);

                // A line whose amount the administrator changed no longer
                // matches its original working, so the stored explanation
                // ("12% of Basic Salary") would be untrue. It is kept only
                // while the figure is untouched.
                $detail = ($existing && (float) $existing->amount === $amount)
                    ? $existing->calculation_detail
                    : null;

                $adjustments[] = new ManualAdjustment(
                    name: trim($row['component_name']),
                    code: $existing->component_code ?? 'MANUAL-'.Str::upper(Str::random(6)),
                    type: $row['type'],
                    amount: $amount,
                    componentId: $existing->salary_component_id ?? null,
                    detail: $detail,
                );
            }

            // Totals, net and the negative-net guard all come from the same
            // engine the payroll run uses. This endpoint used to add round-off
            // to the net while generateSlip() derived it, so saving a slip
            // unchanged could alter its net salary.
            $slip = $this->payrollService->recalculateManual(
                $salarySlip,
                $adjustments,
                isset($validated['round_off']) ? (float) $validated['round_off'] : null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Salary slip updated.',
                'data' => $slip,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function approve(SalarySlip $salarySlip)
    {
        // Outside the try block: an AuthorizationException caught below would
        // surface as a 422 business error instead of a 403.
        $this->guardActiveStore($salarySlip);
        $this->authorize('approve', $salarySlip);

        try {
            $slip = $this->payrollService->approve($salarySlip);

            return response()->json(['success' => true, 'message' => 'Salary slip approved.', 'data' => $slip]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function markPaid(Request $request, SalarySlip $salarySlip)
    {
        $this->guardActiveStore($salarySlip);
        $this->authorize('markPaid', $salarySlip);

        $validated = $request->validate([
            // Company-scoped: a bare exists rule accepted another tenant's id,
            // which then failed the tenant-scoped lookup in the service and
            // left the slip irreversibly paid with a blank payment method.
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('company_id', Auth::user()->company_id)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['nullable', 'date'],
        ]);

        try {
            $slip = $this->payrollService->markPaid($salarySlip, $validated);

            return response()->json(['success' => true, 'message' => 'Salary slip marked as paid.', 'data' => $slip]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(SalarySlip $salarySlip)
    {
        $this->guardActiveStore($salarySlip);
        $this->authorize('delete', $salarySlip);

        // Approval is a sign-off, and deleting the slip discards it without
        // trace — the next generation can produce different figures under the
        // same slip number with nothing recording that anyone approved the
        // first. Corrections after approval belong in a void/reversal flow
        // that keeps the original; until that exists, deletion stops here.
        if (in_array($salarySlip->status, [SalarySlip::STATUS_APPROVED, SalarySlip::STATUS_PAID], true)) {
            return response()->json([
                'success' => false,
                'message' => $salarySlip->status === SalarySlip::STATUS_PAID
                    ? 'Paid salary slips cannot be deleted.'
                    : 'Approved salary slips cannot be deleted. Cancel the slip instead if it needs to be reissued.',
            ], 422);
        }

        // Permanently delete the related items first
        $salarySlip->items()->forceDelete();

        // Permanently delete the slip to free up the unique index
        $salarySlip->forceDelete();

        return response()->json(['success' => true, 'message' => 'Salary slip deleted successfully.']);
    }

    public function downloadPdf(SalarySlip $salarySlip)
    {
        // The PDF carries the same figures as the detail page, so it needs the
        // same gate — it had neither a store guard nor an authorisation check.
        $this->guardActiveStore($salarySlip);
        $this->authorize('downloadPdf', $salarySlip);

        $salarySlip->load(['employee.user', 'employee.department', 'employee.designation', 'items']);

        $pdf = Pdf::loadView('admin.hrm.salary-slips.pdf', compact('salarySlip'))
            ->setOption(['defaultFont' => 'DejaVu Sans']); // Ensures ₹ is supported globally

        return $pdf->download("salary-slip-{$salarySlip->slip_number}.pdf");
    }
}
