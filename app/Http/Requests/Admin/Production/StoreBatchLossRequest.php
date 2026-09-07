<?php

namespace App\Http\Requests\Admin\Production;

use App\Enums\Production\LossReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBatchLossRequest extends FormRequest
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
                // Scoped to company — worker cannot report loss on another company's batch
                "exists:production_plant_batches,id,company_id,{$companyId},deleted_at,NULL",
            ],

            'quantity_lost' => [
                'required',
                'integer',
                'min:1',
            ],

            'reason' => [
                'required',
                'string',
                'in:'.implode(',', LossReason::values()),
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
            'quantity_lost.required' => 'Please enter the quantity lost.',
            'quantity_lost.min' => 'Quantity lost must be at least 1.',
            'reason.required' => 'Please select a reason.',
            'reason.in' => 'The selected reason is invalid.',
        ];
    }
}