<?php

namespace App\Http\Requests\Admin\Production;

use App\Models\Production\ProductionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionPlanRequest extends FormRequest
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

            // Items are optional on create — a plan can be saved as Draft
            // with no items yet, and items added later.
            'items' => ['nullable', 'array'],
            // Company-scoped, matching StorePlantBatchRequest. A bare exists
            // rule accepted another tenant's product id, and ProductionPlanService
            // writes items with a raw insert() — no model events, no tenant
            // scope — so this rule is the only gate on the value.
            // Soft-deleted products are excluded too: Product uses SoftDeletes,
            // and exists matches archived rows.
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'items.*.target_quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.target_date' => ['required_with:items', 'date', 'after_or_equal:today'],
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
            'items.*.target_date.after_or_equal' => 'Target date cannot be in the past.',
        ];
    }
}