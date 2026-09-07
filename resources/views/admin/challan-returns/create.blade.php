@extends('layouts.admin')

@section('title', 'Process Return - Qlinkon BIZNESS')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Challan Return {{ $challan->challan_number }}</h1>
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
    <div class="pb-20" x-data="returnForm(@js($challan->items))">
        
        {{-- HEADER --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-[1.5rem] font-bold text-[#212538] tracking-tight mb-1">Process Goods Return</h1>
                <p class="text-sm text-gray-500 font-medium">Returning goods against {{ $challan->type_label }} #<strong class="text-gray-800">{{ $challan->challan_number }}</strong></p>
            </div>
        </div>

        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200 shadow-sm">
                <div class="font-bold mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    Please fix the following errors:
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof Swal !== 'undefined') Swal.close();
                });
            </script>
        @endif

        <form id="mainReturnForm" action="{{ route('admin.challan-returns.store') }}" method="POST"
            @submit="validateAndSubmit($event)">
            @csrf

            <input type="hidden" name="challan_id" value="{{ $challan->id }}">

            {{-- 1. PARENT CHALLAN SNAPSHOT (Read Only) --}}
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 mb-6 p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Party Details</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challan->party_name ?: 'Unknown Party' }}</div>
                    @if($challan->party_phone)
                        <div class="text-xs text-gray-500 mt-0.5">{{ $challan->party_phone }}</div>
                    @endif
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Dispatched From</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challan->store->name ?? 'N/A' }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">Warehouse: {{ $challan->warehouse->name ?? 'Primary' }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Challan Date</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challan->challan_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Expected Return</div>
                    <div class="text-sm font-bold {{ $challan->is_return_overdue ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $challan->return_due_date ? $challan->return_due_date->format('d M Y') : 'N/A' }}
                        @if($challan->is_return_overdue)
                            <span class="text-[10px] bg-red-100 px-1.5 py-0.5 rounded ml-1 uppercase">Overdue</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. RETURN META DETAILS --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Return Logistics</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Return Date <span class="text-red-500">*</span></label>
                        <input type="date" name="return_date" x-model="formData.return_date" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none font-medium bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Overall Condition <span class="text-red-500">*</span></label>
                        <select name="condition" x-model="formData.condition" required
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none bg-white font-bold text-gray-700">
                            <option value="good">Good Condition</option>
                            <option value="partial">Partial / Mixed</option>
                            <option value="damaged">Damaged / Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Vehicle / Courier No.</label>
                        <input type="text" name="vehicle_number" placeholder="Optional..."
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none uppercase">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Received By (Staff)</label>
                        <input type="text" name="received_by" value="{{ auth()->user()->name }}"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none">
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Return Notes</label>
                        <textarea name="notes" rows="2" placeholder="Any specific details regarding this return..."
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none resize-none"></textarea>
                    </div>

                </div>
            </div>

            {{-- 3. RETURN ITEMS TABLE --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Select Items to Return</h2>
                    <div class="text-sm font-bold text-gray-500 bg-white px-3 py-1.5 rounded border border-gray-200">
                        Total Selected: <span class="text-brand-600" x-text="items.length"></span>
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-4 w-[40px] text-center"></th>
                                <th class="px-2 py-4 min-w-[250px]">PRODUCT DETAILS</th>
                                <th class="px-4 py-4 w-[100px] text-center">PENDING</th>
                                <th class="px-4 py-4 w-[160px] text-center bg-blue-50/50 border-l border-blue-100">QTY RETURNING <span class="text-red-500">*</span></th>
                                <th class="px-4 py-4 w-[160px] text-center bg-red-50/50 border-x border-red-100">QTY DAMAGED</th>
                                <th class="px-4 py-4 w-[250px]">DAMAGE NOTES</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.challan_item_id">
                                <tr class="hover:bg-gray-50/50 transition-colors" :class="{'bg-gray-50 opacity-60': !item.is_selected}">
                                    
                                    {{-- Checkbox --}}
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" x-model="item.is_selected" @change="calculate()"
                                            class="w-4 h-4 text-brand-600 rounded border-gray-300 focus:ring-brand-500 cursor-pointer">
                                        
                                        {{-- Laravel Hidden Inputs (Only submit if selected) --}}
                                        <template x-if="item.is_selected">
                                            <div>
                                                <input type="hidden" :name="'items[' + index + '][challan_item_id]'" :value="item.challan_item_id">
                                                <input type="hidden" :name="'items[' + index + '][qty_returned]'" :value="item.qty_returned">
                                                <input type="hidden" :name="'items[' + index + '][qty_damaged]'" :value="item.qty_damaged">
                                                <input type="hidden" :name="'items[' + index + '][damage_note]'" :value="item.damage_note">
                                            </div>
                                        </template>
                                    </td>

                                    {{-- Product Info --}}
                                    <td class="px-2 py-4" @click="item.is_selected = !item.is_selected; calculate()" style="cursor: pointer;">
                                        <div class="text-[13px] font-bold text-gray-800" x-text="item.product_name"></div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-gray-500 font-mono" x-text="'SKU: ' + item.sku_code"></span>
                                        </div>
                                    </td>

                                    {{-- Pending Qty --}}
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-[14px] font-black text-gray-600" x-text="item.qty_pending"></span>
                                    </td>

                                    {{-- Returning Qty Input --}}
                                    <td class="px-4 py-4 bg-blue-50/20 border-l border-blue-50">
                                        <div class="relative max-w-[120px] mx-auto">
                                            <input type="number" step="0.01" min="0" x-model.number="item.qty_returned" @keydown="window.preventInvalidChars($event)" @input="enforceLimits(item)"
                                                :disabled="!item.is_selected"
                                                class="w-full border border-blue-200 rounded px-2 py-2 text-sm focus:border-blue-500 outline-none font-bold text-blue-800 text-center disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-200">
                                        </div>
                                    </td>

                                    {{-- Damaged Qty Input --}}
                                    <td class="px-4 py-4 bg-red-50/20 border-x border-red-50">
                                        <div class="relative max-w-[120px] mx-auto">
                                            <input type="number" step="0.01" min="0" x-model.number="item.qty_damaged" @keydown="window.preventInvalidChars($event)" @input="enforceLimits(item)"
                                                :disabled="!item.is_selected"
                                                class="w-full border border-red-200 rounded px-2 py-2 text-sm focus:border-red-500 outline-none font-bold text-red-600 text-center disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-200">
                                        </div>
                                    </td>

                                    {{-- Damage Notes --}}
                                    <td class="px-4 py-4 pr-6">
                                        <input type="text" x-model="item.damage_note" placeholder="Reason for damage..."
                                            :disabled="!item.is_selected || item.qty_damaged <= 0"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-red-500 outline-none disabled:bg-gray-50 disabled:placeholder-gray-300">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. FOOTER TOTALS --}}
            <div class="flex justify-end mb-10">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 w-full md:w-[350px]">
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-3 mb-3">Return Summary</h3>
                    
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-gray-600">Total Items Returning:</span>
                        <span class="text-lg font-black text-gray-800" x-text="totals.total_returned"></span>
                    </div>
                    
                    <div class="flex justify-between items-center text-red-600">
                        <span class="text-sm font-bold">Of which Damaged:</span>
                        <span class="text-base font-black" x-text="totals.total_damaged"></span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center text-green-600">
                        <span class="text-sm font-bold uppercase">Clean Stock Recovered:</span>
                        <span class="text-xl font-black" x-text="totals.total_clean"></span>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm mb-6">
                <a href="{{ route('admin.challans.show', $challan->id) }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit" :disabled="items.length === 0"
                    :class="items.length === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-brand-500 hover:bg-brand-600 shadow-md'"
                    class="text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="undo-2" class="w-4 h-4"></i> Confirm Return
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function returnForm(rawItems) {
            return {
                formData: {
                    return_date: new Date().toISOString().split('T')[0],
                    condition: 'good',
                },
                
                items: [],
                
                totals: {
                    total_returned: 0,
                    total_damaged: 0,
                    total_clean: 0
                },

                init() {
                    // Map raw backend items into our reactive Alpine array
                    // We only want items that have a pending quantity > 0
                    this.items = rawItems.map(item => {
                        // The controller already filters for qty_pending, but we compute it here just in case
                        let pending = Math.max(0, parseFloat(item.qty_sent) - parseFloat(item.qty_returned) - parseFloat(item.qty_invoiced));
                        
                        return {
                            challan_item_id: item.id,
                            product_name: item.product_name || 'Unknown',
                            sku_code: item.sku_code || 'N/A',
                            qty_pending: pending,
                            qty_returned: pending, // Default to returning everything that's left
                            qty_damaged: 0,
                            damage_note: '',
                            is_selected: true // Default all to selected
                        };
                    }).filter(item => item.qty_pending > 0);

                    this.calculate();
                },

                // 🌟 Strict Validation Guardrails
                enforceLimits(item) {
                    // 1. Cannot be negative or non-numeric
                    if (isNaN(item.qty_returned) || item.qty_returned < 0) item.qty_returned = 0;
                    if (isNaN(item.qty_damaged) || item.qty_damaged < 0) item.qty_damaged = 0;

                    // 2. Cannot return more than pending
                    if (item.qty_returned > item.qty_pending) {
                        item.qty_returned = item.qty_pending;
                        BizAlert.toast('Cannot return more than pending quantity.', 'warning');
                    }

                    // 3. Damaged cannot exceed returned
                    if (item.qty_damaged > item.qty_returned) {
                        item.qty_damaged = item.qty_returned;
                    }

                    // 4. Auto-clear notes if no damage
                    if (item.qty_damaged <= 0) {
                        item.damage_note = '';
                    }

                    this.calculate();
                },

                calculate() {
                    let returned = 0;
                    let damaged = 0;

                    this.items.forEach(item => {
                        if (item.is_selected) {
                            returned += (parseFloat(item.qty_returned) || 0);
                            damaged += (parseFloat(item.qty_damaged) || 0);
                        }
                    });

                    this.totals.total_returned = returned;
                    this.totals.total_damaged = damaged;
                    this.totals.total_clean = Math.max(0, returned - damaged);

                    // Auto-adjust condition dropdown based on math
                    if (damaged > 0 && damaged === returned) {
                        this.formData.condition = 'damaged';
                    } else if (damaged > 0) {
                        this.formData.condition = 'partial';
                    } else {
                        this.formData.condition = 'good';
                    }
                },

                validateAndSubmit(e) {
                    // Prevent submission if no items are selected
                    let hasSelected = this.items.some(item => item.is_selected);
                    
                    if (!hasSelected) {
                        e.preventDefault();
                        BizAlert.toast('You must select at least one item to return.', 'error');
                        return false;
                    }

                    // Ensure at least one selected item has a qty > 0
                    let totalQty = this.items.reduce((sum, item) => item.is_selected ? sum + (parseFloat(item.qty_returned) || 0) : sum, 0);
                    if (totalQty <= 0) {
                        e.preventDefault();
                        BizAlert.toast('Total return quantity must be greater than zero.', 'error');
                        return false;
                    }

                    BizAlert.loading('Processing Return...');
                    return true; // Let standard form submission proceed
                }
            }
        }
    </script>
@endpush