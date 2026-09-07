<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StoreActivityRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use App\Models\CrmActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(CrmActivity::TYPES))],
            'description' => ['required', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Activity type is required.',
            'type.in' => 'Invalid activity type.',
            'description.required' => 'Activity description is required.',
        ];
    }
}
