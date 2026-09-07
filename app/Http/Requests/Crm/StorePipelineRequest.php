<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StorePipelineRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StorePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;
        $pipelineId = $this->route('pipeline')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('crm_pipelines')
                    ->where('company_id', $companyId)
                    ->ignore($pipelineId),
            ],
            'description' => ['nullable', 'string', 'max:300'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A pipeline with this name already exists.',
        ];
    }
}
