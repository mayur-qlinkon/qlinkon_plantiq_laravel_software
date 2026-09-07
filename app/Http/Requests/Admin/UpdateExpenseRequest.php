<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Fallback to 0 if the browser somehow strips the tax percent
            'tax_percent' => $this->input('tax_percent', 0),            
        ]);
    }

    public function rules(): array
    {
        return [            
            'expense_category_id' => ['sometimes', 'required', 'integer', Rule::exists('expense_categories', 'id')],

            'merchant_name' => ['sometimes', 'required', 'string', 'max:255'],
            'merchant_gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'expense_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],

            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'tax_type' => ['sometimes', 'required', 'string', Rule::in(['cgst_sgst', 'igst', 'none'])],
            'tax_percent' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'base_amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],

            'is_reimbursable' => ['nullable', 'boolean'],
            'is_billable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_gstin.regex' => 'The GSTIN format is invalid. Please enter a valid 15-character Indian GSTIN.',
        ];
    }
}
