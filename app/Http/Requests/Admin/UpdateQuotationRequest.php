<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 🌟 Security: Prevent updating if already converted to an invoice
        $quotation = $this->route('quotation'); // Assuming route is model-bound

        if ($quotation && $quotation->status === 'converted') {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        // We can reuse the exact same rules as the Store Request
        return [
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
                'required_without:customer_id',
            ],

            'customer_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'customer_gstin' => [
                'nullable',
                'string',
                'max:50',
            ],

            'billing_address' => [
                'nullable',
                'array',
            ],

            'shipping_address' => [
                'nullable',
                'array',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'quotation_date' => [
                'required',
                'date',
            ],

            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:quotation_date',
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

            'supply_state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'gst_treatment' => [
                'required',
                Rule::in([
                    'registered',
                    'unregistered',
                    'composition',
                    'overseas',
                    'sez',
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'sent',
                ]),
            ],

            // Global Financials
            'discount_type' => [
                'required',
                Rule::in([
                    'fixed',
                    'percentage',
                ]),
            ],

            'discount_value' => [
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

            'notes' => [
                'nullable',
                'string',
            ],

            'terms_conditions' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
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
                'max:8',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.tax_type' => [
                'required',
                Rule::in([
                    'inclusive',
                    'exclusive',
                ]),
            ],

            'items.*.tax_percent' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'items.*.discount_type' => [
                'required',
                Rule::in([
                    'fixed',
                    'percentage',
                ]),
            ],

            'items.*.discount_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'You must add at least one item to the quotation.',
            'customer_name.required_without' => 'Please provide a customer name if no existing client is selected.',
        ];
    }
}