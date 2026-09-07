<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming you handle granular permissions in middleware or controller
        // You could also add `return has_permission('suppliers.create');` here if applicable.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [            
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:100'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'phone')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'state_id' => ['nullable', 'exists:states,id'], // 🌟 REQUIRED for GST routing

            // Indian Compliance
            'gstin' => [
                'nullable',
                'string',
                'max:15',
                // Matches suppliers_company_gstin_unique exactly. Soft-deleted
                // rows are intentionally included, because the index counts
                // them too.
                Rule::unique('suppliers', 'gstin')
                    ->where('company_id', $companyId),
            ],
            'pan' => ['nullable', 'string', 'max:10'],
            'registration_type' => ['required', 'string', 'in:regular,composition,unregistered,sez,overseas'],

            // Banking
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:255'],

            // Financials
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'balance_type' => ['required', 'in:payable,advance'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],

            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure boolean fields are cast correctly before validation
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
