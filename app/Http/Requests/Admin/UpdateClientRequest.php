<?php

namespace App\Http\Requests\Admin;

use App\Enums\Client\RegistrationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $client = $this->route('client'); 

        return $client && $client->company_id === $this->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $clientId = $this->route('client')->id ?? null; 

        return [            
            'name' => ['required', 'string', 'max:255'],
            'client_code' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],

            // STRICT PHONE VALIDATION: Ignore THIS specific client's ID
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients', 'phone')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->ignore($clientId),
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

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Another customer with this phone number already exists in your company.',
        ];
    }
}