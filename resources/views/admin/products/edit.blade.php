@extends ('layouts.admin')

@section('title', 'Edit Product')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}"
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-900">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Edit Product</h1>
    </div>
@endsection

@section('content')
    @php
        // Prepare string-keyed arrays for strict type matching in edit mode
        $categoryOptions = [];
        foreach ($categories ?? [] as $cat) {
            $categoryOptions[(string) $cat->id] = $cat->name;
        }

        $supplierOptions = [];
        foreach ($suppliers ?? [] as $sup) {
            $supplierOptions[(string) $sup->id] = $sup->name;
        }

        $unitOptions = [];
        foreach ($units ?? [] as $unit) {
            $unitOptions[(string) $unit->id] = "{$unit->name} ({$unit->short_name})";
        }
    @endphp
    <div class="space-y-6 pb-10" x-data="productForm()">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                {{-- <h1 class="text-2xl font-bold text-[#212538] tracking-tight">Edit Product</h1> --}}
                <p class="mt-1 text-sm font-medium text-gray-500">Update inventory item details and pricing.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 shadow-sm">
                <div class="mb-1 flex items-center gap-2 font-bold">
                    <i data-lucide="alert-circle" class="h-4 w-4"></i> Please fix the following mistakes:
                </div>
                <ul class="ml-6 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6" @submit="BizAlert.loading('Updating Product...')">
            @csrf
            @method ('PUT')

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 border-b border-gray-100 pb-2 text-lg font-bold text-gray-800">1. Basic Information</h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Product Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Category <span
                                class="text-red-500">*</span></label>
                        <x-custom-select name="category_id" placeholder="Select Category" :options="$categoryOptions"
                            :selected="old('category_id', (string) $product->category_id)" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">HSN Code</label>
                        <input type="text" name="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}"
                            placeholder=" 61091000"
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Status</label>
                        <div x-data="{ isActive: '{{ old('is_active', $product->is_active) ? 1 : 0 }}' }"
                            class="flex h-[42px] overflow-hidden rounded-lg border border-gray-300">
                            <input type="hidden" name="is_active" :value="isActive" />
                            <button type="button" @click="isActive = '1'"
                                :class="isActive == '1' ?
                                    'bg-[#108c2a] text-white' :
                                    'bg-white text-gray-500 hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1.5 text-[13px] font-semibold transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M8 12l3 3 5-5" />
                                </svg>
                                Active
                            </button>
                            <button type="button" @click="isActive = '0'"
                                :class="isActive == '0' ?
                                    'bg-gray-500 text-white' :
                                    'bg-white text-gray-500 hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1.5 border-l border-gray-300 text-[13px] font-semibold transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M9 9l6 6M15 9l-6 6" />
                                </svg>
                                Draft
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Show in Storefront</label>
                        <div x-data="{ inStorefront: '{{ old('show_in_storefront', $product->show_in_storefront) ? 1 : 0 }}' }"
                            class="flex h-[42px] overflow-hidden rounded-lg border border-gray-300">
                            <input type="hidden" name="show_in_storefront" :value="inStorefront" />
                            <button type="button" @click="inStorefront = '1'"
                                :class="inStorefront == '1' ?
                                    'bg-[#108c2a] text-white' :
                                    'bg-white text-gray-500 hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1.5 text-[13px] font-semibold transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                Listed
                            </button>
                            <button type="button" @click="inStorefront = '0'"
                                :class="inStorefront == '0' ?
                                    'bg-gray-500 text-white' :
                                    'bg-white text-gray-500 hover:bg-gray-50'"
                                class="flex flex-1 items-center justify-center gap-1.5 border-l border-gray-300 text-[13px] font-semibold transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22" />
                                </svg>
                                Hidden
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Supplier</label>
                        <x-custom-select name="supplier_id" placeholder="Select Supplier" :options="$supplierOptions"
                            :selected="old('supplier_id', $product->supplier_id ? (string) $product->supplier_id : '')" />
                    </div>

                    @if (has_module('storefront'))
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Show as Addon</label>
                            <label
                                class="flex h-[42px] cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 text-[13px] font-semibold text-gray-700 hover:bg-gray-50">
                                <input type="hidden" name="show_as_addon" value="0" />
                                <input type="checkbox" name="show_as_addon" value="1"
                                    {{ old('show_as_addon', $product->show_as_addon) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                                People Also Buy
                            </label>
                        </div>
                    @endif

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]">{{ old('description', $product->description) }}</textarea
                        >
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 border-b border-gray-100 pb-2 text-lg font-bold text-gray-800">
                    2. Units & Measurements
                </h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                            >Product Unit <span class="text-red-500">*</span></label
                        >
                        <x-custom-select
                            name="product_unit_id"
                            placeholder="Select Base Unit"
                            :options="$unitOptions"
                            :selected="old('product_unit_id', (string) $product->product_unit_id)"
                            required
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                            >Sale Unit <span class="text-red-500">*</span></label
                        >
                        <x-custom-select
                            name="sale_unit_id"
                            placeholder="Select Sale Unit"
                            :options="$unitOptions"
                            :selected="old('sale_unit_id', (string) $product->sale_unit_id)"
                            required
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                            >Purchase Unit <span class="text-red-500">*</span></label
                        >
                        <x-custom-select
                            name="purchase_unit_id"
                            placeholder="Select Purchase Unit"
                            :options="$unitOptions"
                            :selected="old('purchase_unit_id', (string) $product->purchase_unit_id)"
                            required
                        />
                    </div>
                </div>
            </div>

            @if (has_module('plant_education'))
{{-- ── Product Type Selector (module-gated) ── --}}
                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold text-gray-800">Product Purpose</h2>
                    <div class="flex flex-col items-stretch gap-4 sm:flex-row sm:items-center">
                        <label
                            class="flex flex-1 cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-all"
                            :class="catalogMode === 'sellable'
                                ?
                                'border-brand-500 bg-brand-50' :
                                'border-gray-200 bg-white hover:bg-gray-50'"
                        >
                            <input
                                type="radio"
                                name="product_type"
                                value="sellable"
                                x-model="catalogMode"
                                class="hidden"
                            />
                            <i
                                data-lucide="shopping-cart"
                                class="h-5 w-5"
                                :class="catalogMode === 'sellable' ? 'text-brand-600' : 'text-gray-400'"
                            ></i>
                            <div>
                                <p
                                    class="text-sm font-bold"
                                    :class="catalogMode === 'sellable' ? 'text-brand-700' : 'text-gray-700'"
                                >Sellable</p>
                                <p class="text-[11px] text-gray-400">Normal product with pricing, stock & POS</p>
                            </div>
                        </label>
                        <label
                            class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border-2 px-4 py-3 transition-all"
                            :class="catalogMode === 'catalog'
                                ?
                                'border-teal-500 bg-teal-50' :
                                'border-gray-200 bg-white hover:bg-gray-50'"
                        >
                            <input
                                type="radio"
                                name="product_type"
                                value="catalog"
                                x-model="catalogMode"
                                class="hidden"
                            />
                            <i
                                data-lucide="book-open"
                                class="h-5 w-5"
                                :class="catalogMode === 'catalog' ? 'text-teal-600' : 'text-gray-400'"
                            ></i>
                            <div>
                                <p
                                    class="text-sm font-bold"
                                    :class="catalogMode === 'catalog' ? 'text-teal-700' : 'text-gray-700'"
                                >Catalog</p>
                                <p class="text-[11px] text-gray-400">Informational product — no pricing or stock</p>
                            </div>
                        </label>
                    </div>
                </div>
@endif

            <div
                class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
                x-show="catalogMode !== 'catalog'"
                x-cloak
            >
                <div
                    class="mb-5 flex flex-col justify-between gap-4 border-b border-gray-100 pb-3 sm:flex-row sm:items-center"
                >
                    <h2 class="text-base font-bold text-gray-800 sm:text-lg">3. Product Pricing & SKUs</h2>

                    <div class="flex w-full items-center rounded-lg bg-gray-100 p-1 sm:w-auto">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="single" x-model="productType" class="peer hidden" />
                            <span
                                class="block rounded-md px-4 py-1.5 text-sm font-bold text-gray-500 transition-all peer-checked:bg-white peer-checked:text-[#108c2a] peer-checked:shadow-sm"
                                >Single Item</span
                            >
                        </label>
                        <label class="cursor-pointer">
                            <input
                                type="radio"
                                name="type"
                                value="variable"
                                x-model="productType"
                                class="peer hidden"
                            />
                            <span
                                class="block rounded-md px-4 py-1.5 text-sm font-bold text-gray-500 transition-all peer-checked:bg-white peer-checked:text-[#108c2a] peer-checked:shadow-sm"
                                >Variable Product</span
                            >
                        </label>
                    </div>
                </div>

                @php
                    // Helper to pre-load single product data securely (loads first SKU regardless of current type to prevent data loss on toggle)
                    $singleSku = $product->skus->first();
                @endphp

                <div x-show="catalogMode !== 'catalog'" x-cloak>
                    <div
                        x-show="productType === 'single'"
                        class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >SKU <span class="text-red-500">*</span></label
                            >
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    name="single_sku"
                                    x-model="singleSku"
                                    :required="productType === 'single' && catalogMode !== 'catalog'"
                                    class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                                />
                                <button
                                    type="button"
                                    @click="singleSku = generateSKU()"
                                    title="Generate Random SKU"
                                    class="flex-shrink-0 rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-gray-600 transition-colors hover:bg-gray-200"
                                >
                                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        {{-- 🌟 NEW: Barcode Field with Generate Button --}}
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >Barcode</label
                            >
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    name="single_barcode"
                                    x-model="singleBarcode"
                                    placeholder="Scan or auto-generate"
                                    class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                                />

                                <button
                                    type="button"
                                    @click="singleBarcode = generateBarcode()"
                                    title="Generate Random Barcode"
                                    class="flex-shrink-0 rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-gray-600 transition-colors hover:bg-gray-200"
                                >
                                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        {{-- 🌟 NEW: MRP Field --}}
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >MRP (₹)</label
                            >
                            <input
                                type="number"
                                step="0.01"
                                name="single_mrp"
                                x-model="singleMrp"
                                placeholder="Original Price"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >Selling Price (₹) <span class="text-red-500">*</span></label
                            >
                            <input
                                type="number"
                                step="0.01"
                                name="single_price"
                                value="{{ old('single_price', $singleSku?->price) }}"
                                :required="productType === 'single' && catalogMode !== 'catalog'"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >Purchase Cost (₹) <span class="text-red-500">*</span></label
                            >
                            <input
                                type="number"
                                step="0.01"
                                name="single_cost"
                                value="{{ old('single_cost', $singleSku?->cost) }}"
                                :required="productType === 'single' && catalogMode !== 'catalog'"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >Tax Type <span class="text-red-500">*</span></label
                            >
                            {{-- Two choices, so a toggle rather than a menu — both options stay
                                 visible, which matters because the difference changes what the
                                 customer is charged. The hidden input keeps the posted field
                                 name unchanged. --}}
                            <div x-data="{ taxType: '{{ old('single_tax_type', $singleSku?->tax_type ?? 'exclusive') }}' }">
                                <input type="hidden" name="single_tax_type" :value="taxType" />
                                <div class="flex gap-1 rounded-md border border-gray-300 bg-gray-50 p-1">
                                    <button
                                        type="button"
                                        @click="taxType = 'exclusive'"
                                        :class="taxType === 'exclusive' ? 'bg-white text-[#108c2a] shadow-sm' :
                                            'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 rounded py-1.5 text-[13px] font-bold transition-all"
                                    >
                                        Exclusive
                                    </button>
                                    <button
                                        type="button"
                                        @click="taxType = 'inclusive'"
                                        :class="taxType === 'inclusive' ? 'bg-white text-[#108c2a] shadow-sm' :
                                            'text-gray-500 hover:text-gray-700'"
                                        class="flex-1 rounded py-1.5 text-[13px] font-bold transition-all"
                                    >
                                        Inclusive
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Tax (%)</label>
                            <input
                                type="number"
                                step="0.01"
                                name="single_order_tax"
                                value="{{ old('single_order_tax', $singleSku?->order_tax ?? 0) }}"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Low Stock Alert</label>
                            <input
                                type="number"
                                name="single_stock_alert"
                                value="{{ old('single_stock_alert', $singleSku?->stock_alert ?? 0) }}"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                                >HSN Override
                                </label
                            >
                            <input
                                type="text"
                                name="single_hsn_code"
                                value="{{ old('single_hsn_code', $singleSku?->hsn_code ?? '') }}"
                                placeholder=" 61091000"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                            />
                            <p class="mt-1 text-[11px] text-gray-400">Leave empty to use product HSN</p>
                        </div>
                    </div>

                    <div x-show="productType === 'variable'" x-cloak>
                        <div
                            class="mb-6 flex items-start gap-3 rounded-xl border border-[#108c2a]/20 bg-[#108c2a]/5 p-4"
                        >
                            <i data-lucide="layers" class="mt-0.5 h-5 w-5 text-[#108c2a]"></i>
                            <div>
                                <p class="text-sm font-bold text-gray-800">Variant Generator</p>
                                <p class="mt-1 text-[13px] text-gray-600">Select the attributes below and click generate. We will automatically append new combinations to your existing variants without overwriting them.</p>
                            </div>
                        </div>

                        {{-- STEP 1: Attribute Selection --}}
                        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 class="mb-4 text-[13px] font-bold tracking-wider text-gray-400 uppercase">
                                Step 1: Select Attributes
                            </h3>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                @foreach ($attributes as $attr)
<div>
                                        <div class="mb-3 flex items-center justify-between">
                                            <p class="text-sm font-bold text-gray-800">{{ $attr->name }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2.5">
                                            @foreach ($attr->values as $val)
<label
                                                    :class="(selectedValues[{{ $attr->id }}] || []).includes(
                                                            '{{ $val->id }}::{{ $val->value }}') ?
                                                        'bg-[#108c2a] border-[#108c2a] text-white shadow-sm' :
                                                        'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                                    class="relative inline-flex cursor-pointer items-center justify-center rounded-lg border px-4 py-2 text-[13px] font-bold transition-all select-none"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        x-model="selectedValues[{{ $attr->id }}]"
                                                        value="{{ $val->id }}::{{ $val->value }}"
                                                        class="sr-only"
                                                    />
                                                    <span>{{ $val->value }}</span>
                                                </label>
@endforeach
                                        </div>
                                    </div>
@endforeach
                            </div>

                            <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-100 pt-5">
                                <button
                                    type="button"
                                    @click="confirmVariantGeneration()"
                                    class="flex items-center gap-2 rounded-lg bg-[#108c2a] px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0c6b1f]"
                                >
                                    <i data-lucide="sparkles" class="h-4 w-4"></i> Generate Missing Combinations
                                </button>
                                <button
                                    type="button"
                                    @click="clearSelections()"
                                    class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50"
                                >
                                    <i data-lucide="rotate-ccw" class="h-4 w-4 text-gray-400"></i> Clear Selections
                                </button>
                            </div>
                        </div>

                        {{-- STEP 2: Spreadsheet Table --}}
                        <div x-show="variations.length > 0" x-cloak x-transition>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-[13px] font-bold tracking-wider text-gray-400 uppercase">
                                    Step 2: Pricing & Inventory
                                </h3>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"
                                        x-text="variations.length + ' Total Variants'"
                                    ></span>
                                </div>
                            </div>

                            {{-- 🌟 BULK EDIT PANEL --}}
                            <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4 shadow-inner">
                                <div class="mb-3 flex items-center gap-2">
                                    <i data-lucide="edit-3" class="h-4 w-4 text-gray-500"></i>
                                    <h4 class="text-[13px] font-bold text-gray-700">Quick Bulk Edit</h4>
                                    <span class="ml-1 text-[11px] text-gray-400">(Leaves empty fields unchanged)</span>
                                </div>

                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="w-24">
                                        <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                            >Price (₹)</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bulk.price"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm outline-none focus:border-[#108c2a]"
                                            placeholder="--"
                                        />
                                    </div>
                                    <div class="w-24">
                                        <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                            >Cost (₹)</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bulk.cost"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm outline-none focus:border-[#108c2a]"
                                            placeholder="--"
                                        />
                                    </div>
                                    <div class="w-28">
                                        <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                            >Tax Type</label
                                        >
                                        <select
                                            x-model="bulk.tax_type"
                                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-sm outline-none focus:border-[#108c2a]"
                                        >
                                            <option value="">Leave As Is</option>
                                            <option value="exclusive">Exclusive</option>
                                            <option value="inclusive">Inclusive</option>
                                        </select>
                                    </div>
                                    <div class="w-20">
                                        <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                            >Tax (%)</label
                                        >
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="bulk.tax_percent"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm outline-none focus:border-[#108c2a]"
                                            placeholder="--"
                                        />
                                    </div>
                                    <div class="w-20">
                                        <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                            >Alert Qty</label
                                        >
                                        <input
                                            type="number"
                                            x-model="bulk.alert"
                                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm outline-none focus:border-[#108c2a]"
                                            placeholder="--"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        @click="applyBulkEdit()"
                                        class="ml-auto h-[34px] rounded bg-gray-800 px-4 py-1.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-gray-700 sm:ml-0"
                                    >
                                        Apply to All
                                    </button>
                                </div>
                            </div>

                            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
                            <div
                                class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm md:block"
                            >
                                <table class="w-full border-collapse text-left whitespace-nowrap">
                                    <thead
                                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase"
                                    >
                                        <tr>
                                            <th
                                                class="sticky left-0 z-10 border-r border-gray-200 bg-gray-50 px-4 py-3 shadow-[1px_0_0_0_#e5e7eb]"
                                            >
                                                Variant Name
                                            </th>
                                            <th class="px-4 py-3">SKU</th>
                                            <th class="px-4 py-3">Barcode</th>
                                            <th class="px-4 py-3">MRP (₹)</th>
                                            <th class="px-4 py-3">Price (₹) <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3">Cost (₹) <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3">Tax Type</th>
                                            <th class="px-4 py-3">Tax (%)</th>
                                            <th class="px-4 py-3">HSN Code</th>
                                            <th class="px-4 py-3">Alert Qty</th>
                                            <th class="px-4 py-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(variant, index) in variations" :key="variant.id">
                                            <tr class="transition-colors hover:bg-gray-50/50">
                                                <td
                                                    class="sticky left-0 z-10 border-r border-gray-100 bg-white px-4 py-2 text-[13px] font-bold text-gray-800 shadow-[1px_0_0_0_#f3f4f6]"
                                                >
                                                    <span x-text="variant.label || 'Variation ' + (index + 1)"></span>
                                                    <template x-if="variant.is_existing">
                                                        <span
                                                            class="ml-2 rounded bg-blue-100 px-1.5 py-0.5 text-[9px] tracking-wider text-blue-700 uppercase"
                                                            >Saved</span
                                                        >
                                                    </template>
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="text"
                                                        :name="'variations[' + index + '][sku]'"
                                                        x-model="variant.sku"
                                                        class="w-32 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm uppercase transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="Auto"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="text"
                                                        :name="'variations[' + index + '][barcode]'"
                                                        x-model="variant.barcode"
                                                        class="w-32 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="Barcode"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][mrp]'"
                                                        x-model="variant.mrp"
                                                        class="w-24 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0.00"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][price]'"
                                                        x-model="variant.price"
                                                        :required="productType === 'variable' &&
                                                            catalogMode !== 'catalog'"
                                                        class="w-24 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm font-bold text-gray-800 transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0.00"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][cost]'"
                                                        x-model="variant.cost"
                                                        :required="productType === 'variable' &&
                                                            catalogMode !== 'catalog'"
                                                        class="w-24 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0.00"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    {{-- Labels are abbreviated because the column is only w-28;
                                                         title gives the full word on hover. --}}
                                                    <input type="hidden" :name="'variations[' + index + '][tax_type]'" :value="variant.tax_type" />
                                                    <div class="flex w-28 gap-0.5 rounded border border-gray-300 bg-gray-50 p-0.5">
                                                        <button
                                                            type="button"
                                                            title="Exclusive"
                                                            @click="variant.tax_type = 'exclusive'"
                                                            :class="variant.tax_type === 'exclusive' ?
                                                                'bg-white text-[#108c2a] shadow-sm' :
                                                                'text-gray-500 hover:text-gray-700'"
                                                            class="flex-1 rounded py-1.5 text-[11px] font-bold transition-all"
                                                        >
                                                            Excl.
                                                        </button>
                                                        <button
                                                            type="button"
                                                            title="Inclusive"
                                                            @click="variant.tax_type = 'inclusive'"
                                                            :class="variant.tax_type === 'inclusive' ?
                                                                'bg-white text-[#108c2a] shadow-sm' :
                                                                'text-gray-500 hover:text-gray-700'"
                                                            class="flex-1 rounded py-1.5 text-[11px] font-bold transition-all"
                                                        >
                                                            Incl.
                                                        </button>
                                                    </div>
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][order_tax]'"
                                                        x-model="variant.order_tax"
                                                        class="w-20 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="text"
                                                        :name="'variations[' + index + '][hsn_code]'"
                                                        x-model="variant.hsn_code"
                                                        class="w-24 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="HSN"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        :name="'variations[' + index + '][stock_alert]'"
                                                        x-model="variant.alert"
                                                        class="w-20 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0"
                                                    />
                                                </td>

                                                <td class="px-3 py-2 text-center">
                                                    <button
                                                        type="button"
                                                        @click="removeVariation(index)"
                                                        title="Remove Variant"
                                                        class="rounded p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </td>

                                                <template x-if="variant.is_existing">
                                                    <input
                                                        type="hidden"
                                                        :name="'variations[' + index + '][id]'"
                                                        :value="variant.id"
                                                    />
                                                </template>

                                                <template x-for="(valueId, attrId) in variant.attrs" :key="attrId">
                                                    <input
                                                        type="hidden"
                                                        :name="'variations[' + index + '][attrs][' + attrId + ']'"
                                                        :value="valueId"
                                                    />
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- 📱 MOBILE VIEW (CARDS) --}}
                            <div class="mt-4 space-y-4 md:hidden">
                                <template x-for="(variant, index) in variations" :key="variant.id">
                                    <div
                                        class="relative flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                                    >
                                        {{-- Header --}}
                                        <div class="flex items-start justify-between pr-8">
                                            <div>
                                                <div
                                                    class="text-[14px] font-bold text-gray-800"
                                                    x-text="variant.label || 'Variation ' + (index + 1)"
                                                ></div>
                                                <template x-if="variant.is_existing">
                                                    <span
                                                        class="mt-1 inline-block rounded bg-blue-100 px-1.5 py-0.5 text-[9px] tracking-wider text-blue-700 uppercase"
                                                        >Saved</span
                                                    >
                                                </template>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeVariation(index)"
                                                title="Remove Variant"
                                                class="absolute top-4 right-4 rounded bg-red-50 p-1.5 text-red-400 transition-colors hover:text-red-600"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>

                                        {{-- Grid for Inputs --}}
                                        <div class="mt-1 grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >SKU</label
                                                >
                                                <input
                                                    type="text"
                                                    :name="'variations[' + index + '][sku]'"
                                                    x-model="variant.sku"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm uppercase transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="Auto"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Barcode</label
                                                >
                                                <input
                                                    type="text"
                                                    :name="'variations[' + index + '][barcode]'"
                                                    x-model="variant.barcode"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="Barcode"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Cost (₹) <span class="text-red-500">*</span></label
                                                >
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    :name="'variations[' + index + '][cost]'"
                                                    x-model="variant.cost"
                                                    :required="productType === 'variable' && catalogMode !== 'catalog'"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Price (₹) <span class="text-red-500">*</span></label
                                                >
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    :name="'variations[' + index + '][price]'"
                                                    x-model="variant.price"
                                                    :required="productType === 'variable' && catalogMode !== 'catalog'"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm font-bold text-gray-800 transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >MRP (₹)</label
                                                >
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    :name="'variations[' + index + '][mrp]'"
                                                    x-model="variant.mrp"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Tax Type</label
                                                >
                                                <input type="hidden" :name="'variations[' + index + '][tax_type]'" :value="variant.tax_type" />
                                                <div class="flex gap-0.5 rounded border border-gray-300 bg-gray-50 p-0.5">
                                                    <button
                                                        type="button"
                                                        @click="variant.tax_type = 'exclusive'"
                                                        :class="variant.tax_type === 'exclusive' ?
                                                            'bg-white text-[#108c2a] shadow-sm' :
                                                            'text-gray-500 hover:text-gray-700'"
                                                        class="flex-1 rounded py-1.5 text-[11px] font-bold transition-all"
                                                    >
                                                        Exclusive
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="variant.tax_type = 'inclusive'"
                                                        :class="variant.tax_type === 'inclusive' ?
                                                            'bg-white text-[#108c2a] shadow-sm' :
                                                            'text-gray-500 hover:text-gray-700'"
                                                        class="flex-1 rounded py-1.5 text-[11px] font-bold transition-all"
                                                    >
                                                        Inclusive
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Tax (%)</label
                                                >
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    :name="'variations[' + index + '][order_tax]'"
                                                    x-model="variant.order_tax"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="0"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >Alert Qty</label
                                                >
                                                <input
                                                    type="number"
                                                    :name="'variations[' + index + '][stock_alert]'"
                                                    x-model="variant.alert"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="0"
                                                />
                                            </div>
                                            <div class="col-span-2">
                                                <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase"
                                                    >HSN Code</label
                                                >
                                                <input
                                                    type="text"
                                                    :name="'variations[' + index + '][hsn_code]'"
                                                    x-model="variant.hsn_code"
                                                    class="w-full rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                    placeholder="HSN"
                                                />
                                            </div>
                                        </div>

                                        {{-- Hidden Inputs --}}
                                        <template x-if="variant.is_existing">
                                            <input
                                                type="hidden"
                                                :name="'variations[' + index + '][id]'"
                                                :value="variant.id"
                                            />
                                        </template>
                                        <template x-for="(valueId, attrId) in variant.attrs" :key="attrId">
                                            <input
                                                type="hidden"
                                                :name="'variations[' + index + '][attrs][' + attrId + ']'"
                                                :value="valueId"
                                            />
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-5 border-b border-gray-100 pb-3">
                        <h2 class="text-lg font-bold text-gray-800">4. Product Media</h2>
                        <p class="mt-1 text-xs text-gray-500">Add images or YouTube videos. Drag to reorder, and click 'Set Main' for your primary thumbnail.</p>
                    </div>

                    <input
                        type="hidden"
                        name="primary_media_index"
                        :value="mediaList.findIndex((m) => m.id == primaryMediaId)"
                    />

                    {{-- 🌟 NEW: Responsive Grid Container --}}
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        <template x-for="(media, index) in mediaList" :key="media.id">
                            <div
                                class="group relative flex flex-col overflow-hidden rounded-xl border-2 bg-gray-50 shadow-sm transition-all duration-200"
                                :class="primaryMediaId == media.id ?
                                    'border-[#108c2a] ring-2 ring-[#108c2a]/20' :
                                    'border-gray-200 hover:border-gray-300'"
                                draggable="true"
                                @dragstart="dragStart(index, $event)"
                                @dragend="dragEnd()"
                                @dragover="dragOver($event)"
                                @drop="drop(index)"
                            >
                                {{-- Hidden Inputs --}}
                                <input type="hidden" :name="'media[' + index + '][type]'" :value="media.type" />
                                <template x-if="media.is_existing">
                                    <input type="hidden" :name="'media[' + index + '][id]'" :value="media.id" />
                                </template>

                                {{-- 🌟 Visual Main Image Badge --}}
                                <label
                                    x-show="media.type === 'image'"
                                    class="absolute top-2 left-2 z-20 cursor-pointer transition-opacity"
                                    :class="primaryMediaId == media.id ?
                                        'opacity-100' :
                                        'opacity-0 group-hover:opacity-100'"
                                >
                                    <input type="radio" :value="media.id" x-model="primaryMediaId" class="hidden" />
                                    <div
                                        :class="primaryMediaId == media.id ?
                                            'bg-[#108c2a] text-white shadow-md' :
                                            'bg-white text-gray-600 shadow border border-gray-200 hover:bg-gray-50'"
                                        class="flex items-center gap-1 rounded-md px-2 py-1 text-[10px] font-bold tracking-wide transition-colors"
                                    >
                                        <i
                                            data-lucide="star"
                                            class="h-3 w-3"
                                            :class="primaryMediaId == media.id ? 'fill-current' : ''"
                                        ></i>
                                        <span x-text="primaryMediaId == media.id ? 'Main' : 'Set Main'"></span>
                                    </div>
                                </label>

                                {{-- Delete Button Overlay --}}
                                <button
                                    type="button"
                                    @click="removeMedia(index)"
                                    class="absolute top-2 right-2 z-20 rounded-md border border-gray-200 bg-white p-1.5 text-red-500 opacity-0 shadow transition-opacity group-hover:opacity-100 hover:bg-red-50"
                                >
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>

                                {{-- Media Preview Area (Perfectly Square) --}}
                                <div
                                    class="relative flex aspect-square w-full items-center justify-center overflow-hidden border-b border-gray-200 bg-gray-100"
                                >
                                    {{-- Drag Handle (Overlay on hover) --}}
                                    <div
                                        class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center bg-black/5 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <div class="rounded bg-white/90 p-1.5 shadow-sm backdrop-blur-sm">
                                            <i data-lucide="grip" class="h-4 w-4 text-gray-500"></i>
                                        </div>
                                    </div>

                                    {{-- Image Logic --}}
                                    <template x-if="media.type === 'image'">
                                        <div class="relative h-full w-full">
                                            {{-- Invisible file input overlaid ONLY for new images --}}
                                            <template x-if="!media.is_existing">
                                                <input
                                                    type="file"
                                                    :name="'media[' + index + '][file]'"
                                                    accept="image/*"
                                                    :required="!media.preview"
                                                    @change="
                                                        if ($event.target.files.length > 0) {
                                                            media.preview = URL.createObjectURL($event.target.files[0]);
                                                        }
                                                    "
                                                    class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                                />
                                            </template>

                                            {{-- Instant Preview Image (Works for both existing saved images and newly uploaded blobs) --}}
                                            <template x-if="media.preview">
                                                <img :src="media.preview" class="h-full w-full object-cover" />
                                            </template>
                                            {{-- Placeholder before selection --}}
                                            <template x-if="!media.preview">
                                                <div
                                                    class="flex h-full w-full flex-col items-center justify-center gap-2 text-gray-400"
                                                >
                                                    <i data-lucide="image-plus" class="h-6 w-6"></i>
                                                    <span class="text-[10px] font-bold tracking-wider uppercase"
                                                        >Click to Browse</span
                                                    >
                                                </div>
                                            </template>

                                            {{-- 🌟 Saved Image Indicator Overlay --}}
                                            <template x-if="media.is_existing">
                                                <div
                                                    class="absolute right-0 bottom-0 left-0 z-10 flex items-center justify-between bg-black/50 px-2 py-1 text-[9px] text-white"
                                                >
                                                    <span>Saved in DB</span>
                                                    <span x-text="'ID: ' + media.id"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- YouTube Logo --}}
                                    <template x-if="media.type === 'youtube'">
                                        <div
                                            class="flex h-full w-full flex-col items-center justify-center gap-2 bg-red-50/50 text-red-500"
                                        >
                                            <i data-lucide="youtube" class="h-8 w-8"></i>
                                            <span class="text-[10px] font-bold tracking-wider text-red-700 uppercase"
                                                >Video</span
                                            >
                                        </div>
                                    </template>
                                </div>

                                {{-- Footer Controls (Inputs nested cleanly at bottom of card) --}}
                                <div class="relative z-20 flex flex-1 flex-col justify-end gap-2 bg-white p-2.5">
                                    {{-- YouTube URL Input --}}
                                    <template x-if="media.type === 'youtube'">
                                        <div>
                                            <input
                                                type="url"
                                                :name="'media[' + index + '][url]'"
                                                x-model="media.url"
                                                placeholder="Paste YouTube URL..."
                                                required
                                                class="w-full rounded border border-gray-300 px-2 py-1.5 text-[11px] transition-colors outline-none focus:border-red-400"
                                            />
                                        </div>
                                    </template>

                                    {{-- Unified Variant Dropdown (edit.blade uses variant.id) --}}
                                    <template x-if="productType === 'variable'">
                                        <div>
                                            <select
                                                :name="'media[' + index + '][sku_index]'"
                                                x-model="media.sku_index"
                                                class="w-full rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-[10px] text-gray-600 transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                            >
                                                <option value="">All Variants</option>
                                                <template x-for="(variant, vIndex) in variations" :key="variant.id">
                                                    <option
                                                        :value="variant.id"
                                                        x-text="
                                                            variant.sku
                                                                ? 'SKU: ' + variant.sku
                                                                : 'Variant ' + (vIndex + 1)
                                                        "
                                                    ></option>
                                                </template>
                                            </select>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- 🌟 The "Add Media" Action Cards --}}
                        <button
                            type="button"
                            @click="addMedia('image')"
                            class="group relative flex aspect-square flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 text-gray-400 transition-colors hover:border-[#108c2a] hover:bg-[#108c2a]/5 hover:text-[#108c2a]"
                        >
                            <div
                                class="mb-2 rounded-full bg-gray-100 p-3 transition-colors group-hover:bg-[#108c2a]/10"
                            >
                                <i data-lucide="image-plus" class="h-5 w-5"></i>
                            </div>
                            <span class="text-[11px] font-bold tracking-wider uppercase">Add Image</span>
                        </button>

                        <button
                            type="button"
                            @click="addMedia('youtube')"
                            class="group relative flex aspect-square flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 text-gray-400 transition-colors hover:border-red-400 hover:bg-red-50 hover:text-red-500"
                        >
                            <div class="mb-2 rounded-full bg-gray-100 p-3 transition-colors group-hover:bg-red-100">
                                <i data-lucide="youtube" class="h-5 w-5"></i>
                            </div>
                            <span class="text-[11px] font-bold tracking-wider uppercase">Add Video</span>
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    @if (has_module('plant_education'))
{{-- ── Plant Education: 2 fixed care fields ── --}}
                    <div class="mb-5 flex items-center gap-2.5 border-b border-gray-100 pb-3">
                        <i data-lucide="leaf" class="h-5 w-5 shrink-0 text-green-600"></i>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">
                                5. Product Information
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-500">Add Product Guidance</p>
                        </div>
                    </div>

                    @php
                        $guideMap = collect($product->product_guide ?? [])->keyBy('title');
                        $sunlightDesc = $guideMap->get('Sunlight')['description'] ?? '';
                        $wateringDesc = $guideMap->get('Watering')['description'] ?? '';
                    @endphp

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {{-- Sunlight --}}
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="text-xl">☀️</span>
                                <label class="text-[13px] font-bold text-amber-800">Sunlight</label>
                            </div>
                            <input type="hidden" name="product_guide[0][title]" value="Sunlight" />
                            <input
                                type="text"
                                name="product_guide[0][description]"
                                value="{{ old('product_guide.0.description', $sunlightDesc) }}"
                                placeholder="4–6 hours of indirect sunlight"
                                class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm transition-all outline-none focus:border-amber-400"
                            />
                        </div>

                        {{-- Watering --}}
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="text-xl">💧</span>
                                <label class="text-[13px] font-bold text-blue-800">Watering</label>
                            </div>
                            <input type="hidden" name="product_guide[1][title]" value="Watering" />
                            <input
                                type="text"
                                name="product_guide[1][description]"
                                value="{{ old('product_guide.1.description', $wateringDesc) }}"
                                placeholder="1–2 times a week"
                                class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm transition-all outline-none focus:border-blue-400"
                            />
                        </div>
                    </div>

                    {{-- Additional Info sections (optional) — indices start at 2 after Sunlight+Watering --}}
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-700">Additional Info</p>
                                <p class="mt-0.5 text-xs text-gray-500">Add extra care tips, fertilizing notes, repotting info, etc.</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(guide, index) in productGuides" :key="guide.id">
                                <div class="relative rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <button
                                        type="button"
                                        @click="removeGuide(index)"
                                        class="absolute top-3 right-3 rounded p-1.5 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Remove"
                                    >
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                    <div class="grid grid-cols-1 gap-3 pr-10">
                                        <div>
                                            <label class="mb-1 block text-[12px] font-bold text-gray-700"
                                                >Section Title <span class="text-red-500">*</span></label
                                            >
                                            <input
                                                type="text"
                                                :name="'product_guide[' + (index + 2) + '][title]'"
                                                x-model="guide.title"
                                                required
                                                placeholder=" Fertilizing Tips"
                                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[12px] font-bold text-gray-700"
                                                >Details <span class="text-red-500">*</span></label
                                            >
                                            <textarea
                                                :name="'product_guide[' + (index + 2) + '][description]'"
                                                x-model="guide.description"
                                                required
                                                rows="2"
                                                placeholder=" Feed monthly during the growing season."
                                                class="w-full resize-y rounded-md border border-gray-300 bg-white px-3 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                                            ></textarea>
                    </div>
                </div>
            </div>
            </template>
    </div>

    <button type="button" @click="addGuide()"
        class="mt-4 flex w-fit items-center gap-1 rounded bg-[#108c2a]/10 px-3 py-1.5 text-xs font-bold text-[#108c2a] transition-colors hover:bg-[#108c2a]/20">
        <i data-lucide="plus-circle" class="h-3 w-3"></i> Add Section
    </button>
    </div>
    </div>
    @endif

    <div class="flex flex-col justify-end border-t border-gray-200 pt-4 sm:flex-row">
        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#108c2a] px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0c6b1f] sm:w-auto">
            <i data-lucide="save" class="h-4 w-4"></i> Update Product
        </button>
    </div>
    </form>
    </div>
