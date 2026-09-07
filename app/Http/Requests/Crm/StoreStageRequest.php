<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StoreStageRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;
        $pipelineId = $this->route('pipeline')->id;
        $stageId = $this->route('stage')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('crm_stages')
                    ->where('company_id', $companyId)
                    ->where('crm_pipeline_id', $pipelineId)
                    ->ignore($stageId),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_won' => ['nullable', 'boolean'],
            'is_lost' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A stage with this name already exists in this pipeline.',
            'color.regex' => 'Color must be a valid hex code (e.g. #3b82f6).',
        ];
    }

    /**
     * Business rule: a pipeline can have only one Won and one Lost stage.
     * Checked in controller — not here — because it requires DB query.
     */
}
