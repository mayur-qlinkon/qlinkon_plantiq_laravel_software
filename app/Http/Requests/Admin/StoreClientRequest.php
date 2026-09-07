<?php

namespace App\Http\Requests\Admin;

use App\Enums\Client\RegistrationType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [            
            'name' => ['required', 'string', 'max:255'],
            'client_code' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],

            // STRICT PHONE VALIDATION: Unique per company, ignoring deleted records
            // Nullable in the schema, so a walk-in whose number nobody asked
            // for can still be recorded by name. The uniqueness rule only runs
            // when a number is actually given — `nullable` stops the chain
            // before it, and MySQL allows any number of NULLs in a unique index.
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients', 'phone')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at'),
            ],

            'gst_number' => ['nullable', 'string', 'max:30'],
            'registration_type' => ['nullable', Rule::enum(RegistrationType::class)],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Custom messages for specific validation failures
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'A customer with this phone number already exists in your company.',
        ];
    }
}