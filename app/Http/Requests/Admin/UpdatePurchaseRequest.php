<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Add protection: Ensure they aren't trying to update a purchase that belongs to someone else
        return $this->route('purchase')->company_id === auth()->user()->company_id;
    }

    protected function prepareForValidation()
    {
        if (empty($this->store_id)) {
            $this->merge([
                'store_id' => session('store_id'),
            ]);
        }

        // ✅ Normalize items
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

        // 🌟 FIX: Assign to the $rules variable first, do not return immediately!
        $rules = [
            // --- Header Info ---
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date' => ['nullable', 'date'],

            // Note: If purchase is already 'received', you usually shouldn't be able to revert it to 'draft'
            // easily without reversing stock. This is handled in the controller/service layer.
            // partially_received is not offered: no code path sets
            // quantity_received per line or receives stock for it, so choosing
            // it saved a status that quietly did nothing.
            'status' => ['required', 'string', 'in:draft,ordered,received,cancelled'],

            // --- Global Amounts ---
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

            // 🌟 NEW: The item ID is required ONLY if updating an existing line item.
            // If missing, the backend will treat it as a newly added item.
            'items.*.id' => ['nullable', Rule::exists('purchase_items', 'id')->where('purchase_id', $this->route('purchase')->id)],

            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            // Scoped to the tenant — deductStock() runs against this SKU.
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

        // ✅ BATCH RULES (ONLY IF ENABLED)
        // Now this block will successfully execute and append to the $rules array.
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
            'items.required' => 'You must have at least one product on the purchase order.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
            'items.*.expiry_date.after_or_equal' => 'Expiry date must be after manufacturing date.',
        ];
    }
}
