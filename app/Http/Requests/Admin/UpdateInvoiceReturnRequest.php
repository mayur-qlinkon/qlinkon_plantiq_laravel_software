<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateInvoiceReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation()
    {
        $this->merge([
            // Unchecked checkboxes are absent from POST; default to false (don't restock unless toggled on).
            'restock' => $this->has('restock')
                ? filter_var($this->restock, FILTER_VALIDATE_BOOLEAN)
                : false,
        ]);
    }

    public function rules(): array
    {
        // The rules for updating a return are virtually identical to storing it.
        // We ensure the basic integrity is maintained. We do NOT allow changing the 'invoice_id'
        // on an update, so we exclude it from the update rules to prevent manipulation.

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
            ],

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

            'supply_state' => [
                'required',
                'string',
                'max:100',
            ],

            'gst_treatment' => [
                'required',
                'in:registered,unregistered,composition,overseas,sez',
            ],

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
     * After structural rules pass, cross-check each line against remaining returnable capacity.
     * Excludes the current draft return's own lines so re-saving the same draft is allowed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            // Resource routes bind as `invoice_return`; the confirm custom route uses `invoiceReturn`.
            // Support both so the request class works regardless of which endpoint invoked it.
            $invoiceReturn = $this->route('invoice_return') ?? $this->route('invoiceReturn');

            if (! $invoiceReturn) {
                return;
            }

            InvoiceReturnQuantityValidator::validate(
                validator: $validator,
                items: $items,
                invoiceId: (int) $invoiceReturn->invoice_id,
                excludeReturnId: (int) $invoiceReturn->id,
            );
        });
    }
}