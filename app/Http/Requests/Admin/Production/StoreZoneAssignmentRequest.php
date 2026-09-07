<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreZoneAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'zone_id' => ['required', Rule::exists('production_zones', 'id')->where('company_id', $companyId)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select employee.',
            'zone_id.required' => 'Please select zone.',
        ];
    }
}