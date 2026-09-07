<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBatchPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route-level middleware handles auth
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'growing_space_id' => [
                'required',
                'integer',
                // Scoped to company — tenant cannot place into another company's space
                "exists:production_growing_spaces,id,company_id,{$companyId},deleted_at,NULL",
            ],

            // Optional: defaults to now() in the service if omitted.
            // Allowing past dates so managers can record a placement that
            // physically happened earlier in the day.
            'placed_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'growing_space_id.required' => 'Please select a growing space.',
            'growing_space_id.exists'   => 'The selected growing space is invalid or does not belong to your company.',
            'placed_at.before_or_equal' => 'Placement date cannot be in the future.',
        ];
    }
}