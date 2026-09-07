<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReceiveHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route-level middleware handles auth
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'received_quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'warehouse_id' => [
                'required',
                'integer',
                "exists:warehouses,id,company_id,{$companyId}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'received_quantity.required' => 'Please enter the received quantity.',
            'received_quantity.min' => 'Received quantity must be at least 1.',
            'warehouse_id.required' => 'Please select a warehouse.',
            'warehouse_id.exists' => 'The selected warehouse is invalid.',
        ];
    }
}