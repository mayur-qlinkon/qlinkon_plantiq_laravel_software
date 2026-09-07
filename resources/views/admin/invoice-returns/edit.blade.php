@extends('layouts.admin')

@section('title', 'Edit Credit Note: ' . $invoiceReturn->credit_note_number)

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit Sales Return</h1>
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
    <div class="pb-20" x-data="invoiceReturnForm(@js($invoice), @js($invoiceReturn), @js($companyState), @js($returnableMap))">

        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-[1.5rem] font-bold text-gray-500 uppercase tracking-widest">
                    Edit Return <span class="text-[#108c2a]">{{ $invoiceReturn->credit_note_number }}</span>
                </h1>
                <p class="text-sm text-gray-500 font-medium">Original Invoice:
                    <strong>{{ $invoice->invoice_number }}</strong>
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 shadow-sm">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="returnForm" action="{{ route('admin.invoice-returns.update', $invoiceReturn->id) }}" method="POST"
            @submit="BizAlert.loading('Updating Return...')">
            @csrf
            @method('PUT')

            <input type="hidden" name="store_id" value="{{ $invoiceReturn->store_id }}">
            <input type="hidden" name="supply_state" value="{{ $invoiceReturn->supply_state }}">
            <input type="hidden" name="gst_treatment" value="{{ $invoiceReturn->gst_treatment }}">
            <input type="hidden" name="currency_code" value="{{ $invoiceReturn->currency_code }}">
            <input type="hidden" name="exchange_rate" value="{{ $invoiceReturn->exchange_rate }}">

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
                        <select name="return_type" x-model="formData.return_type" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-red-500 outline-none bg-white font-medium">
                            <option value="refund">Refund (Give money back)</option>
                            <option value="credit_note">Credit Note (Adjust Ledger)</option>
                            <option value="replacement">Replacement (Exchange)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Reason</label>
                        <select name="return_reason" x-model="formData.return_reason" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-red-500 outline-none bg-white font-medium">
                            <option value="customer_return">Customer Changed Mind</option>
                            <option value="damaged">Damaged Goods</option>
                            <option value="quality_issue">Quality Issue</option>
                            <option value="wrong_item">Wrong Item Sent</option>
                            <option value="expired">Expired</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return to
                                Warehouse</label>
                            <select name="warehouse_id" x-model="formData.warehouse_id" required
                                class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-red-500 outline-none bg-white font-medium">
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

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
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- 2. INVOICE ITEMS (Strictly loaded from original invoice) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Modify Items to Return</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-4 w-12 text-center"><i data-lucide="check-square" class="w-4 h-4 mx-auto"></i></th>
                                <th class="px-4 py-4 min-w-[250px]">PRODUCT DETAILS</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">ORIGINAL</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">RETURNED</th>
                                <th class="px-4 py-4 min-w-[90px] text-center">REMAINING</th>
                                <th class="px-4 py-4 min-w-[140px] text-right">RETURN PRICE (₹)</th>
                                <th class="px-4 py-4 min-w-[110px] text-right">TAX (%)</th>
                                <th class="px-4 py-4 min-w-[140px] text-center">RETURN QTY</th>
                                <th class="px-5 py-4 min-w-[140px] text-right">REFUND AMT (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.invoice_item_id">
                                <tr class="transition-colors"
                                    :class="item.remaining_qty <= 0 && !item.is_returning ? 'bg-gray-50 opacity-60' : (item.is_returning ? 'bg-red-50/30' : 'hover:bg-gray-50/50')">

                                    {{-- Checkbox --}}
                                    <td class="px-4 py-3 text-center align-middle">
                                        <div class="flex items-center justify-center w-full h-full min-h-[40px]">
                                            <input type="checkbox" x-model="item.is_returning" @change="calculate()"
                                                :disabled="item.remaining_qty <= 0 && !item.is_returning"
                                                class="w-5 h-5 text-red-600 rounded border-gray-300 focus:ring-red-500 cursor-pointer shadow-sm disabled:cursor-not-allowed disabled:opacity-40">
                                        </div>
                                    </td>

                                    {{-- Product Info --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <div class="text-[13px] font-bold text-gray-800" x-text="item.product_name"></div>
                                            <span x-show="item.remaining_qty <= 0 && !item.is_returning"
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
                                            <input type="number" step="0.01" x-model="item.unit_price"
                                                @input="calculate()" :disabled="!item.is_returning"
                                                class="w-full h-10 border border-gray-300 rounded px-2 pl-7 text-sm focus:border-red-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white">
                                        </div>
                                    </td>

                                    {{-- Editable Tax Percentage --}}
                                    <td class="px-4 py-3 align-middle text-right">
                                        <div class="relative inline-block w-20 min-w-[80px]" :class="!item.is_returning ? 'opacity-50 pointer-events-none' : ''">
                                            <input type="number" step="0.01" x-model="item.tax_percent"
                                                @input="calculate()" :disabled="!item.is_returning"
                                                class="w-full h-10 border border-gray-300 rounded px-2 pr-6 text-sm focus:border-red-500 outline-none font-bold text-gray-700 text-right shadow-sm transition-all bg-white">
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">%</span>
                                        </div>
                                    </td>

                                    {{-- Return Qty Input (capped at remaining) --}}
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex flex-col items-center min-w-[100px]"
                                            :class="(!item.is_returning || item.remaining_qty <= 0) ? 'opacity-50 pointer-events-none' : ''">
                                            <input type="number" step="0.0001" x-model="item.return_qty"
                                                @input="validateQty(item)"
                                                :disabled="!item.is_returning || item.remaining_qty <= 0"
                                                :max="item.remaining_qty"
                                                class="w-24 h-10 border border-gray-300 rounded px-2 text-center text-sm font-black focus:border-red-500 outline-none text-red-600 bg-white shadow-inner">
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

                                    {{-- Hidden Inputs for Form Submission --}}
                                    <template x-if="item.is_returning && item.return_qty > 0">
                                        <div style="display: none;">
                                            <input type="hidden" :name="'items[' + index + '][invoice_item_id]'"
                                                :value="item.invoice_item_id">
                                            <input type="hidden" :name="'items[' + index + '][product_name]'"
                                                :value="item.product_name">
                                            <input type="hidden" :name="'items[' + index + '][sku_code]'"
                                                :value="item.sku_code">
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col gap-4">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b pb-3">Notes</h3>
                    <textarea name="notes" rows="3" x-model="formData.notes"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none resize-none"></textarea>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-4">
                        Refund Summary
                    </h3>

                    <div class="space-y-4 text-sm">
                        {{-- Subtotal --}}
                        <div class="flex justify-between items-center text-gray-600">
                            <span class="font-semibold">Subtotal Return:</span>
                            <span class="font-bold text-gray-800" x-text="formatCurrency(totals.subtotal)"></span>
                        </div>

                        {{-- UI Fix: Allowed wrapping, grouped controls together, improved touch targets --}}
                        <div class="flex flex-wrap justify-between items-center text-gray-600 pt-3 border-t border-gray-50 gap-3">
                            <div class="font-semibold text-gray-600 whitespace-nowrap">Adjustment Discount:</div>
                            
                            <div class="flex items-center gap-2 ml-auto">
                                <select name="discount_type" x-model="global.discount_type" @change="calculate()"
                                    class="border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-gray-600 focus:border-red-500 outline-none bg-gray-50 cursor-pointer shrink-0">
                                    <option value="percentage">Percent (%)</option>
                                    <option value="fixed">Flat (₹)</option>
                                </select>
                                <input type="number" step="0.01" name="discount_amount" x-model="global.discount_value"
                                    @input="calculate()"
                                    class="w-24 sm:w-28 border border-gray-300 rounded px-3 py-1.5 text-right font-bold text-gray-800 focus:border-red-500 outline-none shadow-sm">
                            </div>
                        </div>

                        {{-- Grand Total --}}
                        <div class="flex justify-between items-end pt-4 border-t border-gray-200">
                            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Grand Total Refund</div>
                            <div class="text-3xl font-black text-red-600" x-text="formatCurrency(totals.grand_total)"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm">
                <a href="{{ route('admin.invoice-returns.index') }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit"
                    class="bg-[#212538] hover:bg-black text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Update Draft
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function invoiceReturnForm(invoiceData, returnData, companyState, returnableMap) {

            // Map Invoice Items and Check if they exist in the existing Return
            const initialItems = invoiceData.items.map(invItem => {
                // Find if this specific invoice line exists in the current return record
                const existingReturnLine = returnData.items.find(ri => ri.invoice_item_id === invItem.id);

                const cap = (returnableMap && returnableMap[invItem.id]) ? returnableMap[invItem.id] : null;
                const original = parseFloat(invItem.quantity) || 0;
                const alreadyReturned = cap ? parseFloat(cap.returned) || 0 : 0;
                const remaining = cap ? parseFloat(cap.remaining) || 0 : original;

                return {
                    invoice_item_id: invItem.id,
                    product_id: invItem.product_id,
                    product_sku_id: invItem.product_sku_id,
                    unit_id: invItem.unit_id,
                    product_name: invItem.product_name,
                    sku_code: invItem.sku ? (invItem.sku.sku_code || invItem.sku.sku) : '',
                    hsn_code: invItem.hsn_code || '',
                    original_qty: original,
                    already_returned_qty: alreadyReturned,
                    remaining_qty: remaining,

                    // Logic: If it exists in return, mark as true and set the qty
                    is_returning: !!existingReturnLine,
                    return_qty: existingReturnLine ? parseFloat(existingReturnLine.quantity) : remaining,

                    unit_price: parseFloat(invItem.unit_price) || 0,
                    tax_percent: parseFloat(invItem.tax_percent) || 0,
                    tax_type: invItem.tax_type || 'exclusive',
                    discount_type: invItem.discount_type || 'percentage',
                    discount_amount: parseFloat(invItem.discount_amount) || 0,
                    line_total: 0
                };
            });

            return {
                company_state: companyState,
                supply_state: invoiceData.supply_state || companyState,
                items: initialItems,

                formData: {
                    // Laravel date cast serializes as "YYYY-MM-DD HH:MM:SS"; slice to get "YYYY-MM-DD" for the input.
                    return_date: returnData.return_date ? returnData.return_date.substring(0, 10) : new Date().toISOString().split('T')[0],
                    return_type: returnData.return_type || 'refund',
                    return_reason: returnData.return_reason || 'customer_return',
                    warehouse_id: returnData.warehouse_id || '',
                    restock: returnData.restock,
                    notes: returnData.notes || ''
                },

                global: {
                    discount_type: returnData.discount_type || 'percentage',
                    discount_value: parseFloat(returnData.discount_amount) || 0,
                },

                totals: {
                    subtotal: 0,
                    tax: 0,
                    grand_total: 0
                },

                init() {
                    this.calculate();
                },

                validateQty(item) {
                    if (item.return_qty > item.remaining_qty) item.return_qty = item.remaining_qty;
                    if (item.return_qty < 0) item.return_qty = 0;
                    this.calculate();
                },

                formatQty(val) {
                    const num = parseFloat(val) || 0;
                    return num.toString().replace(/\.?0+$/, '') || '0';
                },

                calculate() {
                    let subtotalAcc = 0;
                    let taxAcc = 0;

                    this.items.forEach(item => {
                        if (item.is_returning && item.return_qty > 0) {
                            let qty = parseFloat(item.return_qty);
                            let price = parseFloat(item.unit_price);
                            let taxPct = parseFloat(item.tax_percent);
                            let discVal = parseFloat(item.discount_amount);

                            let baseVal = qty * price;
                            let lineDiscountAmt = (item.discount_type === 'percentage') ? (baseVal * (discVal /
                                100)) : (discVal / item.original_qty * qty);
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
                        }
                    });

                    this.totals.subtotal = subtotalAcc;
                    let globalDiscVal = parseFloat(this.global.discount_value) || 0;
                    let itemsSum = subtotalAcc + taxAcc;
                    let globalDiscountAmount = (this.global.discount_type === 'percentage') ? (itemsSum * (globalDiscVal /
                        100)) : globalDiscVal;

                    this.totals.grand_total = Math.round(Math.max(0, itemsSum - globalDiscountAmount));
                },

                formatCurrency(val) {
                    return isNaN(val) ? '₹0.00' : '₹' + val.toLocaleString('en-IN', {
                        minimumFractionDigits: 2
                    });
                }
            }
        }
    </script>
@endpush
