<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChallanReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authorization is handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            // Parent: Challan Return Details
            'challan_id' => [
                'required',
                'integer',
                Rule::exists('challans', 'id')->where('company_id', $companyId),
            ],
            'return_date' => ['required', 'date'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'condition' => ['required', 'string', Rule::in(['good', 'damaged', 'partial'])],

            // Children: Return Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.challan_item_id' => ['required', 'integer', Rule::exists('challan_items', 'id')],

            // Qty Rules
            'items.*.qty_returned' => ['required', 'numeric', 'min:0.01'],

            // Damaged qty cannot exceed the returned qty for that specific line item
            'items.*.qty_damaged' => ['nullable', 'numeric', 'min:0', 'lte:items.*.qty_returned'],

            'items.*.damage_note' => ['nullable', 'string'],
        ];
    }

    /**
     * Custom messages for specific validation failures.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'You must return at least one item.',
            'items.*.qty_returned.min' => 'Return quantity must be greater than zero.',
            'items.*.qty_damaged.lte' => 'Damaged quantity cannot exceed the returned quantity.',
        ];
    }
}
