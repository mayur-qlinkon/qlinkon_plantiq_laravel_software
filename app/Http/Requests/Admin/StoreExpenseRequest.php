<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware & policies
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_percent' => $this->input('tax_percent', 0),
            'store_id'    => $this->input('store_id') ?: active_store()?->id,
        ]);
    }

    public function rules(): array
    {
        return [            
            'store_id'            => ['required', 'integer', Rule::exists('stores', 'id')],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],

            // Merchant & Reference
            'merchant_name' => ['required', 'string', 'max:255'],
            // 🌟 Pro-Tip: Regex strictly enforces the 15-character Indian GSTIN format
            'merchant_gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],

            // Taxes & Financials
            'currency_code' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'tax_type' => ['required', 'string', Rule::in(['cgst_sgst', 'igst', 'none'])],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'base_amount' => ['required', 'numeric', 'min:0.01'],

            // Workflow
            'is_reimbursable' => ['nullable', 'boolean'],
            'is_billable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Media (Spatie)
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5MB Max
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_gstin.regex' => 'The GSTIN format is invalid. Please enter a valid 15-character Indian GSTIN.',
            'base_amount.min' => 'The expense base amount must be greater than zero.',
        ];
    }
}
