<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class CompanyOnboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware handles super_admin check
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subdomain' => filled($this->subdomain)
                ? strtolower(trim($this->subdomain))
                : null,
            'domain' => filled($this->domain)
                ? strtolower(explode('/', preg_replace('#^https?://#i', '', trim($this->domain)))[0])
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            // ── Company ──────────────────────────────────────────
            'company_name'  => ['required', 'string', 'max:150'],
            'company_email' => ['required', 'email', 'max:150'],
            'slug'          => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,slug'],
            'subdomain'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,subdomain',
                                'not_in:www,app,api,admin,mail,cdn,static,assets,blog,help,support,status,dashboard'],
            'domain'        => ['nullable', 'string', 'max:255', 'regex:/^([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/', 'unique:companies,domain'],
            'phone'         => ['nullable', 'string', 'max:10'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state_id'      => ['nullable', 'exists:states,id'],
            'gst_number'    => ['nullable', 'string', 'max:15'],
            'is_active'     => ['nullable', 'boolean'],

            // ── Owner ─────────────────────────────────────────────
            'owner_name'     => ['required', 'string', 'max:100'],
            'owner_email'    => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],

            // ── Plan (optional at create time) ────────────────────
            'plan_name'            => ['nullable', 'string', 'max:150', 'required_with:plan_price'],
            'plan_description'     => ['nullable', 'string', 'max:255'],
            'plan_price'           => ['nullable', 'numeric', 'min:0'],
            'billing_cycle'        => ['nullable', 'string', 'in:monthly,yearly,lifetime'],
            'trial_days'           => ['nullable', 'integer', 'min:0'],
            'user_limit'           => ['nullable', 'integer', 'min:1'],
            'store_limit'          => ['nullable', 'integer', 'min:1'],
            'product_limit'        => ['nullable', 'integer', 'min:1'],
            'employee_limit'       => ['nullable', 'integer', 'min:1'],
            'ocr_scan_limit'       => ['nullable', 'integer', 'min:0'],
            'ai_chat_daily_limit'  => ['nullable', 'integer', 'min:0'],
            'ai_token_daily_limit' => ['nullable', 'integer', 'min:-1'],
            'modules'              => ['nullable', 'array'],
            'modules.*'            => ['exists:modules,id'],
            'module_seats.*'       => ['nullable', 'integer', 'min:1'],

            // ── Subscription ──────────────────────────────────────
            'starts_at'     => ['nullable', 'date'],
            'expires_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sub_is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'         => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique'        => 'This slug is already taken.',
            'subdomain.regex'    => 'Subdomain may only contain lowercase letters, numbers, and hyphens.',
            'subdomain.unique'   => 'This subdomain is already taken.',
            'subdomain.not_in'   => 'This subdomain is reserved.',
            'domain.regex'       => 'Enter a valid domain like clientshop.com (no http:// needed).',
            'domain.unique'      => 'This domain is already linked to another company.',
            'plan_name.required_with' => 'Plan name is required when price is set.',
        ];
    }
}