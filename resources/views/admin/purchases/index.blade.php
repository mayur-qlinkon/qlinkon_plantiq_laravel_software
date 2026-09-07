@extends('layouts.admin')

@section('title', 'Purchases - ' . config('app.name'))

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Purchases</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="purchaseIndex()">

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
            <form id="purchase-filter-form" action="{{ route('admin.purchases.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full"
                @submit.prevent="submitForm"
                @change="submitForm">

                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex flex-row items-center gap-2 flex-1 min-w-[250px] max-w-md w-full">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search PO Number, Supplier..."
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

                <div class="w-full lg:w-auto shrink-0">
                    <x-custom-select
                        name="status"
                        placeholder="All Statuses"
                        :options="[
                            'draft' => 'Draft', 
                            'ordered' => 'Ordered', 
                            'partially_received' => 'Partially Received', 
                            'received' => 'Received', 
                            'cancelled' => 'Cancelled'
                        ]"
                        selected="{{ request('status') }}"
                    />
                </div>

                <div class="w-full lg:w-auto shrink-0">
                    <x-custom-select
                        name="payment_status"
                        placeholder="All Payments"
                        :options="[
                            'unpaid' => 'Unpaid', 
                            'partial' => 'Partial', 
                            'paid' => 'Paid'
                        ]"
                        selected="{{ request('payment_status') }}"
                    />
                </div>

                <div class="flex items-center justify-between gap-1.5 shrink-0 w-full sm:w-auto">
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        max="{{ request('end_date') ?: now()->format('Y-m-d') }}"
                        title="From date"
                        class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:border-[#108c2a] outline-none bg-white w-[130px]">
                    <span class="text-gray-400 text-xs shrink-0">to</span>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        min="{{ request('start_date') }}"
                        max="{{ now()->format('Y-m-d') }}"
                        title="To date"
                        class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:border-[#108c2a] outline-none bg-white w-[130px]">
                </div>

              {{-- 3. Create PO Button (Pushed to the right) --}}
                @if(has_permission('purchases.create'))
                <div class="ml-auto flex shrink-0 w-full sm:w-auto">
                    <a href="{{ route('admin.purchases.create') }}"
                        class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create PO
                    </a>
                </div>
                @endif
            </form>

            @if(! is_null($filteredCount))
                <div class="px-1 pt-3 text-xs sm:text-sm text-gray-600">
                    <span class="font-bold text-gray-800">{{ number_format($filteredCount) }}</span>
                    purchase{{ $filteredCount === 1 ? '' : 's' }} found
                    @if(request('start_date') && request('end_date'))
                        between <span class="font-semibold">{{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}</span>
                        and <span class="font-semibold">{{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</span>
                    @elseif(request('start_date'))
                        from <span class="font-semibold">{{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}</span>
                    @elseif(request('end_date'))
                        up to <span class="font-semibold">{{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</span>
                    @endif
                </div>
            @endif
        </div>

        {{-- DATA TABLE --}}
        <div id="purchases-list-container" class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">PO DETAILS</th>
                            <th class="px-6 py-4 hidden md:table-cell">SUPPLIER</th>
                            <th class="px-6 py-4 hidden md:table-cell">DESTINATION</th>
                            <th class="px-6 py-4 text-center hidden md:table-cell">STATUS</th>
                            <th class="px-6 py-4 text-center">PAYMENT</th>
                            <th class="px-6 py-4 text-right">TOTAL AMOUNT</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($purchases as $purchase)
                            <tr class="hover:bg-gray-50/50 transition-colors group">

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.purchases.show', $purchase->id) }}"
                                            class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                        <span class="text-[11px] text-gray-500 mt-0.5 font-medium">
                                            {{ $purchase->purchase_date->format('d M, Y') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-bold text-gray-800 text-[13px]">{{ $purchase->supplier->name ?? 'Unknown' }}</span>
                                        @if ($purchase->supplier_invoice_number)
                                            <span class="text-[11px] text-gray-400 mt-0.5 font-mono">Inv:
                                                {{ $purchase->supplier_invoice_number }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-semibold text-gray-700 text-[12px]">{{ $purchase->warehouse->name ?? 'N/A' }}</span>
                                        @if ($purchase->store)
                                            <span
                                                class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">{{ $purchase->store->name }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center hidden md:table-cell">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                                            'ordered' => 'bg-blue-50 text-blue-600 border-blue-200',
                                            'partially_received' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                            'received' => 'bg-green-50 text-green-700 border-green-200',
                                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                                        ];
                                        $color = $statusColors[$purchase->status] ?? $statusColors['draft'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                        {{ str_replace('_', ' ', $purchase->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @php
                                        $payColors = [
                                            'unpaid' => 'bg-red-50 text-red-600',
                                            'partial' => 'bg-orange-50 text-orange-600',
                                            'paid' => 'bg-green-50 text-green-700',
                                        ];
                                        $pColor = $payColors[$purchase->payment_status] ?? $payColors['unpaid'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $pColor }}">
                                        {{ $purchase->payment_status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span
                                            class="font-extrabold text-gray-800">₹{{ number_format($purchase->total_amount, 2) }}</span>
                                        @if ($purchase->balance_amount > 0)
                                            <span class="text-[10px] font-bold text-red-500 mt-0.5">Bal:
                                                ₹{{ number_format($purchase->balance_amount, 2) }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 transition-opacity">

                                        @if(has_permission('purchases.view'))
                                        <a href="{{ route('admin.purchases.show', $purchase->id) }}"
                                            class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                            title="View PO">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @endif

                                        @if ($purchase->status !== 'received' && $purchase->status !== 'cancelled' && has_permission('purchases.update'))
                                            <a href="{{ route('admin.purchases.edit', $purchase->id) }}"
                                                class="w-8 h-8 rounded border border-blue-200 text-blue-600 hover:bg-blue-50 flex items-center justify-center transition-colors"
                                                title="Edit PO">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                        @endif

                                        @if ($purchase->status === 'received' && has_permission('purchase_returns.create'))
                                            <a href="{{ route('admin.purchase-returns.create', ['purchase_id' => $purchase->id]) }}"
                                                class="w-8 h-8 rounded border border-orange-200 text-orange-500 hover:bg-orange-50 flex items-center justify-center transition-colors"
                                                title="Create Return">
                                                <i data-lucide="undo-2" class="w-4 h-4"></i>
                                            </a>
                                        @endif

                                        @if ($purchase->payment_status !== 'paid' && $purchase->status === 'received' && has_permission('purchases.add_payment'))
                                            <button type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-quick-payment', { detail: { dueAmount: {{ (float) $purchase->balance_amount }}, action: '{{ route('admin.purchases.pay', $purchase->id) }}' } }))"
                                                class="w-8 h-8 rounded border border-green-200 text-green-600 hover:bg-green-50 flex items-center justify-center transition-colors"
                                                title="Record Payment">
                                                <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                                            </button>
                                        @endif

                                        @if ($purchase->status !== 'received')
                                            @if (has_permission('purchases.delete'))
                                                <form action="{{ route('admin.purchases.destroy', $purchase->id) }}"
                                                    method="POST" @submit.prevent="confirmDelete($event.target)"
                                                    class="inline-block">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="w-8 h-8 rounded border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors"
                                                        title="Delete PO">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <div class="w-8 h-8 rounded border border-gray-100 text-gray-300 flex items-center justify-center cursor-not-allowed"
                                                title="Cannot delete received stock">
                                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                            </div>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="shopping-cart" class="w-10 h-10 mb-3 opacity-20"></i>
                                        <p class="text-sm font-medium">No purchase orders found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse ($purchases as $purchase)
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'ordered' => 'bg-blue-50 text-blue-600 border-blue-200',
                            'partially_received' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            'received' => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                        ];
                        $color = $statusColors[$purchase->status] ?? $statusColors['draft'];
                        
                        $payColors = [
                            'unpaid' => 'bg-red-50 text-red-600',
                            'partial' => 'bg-orange-50 text-orange-600',
                            'paid' => 'bg-green-50 text-green-700',
                        ];
                        $pColor = $payColors[$purchase->payment_status] ?? $payColors['unpaid'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Supplier & Total --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-gray-800 text-[14px] truncate">
                                    {{ $purchase->supplier->name ?? 'Unknown' }}
                                </p>
                                @if ($purchase->supplier_invoice_number)
                                    <p class="text-[11px] text-gray-400 mt-0.5 font-mono truncate">
                                        Inv: {{ $purchase->supplier_invoice_number }}
                                    </p>
                                @else
                                    <p class="text-[11px] text-gray-400 mt-0.5 font-medium truncate">
                                        {{ $purchase->warehouse->name ?? 'N/A' }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end">
                                <span class="font-black text-gray-800 text-[15px]">₹{{ number_format($purchase->total_amount, 2) }}</span>
                                @if ($purchase->balance_amount > 0)
                                    <span class="text-[10px] font-bold text-red-500 mt-0.5">Bal: ₹{{ number_format($purchase->balance_amount, 2) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- PO Details & Badges --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                    {{ $purchase->purchase_number }}
                                </a>
                                <span class="text-[11px] text-gray-500 font-medium">
                                    {{ $purchase->purchase_date->format('d M, Y') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100/50">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                    {{ str_replace('_', ' ', $purchase->status) }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider {{ $pColor }}">
                                    {{ $purchase->payment_status }}
                                </span>
                                @if($purchase->store)
                                    <span class="text-gray-300">|</span>
                                    <span class="text-[9px] text-gray-500 uppercase tracking-widest font-semibold">
                                        {{ $purchase->store->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 flex-wrap">
                            @if(has_permission('purchases.view'))
                                <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="View PO">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if ($purchase->status !== 'received' && $purchase->status !== 'cancelled' && has_permission('purchases.update'))
                                <a href="{{ route('admin.purchases.edit', $purchase->id) }}" class="w-8 h-8 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Edit PO">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if ($purchase->status === 'received' && has_permission('purchase_returns.create'))
                                <a href="{{ route('admin.purchase-returns.create', ['purchase_id' => $purchase->id]) }}"
                                    class="w-8 h-8 rounded-lg border border-orange-200 text-orange-500 hover:bg-orange-50 flex items-center justify-center transition-colors"
                                    title="Create Return">
                                    <i data-lucide="undo-2" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if ($purchase->payment_status !== 'paid' && $purchase->status === 'received' && has_permission('purchases.add_payment'))
                                <button type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('open-quick-payment', { detail: { dueAmount: {{ (float) $purchase->balance_amount }}, action: '{{ route('admin.purchases.pay', $purchase->id) }}' } }))"
                                    class="w-8 h-8 rounded-lg border border-green-200 text-green-600 hover:bg-green-50 flex items-center justify-center transition-colors"
                                    title="Record Payment">
                                    <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if ($purchase->status !== 'received')
                                @if (has_permission('purchases.delete'))
                                    <form action="{{ route('admin.purchases.destroy', $purchase->id) }}" method="POST" @submit.prevent="confirmDelete($event.target)" class="inline-block m-0 p-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors" title="Delete PO">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <div class="w-8 h-8 rounded-lg border border-gray-100 text-gray-300 flex items-center justify-center cursor-not-allowed" title="Cannot delete received stock">
                                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="shopping-cart" class="w-10 h-10 mb-3 opacity-20"></i>
                            <p class="font-medium text-gray-500 text-[13px]">No purchase orders found.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($purchases->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $purchases->links() }}
                </div>
            @endif
        </div>
        {{-- Single shared quick-payment modal — opened dynamically per row via $dispatch --}}
    <x-modals.quick-payment
        action="#"
        :due-amount="0"
        :total-amount="0"
        :paid-amount="0"
        :payment-methods="$paymentMethods"
        title="Record Supplier Payment"
        subtitle="Payment will be recorded against this purchase order."
    />

    </div>
@endsection

@push('scripts')
    <script>
        function purchaseIndex() {
            return {
                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitPurchaseForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('purchase-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('purchase-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => { if (v) url.searchParams.set(k, v); });
                    
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('purchase-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], input[type="date"], select').forEach(el => el.value = '');                
                    }
                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('purchases-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('purchases-list-container');
                            
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
                },

                

                confirmDelete(form) {
                    BizAlert.confirm(
                        'Delete Purchase Order?',
                        'This action cannot be undone. Any drafted items will be permanently removed.',
                        'Yes, Delete'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading('Deleting...');
                            form.submit();
                        }
                    });
                }
            }
        }
    </script>
@endpush
