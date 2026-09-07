<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'store_id' => ['nullable', Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a name for the production site.',
        ];
    }
}