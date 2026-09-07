@extends('layouts.admin')

@section('title', 'Customer Ledger')

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Customer Ledger</h1>
@endsection

@section('content')
<div class="pb-10">

    {{-- Flash Messages --}}
    @if (session('success'))
        <script>document.addEventListener('DOMContentLoaded', () => BizAlert.toast("{{ session('success') }}", 'success'));</script>
    @endif

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Customer Ledger</h2>
            <p class="text-sm text-gray-500 mt-0.5">View outstanding balances and payment history for each customer.</p>
        </div>
    </div>

    {{-- ── FILTER BAR ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <form method="GET" action="{{ route('admin.ledger.index') }}" class="flex flex-col sm:flex-row gap-3">
            {{-- Search --}}
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search customer name, phone, email..."
                    class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-700 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all placeholder-gray-400">
            </div>

            {{-- Store Filter --}}
            @if($stores->count() > 1)
            <select name="store_id"
                class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:border-[#108c2a] outline-none bg-white min-w-[160px]">
                <option value="">All Stores</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ ($filters['store_id'] ?? '') == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
            @endif

            <button type="submit"
                class="bg-[#108c2a] hover:bg-[#0d7523] text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm shrink-0">
                Search
            </button>

            @if(request()->hasAny(['search', 'store_id']))
                <a href="{{ route('admin.ledger.index') }}"
                    class="bg-red-50 hover:bg-red-100 text-red-500 w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition-colors" title="Clear Filters">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            @endif
        </form>
    </div>

    {{-- ── CUSTOMER TABLE ── --}}
    <div class="bg-transparent lg:bg-white lg:rounded-xl lg:shadow-sm lg:border lg:border-gray-100 lg:overflow-hidden">
        @if($customers->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <i data-lucide="users" class="w-14 h-14 mb-4 text-gray-200"></i>
                <p class="text-base font-semibold text-gray-500">No customers found</p>
                <p class="text-sm mt-1">Try adjusting your search or filters.</p>
            </div>
        @else
            <div class="overflow-x-auto lg:overflow-visible">
                <table class="w-full text-sm text-left block lg:table">
                    <thead class="hidden lg:table-header-group">
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Invoiced</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Total Paid</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Outstanding</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Invoices</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y lg:divide-y-0 divide-gray-100 block lg:table-row-group">
                        @foreach($customers as $index => $customer)
                            @php
                                $invoiced  = (float) ($customer->total_invoiced ?? 0);
                                $paid      = (float) ($paidTotals[$customer->id] ?? 0);
                                $balance   = $invoiced - $paid;
                                $isCredit  = $balance < 0;
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors flex flex-col lg:table-row border border-gray-200 lg:border-b lg:border-gray-50 lg:border-x-0 lg:border-t-0 mb-4 lg:mb-0 bg-white shadow-sm lg:shadow-none rounded-xl lg:rounded-none">
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 text-gray-400 text-xs flex justify-between lg:table-cell items-center bg-gray-50 lg:bg-transparent rounded-t-xl lg:rounded-none">
                                    <span class="lg:hidden font-bold text-gray-500 uppercase tracking-wider">#</span>
                                    <span>{{ $customers->firstItem() + $index }}</span>
                                </td>
                                <td class="px-4 lg:px-5 py-3 lg:py-3.5 flex flex-col lg:table-cell gap-1 lg:gap-0 border-b border-gray-50 lg:border-none">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-[#108c2a]/10 flex items-center justify-center text-[#108c2a] font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $customer->name }}</p>
                                            @if($customer->company_name)
                                                <p class="text-xs text-gray-400">{{ $customer->company_name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 flex justify-between lg:table-cell items-center border-b border-gray-50 lg:border-none">
                                    <span class="lg:hidden font-bold text-xs text-gray-500 uppercase">Contact</span>
                                    <div class="text-right lg:text-left">
                                        <p class="text-gray-700">{{ $customer->phone ?? '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $customer->email ?? '' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 flex justify-between lg:table-cell items-center lg:text-right border-b border-gray-50 lg:border-none">
                                    <span class="lg:hidden font-bold text-xs text-gray-500 uppercase">Total Invoiced</span>
                                    <span class="font-semibold text-gray-800">₹{{ number_format($invoiced, 2) }}</span>
                                </td>
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 flex justify-between lg:table-cell items-center lg:text-right border-b border-gray-50 lg:border-none">
                                    <span class="lg:hidden font-bold text-xs text-gray-500 uppercase">Total Paid</span>
                                    <span class="font-semibold text-green-600">₹{{ number_format($paid, 2) }}</span>
                                </td>
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 flex justify-between lg:table-cell items-center lg:text-right border-b border-gray-50 lg:border-none">
                                    <span class="lg:hidden font-bold text-xs text-gray-500 uppercase">Outstanding</span>
                                    @if($balance <= 0)
                                        <span class="inline-flex items-center gap-1 font-bold text-green-600">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Cleared
                                        </span>
                                    @else
                                        <span class="font-bold text-red-600">₹{{ number_format($balance, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 lg:px-5 py-2.5 lg:py-3.5 flex justify-between lg:table-cell items-center lg:text-center border-b border-gray-50 lg:border-none">
                                    <span class="lg:hidden font-bold text-xs text-gray-500 uppercase">Invoices</span>
                                    <span class="bg-blue-50 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                        {{ $customer->invoices_count }}
                                    </span>
                                </td>
                                <td class="px-4 lg:px-5 py-4 lg:py-3.5 flex justify-center lg:table-cell lg:text-center">
                                    <a href="{{ route('admin.ledger.show', $customer->id) }}"
                                        class="inline-flex items-center justify-center gap-1.5 w-full lg:w-auto bg-[#108c2a] hover:bg-[#0d7523] text-white text-xs font-bold px-3 py-2.5 lg:py-1.5 rounded-lg transition-colors shadow-sm">
                                        <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                                        Ledger
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
                <div class="px-5 py-4 lg:border-t border-gray-100 bg-white lg:bg-gray-50 rounded-xl lg:rounded-none shadow-sm lg:shadow-none">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection