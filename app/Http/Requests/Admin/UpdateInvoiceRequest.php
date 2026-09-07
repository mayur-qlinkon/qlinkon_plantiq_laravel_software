<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only drafts are editable. Cancelled + confirmed invoices are locked.
        $invoice = $this->route('invoice');

        if ($invoice && in_array($invoice->status, ['cancelled', 'confirmed'], true)) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $supplyStateRaw = $this->input('supply_state');

        if ($supplyStateRaw && is_numeric($supplyStateRaw)) {
            $stateName = \App\Models\State::find((int) $supplyStateRaw)?->name;

            if ($stateName) {
                $this->merge([
                    'supply_state' => $stateName,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Transaction Header
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

            'supply_state' => [
                'required',
                'string',
                'max:100',
            ],

            'gst_treatment' => [
                'required',
                'string',
                'in:registered,unregistered,composition,overseas,sez',
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:invoice_date',
            ],

            'status' => [
                'nullable',
                'in:draft,confirmed',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'terms_conditions' => [
                'nullable',
                'string',
            ],

            // Global Financials
            'shipping_charge' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_tax_rate' => [
                'nullable',
                'numeric',
                'in:0,5,12,18,28',
            ],

            'discount_type' => [
                'nullable',
                'in:fixed,percentage',
            ],

            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when(
                    fn () => in_array($this->input('discount_type'), ['percent', 'percentage'], true),
                    ['max:100']
                ),
            ],

            // Payment Receipt Data
            'payment_method_id' => [
                'required_if:amount_paid,>0',
                'nullable',
                company_exists('payment_methods'),
            ],

            'amount_paid' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            // Items Array Validation
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_sku_id' => [
                'required',
                company_exists('product_skus'),
            ],

            'items.*.unit_id' => [
                'required',
                company_exists('units'),
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

            // String, not integer — HSN codes carry leading zeros ("06029000")
            // that a numeric cast would silently strip.
            'items.*.hsn_code' => [
                'nullable',
                'string',
                'max:8',
            ],

            'items.*.tax_percent' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'items.*.tax_type' => [
                'required',
                'in:inclusive,exclusive',
            ],

            'items.*.discount_type' => [
                'required',
                'in:fixed,percentage',
            ],

            'items.*.discount_value' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Cross-field discount guards.
     *
     * A discount that exceeds the value it is deducted from produces a zero
     * grand total on an invoice whose stock has already left the warehouse.
     * The service clamps this defensively; here we reject it outright so the
     * user sees what went wrong instead of silently getting a different bill.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $itemsTotal = 0.0;

            foreach ($items as $index => $item) {
                $lineBase = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
                $value    = (float) ($item['discount_value'] ?? 0);
                $isPct    = in_array($item['discount_type'] ?? 'fixed', ['percent', 'percentage'], true);

                if ($isPct) {
                    if ($value > 100) {
                        $validator->errors()->add(
                            "items.{$index}.discount_value",
                            'The discount percentage cannot exceed 100%.'
                        );
                    }
                    $itemsTotal += $lineBase - ($lineBase * (min($value, 100) / 100));
                } else {
                    if ($value > $lineBase) {
                        $validator->errors()->add(
                            "items.{$index}.discount_value",
                            'The discount cannot be greater than the line total.'
                        );
                    }
                    $itemsTotal += $lineBase - min($value, $lineBase);
                }
            }

            // Global fixed discount is measured against the post-line-discount total.
            $globalValue = (float) $this->input('discount_value', 0);
            $globalIsPct = in_array($this->input('discount_type'), ['percent', 'percentage'], true);

            if (! $globalIsPct && $globalValue > $itemsTotal) {
                $validator->errors()->add(
                    'discount_value',
                    'The overall discount cannot be greater than the invoice total.'
                );
            }
        });
    }

    /**
     * Custom error messages for better UX.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'You must add at least one product to the invoice.',
            'items.*.unit_price.min' => 'The unit price cannot be negative.',
            'items.*.quantity.min' => 'The quantity must be greater than zero.',
            'customer_id.required_without' => 'Please select a customer or provide a guest name.',
        ];
    }
}