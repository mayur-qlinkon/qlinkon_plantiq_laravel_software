<?php

namespace App\Http\Requests\Admin;

use App\Models\Challan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChallanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 🛡️ SECURITY: Ensure they aren't trying to update a challan from another tenant
        $challan = $this->route('challan');

        return $challan && $challan->company_id === auth()->user()->company_id;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->store_id)) {
            $this->merge([
                'store_id' => session('store_id'),
            ]);
        }

        // Convert any boolean strings to actual booleans for strict validation
        if ($this->has('is_returnable')) {
            $this->merge([
                'is_returnable' => filter_var($this->is_returnable, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $companyId = auth()->user()->company_id;
        $challanId = $this->route('challan')->id;

        return [
            // ── Tenancy & Core ──
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'challan_date' => ['required', 'date'],
            'challan_type' => ['required', 'string', Rule::in(array_keys(Challan::TYPE_LABELS))],
            'direction' => ['required', 'string', Rule::in([Challan::DIRECTION_OUTWARD, Challan::DIRECTION_INWARD])],

            // Allow updating status (business logic/guards should be in Controller/Service)
            'status' => ['sometimes', 'string', Rule::in(array_keys(Challan::STATUS_LABELS))],

            // ── Parties (Smartly Required based on Challan Type) ──
            'client_id' => [
                Rule::requiredIf($this->challan_type === Challan::TYPE_DELIVERY),
                'nullable',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'supplier_id' => [
                Rule::requiredIf(in_array($this->challan_type, [Challan::TYPE_JOB_WORK_OUT, Challan::TYPE_REPAIR_OUT])),
                'nullable',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'branch_store_id' => [
                Rule::requiredIf($this->challan_type === Challan::TYPE_BRANCH_TRANSFER),
                'nullable',
                Rule::exists('stores', 'id')->where('company_id', $companyId),
            ],

            // Walk-in Party Overrides
            'party_name' => ['nullable', 'string', 'max:255'],
            'party_address' => ['nullable', 'string'],
            'party_gst' => ['nullable', 'string', 'max:15'],
            'party_phone' => ['nullable', 'string', 'max:20'],
            'party_state' => ['nullable', 'string', 'max:255'],

            // ── States ──
            'from_state_id' => ['nullable', Rule::exists('states', 'id')],
            'to_state_id' => ['nullable', Rule::exists('states', 'id')],

            // ── Transport ──
            'transport_mode' => ['nullable', 'string', 'max:20'],
            'transport_name' => ['nullable', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'lr_number' => ['nullable', 'string', 'max:50'],
            'eway_bill_number' => ['nullable', 'string', 'max:20'],
            'eway_bill_expiry' => ['nullable', 'date', 'after_or_equal:challan_date'],

            // ── Returns & References ──
            'is_returnable' => ['nullable', 'boolean'],
            'return_due_date' => ['nullable', 'date', 'after_or_equal:challan_date'],
            'source_type' => ['nullable', 'string'],
            'source_id' => ['nullable', 'integer'],

            // ── Notes ──
            'purpose_note' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],

            // ════════════════════════════════════════════════════
            //  LINE ITEMS (Update Logic)
            // ════════════════════════════════════════════════════
            'items' => ['required', 'array', 'min:1'],

            // 🌟 SECURITY: Ensure existing item IDs actually belong to this exact Challan
            'items.*.id' => ['nullable', Rule::exists('challan_items', 'id')->where('challan_id', $challanId)],

            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.product_sku_id' => ['nullable', Rule::exists('product_skus', 'id')],

            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku_label' => ['nullable', 'string', 'max:255'],
            'items.*.sku_code' => ['nullable', 'string', 'max:255'],
            'items.*.hsn_code' => ['nullable', 'string', 'max:20'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],

            'items.*.qty_sent' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // 🌟 NEW: Batch Tracking Validation Rules
            'items.*.batch_id' => ['nullable', 'integer', Rule::exists('product_batches', 'id')],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date' => ['nullable', 'date'],

            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'branch_store_id' => 'branch store',
            'items.*.product_name' => 'product name',
            'items.*.qty_sent' => 'quantity',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'branch_store_id.required' => 'A destination branch store is required for Branch Transfers.',
            'client_id.required' => 'A client is required for Delivery challans.',
            'supplier_id.required' => 'A supplier is required for Job Work or Repair challans.',
            'items.required' => 'You must have at least one item on the challan.',
            'items.*.qty_sent.min' => 'Item quantity must be greater than zero.',
        ];
    }
}
