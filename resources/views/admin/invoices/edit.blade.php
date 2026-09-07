@extends ('layouts.admin')

@section('title', 'Edit Sales Invoice')

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Edit Invoice</h1>
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

        $unitOptions = [];
        foreach ($units ?? [] as $unit) {
            $unitOptions[$unit->id] = $unit->name;
        }

        $discountTypeOptions = ['fixed' => 'Flat (₹)', 'percentage' => 'Percent (%)'];

        $shippingGstOptions = [
            '0' => '0% — Exempt',
            '5' => '5%',
            '12' => '12%',
            '18' => '18% (Courier)',
            '28' => '28%',
        ];

        $taxTypeOptions = [
            'exclusive' => 'Exclusive (Price + Tax)',
            'inclusive' => 'Inclusive (Price includes Tax)',
        ];

        $itemDiscountTypeOptions = ['percentage' => 'Percentage (%)', 'fixed' => 'Fixed Amount (₹)'];

        /* Older rows stored 'percent'; the option keys use 'percentage'. The
           Alpine factory already normalises this — the select has to match, or
           a legacy invoice opens showing the placeholder instead of its type. */
        $rawDiscountType = old('discount_type', $invoice->discount_type ?? 'fixed');
        $selectedDiscountType = $rawDiscountType === 'percent' ? 'percentage' : $rawDiscountType;

        /* shipping_tax_rate is a decimal column, so it reads back as "18.00"
           while the option keys are "18". Cast through int so the saved rate
           actually matches an option. */
        $selectedShippingRate = (string) (int) old('shipping_tax_rate', $invoice->shipping_tax_rate ?? 0);
    @endphp

    {{-- 🌟 Injected $invoice into Alpine --}}
    <div class="pb-20" x-data="invoiceForm(@js($units ?? []), @js($companyState), @js($invoice), @js($clients ?? []))">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-[1.5rem] font-bold tracking-widest text-gray-500 uppercase">
                    Edit Invoice: {{ $invoice->invoice_number }}
                </h1>
            </div>
        </div>

        @if ($invoice->status === 'draft')
            <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
                <i data-lucide="file-edit" class="mt-0.5 h-5 w-5 flex-shrink-0"></i>
                <div class="text-sm">
                    <div class="font-bold">This invoice is a draft</div>
                    <div class="text-xs">
                        Stock has not been deducted yet. Use <strong>Save &amp; Confirm</strong> to finalize, or keep
                        editing as a draft.
                    </div>
                </div>
            </div>
        @endif

        {{-- Validation Error Display --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 shadow-sm">
                {{-- <div class="font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    Please fix the following errors:
                </div> --}}
                <ul class="list-inside list-disc space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    if (typeof Swal !== "undefined") Swal.close();
                });
            </script>
        @endif

        {{-- 🌟 Updated to PUT method for updating --}}
        <form id="mainInvoiceForm" action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST"
            @submit="
                if (Number(global.amount_paid) > 0 && ! formData.payment_method_id) {
                    $event.preventDefault();
                    BizAlert.toast('Please select a payment mode for the amount received.', 'error');
                    window.focusCustomSelect('payment_method_id');
                    return;
                }
                BizAlert.loading('Updating Invoice...');
            ">
            @csrf
            @method ('PUT')

            <input type="hidden" name="source" value="{{ $invoice->source }}" />

            {{-- 1. TRANSACTION HEADER --}}
            <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">
                    {{-- Customer Selection (Spans 2 columns) --}}
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Customer <span
                                class="text-red-500">*</span></label>
                        <div class="flex items-start gap-2">
                            <div class="relative flex-1">
                                {{-- Searchable Input --}}
                                <div class="relative flex w-full gap-1" @click.away="isClientDropdownOpen = false">
                                    <div class="relative flex-1">
                                        <input type="text" x-model="clientSearchTerm"
                                            @focus="isClientDropdownOpen = true"
                                            @input="
                                                isClientDropdownOpen = true;
                                                formData.customer_id = '';
                                            "
                                            placeholder="Search customer by name or phone..."
                                            class="focus:border-brand-500 w-full rounded border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-700 outline-none" />
                                        <i data-lucide="chevron-down"
                                            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                    </div>

                                    {{-- Hidden Input for Laravel Form Submission --}}
                                    <input type="hidden" name="customer_id" x-model="formData.customer_id" />

                                    {{-- Floating Dropdown List --}}
                                    <ul x-show="isClientDropdownOpen" x-cloak x-transition
                                        class="custom-scrollbar absolute top-full left-0 z-[60] mt-1 max-h-60 w-full overflow-y-auto overscroll-contain rounded-lg border border-gray-200 bg-white shadow-2xl">
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

                                {{-- GSTIN Display --}}
                                <div x-show="formData.customer_gstin" x-cloak
                                    class="mt-1.5 pl-1 text-[11px] font-bold text-gray-500">
                                    GSTIN: <span class="text-blue-600" x-text="formData.customer_gstin"></span>
                                </div>
                            </div>

                            {{-- Guest Toggle Button --}}
                            <button type="button" @click="toggleGuestMode()"
                                :class="isGuest ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600'"
                                class="shrink-0 rounded border border-transparent px-4 py-4 text-xs font-bold tracking-widest uppercase transition-colors">
                                <span x-text="isGuest ? 'Guest Active' : 'Guest'"></span>
                            </button>
                        </div>

                        {{-- Guest Name Input --}}
                        <div x-show="isGuest" x-cloak class="mt-3">
                            <input type="text" name="customer_name" x-model="formData.customer_name"
                                placeholder="Enter Guest Name..."
                                class="focus:border-brand-500 w-full rounded border border-gray-300 px-3 py-2 text-sm outline-none" />
                        </div>
                    </div>

                    {{-- Remaining Fields --}}
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
                            ]" :selected="old('gst_treatment', $invoice->gst_treatment)"
                                @set-gst-treatment.window="value = $event.detail" />
                        </div>
                    </div>

                    <input type="hidden" name="store_id" value="{{ $invoice->store_id }}" />

                    <div>
                        <label class="mb-2 block text-xs font-bold tracking-wider text-gray-600 uppercase">Stock
                            Warehouse</label>
                        <div @change="formData.warehouse_id = $event.target.value">
                            <x-custom-select name="warehouse_id" placeholder="Select Warehouse" :options="$warehouseOptions"
                                :selected="old('warehouse_id', $invoice->warehouse_id)" required />
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
                                class="font-bold text-[#108c2a]" :options="$stateOptions" :selected="old('supply_state', $invoice->supply_state)"
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

                    <div class="relative w-full sm:max-w-md md:max-w-lg lg:max-w-2xl">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" x-model="globalSearch" @input.debounce.300ms="fetchGlobalSkus()"
                            @focus="showResults = true" @click.away="showResults = false"
                            placeholder="Type product name or sku..."
                            class="focus:border-brand-500 w-full rounded border border-gray-300 bg-white py-2.5 pr-4 pl-9 text-sm shadow-sm outline-none" />

                        <ul x-show="showResults && globalSearch.length > 1" x-cloak
                            class="absolute top-full left-0 z-[60] mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-2xl">
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
                                            x-text="result.price ? '₹' + parseFloat(result.price).toFixed(2) : '₹0.00'">
                                        </div>
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
                                <th class="min-w-[250px] px-5 py-4">PRODUCT</th>
                                <th class="w-[160px] min-w-[140px] px-4 py-4">BASE UNIT PRICE</th>
                                <th class="w-[180px] min-w-[140px] px-4 py-4 text-center">QTY</th>
                                <th class="w-[160px] min-w-[120px] px-5 py-4 text-right">TOTAL (INC TAX)</th>
                                <th class="w-[60px] px-4 py-4 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.key">
                                <tr class="transition-colors hover:bg-gray-50/50">
                                    <td class="w-full px-5 py-3 md:w-auto">
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
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] font-medium text-gray-500">Code:</span>
                                                <span
                                                    class="rounded border border-slate-200 bg-[#f1f5f9] px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wide text-[#475569]"
                                                    x-text="item.sku_code"></span>
                                            </div>

                                            {{-- HSN surfaced here too, so a missing code is obvious
                                                 before saving rather than at GST filing time. --}}
                                            <span class="rounded border px-1.5 py-0.5 text-[10px] font-bold"
                                                :class="item.hsn_code ?
                                                    'border-gray-200 bg-gray-50 text-gray-600' :
                                                    'border-red-200 bg-red-50 text-red-600'"
                                                x-text="item.hsn_code ? 'HSN ' + item.hsn_code : 'HSN missing'"></span>

                                            <span
                                                class="rounded border border-blue-100 bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700"
                                                x-text="
                                                    'GST ' + item.tax_percent + '% ' +
                                                    (item.tax_type === 'inclusive' ? 'incl.' : 'excl.')
                                                "></span>

                                            <span x-show="item.discount_value > 0"
                                                class="rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
                                                Disc:
                                                <span
                                                    x-text="
                                                        item.discount_type === 'percentage'
                                                            ? item.discount_value + '%'
                                                            : '₹' + item.discount_value
                                                    "></span>
                                            </span>

                                            {{-- Hidden Inputs --}}
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
                                            {{-- Without this the code is loaded into Alpine, shown in
                                                 the UI, and then silently dropped on submit. --}}
                                            <input type="hidden" :name="'items[' + index + '][hsn_code]'"
                                                :value="item.hsn_code" />
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-middle">
                                        <div class="relative w-full min-w-[100px]">
                                            <span
                                                class="absolute top-1/2 left-3 -translate-y-1/2 text-sm font-bold text-gray-400">₹</span>
                                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                                x-model="item.unit_price"
                                                @input="item.unit_price = window.sanitizeInteger($event); calculate();"
                                                @blur="item.unit_price = window.commitInteger(item.unit_price, 0); calculate();"
                                                class="focus:border-brand-500 h-10 w-full rounded border border-gray-300 px-2 pl-7 text-sm font-bold text-gray-700 shadow-sm transition-all outline-none md:h-9" />
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-middle">
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
                                                class="focus:border-brand-500 h-10 w-16 border-x-0 border-y border-gray-300 p-0 text-center text-sm font-bold text-gray-700 shadow-inner outline-none focus:ring-0 md:h-9" />
                                            <button type="button"
                                                @click="
                                                    item.quantity = parseFloat(item.quantity || 0) + 1;
                                                    calculate();
                                                "
                                                class="flex h-10 w-10 items-center justify-center rounded-r border border-gray-300 bg-gray-50 text-gray-600 transition-colors hover:bg-gray-100 active:bg-gray-200 md:h-9 md:w-8">
                                                +
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-5 py-3 text-right max-w-[140px]">
                                        <span class="text-[14px] font-black text-gray-800 block truncate"
                                            :title="formatCurrency(item.line_total)"
                                            x-text="formatCurrency(item.line_total)"></span>
                                    </td>

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
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <span class="text-[11px] font-medium text-gray-500">Code:</span>
                                        <div class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wide text-gray-600"
                                            x-text="item.sku_code"></div>
                                    </div>
                                    <span x-show="item.discount_value > 0"
                                        class="mt-1.5 inline-block rounded border border-amber-200 bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
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
                                <span :class="item.hsn_code ? '' : 'font-bold text-red-600'">
                                    HSN:
                                    <span class="font-mono font-bold"
                                        :class="item.hsn_code ? 'text-gray-700' : 'text-red-600'"
                                        x-text="item.hsn_code || 'missing'"></span>
                                </span>
                                <span>Tax: <span class="font-bold text-gray-700" x-text="item.tax_percent + '%'"></span>
                                    <span class="uppercase" x-text="item.tax_type.substring(0, 3)"></span></span>
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
                                            class="focus:border-brand-500 h-10 w-full rounded border border-gray-300 px-2 pl-7 text-sm font-bold text-gray-700 shadow-sm transition-all outline-none"
                                            placeholder="0" />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-center text-[10px] font-bold text-gray-500 uppercase">Qty</label>
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
                                            class="focus:border-brand-500 h-10 w-14 border-x-0 border-y border-gray-300 p-0 text-center text-sm font-bold text-gray-700 shadow-inner outline-none focus:ring-0"
                                            placeholder="0" />
                                        <button type="button"
                                            @click="
                                                item.quantity = parseFloat(item.quantity || 0) + 1;
                                                calculate();
                                            "
                                            class="flex h-10 w-9 items-center justify-center rounded-r border border-gray-300 bg-gray-50 text-gray-600 hover:bg-gray-100 active:bg-gray-200">
                                            +
                                        </button>
                                    </div>
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
            <div class="mb-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:col-span-1">
                    <h3 class="pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Notes & Terms
                    </h3>
                    <textarea name="notes" rows="2" placeholder="Internal notes..."
                        class="focus:border-brand-500 w-full resize-none rounded border border-gray-300 px-3 py-2 text-sm outline-none">{{ old('notes', $invoice->notes) }}</textarea>
                    <textarea name="terms_conditions" rows="2" placeholder="Customer terms..."
                        class="focus:border-brand-500 w-full resize-none rounded border border-gray-300 px-3 py-2 text-sm outline-none">{{ old('terms_conditions', $invoice->terms_conditions) }}</textarea>
                </div>

                {{-- PAYMENT & RECONCILIATION --}}
                <div class="rounded-lg border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Payment Receipt
                    </h3>
                    <div class="space-y-4">
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

                        {{-- 🌟 Bind x-model so it auto-selects the saved payment method --}}
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

                {{-- CLEAN FINANCIAL SUMMARY --}}
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3
                        class="mb-4 border-b border-gray-200 pb-3 text-xs font-bold tracking-wider text-gray-800 uppercase">
                        Financials
                    </h3>

                    {{-- Global Discount --}}
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 pt-2 xl:flex-nowrap">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-600">Discount:</span>
                            {{-- The wrapper catches the change event bubbling out of
                                 the component's hidden native select. That is the only
                                 way the value reaches this Alpine scope — the component
                                 holds its own state. --}}
                            <div class="w-36"
                                @change="
                                    global.discount_type = $event.target.value;

                                    if (global.discount_type === 'percentage' && Number(global.discount_value) > 100) {
                                        global.discount_value = 100;
                                    }
                                    calculate();
                                ">
                                <x-custom-select name="discount_type" :options="$discountTypeOptions" :selected="$selectedDiscountType" />
                            </div>
                        </div>

                        <input type="text" inputmode="numeric" pattern="[0-9]*" name="discount_value"
                            x-model="global.discount_value"
                            @input="global.discount_value = window.sanitizeInteger($event, {
                                max: global.discount_type === 'percentage' ? 100 : null
                            }); calculate()"
                            @blur="global.discount_value = window.commitInteger(global.discount_value, 0); calculate()"
                            class="ml-auto w-24 rounded border border-gray-300 px-3 py-1 text-right font-bold text-red-500 outline-none focus:border-[#108c2a] sm:w-32"
                            placeholder="0" />
                    </div>

                    {{-- Shipping + GST --}}
                    <div class="space-y-3 border-b border-gray-100 pb-4">
                        {{-- Row 1: Shipping Amount --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 xl:flex-nowrap">
                            <span class="font-semibold text-gray-600">Shipping (₹):</span>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" name="shipping_charge"
                                x-model="global.shipping"
                                @input="
                                    global.shipping = window.sanitizeInteger($event);
                                    calculate();
                                "
                                @blur="global.shipping = window.commitInteger(global.shipping, 0); calculate();"
                                class="ml-auto w-24 rounded border border-gray-300 px-3 py-1 text-right font-bold text-gray-800 outline-none focus:border-[#108c2a] sm:w-32"
                                placeholder="0" />
                        </div>
                        {{-- Row 2: Shipping GST Rate --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 xl:flex-nowrap">
                            <div class="flex shrink-0 items-center gap-1.5">
                                <span class="text-sm font-semibold text-gray-600">Shipping GST:</span>
                                <div class="w-44"
                                    @change="
                                        global.shipping_tax_rate = $event.target.value;
                                        calculate();
                                    ">
                                    <x-custom-select name="shipping_tax_rate" :options="$shippingGstOptions" :selected="$selectedShippingRate" />
                                </div>
                            </div>
                            <div class="ml-auto text-right">
                                <span x-show="global.shipping_tax > 0" class="text-sm font-bold text-gray-700"
                                    x-text="'₹ ' + parseFloat(global.shipping_tax).toFixed(2)">
                                </span>
                                <span x-show="global.shipping_tax == 0"
                                    class="rounded bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-400">
                                    Exempt
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">Subtotal (Taxable):</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
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
                            <span class="font-semibold">Total Tax (GST):</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.tax)"></span>
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
                <button type="submit" name="status" value="draft"
                    class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50">
                    <i data-lucide="save" class="h-4 w-4"></i> Save as Draft
                </button>
                <button type="submit" name="status" value="confirmed"
                    class="flex items-center justify-center gap-2 rounded-lg bg-[#108c2a] px-8 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0c6b1f] active:scale-95">
                    <i data-lucide="check-circle" class="h-4 w-4"></i> Save &amp; Confirm
                </button>
            </div>
        </form>

        {{-- ITEM SETTINGS MODAL --}}
        <div x-show="isItemModalOpen" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-end bg-black/50 backdrop-blur-sm transition-opacity">
            <div class="flex h-full w-full max-w-md flex-col bg-white shadow-2xl" x-show="isItemModalOpen" x-transition
                @click.away="closeItemModal()">
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
                        {{-- Modal fields need two-way sync: the wrapper writes out on
                             change, and openItemModal() pushes the current line's
                             value back in. --}}
                        <div @change="activeEditData.tax_type = $event.target.value">
                            <x-custom-select name="modal_tax_type" :options="$taxTypeOptions" selected="exclusive"
                                @set-item-tax-type.window="value = $event.detail" />
                        </div>
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
                        <label class="mb-1.5 block text-[11px] font-bold tracking-wider text-gray-600 uppercase">GST
                            (%)</label>
                        <input type="number" step="0.01" x-model="activeEditData.tax_percent"
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
                <div class="grid grid-cols-2 gap-3 border-t border-gray-100 bg-white p-4 sm:grid-cols-2 sm:p-5">
                    <!-- Note: I kept the existing classes but added 'sm:p-5' to ensure padding adapts smoothly -->

                    <button type="button" @click="closeItemModal()"
                        class="col-span-1 rounded-lg bg-gray-100 py-2.5 text-sm font-bold text-gray-700">
                        Cancel
                    </button>

                    <button type="button" @click="saveItemModal()"
                        class="col-span-1 rounded-lg bg-[#108c2a] py-2.5 text-sm font-bold text-white">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function invoiceForm(allUnits = [], companyState = "", invoiceData = null, allClients = []) {
            // 🌟 Map old line items from database
            let initialItems = [];
            // 🌟 Grab the first payment record if it exists
            let initialPayment =
                invoiceData && invoiceData.payments && invoiceData.payments.length > 0 ? invoiceData.payments[0] : null;
            if (invoiceData && invoiceData.items) {
                initialItems = invoiceData.items.map((item, index) => ({
                    key: index,
                    product_id: item.product_id,
                    product_sku_id: item.product_sku_id,
                    unit_id: item.unit_id,
                    product_name: item.product_name,
                    display_name: item.display_name || item.product_name, // 🌟 Map computed DB name
                    // 🌟 Read the eager-loaded SKU code
                    sku_code: item.sku?.sku_code || item.sku?.sku || "N/A",
                    hsn_code: item.hsn_code || "", // 🌟 Added HSN for Edit Mode
                    quantity: parseFloat(item.quantity) || 1,
                    unit_price: parseFloat(item.unit_price) || 0,
                    tax_percent: parseFloat(item.tax_percent) || 0,
                    tax_type: item.tax_type || "exclusive",
                    discount_type: item.discount_type || "percentage",
                    discount_value: parseFloat(item.discount_value) || 0,
                    line_total: parseFloat(item.total_amount) || 0,
                }));
            }

            // 🌟 Inject Old Data if Validation Failed (Overrides the DB items above)
            let oldItems = @json (old('items', []));
            if (oldItems && Object.keys(oldItems).length > 0) {
                let itemsArray = Array.isArray(oldItems) ? oldItems : Object.values(oldItems);
                initialItems = itemsArray.map((item, index) => ({
                    key: index,
                    product_id: item.product_id,
                    product_sku_id: item.product_sku_id,
                    unit_id: item.unit_id,
                    product_name: item.product_name || "Restored Item",
                    display_name: item.display_name || item.product_name || "Restored Item", // 🌟 Restore UI
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
            }

            return {
                units: allUnits,
                company_state: companyState,
                items: initialItems,
                itemCounter: initialItems.length,
                globalSearch: "",
                isSearching: false,
                globalSearchResults: [],
                showResults: false,
                clientsList: allClients,
                clientSearchTerm: invoiceData ? invoiceData.customer_name || "" : "",
                isClientDropdownOpen: false,

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

                isGuest: invoiceData ? !invoiceData.customer_id : false,

                formData: {
                    customer_id: @json (old('customer_id')) || (invoiceData ? invoiceData.customer_id || "" : ""),
                    customer_name: @json (old('customer_name')) || (invoiceData ? invoiceData.customer_name || "" : ""),
                    customer_gstin: invoiceData && invoiceData.client ? invoiceData.client.gst_number || "" : "",
                    gst_treatment: @json (old('gst_treatment')) ||
                        (invoiceData ? invoiceData.gst_treatment || "unregistered" : "unregistered"),
                    supply_state_id: @json (old('supply_state') ? null : $invoiceStateId ?? null) ?? "",
                    supply_state: @json (old('supply_state')) || (invoiceData ? invoiceData.supply_state : companyState),
                    payment_method_id: @json (old('payment_method_id')) || (initialPayment ? initialPayment.payment_method_id :
                        ""),
                    invoice_date: @json (old('invoice_date')) ||
                        (invoiceData ? invoiceData.invoice_date.split("T")[0] : new Date().toISOString().split("T")[0]),
                    due_date: @json (old('due_date')) ||
                        (invoiceData && invoiceData.due_date ?
                            invoiceData.due_date.split("T")[0] :
                            new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split("T")[0]),
                    store_id: invoiceData ? invoiceData.store_id : "{{ $invoice->store_id ?? '' }}",
                    warehouse_id: @json (old('warehouse_id')) || (invoiceData ? invoiceData.warehouse_id : ""),
                },

                global: {
                    shipping: parseFloat(@json (old('shipping_charge'))) ||
                        (invoiceData ? parseFloat(invoiceData.shipping_charge) || 0 : 0),
                    shipping_tax_rate: parseFloat(@json (old('shipping_tax_rate'))) ||
                        (invoiceData ? parseFloat(invoiceData.shipping_tax_rate) || 0 : 0),
                    shipping_tax: 0, // auto-calculated on calculate(), never submitted
                    amount_paid: parseFloat(@json (old('amount_paid'))) ||
                        (initialPayment ? parseFloat(initialPayment.amount_received || initialPayment.amount) : 0),
                    discount_type: @json (old('discount_type')) ||
                        (invoiceData && invoiceData.discount_type ?
                            invoiceData.discount_type === "percent" ?
                            "percentage" :
                            invoiceData.discount_type :
                            "fixed"),
                    discount_value: parseFloat(@json (old('discount_value'))) ||
                        (invoiceData ? parseFloat(invoiceData.discount_amount) || 0 : 0),
                    round_off: invoiceData ? invoiceData.round_off : "0.00",
                },
                totals: {
                    subtotal: invoiceData ? parseFloat(invoiceData.subtotal) || 0 : 0,
                    tax: invoiceData ? parseFloat(invoiceData.tax_amount) || 0 : 0,
                    cgst: invoiceData ? parseFloat(invoiceData.cgst_amount) || 0 : 0,
                    sgst: invoiceData ? parseFloat(invoiceData.sgst_amount) || 0 : 0,
                    igst: invoiceData ? parseFloat(invoiceData.igst_amount) || 0 : 0,
                    cgst_rate: 0,
                    sgst_rate: 0,
                    igst_rate: 0,
                    isInterState: false,
                    grand_total: invoiceData ? parseFloat(invoiceData.grand_total) || 0 : 0,
                },
                balance: {
                    value: 0,
                    is_change: false,
                },

                isItemModalOpen: false,
                activeEditIndex: null,
                activeEditData: {},

                init() {
                    if (this.items.length > 0) {
                        this.calculate();
                    }
                },

                toggleGuestMode() {
                    this.isGuest = !this.isGuest;
                    if (this.isGuest) {
                        this.formData.customer_id = "";
                        this.formData.customer_name = "";
                        this.clientSearchTerm = ""; // <-- ADD THIS LINE
                        this.formData.supply_state = this.company_state;

                        // 🌟 Sync State Custom Select
                        window.dispatchEvent(new CustomEvent("set-supply-state", {
                            detail: this.company_state
                        }));
                    }
                    this.calculate();
                },

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

                    this.isSearching = true;

                    try {
                        let response = await fetch(
                            `{{ route('admin.api.invoices.search-skus') }}?term=${encodeURIComponent(this.globalSearch)}&warehouse_id=${warehouseId}`,
                        );

                        if (!response.ok) {
                            let errorData = await response.text();
                            console.error("Backend Error:", errorData);
                            BizAlert.toast("Error searching products. Check console.", "error");
                            return;
                        }

                        this.globalSearchResults = await response.json();
                    } catch (error) {
                        console.error("Network or Parsing Error:", error);
                    } finally {
                        this.isSearching = false;
                    }
                },

                addSkuToTable(result) {
                    this.items.push({
                        key: this.itemCounter++,
                        product_id: result.product_id,
                        product_sku_id: result.product_sku_id,
                        unit_id: result.unit_id,
                        product_name: result.product_name,
                        display_name: result.display_name, // 🌟 Map the new computed variant name
                        sku_code: result.sku_code,
                        hsn_code: result.hsn_code || "",
                        quantity: 1,
                        unit_price: parseFloat(result.price) || 0,
                        tax_percent: parseFloat(result.tax_percent) || 18,
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

                openItemModal(index) {
                    this.activeEditIndex = index;
                    this.activeEditData = JSON.parse(JSON.stringify(this.items[index]));
                    this.isItemModalOpen = true;

                    // The custom selects hold their own value and cannot read
                    // activeEditData, so the line's values are pushed in. Strings
                    // throughout: option keys arrive from PHP as strings, and
                    // unit_id is numeric. Legacy 'percent' is normalised here too.
                    this.$nextTick(() => {
                        let discType = this.activeEditData.discount_type ?? "fixed";
                        if (discType === "percent") discType = "percentage";

                        window.dispatchEvent(new CustomEvent("set-item-tax-type", {
                            detail: String(this.activeEditData.tax_type ?? "exclusive"),
                        }));
                        window.dispatchEvent(new CustomEvent("set-item-discount-type", {
                            detail: String(discType),
                        }));
                        window.dispatchEvent(new CustomEvent("set-item-unit", {
                            detail: String(this.activeEditData.unit_id ?? ""),
                        }));
                    });
                },

                saveItemModal() {
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

                    let subtotalAcc = 0;
                    let taxAcc = 0;

                    // ── 1. Per-item tax (correct, unchanged) ──────────────────────
                    this.items.forEach((item) => {
                        let qty = parseFloat(item.quantity) || 0;
                        let price = parseFloat(item.unit_price) || 0;
                        let taxPct = parseFloat(item.tax_percent) || 0;
                        let discVal = parseFloat(item.discount_value) || 0;
                        let baseVal = qty * price;

                        let discountAmount = 0;
                        if (item.discount_type === "percent" || item.discount_type === "percentage") {
                            discountAmount = baseVal * (discVal / 100);
                        } else {
                            discountAmount = discVal;
                        }
                        let afterDiscount = Math.max(0, baseVal - discountAmount);

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

                    // ── 2. Inter-state detection ──────────────────────────────────
                    const isInterState =
                        (this.formData.supply_state || "").trim().toLowerCase() !==
                        (this.company_state || "").trim().toLowerCase();
                    this.totals.isInterState = isInterState;

                    let uniqueRates = [...new Set(this.items.map((i) => parseFloat(i.tax_percent) || 0))];
                    let isMixed = uniqueRates.length > 1;
                    let baseRate = uniqueRates.length === 1 ? uniqueRates[0] : 0;

                    // ── 3. Global discount on ITEMS ONLY (not shipping) ───────────
                    let globalDiscVal = parseFloat(this.global.discount_value) || 0;
                    let globalDiscountAmount = 0;
                    if (this.global.discount_type === "percent" || this.global.discount_type === "percentage") {
                        globalDiscountAmount = subtotalAcc * (globalDiscVal / 100);
                    } else {
                        globalDiscountAmount = globalDiscVal;
                    }
                    let itemsAfterDiscount = Math.max(0, subtotalAcc - globalDiscountAmount);

                    // ── 4. Proportionally reduce item GST by discount ratio ────────
                    let discountRatio = subtotalAcc > 0 ? itemsAfterDiscount / subtotalAcc : 0;
                    let itemTaxAfterDiscount = round2(taxAcc * discountRatio);

                    // ── 5. Shipping GST — independent at its own rate ─────────────
                    let shipping = parseFloat(this.global.shipping) || 0;
                    let shippingTaxRate = parseFloat(this.global.shipping_tax_rate) || 0;
                    let shippingTax = round2((shipping * shippingTaxRate) / 100);
                    this.global.shipping_tax = shippingTax;

                    // ── 6. Reassemble totals ──────────────────────────────────────
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
                    this.totals.taxable_amount = itemsAfterDiscount;

                    // ── 7. Grand total = items_taxable + total_tax + shipping_net ─
                    let grandBeforeRound = itemsAfterDiscount + totalTax + shipping;
                    this.totals.grand_total = Math.round(grandBeforeRound);
                    this.global.round_off = (this.totals.grand_total - grandBeforeRound).toFixed(2);

                    // ── 8. Payment reconciliation ─────────────────────────────────
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
