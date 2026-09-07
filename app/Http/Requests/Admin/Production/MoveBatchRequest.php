<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MoveBatchRequest extends FormRequest
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
                // Scoped to company — worker cannot move another company's batch
                "exists:production_plant_batches,id,company_id,{$companyId},deleted_at,NULL",
            ],

            'growing_space_id' => [
                'required',
                'integer',
                "exists:production_growing_spaces,id,company_id,{$companyId},deleted_at,NULL",
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
            'growing_space_id.required' => 'Please select a destination space.',
            'growing_space_id.exists' => 'The selected destination space is invalid.',
        ];
    }
}