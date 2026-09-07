<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBatchHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route-level middleware handles auth
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'plant_batch_id' => [
                'required',
                'integer',
                // Scoped to company — worker cannot report harvest on another company's batch
                "exists:production_plant_batches,id,company_id,{$companyId},deleted_at,NULL",
            ],

            'quantity_harvested' => [
                'required',
                'integer',
                'min:1',
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                "exists:warehouses,id,company_id,{$companyId}",
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
            'plant_batch_id.required' => 'Please select a batch.',
            'plant_batch_id.exists' => 'This batch is invalid or does not belong to your company.',
            'quantity_harvested.required' => 'Please enter the quantity harvested.',
            'quantity_harvested.min' => 'Quantity harvested must be at least 1.',
            'warehouse_id.exists' => 'The selected warehouse is invalid.',
        ];
    }
}