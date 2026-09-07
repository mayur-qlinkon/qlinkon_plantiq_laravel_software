<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (empty($this->store_id)) {
            $this->merge([
                'store_id' => session('store_id'),
            ]);
        }
        // ✅ Clean empty batch fields
        if ($this->has('items')) {
            $items = collect($this->items)->map(function ($item) {
                $item['batch_number'] = $item['batch_number'] ?? null;
                $item['manufacturing_date'] = $item['manufacturing_date'] ?? null;
                $item['expiry_date'] = $item['expiry_date'] ?? null;

                return $item;
            });

            $this->merge(['items' => $items->toArray()]);
        }
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;
        $batchEnabled = batch_enabled();

        // 🌟 FIX: Assign to a variable FIRST, do not return immediately!
        $rules = [
            // --- Header Info ---
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:draft,ordered,received'],

            // --- Global Amounts (Calculated/Input) ---            
            'discount_type' => ['nullable', 'string', 'in:percent,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'round_off' => ['nullable', 'numeric'],

            // --- Extras ---
            'notes' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],

            // --- Line Items Array ---
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            // Scoped to the tenant. An unscoped id let another company's SKU
            // through, and receiveStock() then wrote a stock movement and a new
            // cost onto it.
            'items.*.product_sku_id' => [
                'required',
                Rule::exists('product_skus', 'id')->where('company_id', $companyId),
            ],
            'items.*.unit_id' => ['required', Rule::exists('units', 'id')],

            // --- Item Values ---
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['required', 'string', 'in:percent,fixed'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_type' => ['required', 'string', 'in:inclusive,exclusive'],
        ];

        // 🌟 NOW this logic will actually run!
        if ($batchEnabled) {
            $rules['items.*.batch_number'] = ['nullable', 'string', 'max:100'];
            $rules['items.*.manufacturing_date'] = ['nullable', 'date'];
            $rules['items.*.expiry_date'] = ['nullable', 'date', 'after_or_equal:items.*.manufacturing_date'];
        }

        return $rules; // 🌟 Finally return the complete array
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier before creating the purchase order.',
            'items.required' => 'You must add at least one product to the purchase order.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.unit_cost.min' => 'Unit cost cannot be negative.',
            'items.*.expiry_date.after_or_equal' => 'Expiry date must be after manufacturing date.',
        ];
    }
}
