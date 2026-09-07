<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Assuming middleware handles super_admin check
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'subdomain' => filled($this->subdomain) ? strtolower(trim($this->subdomain)) : null,
            'domain'    => filled($this->domain)
                ? strtolower(explode('/', preg_replace('#^https?://#i', '', trim($this->domain)))[0])
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            // Company Fields
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['required', 'email', 'max:150'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,slug'],
            'subdomain' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:companies,subdomain', 'not_in:www,app,api,admin,mail,cdn,static,assets,blog,help,support,status,dashboard'],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/', 'unique:companies,domain'],
            'phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'gst_number' => ['nullable', 'string', 'max:15'],
            'is_active' => ['boolean'],

            // Owner Fields
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_email' => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken by another company.',
            'subdomain.regex' => 'Subdomain may only contain lowercase letters, numbers, and hyphens.',
            'subdomain.unique' => 'This subdomain is already taken by another company.',
            'subdomain.not_in' => 'This subdomain is reserved and cannot be used.',
            'domain.regex' => 'Enter a valid domain like clientshop.com (no http:// or trailing slash).',
            'domain.unique' => 'This domain is already linked to another company.',
        ];
    }
}
