<?php

namespace App\Http\Requests\Admin\Production;

use App\Models\Production\GrowingSpaceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGrowingSpaceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('production_growing_space_types', 'name')->where('company_id', $companyId)],
            'capacity_unit' => ['required', Rule::in(array_keys(GrowingSpaceType::CAPACITY_UNIT_LABELS))],
            'custom_unit_label' => ['required_if:capacity_unit,'.GrowingSpaceType::CAPACITY_UNIT_CUSTOM, 'nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for this growing space type.',
            'name.unique' => 'A growing space type with this name already exists.',
            'capacity_unit.required' => 'Please select a capacity unit.',
            'custom_unit_label.required_if' => 'Please specify a label for the custom unit.',
        ];
    }
}