@extends('layouts.admin')

@section('title', 'Edit Purchase Return')
@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit Purchase Return</h1>
@endsection
@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    @php
        // String keys — an integer-keyed array makes x-custom-select reset to
        // index 0 on render.
        $warehouseOptions = [];
        foreach ($warehouses as $wh) {
            $warehouseOptions[(string) $wh->id] = $wh->name;
        }

        $returnReasonOptions = [
            'damaged' => 'Damaged',
            'wrong_item' => 'Wrong Item',
            'excess_quantity' => 'Excess Qty',
            'quality_issue' => 'Quality Issue',
            'expired' => 'Expired',
            'other' => 'Other',
        ];
    @endphp
    <div class="pb-10" x-data="purchaseReturnForm(@js($purchaseReturn), @js($purchaseReturn->purchase), @js($units ?? []))">

        @if (session('error'))
            <div
                class="bg-red-50 text-red-700 px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6 flex items-center gap-2">
                <i data-lucide="alert-octagon" class="w-5 h-5"></i> {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="bg-[#fee2e2] text-[#ef4444] px-5 py-4 rounded-xl text-sm font-bold shadow-sm border border-red-100 mb-6">
                <div class="flex items-center gap-2 mb-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> Please fix the
                    following errors:</div>
                <ul class="list-disc list-inside pl-7 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-[1.5rem] font-bold text-[#212538] tracking-tight mb-1">Edit Purchase Return</h1>
                <p class="text-[13px] text-gray-500 font-medium">Update return quantities and reasons for PO: <span
                        class="font-bold text-gray-700">{{ $purchaseReturn->purchase->purchase_number }}</span></p>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200">
                <div class="font-bold mb-2">Please fix the following errors:</div>
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($purchaseReturn->status === 'returned')
            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg mb-6 border border-yellow-200 font-bold text-sm">
                <i data-lucide="alert-triangle" class="inline-block w-4 h-4 mr-1"></i>
                Warning: This return has already been processed. Modifications should be handled carefully.
            </div>
        @endif

        <form action="{{ route('admin.purchase-returns.update', $purchaseReturn->id) }}" method="POST"
            @submit="BizAlert.loading('Updating Return...')">
            @csrf
            @method('PUT')

            <input type="hidden" name="purchase_id" x-model="header.purchase_id">
            <input type="hidden" name="supplier_id" x-model="header.supplier_id">
            <input type="hidden" name="store_id" x-model="header.store_id">
            <input type="hidden" name="tax_type" x-model="header.tax_type">

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">

                    <div class="md:col-span-2 relative" @click.away="showPoResults = false">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Original Purchase
                            Order <span class="text-red-500">*</span></label>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="search" class="w-4 h-4 text-gray-400" x-show="!isSearchingPo"></i>
                                <svg x-show="isSearchingPo" class="animate-spin h-4 w-4 text-[#108c2a]"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>

                            <input type="text" x-model="poSearchTerm" @input.debounce.300ms="searchPOs()"
                                @focus="showPoResults = true; if(poSearchResults.length === 0) searchPOs()"
                                placeholder="Search PO Number or Supplier Name..."
                                class="w-full border border-gray-300 rounded pl-9 pr-4 py-2.5 text-sm focus:border-brand-500 outline-none transition-colors disabled:bg-gray-50 disabled:font-bold disabled:text-gray-700"
                                :disabled="header.purchase_id !== ''">

                            <button type="button" x-show="header.purchase_id !== ''" @click="clearPO()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <ul x-show="showPoResults" x-cloak
                            class="absolute z-[60] w-full bg-white border border-gray-200 rounded-lg shadow-2xl mt-1 max-h-60 overflow-y-auto top-full left-0">

                            <li x-show="!isSearchingPo && poSearchTerm.length === 0 && poSearchResults.length > 0"
                                class="px-4 py-2 bg-gray-50 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                Recent Purchase Orders
                            </li>

                            <li x-show="!isSearchingPo && poSearchResults.length === 0" class="px-4 py-4 text-center">
                                <span class="block text-sm font-bold text-gray-700">No Purchase Orders found</span>
                            </li>

                            <template x-for="po in poSearchResults" :key="po.id">
                                <li @click="selectPO(po)"
                                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors flex justify-between items-center">
                                    <div>
                                        <div class="text-[13px] font-bold text-[#108c2a]" x-text="po.purchase_number"></div>
                                        <div class="text-[11px] text-gray-500 font-medium"
                                            x-text="po.supplier?.name || 'Unknown Supplier'"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[12px] font-bold text-gray-700" x-text="'₹' + po.total_amount">
                                        </div>
                                        <div class="text-[10px] text-gray-400"
                                            x-text="new Date(po.purchase_date).toLocaleDateString('en-GB')"></div>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return Date <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="return_date"
                            value="{{ old('return_date', $purchaseReturn->return_date->format('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Status <span
                                class="text-red-500">*</span></label>
                        <x-custom-select name="status" :options="[
                            'draft' => 'Draft',
                            'returned' => 'Returned (Deducts Stock)',
                        ]" :selected="old('status', $purchaseReturn->status)" required />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return From
                            Warehouse <span class="text-red-500">*</span></label>
                        {{-- Two-way sync: the wrapper writes out on change, and
                             loadPurchaseData() pushes the PO's warehouse back in.
                             The component keeps its own value, so without that
                             push it would still read "Select warehouse" while
                             header.warehouse_id already held the right id. --}}
                        <div @change="header.warehouse_id = $event.target.value">
                            <x-custom-select name="warehouse_id" :options="$warehouseOptions" placeholder="Select warehouse"
                                :selected="old('warehouse_id', (string) $purchaseReturn->warehouse_id)" @set-return-warehouse.window="value = $event.detail" />
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Defaults to the purchase warehouse. Change it if the
                            stock was transferred to another warehouse.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col" x-show="items.length > 0"
                x-cloak>
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Items to Return</h2>
                    <span class="text-xs font-medium text-gray-500">Only check the items you are returning.</span>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="hidden md:table-header-group bg-gray-50 border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-4 w-[50px] text-center">Inc?</th>
                                <th class="px-5 py-4 min-w-[250px]">PRODUCT</th>
                                <th class="px-4 py-4 w-[120px] text-right">UNIT COST</th>
                                <th class="px-4 py-4 w-[100px] text-center">MAX QTY</th>
                                <th class="px-4 py-4 w-[140px]">RETURN QTY</th>
                                <th class="px-4 py-4 w-[180px]">REASON</th>
                                <th class="px-5 py-4 w-[140px] text-right">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.purchase_item_id">
                                <tr class="hover:bg-gray-50/50 transition-colors flex flex-col md:table-row border-b border-gray-200 md:border-none p-4 md:p-0 relative"
                                    :class="item.is_included ? 'bg-blue-50/20' : 'opacity-60'">

                                    <td
                                        class="px-4 md:py-3 py-2 flex items-center justify-between md:table-cell absolute top-4 right-4 md:static">
                                        <span class="md:hidden text-xs font-bold text-gray-500 uppercase">Include</span>
                                        <input type="checkbox" x-model="item.is_included" @change="calculate()"
                                            class="w-5 h-5 md:w-4 md:h-4 text-[#108c2a] rounded border-gray-300 focus:ring-[#108c2a]">
                                    </td>

                                    <td class="px-5 md:py-3 py-2 flex flex-col md:table-cell pr-16 md:pr-5">
                                        <div class="text-[13px] font-bold text-gray-800" x-text="item.product_name"></div>
                                        <div class="text-[10px] text-gray-500 mt-0.5 font-mono" x-text="item.sku_code">
                                        </div>

                                        <template x-if="item.is_included">
                                            <div>
                                                <template x-if="item.id">
                                                    <input type="hidden" :name="'items[' + index + '][id]'"
                                                        :value="item.id">
                                                </template>

                                                <input type="hidden" :name="'items[' + index + '][purchase_item_id]'"
                                                    :value="item.purchase_item_id">
                                                <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                    :value="item.product_id">
                                                <input type="hidden" :name="'items[' + index + '][product_sku_id]'"
                                                    :value="item.product_sku_id">
                                                <input type="hidden" :name="'items[' + index + '][unit_id]'"
                                                    :value="item.unit_id">
                                                <input type="hidden" :name="'items[' + index + '][unit_cost]'"
                                                    :value="item.unit_cost">
                                                <input type="hidden" :name="'items[' + index + '][tax_percent]'"
                                                    :value="item.tax_percent">
                                            </div>
                                        </template>
                                    </td>

                                    <td
                                        class="px-4 md:py-3 py-2 flex items-center justify-between md:table-cell text-left md:text-right text-[13px] font-semibold text-gray-600">
                                        <span class="md:hidden text-xs font-bold text-gray-500 uppercase">Unit Cost</span>
                                        <div>₹<span x-text="formatCurrency(item.unit_cost)"></span></div>
                                    </td>

                                    <td
                                        class="px-4 md:py-3 py-2 flex items-center justify-between md:table-cell text-left md:text-center text-[13px] font-bold text-gray-800">
                                        <span class="md:hidden text-xs font-bold text-gray-500 uppercase">Max Qty</span>
                                        <span x-text="item.max_qty"></span>
                                    </td>

                                    <td class="px-4 md:py-3 py-2 flex items-center justify-between md:table-cell">
                                        <span class="md:hidden text-xs font-bold text-gray-500 uppercase">Return Qty</span>
                                        <div class="flex items-center gap-1.5 md:w-[130px] justify-center"
                                            :class="!item.is_included ? 'opacity-50' : ''">

                                            <button type="button" tabindex="-1"
                                                class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed border border-gray-200 transition-colors shadow-sm"
                                                :disabled="!item.is_included || item.quantity <= 0"
                                                @click="item.quantity = Math.max(0, parseFloat(item.quantity || 0) - 1); calculate()">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="5" y1="12" x2="19" y2="12">
                                                    </line>
                                                </svg>
                                            </button>

                                            <input type="number" step="0.0001" min="0.0001" :max="item.max_qty"
                                                x-model="item.quantity" @keydown="window.preventInvalidChars($event)"
                                                @input="item.quantity = window.sanitizePositiveNumber(item.quantity); calculate()"
                                                :disabled="!item.is_included"
                                                :name="item.is_included ? 'items[' + index + '][quantity]' : ''"
                                                class="w-16 text-center py-1 text-sm border border-gray-300 rounded focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none disabled:bg-gray-50 font-bold text-gray-800 m-0 p-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none shadow-inner"
                                                style="-moz-appearance: textfield;">

                                            <button type="button" tabindex="-1"
                                                class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed border border-gray-200 transition-colors shadow-sm"
                                                :disabled="!item.is_included || item.quantity >= item.max_qty"
                                                @click="item.quantity = Math.min(item.max_qty, parseFloat(item.quantity || 0) + 1); calculate()">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="12" y1="5" x2="12" y2="19">
                                                    </line>
                                                    <line x1="5" y1="12" x2="19" y2="12">
                                                    </line>
                                                </svg>
                                            </button>

                                        </div>
                                    </td>

                                    <td class="px-4 md:py-3 py-2 flex flex-col md:table-cell gap-1.5 md:gap-0">
                                        <span class="md:hidden text-xs font-bold text-gray-500 uppercase">Return
                                            Reason</span>
                                        {{-- Built inline rather than with x-custom-select.
                                             That component takes a static name and owns its
                                             own value; this one needs a per-row name bound to
                                             the loop index and must read item.return_reason
                                             directly, or every row would show row one's pick. --}}
                                        {{-- The panel is position:fixed, not absolute. Its
                                             scroll container sets overflow-x-auto, and CSS
                                             computes overflow-y to auto whenever the other
                                             axis is not visible — so an absolute panel was
                                             clipped by the table and added a stray
                                             scrollbar. Fixed takes it out of that box
                                             entirely; place() then measures the trigger and
                                             flips the panel above when the row sits near the
                                             bottom of the viewport. --}}
                                        <div class="w-full" x-data="{
                                            open: false,
                                            above: false,
                                            top: 0,
                                            left: 0,
                                            width: 0,
                                            place() {
                                                const r = this.$refs.trigger.getBoundingClientRect();
                                                this.left = r.left;
                                                this.width = r.width;
                                                this.above = (window.innerHeight - r.bottom) < 240;
                                                this.top = this.above ? r.top - 6 : r.bottom + 6;
                                            },
                                            toggle() {
                                                if (this.open) { this.open = false; return; }
                                                this.place();
                                                this.open = true;
                                            },
                                        }" @keydown.escape.window="open = false"
                                            @scroll.window.capture="open = false" @resize.window="open = false">
                                            <input type="hidden" x-model="item.return_reason"
                                                :name="item.is_included ? 'items[' + index + '][return_reason]' : ''">

                                            <button type="button" role="combobox" x-ref="trigger"
                                                :aria-expanded="open" :disabled="!item.is_included" @click="toggle()"
                                                :class="open ? 'border-brand-500 ring-2 ring-brand-500/15' :
                                                    'border-gray-300 hover:border-gray-400'"
                                                class="flex w-full items-center gap-2 rounded border bg-white px-2 py-1.5 text-left text-[12px] outline-none transition-all disabled:cursor-not-allowed disabled:bg-gray-100">
                                                <span class="flex-1 truncate">
                                                    @foreach ($returnReasonOptions as $value => $label)
                                                        <span
                                                            x-show="item.return_reason === '{{ $value }}'">{{ $label }}</span>
                                                    @endforeach
                                                </span>
                                                <svg class="h-3 w-3 shrink-0 text-gray-400 transition-transform"
                                                    :class="open && 'rotate-180'" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m6 9 6 6 6-6" />
                                                </svg>
                                            </button>

                                            <div x-cloak x-show="open" @click.away="open = false" role="listbox"
                                                :style="`top:${top}px; left:${left}px; width:${width}px;` + (above ?
                                                    ' transform: translateY(-100%);' : '')"
                                                class="fixed z-[70] rounded-lg border border-gray-100 bg-white p-1 shadow-[0_10px_40px_-8px_rgba(0,0,0,0.18)]"
                                                style="display: none;">
                                                @foreach ($returnReasonOptions as $value => $label)
                                                    <button type="button" role="option"
                                                        @click="open = false; item.return_reason = '{{ $value }}'"
                                                        :class="item.return_reason === '{{ $value }}' ?
                                                            'bg-brand-500/10 text-brand-600 font-bold' :
                                                            'text-gray-700 hover:bg-gray-50'"
                                                        class="flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-[12px] transition-colors">
                                                        <span class="flex-1 truncate">{{ $label }}</span>
                                                        <svg class="h-3.5 w-3.5 shrink-0"
                                                            x-show="item.return_reason === '{{ $value }}'"
                                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="m5 13 4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>

                                    <td
                                        class="px-5 md:py-3 py-3 flex items-center justify-between md:table-cell text-left md:text-right border-t border-gray-100 md:border-none mt-2 md:mt-0">
                                        <span
                                            class="md:hidden text-[13px] font-extrabold text-gray-700 uppercase">Subtotal</span>
                                        <span class="font-bold text-gray-800 text-[14px]"
                                            x-text="formatCurrency(item.line_total)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" x-show="items.length > 0" x-cloak>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 flex flex-col gap-5">

                    <div class="flex flex-col h-full">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            Reason for Return
                        </label>
                        <textarea name="reason" rows="3"
                            class="w-full border border-gray-300 rounded p-3 text-sm focus:border-[#108c2a] outline-none resize-y transition-colors"
                            placeholder="Provide a detailed reason for the return...">{{ old('reason', $purchaseReturn->reason) }}</textarea>
                    </div>

                    <div class="flex flex-col h-full">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                            Supplier Credit Note Ref (Optional)
                        </label>
                        <textarea name="supplier_credit_note_number" rows="2"
                            class="w-full border border-gray-300 rounded p-3 text-sm focus:border-[#108c2a] outline-none resize-y transition-colors"
                            placeholder="Enter credit note references, terms, or supplier communication details...">{{ old('supplier_credit_note_number', $purchaseReturn->supplier_credit_note_number) }}</textarea>
                    </div>

                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3
                        class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-4">
                        Refund Summary</h3>

                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Subtotal (Taxable):</span>
                            <span class="font-bold text-gray-800" x-text="'₹' + formatCurrency(totals.subtotal)"></span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Total Tax Return:</span>
                            <span class="font-bold text-gray-800" x-text="'₹' + formatCurrency(totals.tax)"></span>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t border-gray-100 text-lg">
                            <span class="font-extrabold text-gray-800">Total Refund Expected:</span>
                            <span class="font-extrabold text-[#108c2a]"
                                x-text="'₹' + formatCurrency(totals.grand_total)"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm mb-6"
                x-show="items.length > 0" x-cloak>
                <a href="{{ route('admin.purchase-returns.index') }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit"
                    class="bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Update Return
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function purchaseReturnForm(existingReturn, originalPurchase, allUnits = []) {

            // Handle Laravel Validation Fallbacks securely
            const oldItemsRaw = @json(old('items', []));
            const isOldData = oldItemsRaw && Object.keys(oldItemsRaw).length > 0;
            const oldItems = Array.isArray(oldItemsRaw) ? oldItemsRaw : Object.values(oldItemsRaw || {});

            return {
                // PO Search State
                poSearchTerm: originalPurchase.purchase_number,
                poSearchResults: [],
                showPoResults: false,
                isSearchingPo: false,

                header: {
                    purchase_id: existingReturn.purchase_id,
                    purchase_number: originalPurchase.purchase_number,
                    supplier_id: existingReturn.supplier_id,
                    warehouse_id: existingReturn.warehouse_id,
                    store_id: existingReturn.store_id,
                    tax_type: existingReturn.tax_type,
                },

                items: [],

                totals: {
                    subtotal: 0,
                    tax: 0,
                    grand_total: 0
                },

                init() {
                    // Map the saved database items on first load
                    this.items = (originalPurchase?.items || []).map((pItem) => {
                        let isActive = false;
                        let qty = 0;
                        let reason = 'damaged';
                        let rtnItemId = null;

                        if (isOldData) {
                            let oldMatch = oldItems.find(o => o.purchase_item_id == pItem.id);
                            if (oldMatch) {
                                isActive = true;
                                qty = parseFloat(oldMatch.quantity);
                                reason = oldMatch.return_reason;
                                rtnItemId = oldMatch.id || null;
                            }
                        } else {
                            let dbMatch = existingReturn.items.find(r => r.purchase_item_id == pItem.id);
                            if (dbMatch) {
                                isActive = true;
                                qty = parseFloat(dbMatch.quantity);
                                reason = dbMatch.return_reason;
                                rtnItemId = dbMatch.id;
                            }
                        }

                        return {
                            is_included: isActive,
                            id: rtnItemId, // Keeps the DB ID so we update, not duplicate
                            purchase_item_id: pItem.id,
                            product_id: pItem.product_id,
                            product_sku_id: pItem.product_sku_id,
                            unit_id: pItem.unit_id,
                            product_name: pItem.product?.name || 'Unknown',
                            sku_code: pItem.product_sku?.sku || 'Unknown',
                            unit_cost: parseFloat(pItem.unit_cost),
                            tax_percent: parseFloat(pItem.tax_percent),
                            tax_type: originalPurchase.tax_type,
                            max_qty: parseFloat(pItem.available_qty !== undefined ? pItem.available_qty : pItem
                                .quantity),
                            quantity: qty,
                            return_reason: reason,
                            line_total: 0
                        };
                    });

                    this.calculate();
                },

                // --- AJAX SEARCH LOGIC ---
                async searchPOs() {
                    let term = this.poSearchTerm.trim();
                    this.isSearchingPo = true;
                    this.showPoResults = true;

                    try {
                        let response = await fetch(
                            `{{ route('admin.api.purchases.search') }}?term=${encodeURIComponent(term)}`);
                        if (!response.ok) throw new Error("Search failed");
                        this.poSearchResults = await response.json();
                    } catch (error) {
                        console.error(error);
                        this.poSearchResults = [];
                    } finally {
                        this.isSearchingPo = false;
                    }
                },

                async selectPO(po) {
                    this.poSearchTerm = po.purchase_number;
                    this.showPoResults = false;
                    BizAlert.loading('Loading PO details...');

                    try {
                        let response = await fetch(
                            `/admin/api/purchases/${po.id}/for-return?exclude_return_id=${this.header.purchase_id ? '{{ $purchaseReturn->id }}' : ''}`
                        );
                        if (!response.ok) throw new Error('PO Not Found');
                        let data = await response.json();
                        this.loadNewPurchaseData(data);
                    } catch (error) {
                        BizAlert.toast('Failed to load PO details.', 'error');
                        this.clearPO();
                    }
                },

                // Only used if the user clears the input and selects a DIFFERENT PO!
                loadNewPurchaseData(data) {
                    this.header = {
                        purchase_id: data.id,
                        purchase_number: data.purchase_number,
                        supplier_id: data.supplier_id,
                        warehouse_id: data.warehouse_id,
                        store_id: data.store_id,
                        tax_type: data.tax_type,
                    };

                    // The warehouse select holds its own value and cannot read
                    // header.warehouse_id, so the new PO's warehouse is pushed in.
                    this.$nextTick(() => {
                        window.dispatchEvent(new CustomEvent("set-return-warehouse", {
                            detail: String(data.warehouse_id ?? ""),
                        }));
                    });

                    this.items = data.items.map(item => ({
                        is_included: false,
                        id: null, // Wipe ID! If they submit a new PO, the backend will delete the old draft items and make new ones.
                        purchase_item_id: item.id,
                        product_id: item.product_id,
                        product_sku_id: item.product_sku_id,
                        unit_id: item.unit_id,
                        product_name: item.product?.name || 'Unknown',
                        sku_code: item.product_sku?.sku || 'Unknown',
                        unit_cost: parseFloat(item.unit_cost),
                        tax_percent: parseFloat(item.tax_percent),
                        tax_type: data.tax_type,
                        max_qty: parseFloat(item.available_qty !== undefined ? item.available_qty : item
                            .quantity),
                        quantity: 0,
                        return_reason: 'damaged',
                        line_total: 0
                    }));

                    this.calculate();
                    BizAlert.toast('New Purchase Order loaded!', 'success');
                },

                clearPO() {
                    this.poSearchTerm = '';
                    this.header.purchase_id = '';
                    this.header.purchase_number = '';
                    this.items = [];
                    this.calculate();
                },

                // --- MATH LOGIC ---
                calculate() {
                    let subtotalAcc = 0;
                    let taxAcc = 0;

                    this.items.forEach(item => {
                        if (!item.is_included) {
                            item.line_total = 0;
                            return;
                        }

                        let qty = parseFloat(item.quantity) || 0;
                        if (qty > item.max_qty) qty = item.max_qty;
                        item.quantity = qty;

                        let cost = parseFloat(item.unit_cost) || 0;
                        let taxPct = parseFloat(item.tax_percent) || 0;
                        let isInclusive = item.tax_type === 'inclusive';

                        let baseVal = qty * cost;
                        let taxable = 0;
                        let tax = 0;

                        if (isInclusive) {
                            taxable = baseVal / (1 + (taxPct / 100));
                            tax = baseVal - taxable;
                        } else {
                            taxable = baseVal;
                            tax = taxable * (taxPct / 100);
                        }

                        item.line_total = taxable + tax;
                        subtotalAcc += taxable;
                        taxAcc += tax;
                    });

                    this.totals.subtotal = subtotalAcc;
                    this.totals.tax = taxAcc;
                    this.totals.grand_total = subtotalAcc + taxAcc;
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
