@extends('layouts.platform')

@section('title', 'Plantiq Tenants')
@section('header', 'Tenant Management')

@section('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="space-y-5" x-data="tenantIndex()">

        {{-- ── Flash ── --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 text-sm font-medium px-4 py-3 rounded-xl">
                <i class="fas fa-circle-check shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded-xl">
                <i class="fas fa-circle-exclamation shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── Header ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Tenants</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $companies->count() }} total companies onboarded</p>
            </div>
            <a href="{{ route('platform.tenants.create') }}"
                class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shrink-0">
                <i class="fas fa-plus text-xs"></i>
                Onboard Company
            </a>
        </div>

        {{-- ── Filters ── --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1 max-w-sm">
                <i class="fas fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input x-model="search" type="text" placeholder="Search by company, email, plan…"
                    class="w-full pl-8 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-brand-500 bg-white">
            </div>
            <div class="flex gap-2">
                <button @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'bg-brand-600 text-white' :
                        'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    class="px-4 py-2 text-sm font-semibold rounded-xl transition-colors">All</button>
                <button @click="statusFilter = 'active'"
                    :class="statusFilter === 'active' ? 'bg-green-600 text-white' :
                        'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    class="px-4 py-2 text-sm font-semibold rounded-xl transition-colors">Active</button>
                <button @click="statusFilter = 'inactive'"
                    :class="statusFilter === 'inactive' ? 'bg-red-500 text-white' :
                        'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    class="px-4 py-2 text-sm font-semibold rounded-xl transition-colors">Inactive</button>
                <button @click="statusFilter = 'expired'"
                    :class="statusFilter === 'expired' ? 'bg-orange-500 text-white' :
                        'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                    class="px-4 py-2 text-sm font-semibold rounded-xl transition-colors">Expired</button>
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-5 py-3.5">Company</th>
                            <th class="text-left px-5 py-3.5 hidden md:table-cell">Plan</th>
                            <th class="text-center px-5 py-3.5 hidden lg:table-cell">Modules</th>
                            <th class="text-left px-5 py-3.5 hidden xl:table-cell">Subscription</th>
                            <th class="text-center px-5 py-3.5">Status</th>
                            <th class="text-right px-5 py-3.5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($companies as $company)
                            @php
                                $owner = $company->users->first();
                                $sub = $company->subscription;
                                $plan = $sub?->plan;

                                // Subscription status logic
                                $now = \Carbon\Carbon::now();
                                $expiryDate = $sub?->expires_at
                                    ? \Carbon\Carbon::parse($sub->expires_at)->endOfDay()
                                    : null;
                                $isExpired = $expiryDate && $expiryDate->isPast();
                                $daysLeft =
                                    $expiryDate && !$isExpired ? (int) $now->diffInDays($expiryDate, false) : null;

                                if (!$sub) {
                                    $subTag = 'no-sub';
                                } elseif (!$sub->is_active) {
                                    $subTag = 'inactive';
                                } elseif ($isExpired) {
                                    $subTag = 'expired';
                                } else {
                                    $subTag = 'active';
                                }

                                // For Alpine filter — build a data string
                                $searchStr = strtolower(
                                    $company->name . ' ' . $company->email . ' ' . ($plan?->name ?? ''),
                                );
                                $compActive = $company->is_active ? 'active' : 'inactive';
                                $subStatus = $subTag; // active | inactive | expired | no-sub
                            @endphp

                            <tr class="hover:bg-gray-50/60 transition-colors group"
                                x-show="rowVisible('{{ $searchStr }}', '{{ $compActive }}', '{{ $subStatus }}')"
                                data-search="{{ $searchStr }}" data-comp="{{ $compActive }}"
                                data-sub="{{ $subStatus }}">

                                {{-- Company --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-brand-500/10 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($company->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 leading-tight">{{ $company->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $company->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Plan --}}
                                <td class="px-5 py-4 hidden md:table-cell">
                                    @if ($plan)
                                        <div class="flex flex-col gap-0.5">
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-700">
                                                <i class="fas fa-layer-group text-[10px]"></i>
                                                {{ $plan->name }}
                                            </span>
                                            <span class="text-[11px] text-gray-400">
                                                ₹{{ number_format($plan->price, 0) }} / {{ $plan->billing_cycle }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">No plan</span>
                                    @endif
                                </td>

                                {{-- Modules count --}}
                                <td class="px-5 py-4 text-center hidden lg:table-cell">
                                    @if ($plan && $plan->modules->count() > 0)
                                        <span
                                            class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 text-xs font-bold px-2.5 py-1 rounded-lg">
                                            <i class="fas fa-cubes text-[10px]"></i>
                                            {{ $plan->modules->count() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Subscription dates --}}
                                <td class="px-5 py-4 hidden xl:table-cell">
                                    @if ($sub)
                                        <div class="flex flex-col gap-0.5 text-[11px]">
                                            <span class="text-gray-500">
                                                <span class="text-gray-400 font-medium">Start:</span>
                                                {{ $sub->starts_at ? \Carbon\Carbon::parse($sub->starts_at)->format('d M Y') : '—' }}
                                            </span>
                                            <span class="text-gray-500">
                                                <span class="text-gray-400 font-medium">Exp:</span>
                                                {{ $sub->expires_at ? \Carbon\Carbon::parse($sub->expires_at)->format('d M Y') : 'Lifetime' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>

                                {{-- Subscription status --}}
                                <td class="px-5 py-4 text-center">
                                    @if (!$sub)
                                        <span
                                            class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                            No Plan
                                        </span>
                                    @elseif (!$sub->is_active)
                                        <span
                                            class="inline-flex items-center gap-1 bg-red-100 text-red-600 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                            Inactive
                                        </span>
                                    @elseif ($isExpired)
                                        <div class="flex flex-col items-center gap-1">
                                            <span
                                                class="inline-flex items-center gap-1 bg-gray-100 text-gray-600 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                                Expired
                                            </span>
                                            <span
                                                class="text-[10px] text-red-500 font-medium">{{ $expiryDate->diffForHumans() }}</span>
                                        </div>
                                    @elseif ($expiryDate)
                                        <div class="flex flex-col items-center gap-1">
                                            <span
                                                class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                                Active
                                            </span>
                                            @if ($daysLeft !== null && $daysLeft <= 7)
                                                <span
                                                    class="text-[10px] text-orange-600 font-bold bg-orange-50 px-1.5 py-0.5 rounded">
                                                    {{ $daysLeft }}d left
                                                </span>
                                            @else
                                                <span class="text-[10px] text-gray-400 font-medium">{{ $daysLeft }}d
                                                    left</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-1">
                                            <span
                                                class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                                Active
                                            </span>
                                            <span
                                                class="text-[10px] text-brand-600 font-medium bg-brand-50 px-1.5 py-0.5 rounded">Lifetime</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('platform.tenants.show', $company) }}"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                            title="View Details">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('platform.tenants.edit', $company) }}"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Edit">
                                            <i class="fas fa-pen text-xs"></i>
                                        </a>
                                        <form id="del-{{ $company->id }}" method="POST"
                                            action="{{ route('platform.tenants.destroy', $company) }}" class="hidden">
                                            @csrf @method('DELETE')
                                        </form>
                                        <button type="button"
                                            onclick="confirmDelete({{ $company->id }}, '{{ addslashes($company->name) }}')"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Terminate">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center text-gray-400 text-sm">
                                    No companies onboarded yet.
                                    <a href="{{ route('platform.tenants.create') }}"
                                        class="text-brand-600 font-semibold hover:underline ml-1">Onboard the first
                                        one.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- No results message --}}
            <div x-show="noResults" class="px-5 py-12 text-center text-gray-400 text-sm border-t border-gray-50">
                <i class="fas fa-magnifying-glass text-2xl mb-3 block text-gray-300"></i>
                No companies match your search.
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        function tenantIndex() {
            return {
                search: '',
                statusFilter: 'all',
                _visibleCount: 0,
                _totalRows: 0,

                get noResults() {
                    return this._totalRows > 0 && this._visibleCount === 0;
                },

                rowVisible(searchStr, compStatus, subStatus) {
                    const q = this.search.toLowerCase().trim();
                    const matchSearch = q === '' || searchStr.includes(q);

                    let matchStatus = true;
                    if (this.statusFilter === 'active') {
                        matchStatus = compStatus === 'active';
                    } else if (this.statusFilter === 'inactive') {
                        matchStatus = compStatus === 'inactive';
                    } else if (this.statusFilter === 'expired') {
                        matchStatus = subStatus === 'expired';
                    }

                    return matchSearch && matchStatus;
                },

                init() {
                    this.$watch('search', () => this._recount());
                    this.$watch('statusFilter', () => this._recount());
                },

                _recount() {
                    const rows = this.$el.querySelectorAll('tbody tr[data-search]');
                    this._totalRows = rows.length;
                    this._visibleCount = 0;
                    rows.forEach(r => {
                        if (this.rowVisible(r.dataset.search, r.dataset.comp, r.dataset.sub)) {
                            this._visibleCount++;
                        }
                    });
                }
            }
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'Terminate Company?',
                html: `<p style="color:#4b5563;font-size:14px;margin-top:4px;">
                    You are about to permanently terminate:<br>
                    <strong style="color:#111827">${name}</strong>
               </p>
               <p style="color:#ef4444;font-size:13px;margin-top:10px;">
                   All stores, data, and users will be deleted.<br>
                   <strong>This action cannot be undone.</strong>
               </p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Terminate',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('del-' + id).submit();
                }
            });
        }
    </script>
@endsection
