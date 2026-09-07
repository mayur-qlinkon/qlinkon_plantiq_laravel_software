@extends('layouts.admin')

@section('title', 'Credit Notes & Returns')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Sales Returns</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="invoiceReturnIndex()">

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

       

        {{-- 🌟 SYSTEM CONSISTENCY FIX: The Unified Container Structure --}}
        {{-- SEARCH & FILTER BAR --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 p-4 border-b-0">
            <form id="invoice-return-filter-form" action="{{ route('admin.invoice-returns.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3"
                @submit.prevent="submitForm"
                @change="submitForm">

                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex flex-row items-center gap-2 flex-1 max-w-lg w-full">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search CN Number, Customer..."
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

                <div x-data="{ filterOpen: false }" @click.away="filterOpen = false" class="relative">
                    <button type="button" @click="filterOpen = !filterOpen"
                        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 h-full">
                        <i data-lucide="filter" class="w-4 h-4 text-gray-500"></i> Filters
                        @if (request('status') || request('return_type'))
                            <span class="w-2 h-2 rounded-full bg-red-500 ml-1"></span>
                        @endif
                    </button>

                    <div x-show="filterOpen" x-cloak x-transition
                        class="absolute right-0 mt-2 w-64 bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-4">

                        <div class="mb-3">
                            <label
                                class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                            <select name="status"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-gray-500 outline-none bg-white">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed
                                </option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Return
                                Type</label>
                            <select name="return_type"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:border-gray-500 outline-none bg-white">
                                <option value="">All Types</option>
                                <option value="refund" {{ request('return_type') == 'refund' ? 'selected' : '' }}>Refund
                                </option>
                                <option value="credit_note" {{ request('return_type') == 'credit_note' ? 'selected' : '' }}>
                                    Credit Note</option>
                                <option value="replacement" {{ request('return_type') == 'replacement' ? 'selected' : '' }}>
                                    Replacement</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.invoice-returns.index') }}"
                                class="px-3 py-1.5 text-xs font-bold text-gray-500 hover:text-gray-800 transition-colors">Clear</a>
                            <button type="submit"
                                class="bg-[#212538] text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-black transition-colors">Apply</button>
                        </div>
                    </div>
                </div>
                
            </form>
        </div>

        {{-- DATA TABLE --}}
        <div id="returns-list-container" class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">CN DETAILS</th>
                            <th class="px-6 py-4">ORIGINAL INVOICE</th>
                            <th class="px-6 py-4">CUSTOMER</th>
                            <th class="px-6 py-4 text-center">TYPE</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-right">TOTAL REFUND</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($returns as $return)
                            <tr class="hover:bg-gray-50/50 transition-colors group">

                                {{-- CN Details --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.invoice-returns.show', $return->id) }}"
                                            class="font-extrabold text-gray-900 text-[13px] hover:underline">
                                            {{ $return->credit_note_number }}
                                        </a>
                                        <span class="text-[11px] text-gray-500 mt-0.5 font-medium">
                                            {{ \Carbon\Carbon::parse($return->return_date)->format('d M Y') }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Original Invoice --}}
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.invoices.show', $return->invoice_id) }}"
                                        class="font-mono text-[12px] font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $return->invoice->invoice_number ?? 'Unknown' }}
                                    </a>
                                </td>

                                {{-- Customer --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 text-[13px]">
                                            {{ $return->customer_name }}
                                        </span>
                                        @if ($return->customer && $return->customer->phone)
                                            <span class="text-[11px] text-gray-400 mt-0.5 font-bold tracking-tighter">
                                                {{ $return->customer->phone }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $typeColors = [
                                            'refund' => 'text-purple-600',
                                            'credit_note' => 'text-indigo-600',
                                            'replacement' => 'text-orange-600',
                                        ];
                                        $color = $typeColors[$return->return_type] ?? 'text-gray-600';
                                    @endphp
                                    <span class="text-[10px] font-black uppercase tracking-widest {{ $color }}">
                                        {{ str_replace('_', ' ', $return->return_type) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                                            'confirmed' => 'bg-green-50 text-green-700 border-green-200',
                                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                                        ];
                                        $color = $statusColors[$return->status] ?? $statusColors['draft'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                        {{ $return->status }}
                                    </span>
                                </td>

                                {{-- Total Refund --}}
                                <td class="px-6 py-4 text-right">
                                    <span
                                        class="font-extrabold text-[#108c2a]">₹{{ number_format($return->grand_total, 2) }}</span>
                                    @if ($return->status === 'confirmed' && $return->restock)
                                        <div class="text-[10px] text-gray-400 font-bold mt-0.5 uppercase tracking-wide">
                                            Stock Updated
                                        </div>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 transition-opacity">

                                        @if(has_permission('invoice_returns.view'))
                                        <a href="{{ route('admin.invoice-returns.show', $return->id) }}"
                                            class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                            title="View Credit Note">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @endif

                                        @if ($return->status === 'draft')
                                            @if(has_permission('invoice_returns.update'))
                                            <a href="{{ route('admin.invoice-returns.edit', $return->id) }}"
                                                class="w-8 h-8 rounded border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors"
                                                title="Edit Draft">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </a>
                                            @endif

                                            @if(has_permission('invoice_returns.delete'))
                                            <form action="{{ route('admin.invoice-returns.destroy', $return->id) }}"
                                                method="POST" class="inline-block"
                                                onsubmit="event.preventDefault(); BizAlert.confirm('Delete Draft?', 'Are you sure you want to delete this draft credit note?').then((result) => { if(result.isConfirmed) this.submit(); });">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors"
                                                    title="Delete Draft">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="file-x-2" class="w-10 h-10 mb-3 opacity-20"></i>
                                        <p class="text-sm font-medium">No Credit Notes Found</p>
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
                        $typeColors = [
                            'refund' => 'text-purple-600',
                            'credit_note' => 'text-indigo-600',
                            'replacement' => 'text-orange-600',
                        ];
                        $tColor = $typeColors[$return->return_type] ?? 'text-gray-600';

                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'confirmed' => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                        ];
                        $sColor = $statusColors[$return->status] ?? $statusColors['draft'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Customer & Total --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-800 text-[14px] truncate">
                                    {{ $return->customer_name }}
                                </p>
                                @if ($return->customer && $return->customer->phone)
                                    <p class="text-[11px] text-gray-400 mt-0.5 font-bold tracking-tighter truncate">
                                        {{ $return->customer->phone }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-black text-[#108c2a] text-[15px]">₹{{ number_format($return->grand_total, 2) }}</span>
                            </div>
                        </div>

                        {{-- Details & Badges --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <a href="{{ route('admin.invoice-returns.show', $return->id) }}" class="font-extrabold text-gray-900 text-[13px] hover:underline">
                                    {{ $return->credit_note_number }}
                                </a>
                                <span class="text-[11px] text-gray-500 font-medium">
                                    {{ \Carbon\Carbon::parse($return->return_date)->format('d M Y') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100/50">
                                <a href="{{ route('admin.invoices.show', $return->invoice_id) }}" class="text-[10px] text-blue-600 hover:text-blue-800 font-bold uppercase tracking-wider">
                                    Inv: {{ $return->invoice->invoice_number ?? 'Unknown' }}
                                </a>
                                <span class="text-gray-300">|</span>
                                <span class="text-[9px] font-black uppercase tracking-widest {{ $tColor }}">
                                    {{ str_replace('_', ' ', $return->return_type) }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider border {{ $sColor }} ml-auto">
                                    {{ $return->status }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1">
                            @if(has_permission('invoice_returns.view'))
                                <a href="{{ route('admin.invoice-returns.show', $return->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="View Credit Note">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if ($return->status === 'draft')
                                @if(has_permission('invoice_returns.update'))
                                    <a href="{{ route('admin.invoice-returns.edit', $return->id) }}" class="w-8 h-8 rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Edit Draft">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                @endif

                                @if(has_permission('invoice_returns.delete'))
                                    <form action="{{ route('admin.invoice-returns.destroy', $return->id) }}" method="POST" class="inline-block" onsubmit="event.preventDefault(); BizAlert.confirm('Delete Draft?', 'Are you sure you want to delete this draft credit note?').then((result) => { if(result.isConfirmed) this.submit(); });">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors" title="Delete Draft">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="file-x-2" class="w-10 h-10 mb-3 opacity-20"></i>
                            <p class="font-medium text-gray-500">No Credit Notes Found</p>
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
        function invoiceReturnIndex() {
            return {
                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitReturnForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('invoice-return-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('invoice-return-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => { if (v) url.searchParams.set(k, v); });
                    
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('invoice-return-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach(el => el.value = '');                
                    }
                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('returns-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('returns-list-container');
                            
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
