<?php

namespace App\Http\Requests\Admin\Production;

use App\Enums\Production\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreActivityTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            // Null or empty value turns this into a Global Default template
            'product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'activity_type' => ['required', Rule::in(ActivityType::values())],
            'frequency_type' => ['nullable', 'string', Rule::in(['interval', 'daily', 'weekly', 'monthly'])],
            'frequency_value' => ['required', 'integer', 'min:1', 'max:365'],
            'start_after_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'The selected product does not exist.',
            'activity_type.required' => 'Activity type select karna zaroori hai.',
            'activity_type.in' => 'Selected activity type invalid hai.',
            'frequency_value.required' => 'Frequency value (days) batao.',
            'frequency_value.min' => 'Frequency kam se kam 1 din honi chahiye.',
            'start_after_days.min' => 'Start after days minimum 0 hona chahiye.',
        ];
    }
}