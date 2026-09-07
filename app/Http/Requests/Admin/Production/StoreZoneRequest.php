<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'production_site_id' => ['required', Rule::exists('production_sites', 'id')->where('company_id', $companyId)],
            'parent_id' => ['nullable', Rule::exists('production_zones', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for the zone.',
            'production_site_id.required' => 'A zone must belong to a production site.',
        ];
    }
}