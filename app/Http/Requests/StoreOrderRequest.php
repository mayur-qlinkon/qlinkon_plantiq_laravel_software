<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Add specific permission checks if needed,
        // but generally true if auth middleware handles access.
        return true;
    }

    /**
     * Clean and prepare data BEFORE validation runs.
     * This makes it "Fallback Safe".
     */
    protected function prepareForValidation(): void
    {
        // 🌟 FIX: Map frontend 'address' to 'delivery_address' seamlessly
        if ($this->has('address') && ! $this->has('delivery_address')) {
            $this->merge([
                'delivery_address' => $this->input('address'),
            ]);
        }

        $this->merge([
            // Default to 'storefront' if not provided (e.g., public website orders)
            'source' => $this->input('source', 'storefront'),

            // Default to 'retail' if order type isn't explicitly set
            'order_type' => $this->input('order_type', 'retail'),

            // Default payment method if missing
            'payment_method' => $this->input('payment_method', 'cod'),

            // Ensure items is an array even if empty or malformed
            'items' => is_array($this->items) ? $this->items : [],

            'supply_state' => $this->input('supply_state')
                ?: $this->input('delivery_state')
                ?: $this->input('delivery_city')
                ?: null, // GST defaults to intra-state in OrderService when null

            'customer_id' => $this->input('customer_id')
                ?: $this->input('client_id')
                ?: null,
        ]);

        if (! $this->filled('store_id')) {
            // active_store() works only for authenticated admin/staff users.
            // For storefront requests (guest checkout OR logged-in customer),
            // it will return null because customers have no store_user pivot row.
            // In that case we resolve the store directly from the company slug
            // that is always present in the storefront URL — same logic that
            // OrderService::placeOrder() uses when it finally writes the order.
            $storeId = active_store()?->id;

            if (! $storeId && $this->route('slug')) {
                $company = Company::where('slug', $this->route('slug'))->first();

                if ($company) {
                    $storeId = get_setting(
                        'default_storefront_store_id',
                        null,
                        $company->id
                    ) ?? Store::where('company_id', $company->id)
                        ->where('is_active', true)
                        ->value('id');
                }
            }

            $this->merge([
                'store_id' => $storeId,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // ── ORDER META ──
            'order_type' => [
                'required',
                'string',
                Rule::in([
                    'retail',
                    'wholesale',
                    'inquiry',
                    'sample',
                    'subscription',
                    'repair',
                ]),
            ],

            'source' => [
                'required',
                'string',
                Rule::in([
                    'pos',
                    'storefront',
                    'admin',
                    'api',
                ]),
            ],

            'store_id' => [
                'required',
                'integer',
                company_or_host_exists('stores'),
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                company_or_host_exists('warehouses'),
            ],

            'customer_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'admin_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // ── CUSTOMER INFO ──
            'customer_id' => [
                'nullable',
                'integer',
                company_or_host_exists('clients'),
            ],

            'customer_name' => [
                'required_if:source,storefront',
                'nullable',
                'string',
                'max:255',
            ],

            'customer_phone' => [
                'required_if:source,storefront',
                'nullable',
                'string',
                'max:20',
            ],

            'customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            // ── ADDRESS & TAX INFO ──
            // Addresses are required UNLESS it's a direct POS walk-in
            'delivery_address' => [
                'required_if:source,storefront',
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

            // Supply state is crucial for IGST vs CGST/SGST calculation
            'supply_state' => [
                'nullable',
                'string',
                'max:100',
            ],

            // ── PAYMENT INFO ──
            'payment_method' => [
                'required',
                'string',
                'max:50',
            ],

            'coupon_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            // ── CART ITEMS (The most crucial part) ──
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                company_or_host_exists('products'),
            ],

            'items.*.sku_id' => [
                'required',
                'integer',
                company_or_host_exists('product_skus'),
            ],

            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            // ── ADMIN OVERRIDES (Offline Orders) ──
            'status' => [
                'nullable',
                'string',
                'in:inquiry,confirmed,processing,shipped,delivered',
            ],

            'payment_status' => [
                'nullable',
                'string',
                'in:pending,paid,partial',
            ],

            'delivery_type' => [
                'nullable',
                'string',
                'in:delivery,pickup,digital',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Custom easily trackable error messages.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Your cart cannot be empty.',
            'items.min' => 'You must add at least one item to place an order.',

            'items.*.product_id.required' => 'A product ID is missing from one of your cart items.',
            'items.*.product_id.exists' => 'One of the products in your cart is no longer available.',

            'items.*.sku_id.required' => 'A variant (SKU) ID is missing from a cart item.',
            'items.*.sku_id.exists' => 'One of the selected product variants is invalid.',

            'items.*.qty.required' => 'Quantity is required for all items.',
            'items.*.qty.min' => 'Quantity must be at least 1 for all items.',

            'customer_name.required' => 'Customer name is required to process the order.',
            'customer_phone.required' => 'A contact number is required for delivery updates.',
            'supply_state.required' => 'Supply state is required to calculate accurate taxes.',

            'delivery_address.required_unless' => 'Delivery address is required for storefront orders.',
            'delivery_city.required_unless' => 'City is required for storefront orders.',
        ];
    }
}