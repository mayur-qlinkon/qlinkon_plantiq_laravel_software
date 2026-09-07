<?php

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && has_permission('users.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\.\-]+$/',
            ],

            'email' => [
                'required',
                'email:rfc,dns',
                'max:150',
                // Tenant-aware unique check
                Rule::unique('users', 'email')->where('company_id', $companyId),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9\-\+\s\(\)]+$/',
                // Tenant-aware unique check
                Rule::unique('users', 'phone')->where('company_id', $companyId),
            ],

            'password' => [
                'required',
                'string',
                'min:6',
            ],

            'store_ids' => [
                'required',
                'array',
                'min:1',
            ],

            // Platform-level table — intentionally NOT company-scoped.
            'module_ids' => [
                'sometimes',
                'array',
            ],

            'module_ids.*' => [
                'integer',
                'exists:modules,id',
            ],

            'store_ids.*' => [
                'integer',
                company_exists('stores'),
            ],

            'role_id' => [
                'required',

                // Ensure the assigned role actually belongs to this tenant
                // or is a global/system role.
                Rule::exists('roles', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                }),
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    'active',
                    'inactive',
                    'suspended',
                ]),
            ],

            // Only company_admin and internal are creatable from this screen.
            // Temporarily hidden from the UI. Kept as a nullable rule so the
            // field still validates if something posts it, and so re-enabling
            // the selector later is a one-word change.
            'user_type' => [
                'nullable',
                Rule::enum(UserType::class)->only(UserType::assignable()),
            ],

            // Profile & Contact (Optional Fields)
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state_id' => [
                'nullable',
                'integer',
                'exists:states,id',
            ],

            'zip_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            // Image Upload
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * Custom user-friendly error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the user\'s full name.',
            'name.regex' => 'The name can only contain letters, spaces, and hyphens.',
            'email.required' => 'An email address is required for login.',
            'email.unique' => 'A user with this email already exists in your company.',
            'phone.unique' => 'This phone number is already registered to another user.',
            'password.required' => 'A secure password is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.required' => 'Please assign a role to this user.',
            'role_id.exists' => 'The selected role is invalid or unavailable.',
            'store_ids.required' => 'Please assign at least one store to this user.',
            'image.max' => 'The profile image must not be larger than 2MB.',
            'image.mimes' => 'Please upload a valid image file (JPG, PNG, or WEBP).',
        ];
    }
}