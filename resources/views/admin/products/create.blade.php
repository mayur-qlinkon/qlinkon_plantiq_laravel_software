@extends ('layouts.admin')

@section('title', 'Add New Product')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}"
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition-colors hover:bg-slate-200 hover:text-slate-900">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
        </a>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Create Product</h1>
    </div>
@endsection
@push('styles')
    <style>
        /* Clean minimal scrollbars for the horizontal wrappers */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }

        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush
@section('content')
    @php
        // Prepare string-keyed options collections for structural custom-select items
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
                {{-- <h1 class="text-2xl font-bold text-[#212538] tracking-tight">Add New Product</h1> --}}
                <p class="mt-1 text-sm font-medium text-gray-500">Create a new item in your inventory.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 shadow-sm">
                <div class="mb-1 flex items-center gap-2 font-bold">
                    <i data-lucide="alert-circle" class="h-4 w-4"></i> Please fix the following errors:
                </div>
                <ul class="ml-6 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
            @submit="BizAlert.loading('Saving Product...')">
            @csrf

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 border-b border-gray-100 pb-2 text-lg font-bold text-gray-800">1. Basic Information</h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Product Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Category <span
                                class="text-red-500">*</span></label>
                        <x-custom-select name="category_id" placeholder="Select Category" :options="$categoryOptions"
                            :selected="old('category_id', '')" required />
                    </div>


                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">HSN Code</label>
                        <input type="text" name="hsn_code" value="{{ old('hsn_code') }}" placeholder="61091000"
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Status</label>
                        <div x-data="{ isActive: '{{ old('is_active', '1') }}' }"
                            class="flex h-[42px] overflow-hidden rounded-lg border border-gray-300">
                            <input type="hidden" name="is_active" :value="isActive" />
                            <button type="button" @click="isActive = '1'"
                                :class="isActive === '1'
                                    ?
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
                                :class="isActive === '0'
                                    ?
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
                        <div x-data="{ inStorefront: '{{ old('show_in_storefront', '1') }}' }"
                            class="flex h-[42px] overflow-hidden rounded-lg border border-gray-300">
                            <input type="hidden" name="show_in_storefront" :value="inStorefront" />
                            <button type="button" @click="inStorefront = '1'"
                                :class="inStorefront === '1'
                                    ?
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
                                :class="inStorefront === '0'
                                    ?
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
                            :selected="old('supplier_id', '')" />
                    </div>

                    @if (has_module('storefront'))
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Show as Addon</label>
                            <label
                                class="flex h-[42px] cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-3 text-[13px] font-semibold text-gray-700 hover:bg-gray-50">
                                <input type="hidden" name="show_as_addon" value="0" />
                                <input type="checkbox" name="show_as_addon" value="1"
                                    {{ old('show_as_addon') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                                People Also Buy
                            </label>
                        </div>
                    @endif

                    <div class="lg:col-span-3">
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]">{{ old('description') }}</textarea
                        >
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="mb-5 border-b border-gray-100 pb-2 text-lg font-bold text-gray-800">
                    2. Units & Measurements
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700"
                            >Product Unit <span class="text-red-500">*</span></label
                        >
                        <x-custom-select
                            name="product_unit_id"
                            placeholder="Select Base Unit"
                            :options="$unitOptions"
                            :selected="old('product_unit_id', '')"
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
                            :selected="old('sale_unit_id', '')"
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
                            :selected="old('purchase_unit_id', '')"
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
                    <h2 class="text-base font-bold text-gray-800 sm:text-lg">3. Product Pricing & Stock</h2>

                    <div class="flex w-full items-center rounded-lg bg-gray-100 p-1 sm:w-auto">
                        <label class="flex-1 cursor-pointer text-center sm:flex-none">
                            <input type="radio" name="type" value="single" x-model="productType" class="peer hidden" />
                            <span
                                class="block rounded-md px-3 py-1.5 text-xs font-bold whitespace-nowrap text-gray-500 transition-all peer-checked:bg-white peer-checked:text-[#108c2a] peer-checked:shadow-sm sm:px-4 sm:text-sm"
                                >Single Item</span
                            >
                        </label>
                        <label class="flex-1 cursor-pointer text-center sm:flex-none">
                            <input
                                type="radio"
                                name="type"
                                value="variable"
                                x-model="productType"
                                class="peer hidden"
                            />
                            <span
                                class="block rounded-md px-3 py-1.5 text-xs font-bold whitespace-nowrap text-gray-500 transition-all peer-checked:bg-white peer-checked:text-[#108c2a] peer-checked:shadow-sm sm:px-4 sm:text-sm"
                                >Variable Product</span
                            >
                        </label>
                    </div>
                </div>

                <div x-show="catalogMode !== 'catalog'" x-cloak>
                    <div x-show="productType === 'single'" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
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
                                    placeholder="Type or auto-generate"
                                    class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                                />

                                <button
                                    type="button"
                                    @click="singleSku = generateSKU()"
                                    title="Generate Random SKU"
                                    class="flex-shrink-0 rounded-md border border-gray-200 bg-gray-100 px-3 py-2.5 text-gray-600 transition-colors hover:bg-gray-200"
                                >
                                    <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>

                        {{-- 🌟 UPDATED: Barcode Field with Generate Button --}}
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
                                    class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                                />

                                <button
                                    type="button"
                                    @click="singleBarcode = generateBarcode()"
                                    title="Generate Random Barcode"
                                    class="flex-shrink-0 rounded-md border border-gray-200 bg-gray-100 px-3 py-2.5 text-gray-600 transition-colors hover:bg-gray-200"
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
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]"
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
                                value="{{ old('single_price') }}"
                                :required="productType === 'single' && catalogMode !== 'catalog'"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]"
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
                                value="{{ old('single_cost') }}"
                                :required="productType === 'single' && catalogMode !== 'catalog'"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none focus:border-[#108c2a]"
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
                            <div x-data="{ taxType: '{{ old('single_tax_type', 'exclusive') }}' }">
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
                                value="{{ old('single_order_tax', 0) }}"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2 text-sm transition-all outline-none focus:border-[#108c2a]"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Low Stock Alert</label>
                            <input
                                type="number"
                                name="single_stock_alert"
                                value="{{ old('single_stock_alert', 0) }}"
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
                                value="{{ old('single_hsn_code') }}"
                                placeholder=" 61091000"
                                class="w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm uppercase transition-all outline-none focus:border-[#108c2a]"
                            />
                            <p class="mt-1 text-[11px] text-gray-400">Leave empty to use product HSN</p>
                        </div>

                        {{-- Opening stock — optional. Mirrors the variant stock modal: the
                             visible controls live in the modal and carry no name, the form
                             payload comes from the hidden inputs below. --}}
                        <div>
                            <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Opening Stock</label>
                            <button
                                type="button"
                                @click="openSingleStockModal()"
                                class="flex h-[42px] w-full items-center justify-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 text-sm font-bold text-blue-700 transition-colors hover:bg-blue-100"
                            >
                                <i data-lucide="package" class="h-4 w-4"></i>
                                <span x-text="calculateSingleStock() > 0 ? calculateSingleStock() + ' Units' : '+ Add Stock'"></span>
                            </button>

                            {{-- Hidden payload for ProductService::processInitialStock --}}
                            <template x-for="(stock, stockIdx) in singleStocks" :key="stock.id">
                                <div>
                                    <input type="hidden" :name="'single_stock[' + stockIdx + '][warehouse_id]'" :value="stock.warehouse_id" />
                                    <input type="hidden" :name="'single_stock[' + stockIdx + '][qty]'" :value="stock.qty" />
                                </div>
                            </template>
                        </div>
                        <div class="hidden" x-data="{
                            rows: [],
                            addRow() { this.rows.push({ id: Date.now() + Math.random(), warehouse_id: '', qty: '' }); },
                            removeRow(i) { this.rows.splice(i, 1); },
                        }">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center gap-2">
                                    <i data-lucide="package" class="h-4 w-4 text-gray-500"></i>
                                    <h4 class="text-[13px] font-bold text-gray-700">Opening Stock</h4>
                                    <span class="ml-1 text-[11px] text-gray-400">(Optional — recorded as an opening stock movement)</span>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(row, i) in rows" :key="row.id">
                                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-2">
                                            <select
                                                x-model="row.warehouse_id"
                                                :name="'single_stock[' + i + '][warehouse_id]'"
                                                :disabled="!row.warehouse_id || !row.qty"
                                                class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#108c2a]"
                                            >
                                                <option value="">Select Warehouse...</option>
                                                @foreach ($warehouses ?? [] as $wh)
<option value="{{ $wh->id }}">{{ $wh->name }}</option>
@endforeach
                                            </select>

                                            <input
                                                type="number"
                                                min="1"
                                                x-model="row.qty"
                                                :name="'single_stock[' + i + '][qty]'"
                                                :disabled="!row.warehouse_id || !row.qty"
                                                placeholder="Qty"
                                                class="w-24 rounded-md border border-gray-300 px-3 py-2 text-center text-sm font-bold text-gray-800 outline-none focus:border-[#108c2a]"
                                            />

                                            <button
                                                type="button"
                                                @click="removeRow(i)"
                                                title="Remove"
                                                class="rounded-md p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <button
                                    type="button"
                                    @click="addRow(); $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
                                    class="mt-3 flex items-center justify-center gap-1.5 rounded-lg border border-dashed border-[#108c2a]/40 px-3 py-2 text-xs font-bold text-[#108c2a] transition-colors hover:bg-[#108c2a]/10"
                                    :class="rows.length === 0 ? 'w-full' : 'w-fit'"
                                >
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    <span x-text="rows.length === 0 ? 'Add Opening Stock' : 'Add Warehouse Allocation'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="productType === 'variable'" x-cloak>
                        <div
                            class="mb-6 flex items-start gap-3 rounded-xl border border-[#108c2a]/20 bg-[#108c2a]/5 p-4"
                        >
                            <i data-lucide="layers" class="mt-0.5 h-5 w-5 text-[#108c2a]"></i>
                            <div>
                                <p class="text-sm font-bold text-gray-800">Variant Generator</p>
                                <p class="mt-1 text-[13px] text-gray-600">Select the attributes below and click generate. We'll automatically create the matrix of all combinations for you.</p>
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
{{-- Dynamic Alpine classes to make them look like interactive pills --}}
                                                {{-- 🌟 Added safe array wrapper ( ... || []) so Alpine doesn't crash on boot --}}
                                                <label
                                                    :class="(selectedValues[{{ $attr->id }}] || []).includes(
                                                            '{{ $val->id }}::{{ $val->value }}') ?
                                                        'bg-[#108c2a] border-[#108c2a] text-white shadow-sm' :
                                                        'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                                    class="relative inline-flex cursor-pointer items-center justify-center rounded-lg border px-4 py-2 text-[13px] font-bold transition-all select-none"
                                                >
                                                    {{-- The value is combined ID and Name so Alpine can easily parse it for the table --}}
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
                                {{-- 🌟 Updated to call confirmVariantGeneration() --}}
                                <button
                                    type="button"
                                    @click="confirmVariantGeneration()"
                                    class="flex items-center gap-2 rounded-lg bg-[#108c2a] px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0c6b1f]"
                                >
                                    <i data-lucide="sparkles" class="h-4 w-4"></i> Generate Combinations
                                </button>
                                <button
                                    type="button"
                                    @click="clearGeneratedVariants()"
                                    class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50"
                                >
                                    <i data-lucide="rotate-ccw" class="h-4 w-4 text-gray-400"></i> Clear Selections
                                </button>
                            </div>
                        </div>

                        {{-- STEP 2: Spreadsheet Table --}}
                        <div x-show="generatedVariants.length > 0" x-cloak x-transition>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-[13px] font-bold tracking-wider text-gray-400 uppercase">
                                    Step 2: Pricing & Inventory
                                </h3>

                                {{-- 🌟 Variant Count Badge & Clear Action --}}
                                <div class="flex items-center gap-3">
                                    <span
                                        class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"
                                        x-text="generatedVariants.length + ' Variants Generated'"
                                    ></span>
                                    <button
                                        type="button"
                                        @click="clearGeneratedVariants()"
                                        class="text-[11px] font-bold text-red-500 hover:text-red-700 hover:underline"
                                    >
                                        Clear All
                                    </button>
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
                                            <th class="px-4 py-3">SKU <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3">Barcode</th>
                                            <th class="px-4 py-3">MRP (₹)</th>
                                            <th class="px-4 py-3">Price (₹) <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3">Cost (₹) <span class="text-red-500">*</span></th>
                                            <th class="px-4 py-3">Tax Type</th>
                                            <th class="px-4 py-3">Tax (%)</th>
                                            <th class="px-4 py-3">HSN Code</th>
                                            <th class="px-4 py-3">Alert Qty</th>
                                            <th class="px-4 py-3 text-center">Stock (Whs)</th>
                                            <th class="px-4 py-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(variant, index) in generatedVariants" :key="variant.id">
                                            <tr class="transition-colors hover:bg-gray-50/50">
                                                {{-- Sticky left column so the variant name never scrolls out of view --}}
                                                <td
                                                    class="sticky left-0 z-10 border-r border-gray-100 bg-white px-4 py-2 text-[13px] font-bold text-gray-800 shadow-[1px_0_0_0_#f3f4f6]"
                                                    x-text="variant.label"
                                                ></td>

                                                <td class="px-2 py-2">
                                                    {{-- 🌟 Removed 'required', changed placeholder to indicate auto-generation --}}
                                                    <input
                                                        type="text"
                                                        :name="'variations[' + index + '][sku]'"
                                                        x-model="variant.sku"
                                                        class="w-32 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm uppercase transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="Auto-generated"
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
                                                        class="w-28 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0.00"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][price]'"
                                                        x-model="variant.price"
                                                        required
                                                        class="w-28 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm font-bold text-gray-800 transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0.00"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        :name="'variations[' + index + '][cost]'"
                                                        x-model="variant.cost"
                                                        required
                                                        class="w-28 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
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
                                                        class="w-28 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="HSN"
                                                    />
                                                </td>

                                                <td class="px-2 py-2">
                                                    <input
                                                        type="number"
                                                        :name="'variations[' + index + '][stock_alert]'"
                                                        x-model="variant.alert"
                                                        class="w-24 rounded border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                                        placeholder="0"
                                                    />
                                                </td>

                                                {{-- 🌟 NEW: Stock Management Cell --}}
                                                <td class="px-2 py-2 text-center">
                                                    <button
                                                        type="button"
                                                        @click="openStockModal(index)"
                                                        class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold whitespace-nowrap text-blue-700 transition-colors hover:bg-blue-100"
                                                    >
                                                        <span
                                                            x-text="
                                                                calculateTotalStock(variant) > 0
                                                                    ? calculateTotalStock(variant) + ' Units'
                                                                    : '+ Add Stock'
                                                            "
                                                        ></span>
                                                    </button>

                                                    {{-- Hidden Payload for Laravel's processInitialStock --}}
                                                    <template
                                                        x-for="(stock, stockIdx) in variant.stocks"
                                                        :key="stock.id"
                                                    >
                                                        <div>
                                                            <input
                                                                type="hidden"
                                                                :name="'variations[' +
                                                                index +
                                                                    '][stock][' +
                                                                    stockIdx +
                                                                    '][warehouse_id]'"
                                                                :value="stock.warehouse_id"
                                                            />
                                                            <input
                                                                type="hidden"
                                                                :name="'variations[' +
                                                                index +
                                                                    '][stock][' +
                                                                    stockIdx +
                                                                    '][qty]'"
                                                                :value="stock.qty"
                                                            />
                                                        </div>
                                                    </template>
                                                </td>

                                                <td class="px-3 py-2 text-center">
                                                    <button
                                                        type="button"
                                                        @click="
                                                            typeof removeGeneratedVariant === 'function'
                                                                ? removeGeneratedVariant(index)
                                                                : removeVariation(index)
                                                        "
                                                        title="Remove Variant"
                                                        class="rounded p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </td>

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
                                <template x-for="(variant, index) in generatedVariants" :key="variant.id">
                                    <div
                                        class="relative flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                                    >
                                        {{-- Header --}}
                                        <div class="flex items-start justify-between pr-8">
                                            <div
                                                class="text-[14px] font-bold text-gray-800"
                                                x-text="variant.label"
                                            ></div>
                                            <button
                                                type="button"
                                                @click="removeGeneratedVariant(index)"
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
                                                    required
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
                                                    required
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

                                        {{-- Hidden Attribute Payload --}}
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
                        :value="mediaList.findIndex((m) => m.id === primaryMediaId)"
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
                                    class="relative flex aspect-square w-full items-center justify-center border-b border-gray-200 bg-gray-100"
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
                                            {{-- Invisible file input overlaid to allow clicking the whole card to upload --}}
                                            <input
                                                type="file"
                                                :name="'media[' + index + '][file]'"
                                                accept="image/*"
                                                required
                                                @change="
                                                    if ($event.target.files.length > 0) {
                                                        media.preview = URL.createObjectURL($event.target.files[0]);
                                                    }
                                                "
                                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                            />

                                            {{-- Instant Preview Image --}}
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

                                    {{-- Unified Variant Dropdown --}}
                                    <template x-if="productType === 'variable'">
                                        <div>
                                            <select
                                                :name="'media[' + index + '][sku_index]'"
                                                x-model="media.sku_index"
                                                class="w-full rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-[10px] text-gray-600 transition-colors outline-none focus:border-[#108c2a] focus:bg-white"
                                            >
                                                <option value="">All Variants</option>
                                                <template x-for="(variant, vIndex) in generatedVariants" :key="variant.id">
                                                    <option
                                                        :value="vIndex"
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
                        <i data-lucide="tag" class="h-5 w-5 shrink-0 text-blue-600"></i>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">
                                5. Product Information
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-500">Add Product Guidance</p>
                        </div>
                    </div>

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
                                value="{{ old('product_guide.0.description') }}"
                                placeholder=" 4–6 hours of indirect sunlight"
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
                                value="{{ old('product_guide.1.description') }}"
                                placeholder=" 1–2 times a week"
                                class="w-full rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm transition-all outline-none focus:border-blue-400"
                            />
                        </div>
                    </div>

                    {{-- Additional Info sections (optional) — indices start at 2 after Sunlight+Watering --}}
                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <div class="mb-4">
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
    <div class="mt-4">
        <button type="button" @click="addGuide()"
            class="flex items-center gap-1 rounded bg-[#108c2a]/10 px-3 py-1.5 text-xs font-bold text-[#108c2a] transition-colors hover:bg-[#108c2a]/20">
            <i data-lucide="plus-circle" class="h-3 w-3"></i> Add Section
        </button>
    </div>
    </div>
    </div>
    @endif

    <div class="flex flex-col justify-end border-t border-gray-200 pt-4 sm:flex-row">
        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#108c2a] px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0c6b1f] sm:w-auto">
            <i data-lucide="save" class="h-4 w-4"></i> Create Product
        </button>
    </div>

    {{-- SINGLE ITEM STOCK MODAL --}}
    <div x-show="isSingleStockModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="closeSingleStockModal()">
        <div class="flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            x-show="isSingleStockModalOpen" x-transition.scale.origin.bottom>
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-800">Adjust Opening Stock</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Assign physical stock to warehouses</p>
                </div>
                <button type="button" @click="closeSingleStockModal()"
                    class="rounded-md border border-gray-200 bg-white p-1 text-gray-400 shadow-sm hover:text-red-500">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto bg-white p-6">
                <div class="space-y-3">
                    <template x-for="(stock, stockIndex) in singleStocks" :key="stock.id">
                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-2">
                            {{-- Same custom dropdown as the POS warehouse switcher — a native
                                 select renders its menu with OS chrome that ignores the app's
                                 styling entirely. --}}
                            {{-- The panel is teleported to <body>: this row sits inside the
                                 modal's overflow-y-auto area, which clips any absolutely
                                 positioned child. Position is measured from the button on
                                 open, and the panel closes on scroll or resize rather than
                                 tracking it. --}}
                            <div x-data="{
                                open: false,
                                top: 0,
                                left: 0,
                                width: 0,
                                warehouses: @js(($warehouses ?? collect())->map->only(['id', 'name'])->values()),
                                toggle() {
                                    if (this.open) { this.open = false; return; }
                                    const r = this.$refs.trigger.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = r.left;
                                    this.width = r.width;
                                    this.open = true;
                                    this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                                },
                            }" class="relative flex-1">
                                <button type="button" x-ref="trigger" @click="toggle()"
                                    :class="open ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/15' :
                                        'border-gray-300 bg-white hover:border-gray-400 hover:bg-gray-50'"
                                    class="flex h-[42px] w-full items-center gap-2 rounded-xl border pr-2.5 pl-3 shadow-sm transition-all">
                                    <i data-lucide="warehouse" class="h-4 w-4 shrink-0 text-blue-500"></i>
                                    <span class="flex flex-1 flex-col items-start leading-none">
                                        <span
                                            class="text-[9px] font-bold tracking-widest text-gray-400 uppercase">Warehouse</span>
                                        <span class="mt-0.5 truncate text-[12px] font-bold text-gray-800"
                                            x-text="warehouses.find(w => w.id == stock.warehouse_id)?.name || 'Select'"></span>
                                    </span>
                                    <i data-lucide="chevron-down"
                                        class="h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform"
                                        :class="open && 'rotate-180'"></i>
                                </button>

                                <template x-teleport="body">
                                    <div x-cloak x-show="open" @click.away="open = false" @scroll.window="open = false"
                                        @resize.window="open = false" @keydown.escape.window="open = false"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        :style="`top:${top}px; left:${left}px; width:${Math.max(width, 220)}px`"
                                        class="fixed z-[200] max-h-56 overflow-y-auto rounded-xl border border-gray-100 bg-white p-1.5 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]">
                                        <p
                                            class="px-2.5 pt-1.5 pb-2 text-[9px] font-black tracking-widest text-gray-400 uppercase">
                                            Select Warehouse
                                        </p>
                                        <template x-for="wh in warehouses" :key="wh.id">
                                            <button type="button" @click="stock.warehouse_id = wh.id; open = false"
                                                :class="stock.warehouse_id == wh.id ? 'bg-blue-50 text-blue-700' :
                                                    'text-gray-700 hover:bg-gray-50'"
                                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition-colors">
                                                <i data-lucide="warehouse" class="h-4 w-4 shrink-0"
                                                    :class="stock.warehouse_id == wh.id ? 'text-blue-500' : 'text-gray-400'"></i>
                                                <span class="flex-1 truncate text-[13px]"
                                                    :class="stock.warehouse_id == wh.id ? 'font-bold' : 'font-medium'"
                                                    x-text="wh.name"></span>
                                                <i data-lucide="check" class="h-4 w-4 shrink-0 text-blue-500"
                                                    x-show="stock.warehouse_id == wh.id"></i>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <input type="number" x-model="stock.qty" placeholder="Qty" min="1"
                                class="w-24 rounded-md border border-gray-300 px-3 py-2 text-center text-sm font-bold text-gray-800 outline-none focus:border-[#108c2a]" />

                            <button type="button" @click="removeSingleStock(stockIndex)" title="Remove"
                                class="rounded-md p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </template>

                    <div class="pt-2">
                        <button type="button" @click="addSingleStock()"
                            class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-[#108c2a]/20 px-3 py-2 text-xs font-bold text-[#108c2a] transition-colors hover:bg-[#108c2a]/10">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add Warehouse Allocation
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 bg-gray-50 p-5">
                <button type="button" @click="closeSingleStockModal()"
                    class="rounded-xl bg-[#108c2a] px-8 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0c6b1f]">
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- 🌟 VARIANT STOCK MODAL --}}
    <div x-show="isStockModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="closeStockModal()">
        <div class="flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            x-show="isStockModalOpen" x-transition.scale.origin.bottom>
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-800">Adjust Opening Stock</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Assign physical stock to warehouses</p>
                </div>
                <button type="button" @click="closeStockModal()"
                    class="rounded-md border border-gray-200 bg-white p-1 text-gray-400 shadow-sm hover:text-red-500">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="max-h-[60vh] overflow-y-auto bg-white p-6">
                <template x-if="activeVariantIndex !== null">
                    <div class="space-y-3">
                        <template x-for="(stock, stockIndex) in getActiveVariant().stocks" :key="stock.id">
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-2">
                                <select x-model="stock.warehouse_id"
                                    class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#108c2a]">
                                    <option value="">Select Warehouse...</option>
                                    @foreach ($warehouses ?? [] as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>

                                <input type="number" x-model="stock.qty" placeholder="Qty" min="1"
                                    class="w-24 rounded-md border border-gray-300 px-3 py-2 text-center text-sm font-bold text-gray-800 outline-none focus:border-[#108c2a]" />

                                <button type="button" @click="removeActiveVariantStock(stockIndex)" title="Remove"
                                    class="rounded-md p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </template>

                        <div class="pt-2">
                            <button type="button" @click="addActiveVariantStock()"
                                class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-[#108c2a]/20 px-3 py-2 text-xs font-bold text-[#108c2a] transition-colors hover:bg-[#108c2a]/10">
                                <i data-lucide="plus" class="h-4 w-4"></i> Add Warehouse Allocation
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end border-t border-gray-100 bg-gray-50 p-5">
                <button type="button" @click="closeStockModal()"
                    class="rounded-xl bg-[#108c2a] px-8 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0c6b1f]">
                    Done
                </button>
            </div>
        </div>
    </div>
    </form>
    </div>
@endsection

@push('scripts')
    <script>
        function productForm() {
            return {
                catalogMode: "{{ old('product_type', 'sellable') }}",
                // Read old input if validation failed, default to 'single'
                productType: "{{ old('type', 'single') }}",
                singleMrp: "{{ old('single_mrp', '') }}",
                singleSku: "{{ old('single_sku') }}",
                singleBarcode: "{{ old('single_barcode') }}",
                mediaList: [],
                productGuides: [],
                primaryMediaIndex: 0,
                draggedIndex: null,

                addGuide() {
                    if (this.productGuides.length >= 50) {
                        BizAlert.toast("Maximum 50 guidance sections allowed.", "error");
                        return;
                    }
                    // Push a new empty key-value object
                    this.productGuides.push({
                        id: Date.now(),
                        title: "",
                        description: "",
                    });

                    // Re-render icons for the new row
                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },

                removeGuide(index) {
                    this.productGuides.splice(index, 1);
                },

                addMedia(type) {
                    if (this.mediaList.length >= 10) {
                        BizAlert.toast("Maximum 10 media items allowed.", "error");
                        return;
                    }

                    const newItem = {
                        id: Date.now(),
                        type: type,
                        sku_index: "",
                    };
                    this.mediaList.push(newItem);

                    // Auto-select as primary if it's the first image added
                    if (type === "image" && !this.primaryMediaId) {
                        this.primaryMediaId = newItem.id;
                    }

                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },

                removeMedia(index) {
                    const removedId = this.mediaList[index].id;
                    this.mediaList.splice(index, 1);

                    // If they deleted the primary image, auto-assign the next available image
                    if (this.primaryMediaId === removedId) {
                        const nextImage = this.mediaList.find((m) => m.type === "image");
                        this.primaryMediaId = nextImage ? nextImage.id : null;
                    }
                },
                dragStart(index, event) {
                    this.draggedIndex = index;
                    event.dataTransfer.effectAllowed = "move";
                    // Optional: You can set a custom drag image here if you want
                },
                dragEnd() {
                    this.draggedIndex = null;
                },
                dragOver(event) {
                    event.preventDefault(); // Necessary to allow dropping
                    event.dataTransfer.dropEffect = "move";
                },
                drop(index) {
                    if (this.draggedIndex === null || this.draggedIndex === index) return;

                    // Extract the dragged item from the array
                    const draggedItem = this.mediaList.splice(this.draggedIndex, 1)[0];

                    // Insert it at the new dropped position
                    this.mediaList.splice(index, 0, draggedItem);

                    this.draggedIndex = null;
                },

                // Initialize variations array (start with 1 empty row)
                // 🌟 Pre-initialize empty arrays for every attribute ID directly in state
                selectedValues: {
                    @foreach ($attributes as $attr)
                        {{ $attr->id }}: [],
                    @endforeach
                },
                generatedVariants: [],

                // 🌟 Added Bulk Edit State
                bulk: {
                    price: "",
                    cost: "",
                    tax_type: "",
                    tax_percent: "",
                    alert: "",
                },
                // 🌟 Calculates the math matrix before executing
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

                // 🌟 The Safety Limiter
                confirmVariantGeneration() {
                    const total = this.calculateCombinations();

                    if (total === 0) {
                        BizAlert.toast("Please select at least one attribute.", "error");
                        return;
                    }

                    if (total > 100) {
                        BizAlert.toast(
                            `Blocked: Attempting to generate ${total} variants. Maximum allowed is 100 to protect system performance.`,
                            "error",
                        );
                        return;
                    }

                    if (total > 50) {
                        if (
                            !confirm(
                                `Warning: You are about to generate ${total} variants. This creates a massive matrix and might slow down the page. Continue?`,
                            )
                        ) {
                            return;
                        }
                    }

                    this.generateVariants();
                },
                generateSKU(length = 8, prefix = "SKU-") {
                    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                    let result = prefix;

                    // Use crypto for better randomness
                    const randomValues = new Uint32Array(length);
                    crypto.getRandomValues(randomValues);

                    for (let i = 0; i < length; i++) {
                        result += chars[randomValues[i] % chars.length];
                    }

                    return result;
                },

                // 🌟 Applies the bulk input fields
                applyBulkEdit() {
                    if (this.generatedVariants.length === 0) return;

                    let applied = false;
                    this.generatedVariants.forEach((variant) => {
                        if (this.bulk.price !== "") {
                            variant.price = this.bulk.price;
                            applied = true;
                        }
                        if (this.bulk.cost !== "") {
                            variant.cost = this.bulk.cost;
                            applied = true;
                        }
                        if (this.bulk.tax_type !== "") {
                            variant.tax_type = this.bulk.tax_type;
                            applied = true;
                        }
                        if (this.bulk.tax_percent !== "") {
                            variant.order_tax = this.bulk.tax_percent;
                            applied = true;
                        }
                        if (this.bulk.alert !== "") {
                            variant.alert = this.bulk.alert;
                            applied = true;
                        }
                    });

                    if (applied) {
                        BizAlert.toast(`Bulk updated ${this.generatedVariants.length} variants successfully!`, "success");
                    }

                    // Reset fields to placeholder state
                    this.bulk = {
                        price: "",
                        cost: "",
                        tax_type: "",
                        tax_percent: "",
                        alert: "",
                    };
                },
                generateVariants() {
                    let arraysToCombine = [];
                    let attrKeys = [];

                    // 1. Gather all attributes that actually have checked boxes
                    for (const [attrId, values] of Object.entries(this.selectedValues)) {
                        if (values && values.length > 0) {
                            arraysToCombine.push(values);
                            attrKeys.push(attrId);
                        }
                    }

                    if (arraysToCombine.length === 0) {
                        BizAlert.toast("Please select at least one attribute to generate variants.", "error");
                        return;
                    }

                    // 2. Perform Cartesian Product (Create all combinations)
                    const combine = (arrs) => arrs.reduce((a, b) => a.flatMap((d) => b.map((e) => [...d, e])), [
                        []
                    ]);
                    const combinations = combine(arraysToCombine);

                    // 3. Map combinations into our unified variant objects
                    let newVariants = combinations.map((combo) => {
                        let labelParts = [];
                        let attrsPayload = {};

                        combo.forEach((valString, index) => {
                            // Split the string payload "ValueID::ValueName"
                            let [valId, valName] = valString.split("::");
                            labelParts.push(valName);
                            attrsPayload[attrKeys[index]] = valId;
                        });

                        return {
                            id: Date.now() + Math.random().toString(36).substr(2, 9),
                            label: labelParts.join(" / "), // e.g. "Red / Small"
                            attrs: attrsPayload,
                            sku: "", // 🌟 Leave empty so backend handles it intelligently
                            barcode: "",
                            mrp: this.singleMrp || "", // Inherit from generic if filled early
                            price: "",
                            cost: "",
                            tax_type: "exclusive",
                            order_tax: "",
                            hsn_code: "",
                            alert: 0,
                            stocks: [], // 🌟 CRITICAL: Init empty stock array
                        };
                    });

                    this.generatedVariants = newVariants;
                    BizAlert.toast(`${newVariants.length} variants successfully generated!`, "success");

                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },
                clearGeneratedVariants() {
                    this.generatedVariants = [];
                    // Uncheck everything
                    for (let key in this.selectedValues) {
                        this.selectedValues[key] = [];
                    }
                },
                removeGeneratedVariant(index) {
                    this.generatedVariants.splice(index, 1);
                },
                // 🌟 ADD THIS NEW FUNCTION
                generateBarcode() {
                    // Generates a random 12-digit numeric string (similar to UPC format)
                    return Math.floor(100000000000 + Math.random() * 900000000000).toString();
                },
                addVariation() {
                    this.variations.push({
                        id: Date.now(),
                        sku: "",
                        barcode: "",
                        price: "",
                        cost: "",
                        mrp: "",
                        tax_type: "exclusive",
                        order_tax: "",
                        alert: 0,
                        stocks: [],
                    });

                    // Re-initialize icons for the new row
                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },

                removeVariation(index) {
                    if (this.variations.length > 1) {
                        this.variations.splice(index, 1);
                    } else {
                        BizAlert.toast("You must have at least one variation.", "error");
                    }
                },

                // 🌟 NEW: Helper methods to add/remove warehouse rows for Variable Products
                // 🌟 NEW: Advanced Modal Stock Management
                isStockModalOpen: false,
                activeVariantIndex: null,

                // ── Single item opening stock ──
                isSingleStockModalOpen: false,
                singleStocks: [],

                openSingleStockModal() {
                    if (this.singleStocks.length === 0) {
                        this.addSingleStock();
                    }
                    this.isSingleStockModalOpen = true;
                },

                closeSingleStockModal() {
                    // Drop half-filled rows so single_stock.*.required_with never trips
                    this.singleStocks = this.singleStocks.filter(
                        (s) => s.warehouse_id !== "" && s.qty !== "" && Number(s.qty) > 0
                    );
                    this.isSingleStockModalOpen = false;
                },

                addSingleStock() {
                    this.singleStocks.push({
                        id: Date.now() + Math.random(),
                        warehouse_id: "",
                        qty: "",
                    });
                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },

                removeSingleStock(stockIndex) {
                    this.singleStocks.splice(stockIndex, 1);
                },

                calculateSingleStock() {
                    return this.singleStocks.reduce((sum, s) => sum + (parseFloat(s.qty) || 0), 0);
                },

                getActiveVariant() {
                    let targetArray = (this.generatedVariants && this.generatedVariants.length > 0) ?
                        this.generatedVariants :
                        (this.variations || []);
                    return targetArray[this.activeVariantIndex] || {};
                },

                openStockModal(index) {
                    this.activeVariantIndex = index;
                    let variant = this.getActiveVariant();

                    if (!variant.stocks) {
                        variant.stocks = [];
                    }

                    // Auto-add first row if empty to save a click
                    if (variant.stocks.length === 0) {
                        this.addActiveVariantStock();
                    }

                    this.isStockModalOpen = true;
                },

                closeStockModal() {
                    // Filter out any blank rows before closing
                    let variant = this.getActiveVariant();
                    variant.stocks = variant.stocks.filter((s) => s.warehouse_id !== "" && s.qty !== "");

                    this.isStockModalOpen = false;
                    this.activeVariantIndex = null;
                },

                addActiveVariantStock() {
                    this.getActiveVariant().stocks.push({
                        id: Date.now() + Math.random(),
                        warehouse_id: "",
                        qty: "",
                    });
                    setTimeout(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    }, 50);
                },

                removeActiveVariantStock(stockIndex) {
                    this.getActiveVariant().stocks.splice(stockIndex, 1);
                },

                calculateTotalStock(variant) {
                    if (!variant || !variant.stocks) return 0;
                    return variant.stocks.reduce((sum, stock) => sum + (parseFloat(stock.qty) || 0), 0);
                },
                removeVariationStock(varIndex, stockIndex) {
                    this.variations[varIndex].stocks.splice(stockIndex, 1);
                },
            };
        }
    </script>
@endpush
