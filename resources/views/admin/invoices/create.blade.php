@extends ('layouts.admin')

@section('title', 'Create Sales Invoice')

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Create Invoice</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        body.item-modal-open {
            overflow: hidden;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
@endpush

@section('content')
    @php
        // Prepare options arrays for the custom select components
        $warehouseOptions = [];
        foreach ($warehouses ?? [] as $warehouse) {
            $warehouseOptions[$warehouse->id] = $warehouse->name;
        }

        $stateOptions = [];
        foreach ($states ?? [] as $state) {
            $stateOptions[$state->name] = $state->name;
        }

        // Units come from the same collection Alpine receives, so the dropdown
        // and the x-for list can never drift apart.
        $unitOptions = [];
        foreach ($units ?? [] as $unit) {
            $unitOptions[$unit->id] = $unit->name;
        }

        $discountTypeOptions = ['fixed' => 'Flat (₹)', 'percentage' => 'Percent (%)'];

        $shippingGstOptions = [
            '0' => '0%',
            '5' => '5%',
            '12' => '12%',
            '18' => '18%',
            '28' => '28%',
        ];

        $taxTypeOptions = [
            'exclusive' => 'Exclusive (Price + Tax)',
            'inclusive' => 'Inclusive (Price includes Tax)',
        ];

        $itemDiscountTypeOptions = ['percentage' => 'Percentage (%)', 'fixed' => 'Fixed Amount (₹)'];
    @endphp

    {{-- 🌟 Pass the clients into Alpine --}}
    <div class="pb-20" x-data="invoiceForm(@js($units ?? []), @js($companyState), @js($clients ?? []), @js($challanPrefillJs ?? null), @js($orderPrefillJs ?? null), @js($orderGuestPrefill ?? null))">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-[1.5rem] font-bold tracking-widest text-gray-500 uppercase">Create Invoice</h1>
            </div>
        </div>
        {{-- 🚨 ADD THIS: Validation Error Display --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 shadow-sm">
                <ul class="list-inside list-disc space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <script>
                // Auto-close the loading alert if validation fails
                document.addEventListener("DOMContentLoaded", () => {
                    if (typeof Swal !== "undefined") Swal.close();
                });
            </script>
        @endif

        <form id="mainInvoiceForm" action="{{ route('admin.invoices.store') }}" method="POST"
            @submit="handleFormSubmit($event)">
            @csrf

            {{-- HIDDEN SOURCE TRACKING --}}
            <input type="hidden" name="source" value="direct" />

            @if ($challanPrefill)
                {{-- Challan Conversion: Pass the challan ID so InvoiceService updates qty_invoiced --}}
                <input type="hidden" name="challan_id" value="{{ $challanPrefill->id }}" />

                {{-- Conversion Banner --}}
                {{-- UI Fix: Stack elements on mobile, side-by-side on sm+ --}}
                <div
                    class="mb-6 flex flex-col items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center">
                    <div class="flex-shrink-0 rounded-lg bg-blue-600 p-2 text-white">
                        <i data-lucide="file-check" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-800">Converting from Delivery Challan</h4>
                        <p class="text-xs font-medium text-blue-600">Challan <span
                                class="font-black">{{ $challanPrefill->challan_number }}</span> —
                            {{ $challanPrefill->items->sum('qty_pending') }} pending unit(s) pre-loaded below. Quantities
                            are locked to the pending amount.</p>
                    </div>
                    <a href="{{ route('admin.challans.show', $challanPrefill->id) }}"
                        class="ml-auto flex-shrink-0 text-xs font-bold text-blue-600 hover:underline">
                        View Challan →
                    </a>
                </div>
            @endif

            @if ($orderPrefill)
                {{-- Order Conversion: Pass order_id so store() links invoice back to order --}}
                <input type="hidden" name="order_id" value="{{ $orderPrefill->id }}" />

                {{-- Order Conversion Banner --}}
                <div
                    class="mb-6 flex flex-col items-start gap-3 rounded-lg border border-violet-200 bg-violet-50 p-4 sm:flex-row sm:items-center">
                    <div class="flex-shrink-0 rounded-lg bg-violet-600 p-2 text-white">
                        <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-violet-800">Creating Invoice from Order</h4>
                        <p class="text-xs font-medium text-violet-600">
                            Order
                            <span class="font-black">{{ $orderPrefill->order_number }}</span>
                            — {{ $orderPrefill->items->count() }} item(s) pre-loaded. Customer:
                            <span class="font-black">{{ $orderPrefill->customer_name }}</span>
                            @if ($orderPrefill->customer_phone)
                                · {{ $orderPrefill->customer_phone }}
                            @endif
                            @if ($orderPrefill->order_type === 'inquiry')
                                ·
                                <span class="font-bold text-amber-600">Catalog order — please set prices below.</span>
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.orders.show', $orderPrefill->id) }}"
                        class="ml-auto flex-shrink-0 text-xs font-bold text-violet-600 hover:underline">
                        View Order →
                    </a>
                </div>
            @endif

            {{-- 1. TRANSACTION HEADER --}}
            <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
                    {{-- Customer Selection --}}
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Customer <span
                                class="text-red-500">*</span></label>
                        <div class="flex items-start gap-2">
                            <div class="relative flex-1">
                                {{-- Select & Button Row --}}
                                {{-- Searchable Input & Button Row --}}
                                <div class="relative flex w-full gap-1" @click.away="isClientDropdownOpen = false">
                                    {{-- Visible Search Input --}}
                                    <div class="relative flex-1">
                                        <input type="text" x-model="clientSearchTerm"
                                            @focus="isClientDropdownOpen = true"
                                            @input="
                                                isClientDropdownOpen = true;
                                                formData.customer_id = '';
                                            "
                                            placeholder="Search customer by name or phone..."
                                            class="focus:border-brand-500 w-full rounded-l border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 outline-none" />
                                        <i data-lucide="chevron-down"
                                            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                    </div>

                                    {{-- Hidden Input for Laravel Form Submission --}}
                                    <input type="hidden" name="customer_id" x-model="formData.customer_id"
                                        id="customerSelect" />

                                    {{-- Quick Add Button --}}
                                    <button type="button" @click="isClientModalOpen = true"
                                        class="flex shrink-0 items-center justify-center rounded-r border border-blue-200 bg-blue-50 px-3 text-blue-600 transition-colors hover:bg-blue-100"
                                        title="Quick Add Client">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                    </button>

                                    {{-- Floating Dropdown List --}}
                                    <ul x-show="isClientDropdownOpen" x-cloak x-transition
                                        class="custom-scrollbar absolute top-full left-0 z-[60] mt-1 max-h-60 w-[calc(100%-2.5rem)] overflow-y-auto overscroll-contain rounded-lg border border-gray-200 bg-white shadow-2xl">
                                        <li x-show="filteredClientList.length === 0"
                                            class="px-4 py-4 text-center text-sm font-medium text-gray-500">
                                            No matching customers found.
                                        </li>

                                        <template x-for="client in filteredClientList" :key="client.id">
                                            <li @click="selectCustomer(client)"
                                                class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors last:border-0 hover:bg-gray-50">
                                                <div class="text-[13px] font-bold text-gray-800" x-text="client.name"></div>
                                                <div
                                                    class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                                    <span x-show="client.phone" x-text="'📞 ' + client.phone"></span>
                                                    <span x-show="client.gst_number || client.gstin"
                                                        class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold text-gray-600"
                                                        x-text="'GST: ' + (client.gst_number || client.gstin)"></span>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                {{-- 🌟 GSTIN Drops cleanly below the row --}}
                                <div x-show="formData.customer_gstin" x-cloak
                                    class="mt-1.5 pl-1 text-[11px] font-bold text-gray-500">
                                    GSTIN: <span class="text-blue-600" x-text="formData.customer_gstin"></span>
                                </div>
                            </div>
                            <button type="button" @click="toggleGuestMode()"
                                :class="isGuest ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600'"
                                class="rounded border border-transparent px-4 py-4 text-xs font-bold tracking-widest uppercase transition-colors">
                                <span x-text="isGuest ? 'Guest Active' : 'Guest'"></span>
                            </button>
                        </div>
                        {{-- Manual Name for Guests --}}
                        <div x-show="isGuest" x-cloak class="mt-3">
                            <input type="text" name="customer_name" x-model="formData.customer_name"
                                placeholder="Enter Guest Name..."
                                class="focus:border-brand-500 w-full rounded border border-gray-300 px-3 py-2 text-sm outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Invoice
                            Date</label>
                        <input type="date" name="invoice_date" x-model="formData.invoice_date" required
                            class="focus:border-brand-500 w-full rounded border border-gray-300 px-3 py-2.5 text-sm font-medium outline-none" />
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Due Date</label>
                        <input type="date" name="due_date" x-model="formData.due_date"
                            class="focus:border-brand-500 w-full rounded border border-gray-300 px-3 py-2.5 text-sm font-medium outline-none" />
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">GST
                            Treatment</label>
                        <div
                            @change="
                                formData.gst_treatment = $event.target.value;
                                calculate();
                            ">
                            <x-custom-select name="gst_treatment" :options="[
                                'unregistered' => 'Unregistered Business (B2C)',
                                'registered' => 'Registered Business (B2B)',
                                'composition' => 'Composition',
                                'overseas' => 'Overseas (Export)',
                                'sez' => 'SEZ',
                            ]" :selected="old('gst_treatment', 'unregistered')"
                                @set-gst-treatment.window="value = $event.detail" />
                        </div>
                    </div>
                    <input type="hidden" name="store_id" value="{{ active_store()?->id }}" />

                    <div>
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Stock
                            Warehouse</label>
                        <div @change="formData.warehouse_id = $event.target.value">
                            <x-custom-select name="warehouse_id" placeholder="Select Warehouse" :options="$warehouseOptions"
                                :selected="old(
                                    'warehouse_id',
                                    $warehouses->firstWhere('is_default', true)?->id ??
                                        ($warehouses->first()?->id ?? ''),
                                )" required />
                        </div>
                    </div>

                    <div>
                        <label for="supply_state"
                            class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Place of Supply
                            (State)</label>
                        <div
                            @change="
                                formData.supply_state = $event.target.value;
                                calculate();
                            ">
                            <x-custom-select name="supply_state" id="supply_state" placeholder="Select State"
                                class="font-bold text-[#108c2a]" :options="$stateOptions" :selected="old('supply_state', $companyState)"
                                @set-supply-state.window="value = $event.detail" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. PRODUCT SEARCH & LINE ITEMS --}}
            <div class="mb-6 flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm">
                <div
                    class="flex flex-col justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center">
                    <h2 class="text-lg font-bold tracking-tight text-gray-800">Billing Items</h2>

                    {{-- Global SKU Search (Exactly like Purchase Module) --}}
                    <div class="relative w-full sm:max-w-md md:max-w-lg lg:max-w-2xl" x-data="{ showResults: false }">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" x-model="globalSearch" @input.debounce.300ms="fetchGlobalSkus()"
                            @focus="showResults = true" @click.away="showResults = false"
                            placeholder="Type product name or sku..."
                            class="focus:border-brand-500 w-full rounded border border-gray-300 bg-white py-2.5 pr-4 pl-9 text-sm shadow-sm outline-none" />

                        <ul x-show="showResults && globalSearch.length > 1" x-cloak
                            class="custom-scrollbar absolute top-full left-0 z-[60] mt-1 max-h-60 w-full overflow-y-auto overscroll-contain rounded-lg border border-gray-200 bg-white shadow-2xl">
                            {{-- 🌟 Searching Indicator --}}
                            <li x-show="isSearching"
                                class="flex items-center justify-center gap-2 px-4 py-3 text-center text-xs font-medium text-gray-500">
                                <svg class="h-4 w-4 animate-spin text-[#108c2a]" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Searching inventory...
                            </li>

                            {{-- 🌟 No Results Indicator --}}
                            <li x-show="!isSearching && globalSearchResults.length === 0" class="px-4 py-4 text-center">
                                <span class="block text-sm font-bold text-gray-700">No products found in this
                                    warehouse</span>
                                <span class="mt-0.5 block text-[11px] text-gray-400">Try a different name or ensure the
                                    item has stock.</span>
                            </li>

                            <template x-for="result in globalSearchResults" :key="result.product_sku_id">
                                <li @click="
                                        addSkuToTable(result);
                                        showResults = false;
                                    "
                                    class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors last:border-0 hover:bg-gray-50">
                                    <div class="flex items-start justify-between gap-2">
                                        {{-- 🌟 NEW: Primary Display Name (Product - Variant) --}}
                                        <div class="text-[13px] leading-tight font-bold text-gray-800"
                                            x-text="result.display_name"></div>
                                        <div class="flex-shrink-0 text-[12px] font-black text-[#108c2a]"
                                            x-text="result.price ? '₹' + parseFloat(result.price).toFixed(2) : '₹0'"></div>
                                    </div>
                                    {{-- 🌟 NEW: Detailed Secondary Line --}}
                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-gray-500">
                                        <span>Code:</span>
                                        <span
                                            class="rounded border border-gray-200 bg-gray-100 px-1 py-0.5 font-mono text-[10px] font-bold tracking-wide text-gray-600"
                                            x-text="result.sku_code"></span>
                                        <span class="text-gray-300">&middot;</span>
                                        <span x-text="'Stock: ' + (result.stock || 0)"></span>
                                        <span class="text-gray-300">&middot;</span>
                                        <span x-text="'Unit: ' + (result.unit_name || 'Unit')"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
                <div class="hidden min-h-[200px] overflow-x-auto md:block">
                    <table class="w-full border-collapse text-left">
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                            <tr>
                                <th class="min-w-[250px] px-5 py-4">PRODUCT DETAILS</th>
                                <th class="min-w-[100px] px-4 py-4 text-center">HSN/SAC</th>
                                {{-- Updated: Added min-w-[140px] --}}
                                <th class="min-w-[140px] px-4 py-4 text-right">UNIT PRICE</th>
                                {{-- Updated: Added min-w-[140px] --}}
                                <th class="min-w-[140px] px-4 py-4 text-center">QTY</th>
                                <th class="min-w-[100px] px-4 py-4 text-right">TAX %</th>
                                <th class="min-w-[120px] px-5 py-4 text-right">LINE TOTAL</th>
                                <th class="w-[60px] px-4 py-4 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.key">
                                <tr class="transition-colors hover:bg-gray-50/50">
                                    {{-- 1. Product Details & Actions --}}
                                    <td class="px-5 py-3">
                                        <div class="flex items-start gap-2">
                                            {{-- 🌟 FIX: Primary Title + Icon tightly grouped --}}
                                            <div class="text-[13px] leading-tight font-bold text-gray-800"
                                                x-text="item.display_name"></div>
                                            {{-- A bare pencil gave no clue what it edits. The label
                                                 names the panel it opens, so tax, discount, HSN and
                                                 unit are discoverable without guessing. --}}
                                            <button type="button" @click="openItemModal(index)"
                                                class="mt-0.5 inline-flex flex-shrink-0 items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-600 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                                title="Tax, discount, HSN and unit for this line">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z">
                                                    </path>
                                                </svg>
                                                Options
                                            </button>
                                        </div>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] font-medium text-gray-500">Code:</span>
                                                <span
                                                    class="rounded border border-green-200 bg-[#dcfce7] px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wide text-[#16a34a]"
                                                    x-text="item.sku_code"></span>
                                            </div>

                                            {{-- HSN surfaced here too, so a missing code is obvious
                                                 before the invoice is saved rather than at filing time. --}}
                                            <span class="rounded border px-1.5 py-0.5 text-[10px] font-bold"
                                                :class="item.hsn_code ?
                                                    'border-gray-200 bg-gray-50 text-gray-600' :
                                                    'border-red-200 bg-red-50 text-red-600'"
                                                x-text="item.hsn_code ? 'HSN ' + item.hsn_code : 'HSN missing'"></span>

                                            <span x-show="Number(item.tax_percent) > 0"
                                                class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700"
                                                x-text="
                                                    'GST ' + item.tax_percent + '% ' +
                                                    (item.tax_type === 'inclusive' ? 'incl.' : 'excl.')
                                                "></span>

                                            <span x-show="item.discount_value > 0"
                                                class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
                                                Disc:
                                                <span
                                                    x-text="
                                                        item.discount_type === 'percentage'
                                                            ? item.discount_value + '%'
                                                            : '₹' + item.discount_value
                                                    "></span>
                                            </span>
                                        </div>

                                        {{-- Hidden Inputs for Laravel --}}
                                        <input type="hidden" :name="'items[' + index + '][tax_type]'"
                                            :value="item.tax_type" />
                                        <input type="hidden" :name="'items[' + index + '][tax_percent]'"
                                            :value="item.tax_percent" />
                                        <input type="hidden" :name="'items[' + index + '][discount_type]'"
                                            :value="item.discount_type" />
                                        <input type="hidden" :name="'items[' + index + '][discount_value]'"
                                            :value="item.discount_value" />
                                        <input type="hidden" :name="'items[' + index + '][product_id]'"
                                            :value="item.product_id" />
                                        <input type="hidden" :name="'items[' + index + '][product_name]'"
                                            :value="item.product_name" />
                                        {{-- 🌟 NEW: Keep Display Name on validation errors --}}
                                        <input type="hidden" :name="'items[' + index + '][display_name]'"
                                            :value="item.display_name" />
                                        <input type="hidden" :name="'items[' + index + '][sku_code]'"
                                            :value="item.sku_code" />
                                        <input type="hidden" :name="'items[' + index + '][product_sku_id]'"
                                            :value="item.product_sku_id" />
                                        <input type="hidden" :name="'items[' + index + '][unit_id]'"
                                            :value="item.unit_id" />
                                        <input type="hidden" :name="'items[' + index + '][unit_price]'"
                                            :value="item.unit_price" />
                                        <input type="hidden" :name="'items[' + index + '][quantity]'"
                                            :value="item.quantity" />
                                        <input type="hidden" :name="'items[' + index + '][hsn_code]'"
                                            :value="item.hsn_code" />
                                        {{-- Challan conversion fields --}}
                                        <input type="hidden" :name="'items[' + index + '][challan_item_id]'"
                                            :value="item.challan_item_id ?? ''" />
                                        <input type="hidden" :name="'items[' + index + '][batch_id]'"
                                            :value="item.batch_id ?? ''" />
                                        <input type="hidden" :name="'items[' + index + '][batch_number]'"
                                            :value="item.batch_number ?? ''" />
                                    </td>

                                    {{-- 2. HSN/SAC Code --}}
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-mono text-[12px] text-gray-600"
                                            x-text="item.hsn_code || '-'"></span>
                                    </td>

                                    {{-- 3. Base Unit Price Input --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="relative w-full min-w-[100px]">
                                            <span
                                                class="absolute top-1/2 left-3 -translate-y-1/2 text-sm font-bold text-gray-400">₹</span>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                x-model="item.unit_price"
                                                @input="item.unit_price = window.sanitizeInteger($event); calculate();"
                                                @blur="item.unit_price = window.commitInteger(item.unit_price, 0); calculate();"
                                                class="focus:border-brand-500 h-10 w-full rounded border border-gray-300 px-2 pl-7 text-right text-sm font-bold text-gray-700 shadow-sm transition-all outline-none md:h-9"
                                                placeholder="0" />
                                        </div>
                                    </td>

                                    {{-- 4. Quantity Input --}}
                                    <td class="px-4 py-3 align-middle">
                                        <template x-if="item.challan_item_id">
                                            <div class="text-center text-[14px] font-black text-gray-800"
                                                x-text="item.quantity"></div>
                                        </template>
                                        <template x-if="!item.challan_item_id">
                                            <div class="flex min-w-[120px] items-center justify-center">
                                                <button type="button"
                                                    @click="
                                                        item.quantity = Math.max(1, parseFloat(item.quantity || 0) - 1);
                                                        calculate();
                                                    "
                                                    class="flex h-10 w-10 items-center justify-center rounded-l border border-gray-300 bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100 active:bg-gray-200 md:h-9 md:w-8">
                                                    -
                                                </button>
                                                <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                    x-model="item.quantity"
                                                    @input="item.quantity = window.sanitizeInteger($event); calculate();"
                                                    @blur="item.quantity = window.commitInteger(item.quantity, 1); calculate();"
                                                    class="focus:border-brand-500 h-10 w-16 border-x-0 border-y border-gray-300 p-0 text-center text-sm font-bold text-gray-700 shadow-inner outline-none focus:ring-0 md:h-9"
                                                    placeholder="0" />
                                                <button type="button"
                                                    @click="
                                                        item.quantity = parseFloat(item.quantity || 0) + 1;
                                                        calculate();
                                                    "
                                                    class="flex h-10 w-10 items-center justify-center rounded-r border border-gray-300 bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100 active:bg-gray-200 md:h-9 md:w-8">
                                                    +
                                                </button>
                                            </div>
                                        </template>
                                    </td>

                                    {{-- 5. Tax Percentage Display --}}
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-[12px] font-bold text-gray-700" x-text="item.tax_percent + '%'">
                                        </div>
                                        <div class="text-[9px] text-gray-400 uppercase" x-text="item.tax_type"></div>
                                    </td>

                                    {{-- 6. Line Total --}}
                                    <td class="px-5 py-3 text-right max-w-[140px]">
                                        <span class="text-[14px] font-black text-gray-800 block truncate"
                                            :title="formatCurrency(item.line_total)"
                                            x-text="formatCurrency(item.line_total)"></span>
                                    </td>

                                    {{-- 7. Remove Action --}}
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeItem(index)"
                                            class="rounded p-1.5 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                            title="Remove Item">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
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
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="divide-y divide-gray-100 border-t border-gray-100 md:hidden">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="relative flex flex-col gap-3 bg-white p-4">
                            {{-- Header: Name, SKU, Remove --}}
                            <div class="flex items-start justify-between pr-8">
                                <div>
                                    <div class="text-[13px] leading-tight font-bold text-gray-800"
                                        x-text="item.display_name"></div>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="text-[11px] font-medium text-gray-500">Code:</span>
                                        <div class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wide text-gray-600"
                                            x-text="item.sku_code"></div>
                                    </div>
                                    <span x-show="item.discount_value > 0"
                                        class="mt-1 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
                                        Disc:
                                        <span
                                            x-text="
                                                item.discount_type === 'percentage'
                                                    ? item.discount_value + '%'
                                                    : '₹' + item.discount_value
                                            "></span>
                                    </span>
                                </div>
                                <button type="button" @click="removeItem(index)"
                                    class="absolute top-4 right-4 rounded bg-red-50 p-1.5 text-red-400 transition-colors hover:text-red-600"
                                    title="Remove Item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                        </path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>
                            </div>

                            {{-- Context: HSN & Tax --}}
                            <div
                                class="flex items-center justify-between rounded border border-gray-100 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-500">
                                <span>HSN:
                                    <span class="font-mono font-bold text-gray-700"
                                        x-text="item.hsn_code || '-'"></span></span>
                                <span>Tax: <span class="font-bold text-gray-700" x-text="item.tax_percent + '%'"></span>
                                    <span class="uppercase" x-text="item.tax_type"></span></span>
                            </div>

                            {{-- Inputs: Price & Qty --}}
                            <div class="mt-1 flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="mb-1 block text-[10px] font-bold text-gray-500 uppercase">Unit
                                        Price</label>
                                    <div class="relative w-full">
                                        <span
                                            class="absolute top-1/2 left-2.5 -translate-y-1/2 text-sm font-bold text-gray-400">₹</span>
                                        <input type="text" inputmode="numeric" pattern="[0-9]*"
                                            x-model="item.unit_price"
                                            @input="item.unit_price = window.sanitizeInteger($event); calculate();"
                                            @blur="item.unit_price = window.commitInteger(item.unit_price, 0); calculate();"
                                            class="focus:border-brand-500 h-10 w-full rounded border border-gray-300 px-2 pl-7 text-sm font-bold text-gray-700 shadow-sm transition-all outline-none" />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-center text-[10px] font-bold text-gray-500 uppercase">Qty</label>
                                    <template x-if="item.challan_item_id">
                                        <div class="flex h-10 min-w-[100px] items-center justify-center rounded border border-gray-200 bg-gray-50 px-4 text-[14px] font-black text-gray-800"
                                            x-text="item.quantity"></div>
                                    </template>
                                    <template x-if="!item.challan_item_id">
                                        <div class="flex min-w-[110px] items-center justify-center">
                                            <button type="button"
                                                @click="
                                                    item.quantity = Math.max(1, parseFloat(item.quantity || 0) - 1);
                                                    calculate();
                                                "
                                                class="flex h-10 w-9 items-center justify-center rounded-l border border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100 active:bg-gray-200">
                                                -
                                            </button>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                x-model="item.quantity"
                                                @input="item.quantity = window.sanitizeInteger($event); calculate();"
                                                @blur="item.quantity = window.commitInteger(item.quantity, 1); calculate();"
                                                class="focus:border-brand-500 h-10 w-14 border-x-0 border-y border-gray-300 p-0 text-center text-sm font-bold text-gray-700 shadow-inner outline-none focus:ring-0" />
                                            <button type="button"
                                                @click="
                                                    item.quantity = parseFloat(item.quantity || 0) + 1;
                                                    calculate();
                                                "
                                                class="flex h-10 w-9 items-center justify-center rounded-r border border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100 active:bg-gray-200">
                                                +
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Footer: Line Total & Settings --}}
                            <div class="mt-1 flex items-center justify-between border-t border-gray-50 pt-3">
                                <button type="button" @click="openItemModal(index)"
                                    class="flex items-center gap-1.5 rounded bg-blue-50 px-2.5 py-1.5 text-[11px] font-bold tracking-wider text-blue-600 uppercase transition-colors hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                    </svg>
                                    Edit Settings
                                </button>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Line
                                        Total</span>
                                    <span class="text-[16px] font-black text-[#108c2a]"
                                        x-text="formatCurrency(item.line_total)"></span>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Empty State for Mobile --}}
                    <div x-show="items.length === 0" class="bg-white p-8 text-center text-sm text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-2 h-8 w-8 opacity-50"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        No items added yet.
                    </div>
                </div>
            </div>

            {{-- 3. SUMMARY & PAYMENT --}}
            {{-- UI Fix: 2 columns on iPad (md), 3 columns on Desktop (xl) --}}
            <div class="mb-10 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                {{-- UI Fix: Notes takes full width on iPad, 1 column on Desktop --}}
                <div
                    class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm md:col-span-2 xl:col-span-1">
                    <h3 class="pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Notes & Terms
                    </h3>
                    <textarea name="notes" rows="2" placeholder="Internal notes..."
                        class="focus:border-brand-500 w-full resize-none rounded border border-gray-300 px-3 py-2 text-sm outline-none"></textarea>
                    <textarea name="terms_conditions" rows="2" placeholder="Customer terms..."
                        class="focus:border-brand-500 w-full resize-none rounded border border-gray-300 px-3 py-2 text-sm outline-none"></textarea>
                </div>

                {{-- PAYMENT & RECONCILIATION --}}
                <div class="rounded-lg border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Payment Receipt
                    </h3>
                    <div class="space-y-4">
                        {{-- Amount first: the mode only matters once money has
                             actually been received, so asking for it up front
                             made an optional field look mandatory. --}}
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">Amount
                                Received (₹)</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="amount_paid"
                                x-model="global.amount_paid"
                                @input="
                                    global.amount_paid = window.sanitizeInteger($event);
                                    calculate();
                                "
                                @blur="global.amount_paid = window.commitInteger(global.amount_paid, 0); calculate();"
                                class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm font-black text-[#108c2a] outline-none focus:border-[#108c2a]"
                                placeholder="0" />
                        </div>

                        <x-payment-method-select name="payment_method_id" label="Payment Mode" :required="false"
                            variant="rich" required-when="Number(global.amount_paid) > 0"
                            x-model="formData.payment_method_id" />

                        <div class="flex items-center justify-between border-t border-gray-50 pt-3">
                            <span class="text-xs font-bold text-gray-400 uppercase"
                                x-text="balance.is_change ? 'Return Change:' : 'Due Amount:'"></span>
                            <span :class="balance.is_change ? 'text-blue-600' : 'text-red-600'" class="text-lg font-black"
                                x-text="formatCurrency(balance.value)"></span>
                        </div>
                    </div>
                </div>

                {{-- FINANCIAL SUMMARY --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3
                        class="mb-4 border-b border-gray-200 pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Financials
                    </h3>

                    {{-- Global Discount --}}
                    <div class="flex w-full items-center pt-2">
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="font-semibold text-gray-600">Discount:</span>

                            {{-- The wrapper catches the change event bubbling up
                                 from the component's hidden native select. That is
                                 the only way this component writes back into the
                                 parent Alpine scope — it owns its own value. --}}
                            <div class="w-36"
                                @change="
                                    global.discount_type = $event.target.value;

                                    if (global.discount_type === 'percentage') {
                                        global.discount_value = Math.min(100, global.discount_value || 0);
                                    }

                                    calculate();
                                ">
                                <x-custom-select name="discount_type" :options="$discountTypeOptions" :selected="old('discount_type', 'fixed')" />
                            </div>
                        </div>

                        {{-- Flex-1 naturally pushes the input to the extreme right --}}
                        <div class="flex flex-1 justify-end pl-4">
                            {{-- Without a name this input never reached the server:
                                 the running total updated correctly in Alpine while
                                 discount_value arrived as null, so every saved
                                 invoice came back with zero discount. --}}
                            <input type="text" inputmode="numeric" name="discount_value" placeholder="0"
                                x-model="global.discount_value"
                                @input="
                                    global.discount_value = window.sanitizeInteger($event, {
                                        max: global.discount_type === 'percentage' ? 100 : null
                                    });

                                    calculate();
                                "
                                @blur="global.discount_value = window.commitInteger(global.discount_value, 0); calculate();"
                                class="w-24 rounded border border-gray-300 px-3 py-1.5 text-right font-bold text-red-500 outline-none focus:border-[#108c2a] sm:w-32" />
                        </div>
                    </div>

                    {{-- Shipping + GST --}}
                    <div class="mt-4 space-y-4 border-b border-gray-100 pb-4">
                        {{-- Row 1: Shipping Amount --}}
                        <div class="flex w-full items-center">
                            <span class="shrink-0 font-semibold text-gray-600">Shipping (₹):</span>
                            <div class="flex flex-1 justify-end pl-4">
                                {{-- Same missing-name bug as the discount field.
                                     The server field is shipping_charge — init()
                                     already reads old('shipping_charge'), so the
                                     name was decided and simply never applied,
                                     which also broke shipping GST downstream. --}}
                                <input type="text" inputmode="numeric" name="shipping_charge" placeholder="0"
                                    x-model="global.shipping"
                                    @input="
                                        global.shipping = window.sanitizeInteger($event);
                                        calculate();
                                    "
                                    @blur="global.shipping = window.commitInteger(global.shipping, 0); calculate();"
                                    class="w-24 rounded border border-gray-300 px-3 py-1.5 text-right font-bold text-gray-800 outline-none focus:border-[#108c2a] sm:w-32" />

                            </div>
                        </div>

                        {{-- Row 2: Shipping GST Rate selector --}}
                        <div class="flex w-full items-center">
                            <div class="flex shrink-0 items-center gap-2">
                                <span class="text-sm font-semibold text-gray-600">Shipping GST:</span>
                                <div class="w-44"
                                    @change="
                                    global.shipping_tax_rate = $event.target.value;
                                    calculate();
                                ">
                                    <x-custom-select name="shipping_tax_rate" :options="$shippingGstOptions" :selected="old('shipping_tax_rate', '0')" />
                                </div>
                            </div>
                            <div class="flex flex-1 items-center justify-end pl-4">
                                <span x-show="global.shipping_tax > 0" class="text-sm font-bold text-gray-700"
                                    x-text="'₹ ' + parseFloat(global.shipping_tax).toFixed(2)">
                                </span>
                                <span x-show="global.shipping_tax == 0"
                                    class="rounded border border-gray-200 bg-gray-100 px-2 py-1 text-[11px] font-bold text-gray-500">
                                    Exempt
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Products Total:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.products_total)"></span>
                        </div>

                        {{-- Shipping net amount --}}
                        <div class="flex items-center justify-between" x-show="global.shipping > 0">
                            <span class="font-semibold">Shipping:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(global.shipping)"></span>
                        </div>

                        {{-- Shipping GST (only when taxable) --}}
                        <div class="flex items-center justify-between text-gray-500" x-show="global.shipping_tax > 0">
                            <span x-text="'Shipping GST (' + global.shipping_tax_rate + '%):'" class="text-sm"></span>
                            <span x-text="formatCurrency(global.shipping_tax)"></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Taxable Amount:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.taxable_amount)"></span>
                        </div>

                        {{-- 🌟 Dynamic Tax Breakdown --}}
                        <template x-if="totals.isInterState">
                            <div class="flex items-center justify-between text-gray-500">
                                <span
                                    x-text="
                                        'IGST (' +
                                        (totals.igst_rate === 'Mixed'
                                            ? 'Mixed'
                                            : parseFloat(totals.igst_rate).toFixed(2) + '%') +
                                        '):'
                                    "></span>
                                <span x-text="formatCurrency(totals.igst)"></span>
                            </div>
                        </template>
                        <template x-if="!totals.isInterState">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-gray-500">
                                    <span
                                        x-text="
                                            'CGST (' +
                                            (totals.cgst_rate === 'Mixed'
                                                ? 'Mixed'
                                                : parseFloat(totals.cgst_rate).toFixed(2) + '%') +
                                            '):'
                                        "></span>
                                    <span x-text="formatCurrency(totals.cgst)"></span>
                                </div>
                                <div class="flex items-center justify-between text-gray-500">
                                    <span
                                        x-text="
                                            'SGST (' +
                                            (totals.sgst_rate === 'Mixed'
                                                ? 'Mixed'
                                                : parseFloat(totals.sgst_rate).toFixed(2) + '%') +
                                            '):'
                                        "></span>
                                    <span x-text="formatCurrency(totals.sgst)"></span>
                                </div>
                            </div>
                        </template>

                        {{-- Final Totals --}}
                        <div class="flex items-end justify-between pt-2">
                            <div>
                                <div class="text-[11px] font-bold text-gray-500 uppercase">Auto Round Off</div>
                                <div class="mt-1 text-sm font-bold text-gray-600" x-text="global.round_off"></div>
                                <input type="hidden" name="round_off" :value="global.round_off" />
                            </div>
                            <div class="text-right">
                                <div class="mb-1 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                    Net Payable
                                </div>
                                <div class="text-2xl font-black text-[#108c2a]"
                                    x-text="formatCurrency(totals.grand_total)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div
                class="flex flex-col items-stretch justify-end gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                <a href="{{ route('admin.invoices.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-center text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50">
                    CANCEL
                </a>
                <button type="submit" name="status" value="confirmed"
                    class="flex items-center justify-center gap-2 rounded-lg bg-[#108c2a] px-8 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0c6b1f] active:scale-95">
                    <i data-lucide="check-circle" class="h-4 w-4"></i> Save &amp; Confirm
                </button>
                <button type="submit" name="status" value="draft"
                    class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                    <i data-lucide="save" class="h-4 w-4"></i> Save as Draft
                </button>
            </div>
        </form>

        {{-- ITEM SETTINGS MODAL (一致性 with Purchase Order) --}}
        <div x-show="isItemModalOpen" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-end bg-black/50 backdrop-blur-sm transition-opacity">
            <div class="flex h-full w-full max-w-md flex-col bg-white shadow-2xl" x-show="isItemModalOpen" x-transition>
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <h3 class="text-[15px] font-bold text-gray-800" x-text="activeEditData.product_name"></h3>
                    <button @click="closeItemModal()" class="text-gray-400 hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="custom-scrollbar flex-1 space-y-5 overflow-y-auto p-6">
                    {{-- HSN first: it is the field most often wrong or missing,
                         and it drives GST filing rather than the line total. --}}
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">HSN / SAC
                            Code</label>
                        <input type="text" inputmode="numeric" maxlength="8" x-model="activeEditData.hsn_code"
                            @input="activeEditData.hsn_code = $event.target.value.replace(/\D/g, '')"
                            placeholder="e.g. 06029000"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 font-mono text-sm outline-none focus:border-[#108c2a]" />
                        <p class="mt-1 text-[10px] text-gray-400">
                            Pre-filled from the SKU, or the product when the SKU has none.
                            Editing here applies to this line only.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">Tax
                            Type</label>
                        {{-- Modal fields need two-way sync: the wrapper writes out
                             on change, and openItemModal() pushes the current line's
                             value back in, since the component keeps its own state. --}}
                        <div @change="activeEditData.tax_type = $event.target.value">
                            <x-custom-select name="modal_tax_type" :options="$taxTypeOptions" selected="exclusive"
                                @set-item-tax-type.window="value = $event.detail" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">GST
                            (%)</label>
                        <input type="number" step="0.01" x-model="activeEditData.tax_percent"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm outline-none" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">Discount
                            Type</label>
                        <div @change="activeEditData.discount_type = $event.target.value">
                            <x-custom-select name="modal_discount_type" :options="$itemDiscountTypeOptions" selected="fixed"
                                @set-item-discount-type.window="value = $event.detail" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">Discount
                            Value</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*"
                            x-model="activeEditData.discount_value"
                            @input="activeEditData.discount_value = window.sanitizeInteger($event, {
                                max: activeEditData.discount_type === 'percentage' ? 100 : null
                            })"
                            @blur="activeEditData.discount_value = window.commitInteger(activeEditData.discount_value, 0)"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm outline-none" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">Sales
                            Unit</label>
                        <div @change="activeEditData.unit_id = $event.target.value">
                            <x-custom-select name="modal_unit_id" placeholder="Select Unit" :options="$unitOptions"
                                @set-item-unit.window="value = $event.detail" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 border-t border-gray-100 bg-white p-5">
                    <button @click="closeItemModal()"
                        class="rounded-lg bg-gray-100 py-2.5 text-sm font-bold text-gray-700">
                        Cancel
                    </button>
                    <button @click="saveItemModal()" class="rounded-lg bg-[#108c2a] py-2.5 text-sm font-bold text-white">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

        {{-- 🌟 QUICK CLIENT MODAL COMPONENT --}}
        <x-quick-client-modal :states="$states" />
    </div>
@endsection

@push('scripts')
    <script>
        // 🌟 ADD allClients and challanItems to the signature
        function invoiceForm(
            allUnits = [],
            companyState = "",
            allClients = [],
            challanItems = null,
            orderItems = null,
            orderGuestPrefill = null,
        ) {
            return {
                clientsList: allClients, // 🌟 Store clients here for dynamic rendering
                units: allUnits,
                company_state: companyState,
                items: [],
                itemCounter: 0,
                globalSearch: "",
                isSearching: false,
                globalSearchResults: [],
                isGuest: false,

                // 🌟 NEW: Searchable Dropdown State
                clientSearchTerm: "",
                isClientDropdownOpen: false,

                formData: {
                    customer_id: @json (old('customer_id')) || "",
                    customer_name: @json (old('customer_name')) || "",
                    customer_gstin: "", // 🌟 Track GSTIN
                    gst_treatment: @json (old('gst_treatment')) || "unregistered",
                    supply_state: @json (old('supply_state')) || companyState,
                    payment_method_id: @json (old('payment_method_id')) || "",
                    invoice_date: @json (old('invoice_date')) || new Date().toISOString().split("T")[0],
                    due_date: @json (old('due_date')) ||
                        new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split("T")[0],
                    store_id: "{{ active_store()?->id ?? '' }}",
                    warehouse_id: "{{ old('warehouse_id', $warehouses->firstWhere('is_default', true)?->id ?? ($warehouses->first()?->id ?? '')) }}",
                },

                global: {
                    shipping: parseFloat(@json (old('shipping_charge'))) || 0,
                    shipping_tax_rate: parseFloat(@json (old('shipping_tax_rate'))) || 0,
                    shipping_tax: 0, // auto-calculated, never submitted (computed from rate)
                    amount_paid: parseFloat(@json (old('amount_paid'))) || 0,
                    discount_type: @json (old('discount_type')) || "fixed",
                    discount_value: parseFloat(@json (old('discount_value'))) || 0,
                    round_off: "0.00",
                },
                totals: {
                    subtotal: 0,
                    products_total: 0,
                    taxable_amount: 0,
                    tax: 0,
                    cgst: 0,
                    sgst: 0,
                    igst: 0,
                    cgst_rate: 0, // 🌟 Track dynamic rates
                    sgst_rate: 0,
                    igst_rate: 0,
                    isInterState: false,
                    grand_total: 0,
                },
                balance: {
                    value: 0,
                    is_change: false,
                },

                // 🌟 NEW: Filter the list based on what the user types
                get filteredClientList() {
                    if (this.clientSearchTerm.trim() === "") {
                        return this.clientsList;
                    }
                    const term = this.clientSearchTerm.toLowerCase();
                    return this.clientsList.filter((client) => {
                        return client.name.toLowerCase().includes(term) || (client.phone && client.phone
                            .includes(term));
                    });
                },

                // 🌟 ADD THIS INIT BLOCK
                init() {
                    let oldItems = @json (old('items', []));

                    // 1. Recover items if validation failed
                    if (oldItems && Object.keys(oldItems).length > 0) {
                        let itemsArray = Array.isArray(oldItems) ? oldItems : Object.values(oldItems);
                        this.items = itemsArray.map((item) => ({
                            key: this.itemCounter++,
                            challan_item_id: item.challan_item_id || null,
                            batch_id: item.batch_id || null,
                            batch_number: item.batch_number || "",
                            product_id: item.product_id,
                            product_sku_id: item.product_sku_id,
                            unit_id: item.unit_id,
                            product_name: item.product_name || "Restored Item",
                            display_name: item.display_name || item.product_name || "Restored Item",
                            sku_code: item.sku_code || "-",
                            hsn_code: item.hsn_code || "",
                            quantity: parseFloat(item.quantity) || 1,
                            unit_price: parseFloat(item.unit_price) || 0,
                            tax_percent: parseFloat(item.tax_percent) || 0,
                            tax_type: item.tax_type || "exclusive",
                            discount_type: item.discount_type || "fixed",
                            discount_value: parseFloat(item.discount_value) || 0,
                            line_total: 0,
                        }));
                        this.calculate();
                    }
                    // 2. Pre-populate items from Challan conversion
                    else if (challanItems && challanItems.length > 0) {
                        this.items = challanItems.map((item) => ({
                            key: this.itemCounter++,
                            challan_item_id: item.challan_item_id,
                            product_id: item.product_id,
                            product_sku_id: item.product_sku_id,
                            unit_id: item.unit_id,
                            product_name: item.product_name,
                            display_name: item.display_name || item.product_name,
                            sku_code: item.sku_code,
                            hsn_code: item.hsn_code || "",
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            tax_percent: item.tax_percent,
                            tax_type: item.tax_type || "exclusive",
                            discount_type: item.discount_type || "fixed",
                            discount_value: item.discount_value || 0,
                            batch_id: item.batch_id || null,
                            batch_number: item.batch_number || "",
                            line_total: 0,
                        }));
                        this.calculate();
                    }

                    // 3. Pre-populate items from Order conversion
                    else if (orderItems && orderItems.length > 0) {
                        this.items = orderItems.map((item) => ({
                            key: this.itemCounter++,
                            challan_item_id: null,
                            product_id: item.product_id,
                            product_sku_id: item.product_sku_id,
                            unit_id: item.unit_id,
                            product_name: item.product_name,
                            display_name: item.display_name || item.product_name,
                            sku_code: item.sku_code || "",
                            hsn_code: item.hsn_code || "",
                            quantity: item.quantity,
                            unit_price: item.unit_price, // 0 for inquiry/catalog orders
                            tax_percent: item.tax_percent,
                            tax_type: item.tax_type || "exclusive",
                            discount_type: "fixed",
                            discount_value: 0,
                            batch_id: null,
                            batch_number: "",
                            line_total: 0,
                        }));
                        this.calculate();
                    }

                    // Pre-fill guest name from order if available.
                    // Admin must manually select or create the matching Client.
                    this.$nextTick(() => {
                        if (orderGuestPrefill && orderGuestPrefill.name) {
                            this.isGuest = true;
                            this.formData.customer_name = orderGuestPrefill.name;
                            this.formData.customer_id = "";
                            this.clientSearchTerm = "";
                        }
                    });
                },

                isItemModalOpen: false,
                activeEditIndex: null,
                activeEditData: {},
                // 🌟 QUICK CLIENT MODAL STATE
                isClientModalOpen: false,
                newClient: {
                    name: "",
                    phone: "",
                    city: "",
                    state_id: "",
                    registration_type: "unregistered",
                },

                async saveQuickClient() {
                    // 1. Check for empty fields
                    if (!this.newClient.name) {
                        BizAlert.toast("Please fill name", "error");
                        return;
                    }

                    // 🌟 2. Strict Phone Check (Exactly 10 digits if provided)
                    if (this.newClient.phone && this.newClient.phone.length !== 10) {
                        BizAlert.toast("Phone number must be exactly 10 digits.", "error");
                        return;
                    }

                    try {
                        BizAlert.loading("Saving Client...");

                        // 1. Safely check for CSRF token
                        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (!csrfMeta) {
                            BizAlert.toast("Security Error: CSRF token missing in layout.", "error");
                            return;
                        }

                        // 2. Use Laravel's route helper to guarantee the correct URL
                        let response = await fetch("{{ route('admin.clients.store') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": csrfMeta.content,
                            },
                            body: JSON.stringify(this.newClient),
                        });

                        // 3. Safely attempt to parse JSON. If Laravel threw a 500 Whoops page, this catches it!
                        let data;
                        try {
                            data = await response.json();
                        } catch (parseError) {
                            console.error("Server did not return JSON. It returned HTML. Check Laravel Logs.");
                            BizAlert.toast("Server Error (500). Please check Laravel logs.", "error");
                            return;
                        }

                        // 4. Handle 422 Validation Errors from the backend
                        if (!response.ok) {
                            let errorMsg = data.message || "Failed to save client";
                            if (data.errors) {
                                errorMsg = Object.values(data.errors)[0][
                                    0
                                ]; // Grab the exact validation message (e.g. "Phone must be unique")
                            }
                            BizAlert.toast(errorMsg, "error");
                            return;
                        }

                        // 5. Success! Close modal and reset form
                        BizAlert.toast("Client added successfully!", "success");
                        this.isClientModalOpen = false;
                        this.newClient = {
                            name: "",
                            phone: "",
                            city: "",
                            state_id: "",
                            registration_type: "unregistered",
                        };

                        // Push to the array so the dropdown updates instantly
                        this.clientsList.push(data.client);

                        // Auto-select the newly created client
                        this.selectCustomer(data.client);
                    } catch (error) {
                        console.error("Fetch Execution Error:", error);
                        BizAlert.toast("Network error. Check console for details.", "error");
                    }
                },
                toggleGuestMode() {
                    this.isGuest = !this.isGuest;
                    if (this.isGuest) {
                        this.formData.customer_id = "";
                        this.formData.customer_name = "";
                        this.clientSearchTerm = "";
                        this.formData.supply_state = this.company_state;

                        // 🌟 Sync State Custom Select
                        window.dispatchEvent(new CustomEvent("set-supply-state", {
                            detail: this.company_state
                        }));
                    }
                    this.calculate();
                },
                // 🌟 NEW: Handle clicking an item in the dropdown
                selectCustomer(client) {
                    this.isGuest = false;

                    this.formData.customer_id = client.id;
                    this.clientSearchTerm = client.name;
                    this.formData.customer_name = client.name;
                    this.formData.customer_gstin = client.gst_number || client.gstin || "";

                    this.formData.gst_treatment = this.formData.customer_gstin ? "registered" : "unregistered";

                    // 🌟 Sync GST Treatment Custom Select
                    window.dispatchEvent(new CustomEvent("set-gst-treatment", {
                        detail: this.formData.gst_treatment
                    }));

                    const resolvedStateName = (client.state_name_only || client.state?.name || "").trim();
                    this.isClientDropdownOpen = false;
                    this.$nextTick(() => {
                        if (resolvedStateName) {
                            this.formData.supply_state = resolvedStateName;
                        } else {
                            this.formData.supply_state = this.company_state;
                        }

                        // 🌟 Sync Supply State Custom Select
                        window.dispatchEvent(new CustomEvent("set-supply-state", {
                            detail: this.formData.supply_state
                        }));
                        this.calculate();
                    });
                },

                async fetchGlobalSkus() {
                    let warehouseId = this.formData.warehouse_id;

                    if (!warehouseId) {
                        BizAlert.toast("Please select a warehouse first", "error");
                        this.globalSearch = "";
                        return;
                    }

                    if (this.globalSearch.length < 2) return;

                    this.isSearching = true; // 🌟 Trigger loading state

                    try {
                        // Stock filtering is decided server-side by the endpoint's
                        // context — it is no longer a query parameter the client
                        // could omit to pull in out-of-stock items.
                        let response = await fetch(
                            `{{ route('admin.api.invoices.search-skus') }}?term=${encodeURIComponent(this.globalSearch)}&warehouse_id=${warehouseId}`,
                        );

                        if (!response.ok) {
                            // The endpoint returns { error: "..." } with a message
                            // written for the user — a 422 says exactly which
                            // warehouse rule failed. Showing "check console"
                            // instead meant a tenant could never act on it.
                            let payload = await response.text();
                            console.error("Backend Error:", payload);

                            let message = "Could not search items. Please try again.";

                            try {
                                let parsed = JSON.parse(payload);
                                if (parsed.error) message = parsed.error;
                            } catch (_) {
                                // A 500 returns an HTML error page — keep the generic text.
                            }

                            BizAlert.toast(message, "error");
                            return;
                        }

                        this.globalSearchResults = await response.json();
                    } catch (error) {
                        console.error("Network or Parsing Error:", error);
                    } finally {
                        this.isSearching = false; // 🌟 Turn off loading state
                    }
                },

                addSkuToTable(result) {
                    // Check if SKU already exists in the items array
                    if (this.items.some((item) => item.product_sku_id === result.product_sku_id)) {
                        BizAlert.toast("This product is already added!", "error");
                        this.globalSearch = "";
                        this.showResults = false;
                        return;
                    }

                    this.items.push({
                        key: this.itemCounter++,
                        challan_item_id: null,
                        batch_id: null,
                        batch_number: "",
                        product_id: result.product_id,
                        product_sku_id: result.product_sku_id,
                        unit_id: result.unit_id,
                        product_name: result.product_name,
                        display_name: result.display_name, // 🌟 Map the new computed name
                        sku_code: result.sku_code,
                        hsn_code: result.hsn_code || "",
                        quantity: 1,
                        unit_price: parseFloat(result.price) || 0,
                        // Safely parse the dynamic tax. Use ?? so 0% tax doesn't trigger a fallback.
                        // We check result.order_tax (based on your schema) and fallback to result.tax_percent if your API aliases it.
                        tax_percent: parseFloat(result.order_tax ?? result.tax_percent ?? 0),
                        tax_type: result.tax_type || "exclusive",
                        discount_type: "fixed",
                        discount_value: 0,
                        line_total: 0,
                    });
                    this.globalSearch = "";
                    this.calculate();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculate();
                },

                handleFormSubmit(event) {
                    // 1. Check Customer Selection or Guest Name
                    if (!this.isGuest && !this.formData.customer_id) {
                        event.preventDefault();
                        BizAlert.toast('Please select a customer.', 'error');
                        const searchInput = document.querySelector('input[placeholder*="Search customer"]');
                        if (searchInput) searchInput.focus();
                        return false;
                    }

                    if (this.isGuest && !this.formData.customer_name.trim()) {
                        event.preventDefault();
                        BizAlert.toast('Please enter guest customer name.', 'error');
                        const guestInput = document.querySelector('input[name="customer_name"]');
                        if (guestInput) guestInput.focus();
                        return false;
                    }

                    // 2. Validate at least one item exists
                    if (!this.items || this.items.length === 0) {
                        event.preventDefault();
                        BizAlert.toast('You must add at least one product to create an invoice.', 'error');
                        const skuInput = document.querySelector('input[placeholder*="Type product name"]');
                        if (skuInput) skuInput.focus();
                        return false;
                    }

                    // 3. Validate Line Items Quantity & Price
                    for (let i = 0; i < this.items.length; i++) {
                        const item = this.items[i];
                        const qty = parseFloat(item.quantity) || 0;
                        const price = parseFloat(item.unit_price) || 0;

                        if (qty <= 0) {
                            event.preventDefault();
                            BizAlert.toast(`Invalid quantity for item "${item.display_name}".`, 'error');
                            return false;
                        }

                        if (price < 0) {
                            event.preventDefault();
                            BizAlert.toast(`Unit price cannot be negative for item "${item.display_name}".`, 'error');
                            return false;
                        }
                    }

                    // 4. Validate Payment Mode if Amount Paid > 0
                    if (Number(this.global.amount_paid) > 0 && !this.formData.payment_method_id) {
                        event.preventDefault();
                        BizAlert.toast('Please select a payment mode for the amount received.', 'error');
                        if (typeof window.focusCustomSelect === 'function') {
                            window.focusCustomSelect('payment_method_id');
                        }
                        return false;
                    }

                    // All checks passed -> Show loading indicator
                    BizAlert.loading('Generating Invoice...');
                },

                openItemModal(index) {
                    this.activeEditIndex = index;
                    this.activeEditData = JSON.parse(JSON.stringify(this.items[index]));
                    this.isItemModalOpen = true;

                    // The custom selects hold their own value and cannot read
                    // activeEditData, so the line's current values are pushed in.
                    // Strings throughout: the component compares option keys,
                    // which arrive from PHP as strings, and unit_id is numeric.
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent("set-item-tax-type", {
                            detail: String(this.activeEditData.tax_type ?? "exclusive"),
                        }));
                        window.dispatchEvent(new CustomEvent("set-item-discount-type", {
                            detail: String(this.activeEditData.discount_type ?? "fixed"),
                        }));
                        window.dispatchEvent(new CustomEvent("set-item-unit", {
                            detail: String(this.activeEditData.unit_id ?? ""),
                        }));
                    });
                },

                saveItemModal() {
                    // 🌟 Ensure inputs are stored as numbers
                    this.activeEditData.tax_percent = parseFloat(this.activeEditData.tax_percent) || 0;
                    this.activeEditData.discount_value = parseFloat(this.activeEditData.discount_value) || 0;

                    // Kept as a string — HSN codes carry leading zeros that a
                    // numeric cast would silently drop.
                    this.activeEditData.hsn_code = String(this.activeEditData.hsn_code ?? '').trim();

                    this.items[this.activeEditIndex] = Object.assign(this.items[this.activeEditIndex], this.activeEditData);
                    this.calculate();
                    this.closeItemModal();
                },

                closeItemModal() {
                    this.isItemModalOpen = false;
                },

                calculate() {
                    const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;

                    let subtotalAcc = 0; // sum of item taxable values (after per-item discount)
                    let taxAcc = 0; // sum of item GST amounts

                    // ── 1. Per-item tax (unchanged — this part is already correct) ──
                    this.items.forEach((item) => {
                        let qty = parseFloat(item.quantity) || 0;
                        let price = parseFloat(item.unit_price) || 0;
                        let taxPct = parseFloat(item.tax_percent) || 0;
                        let discVal = parseFloat(item.discount_value) || 0;
                        let baseVal = qty * price;

                        // Item discount first
                        let discountAmount = 0;
                        if (item.discount_type === "percentage" || item.discount_type === "percent") {
                            discountAmount = baseVal * (discVal / 100);
                        } else {
                            discountAmount = discVal;
                        }
                        let afterDiscount = Math.max(0, baseVal - discountAmount);

                        // Item GST
                        let taxable = 0,
                            tax = 0;
                        if (item.tax_type === "inclusive") {
                            taxable = afterDiscount / (1 + taxPct / 100);
                            tax = afterDiscount - taxable;
                        } else {
                            taxable = afterDiscount;
                            tax = taxable * (taxPct / 100);
                        }

                        item.line_total = round2(taxable + tax);
                        subtotalAcc += taxable;
                        taxAcc += tax;
                    });

                    this.totals.subtotal = subtotalAcc;
                    this.totals.products_total = subtotalAcc;

                    // ── 2. Detect inter-state for IGST vs CGST+SGST split ─────────
                    const isInterState =
                        (this.formData.supply_state || "").trim().toLowerCase() !==
                        (this.company_state || "").trim().toLowerCase();
                    this.totals.isInterState = isInterState;

                    // Rate label logic (for the UI display labels)
                    let uniqueRates = [...new Set(this.items.map((i) => parseFloat(i.tax_percent) || 0))];
                    let isMixed = uniqueRates.length > 1;
                    let baseRate = uniqueRates.length === 1 ? uniqueRates[0] : 0;

                    // ── 3. Global discount — applied to ITEM taxable base ONLY ─────
                    let globalDiscVal = parseFloat(this.global.discount_value) || 0;
                    let globalDiscountAmount = 0;
                    if (this.global.discount_type === "percent" || this.global.discount_type === "percentage") {
                        globalDiscountAmount = subtotalAcc * (globalDiscVal / 100);
                    } else {
                        globalDiscountAmount = globalDiscVal;
                    }
                    let itemsAfterDiscount = Math.max(0, subtotalAcc - globalDiscountAmount);

                    // ── 4. Proportionally reduce item GST by the discount ratio ────
                    // Same % discount → same % tax reduction. Correct for single-rate
                    // invoices; approximately correct for mixed-rate (full per-HSN fix
                    // is the next planned improvement).
                    let discountRatio = subtotalAcc > 0 ? itemsAfterDiscount / subtotalAcc : 0;
                    let itemTaxAfterDiscount = round2(taxAcc * discountRatio);

                    // ── 5. Shipping GST — independent at its own configured rate ───
                    let shipping = parseFloat(this.global.shipping) || 0;
                    let shippingTaxRate = parseFloat(this.global.shipping_tax_rate) || 0;
                    let shippingTax = round2((shipping * shippingTaxRate) / 100);
                    this.global.shipping_tax = shippingTax; // reactive — shown in UI

                    // ── 6. Reassemble totals ───────────────────────────────────────
                    let totalTax = round2(itemTaxAfterDiscount + shippingTax);

                    if (isInterState) {
                        this.totals.igst = totalTax;
                        this.totals.cgst = 0;
                        this.totals.sgst = 0;
                        this.totals.igst_rate = isMixed ? "Mixed" : baseRate;
                        this.totals.cgst_rate = 0;
                        this.totals.sgst_rate = 0;
                    } else {
                        this.totals.igst = 0;
                        this.totals.cgst = round2(totalTax / 2);
                        this.totals.sgst = round2(totalTax / 2);
                        this.totals.igst_rate = 0;
                        this.totals.cgst_rate = isMixed ? "Mixed" : round2(baseRate / 2);
                        this.totals.sgst_rate = isMixed ? "Mixed" : round2(baseRate / 2);
                    }

                    this.totals.tax = totalTax;
                    this.totals.taxable_amount = itemsAfterDiscount; // items only, no shipping

                    // ── 7. Grand total: item_taxable + total_tax + shipping_net ────
                    let grandBeforeRound = itemsAfterDiscount + totalTax + shipping;
                    this.totals.grand_total = Math.round(grandBeforeRound);
                    this.global.round_off = (this.totals.grand_total - grandBeforeRound).toFixed(2);

                    // ── 8. Reconciliation (change / due) ──────────────────────────
                    let paid = parseFloat(this.global.amount_paid) || 0;
                    let diff = paid - this.totals.grand_total;
                    this.balance.is_change = diff > 0;
                    this.balance.value = Math.abs(diff);
                },

                formatCurrency(val) {
                    return (
                        "₹" +
                        parseFloat(val).toLocaleString("en-IN", {
                            minimumFractionDigits: 2,
                        })
                    );
                },
            };
        }
    </script>
@endpush
