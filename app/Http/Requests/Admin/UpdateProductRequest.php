<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make the request.
     */
    public function authorize(): bool
    {
        // Ensure the product exists and belongs to the user's company
        return $this->product && $this->product->company_id === Auth::user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isCatalog = $this->input('product_type') === 'catalog';
        $isVariable = $this->input('type') === 'variable';

        // ── 1. Common Rules ──
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
            'product_guide.*.title' => ['nullable', 'string'],
            'product_guide.*.description' => ['nullable', 'string'],

            'media' => ['nullable', 'array', 'max:10'],
            'media.*.id' => ['nullable', 'integer'],
            'media.*.type' => ['required_with:media', 'in:image,youtube'],

            'media.*.file' => [
                'exclude_if:media.*.type,youtube',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $id = $this->input("media.{$index}.id");

                    if (empty($id) && empty($value) && $this->hasFile($attribute)) {
                        $fail('An image file is required for new uploads.');
                    }
                },
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],

            'media.*.url' => [
                'nullable',
                'url',
                'max:255',
            ],

            'media.*.sku_index' => ['nullable'],
            'primary_media_index' => ['nullable', 'integer'],
        ];

        // ── 2. Catalog Bypass ──
        if ($isCatalog) {
            $rules['product_unit_id'] = ['nullable'];
            $rules['sale_unit_id'] = ['nullable'];
            $rules['purchase_unit_id'] = ['nullable'];
            $rules['type'] = ['nullable', 'in:single,variable'];
            $rules['barcode_symbology'] = ['nullable', 'string'];

            return $rules;
        }

        // ── 3. Sellable Requirements ──
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

        // ── 4. Single Product Uniqueness Logic ──
        if (! $isVariable) {
            // Get the ID of the existing SKU for the single product to ignore it
            $existingSkuId = $this->product->skus()->first()?->id;

            $rules['single_sku'] = [
                'nullable',
                'string',
                Rule::unique('product_skus', 'sku')
                    ->where('company_id', Auth::user()?->company_id)
                    ->ignore($existingSkuId),
            ];

            $rules['single_barcode'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_skus', 'barcode')
                    ->where('company_id', Auth::user()?->company_id)
                    ->ignore($existingSkuId),
            ];

            $rules['single_price'] = [
                'required',
                'numeric',
                'min:0',
            ];

            $rules['single_cost'] = [
                'required',
                'numeric',
                'min:0',
            ];

            $rules['single_mrp'] = [
                'nullable',
                'numeric',
                'min:0',
            ];

            $rules['single_order_tax'] = [
                'nullable',
                'numeric',
                'min:0',
            ];

            $rules['single_tax_type'] = [
                'required',
                'in:inclusive,exclusive',
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
        }

        // ── 5. Variable Product Uniqueness Logic ──
        if ($isVariable) {
            $rules['variations'] = [
                'required',
                'array',
                'min:1',
            ];

            foreach ($this->input('variations', []) as $index => $variation) {
                $variationId = $variation['id'] ?? null;

                $rules["variations.{$index}.id"] = [
                    'nullable',
                    company_exists('product_skus'),
                ];

                $rules["variations.{$index}.sku"] = [
                    'nullable',
                    'string',
                    Rule::unique('product_skus', 'sku')
                        ->where('company_id', Auth::user()?->company_id)
                        ->ignore($variationId),
                ];

                $rules["variations.{$index}.barcode"] = [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('product_skus', 'barcode')
                        ->where('company_id', Auth::user()?->company_id)
                        ->ignore($variationId),
                ];

                $rules["variations.{$index}.price"] = [
                    'required',
                    'numeric',
                    'min:0',
                ];

                $rules["variations.{$index}.cost"] = [
                    'required',
                    'numeric',
                    'min:0',
                ];

                $rules["variations.{$index}.mrp"] = [
                    'nullable',
                    'numeric',
                    'min:0',
                ];

                $rules["variations.{$index}.attrs"] = [
                    'nullable',
                    'array',
                ];

                $rules["variations.{$index}.order_tax"] = [
                    'nullable',
                    'numeric',
                    'min:0',
                ];

                $rules["variations.{$index}.tax_type"] = [
                    'required',
                    'in:inclusive,exclusive',
                ];

                $rules["variations.{$index}.stock_alert"] = [
                    'nullable',
                    'integer',
                    'min:0',
                ];

                $rules["variations.{$index}.hsn_code"] = [
                    'nullable',
                    'string',
                    'max:20',
                ];

                $rules["variations.{$index}.stock"] = [
                    'nullable',
                    'array',
                ];

                $rules["variations.{$index}.stock.*.warehouse_id"] = [
                    'required_with:variations.{$index}.stock',
                    company_exists('warehouses'),
                ];

                $rules["variations.{$index}.stock.*.qty"] = [
                    'required_with:variations.{$index}.stock',
                    'integer',
                    'min:1',
                ];
            }
        }

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