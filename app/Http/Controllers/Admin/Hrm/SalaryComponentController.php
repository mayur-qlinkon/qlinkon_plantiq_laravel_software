<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryComponent;
use Illuminate\Http\Request;
use App\Rules\TenantExists;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        $components = SalaryComponent::with('percentageOfComponent')
            ->ordered()
            ->paginate(50)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $components]);
        }

        // The "percentage of" picker must offer every active component, not
        // just the ones on the current page of the paginated table.
        $baseComponents = SalaryComponent::active()->ordered()->get(['id', 'name', 'code']);

        return view('admin.hrm.salary-slips.components', compact('components', 'baseComponents'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('salary_components')->where('company_id', $companyId)],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'role' => ['required', Rule::in(SalaryComponent::ROLES)],
            'description' => ['nullable', 'string'],
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'percentage_of_component_id' => [
                'nullable',
                'required_if:calculation_type,percentage',
                TenantExists::make('salary_components', softDeletes: true),
            ],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'is_statutory' => ['boolean'],
            'appears_on_payslip' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $component = SalaryComponent::create($validated);

        return response()->json(['success' => true, 'message' => 'Salary component created.', 'data' => $component]);
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('salary_components')->where('company_id', $companyId)->ignore($salaryComponent->id)],
            'type' => ['required', Rule::in(['earning', 'deduction'])],
            'role' => ['required', Rule::in(SalaryComponent::ROLES)],
            'description' => ['nullable', 'string'],
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'percentage_of_component_id' => [
                'nullable',
                'required_if:calculation_type,percentage',
                Rule::notIn([$salaryComponent->id]),
                TenantExists::make('salary_components', softDeletes: true),
            ],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'is_statutory' => ['boolean'],
            'appears_on_payslip' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $salaryComponent->update($validated);

        return response()->json(['success' => true, 'message' => 'Salary component updated.', 'data' => $salaryComponent]);
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->employeeStructures()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete component in use by employee salary structures.'], 422);
        }

        $salaryComponent->delete();

        return response()->json(['success' => true, 'message' => 'Salary component deleted.']);
    }
}
