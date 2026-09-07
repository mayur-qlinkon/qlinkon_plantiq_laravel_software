<?php

namespace App\Http\Requests\Admin\Hrm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\Hrm\Payroll\Data\WageType;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId =Auth::user()->company_id;

        return [
            // Which account strategy the form submitted. Every account field
            // below is required only for the mode it belongs to, so switching
            // modes never leaves the user fighting irrelevant errors.
            'account_mode' => ['required', Rule::in(['new', 'existing'])],

            // ── Mode: existing ──
            'existing_user_id' => [
                'required_if:account_mode,existing',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->whereIn('user_type', ['company_admin', 'internal'])
                ),
                // Archived profiles do not block a re-hire. The service picks
                // the old record back up instead of creating a second one, so
                // only a live profile is a genuine conflict.
                Rule::unique('employees', 'user_id')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],

            // ── Mode: new ──
            'name' => ['required_if:account_mode,new', 'nullable', 'string', 'max:255'],
            'email' => [
                'required_if:account_mode,new',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('company_id', $companyId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->where('company_id', $companyId),
            ],
            'password' => ['required_if:account_mode,new', 'nullable', 'string', 'min:8'],
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => [Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('company_id', $companyId)],
            'shift_id' => ['required', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
            // reporting_to is an HR hierarchy pointing at employees.id — the
            // dropdown supplies employee ids. Validating against users meant
            // every option failed, and in a tenant where some users.id happened
            // to match a valid employees.id it would have passed while storing
            // the wrong reference. UpdateEmployeeRequest already does this.
            'reporting_to' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('employees')->where('company_id', $companyId)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'date_of_joining' => ['required', 'date'],
            'date_of_leaving' => ['nullable', 'date', 'after:date_of_joining'],
            'probation_end_date' => ['nullable', 'date', 'after:date_of_joining'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'intern', 'freelancer'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'terminated', 'on_notice', 'absconding'])],
            // Pay amounts live in the salary structure, not on the employee.
            // salary_type stays because it is a wage basis, not an amount.
            // Hourly is excluded until attendance totals hours worked. Allowing
            // it would let payroll run on a rate it has no quantity for.
            'salary_type' => ['required', Rule::in(WageType::SUPPORTED)],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
            'pan_number' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'aadhaar_number' => ['nullable', 'string', 'size:12', 'regex:/^[0-9]{12}$/'],
            'uan_number' => ['nullable', 'string', 'max:20'],
            'esi_number' => ['nullable', 'string', 'max:20'],
            'pf_number' => ['nullable', 'string', 'max:30'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'id_proof' => ['nullable', 'file', 'max:5120'],
            'address_proof' => ['nullable', 'file', 'max:5120'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_mode.required' => 'Please choose whether to create a new login or use an existing user.',
            'existing_user_id.required_if' => 'Please select the user to link to this employee.',
            'existing_user_id.unique' => 'That user already has an employee profile.',
            'name.required_if' => 'Please provide the employee\'s full name.',
            'email.required_if' => 'An email address is required for the new login.',
            'password.required_if' => 'Please set a login password for the new account.',
            'phone.unique' => 'This phone number is already registered for another user.',
            'email.unique' => 'This email is already in use by another account in your company. Please use a different email.',            
            'password.min' => 'For security, the password must be at least 8 characters long.',
            'store_id.required' => 'Please select a primary branch/store for this employee.',
            'date_of_joining.required' => 'The date of joining is required.',
            'pan_number.regex' => 'The PAN number format seems incorrect. It should be like ABCDE1234F.',
            'aadhaar_number.regex' => 'The Aadhaar number must be exactly 12 digits.',
        ];
    }
}
