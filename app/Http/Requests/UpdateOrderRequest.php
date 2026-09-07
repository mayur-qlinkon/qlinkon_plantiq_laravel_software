<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Add permission checks here (e.g., only admins or store managers can update orders)
        // return auth()->user()->can('update orders');
        return true;
    }

    /**
     * Clean data before validation.
     */
    protected function prepareForValidation(): void
    {
        // If empty strings are sent for tracking, convert them to null
        $this->merge([
            'tracking_number' => $this->input('tracking_number') ?: null,
            'courier_name' => $this->input('courier_name') ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validStatuses = array_keys(Order::STATUS_COLORS);
        $validPaymentStatuses = array_keys(Order::PAYMENT_STATUS_COLORS);

        $order = $this->route('order');

        // 1. Define the base rules inside a variable
        $rules = [
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in($validStatuses),
            ],

            'payment_status' => [
                'sometimes',
                'required',
                'string',
                Rule::in($validPaymentStatuses),
            ],

            'payment_method' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'warehouse_id' => [
                'sometimes',
                'nullable',
                'integer',
                company_exists('warehouses'),
            ],

            'cancellation_reason' => [
                'required_if:status,cancelled',
                'nullable',
                'string',
                'max:500',
            ],

            'courier_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tracking_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'expected_delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            'delivery_type' => [
                'nullable',
                'string',
                Rule::in([
                    'delivery',
                    'pickup',
                    'digital',
                ]),
            ],

            'delivery_address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'delivery_city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'delivery_state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'delivery_pincode' => [
                'nullable',
                'string',
                'max:20',
            ],

            'delivery_country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'admin_notes' => [
                'nullable',
                'string',
                'max:1500',
            ],
        ];

        // 2. Append the financial/item rules ONLY if it's an admin order
        if ($order && $order->source === 'admin') {
            $rules['discount_amount'] = [
                'nullable',
                'numeric',
                'min:0',
            ];

            $rules['shipping_amount'] = [
                'nullable',
                'numeric',
                'min:0',
            ];

            $rules['items'] = [
                'sometimes',
                'array',
                'min:1',
            ];

            $rules['items.*.product_id'] = [
                'required_with:items',
                'integer',
            ];

            $rules['items.*.sku_id'] = [
                'required_with:items',
                company_exists('product_skus'),
            ];

            $rules['items.*.qty'] = [
                'required_with:items',
                'integer',
                'min:1',
            ];

            $rules['items.*.unit_price'] = [
                'required_with:items',
                'numeric',
                'min:0',
            ];

            // 🌟 ADD THESE TWO RULES:
            $rules['items.*.product_name'] = [
                'required_with:items',
                'string',
            ];

            $rules['items.*.sku_code'] = [
                'nullable',
                'string',
            ];
        }

        // 3. Return the final, combined rules array
        return $rules;
    }

    /**
     * Custom easily trackable error messages.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid order status selected.',
            'payment_status.in' => 'Invalid payment status selected.',
            'cancellation_reason.required_if' => 'You must provide a reason when cancelling an order.',
            'expected_delivery_date.after_or_equal' => 'The expected delivery date cannot be in the past.',
        ];
    }

    /**
     * Configure the validator instance to add complex conditional logic.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route('order');

            if ($order) {
                // 1. Guardrail: Address locked if fulfilled/cancelled
                if (in_array($order->status, [
                    'shipped',
                    'out_for_delivery',
                    'delivered',
                    'cancelled',
                ])) {
                    if (
                        $this->filled('delivery_address')
                        && $this->delivery_address !== $order->delivery_address
                    ) {
                        $validator->errors()->add(
                            'delivery_address',
                            'Address cannot be changed after the order has been fulfilled.'
                        );
                    }

                    // Admin Orders: Items locked if fulfilled
                    if ($order->source === 'admin') {
                        if (
                            $this->has('items')
                            || $this->has('discount_amount')
                            || $this->has('shipping_amount')
                        ) {
                            $validator->errors()->add(
                                'items',
                                'Order items and financials cannot be modified after fulfillment.'
                            );
                        }
                    }
                }

                // 2. Strict Guardrail: Storefront orders can NEVER have their items modified
                if ($order->source === 'storefront') {
                    if (
                        $this->has('items')
                        || $this->has('discount_amount')
                        || $this->has('shipping_amount')
                    ) {
                        $validator->errors()->add(
                            'items',
                            'Items and financials cannot be modified for customer-placed storefront orders.'
                        );
                    }
                }
            }
        });
    }
}