<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class CompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        $companyId = $this->route('company')?->id;

        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['required', 'email', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', "unique:companies,slug,{$companyId}"],
            'subdomain' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', "unique:companies,subdomain,{$companyId}", 'not_in:www,app,api,admin,mail,cdn,static,assets,blog,help,support,status,dashboard'],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/', "unique:companies,domain,{$companyId}"],
            'phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'gst_number' => ['nullable', 'string', 'max:15'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Slug is required.',
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already taken by another company.',
        ];
    }
}
