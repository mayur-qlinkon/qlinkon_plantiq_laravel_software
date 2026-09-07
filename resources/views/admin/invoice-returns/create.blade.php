@extends('layouts.admin')

@section('title', 'Create Credit Note for ' . $invoice->invoice_number)

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Sales Return</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
    </style>
@endpush

@section('content')
    @php
        // String keys throughout — an integer-keyed array makes x-custom-select
        // reset to index 0 on render.
        $returnTypeOptions = [
            'refund' => 'Refund (Give money back)',
            'credit_note' => 'Credit Note (Adjust Ledger)',
            'replacement' => 'Replacement (Exchange)',
        ];

        $returnReasonOptions = [
            'customer_return' => 'Customer Changed Mind',
            'damaged' => 'Damaged Goods',
            'quality_issue' => 'Quality Issue',
            'wrong_item' => 'Wrong Item Sent',
            'expired' => 'Expired',
            'other' => 'Other',
        ];

        $warehouseOptions = [];
        foreach ($warehouses as $warehouse) {
            $warehouseOptions[(string) $warehouse->id] = $warehouse->name;
        }

        // Mirrors the Alpine default on the warehouse_id line, so the visible
        // pick and the value calculate() uses can never disagree.
        $defaultWarehouseId = (string) old(
            'warehouse_id',
            $invoice->warehouse_id ?? ($warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id ?? ''),
        );

        // 'percentage', not 'percent' — calculate() accepts both, but the value
        // that gets submitted should match what the server stores.
        $discountTypeOptions = ['percentage' => 'Percent (%)', 'fixed' => 'Flat (₹)'];
        $selectedDiscountType = old('discount_type', $invoice->discount_type ?? 'percentage');
    @endphp
    <div class="pb-20" x-data="invoiceReturnForm(@js($invoice), @js($companyState), @js($returnableMap))">

        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-[1.5rem] font-bold text-gray-500 uppercase tracking-widest1">
                    Create Return for <span class="text-[#108c2a]">{{ $invoice->invoice_number }}</span>
                </h1>
                <p class="text-sm text-gray-500 font-medium">Select the items the customer is returning to generate a Credit Note.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 shadow-sm">
                <div class="font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i> Please fix the following errors:
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="returnForm" action="{{ route('admin.invoice-returns.store', $invoice->id) }}" method="POST"
            @submit="BizAlert.loading('Processing Return...')">
            @csrf

            {{-- Hidden Strict Linkages --}}
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="store_id" value="{{ $invoice->store_id }}">
            <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">
            <input type="hidden" name="customer_name" value="{{ $invoice->customer_name }}">
            <input type="hidden" name="supply_state" value="{{ $invoice->supply_state }}">
            <input type="hidden" name="gst_treatment" value="{{ $invoice->gst_treatment }}">

            {{-- 1. RETURN CONFIGURATION HEADER --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return
                            Date</label>
                        <input type="date" name="return_date" x-model="formData.return_date" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-red-500 outline-none font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return
                            Type</label>
                        {{-- The wrapper catches the change event bubbling up from
                             the component's hidden native select. That is the only
                             way this component writes back into the parent Alpine
                             scope — it owns its own value. --}}
                        <div @change="formData.return_type = $event.target.value">
                            <x-custom-select name="return_type" :options="$returnTypeOptions"
                                :selected="old('return_type', 'refund')" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Reason</label>
                        <div @change="formData.return_reason = $event.target.value">
                            <x-custom-select name="return_reason" :options="$returnReasonOptions"
                                :selected="old('return_reason', 'customer_return')" />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return to
                                Warehouse <span class="text-red-500">*</span></label>
                            <div @change="formData.warehouse_id = $event.target.value">
                                <x-custom-select name="warehouse_id" :options="$warehouseOptions"
                                    :selected="$defaultWarehouseId" />
                            </div>
                        </div>

                        {{-- The Restock Toggle --}}
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" name="restock" value="true" x-model="formData.restock"
                                    class="sr-only">
                                <div class="block w-10 h-6 rounded-full transition-colors"
                                    :class="formData.restock ? 'bg-[#108c2a]' : 'bg-gray-300'"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform"
                                    :class="formData.restock ? 'transform translate-x-4' : ''"></div>
                            </div>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-800">Restock Items</span>
                                <span class="block text-[11px] text-gray-500">Put items back into physical inventory.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 2. INVOICE ITEMS (Strictly loaded from original invoice) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Select Items to Return</h2>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-4 w-12 text-center"><i data-lucide="check-square" class="w-4 h-4 mx-auto"></i></th>
                                <th class="px-4 py-4 min-w-[250px]">PRODUCT DETAILS</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">ORIGINAL</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">RETURNED</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">REMAINING</th>
                                <th class="px-4 py-4 min-w-[140px] text-right">RETURN PRICE (₹)</th>
                                {{-- 🌟 NEW: Editable Tax Column --}}
                                <th class="px-4 py-4 min-w-[110px] text-right">TAX (%)</th>
                                <th class="px-4 py-4 min-w-[140px] text-center">RETURN QTY</th>
                                <th class="px-5 py-4 min-w-[140px] text-right">REFUND AMT (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.invoice_item_id">
                                <tr class="transition-colors"
                                    :class="item.remaining_qty <= 0 ? 'bg-gray-50 opacity-60' : (item.is_returning ? 'bg-red-50/30' : 'hover:bg-gray-50/50')">

                                    {{-- Checkbox --}}
                                    <td class="px-4 py-3 text-center align-middle">
                                        {{-- UI Fix: Bigger touch target wrapper --}}
                                        <div class="flex items-center justify-center w-full h-full min-h-[40px]">
                                            <input type="checkbox" x-model="item.is_returning" @change="calculate()"
                                                :disabled="item.remaining_qty <= 0"
                                                class="w-5 h-5 text-red-600 rounded border-gray-300 focus:ring-red-500 cursor-pointer shadow-sm disabled:cursor-not-allowed disabled:opacity-40">
                                        </div>
                                    </td>

                                    {{-- Product Info --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <div class="text-[13px] font-bold text-gray-800" x-text="item.product_name"></div>
                                            <span x-show="item.remaining_qty <= 0"
                                                class="bg-gray-200 text-gray-600 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider">
                                                Fully Returned
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="text-[11px] text-gray-500 font-mono" x-text="'SKU: ' + item.sku_code"></span>
                                            <span x-show="item.discount_amount > 0"
                                                class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-bold border border-amber-200">
                                                DISC: <span x-text="item.discount_type === 'percentage' ? item.discount_amount + '%' : '₹' + item.discount_amount"></span>
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Original Qty --}}
                                    <td class="px-4 py-3 text-center align-middle">
                                        <span class="text-[13px] font-bold text-gray-700 bg-gray-50 px-2 py-1 rounded"
                                            x-text="formatQty(item.original_qty)"></span>
                                    </td>

                                    {{-- Already Returned Qty --}}
                                    <td class="px-4 py-3 text-center align-middle">
                                        <span class="text-[13px] font-bold px-2 py-1 rounded"
                                            :class="item.already_returned_qty > 0 ? 'text-amber-700 bg-amber-50 border border-amber-200' : 'text-gray-400 bg-gray-50'"
                                            x-text="formatQty(item.already_returned_qty)"></span>
                                    </td>

                                    {{-- Remaining Returnable Qty --}}
                                    <td class="px-4 py-3 text-center align-middle">
                                        <span class="text-[13px] font-black px-2 py-1 rounded"
                                            :class="item.remaining_qty > 0 ? 'text-green-700 bg-green-50 border border-green-200' : 'text-gray-400 bg-gray-100'"
                                            x-text="formatQty(item.remaining_qty)"></span>
                                    </td>

                                    {{-- Editable Return Price --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="relative w-full min-w-[100px]" :class="!item.is_returning ? 'opacity-50 pointer-events-none' : ''">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">₹</span>
                                           <input 
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                x-model="item.unit_price"
                                                @input="
                                                    item.unit_price = $event.target.value.replace(/\D/g, '');
                                                    calculate();
                                                "
                                                :disabled="!item.is_returning"
                                                class="w-full h-10 border border-gray-300 rounded px-2 pl-7 text-sm focus:border-red-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white"
                                                placeholder="0"
                                            >
                                        </div>                                        
                                    </td>

                                    {{-- 🌟 NEW: Editable Tax Percentage --}}
                                    <td class="px-4 py-3 align-middle text-right">
                                        <div class="relative inline-block w-20 min-w-[80px]" :class="!item.is_returning ? 'opacity-50 pointer-events-none' : ''">
                                            <input 
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                x-model="item.tax_percent"
                                                @input="
                                                    item.tax_percent = $event.target.value.replace(/\D/g, '');

                                                    if(Number(item.tax_percent) > 100){
                                                        item.tax_percent = 100;
                                                    }

                                                    calculate();
                                                "
                                                :disabled="!item.is_returning"
                                                class="w-full h-10 border border-gray-300 rounded px-2 pr-6 text-sm focus:border-red-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white"
                                                placeholder="0"
                                            >
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">%</span>
                                        </div>
                                    </td>

                                    {{-- Return Qty Input (capped at remaining) --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex flex-col items-center min-w-[100px]"
                                            :class="(!item.is_returning || item.remaining_qty <= 0) ? 'opacity-50 pointer-events-none' : ''">
                                            <input 
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                x-model="item.return_qty"
                                                @input="
                                                    item.return_qty = $event.target.value.replace(/\D/g, '');

                                                    if(Number(item.return_qty) > Number(item.remaining_qty)){
                                                        item.return_qty = item.remaining_qty;
                                                    }

                                                    validateQty(item);
                                                "
                                                :disabled="!item.is_returning || item.remaining_qty <= 0"
                                                class="w-24 h-10 border border-gray-300 rounded px-2 text-center text-sm font-black focus:border-red-500 outline-none text-red-600 bg-white shadow-inner"
                                                placeholder="0"
                                            >
                                            <span class="text-[10px] text-gray-400 font-bold mt-1"
                                                x-show="item.is_returning && item.remaining_qty > 0"
                                                x-text="'max ' + formatQty(item.remaining_qty)"></span>
                                        </div>
                                    </td>

                                    {{-- Line Total Return --}}
                                    <td class="px-5 py-3 text-right align-middle">
                                        <span class="font-black text-gray-900 text-[15px]"
                                            x-text="item.is_returning ? formatCurrency(item.line_total) : '₹0.00'"></span>
                                    </td>

                                    {{-- 🌟 The Hidden Inputs (Only rendered if checkbox is ticked and qty > 0) --}}
                                    <template x-if="item.is_returning && item.return_qty > 0">
                                        <div style="display: none;">
                                            <input type="hidden" :name="'items[' + index + '][invoice_item_id]'"
                                                :value="item.invoice_item_id">
                                            <input type="hidden" :name="'items[' + index + '][product_id]'"
                                                :value="item.product_id">
                                            <input type="hidden" :name="'items[' + index + '][product_sku_id]'"
                                                :value="item.product_sku_id">
                                            <input type="hidden" :name="'items[' + index + '][unit_id]'"
                                                :value="item.unit_id">
                                            <input type="hidden" :name="'items[' + index + '][product_name]'"
                                                :value="item.product_name">
                                            <input type="hidden" :name="'items[' + index + '][sku_code]'"
                                                :value="item.sku_code">
                                            <input type="hidden" :name="'items[' + index + '][hsn_code]'"
                                                :value="item.hsn_code">
                                            <input type="hidden" :name="'items[' + index + '][quantity]'"
                                                :value="item.return_qty">
                                            <input type="hidden" :name="'items[' + index + '][unit_price]'"
                                                :value="item.unit_price">
                                            <input type="hidden" :name="'items[' + index + '][tax_percent]'"
                                                :value="item.tax_percent">
                                            <input type="hidden" :name="'items[' + index + '][tax_type]'"
                                                :value="item.tax_type">
                                            <input type="hidden" :name="'items[' + index + '][discount_type]'"
                                                :value="item.discount_type">
                                            <input type="hidden" :name="'items[' + index + '][discount_amount]'"
                                                :value="item.discount_amount">
                                        </div>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 3. SUMMARY SECTION --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

                {{-- Notes --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col gap-4">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b pb-3">Notes & Reminders
                    </h3>
                    <textarea name="notes" rows="3" placeholder="Internal reason for return..."
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none resize-none"></textarea>

                    <div
                        class="bg-yellow-50 text-yellow-800 text-xs p-3 rounded border border-yellow-200 mt-2 flex gap-2 items-start">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                        <p>Submitting this will create a <strong>Draft Credit Note</strong>. Stock will not be returned to
                            the warehouse until you review and <strong>Confirm</strong> the draft.</p>
                    </div>
                </div>

                {{-- FINANCIAL SUMMARY --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3
                        class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-4">
                        Refund Estimation</h3>

                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Subtotal Return:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
                        </div>

                        {{-- Tax Breakdown --}}
                        <template x-if="totals.igst > 0">
                            <div class="flex justify-between items-center text-gray-500">
                                <span>IGST Reversed:</span>
                                <span x-text="formatCurrency(totals.igst)"></span>
                            </div>
                        </template>
                        <template x-if="totals.igst <= 0 && totals.tax > 0">
                            <div class="space-y-1">
                                <div class="flex justify-between items-center text-gray-500">
                                    <span>CGST Reversed:</span>
                                    <span x-text="formatCurrency(totals.cgst)"></span>
                                </div>
                                <div class="flex justify-between items-center text-gray-500">
                                    <span>SGST Reversed:</span>
                                    <span x-text="formatCurrency(totals.sgst)"></span>
                                </div>
                            </div>
                        </template>

                        {{-- Original Invoice Global Discount Pro-ration (Optional handling) --}}
                        <div class="flex flex-wrap sm:flex-nowrap justify-between items-center pt-2 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-600">Global Discount Reversal:</span>
                                <div class="w-36"
                                    @change="
                                        global.discount_type = $event.target.value;
                                        calculate();
                                    ">
                                    <x-custom-select name="discount_type" :options="$discountTypeOptions"
                                        :selected="$selectedDiscountType" />
                                </div>
                            </div>
                            <input type="number" step="0.01" min="0" name="discount_amount" x-model="global.discount_value"
                                @keydown="window.preventInvalidChars($event)" @input="global.discount_value = window.sanitizePositiveNumber(global.discount_value); calculate()"
                                class="w-24 sm:w-32 border border-gray-300 rounded px-2 py-1 text-right font-bold text-gray-800 focus:border-red-500 outline-none ml-auto shadow-sm">
                        </div>

                        {{-- Final Totals --}}
                        <div class="flex justify-between items-end pt-4 border-t border-gray-100">
                            <div>
                                <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Refund
                                    Value</div>
                                <div class="text-sm font-bold text-gray-400 mt-1"
                                    x-text="'Round off: ' + global.round_off"></div>
                                <input type="hidden" name="round_off" :value="global.round_off">
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-black text-red-600" x-text="formatCurrency(totals.grand_total)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm">
                <a href="{{ route('admin.invoices.show', $invoice->id) }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="file-minus" class="w-4 h-4"></i> Generate Draft Credit Note
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function invoiceReturnForm(invoiceData, companyState, returnableMap) {

            // 🌟 1. Map exactly from the existing invoice items, enriched with capacity info
            const initialItems = invoiceData.items.map(item => {
                const cap = (returnableMap && returnableMap[item.id]) ? returnableMap[item.id] : null;
                const original = parseFloat(item.quantity) || 0;
                const alreadyReturned = cap ? parseFloat(cap.returned) || 0 : 0;
                const remaining = cap ? parseFloat(cap.remaining) || 0 : original;

                return {
                    invoice_item_id: item.id,
                    product_id: item.product_id,
                    product_sku_id: item.product_sku_id,
                    unit_id: item.unit_id,
                    product_name: item.product_name,
                    sku_code: item.sku ? (item.sku.sku_code || item.sku.sku) : '',
                    hsn_code: item.hsn_code || '',
                    original_qty: original,
                    already_returned_qty: alreadyReturned,
                    remaining_qty: remaining,

                    // Default: checkbox OFF, pre-fill with the remaining capacity
                    return_qty: remaining,
                    is_returning: false,

                    unit_price: parseFloat(item.unit_price) || 0,
                    tax_percent: parseFloat(item.tax_percent) || 0,
                    tax_type: item.tax_type || 'exclusive',
                    discount_type: item.discount_type || 'percentage',
                    discount_amount: parseFloat(item.discount_amount) || 0,
                    line_total: 0
                };
            });

            return {
                company_state: companyState,
                supply_state: invoiceData.supply_state || companyState,
                items: initialItems,

                formData: {
                    return_date: new Date().toISOString().split('T')[0],
                    return_type: 'refund',
                    return_reason: 'customer_return',
                    warehouse_id: invoiceData.warehouse_id || '{{ $warehouses->firstWhere('is_default', true)?->id ?? $warehouses->first()?->id ?? '' }}',
                    restock: true
                },

                global: {
                    // Pre-load the original invoice's discount setup so we can reverse it accurately
                    discount_type: invoiceData.discount_type || 'percentage',
                    discount_value: parseFloat(invoiceData.discount_amount) || 0,
                    round_off: '0.00'
                },

                totals: {
                    subtotal: 0,
                    tax: 0,
                    cgst: 0,
                    sgst: 0,
                    igst: 0,
                    grand_total: 0
                },

                init() {
                    // Force an initial calculation just in case
                    this.calculate();
                },

                validateQty(item) {
                    // Cap at the remaining returnable qty (original - already returned).
                    if (item.return_qty > item.remaining_qty) {
                        item.return_qty = item.remaining_qty;
                        BizAlert.toast(
                            'Only ' + this.formatQty(item.remaining_qty) + ' units remain returnable on this line.',
                            'warning'
                        );
                    }
                    if (item.return_qty < 0) item.return_qty = 0;

                    this.calculate();
                },

                formatQty(val) {
                    const num = parseFloat(val) || 0;
                    // Drop trailing zeros so "5.0000" reads as "5" and "5.5000" as "5.5".
                    return num.toString().replace(/\.?0+$/, '') || '0';
                },

                calculate() {
                    let subtotalAcc = 0;
                    let taxAcc = 0;

                    this.items.forEach(item => {
                        // Only calculate items that are ticked and have quantity
                        if (item.is_returning && item.return_qty > 0) {
                            // Every one of these needs its own fallback. A single
                            // NaN propagates through taxable -> subtotal -> grand
                            // total, and formatCurrency() prints NaN as "₹0.00",
                            // so the screen showed a believable zero instead of a
                            // broken total.
                            let qty = parseFloat(item.return_qty) || 0;
                            let price = parseFloat(item.unit_price) || 0;
                            let taxPct = parseFloat(item.tax_percent) || 0;
                            let discVal = parseFloat(item.discount_amount) || 0;

                            let baseVal = qty * price;

                            // Line Discount Logic (Assuming proportional applied per unit)
                            let lineDiscountAmt = 0;
                            if (item.discount_type === 'percentage' || item.discount_type === 'percent') {
                                lineDiscountAmt = baseVal * (discVal / 100);
                            } else {
                                // If it was a flat ₹50 discount on 10 items, and they return 2 items...
                                // We reverse ₹10 of the discount to be fair.
                                // original_qty of 0 would make this Infinity.
                                const originalQty = parseFloat(item.original_qty) || 0;
                                let perUnitDiscount = originalQty > 0 ? discVal / originalQty : 0;
                                lineDiscountAmt = perUnitDiscount * qty;
                            }

                            let afterDiscount = Math.max(0, baseVal - lineDiscountAmt);

                            let taxable = 0,
                                tax = 0;
                            if (item.tax_type === 'inclusive') {
                                taxable = afterDiscount / (1 + (taxPct / 100));
                                tax = afterDiscount - taxable;
                            } else {
                                taxable = afterDiscount;
                                tax = taxable * (taxPct / 100);
                            }

                            item.line_total = taxable + tax;
                            subtotalAcc += taxable;
                            taxAcc += tax;
                        } else {
                            item.line_total = 0;
                        }
                    });

                    this.totals.subtotal = subtotalAcc;
                    this.totals.tax = taxAcc;

                    // Inter/Intra State Math
                    const isInterState = this.supply_state.trim().toLowerCase() !== this.company_state.trim().toLowerCase();

                    if (isInterState) {
                        this.totals.igst = taxAcc;
                        this.totals.cgst = 0;
                        this.totals.sgst = 0;
                    } else {
                        this.totals.igst = 0;
                        this.totals.cgst = taxAcc / 2;
                        this.totals.sgst = taxAcc / 2;
                    }

                    let globalDiscVal = parseFloat(this.global.discount_value) || 0;
                    let itemsSum = subtotalAcc + taxAcc;

                    let globalDiscountAmount = 0;
                    if (this.global.discount_type === 'percentage' || this.global.discount_type === 'percent') {
                        globalDiscountAmount = itemsSum * (globalDiscVal / 100);
                    } else {
                        globalDiscountAmount = globalDiscVal; // Reversing fixed discount
                    }

                    let totalBeforeRound = Math.max(0, itemsSum - globalDiscountAmount);

                    this.totals.grand_total = Math.round(totalBeforeRound);
                    this.global.round_off = (this.totals.grand_total - totalBeforeRound).toFixed(2);
                },

                formatCurrency(val) {
                    // Remove the formatting if it returns NaN
                    if (isNaN(val)) return '₹0.00';
                    return val.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        }
    </script>
@endpush
