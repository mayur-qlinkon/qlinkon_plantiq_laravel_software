<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChallanReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * * NOTE: According to the Model's boot method immutability rules,
     * only 'notes', 'condition', and 'received_by' are editable on the parent.
     * Only 'damage_note' is editable on the items.
     */
    public function rules(): array
    {
        return [
            // Allowed Parent Updates
            'received_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'condition' => ['nullable', 'string', Rule::in(['good', 'damaged', 'partial'])],

            // Allowed Item Updates (If you support editing line-item notes from the same form)
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer', Rule::exists('challan_return_items', 'id')],
            'items.*.damage_note' => ['nullable', 'string'],
        ];
    }

    /**
     * Custom messages for clarity.
     */
    public function messages(): array
    {
        return [
            'items.*.id.exists' => 'The return item you are trying to update does not exist.',
        ];
    }
}
