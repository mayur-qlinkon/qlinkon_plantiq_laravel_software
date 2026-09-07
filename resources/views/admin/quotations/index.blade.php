@extends('layouts.admin')

@section('title', 'Quotations - ' . config('app.name'))

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Quotations</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="quotationIndex()">

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

        {{-- HEADER & ACTIONS --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">Manage and track your customer proposals.</p>
            </div>
        </div>

        {{-- SEARCH & FILTER BAR --}}
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 p-4 border-b-0">
            <form id="quotation-filter-form" action="{{ route('admin.quotations.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full"
                @submit.prevent="submitForm"
                @change="submitForm">

                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex flex-row items-center gap-2 flex-1 min-w-[250px] max-w-md w-full">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search Quotation Number, Customer..."
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

                {{-- 2. Inline Status Custom Select --}}
                <div class="w-full lg:w-auto shrink-0">
                    <x-custom-select
                        name="status"
                        placeholder="All Statuses"
                        :options="[
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'accepted' => 'Accepted',
                            'rejected' => 'Rejected',
                            'expired' => 'Expired',
                            'converted' => 'Converted to Invoice'
                        ]"
                        selected="{{ request('status') }}"
                    />
                </div>

                {{-- 3. Inline Date Range --}}
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

                {{-- 4. Create Button (Pushed to the right) --}}
                @if(has_permission('quotations.create'))
                <div class="ml-auto flex shrink-0 w-full sm:w-auto">
                    <a href="{{ route('admin.quotations.create') }}"
                        class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create Quotation
                    </a>
                </div>
                @endif
                
            </form>

            @if(! is_null($filteredCount))
                <div class="px-1 pt-3 text-xs sm:text-sm text-gray-600">
                    <span class="font-bold text-gray-800">{{ number_format($filteredCount) }}</span>
                    quotation{{ $filteredCount === 1 ? '' : 's' }} found
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
        <div id="quotations-list-container" class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">QT DETAILS</th>
                            <th class="px-6 py-4">CUSTOMER</th>
                            <th class="px-6 py-4">VALIDITY</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-right">TOTAL AMOUNT</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($quotations as $quotation)
                            <tr class="hover:bg-gray-50/50 transition-colors group">

                                {{-- 1. Details --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">

                                        <a href="
                                        @if(has_permission('quotations.view'))
                                            {{ route('admin.quotations.show', $quotation->id) }}
                                         @endif
                                         "
                                            class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                            {{ $quotation->quotation_number }}
                                        </a>

                                        <span class="text-[11px] text-gray-500 mt-0.5 font-medium">
                                            {{ $quotation->quotation_date->format('d M, Y') }}
                                        </span>
                                    </div>
                                </td>

                                {{-- 2. Customer --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 text-[13px]">
                                            {{ $quotation->display_name }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                            {{ $quotation->supply_state ?? 'State N/A' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- 3. Validity --}}
                                <td class="px-6 py-4">
                                    @if ($quotation->valid_until)
                                        <span
                                            class="text-[12px] font-semibold {{ $quotation->is_expired && $quotation->status !== 'converted' ? 'text-red-500' : 'text-gray-600' }}">
                                            {{ $quotation->valid_until->format('d M, Y') }}
                                        </span>
                                    @else
                                        <span class="text-[12px] text-gray-400 font-medium">N/A</span>
                                    @endif
                                </td>

                                {{-- 4. Status Badge --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                                            'sent' => 'bg-blue-50 text-blue-600 border-blue-200',
                                            'accepted' => 'bg-green-50 text-green-700 border-green-200',
                                            'rejected' => 'bg-red-50 text-red-600 border-red-200',
                                            'expired' => 'bg-orange-50 text-orange-600 border-orange-200',
                                            'converted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        ];
                                        $color = $statusColors[$quotation->status] ?? $statusColors['draft'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                        {{ $quotation->status }}
                                    </span>
                                </td>

                                {{-- 5. Total --}}
                                <td class="px-6 py-4 text-right">
                                    <span
                                        class="font-extrabold text-gray-800">₹{{ number_format($quotation->grand_total, 2) }}</span>
                                </td>

                                {{-- 6. Actions --}}
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 transition-opacity">

                                        {{-- View Button --}}
                                        @if(has_permission('quotations.view'))
                                        <a href="{{ route('admin.quotations.show', $quotation->id) }}"
                                            class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                            title="View Quotation">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @endif
                                      

                                        @if ($quotation->status !== 'converted')
                                            {{-- Mark Sent Button (Quick Action) --}}
                                            @if ($quotation->status === 'draft' && has_permission('quotations.mark_sent'))
                                                <form action="{{ route('admin.quotations.mark_sent', $quotation->id) }}"
                                                    method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit"
                                                        class="w-8 h-8 rounded border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors"
                                                        title="Mark as Sent">
                                                        <i data-lucide="send" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Edit Button --}}
                                            @if(has_permission('quotations.update'))
                                            <a href="{{ route('admin.quotations.edit', $quotation->id) }}"
                                                class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                                title="Edit Quotation">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </a>
                                            @endif



                                            {{-- Convert to Invoice Button --}}
                                            @if(has_permission('quotations.convert'))
                                            <form action="{{ route('admin.quotations.convert', $quotation->id) }}"
                                                method="POST" @submit.prevent="confirmConvert($event.target)"
                                                class="inline-block">
                                                @csrf
                                                <button type="submit"
                                                    class="w-8 h-8 rounded border border-green-200 text-green-600 hover:bg-green-50 flex items-center justify-center transition-colors"
                                                    title="Convert to Invoice">
                                                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                            @endif

                                            @if(has_permission('quotations.download_pdf'))
                                            <a href="{{ route('admin.quotations.pdf', $quotation->id) }}" target="_blank"
                                                class="w-8 h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                                title="Download Quotation">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </a>
                                            @endif

                                            {{-- Archive Button --}}
                                            @if(has_permission('quotations.delete'))
                                            <form action="{{ route('admin.quotations.destroy', $quotation->id) }}"
                                                method="POST" @submit.prevent="confirmArchive($event.target)"
                                                class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors"
                                                    title="Archive Quotation">
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
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="file-signature" class="w-10 h-10 mb-3 opacity-20"></i>
                                        <p class="text-sm font-medium">No quotations found.</p>
                                        <p class="text-xs mt-1">Create your first proposal to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse ($quotations as $quotation)
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'sent' => 'bg-blue-50 text-blue-600 border-blue-200',
                            'accepted' => 'bg-green-50 text-green-700 border-green-200',
                            'rejected' => 'bg-red-50 text-red-600 border-red-200',
                            'expired' => 'bg-orange-50 text-orange-600 border-orange-200',
                            'converted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        ];
                        $color = $statusColors[$quotation->status] ?? $statusColors['draft'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Customer & Total --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-800 text-[14px] truncate">
                                    {{ $quotation->display_name }}
                                </p>
                                <p class="text-[11px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                    {{ $quotation->supply_state ?? 'State N/A' }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-black text-[#108c2a] text-[16px]">₹{{ number_format($quotation->grand_total, 2) }}</span>
                            </div>
                        </div>

                        {{-- Details & Badges --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                @if(has_permission('quotations.view'))
                                    <a href="{{ route('admin.quotations.show', $quotation->id) }}" class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                        {{ $quotation->quotation_number }}
                                    </a>
                                @else
                                    <span class="font-extrabold text-[#108c2a] text-[13px]">{{ $quotation->quotation_number }}</span>
                                @endif
                                <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                    {{ $quotation->status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-100/50">
                                <span class="text-[11px] text-gray-500 font-medium">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Date:</span> {{ $quotation->quotation_date->format('d M, Y') }}
                                </span>
                                <span class="text-[11px] font-medium {{ $quotation->is_expired && $quotation->status !== 'converted' ? 'text-red-500 font-bold' : 'text-gray-500' }}">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase">Valid:</span> {{ $quotation->valid_until ? $quotation->valid_until->format('d M, Y') : 'N/A' }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 flex-wrap">
                            @if(has_permission('quotations.view'))
                                <a href="{{ route('admin.quotations.show', $quotation->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="View Quotation">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if(has_permission('quotations.download_pdf'))
                                <a href="{{ route('admin.quotations.pdf', $quotation->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="Download Quotation">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if ($quotation->status !== 'converted')
                                @if ($quotation->status === 'draft' && has_permission('quotations.mark_sent'))
                                    <form action="{{ route('admin.quotations.mark_sent', $quotation->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Mark as Sent">
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(has_permission('quotations.update'))
                                    <a href="{{ route('admin.quotations.edit', $quotation->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="Edit Quotation">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                @endif

                                @if(has_permission('quotations.convert'))
                                    <form action="{{ route('admin.quotations.convert', $quotation->id) }}" method="POST" @submit.prevent="confirmConvert($event.target)" class="inline-block">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg border border-green-200 text-green-600 hover:bg-green-50 flex items-center justify-center transition-colors" title="Convert to Invoice">
                                            <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(has_permission('quotations.delete'))
                                    <form action="{{ route('admin.quotations.destroy', $quotation->id) }}" method="POST" @submit.prevent="confirmArchive($event.target)" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors" title="Archive Quotation">
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
                            <i data-lucide="file-signature" class="w-10 h-10 mb-3 opacity-20"></i>
                            <p class="font-medium text-gray-500 text-[13px]">No quotations found.</p>
                            <p class="text-xs mt-1">Create your first proposal to get started.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($quotations->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $quotations->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function quotationIndex() {
            return {
                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitQuotationForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('quotation-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('quotation-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => { if (v) url.searchParams.set(k, v); });
                    
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('quotation-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], input[type="date"], select').forEach(el => {
                            el.value = '';                            
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('quotations-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('quotations-list-container');
                            
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

                confirmArchive(form) {
                    BizAlert.confirm(
                        'Archive Quotation?',
                        'Are you sure you want to delete this quotation? This cannot be undone.',
                        'Yes, Archive it',
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading('Archiving...');
                            form.submit();
                        }
                    });
                },

                confirmConvert(form) {
                    BizAlert.confirm(
                        'Convert to Invoice?',
                        'This will generate a Draft Invoice with the exact details of this quotation. The quotation will be locked.',
                        'Yes, Convert it',
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading('Converting to Invoice...');
                            form.submit();
                        }
                    });
                }
            }
        }
    </script>
@endpush
