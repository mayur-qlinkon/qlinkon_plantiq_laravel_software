<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StoreTagRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;
        $tagId = $this->route('tag')?->id;

        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('crm_tags')
                    ->where('company_id', $companyId)
                    ->ignore($tagId),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A tag with this name already exists.',
            'color.regex' => 'Color must be a valid hex code (e.g. #10b981).',
        ];
    }
}
