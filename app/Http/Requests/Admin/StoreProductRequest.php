<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCatalog = $this->input('product_type') === 'catalog';

        // ── Common rules (apply to both sellable & catalog) ──
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['nullable', 'in:sellable,catalog'],
            'hsn_code' => ['nullable', 'string', 'max:50'],

            'category_id' => [
                'nullable',
                company_exists('categories'),
            ],

            'categories' => [
                'nullable',
                'array',
            ],

            'categories.*' => [
                'integer',
                company_exists('categories'),
            ],

            'supplier_id' => [
                'nullable',
                company_exists('suppliers'),
            ],

            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'show_in_storefront' => ['boolean'],
            'show_as_addon' => ['boolean'],

            'product_guide' => ['nullable', 'array', 'max:50'],
            'product_guide.*.title' => ['nullable', 'string', 'max:255'],
            'product_guide.*.description' => ['nullable', 'string'],

            'media' => ['nullable', 'array', 'max:10'],
            'media.*.type' => ['required', 'in:image,youtube'],
            'media.*.file' => [
                'exclude_if:media.*.type,youtube',
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],
            'media.*.url' => [
                'exclude_if:media.*.type,image',
                'required',
                'url',
                'max:255',
            ],
            'media.*.sku_index' => ['nullable', 'integer', 'min:0'],
            'primary_media_index' => ['nullable', 'integer'],
        ];

        // ── Catalog products: no SKU / price / stock / units required ──
        if ($isCatalog) {
            $rules['product_unit_id'] = ['nullable'];
            $rules['sale_unit_id'] = ['nullable'];
            $rules['purchase_unit_id'] = ['nullable'];
            $rules['type'] = ['nullable', 'in:single,variable'];
            $rules['barcode_symbology'] = ['nullable', 'string'];

            return $rules;
        }

        // ── Sellable products: full SKU / pricing validation ──
        $rules['product_unit_id'] = [
            'required',
            company_exists('units'),
        ];

        $rules['sale_unit_id'] = [
            'required',
            company_exists('units'),
        ];

        $rules['purchase_unit_id'] = [
            'required',
            company_exists('units'),
        ];

        $rules['quantity_limitation'] = [
            'nullable',
            'integer',
            'min:1',
        ];

        $rules['type'] = [
            'required',
            'in:single,variable',
        ];

        $rules['barcode_symbology'] = [
            'nullable',
            'string',
        ];

        // Single product
        $rules['single_sku'] = [
            'exclude_if:type,variable',
            'required',
            'string',
            Rule::unique('product_skus', 'sku')
                ->where('company_id', Auth::user()?->company_id),
        ];

        $rules['single_barcode'] = [
            'exclude_if:type,variable',
            'nullable',
            'string',
            'max:255',
            Rule::unique('product_skus', 'barcode')
                ->where('company_id', Auth::user()?->company_id),
        ];

        $rules['single_price'] = [
            'exclude_if:type,variable',
            'required',
            'numeric',
            'min:0',
        ];

        $rules['single_cost'] = [
            'exclude_if:type,variable',
            'required',
            'numeric',
            'min:0',
        ];

        $rules['single_mrp'] = [
            'exclude_if:type,variable',
            'nullable',
            'numeric',
            'min:0',
        ];

        $rules['single_stock_alert'] = [
            'nullable',
            'integer',
            'min:0',
        ];

        $rules['single_hsn_code'] = [
            'nullable',
            'string',
            'max:20',
        ];

        $rules['single_stock'] = [
            'nullable',
            'array',
        ];

        $rules['single_stock.*.warehouse_id'] = [
            'required_with:single_stock',
            company_exists('warehouses'),
        ];

        $rules['single_stock.*.qty'] = [
            'required_with:single_stock',
            'integer',
            'min:1',
        ];

        $rules['single_order_tax'] = [
            'exclude_if:type,variable',
            'nullable',
            'numeric',
            'min:0',
        ];

        $rules['single_tax_type'] = [
            'exclude_if:type,variable',
            'required',
            'in:inclusive,exclusive',
        ];

        // Variable product
        $rules['variations'] = [
            'exclude_if:type,single',
            'required',
            'array',
            'min:1',
        ];

        $rules['variations.*.sku'] = [
            'nullable',
            'string',
            Rule::unique('product_skus', 'sku')
                ->where('company_id', Auth::user()?->company_id),
        ];

        $rules['variations.*.barcode'] = [
            'nullable',
            'string',
            'max:255',
            Rule::unique('product_skus', 'barcode')
                ->where('company_id', Auth::user()?->company_id),
        ];

        $rules['variations.*.price'] = [
            'required_with:variations',
            'numeric',
            'min:0',
        ];

        $rules['variations.*.cost'] = [
            'required_with:variations',
            'numeric',
            'min:0',
        ];

        $rules['variations.*.mrp'] = [
            'nullable',
            'numeric',
            'min:0',
        ];

        $rules['variations.*.attrs'] = [
            'nullable',
            'array',
        ];

        $rules['variations.*.order_tax'] = [
            'nullable',
            'numeric',
            'min:0',
        ];

        $rules['variations.*.tax_type'] = [
            'required_with:variations',
            'in:inclusive,exclusive',
        ];

        $rules['variations.*.stock_alert'] = [
            'nullable',
            'integer',
            'min:0',
        ];

        $rules['variations.*.hsn_code'] = [
            'nullable',
            'string',
            'max:20',
        ];

        $rules['variations.*.stock'] = [
            'nullable',
            'array',
        ];

        $rules['variations.*.stock.*.warehouse_id'] = [
            'required_with:variations.*.stock',
            company_exists('warehouses'),
        ];

        $rules['variations.*.stock.*.qty'] = [
            'required_with:variations.*.stock',
            'integer',
            'min:1',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Media UX Messages
            'media.*.file.max' => 'Image #:position is too large! Maximum allowed size is 10MB per image.',
            'media.*.file.mimes' => 'Image #:position must be a valid format (JPEG, PNG, JPG, WEBP).',
            'media.*.file.image' => 'File #:position must be an image.',
            'media.*.url.url' => 'Please provide a valid YouTube URL for media #:position.',

            // SKU & Barcode UX Messages
            'single_sku.unique' => 'This SKU is already taken. Please generate a new one.',
            'variations.*.sku.unique' => 'Variant #:position has a duplicate SKU. SKUs must be totally unique.',
            'single_barcode.unique' => 'This Barcode is already registered to another product.',
            'variations.*.barcode.unique' => 'Variant #:position has a duplicate Barcode.',

            // Pricing UX Messages
            'variations.*.price.required_with' => 'Please enter a selling price for all generated variants.',
            'variations.*.cost.required_with' => 'Please enter a purchase cost for all generated variants.',
        ];
    }
}