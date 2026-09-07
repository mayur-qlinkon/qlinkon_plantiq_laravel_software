<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route-level middleware handles auth
    }

    public function rules(): array
    {
        return [
            // 0 is allowed — a batch can be fully sold/used up via correction,
            // same as current_quantity reaching 0 via Harvest.
            'current_quantity' => [
                'required',
                'integer',
                'min:0',
                // Same unsignedInteger ceiling as initial_quantity.
                'max:4294967295',
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
            'current_quantity.required' => 'Please enter the corrected quantity.',
            'current_quantity.min'      => 'Quantity cannot be negative.',
            'current_quantity.max'      => 'That quantity is too large. Please check the number.',
        ];
    }
}