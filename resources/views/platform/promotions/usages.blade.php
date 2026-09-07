@extends('layouts.platform')

@section('title', 'Coupon Usage')
@section('header', 'Coupon Usage')

@section('styles')
<style>
    .stat-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #f1f5f9;
        padding: 18px 20px;
        transition: box-shadow 0.2s;
    }
    .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }

    .usage-row { transition: background 0.12s; }
    .usage-row:hover { background: #f8fafc; }

    .code-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-family: 'Courier New', monospace;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 3px 9px;
        border-radius: 7px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
        text-transform: uppercase;
    }

    .usable-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .usable-plan         { background: #eff6ff; color: #1d4ed8; }
    .usable-subscription { background: #faf5ff; color: #7e22ce; }
    .usable-order        { background: #fff7ed; color: #c2410c; }
    .usable-default      { background: #f1f5f9; color: #475569; }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
    }
    .filter-chip.active {
        background: #0f766e;
        border-color: #0f766e;
        color: white;
    }

    .discount-pill {
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        color: #065f46;
        background: #d1fae5;
        border-radius: 8px;
        padding: 3px 10px;
    }
</style>
@endsection

@section('content')
<div class="space-y-5">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Coupon Usage</h2>
            <p class="text-sm text-gray-400 mt-0.5">Track every promotion redemption across companies</p>
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="stat-card flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-ticket text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Total Redemptions</p>
                <p class="text-3xl font-extrabold text-gray-900">{{ number_format($stats['total_redemptions']) }}</p>
                @if(array_filter($filters))
                    <p class="text-[11px] text-gray-400 mt-0.5">Filtered result</p>
                @else
                    <p class="text-[11px] text-gray-400 mt-0.5">All time</p>
                @endif
            </div>
        </div>
        <div class="stat-card flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-indian-rupee-sign text-xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Total Discount Given</p>
                <p class="text-3xl font-extrabold text-gray-900">₹{{ number_format($stats['total_discount'], 2) }}</p>
                @if($stats['total_redemptions'] > 0)
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Avg ₹{{ number_format($stats['total_discount'] / $stats['total_redemptions'], 2) }} per use
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" action="{{ route('platform.promotion-usages.index') }}" class="flex flex-wrap gap-3 items-end">

            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-400 mb-1.5">Search</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs"></i>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Coupon code, promotion name, company…"
                        class="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
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
                    <a href="{{ route('platform.promotion-usages.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-200 transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Active chips --}}
        @if(array_filter($filters))
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-50">
            <span class="text-xs text-gray-400 font-medium self-center">Active:</span>
            @if(!empty($filters['q']))
                <span class="filter-chip active">
                    <i class="fa-solid fa-magnifying-glass text-[9px]"></i> "{{ $filters['q'] }}"
                </span>
            @endif
            @if(!empty($filters['from']) || !empty($filters['to']))
                <span class="filter-chip active">
                    <i class="fa-solid fa-calendar text-[9px]"></i>
                    {{ $filters['from'] ?? '…' }} → {{ $filters['to'] ?? 'now' }}
                </span>
            @endif
        </div>
        @endif
    </div>

    {{-- ── Table ── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        {{-- Results meta --}}
        <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-400 font-medium">
                Showing
                <span class="text-gray-700 font-semibold">{{ $usages->firstItem() ?? 0 }}–{{ $usages->lastItem() ?? 0 }}</span>
                of <span class="text-gray-700 font-semibold">{{ $usages->total() }}</span> records
            </p>
            <p class="text-xs text-gray-400">Page {{ $usages->currentPage() }} of {{ $usages->lastPage() }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100">
                        <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Coupon</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Company</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Applied On</th>
                        <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Discount</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wide">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($usages as $usage)
                    <tr class="usage-row">

                        {{-- Coupon --}}
                        <td class="px-5 py-3.5">
                            <span class="code-badge">
                                <i class="fa-solid fa-tag text-[9px]"></i>
                                {{ $usage->promotion->code ?? '—' }}
                            </span>
                            @if($usage->promotion?->name)
                                <p class="text-[11px] text-gray-400 mt-1">{{ $usage->promotion->name }}</p>
                            @endif
                        </td>

                        {{-- Company --}}
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-brand-500/10 text-brand-600 flex items-center justify-center shrink-0 text-xs font-extrabold">
                                    {{ strtoupper(substr($usage->company->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 truncate max-w-[150px] text-xs">
                                        {{ $usage->company->name ?? '—' }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 truncate max-w-[150px]">
                                        {{ $usage->company->email ?? '' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Applied On (usable morph) --}}
                        <td class="px-4 py-3.5">
                            @if($usage->usable_type)
                                @php
                                    $shortType = class_basename($usage->usable_type);
                                    $tagClass = match(strtolower($shortType)) {
                                        'plan'         => 'usable-plan',
                                        'subscription' => 'usable-subscription',
                                        'order'        => 'usable-order',
                                        default        => 'usable-default',
                                    };
                                    $icon = match(strtolower($shortType)) {
                                        'plan'         => 'fa-layer-group',
                                        'subscription' => 'fa-rotate',
                                        'order'        => 'fa-bag-shopping',
                                        default        => 'fa-circle-dot',
                                    };
                                @endphp
                                <span class="usable-tag {{ $tagClass }}">
                                    <i class="fa-solid {{ $icon }} text-[8px]"></i>
                                    {{ $shortType }}
                                </span>
                                <p class="text-[10px] text-gray-400 mt-1 font-mono">#{{ $usage->usable_id }}</p>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Discount --}}
                        <td class="px-4 py-3.5 text-right">
                            <span class="discount-pill">
                                − ₹{{ number_format($usage->discount_amount, 2) }}
                            </span>
                        </td>

                        {{-- Date --}}
                        <td class="px-4 py-3.5">
                            <p class="text-xs text-gray-700 font-medium">{{ $usage->created_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-gray-400">{{ $usage->created_at->format('h:i A') }}</p>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-16">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-gray-50 grid place-items-center">
                                    <i class="fa-solid fa-ticket-simple text-2xl text-gray-200"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-400">No coupon usage found</p>
                                @if(array_filter($filters))
                                    <a href="{{ route('platform.promotion-usages.index') }}"
                                        class="text-xs text-brand-600 font-semibold hover:underline">Clear filters</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>

                {{-- Table footer: column totals --}}
                @if($usages->count() > 0)
                <tfoot>
                    <tr class="bg-gray-50/60 border-t border-gray-100">
                        <td class="px-5 py-3 text-xs font-bold text-gray-500" colspan="3">
                            Page Total
                            <span class="text-gray-400 font-normal ml-1">({{ $usages->count() }} records)</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-xs font-extrabold text-gray-700">
                                − ₹{{ number_format($usages->sum('discount_amount'), 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        @if($usages->hasPages())
        <div class="px-5 py-4 border-t border-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-400">{{ $usages->total() }} total records</p>
            <div class="flex items-center gap-1">
                @if($usages->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-300 bg-gray-50 cursor-not-allowed">← Prev</span>
                @else
                    <a href="{{ $usages->previousPageUrl() }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">← Prev</a>
                @endif

                @foreach($usages->getUrlRange(max(1, $usages->currentPage()-2), min($usages->lastPage(), $usages->currentPage()+2)) as $page => $url)
                    @if($page == $usages->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-brand-600">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">{{ $page }}</a>
                    @endif
                @endforeach

                @if($usages->hasMorePages())
                    <a href="{{ $usages->nextPageUrl() }}"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 transition">Next →</a>
                @else
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-300 bg-gray-50 cursor-not-allowed">Next →</span>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
@endsection