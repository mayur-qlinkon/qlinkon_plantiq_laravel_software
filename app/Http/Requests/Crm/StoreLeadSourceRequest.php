<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StoreLeadSourceRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreLeadSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;
        $sourceId = $this->route('source')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('crm_lead_sources')
                    ->where('company_id', $companyId)
                    ->ignore($sourceId),
            ],
            'icon' => ['nullable', 'string', 'max:60'],  // lucide icon name
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
