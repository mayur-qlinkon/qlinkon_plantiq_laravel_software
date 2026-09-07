<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }
}
