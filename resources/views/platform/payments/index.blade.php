@extends('layouts.platform')

@section('title', 'Payment Logs')
@section('header', 'Payment Logs')

@section('styles')
<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .status-paid    { background: #d1fae5; color: #065f46; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-failed  { background: #fee2e2; color: #991b1b; }
    .status-refunded{ background: #e0e7ff; color: #3730a3; }

    .stat-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #f1f5f9;
        padding: 18px 20px;
        transition: box-shadow 0.2s;
    }
    .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 13px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        background: white;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
        color: #64748b;
    }
    .filter-chip:hover, .filter-chip.active {
        background: #0f766e;
        border-color: #0f766e;
        color: white;
    }

    .log-row {
        transition: background 0.12s;
    }
    .log-row:hover {
        background: #f8fafc;
    }

    .method-tag {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #f1f5f9;
        color: #475569;
    }
</style>
@endsection

@section('content')
<div class="space-y-5">

    {{-- ── Top: Title + Export ── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Payment Logs</h2>
            <p class="text-sm text-gray-400 mt-0.5">All Cashfree subscription payment attempts</p>
        </div>
        <a href="{{ route('platform.payments.index', array_merge(request()->query(), ['export' => 1])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
            <i class="fa-solid fa-download text-xs"></i>
            Export CSV
        </a>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="stat-card">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Collected</p>
            <p class="text-2xl font-extrabold text-gray-900">₹{{ number_format($stats['total_paid'], 2) }}</p>
            <p class="text-xs text-emerald-600 font-medium mt-1">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                {{ $stats['count_paid'] }} successful payments
            </p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Successful</p>
            <p class="text-2xl font-extrabold text-emerald-600">{{ $stats['count_paid'] }}</p>
            <div class="mt-1 h-1 rounded-full bg-gray-100 overflow-hidden">
                @php
                    $total_count = $stats['count_paid'] + $stats['count_pending'] + $stats['count_failed'];
                    $paid_pct = $total_count > 0 ? round($stats['count_paid'] / $total_count * 100) : 0;
                @endphp
                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $paid_pct }}%"></div>
            </div>
        </div>
        <div class="stat-card">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Pending</p>
            <p class="text-2xl font-extrabold text-amber-500">{{ $stats['count_pending'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Awaiting confirmation</p>
        </div>
        <div class="stat-card">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Failed</p>
            <p class="text-2xl font-extrabold text-red-500">{{ $stats['count_failed'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Incomplete attempts</p>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" action="{{ route('platform.payments.index') }}" class="flex flex-wrap gap-3 items-end">

            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">Search</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Order ID, Payment ID, Company…"
                        class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
            </div>

            {{-- Status --}}
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">Status</label>
                <select name="status"
                    class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 bg-white">
                    <option value="">All Status</option>
                    @foreach(['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">From</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">To</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-xl hover:bg-brand-700 transition">
                    <i class="fa-solid fa-filter text-xs mr-1"></i> Filter
                </button>
                @if(array_filter($filters))
                    <a href="{{ route('platform.payments.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Active filter chips --}}
        @if(array_filter($filters))
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-50">
            <span class="text-xs text-gray-400 font-medium self-center">Active:</span>
            @if(!empty($filters['q']))
                <span class="filter-chip active">
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    "{{ $filters['q'] }}"
                </span>
            @endif
            @if(!empty($filters['status']))
                <span class="filter-chip active">
                    <i class="fa-solid fa-circle text-[8px]"></i>
                    {{ ucfirst($filters['status']) }}
                </span>
            @endif
            @if(!empty($filters['from']) || !empty($filters['to']))
                <span class="filter-chip active">
                    <i class="fa-solid fa-calendar text-[10px]"></i>
                    {{ $filters['from'] ?? '…' }} → {{ $filters['to'] ?? 'now' }}
                </span>
            @endif
        </div>
        @endif
    </div>

    {{-- ── Table ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Results count --}}
        <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-400 font-medium">
                Showing <span class="text-gray-700 font-semibold">{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</span>
                of <span class="text-gray-700 font-semibold">{{ $logs->total() }}</span> records
            </p>
            <p class="text-xs text-gray-400">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Company</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Order / Payment ID</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Plan</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Amount</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Method</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                    <tr class="log-row">
                        {{-- Company --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-brand-500/10 text-brand-600 flex items-center justify-center shrink-0 text-xs font-bold">
                                    {{ strtoupper(substr($log->company->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 truncate max-w-[130px]">{{ $log->company->name ?? '—' }}</p>
                                    <p class="text-[11px] text-gray-400 truncate max-w-[130px]">{{ $log->company->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Order / Payment ID --}}
                        <td class="px-4 py-3.5">
                            <p class="font-mono text-xs font-semibold text-gray-700">{{ $log->cf_order_id }}</p>
                            @if($log->cf_payment_id)
                                <p class="font-mono text-[10px] text-gray-400 mt-0.5">{{ $log->cf_payment_id }}</p>
                            @else
                                <p class="text-[10px] text-gray-300 mt-0.5">No payment ID</p>
                            @endif
                        </td>

                        {{-- Plan --}}
                        <td class="px-4 py-3.5">
                            <p class="text-xs font-medium text-gray-600">{{ $log->plan->name ?? '—' }}</p>
                        </td>

                        {{-- Amount --}}
                        <td class="px-4 py-3.5 text-right">
                            <p class="font-bold text-gray-900">₹{{ number_format($log->amount, 2) }}</p>
                            <p class="text-[10px] text-gray-400">{{ $log->currency }}</p>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3.5 text-center">
                            <span class="status-badge status-{{ $log->status }}">
                                @if($log->status === 'paid') <i class="fa-solid fa-circle-check text-[9px]"></i>
                                @elseif($log->status === 'pending') <i class="fa-solid fa-clock text-[9px]"></i>
                                @elseif($log->status === 'failed') <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                @else <i class="fa-solid fa-rotate-left text-[9px]"></i>
                                @endif
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>

                        {{-- Method --}}
                        <td class="px-4 py-3.5">
                            @if($log->payment_method)
                                <span class="method-tag">
                                    {{ is_array($log->payment_method) ? ($log->payment_method['type'] ?? 'N/A') : $log->payment_method }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3.5">
                            <p class="text-xs text-gray-700 font-medium">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $log->created_at->format('h:i A') }}</p>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3.5 text-right">
                            <a href="{{ route('platform.payments.show', $log) }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-50 hover:bg-gray-100 text-xs font-semibold text-gray-600 transition">
                                View <i class="fa-solid fa-chevron-right text-[9px]"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-16">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-gray-50 grid place-items-center">
                                    <i class="fa-solid fa-receipt text-2xl text-gray-200"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-400">No payment logs found</p>
                                @if(array_filter($filters))
                                    <a href="{{ route('platform.payments.index') }}" class="text-xs text-brand-600 font-semibold hover:underline">Clear filters</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-5 py-4 border-t border-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                {{ $logs->total() }} total records
            </p>
            <div class="flex items-center gap-1">
                {{-- Prev --}}
                @if($logs->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-300 bg-gray-50 cursor-not-allowed">← Prev</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">← Prev</a>
                @endif

                {{-- Pages --}}
                @foreach($logs->getUrlRange(max(1, $logs->currentPage()-2), min($logs->lastPage(), $logs->currentPage()+2)) as $page => $url)
                    @if($page == $logs->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-600">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">Next →</a>
                @else
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-300 bg-gray-50 cursor-not-allowed">Next →</span>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
@endsection