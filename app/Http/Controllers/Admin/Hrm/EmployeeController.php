<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Rules\TenantExists;
use App\Http\Requests\Admin\Hrm\StoreEmployeeRequest;
use App\Http\Requests\Admin\Hrm\UpdateEmployeeRequest;
use App\Models\Hrm\Department;
use App\Models\Hrm\Designation;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeeSalaryStructure;
use App\Models\Hrm\SalaryComponent;
use App\Models\Hrm\Shift;
use App\Models\User;

use App\Services\Hrm\EmployeeService;
use App\Services\Hrm\Payroll\PayrollService;
use App\Services\Hrm\Payroll\Data\StructureLine;
use App\Services\Hrm\Payroll\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index(Request $request)
    {                
        $query = Employee::with(['user', 'department', 'designation', 'store']);            

        // Store awareness: HRM is scoped to the currently active store
        // (the one in the store switcher). Multi-store tenants see only the
        // employees of the store they're viewing; switching stores swaps the data.
        $activeStore = active_store();
        if ($activeStore) {
            $query->where('store_id', $activeStore->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $employees = $query->orderBy('employee_code')
            ->paginate(25)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $employees]);
        }

        $departments = Department::active()->ordered()->get();
        $designations = Designation::active()->ordered()->get();
        
        // 3. Use your helper to fetch only the stores the user is authorized to see        
        $stores = auth_stores()->get(); 
        
        $canAddMore = check_plan_limit('employees');

        return view('admin.hrm.employees.index', compact('employees', 'departments', 'designations', 'stores', 'canAddMore'));
    }

    public function create()
    {
        if (! check_plan_limit('employees')) {
            return redirect()->route('admin.hrm.employees.index')
                ->with('error', 'You have reached your subscription limit for employees. Please upgrade your plan to add more.');
        }

        $departments = Department::active()->ordered()->get();
        $designations = Designation::active()->ordered()->get();
        $shifts = Shift::active()->ordered()->get();
        $stores = auth_stores()->get();
        // Managers are employees — reporting_to is an HR hierarchy, not a
        // system-user relationship. A consultant with no HR record cannot
        // be someone's reporting manager.
         $managerOptions = Employee::active()
            ->with('user:id,name')
            ->get()
            ->mapWithKeys(fn ($emp) => [
                (string) $emp->id => trim(($emp->user?->name ?? 'Unnamed').' ('.$emp->employee_code.')'),
            ])
            ->all();

        // People with a login but no HR profile yet — lets the company admin
        // enable their own attendance without creating a second account.
        // The picker loads its options over AJAX, so no user list is embedded
        // in the page. Tenants with hundreds of logins would otherwise ship a
        // huge select on every form render.
        //
        // Deep link from the Users screen ("Create HR Profile") arrives with
        // ?user_id=X so the picker can open pre-selected.
        $preselectedUser = null;

        if ($requestedUserId = request('user_id')) {
            $preselectedUser = $this->linkableUsersQuery()
                ->whereKey($requestedUserId)
                ->first(['id', 'name', 'email']);
        }

        return view('admin.hrm.employees.create', compact(
            'departments', 'designations', 'shifts', 'stores',
            'managerOptions',
            'preselectedUser',
        ));
    }

    public function store(StoreEmployeeRequest $request)
    {
        if (! check_plan_limit('employees')) {
            $message = 'Employee limit reached for your current plan. Please upgrade to add more employees.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['error' => $message]);
        }

        try {
            $employee = $this->employeeService->create($request->validated());

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Employee created.', 'data' => $employee]);
            }

            return redirect()->route('admin.hrm.employees.show', $employee)
                ->with('success', 'Employee created successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Stream an employee identity document (Aadhaar / PAN scan).
     *
     * Gated on employees.view by the route. $type is already constrained to
     * id_proof|address_proof there, and re-checked here so the property read
     * stays safe even if this method is ever called from elsewhere.
     *
     * Route-model binding resolves through Tenantable's global scope, so an
     * employee id from another company 404s before reaching this body.
     */
    public function downloadDocument(Employee $employee, string $type)
    {
        abort_unless(in_array($type, ['id_proof', 'address_proof'], true), 404);

        $path = $employee->{$type};

        abort_if(! $path, 404, 'No document uploaded for this employee.');
        abort_if(! Storage::disk('local')->exists($path), 404, 'File not found.');

        return response()->file(Storage::disk('local')->path($path));
    }

    public function show(Employee $employee)
    {
        // The manager's name lives on their User, not on the Employee row —
        // full_name reads through that relation, so it must be eager loaded.
        $employee->load([
            'user', 'user.stores', 'department', 'designation', 'shift', 'store',
            'reportingManager.user:id,name', 'subordinates.user',
        ]);

        $salaryStructures = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->with(['salaryComponent', 'percentageOfComponent'])
            ->orderBy('is_active', 'desc')
            ->get();

        $salaryComponents = SalaryComponent::where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return view('admin.hrm.employees.show', compact('employee', 'salaryStructures', 'salaryComponents'));
    }

    public function salaryStructures(Employee $employee)
    {
        $structures = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->with(['salaryComponent', 'percentageOfComponent'])
            ->orderBy('is_active', 'desc')
            ->get();

        return response()->json(['data' => $structures]);
    }

    /**
     * What this structure pays over a full period, computed by the payroll
     * engine rather than by the page.
     *
     * The salary screen used to total the components in JavaScript with its
     * own formula, which ignored percentage bases and disagreed with the
     * figure payroll actually produced. Routing it through PayrollCalculator
     * means HR is shown the engine's answer, including its errors.
     */
    public function previewSalaryStructure(Employee $employee, PayrollCalculator $calculator, PayrollService $payrollService): JsonResponse
    {
        $structures = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->active()
            ->effectiveFor(now())
            ->with(['salaryComponent', 'percentageOfComponent'])
            ->get();

        $lines = StructureLine::fromModels($structures);

        if (empty($lines)) {
            return response()->json(['success' => true, 'data' => null]);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $calculator->preview($lines, $payrollService->wageTypeFor($employee))->toArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            // A structure that cannot be calculated is worth surfacing here:
            // it is exactly what would make payroll fail at month end.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeSalaryStructure(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            // Plain `exists` applies neither the tenant scope nor SoftDeletes,
            // so it would accept another company's component.
            'salary_component_id' => ['required', TenantExists::make('salary_components', softDeletes: true)],
            'calculation_type' => ['required', 'in:fixed,percentage'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'percentage_of_component_id' => [
                'nullable',
                'required_if:calculation_type,percentage',
                'different:salary_component_id',
                TenantExists::make('salary_components', softDeletes: true),
            ],
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['is_active'] = true;
        // Default effective_from to start of current month so it covers the current payroll period
        if (empty($validated['effective_from'])) {
            $validated['effective_from'] = now()->startOfMonth()->toDateString();
        }

        // Close any existing row for this component rather than merely
        // deactivating it. Leaving effective_to null recorded a period that
        // never ended, so the history could not say what was paid when.
        EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->where('salary_component_id', $validated['salary_component_id'])
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'effective_to' => Carbon::parse($validated['effective_from'])->subDay()->toDateString(),
            ]);

        $structure = EmployeeSalaryStructure::create($validated);
        $structure->load('salaryComponent');

        return response()->json(['message' => 'Component added to salary structure.', 'data' => $structure], 201);
    }

    /**
     * Correct a component already on this employee's structure.
     *
     * Without this the only way to fix a wrong amount or effective date was to
     * remove the row and add it again — and effective_from defaults to the
     * start of the current month, so a mistake made late in the month could
     * not be re-entered for an earlier one at all.
     *
     * The component itself is not editable: swapping it would silently make
     * this a different line. Remove and add for that.
     */
    public function updateSalaryStructure(Request $request, Employee $employee, EmployeeSalaryStructure $structure): JsonResponse
    {
        abort_if($structure->employee_id !== $employee->id, 403);

        $validated = $request->validate([
            'calculation_type' => ['required', 'in:fixed,percentage'],
            'amount' => ['required', 'numeric', 'min:0'],
            'percentage_of_component_id' => [
                'nullable',
                'required_if:calculation_type,percentage',
                // A component cannot be a percentage of itself.
                Rule::notIn([$structure->salary_component_id]),
                TenantExists::make('salary_components', softDeletes: true),
            ],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
        ]);

        // A percentage chain that cannot be resolved is rejected here rather
        // than at month end. Left to payroll, a two-step cycle (A on B, B on A)
        // passes the self-reference check above and only surfaces as a failed
        // payslip weeks later.
        $this->guardResolvableStructure($employee, $structure, $validated);

        $structure->update($validated);
        $structure->load(['salaryComponent', 'percentageOfComponent']);

        return response()->json(['message' => 'Salary component updated.', 'data' => $structure]);
    }

    /**
     * Run the proposed structure through the payroll engine and refuse it if
     * the engine cannot compute it.
     */
    protected function guardResolvableStructure(Employee $employee, EmployeeSalaryStructure $structure, array $changes): void
    {
        $structures = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->active()
            ->with(['salaryComponent', 'percentageOfComponent'])
            ->get()
            ->map(function (EmployeeSalaryStructure $row) use ($structure, $changes) {
                if ($row->id === $structure->id) {
                    $row->fill($changes);
                }

                return $row;
            });

        try {
            app(PayrollCalculator::class)->preview(StructureLine::fromModels($structures));
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
    }

    public function destroySalaryStructure(Employee $employee, EmployeeSalaryStructure $structure)
    {
        abort_if($structure->employee_id !== $employee->id, 403);
        $structure->delete();

        return response()->json(['message' => 'Component removed from salary structure.']);
    }

    /**
     * AJAX: users inside this company who can log in but have no HR profile.
     *
     * With no search term this returns the four most recently created logins,
     * which is what the picker shows on focus — a new hire added minutes ago
     * on the Users screen is almost always the one being linked.
     */
    public function linkableUsers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = $this->linkableUsersQuery();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orderBy('name')->limit(10);
        } else {
            $query->latest('id')->limit(4);
        }

        $users = $query->get(['id', 'name', 'email']);

        // People who worked here before. Linking them restores that record
        // rather than starting a new one, so the picker says so up front.
        $formerIds = Employee::onlyTrashed()
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('employee_code', 'user_id');

        return response()->json([
            'data' => $users->map(fn (User $user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'roles'      => $user->roles->pluck('name')->values(),
                'modules'    => $user->modules->pluck('name')->values(),
                'former'     => $formerIds->has($user->id),
                'formerCode' => $formerIds->get($user->id),
            ])->values(),
        ]);
    }

    /**
     * Shared definition of "linkable": an internal login, in this company,
     * that does not already own an employee profile. Used by both the AJAX
     * picker and the ?user_id= deep-link lookup so the two can never disagree.
     */
    protected function linkableUsersQuery()
    {
        return User::internal()
            ->whereDoesntHave('employee')
            ->with(['roles:id,name', 'modules:id,name']);
    }

    /**
     * AJAX: check if employee_code is available within this company.
     * Excludes the given employee ID so current holder doesn't self-conflict.
     */
    public function checkCode(Request $request): JsonResponse
    {
        $companyId  =Auth::user()->company_id;
        $code       = trim((string) $request->query('code', ''));
        $excludeId  = (int) $request->query('exclude', 0);

        if ($code === '') {
            return response()->json(['available' => false, 'message' => 'Code cannot be empty.']);
        }

        $taken = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('employee_code', $code)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return response()->json([
            'available' => ! $taken,
            'message'   => $taken ? 'This code is already in use.' : 'Code is available.',
        ]);
    }

    public function edit(Employee $employee)
    {
        $employee->load(['user.roles:id,name', 'user.modules:id,name', 'department', 'designation', 'store']);

        $departments = Department::active()->ordered()->get();
        $designations = Designation::active()->ordered()->get();
        $shifts = Shift::active()->ordered()->get();
        $stores = auth_stores()->get();
        // Managers are employees — reporting_to is an HR hierarchy, not a
        // system-user relationship. A consultant with no HR record cannot
        // be someone's reporting manager.
        $managerOptions = Employee::active()
            ->with('user:id,name')
            ->whereKeyNot($employee->id)
            ->get()
            ->mapWithKeys(fn ($emp) => [
                (string) $emp->id => trim(($emp->user?->name ?? 'Unnamed').' ('.$emp->employee_code.')'),
            ])
            ->all();


        // No account picker on edit — the login is already linked, and it is
        // managed from the Users screen.
        return view('admin.hrm.employees.edit', compact(
            'employee', 'departments', 'designations', 'shifts', 'stores', 'managerOptions'
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        try {
            $employee = $this->employeeService->update($employee, $request->validated());

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Employee updated.', 'data' => $employee]);
            }

            return redirect()->route('admin.hrm.employees.show', $employee)
                ->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            $this->employeeService->delete($employee);

            return response()->json(['success' => true, 'message' => 'Employee deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
