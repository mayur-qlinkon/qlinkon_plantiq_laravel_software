<?php

namespace App\Http\Requests\Admin\Production;

use App\Enums\Production\BatchSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        // Only 'variable' products need an explicit SKU — 'single' products
        // have exactly one SKU, which the service auto-resolves.
        $isVariableProduct = $this->product_id
            && \App\Models\Product::where('id', $this->product_id)
                ->where('company_id', $companyId)
                ->value('type') === 'variable';

        return [
            'product_id' => [
                'required',
                'integer',
                // Scoped to company — prevents cross-tenant product injection.
                Rule::exists('products', 'id')->where('company_id', $companyId),
            ],

            // Required only for variable products — admin must pick which
            // variant/SKU this batch represents. Single products auto-resolve.
            'product_sku_id' => [
                Rule::requiredIf($isVariableProduct),
                'nullable',
                'integer',
                Rule::exists('product_skus', 'id')->where('product_id', $this->product_id)->where('company_id', $companyId),
            ],

            'source_type' => [
                'required',
                'string',
                Rule::in(BatchSourceType::values()),
            ],

            // Required only when source_type is production_plan or purchase.
            'source_reference_id' => [
                Rule::requiredIf(
                    fn() => BatchSourceType::tryFrom($this->source_type)?->hasReference()
                ),
                'nullable',
                'integer',
                'min:1',
            ],

            // Ceiling matches the unsignedInteger column. Without it, a larger
            // value passed validation and failed at the database instead,
            // surfacing SQLSTATE[22003] to the user.
            'initial_quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],

            // One value per rendered create form. Not required — a caller that
            // omits it simply gets no double-submit protection.
            'idempotency_key' => ['nullable', 'uuid'],

            // Must match the column name exactly — this value is passed straight
            // through to PlantBatch::create() via $request->validated(), so a
            // mismatched key is silently dropped by mass assignment.
            // Future dates are rejected: a batch records plants that have
            // physically arrived, and age is measured from this timestamp.
            'batch_start_datetime' => ['required', 'date', 'before_or_equal:now'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists'             => 'The selected product does not exist.',
            'source_reference_id.required'  => 'A source reference is required for this source type.',
            'initial_quantity.min'          => 'Batch must contain at least 1 plant.',
            'initial_quantity.max'          => 'That quantity is too large. Please check the number of plants.',
            'product_sku_id.required'       => 'Please select which variant/SKU this batch is for.',
            'batch_start_datetime.required' => 'Please set when the plants entered the nursery.',
            'batch_start_datetime.before_or_equal' => 'Batch start date/time cannot be in the future.',
        ];
    }
}