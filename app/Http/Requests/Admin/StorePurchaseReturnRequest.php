<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Auto-inject the store_id from the session, mirroring the Purchase module logic
        if (empty($this->store_id)) {
            $this->merge([
                'store_id' => session('store_id'),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Stock is only deducted when the return is finalised ('returned').
            // Drafts don't touch inventory, so skip the availability check.
            if (($this->status ?? null) !== 'returned') {
                return;
            }

            $warehouseId = $this->warehouse_id;

            foreach ((array) ($this->items ?? []) as $index => $item) {
                $skuId = $item['product_sku_id'] ?? null;
                $qty   = (float) ($item['quantity'] ?? 0);

                if (! $skuId || $qty <= 0) {
                    continue;
                }

                // Tenantable trait auto-scopes company_id on this query.
                $available = (float) (\App\Models\ProductStock::query()
                    ->where('product_sku_id', $skuId)
                    ->where('warehouse_id', $warehouseId)
                    ->value('qty') ?? 0);

                if ($qty > $available) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Cannot return {$qty}. Only {$available} available in this warehouse."
                    );
                }
            }
        });
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            // --- Header Info ---
            'purchase_id' => ['required', Rule::exists('purchases', 'id')->where('company_id', $companyId)],
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)],
            'store_id' => ['required', Rule::exists('stores', 'id')->where('company_id', $companyId)],

            'return_date' => ['required', 'date'],
            'supplier_credit_note_number' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:draft,returned'], // 'cancelled' usually done via action, not form

            // --- Indian GST ---
            'tax_type' => ['required', 'string', 'in:cgst_sgst,igst,none'],

            // --- Global Amounts ---
            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            // --- Extras ---
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            // --- Line Items Array ---
            'items' => ['required', 'array', 'min:1'],

            // Critical: Must link to the original purchase line item
            'items.*.purchase_item_id' => [
                'required',
                Rule::exists('purchase_items', 'id')->where('company_id', $companyId),
            ],

            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.product_sku_id' => ['required', Rule::exists('product_skus', 'id')],
            'items.*.unit_id' => [
                'required',
                Rule::exists('units', 'id')->where('company_id', $companyId),
            ],

            // --- Item Values ---
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['required', 'numeric', 'min:0'],

            // --- Item Extras ---
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.return_reason' => ['nullable', 'string', 'in:damaged,wrong_item,excess_quantity,quality_issue,expired,other'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_id.required' => 'The original Purchase Order reference is required.',
            'items.required' => 'You must select at least one product to return.',
            'items.*.quantity.min' => 'Return quantity must be greater than zero.',
            'items.*.purchase_item_id.*' => 'Invalid original purchase item reference.',
            'items.*.return_reason.in' => 'Please select a valid reason for returning the item.',
        ];
    }
}
