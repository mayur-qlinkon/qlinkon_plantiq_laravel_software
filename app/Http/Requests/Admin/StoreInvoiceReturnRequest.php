<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreInvoiceReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert checkbox/toggle values to strict booleans
        $this->merge([
            // Unchecked checkboxes are absent from POST; default to false (don't restock unless toggled on).
            'restock' => $this->has('restock')
                ? filter_var($this->restock, FILTER_VALIDATE_BOOLEAN)
                : false,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // 🌟 Core Linkage
            'invoice_id' => [
                'required',
                company_exists('invoices'),
            ],

            'store_id' => [
                'required',
                company_exists('stores'),
            ],

            'warehouse_id' => [
                'required',
                company_exists('warehouses'),
            ],

            'customer_id' => [
                'nullable',
                company_exists('clients'),
            ],

            'customer_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            // 🌟 Return Specifics
            'return_date' => [
                'required',
                'date',
            ],

            'return_type' => [
                'required',
                'in:refund,credit_note,replacement',
            ],

            'return_reason' => [
                'nullable',
                'in:damaged,expired,wrong_item,customer_return,quality_issue,other',
            ],

            'restock' => [
                'boolean',
            ],

            // 🌟 GST & Taxes
            'supply_state' => [
                'required',
                'string',
                'max:100',
            ],

            'gst_treatment' => [
                'required',
                'in:registered,unregistered,composition,overseas,sez',
            ],

            'currency_code' => [
                'nullable',
                'string',
                'max:3',
            ],

            'exchange_rate' => [
                'nullable',
                'numeric',
                'min:0.0001',
            ],

            // 🌟 Global Financials
            'discount_type' => [
                'required',
                'in:fixed,percentage,percent',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'other_charges' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            // 🌟 Notes
            'notes' => [
                'nullable',
                'string',
            ],

            'terms_conditions' => [
                'nullable',
                'string',
            ],

            // 🌟 The Returned Items Array
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.invoice_item_id' => [
                'required',
                // Verify the item exists and actually belongs to the provided invoice
                Rule::exists('invoice_items', 'id')->where('invoice_id', $this->invoice_id),
            ],

            'items.*.product_id' => [
                'nullable',
                company_exists('products'),
            ],

            'items.*.product_sku_id' => [
                'nullable',
                company_exists('product_skus'),
            ],

            'items.*.unit_id' => [
                'nullable',
                company_exists('units'),
            ],

            'items.*.product_name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.sku_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'items.*.hsn_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            // 🌟 Item Math
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.tax_percent' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.tax_type' => [
                'required',
                'in:inclusive,exclusive',
            ],

            'items.*.discount_type' => [
                'required',
                'in:fixed,percentage,percent',
            ],

            'items.*.discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * After the structural rules pass, cross-check each line against remaining returnable capacity.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            InvoiceReturnQuantityValidator::validate(
                validator: $validator,
                items: $items,
                invoiceId: (int) $this->input('invoice_id'),
            );
        });
    }

    public function messages(): array
    {
        return [
            'items.required' => 'You must select at least one item to return.',
            'items.*.quantity.min' => 'Return quantity must be greater than zero.',
            'invoice_id.required' => 'A valid original invoice must be referenced.',
        ];
    }
}