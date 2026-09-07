@extends('layouts.admin')

@section('title', 'Edit Return: ' . $challanReturn->return_number)

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit Return {{ $challanReturn->return_number }}</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
@endpush

@section('content')
    <div class="pb-20" x-data="returnEditForm(@js($challanReturn), @js($challanReturn->items))">
        
        {{-- HEADER --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-[1.5rem] font-bold text-[#212538] tracking-tight mb-1">Edit Goods Return</h1>
                <p class="text-sm text-gray-500 font-medium">Against {{ $challanReturn->challan->type_label }} #<strong class="text-gray-800">{{ $challanReturn->challan->challan_number }}</strong></p>
            </div>
        </div>

        {{-- IMMUTABILITY WARNING --}}
        <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-xl mb-6 shadow-sm flex items-start gap-3">
            <i data-lucide="shield-check" class="w-5 h-5 shrink-0 mt-0.5 text-blue-600"></i>
            <div class="text-sm leading-relaxed">
                <strong class="font-bold text-blue-900">Inventory Integrity Lock Active.</strong><br>
                Return quantities and dates are permanently locked to preserve inventory history. You may only update the <strong>Condition</strong>, <strong>Received By</strong>, and <strong>Notes</strong>.
            </div>
        </div>

        {{-- VALIDATION ERRORS --}}
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

        <form id="mainReturnEditForm" action="{{ route('admin.challan-returns.update', $challanReturn->id) }}" method="POST"
            @submit="BizAlert.loading('Saving Updates...')">
            @csrf
            @method('PUT')

            {{-- 1. READ-ONLY CONTEXT --}}
            <div class="bg-gray-50 rounded-lg shadow-sm border border-gray-200 mb-6 p-6 grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Return Number</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challanReturn->return_number }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Party Details</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challanReturn->challan->party_name ?: 'Unknown Party' }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Return Date (Locked)</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challanReturn->return_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Vehicle / Courier (Locked)</div>
                    <div class="text-sm font-bold text-gray-800">{{ $challanReturn->vehicle_number ?: 'N/A' }}</div>
                </div>
            </div>

            {{-- 2. EDITABLE META DETAILS --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Editable Logistics</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
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
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Received By (Staff)</label>
                        <input type="text" name="received_by" x-model="formData.received_by"
                            class="w-full border border-gray-300 rounded px-3 py-2.5 text-sm focus:border-brand-500 outline-none">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Return Notes</label>
                        <textarea name="notes" x-model="formData.notes" rows="2" placeholder="Any specific details regarding this return..."
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none resize-none"></textarea>
                    </div>

                </div>
            </div>

            {{-- 3. RETURN ITEMS TABLE --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-gray-800 tracking-tight">Returned Items</h2>
                    <div class="text-xs font-bold text-gray-500">
                        * Quantities are locked. Only damage notes can be edited.
                    </div>
                </div>

                <div class="overflow-x-auto min-h-[200px]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 min-w-[250px]">PRODUCT DETAILS</th>
                                <th class="px-4 py-4 w-[140px] text-center bg-gray-50">QTY RETURNED</th>
                                <th class="px-4 py-4 w-[140px] text-center bg-red-50/50 border-x border-red-100">QTY DAMAGED</th>
                                <th class="px-6 py-4 w-[350px]">DAMAGE NOTES (Editable)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    
                                    {{-- Hidden ID for Update --}}
                                    <input type="hidden" :name="'items[' + index + '][id]'" :value="item.id">

                                    {{-- Product Info --}}
                                    <td class="px-6 py-4">
                                        <div class="text-[13px] font-bold text-gray-800" x-text="item.product_name"></div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-gray-500 font-mono" x-text="'SKU: ' + item.sku_code"></span>
                                        </div>
                                    </td>

                                    {{-- Returned Qty (Locked) --}}
                                    <td class="px-4 py-4 text-center bg-gray-50">
                                        <span class="text-[14px] font-black text-gray-800" x-text="item.qty_returned"></span>
                                    </td>

                                    {{-- Damaged Qty (Locked) --}}
                                    <td class="px-4 py-4 text-center bg-red-50/20 border-x border-red-50">
                                        <span class="text-[14px] font-black" :class="item.qty_damaged > 0 ? 'text-red-600' : 'text-gray-400'" x-text="item.qty_damaged"></span>
                                    </td>

                                    {{-- Damage Notes (Editable) --}}
                                    <td class="px-6 py-4">
                                        <input type="text" :name="'items[' + index + '][damage_note]'" x-model="item.damage_note" 
                                            :placeholder="item.qty_damaged > 0 ? 'Update damage reason...' : 'No damage reported'"
                                            :disabled="parseFloat(item.qty_damaged) <= 0"
                                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:border-brand-500 outline-none disabled:bg-gray-50 disabled:border-gray-100 disabled:text-gray-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- 4. FOOTER TOTALS (Static) --}}
            <div class="flex justify-end mb-10">
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-5 w-full md:w-[350px]">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-semibold text-gray-600">Total Returned:</span>
                        <span class="text-lg font-black text-gray-800">{{ (float) $challanReturn->total_qty_returned }}</span>
                    </div>
                    <div class="flex justify-between items-center text-red-600 mb-3">
                        <span class="text-sm font-bold">Total Damaged:</span>
                        <span class="text-base font-black">{{ (float) $challanReturn->total_qty_damaged }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 flex justify-between items-center text-green-700">
                        <span class="text-sm font-bold uppercase">Clean Stock:</span>
                        <span class="text-xl font-black">{{ (float) $challanReturn->total_qty_clean }}</span>
                    </div>
                </div>
            </div>

            {{-- 🌟 BOTTOM ACTION BUTTONS --}}
            <div class="bg-white border border-gray-200 p-5 rounded-lg flex flex-col sm:flex-row justify-end items-stretch sm:items-center gap-4 shadow-sm mb-6">
                <a href="{{ route('admin.challan-returns.show', $challanReturn->id) }}"
                    class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-50 transition-colors text-center">
                    CANCEL
                </a>
                <button type="submit"
                    class="bg-brand-500 hover:bg-brand-600 text-white px-8 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Updates
                </button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function returnEditForm(returnRecord, rawItems) {
            return {
                formData: {
                    condition: returnRecord.condition || 'good',
                    received_by: returnRecord.received_by || '',
                    notes: returnRecord.notes || ''
                },
                
                items: [],

                init() {
                    // Map items specifically for display and editing notes
                    this.items = rawItems.map(item => {
                        return {
                            id: item.id,
                            product_name: item.challan_item?.product_name || 'Unknown',
                            sku_code: item.challan_item?.sku_code || 'N/A',
                            qty_returned: parseFloat(item.qty_returned),
                            qty_damaged: parseFloat(item.qty_damaged),
                            damage_note: item.damage_note || '',
                        };
                    });
                }
            }
        }
    </script>
@endpush