@endsection

@push('scripts')
    <script>
        function productForm() {
            return {
                catalogMode: "{{ strtolower(old('product_type', $product->product_type ?: 'sellable')) }}",
                productType: "{{ strtolower(old('type', $product->skus->count() > 1 ? 'variable' : ($product->type ?: 'single'))) }}",
                singleSku: @json(old('single_sku', $singleSku ? $singleSku->sku : '')),
                singleMrp: @json(old('single_mrp', $singleSku ? $singleSku->mrp : '')),
                singleBarcode: @json(old('single_barcode', $singleSku ? $singleSku->barcode : '')),
                // 🌟 Pre-load Variations from Database
                @php
                    $variations = old(
                        'variations',
                        $product->skus->count() > 0
                            ? $product->skus
                                ->map(function ($sku) {
                                    return [
                                        'id' => $sku->id,
                                        'is_existing' => true,
                                        'sku' => $sku->sku,
                                        'barcode' => $sku->barcode,
                                        'price' => $sku->price,
                                        'cost' => $sku->cost,
                                        'mrp' => $sku->mrp,
                                        'tax_type' => $sku->tax_type,
                                        'order_tax' => $sku->order_tax,
                                        'alert' => $sku->stock_alert,
                                        'hsn_code' => $sku->hsn_code ?? '',
                                        'attrs' => (object) $sku->skuValues->pluck('attribute_value_id', 'attribute_id')->toArray(),
                                        'label' => $sku->skuValues->map(fn($sv) => $sv->attributeValue?->value)->filter()->implode(' / ') ?: null,
                                    ];
                                })
                                ->values()
                                ->toArray()
                            : [
                                [
                                    'id' => 'temp_' . time(),
                                    'is_existing' => false,
                                    'sku' => '',
                                    'barcode' => '',
                                    'price' => '',
                                    'cost' => '',
                                    'mrp' => '',
                                    'tax_type' => 'exclusive',
                                    'order_tax' => 0,
                                    'alert' => 0,
                                    'hsn_code' => '',
                                    'attrs' => (object) [],
                                ],
                            ],
                    );
                @endphp

                variations: @json($variations).map((v, i) => ({
                    ...v,
                    id: v.id || 'var_' + Date.now() + i
                })),

                // 🌟 Pre-load Media from Database
                @php
                    $skuIndexMap = collect($variations)->pluck('id')->filter(fn($id) => is_numeric($id))->mapWithKeys(fn($id, $index) => [(int) $id => $index])->all();

                    $mediaList = old(
                        'media',
                        $product->media
                            ->sortBy('sort_order')
                            ->map(function ($m) use ($skuIndexMap) {
                                return [
                                    'id' => $m->id,
                                    'is_existing' => true,
                                    'type' => $m->media_type,
                                    'url' => $m->media_type === 'youtube' ? $m->media_path : '',
                                    'preview' => $m->media_type === 'image' ? asset('storage/' . $m->media_path) : '',
                                    'sku_index' => $m->product_sku_id !== null ? $skuIndexMap[$m->product_sku_id] ?? '' : '',
                                ];
                            })
                            ->values()
                            ->toArray(),
                    );
                @endphp

                mediaList: @json($mediaList).map((m, i) => ({
                    ...m,
                    id: m.id || 'media_' + Date.now() + i
                })),

                primaryMediaId: @json(optional($product->media->where('is_primary', true)->first())->id),

                draggedIndex: null,

                // 🌟 Pre-load Product Guides
                @php
                    // When plant_education is active, Sunlight & Watering are fixed inputs (index 0 & 1).
                    // Only load remaining additional guides into the dynamic Alpine array.
                    $isPlantModule = has_module('plant_education');
                    $guides = old(
                        'product_guide',
                        collect($product->product_guide ?? [])
                            ->when($isPlantModule, fn($c) => $c->filter(fn($g) => !in_array($g['title'] ?? '', ['Sunlight', 'Watering'])))
                            ->map(function ($g) {
                                return [
                                    'id' => 'guide_' . uniqid(),
                                    'title' => $g['title'] ?? '',
                                    'description' => $g['description'] ?? '',
                                ];
                            })
                            ->values()
                            ->toArray(),
                    );
                @endphp

                productGuides: @json($guides).map((g, i) => ({
                    ...g,
                    id: g.id || 'guide_' + Date.now() + i
                })),

                addGuide() {
                    if (this.productGuides.length >= 50) {
                        BizAlert.toast('Maximum 50 guidance sections allowed.', 'error');
                        return;
                    }

                    this.productGuides.push({
                        id: Date.now(),
                        title: '',
                        description: ''
                    });

                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 50);
                },

                removeGuide(index) {
                    this.productGuides.splice(index, 1);
                },

                addMedia(type) {
                    if (this.mediaList.length >= 10) {
                        return BizAlert.toast('Maximum 10 media items allowed.', 'error');
                    }

                    const newItem = {
                        id: Date.now(),
                        is_existing: false,
                        type: type,
                        url: '',
                        preview: '',
                        sku_index: ''
                    };

                    this.mediaList.push(newItem);

                    if (type === 'image' && !this.primaryMediaId) {
                        this.primaryMediaId = newItem.id;
                    }

                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 50);
                },

                removeMedia(index) {
                    const removedId = this.mediaList[index].id;
                    this.mediaList.splice(index, 1);

                    // THE FIX: Use == so string "8" matches integer 8
                    if (this.primaryMediaId == removedId) {
                        const nextImage = this.mediaList.find(m => m.type === 'image');
                        this.primaryMediaId = nextImage ? nextImage.id : null;
                    }
                },

                dragStart(index, event) {
                    this.draggedIndex = index;
                    event.dataTransfer.effectAllowed = 'move';
                },

                dragEnd() {
                    this.draggedIndex = null;
                },

                dragOver(event) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                },

                drop(index) {
                    if (this.draggedIndex === null || this.draggedIndex === index) return;

                    const draggedItem = this.mediaList.splice(this.draggedIndex, 1)[0];
                    this.mediaList.splice(index, 0, draggedItem);

                    this.draggedIndex = null;
                },

                // 🌟 Intelligent Formatting for Attributes
                formatSkuAttribute(val) {
                    let cleanVal = val.toUpperCase().trim();
                    const sizeMap = {
                        'SMALL': 'S',
                        'MEDIUM': 'M',
                        'LARGE': 'L',
                        'EXTRA LARGE': 'XL',
                        'EXTRA SMALL': 'XS'
                    };
                    if (sizeMap[cleanVal]) return sizeMap[cleanVal];
                    return cleanVal.substring(0, 3);
                },

                generateBarcode() {
                    return Math.floor(Math.random() * 9000000000000) + 1000000000000;
                },

                generateSKU(length = 8, prefix = 'SKU-') {
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    let result = prefix;

                    // Use crypto for better randomness
                    const randomValues = new Uint32Array(length);
                    crypto.getRandomValues(randomValues);

                    for (let i = 0; i < length; i++) {
                        result += chars[randomValues[i] % chars.length];
                    }

                    return result;
                },

                // 🌟 New Intelligent SKU Builder
                generateIntelligentSku(comboNames, usedSkus) {
                    let nameInput = document.querySelector('input[name="name"]');
                    let pName = nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'PRD';
                    let prefix = pName.substring(0, 3).toUpperCase();
                    let attrCodes = comboNames.map(name => this.formatSkuAttribute(name));
                    let baseSku = [prefix, ...attrCodes].join('-');
                    let finalSku = baseSku;
                    let counter = 1;

                    while (usedSkus.has(finalSku.toUpperCase())) {
                        finalSku = `${baseSku}-${counter.toString().padStart(2, '0')}`;
                        counter++;
                    }
                    usedSkus.add(finalSku.toUpperCase());
                    return finalSku;
                },

                calculateCombinations() {
                    let total = 1;
                    let hasSelection = false;
                    for (const key in this.selectedValues) {
                        if (this.selectedValues[key] && this.selectedValues[key].length > 0) {
                            total *= this.selectedValues[key].length;
                            hasSelection = true;
                        }
                    }
                    return hasSelection ? total : 0;
                },

                confirmVariantGeneration() {
                    const total = this.calculateCombinations();
                    if (total === 0) {
                        return BizAlert.toast('Please select at least one attribute.', 'error');
                    }
                    if (total > 100) {
                        return BizAlert.toast(`Blocked: Attempting to generate ${total} variants. Maximum allowed is 100.`,
                            'error');
                    }
                    if (total > 50) {
                        if (!confirm(`Warning: You are about to generate ${total} variants. Continue?`)) return;
                    }
                    this.generateVariants();
                },

                applyBulkEdit() {
                    if (this.variations.length === 0) return;
                    let applied = false;
                    this.variations.forEach(variant => {
                        if (this.bulk.price !== '') {
                            variant.price = this.bulk.price;
                            applied = true;
                        }
                        if (this.bulk.cost !== '') {
                            variant.cost = this.bulk.cost;
                            applied = true;
                        }
                        if (this.bulk.tax_type !== '') {
                            variant.tax_type = this.bulk.tax_type;
                            applied = true;
                        }
                        if (this.bulk.tax_percent !== '') {
                            variant.order_tax = this.bulk.tax_percent;
                            applied = true;
                        }
                        if (this.bulk.alert !== '') {
                            variant.alert = this.bulk.alert;
                            applied = true;
                        }
                    });
                    if (applied) BizAlert.toast(`Bulk updated ${this.variations.length} variants successfully!`, 'success');
                    this.bulk = {
                        price: '',
                        cost: '',
                        tax_type: '',
                        tax_percent: '',
                        alert: ''
                    };
                },

                generateVariants() {
                    let arraysToCombine = [];
                    let attrKeys = [];

                    for (const [attrId, values] of Object.entries(this.selectedValues)) {
                        if (values && values.length > 0) {
                            arraysToCombine.push(values);
                            attrKeys.push(attrId);
                        }
                    }

                    const combine = (arrs) => arrs.reduce((a, b) => a.flatMap(d => b.map(e => [...d, e])), [
                        []
                    ]);
                    const combinations = combine(arraysToCombine);

                    let usedSkus = new Set();
                    let existingSignatures = new Set();

                    // Track existing items so we don't accidentally overwrite or duplicate them
                    this.variations.forEach(v => {
                        if (v.sku) usedSkus.add(v.sku.toUpperCase());
                        if (v.attrs) existingSignatures.add(JSON.stringify(v.attrs));
                    });

                    let addedCount = 0;

                    combinations.forEach((combo) => {
                        let labelParts = [];
                        let attrsPayload = {};

                        combo.forEach((valString, index) => {
                            let [valId, valName] = valString.split('::');
                            labelParts.push(valName);
                            attrsPayload[attrKeys[index]] = valId;
                        });

                        // Only push to the table if this exact attribute combination doesn't already exist
                        if (!existingSignatures.has(JSON.stringify(attrsPayload))) {
                            this.variations.push({
                                id: Date.now() + Math.random().toString(36).substr(2, 9),
                                is_existing: false,
                                label: labelParts.join(' / '),
                                attrs: attrsPayload,
                                sku: this.generateIntelligentSku(labelParts, usedSkus),
                                barcode: '',
                                mrp: this.singleMrp || '',
                                price: '',
                                cost: '',
                                tax_type: 'exclusive',
                                order_tax: '',
                                hsn_code: '',
                                alert: 0,
                                stocks: [] // 🌟 CRITICAL: Init empty stock array
                            });
                            addedCount++;
                            existingSignatures.add(JSON.stringify(attrsPayload));
                        }
                    });

                    if (addedCount > 0) {
                        BizAlert.toast(`${addedCount} new variants added!`, 'success');
                    } else {
                        BizAlert.toast(`Combinations already exist. No new variants added.`, 'info');
                    }

                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 50);
                },

                clearSelections() {
                    for (let key in this.selectedValues) {
                        this.selectedValues[key] = [];
                    }
                },

                removeVariation(index) {
                    if (this.variations.length > 1) {
                        this.variations.splice(index, 1);
                    } else {
                        BizAlert.toast('You must have at least one variation.', 'error');
                    }
                },

                // 🌟 Initialize array for every attribute ID on page load
                selectedValues: {
                    @foreach ($attributes as $attr)
                        '{{ $attr->id }}': [],
                    @endforeach
                },

                // 🌟 Bulk Edit Tracker
                bulk: {
                    price: '',
                    cost: '',
                    tax_type: '',
                    tax_percent: '',
                    alert: ''
                },
            }
        }
    </script>
@endpush
