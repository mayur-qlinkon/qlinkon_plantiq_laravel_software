<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGrowingSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'production_site_id' => ['sometimes', Rule::exists('production_sites', 'id')->where('company_id', $companyId)],
            'zone_id' => ['nullable', Rule::exists('production_zones', 'id')->where('company_id', $companyId)],
            'growing_space_type_id' => ['required', Rule::exists('production_growing_space_types', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for the growing space.',
            'growing_space_type_id.required' => 'Please select a growing space type.',
            'capacity.required' => 'Please enter the capacity for this growing space.',
            'capacity.min' => 'Capacity must be greater than zero.',
        ];
    }
}