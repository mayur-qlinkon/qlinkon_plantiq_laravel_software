<?php

namespace App\Http\Requests\Admin\Production;

use App\Models\Production\ProductionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', Rule::in(array_keys(ProductionPlan::PURPOSE_LABELS))],
            'notes' => ['nullable', 'string'],

            // Full item array is optional on update (header-only edits are valid).
            // When sent, it replaces all existing items (sync pattern).
            'items' => ['nullable', 'array'],
            // Same gate as the store request, and more load-bearing here:
            // syncItems() deletes every existing item and re-inserts the
            // payload, so one unscoped edit could replace a whole plan's
            // items with another tenant's products.
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'items.*.target_quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.target_date' => ['required_with:items', 'date'],
            'items.*.remarks' => ['nullable', 'string', 'max:1000'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a title for this Production Plan.',
            'items.*.product_id.required_with' => 'Each item must have a product.',
            'items.*.product_id.exists' => 'One or more selected products do not exist.',
            'items.*.target_quantity.min' => 'Target quantity must be at least 1.',
        ];
    }
}