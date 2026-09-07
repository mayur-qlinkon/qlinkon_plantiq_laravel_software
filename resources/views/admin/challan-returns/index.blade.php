@extends('layouts.admin')

@section('title', 'Challan Returns')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">CHALLAN RETURNS</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="challanReturnIndex()">

        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('success') }}", 'success'));
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('error') }}", 'error'));
            </script>
        @endif    

        {{-- SEARCH & FILTER BAR --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 p-4 border-b-0">
            <form id="challan-return-filter-form" action="{{ route('admin.challan-returns.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3"
                @submit.prevent="submitForm"
                @change="submitForm">

                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex flex-row items-center gap-2 flex-1 max-w-lg w-full">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search Return No, Challan No, Party..."
                            @input.debounce.400ms="submitForm"
                            class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-700 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all placeholder-gray-400">
                    </div>

                    <button type="button"
                        @click="clearFilters"
                        x-show="hasActiveFilters" x-cloak
                        class="bg-red-50 hover:bg-red-100 text-red-500 px-3 py-2.5 rounded-lg text-sm font-bold transition-colors shrink-0 flex items-center gap-1.5"
                        title="Clear Filters">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
                    </button>
                </div>

                {{-- 2. Filters Dropdown --}}
                <div @click.away="filterOpen = false" class="relative">
                    <button type="button" @click="filterOpen = !filterOpen"
                        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 h-full">
                        <i data-lucide="filter" class="w-4 h-4 text-gray-500"></i> Filters
                        @if (request('condition'))
                            <span class="w-2 h-2 rounded-full bg-red-500 ml-1"></span>
                        @endif
                    </button>

                    <div x-show="filterOpen" x-cloak x-transition
                        class="absolute right-0 mt-2 w-64 bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-4">

                        <div class="mb-4">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Return Condition</label>
                            <select name="condition" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-[#108c2a] outline-none bg-white">
                                <option value="">All Conditions</option>
                                <option value="good" {{ request('condition') == 'good' ? 'selected' : '' }}>Good Condition</option>
                                <option value="partial" {{ request('condition') == 'partial' ? 'selected' : '' }}>Partial Return</option>
                                <option value="damaged" {{ request('condition') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.challan-returns.index') }}"
                                class="px-3 py-1.5 text-xs font-bold text-gray-500 hover:text-gray-800 transition-colors">Clear</a>
                            <button type="submit"
                                class="bg-[#108c2a] text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-[#0c6b1f] transition-colors">Apply</button>
                        </div>
                    </div>
                </div>

               {{-- 3. Initiate Return Button (Redirects to Challans index) --}}
                <div class="ml-auto flex shrink-0 w-full sm:w-auto mt-2 sm:mt-0">
                    @if(has_permission('challan_returns.create'))
                        <a href="{{ route('admin.challans.index') }}"
                            class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
                            <i data-lucide="undo-2" class="w-4 h-4"></i> Initiate Return
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- DATA TABLE --}}
        <div id="challan-returns-list-container" class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">RETURN DETAILS</th>
                            <th class="px-6 py-4">ORIGINAL CHALLAN</th>
                            <th class="px-6 py-4">PARTY</th>
                            <th class="px-6 py-4 text-center hidden md:table-cell">CONDITION</th>
                            <th class="px-6 py-4 text-center hidden md:table-cell">QTY RETURNED</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($returns as $return)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                
                                {{-- Return Details --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.challan-returns.show', $return->id) }}"
                                            class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                            {{ $return->return_number }}
                                        </a>
                                        <span class="text-[11px] text-gray-500 mt-0.5 font-medium flex items-center gap-1">
                                            <i data-lucide="calendar" class="w-3 h-3"></i> {{ $return->return_date->format('d M, Y') }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Challan Ref --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        @if($return->challan)
                                            <a href="{{ route('admin.challans.show', $return->challan_id) }}" 
                                               class="font-bold text-gray-800 text-[13px] hover:text-blue-600 transition-colors">
                                                {{ $return->challan->challan_number }}
                                            </a>
                                            <span class="text-[10px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                                {{ $return->challan->type_label }}
                                            </span>
                                        @else
                                            <span class="font-bold text-red-400 text-[13px] italic">
                                                Challan Deleted
                                            </span>
                                            <span class="text-[10px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                                Ref ID: {{ $return->challan_id }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Party --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 text-[13px] truncate max-w-[200px]">
                                            {{ $return->challan?->party_name ?? 'Unknown (Record Missing)' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Condition Badge --}}
                                <td class="px-6 py-4 text-center hidden md:table-cell">
                                    @php
                                        $colorMap = [
                                            'green' => 'bg-green-50 text-green-700 border-green-200',
                                            'red'   => 'bg-red-50 text-red-600 border-red-200',
                                            'amber' => 'bg-amber-50 text-amber-600 border-amber-200',
                                            'gray'  => 'bg-gray-50 text-gray-600 border-gray-200',
                                        ];
                                        $c = $colorMap[$return->condition_color] ?? $colorMap['gray'];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $c }}">
                                        {{ $return->condition_label }}
                                    </span>
                                </td>

                                {{-- Qty Returned / Damaged --}}
                                <td class="px-6 py-4 text-center hidden md:table-cell">
                                    <div class="flex flex-col items-center">
                                        <span class="font-black text-gray-800 text-[14px]">
                                            {{ (float) $return->total_qty_returned }}
                                        </span>
                                        @if($return->total_qty_damaged > 0)
                                            <span class="text-[10px] font-bold text-red-500 bg-red-50 px-1.5 py-0.5 rounded mt-1">
                                                {{ (float) $return->total_qty_damaged }} Damaged
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">

                                        {{-- View Button --}}
                                        @if(has_permission('challan_returns.view'))
                                            <a href="{{ route('admin.challan-returns.show', $return->id) }}"
                                                class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                                title="View Return">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                        @endif

                                        {{-- Download PDF Button --}}
                                        @if(has_permission('challan_returns.download_pdf'))
                                            <a href="{{ route('admin.challan-returns.pdf', $return->id) }}" target="_blank"
                                                class="w-8 h-8 rounded border border-indigo-200 text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors"
                                                title="Download PDF">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </a>
                                        @endif

                                        {{-- Edit Button (Restricted attributes only based on Model rules) --}}
                                        @if(has_permission('challan_returns.update'))
                                            <a href="{{ route('admin.challan-returns.edit', $return->id) }}"
                                                class="w-8 h-8 rounded border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors"
                                                title="Edit Return Notes">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </a>
                                        @endif
                                        
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="undo-2" class="w-10 h-10 mb-3 opacity-20"></i>
                                        <p class="text-sm font-medium">No challan returns found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse ($returns as $return)
                    @php
                        $colorMap = [
                            'green' => 'bg-green-50 text-green-700 border-green-200',
                            'red'   => 'bg-red-50 text-red-600 border-red-200',
                            'amber' => 'bg-amber-50 text-amber-600 border-amber-200',
                            'gray'  => 'bg-gray-50 text-gray-600 border-gray-200',
                        ];
                        $c = $colorMap[$return->condition_color] ?? $colorMap['gray'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Return Number & Party --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.challan-returns.show', $return->id) }}" class="font-extrabold text-[#108c2a] text-[14px] hover:underline block truncate">
                                    {{ $return->return_number }}
                                </a>
                                <p class="font-bold text-gray-800 text-[12px] truncate mt-0.5">
                                    {{ $return->challan?->party_name ?? 'Unknown (Record Missing)' }}
                                </p>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider border {{ $c }} shrink-0">
                                {{ $return->condition_label }}
                            </span>
                        </div>

                        {{-- Details: Challan Ref & Quantities --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Ref Challan</span>
                                @if($return->challan)
                                    <a href="{{ route('admin.challans.show', $return->challan_id) }}" class="font-bold text-blue-600 text-[12px] hover:underline">
                                        {{ $return->challan->challan_number }}
                                    </a>
                                @else
                                    <span class="font-bold text-red-400 text-[12px] italic">Deleted (ID: {{ $return->challan_id }})</span>
                                @endif
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-100/50">
                                <div class="flex items-center gap-2 text-[11px] text-gray-600">
                                    <span class="font-medium text-gray-500">Returned:</span>
                                    <span class="font-black text-gray-800">{{ (float) $return->total_qty_returned }}</span>
                                </div>
                                @if($return->total_qty_damaged > 0)
                                    <span class="text-[9px] font-bold text-red-500 bg-red-50 px-1.5 py-0.5 rounded">
                                        {{ (float) $return->total_qty_damaged }} Damaged
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Footer: Date & Actions --}}
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-gray-400 font-medium flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3"></i> {{ $return->return_date->format('d M, Y') }}
                            </span>
                            <div class="flex items-center justify-end gap-2">
                                @if(has_permission('challan_returns.view'))
                                    <a href="{{ route('admin.challan-returns.show', $return->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="View Return">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                @endif

                                @if(has_permission('challan_returns.download_pdf'))
                                    <a href="{{ route('admin.challan-returns.pdf', $return->id) }}" target="_blank" class="w-8 h-8 rounded-lg border border-indigo-200 text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors" title="Download PDF">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                @endif

                                @if(has_permission('challan_returns.update'))
                                    <a href="{{ route('admin.challan-returns.edit', $return->id) }}" class="w-8 h-8 rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Edit Return Notes">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="undo-2" class="w-10 h-10 mb-3 opacity-20"></i>
                            <p class="font-medium text-gray-500 text-[13px]">No challan returns found.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($returns->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function challanReturnIndex() {
            return {
                // Modals and dropdowns
                filterOpen: false,

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitChallanReturnForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('challan-return-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('challan-return-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => { if (v) url.searchParams.set(k, v); });
                    
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('challan-return-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach(el => el.value = '');                
                    }
                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('challan-returns-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('challan-returns-list-container');
                            
                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = '1';
                            targetContainer.style.pointerEvents = 'auto';
                            window.history.pushState({}, '', url);

                            this.checkActiveFilters();
                            
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = '1';
                            targetContainer.style.pointerEvents = 'auto';
                        });
                }
            }
        }
    </script>
@endpush