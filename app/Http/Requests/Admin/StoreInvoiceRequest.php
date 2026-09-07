<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We handle authorization via middleware/gates usually
    }
     protected function prepareForValidation(): void
    {
        // ── Resolve supply_state: convert numeric ID → state name ──
        // The <x-state-select> submits a numeric ID as value.
        // The invoice DB column and show blade both expect a plain name (e.g. "Gujarat").
        $supplyStateRaw = $this->input('supply_state');
        if ($supplyStateRaw && is_numeric($supplyStateRaw)) {
            $stateName = \App\Models\State::find((int) $supplyStateRaw)?->name;
            if ($stateName) {
                $this->merge(['supply_state' => $stateName]);
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
                Rule::exists('stores', 'id')
                    ->where('company_id', Auth::user()->company_id),
            ],

            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', Auth::user()->company_id),
            ],
            'customer_id' => ['nullable', company_exists('clients')],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'supply_state'  => ['required', 'string', 'max:100', 'not_regex:/^\\d+$/'],
            'gst_treatment' => ['required', 'string', 'in:registered,unregistered,composition,overseas,sez'],
            'invoice_date'  => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'source' => ['required', 'in:pos,direct,online'],
            'status' => ['nullable', 'in:draft,confirmed'],
            'notes' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],

            // Global Financials
            'shipping_charge'    => ['nullable', 'numeric', 'min:0'],
            'shipping_tax_rate'  => ['nullable', 'numeric', 'in:0,5,12,18,28'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                // A percentage discount above 100 has no meaning. Fixed values
                // are checked against the actual item total in withValidator().
                Rule::when(
                    fn () => in_array($this->input('discount_type'), ['percent', 'percentage'], true),
                    ['max:100']
                ),
            ],

            // Payment Receipt Data
            'payment_method_id' => [
                // required_if compares for EQUALITY — it has no operator support,
                // so 'required_if:amount_paid,>0' asked whether amount_paid was
                // literally the string ">0" and never once fired. A payment with
                // no method reached the insert and hit the NOT NULL column.
                Rule::requiredIf(fn () => (float) $this->input('amount_paid', 0) > 0),
                'nullable',
                company_exists('payment_methods'),
            ],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],

            // Challan Conversion (optional)
            'challan_id' => ['nullable', company_exists('challans')],

            // Order Conversion (optional) — prefill invoice from an existing order
            'order_id' => ['nullable', company_exists('orders')],

            // Items Array Validation
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_sku_id' => ['required', company_exists('product_skus')],
            'items.*.unit_id' => ['required', 'integer', 	company_exists('units')],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            // String, not integer — HSN codes carry leading zeros ("06029000")
            // that a numeric cast would silently strip.
            'items.*.hsn_code' => ['nullable', 'string', 'max:8'],
            'items.*.tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_type' => ['required', 'in:inclusive,exclusive'],
            'items.*.discount_type' => ['required', 'in:fixed,percentage'],
            // Row-specific caps depend on that row's own quantity and price,
            // so they are enforced in withValidator() instead.
            'items.*.discount_value' => ['required', 'numeric', 'min:0'],
            // Challan item reference (populated when converting from a challan)
            // challan_items has no company_id of its own — a line's owner is
            // whoever owns its challan — so the check hops to the parent. The
            // service increments qty_invoiced on whatever id arrives here.
            'items.*.challan_item_id' => [
                'nullable',
                Rule::exists('challan_items', 'id')->whereIn(
                    'challan_id',
                    \App\Models\Challan::withoutGlobalScopes()
                        ->where('company_id', Auth::user()?->company_id)
                        ->pluck('id')
                ),
            ],
            // Batch fields (populated when batch tracking is enabled)
            'items.*.batch_id' => ['nullable', company_exists('product_batches')],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
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
     * Custom error messages for specific, complex rules.
     */
    public function messages(): array
    {
        return [
            // Header Messages
            'customer_id.required_without' => 'Please select an existing customer or enter a guest name.',
            'customer_name.required_without' => 'Please provide a customer name or select an existing customer.',
            'due_date.after_or_equal' => 'The due date cannot be earlier than the invoice date.',
            
            // Payment Messages
            // Rule::requiredIf reports under the 'required' key, not
            // 'required_if' — the old key silently produced no message.
            'payment_method_id.required' => 'Please select a payment method since an amount is being paid.',
            
            // Item Array Messages
            'items.required' => 'You must add at least one product to create an invoice.',
            'items.min' => 'You must add at least one product to create an invoice.',
            
            // Specific Item Field Messages
            'items.*.quantity.min' => 'The quantity must be greater than zero.',
            'items.*.unit_price.min' => 'The unit price cannot be negative.',
            'items.*.tax_percent.max' => 'The tax percentage cannot exceed 100%.',
        ];
    }

    /**
     * Map technical field names to friendly names.
     * This makes Laravel's default error messages read perfectly.
     */
    public function attributes(): array
    {
        return [
            'store_id' => 'store',
            'warehouse_id' => 'warehouse',
            'customer_id' => 'customer',
            'payment_method_id' => 'payment method',
            
            // Array mappings
            'items.*.product_sku_id' => 'product',
            'items.*.unit_id' => 'unit',
            'items.*.quantity' => 'quantity',
            'items.*.unit_price' => 'price',
            'items.*.tax_percent' => 'tax percentage',
            'items.*.tax_type' => 'tax type',
            'items.*.discount_type' => 'discount type',
            'items.*.discount_value' => 'discount value',
            'items.*.batch_number' => 'batch number',
        ];
    }
}