<?php

namespace App\Http\Requests\Admin\Production;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkGenerateGrowingSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'production_site_id' => ['required', Rule::exists('production_sites', 'id')->where('company_id', $companyId)],
            'zone_id' => ['nullable', Rule::exists('production_zones', 'id')->where('company_id', $companyId)],
            'growing_space_type_id' => ['required', Rule::exists('production_growing_space_types', 'id')->where('company_id', $companyId)],
            'capacity' => ['required', 'numeric', 'min:0.01'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'row_start' => ['required', 'integer', 'min:1'],
            'row_end' => ['required', 'integer', 'gte:row_start'],
            'col_start' => ['required', 'integer', 'min:1'],
            'col_end' => ['required', 'integer', 'gte:col_start'],
            'name_template' => ['nullable', 'string', 'max:100'],
            'sort_order_start' => ['nullable', 'integer', 'min:0'],
            'skip_conflicts' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'growing_space_type_id.required' => 'Please select a growing space type.',
            'row_end.gte' => 'End row must be greater than or equal to start row.',
            'col_end.gte' => 'End column must be greater than or equal to start column.',
        ];
    }

    /**
     * Guard against an accidental huge grid (e.g. typo'd range) locking up
     * a shared-hosting PHP worker with no queue to offload to.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rows = ($this->input('row_end') - $this->input('row_start')) + 1;
            $cols = ($this->input('col_end') - $this->input('col_start')) + 1;

            if ($rows > 0 && $cols > 0 && ($rows * $cols) > 500) {
                $validator->errors()->add('row_end', 'This range would generate more than 500 growing spaces in one request. Please generate in smaller batches.');
            }
        });
    }
}