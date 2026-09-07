@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Purchase</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Prevent body scroll when item edit modal is open */
        body.item-modal-open {
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    @php
        // 'percent', not 'percentage' — invoices use the longer word, but
        // calculate() here compares against 'percent' and a mismatch would
        // silently stop applying percentage discounts.
        $discountTypeOptions = ['fixed' => 'Flat (₹)', 'percent' => 'Percent (%)'];

        // The item modal words these differently from the footer's global
// discount, so it gets its own arrays rather than reusing that one.
$taxTypeOptions = [
    'exclusive' => 'Exclusive (Price + Tax)',
    'inclusive' => 'Inclusive (Price includes Tax)',
];
$itemDiscountTypeOptions = ['percent' => 'Percentage (%)', 'fixed' => 'Fixed Amount (₹)'];

        // Prepare options arrays with string keys to avoid the automatic index-0 reset bug
        $warehouseOptions = [];
        foreach ($warehouses ?? [] as $warehouse) {
            $warehouseOptions[(string) $warehouse->id] = $warehouse->name;
        }
    @endphp

    <div class="pb-10" x-data="purchaseForm(@js($units ?? []), @js($batchEnabled ?? false), @js($selectedSku ?? null), @js($prefillQty ?? 1))">
        <div class="mb-6">
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Purchase Order</h1>
            {{-- <p class="text-[13px] text-gray-500 font-medium">Generate a new PO — select supplier, add products, configure
                taxes, and manage stock</p> --}}
        </div>
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.purchases.store') }}" method="POST" @submit="handleFormSubmit($event)">
            @csrf

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Purchase Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div x-data="supplierSearch(@js($suppliers ?? []), '{{ old('supplier_id') }}')" @click.away="isOpen = false" class="relative">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Supplier <span
                                class="text-red-500">*</span></label>

                        {{-- Visible search input --}}
                        <div class="relative">
                            <input type="text" x-model="searchTerm" @focus="isOpen = true"
                                @input="isOpen = true; selectedId = ''" placeholder="Search supplier by name or phone..."
                                autocomplete="off"
                                class="w-full border border-gray-300 rounded px-3 py-2.5 pr-9 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none bg-white font-bold text-gray-700">
                            <i data-lucide="chevron-down"
                                class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>

                        {{-- Hidden input actually submitted to Laravel --}}
                        <input type="hidden" name="supplier_id" :value="selectedId">

                        {{-- Suggestion dropdown --}}
                        <ul x-show="isOpen" x-cloak x-transition
                            class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto overscroll-contain top-full left-0">

                            <li x-show="filtered.length === 0"
                                class="px-4 py-4 text-sm text-gray-500 text-center font-medium">
                                No matching suppliers found.
                            </li>

                            <template x-for="supplier in filtered" :key="supplier.id">
                                <li @click="select(supplier)"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="font-bold text-[13px] text-gray-800" x-text="supplier.name"></div>
                                    <div class="text-[11px] text-gray-500 mt-0.5 flex flex-wrap items-center gap-2">
                                        <span x-show="supplier.phone" x-text="'📞 ' + supplier.phone"></span>
                                        <span x-show="supplier.gstin"
                                            class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 text-[9px] font-bold text-gray-600"
                                            x-text="'GST: ' + supplier.gstin"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>


                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Supplier Invoice
                            No.</label>
                        <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none uppercase">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Supplier Invoice
                            Date</label>
                        <input type="date" name="supplier_invoice_date" value="{{ old('supplier_invoice_date') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                    </div>


                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Warehouse <span
                                class="text-red-500">*</span></label>
                        <div @change="selectedWarehouse = $event.target.value; fetchGlobalSkus();">
                            <x-custom-select name="warehouse_id" placeholder="-- Select Destination --" :options="$warehouseOptions"
                                :selected="old(
                                    'warehouse_id',
                                    (string) ($warehouses->firstWhere('is_default', true)?->id ??
                                        ($warehouses->first()?->id ?? '')),
                                )" required />
                        </div>
                    </div>

                    <input type="hidden" name="store_id" value="{{ active_store()?->id }}">


                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Purchase Date
                            <span class="text-red-500">*</span></label>
                        <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}"
                            required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Status <span
                                class="text-red-500">*</span></label>
                        <x-custom-select name="status" :options="[
                            'draft' => 'Draft',
                            'ordered' => 'Ordered / Sent',
                            'received' => 'Received (Updates Stock)',
                        ]" :selected="old('status', 'draft')" required />
                    </div>

                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div
                    class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Order Items <span
                            class="text-red-500">*</span></h2>

                    <div class="relative w-full sm:max-w-md md:max-w-lg lg:max-w-2xl" x-data="{ showResults: false }">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" x-model="globalSearch" @input.debounce.300ms="fetchGlobalSkus()"
                            @focus="showResults = true" @click.away="showResults = false"
                            placeholder="Search Product by Code or Name..."
                            class="w-full border border-gray-300 rounded shadow-sm pl-9 pr-4 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none bg-white">

                        <ul x-show="showResults && globalSearch.length > 1" x-cloak
                            class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto top-full left-0">

                            <li x-show="isSearching"
                                class="px-4 py-3 text-xs text-gray-500 text-center font-medium flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-brand-500" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Searching products...
                            </li>

                            <li x-show="!isSearching && globalSearchResults.length === 0" class="px-4 py-4 text-center">
                                <span class="block text-sm font-bold text-gray-700">No products found</span>
                                <span class="block text-[11px] text-gray-400 mt-0.5">Try searching by a different name or
                                    SKU code.</span>
                            </li>

                            <template x-for="result in globalSearchResults" :key="result.product_sku_id">
                                <li @click="addSkuToTable(result); showResults = false"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="flex justify-between items-start gap-2">
                                        {{-- 🌟 NEW: Primary Display Name (Product - Variant) --}}
                                        <div class="text-[13px] font-bold text-gray-800 leading-tight"
                                            x-text="result.display_name"></div>
                                        <span x-text="'₹' + result.cost"
                                            class="text-brand-600 font-bold text-[12px] flex-shrink-0"></span>
                                    </div>
                                    {{-- 🌟 NEW: Detailed Secondary Line --}}
                                    <div
                                        class="text-[11px] text-gray-500 flex items-center flex-wrap gap-1.5 mt-1.5 font-medium">
                                        <span>Code:</span>
                                        <span
                                            class="bg-gray-100 border border-gray-200 text-gray-600 px-1 py-0.5 rounded font-mono text-[10px] font-bold tracking-wide"
                                            x-text="result.sku_code"></span>
                                        <span class="text-gray-300">&middot;</span>
                                        <span x-text="'Stock: ' + result.stock"></span>
                                        <span class="text-gray-300">&middot;</span>
                                        <span x-text="'Unit: ' + result.unit_name"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="hidden md:table-header-group bg-gray-50 border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-4 min-w-[250px]">PRODUCT</th>
                                <th class="px-4 py-4 w-[160px]">NET UNIT COST</th>
                                <th class="px-4 py-4 w-[140px]">QTY</th>
                                <th class="px-4 py-4 w-[220px]" x-show="batchEnabled" x-cloak>BATCH DETAILS</th>
                                <th class="px-5 py-4 w-[160px] text-right">SUBTOTAL</th>
                                <th class="px-4 py-4 w-[60px] text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.key">
                                <tr
                                    class="flex flex-col md:table-row border-b md:border-b-0 border-gray-200 p-4 md:p-0 hover:bg-gray-50/50 transition-colors relative">
                                    <td class="block md:table-cell px-0 py-2 md:px-5 md:py-3 w-full md:w-auto">
                                        <div class="flex items-start gap-2">
                                            {{-- 🌟 FIX: Primary Title and Icon grouped together --}}
                                            <div class="text-[13px] font-bold text-gray-800" x-text="item.display_name">
                                            </div>
                                            <button type="button" @click="openItemModal(index)"
                                                class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1 rounded transition-colors flex-shrink-0 mt-0.5"
                                                title="Edit Tax, Discount & Unit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <span class="text-gray-500 text-[11px] font-medium">Code:</span>
                                            <span
                                                class="bg-[#f1f5f9] text-[#475569] text-[10px] px-1.5 py-0.5 rounded font-mono font-bold tracking-wide border border-slate-200"
                                                x-text="item.sku_code"></span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-center gap-2 mt-2 text-[10px] font-medium text-gray-600">
                                            <span class="bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                                Unit: <span
                                                    x-text="(units.find(u => u.id == item.unit_id) || {}).short_name || 'N/A'"
                                                    class="font-bold text-gray-700"></span>
                                            </span>

                                            <span class="bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                                Tax: <span x-text="item.tax_percent + '%'"
                                                    class="font-bold text-gray-700"></span>
                                                <span x-text="'(' + item.tax_type.substring(0,3).toUpperCase() + ')'"
                                                    class="text-[9px] uppercase tracking-wider text-gray-500"></span>
                                            </span>

                                            <span x-show="item.discount_value > 0"
                                                class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded border border-amber-200">
                                                Disc: <span
                                                    x-text="item.discount_type === 'percent' ? item.discount_value + '%' : '₹' + item.discount_value"
                                                    class="font-bold"></span>
                                            </span>
                                        </div>
                                        <input type="hidden" :name="'items[' + index + '][product_id]'"
                                            :value="item.product_id">
                                        <input type="hidden" :name="'items[' + index + '][product_sku_id]'"
                                            :value="item.product_sku_id">
                                        <input type="hidden" :name="'items[' + index + '][unit_id]'"
                                            :value="item.unit_id">
                                        <input type="hidden" :name="'items[' + index + '][discount_type]'"
                                            :value="item.discount_type">
                                        <input type="hidden" :name="'items[' + index + '][discount_value]'"
                                            :value="item.discount_value">
                                        <input type="hidden" :name="'items[' + index + '][tax_percent]'"
                                            :value="item.tax_percent">
                                        <input type="hidden" :name="'items[' + index + '][tax_type]'"
                                            :value="item.tax_type">
                                        <input type="hidden" :name="'items[' + index + '][product_name]'"
                                            :value="item.product_name">
                                        {{-- 🌟 NEW: Keep Display Name on validation errors --}}
                                        <input type="hidden" :name="'items[' + index + '][display_name]'"
                                            :value="item.display_name">
                                        <input type="hidden" :name="'items[' + index + '][sku_code]'"
                                            :value="item.sku_code">
                                    </td>

                                    <td class="block md:table-cell px-0 py-2 md:px-4 md:py-3 w-full md:w-auto">
                                        <div
                                            class="md:hidden text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                            Net Unit Cost</div>

                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">₹</span>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                :name="'items[' + index + '][unit_cost]'" x-model="item.unit_cost"
                                                @input="item.unit_cost = window.sanitizeInteger($event); calculate();"
                                                @blur="item.unit_cost = window.commitInteger(item.unit_cost, 0); calculate();"
                                                class="w-full border border-gray-300 rounded px-2 pl-7 py-2 text-sm focus:border-brand-500 outline-none transition-all font-semibold text-gray-700"
                                                placeholder="0" required>
                                        </div>
                                    </td>

                                    <td class="block md:table-cell px-0 py-2 md:px-4 md:py-3 w-full md:w-auto">
                                        <div class="flex items-center justify-center">
                                            {{-- Minus Button --}}
                                            <button type="button"
                                                @click="item.quantity = Math.max(1, parseFloat(item.quantity || 0) - 1); calculate()"
                                                class="w-8 h-9 border border-gray-300 rounded-l flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 transition-colors focus:outline-none font-bold">
                                                -
                                            </button>

                                            {{-- QTY Input --}}
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                :name="'items[' + index + '][quantity]'" x-model="item.quantity"
                                                @input="item.quantity = window.sanitizeInteger($event); calculate();"
                                                @blur="item.quantity = window.commitInteger(item.quantity, 1); calculate();"
                                                class="w-16 h-9 border-y border-x-0 border-gray-300 text-center text-sm font-bold focus:ring-0 focus:border-brand-500 outline-none p-0 text-gray-700"
                                                placeholder="0" required>

                                            {{-- Plus Button --}}
                                            <button type="button"
                                                @click="item.quantity = parseFloat(item.quantity || 0) + 1; calculate()"
                                                class="w-8 h-9 border border-gray-300 rounded-r flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 transition-colors focus:outline-none font-bold">
                                                +
                                            </button>
                                        </div>
                                    </td>

                                    {{-- 🌟 FIX 2 & 3: Use x-show on td, and remove the duplicate hidden input! --}}
                                    <td class="block md:table-cell px-0 py-2 md:px-4 md:py-3 w-full md:w-auto align-top"
                                        x-show="batchEnabled" x-cloak>
                                        <div class="flex flex-col gap-2.5">

                                            {{-- Batch Number --}}
                                            <div>
                                                <label
                                                    class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">Batch
                                                    Number</label>
                                                <input type="text" :name="'items[' + index + '][batch_number]'"
                                                    x-model="item.batch_number" placeholder="e.g. BATCH-001"
                                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-mono text-gray-700 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                                            </div>

                                            {{-- Dates Grid --}}
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label
                                                        class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mfg
                                                        Date</label>
                                                    <input type="date"
                                                        :name="'items[' + index + '][manufacturing_date]'"
                                                        x-model="item.manufacturing_date"
                                                        class="w-full border border-gray-300 rounded px-1.5 py-1.5 text-[11px] text-gray-600 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-[9px] font-bold text-gray-500 uppercase tracking-wider mb-1">Exp
                                                        Date</label>
                                                    <input type="date" :name="'items[' + index + '][expiry_date]'"
                                                        x-model="item.expiry_date"
                                                        class="w-full border border-gray-300 rounded px-1.5 py-1.5 text-[11px] text-gray-600 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none">
                                                </div>
                                            </div>

                                        </div>
                                    </td>

                                    <td
                                        class="block flex justify-between items-center md:table-cell px-0 py-2 md:px-5 md:py-3 w-full md:w-auto md:text-right md:max-w-[140px]">
                                        <div
                                            class="md:hidden text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                            Subtotal</div>
                                        <span class="font-bold text-gray-800 text-[14px] md:block md:truncate"
                                            :title="formatCurrency(item.line_total)"
                                            x-text="formatCurrency(item.line_total)"></span>
                                    </td>

                                    <td
                                        class="absolute top-2 right-2 md:relative md:top-auto md:right-auto block md:table-cell px-0 py-0 md:px-4 md:py-3 text-center w-auto">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-400 hover:text-red-600 transition-colors p-1.5 rounded hover:bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                                <line x1="10" y1="11" x2="10" y2="17">
                                                </line>
                                                <line x1="14" y1="11" x2="14" y2="17">
                                                </line>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="items.length === 0" x-cloak>
                                <td :colspan="batchEnabled ? 6 : 5" class="px-6 py-12 text-center">
                                    <p class="text-sm font-medium text-gray-400">Search and add a product to begin.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Internal
                            Notes</label>
                        <textarea name="notes" rows="3"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none resize-none">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Terms &
                            Conditions</label>
                        <textarea name="terms_and_conditions" rows="3"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none resize-none">{{ old('terms_and_conditions') }}</textarea>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3
                        class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-4">
                        Financial Summary</h3>

                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Subtotal (Taxable):</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Total Tax (GST):</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.tax)"></span>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <span class="font-semibold">Global Discount:</span>
                            <div class="flex items-center gap-2">
                                {{-- The wrapper catches the change event bubbling up
                                     from the component's hidden native select. That is
                                     the only way this component writes back into the
                                     parent Alpine scope — it owns its own value. --}}
                                <div class="w-36"
                                    @change="
                                        global.discount_type = $event.target.value;

                                        if(global.discount_type === 'percent' && Number(global.discount_value) > 100){
                                            global.discount_value = 100;
                                        }

                                        calculate();
                                    ">
                                    <x-custom-select name="discount_type" :options="$discountTypeOptions" :selected="old('discount_type', 'percent')" />
                                </div>
                                {{-- No unit prefix inside the box: the type dropdown
                                     beside it already reads "Flat (₹)" or
                                     "Percent (%)", and the padding it needed was what
                                     made this field look narrower than the rest. --}}
                                <input type="text" inputmode="numeric" name="discount_value"
                                    x-model="global.discount_value"
                                    @input="global.discount_value = window.sanitizeInteger($event, { max: global.discount_type === 'percent' ? 100 : null }); calculate();"
                                    @blur="global.discount_value = window.commitInteger(global.discount_value, 0); calculate();"
                                    class="w-24 rounded border border-gray-300 px-3 py-1.5 text-right font-bold text-red-500 outline-none focus:border-[#108c2a] sm:w-32"
                                    placeholder="0">
                            </div>
                            <input type="hidden" name="discount_amount" :value="global.discount_amount">
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <span class="font-semibold">Shipping Cost (₹):</span>
                            <input type="text" inputmode="numeric" name="shipping_cost" x-model="global.shipping"
                                @input="global.shipping = window.sanitizeInteger($event); calculate();"
                                @blur="global.shipping = window.commitInteger(global.shipping, 0); calculate();"
                                class="w-24 rounded border border-gray-300 px-3 py-1.5 text-right font-bold text-gray-800 outline-none focus:border-[#108c2a] sm:w-32"
                                placeholder="0">
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <span class="font-semibold">Other Charges (₹):</span>
                            <input type="text" inputmode="numeric" name="other_charges" x-model="global.other"
                                @input="global.other = window.sanitizeInteger($event); calculate();"
                                @blur="global.other = window.commitInteger(global.other, 0); calculate();"
                                class="w-24 rounded border border-gray-300 px-3 py-1.5 text-right font-bold text-gray-800 outline-none focus:border-[#108c2a] sm:w-32"
                                placeholder="0">
                        </div>

                        <div class="flex justify-between items-center pt-2 pb-2 border-b border-gray-200">
                            <span class="font-semibold">Auto Round Off:</span>
                            <span class="font-bold text-gray-800" x-text="global.round_off"></span>
                            <input type="hidden" name="round_off" :value="global.round_off">
                        </div>

                        <div class="flex justify-between items-center pt-2 text-lg">
                            <span class="font-extrabold text-gray-800">Grand Total:</span>
                            <span class="font-extrabold text-[#108c2a]"
                                x-text="formatCurrency(totals.grand_total)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 p-5 rounded-lg flex justify-end gap-4 shadow-sm">
                <a href="{{ route('admin.purchases.index') }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors">
                    CANCEL
                </a>
                <button type="submit"
                    class="bg-gray-800 text-white px-8 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-900 shadow-md transition-all active:scale-95">
                    SUBMIT
                </button>
            </div>
        </form>

        <div x-show="isItemModalOpen" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-end bg-black/50 backdrop-blur-sm transition-opacity">
            <div class="bg-white w-full max-w-md h-full shadow-2xl flex flex-col transform transition-transform"
                x-show="isItemModalOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" @click.away="closeItemModal()">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="text-[15px] font-bold text-gray-800" x-text="activeEditData.product_name"></h3>
                        {{-- <p class="text-[11px] text-gray-500 font-medium mt-0.5" x-text="activeEditData.product_name"></p> --}}
                    </div>
                    <button type="button" @click="closeItemModal()"
                        class="text-gray-400 hover:text-red-500 transition-colors p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 flex-1 overflow-y-auto space-y-5 custom-scrollbar">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Tax Type
                            <span class="text-red-500">*</span></label>
                        {{-- The wrapper catches the change event bubbling up from
                             the component's hidden native select. That is the only
                             way this component writes back into the parent Alpine
                             scope — it owns its own value. --}}
                        {{-- Modal fields need two-way sync: the wrapper writes out
                             on change, and openItemModal() pushes the current line's
                             value back in, since the component keeps its own state. --}}
                        <div @change="activeEditData.tax_type = $event.target.value">
                            <x-custom-select name="modal_tax_type" :options="$taxTypeOptions" selected="exclusive"
                                placeholder="Select tax type" @set-item-tax-type.window="value = $event.detail" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Order Tax
                            (%) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" inputmode="numeric" data-int data-int-min="0" data-int-max="100"
                                x-model="activeEditData.tax_percent"
                                class="w-full border border-gray-300 rounded px-3 pr-8 py-2.5 text-sm focus:border-brand-500 outline-none">
                            <span
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">%</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Discount
                                Type</label>
                            <div @change="activeEditData.discount_type = $event.target.value">
                                <x-custom-select name="modal_discount_type" :options="$itemDiscountTypeOptions" selected="percent"
                                    placeholder="Select type" @set-item-discount-type.window="value = $event.detail" />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Discount
                                Value</label>
                            <div class="relative">
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    :data-int-max="activeEditData.discount_type === 'percent' ? 100 : ''"
                                    x-model="activeEditData.discount_value"
                                    class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs"
                                    x-text="activeEditData.discount_type === 'percent' ? '%' : '₹'"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Purchase
                            Unit <span class="text-red-500">*</span></label>
                        <x-alpine-select model="activeEditData.unit_id" items="units" item-key="id"
                            item-label="item.name + ' (' + item.short_name + ')'" placeholder="Select Unit"
                            empty-text="No units defined yet." />
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-white grid grid-cols-2 gap-3">
                    <button type="button" @click="closeItemModal()"
                        class="w-full bg-gray-100 text-gray-700 font-bold text-sm py-2.5 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="saveItemModal()"
                        class="w-full bg-brand-500 text-white font-bold text-sm py-2.5 rounded-lg hover:bg-brand-600 transition-colors">
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function supplierSearch(suppliers, preselectedId) {
            return {
                suppliers: suppliers || [],
                searchTerm: '',
                selectedId: preselectedId ? String(preselectedId) : '',
                isOpen: false,

                init() {
                    if (this.selectedId) {
                        const match = this.suppliers.find(s => String(s.id) === this.selectedId);
                        if (match) {
                            this.searchTerm = match.name;
                        }
                    }
                },

                get filtered() {
                    const term = (this.searchTerm || '').trim().toLowerCase();
                    if (term === '') {
                        return this.suppliers;
                    }
                    return this.suppliers.filter(s => {
                        const nameMatch = s.name && s.name.toLowerCase().includes(term);
                        const phoneMatch = s.phone && String(s.phone).includes(term);
                        return nameMatch || phoneMatch;
                    });
                },

                select(supplier) {
                    this.selectedId = String(supplier.id);
                    this.searchTerm = supplier.name;
                    this.isOpen = false;
                }
            };
        }

        function purchaseForm(allUnits = [], batchEnabled = false, prefillSku = null, prefillQty = 1) {
            // 1. Safely handle old data so UI doesn't wipe on validation error
            const oldItemsRaw = @json(old('items', []));
            const oldItems = Array.isArray(oldItemsRaw) ? oldItemsRaw : Object.values(oldItemsRaw || {});

            const initialItems = oldItems.map((item, index) => ({
                batch_number: item.batch_number || '',
                manufacturing_date: item.manufacturing_date || '',
                expiry_date: item.expiry_date || '',

                key: index,
                product_id: item.product_id || '',
                product_sku_id: item.product_sku_id || '',
                unit_id: item.unit_id || '',
                product_name: item.product_name || '',
                display_name: item.display_name || item.product_name || '', // 🌟 NEW: Restore Variant String
                sku_code: item.sku_code || '',
                quantity: parseFloat(item.quantity) || 1,
                unit_cost: parseFloat(item.unit_cost) || 0,
                discount_type: item.discount_type || 'percent',
                discount_value: parseFloat(item.discount_value) || 0,
                tax_percent: parseFloat(item.tax_percent) || 0,
                tax_type: item.tax_type || 'exclusive',
                line_total: 0
            }));

            // 🌟 NEW: Inject the Reorder SKU if it exists and no old validation data is present
            if (prefillSku && oldItems.length === 0) {
                let datePart = new Date().toISOString().slice(2, 7).replace(/-/g, '');
                let randomPart = Math.random().toString(36).substring(2, 6).toUpperCase();

                initialItems.push({
                    batch_number: `B-${datePart}-${randomPart}`,
                    manufacturing_date: '',
                    expiry_date: '',
                    key: initialItems.length,
                    product_id: prefillSku.product_id,
                    product_sku_id: prefillSku.id,
                    unit_id: prefillSku.product ? prefillSku.product.purchase_unit_id : '',
                    product_name: prefillSku.product ? prefillSku.product.name : 'Unknown Product',
                    display_name: prefillSku.display_name || (prefillSku.product ? prefillSku.product.name :
                        'Unknown Product'), // 🌟 NEW
                    sku_code: prefillSku.sku,
                    quantity: parseFloat(prefillQty) || 1,
                    unit_cost: parseFloat(prefillSku.cost) || 0,
                    discount_type: 'percent',
                    discount_value: 0,
                    tax_percent: parseFloat(prefillSku.order_tax) || 0,
                    tax_type: prefillSku.tax_type || 'exclusive',
                    line_total: 0
                });
            }

            return {
                batchEnabled: batchEnabled,
                selectedWarehouse: "{{ old('warehouse_id', $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '')) }}",

                units: allUnits,
                items: initialItems,
                itemCounter: initialItems.length,

                // Global Search State
                globalSearch: '',
                globalSearchResults: [],
                isSearching: false,

                // Edit Modal State
                isItemModalOpen: false,
                activeEditIndex: null,
                activeEditData: {},

                global: {
                    discount_type: "{{ old('discount_type', 'percent') }}",
                    discount_value: parseFloat("{{ old('discount_value', 0) }}") || 0,
                    discount_amount: 0,
                    shipping: parseFloat("{{ old('shipping_cost', 0) }}") || 0,
                    other: parseFloat("{{ old('other_charges', 0) }}") || 0,
                    round_off: '0.00'
                },

                totals: {
                    subtotal: 0,
                    tax: 0,
                    grand_total: 0
                },

                init() {
                    this.calculate();
                },

                // --- AJAX SEARCH ---                
                async fetchGlobalSkus() {
                    let term = this.globalSearch.trim();
                    if (term.length < 2) {
                        this.globalSearchResults = [];
                        this.isSearching = false;
                        return;
                    }

                    this.isSearching = true; // 🌟 Set to true before fetch

                    try {
                        // Warehouse is optional here — a purchase does not consume
                        // stock, so it is sent only to display current levels.
                        let url = `{{ route('admin.api.purchases.search-skus') }}?term=${encodeURIComponent(term)}`;
                        if (this.selectedWarehouse) {
                            url += `&warehouse_id=${encodeURIComponent(this.selectedWarehouse)}`;
                        }

                        let response = await fetch(url);
                        if (!response.ok) {
                            throw new Error("Server Error: Check Laravel Logs");
                        }
                        let data = await response.json();
                        this.globalSearchResults = data;
                    } catch (error) {
                        console.error("Error fetching SKUs:", error);
                        this.globalSearchResults = [];
                    } finally {
                        this.isSearching = false; // 🌟 Set to false when done (even if it fails)
                    }
                },

                addSkuToTable(result) {
                    // 🌟 NEW: Auto-generate a smart, unique batch number (Format: B-YYMM-XXXX)
                    let datePart = new Date().toISOString().slice(2, 7).replace(/-/g, ''); // Gives "2603" for March 2026
                    let randomPart = Math.random().toString(36).substring(2, 6).toUpperCase();
                    let autoBatchNumber = `B-${datePart}-${randomPart}`;

                    this.items.push({
                        batch_number: autoBatchNumber,
                        manufacturing_date: '',
                        expiry_date: '',
                        key: this.itemCounter++,
                        product_id: result.product_id,
                        product_sku_id: result.product_sku_id,
                        unit_id: result.unit_id,
                        product_name: result.product_name,
                        display_name: result.display_name, // 🌟 NEW: Map to state
                        sku_code: result.sku_code,
                        quantity: 1,
                        unit_cost: parseFloat(result.cost) || 0,
                        discount_type: 'percent',
                        discount_value: 0,
                        tax_percent: parseFloat(result.tax_percent) || 0,
                        tax_type: result.tax_type || 'exclusive',
                        line_total: 0
                    });

                    this.globalSearch = '';
                    this.globalSearchResults = [];
                    this.calculate();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculate();
                },

                handleFormSubmit(event) {
                    // 1. Validate Supplier Selection
                    const supplierInput = document.querySelector('input[name="supplier_id"]');
                    if (!supplierInput || !supplierInput.value.trim()) {
                        event.preventDefault();
                        BizAlert.toast('Please select a supplier.', 'error');
                        const searchInput = document.querySelector('input[placeholder*="Search supplier"]');
                        if (searchInput) searchInput.focus();
                        return false;
                    }

                    // 2. Validate Destination Warehouse
                    if (!this.selectedWarehouse) {
                        event.preventDefault();
                        BizAlert.toast('Please select a warehouse.', 'error');
                        if (typeof window.focusCustomSelect === 'function') {
                            window.focusCustomSelect('warehouse_id');
                        }
                        return false;
                    }

                    // 3. Validate at least one item added
                    if (!this.items || this.items.length === 0) {
                        event.preventDefault();
                        BizAlert.toast('You must add at least one product to create a purchase order.', 'error');
                        const productSearchInput = document.querySelector('input[placeholder*="Search Product"]');
                        if (productSearchInput) productSearchInput.focus();
                        return false;
                    }

                    // 4. Validate Line Items (Quantity, Cost & Batch details if enabled)
                    for (let i = 0; i < this.items.length; i++) {
                        const item = this.items[i];
                        const qty = parseFloat(item.quantity) || 0;
                        const cost = parseFloat(item.unit_cost) || 0;
                        const itemName = item.display_name || item.product_name || `Item #${i + 1}`;

                        if (qty <= 0) {
                            event.preventDefault();
                            BizAlert.toast(`Invalid quantity for item "${itemName}".`, 'error');
                            return false;
                        }

                        if (cost < 0) {
                            event.preventDefault();
                            BizAlert.toast(`Unit cost cannot be negative for item "${itemName}".`, 'error');
                            return false;
                        }

                        if (this.batchEnabled && !item.batch_number.trim()) {
                            event.preventDefault();
                            BizAlert.toast(`Please enter a batch number for item "${itemName}".`, 'error');
                            return false;
                        }
                    }

                    // All checks passed -> Show loading indicator
                    BizAlert.loading('Saving Purchase...');
                },

                // --- MODAL CONTROLS ---
                openItemModal(index) {
                    this.activeEditIndex = index;
                    this.activeEditData = JSON.parse(JSON.stringify(this.items[index]));
                    this.isItemModalOpen = true;

                    // The custom selects hold their own value and cannot read
                    // activeEditData, so the line's current values are pushed in.
                    // Without this the modal kept showing whatever the previously
                    // opened item had, while activeEditData held the right value.
                    // Strings throughout: the component compares option keys,
                    // which arrive from PHP as strings.
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent("set-item-tax-type", {
                            detail: String(this.activeEditData.tax_type ?? "exclusive"),
                        }));
                        window.dispatchEvent(new CustomEvent("set-item-discount-type", {
                            detail: String(this.activeEditData.discount_type ?? "percent"),
                        }));
                    });
                },

                closeItemModal() {
                    this.isItemModalOpen = false;
                    setTimeout(() => {
                        this.activeEditIndex = null;
                        this.activeEditData = {};
                    }, 200);
                },

                saveItemModal() {
                    if (this.activeEditIndex !== null) {
                        this.items[this.activeEditIndex].tax_type = this.activeEditData.tax_type;
                        this.items[this.activeEditIndex].tax_percent = parseFloat(this.activeEditData.tax_percent) || 0;
                        this.items[this.activeEditIndex].discount_type = this.activeEditData.discount_type;
                        this.items[this.activeEditIndex].discount_value = parseFloat(this.activeEditData.discount_value) ||
                            0;
                        this.items[this.activeEditIndex].unit_id = this.activeEditData.unit_id;
                        this.calculate();
                    }
                    this.closeItemModal();
                },

                // --- MATH ENGINE ---
                calculate() {
                    let subtotalAcc = 0;
                    let taxAcc = 0;

                    this.items.forEach(item => {
                        let qty = parseFloat(item.quantity) || 0;
                        let cost = parseFloat(item.unit_cost) || 0;
                        let discountValInput = parseFloat(item.discount_value) || 0;
                        let taxPct = parseFloat(item.tax_percent) || 0;
                        let isInclusive = item.tax_type === 'inclusive';

                        let baseVal = qty * cost;

                        let discountVal = 0;
                        if (item.discount_type === 'percent') {
                            discountVal = baseVal * (discountValInput / 100);
                        } else {
                            discountVal = discountValInput;
                        }

                        let afterDiscount = Math.max(0, baseVal - discountVal); // Prevent negative totals

                        let taxable = 0;
                        let tax = 0;

                        if (isInclusive) {
                            taxable = afterDiscount / (1 + (taxPct / 100));
                            tax = afterDiscount - taxable;
                        } else {
                            taxable = afterDiscount;
                            tax = taxable * (taxPct / 100);
                        }

                        item.line_total = taxable + tax;

                        subtotalAcc += taxable;
                        taxAcc += tax;
                    });

                    this.totals.subtotal = subtotalAcc;
                    this.totals.tax = taxAcc;

                    let globalDiscountVal = parseFloat(this.global.discount_value) || 0;
                    if (this.global.discount_type === 'percent') {
                        this.global.discount_amount = subtotalAcc * (globalDiscountVal / 100);
                    } else {
                        this.global.discount_amount = globalDiscountVal;
                    }
                    let gDiscount = this.global.discount_amount;
                    let shipping = parseFloat(this.global.shipping) || 0;
                    let other = parseFloat(this.global.other) || 0;

                    let totalBeforeRound = subtotalAcc - gDiscount + taxAcc + shipping + other;

                    this.totals.grand_total = Math.round(totalBeforeRound);
                    this.global.round_off = (this.totals.grand_total - totalBeforeRound).toFixed(2);
                },

                formatCurrency(value) {
                    return parseFloat(value).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        }
    </script>
@endpush
