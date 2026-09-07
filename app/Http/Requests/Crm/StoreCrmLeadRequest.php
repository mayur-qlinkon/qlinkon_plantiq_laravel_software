<?php

// ════════════════════════════════════════════════════
//  FILE: app/Http/Requests/Crm/StoreCrmLeadRequest.php
// ════════════════════════════════════════════════════

namespace App\Http\Requests\Crm;

use App\Rules\AssignableUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrmLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Person ──
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'company_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            // ── Pipeline & Stage ──
            'crm_pipeline_id' => [
                'nullable',
                'integer',
                company_exists('crm_pipelines'),
            ],

            'crm_stage_id' => [
                'nullable',
                'integer',
                company_exists('crm_stages'),
            ],

            'crm_lead_source_id' => [
                'nullable',
                'integer',
                'exists:crm_lead_sources,id',
            ],

            // ── Address ──
            'address' => [
                'nullable',
                'string',
                'max:300',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'zip_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            // ── Social ──
            'instagram_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'facebook_id' => [
                'nullable',
                'string',
                'max:100',
            ],

            'google_profile' => [
                'nullable',
                'string',
                'max:255',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            // ── Priority & value ──
            'priority' => [
                'nullable',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'hot',
                ]),
            ],

            'lead_value' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999',
            ],

            // ── Notes ──
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            // ── Relational ──
            'tags' => [
                'nullable',
                'array',
            ],

            'tags.*' => [
                'integer',
            ],

            'assigned_to' => [
                'nullable',
                'integer',
                new AssignableUser,
            ],

            // ── Follow-up ──
            'next_followup_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Lead name is required.',
            'email.email' => 'Please enter a valid email address.',
            'website.url' => 'Please enter a valid URL (including https://).',
            'crm_pipeline_id.exists' => 'Selected pipeline does not exist.',
            'crm_stage_id.exists' => 'Selected stage does not exist.',
            'crm_lead_source_id.exists' => 'Selected source does not exist.',
            'priority.in' => 'Priority must be low, medium, high or hot.',
        ];
    }
}