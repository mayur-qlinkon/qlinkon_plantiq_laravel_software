@extends('layouts.admin')

@section('title', 'Challans')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Challans</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="challanIndex()">

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
            <form id="challan-filter-form" action="{{ route('admin.challans.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full"
                @submit.prevent="submitForm"
                @change="submitForm">

                {{-- 1. Search Group (Input + Clear) --}}
                <div class="flex flex-row items-center gap-2 flex-1 min-w-[250px] max-w-md w-full">
                    <div class="relative flex-1">                        
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search Challan Number, Party Name..."
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
                        name="direction"
                        placeholder="All Directions"
                        :options="['outward' => 'Outward', 'inward' => 'Inward']"
                        selected="{{ request('direction') }}"
                    />
                </div>

                <div class="w-full lg:w-auto shrink-0">
                    <x-custom-select
                        name="challan_type"
                        placeholder="All Types"
                        :options="\App\Models\Challan::TYPE_LABELS"
                        selected="{{ request('challan_type') }}"
                    />
                </div>

                <div class="w-full lg:w-auto shrink-0">
                    <x-custom-select
                        name="status"
                        placeholder="All Statuses"
                        :options="\App\Models\Challan::STATUS_LABELS"
                        selected="{{ request('status') }}"
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

               {{-- 3. Create Button --}}
               @if(has_permission('challans.create'))
                <div class="ml-auto flex shrink-0 w-full sm:w-auto">
                    <a href="{{ route('admin.challans.create') }}"
                        class="w-full sm:w-auto bg-brand-500 hover:bg-brand-600 text-white px-5 py-2.5 rounded-lg text-sm font-bold transition-colors shadow-sm flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create Challan
                    </a>
                </div>
                @endif
            </form>

            @if(! is_null($filteredCount))
                <div class="px-1 pt-3 text-xs sm:text-sm text-gray-600">
                    <span class="font-bold text-gray-800">{{ number_format($filteredCount) }}</span>
                    challan{{ $filteredCount === 1 ? '' : 's' }} found
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
        <div id="challans-list-container" class="bg-white rounded-b-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col"
            @click="handlePaginationClick($event)">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">CHALLAN DETAILS</th>
                            <th class="px-6 py-4">PARTY</th>
                            <th class="px-6 py-4 hidden md:table-cell">TYPE & DIR</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-right hidden md:table-cell">TOTAL VALUE</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($challans as $challan)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.challans.show', $challan->id) }}"
                                            class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                            {{ $challan->challan_number }}
                                        </a>
                                        <span class="text-[11px] text-gray-500 mt-0.5 font-medium flex items-center gap-1">
                                            <i data-lucide="calendar" class="w-3 h-3"></i> {{ $challan->challan_date->format('d M, Y') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 text-[13px] truncate max-w-[200px]">
                                            {{ $challan->party_name ?: 'Unknown Party' }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                            {{ $challan->party_state ?? 'No State' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 hidden md:table-cell">
                                    <div class="flex flex-col">
                                        <span class="text-[12px] font-bold text-gray-700">
                                            {{ $challan->type_label }}
                                        </span>
                                        <span class="text-[10px] font-black uppercase tracking-widest mt-0.5 {{ $challan->direction === 'outward' ? 'text-blue-500' : 'text-purple-500' }}">
                                            <i data-lucide="{{ $challan->direction === 'outward' ? 'arrow-up-right' : 'arrow-down-left' }}" class="w-3 h-3 inline"></i> 
                                            {{ $challan->direction }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @php
                                        // Tailwind safe map using the model's color output
                                        $colorMap = [
                                            'gray'   => 'bg-gray-50 text-gray-600 border-gray-200',
                                            'blue'   => 'bg-blue-50 text-blue-600 border-blue-200',
                                            'indigo' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                            'cyan'   => 'bg-cyan-50 text-cyan-600 border-cyan-200',
                                            'amber'  => 'bg-amber-50 text-amber-600 border-amber-200',
                                            'teal'   => 'bg-teal-50 text-teal-600 border-teal-200',
                                            'green'  => 'bg-green-50 text-green-700 border-green-200',
                                            'lime'   => 'bg-lime-50 text-lime-700 border-lime-200',
                                            'slate'  => 'bg-slate-50 text-slate-700 border-slate-200',
                                            'red'    => 'bg-red-50 text-red-600 border-red-200',
                                        ];
                                        $c = $colorMap[$challan->status_color] ?? $colorMap['gray'];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $c }}">
                                        {{ $challan->status_label }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right hidden md:table-cell">
                                    <span class="font-extrabold text-gray-800">₹{{ number_format($challan->total_value, 2) }}</span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity transition-opacity">

                                        {{-- View Button --}}
                                        @if(has_permission('challans.view'))
                                        <a href="{{ route('admin.challans.show', $challan->id) }}"
                                            class="w-10 h-10 md:w-8 md:h-8 rounded border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors"
                                            title="View Challan">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        @endif

                                        {{-- Process Return Button --}}
                                        @if(has_permission('challan_returns.create'))
                                            @if ($challan->is_returnable && $challan->status !== 'cancelled')
                                            <a href="{{ route('admin.challan-returns.create', ['challan' => $challan->id]) }}"
                                                class="w-10 h-10 md:w-8 md:h-8 rounded border border-amber-200 text-amber-600 hover:bg-amber-50 flex items-center justify-center transition-colors"
                                                    title="Process Return">
                                                    <i data-lucide="undo-2" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                        @endif
                                            
                                            
                                        {{-- 🌟 Quick Status Update Button --}}
                                        @if(has_permission('challans.change_status'))
                                            @if (!in_array($challan->status, ['closed', 'cancelled']))
                                                <button type="button"
                                                    @click="openStatusModal({{ $challan->id }}, '{{ $challan->challan_number }}', '{{ $challan->status }}')"
                                                    class="w-10 h-10 md:w-8 md:h-8 rounded border border-indigo-200 text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors"
                                                    title="Update Status">
                                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                                </button>
                                            @endif
                                        @endif

                                        {{-- Edit Button --}}
                                        @if(has_permission('challans.update'))
                                            @if ($challan->status !== 'cancelled')
                                                <a href="{{ route('admin.challans.edit', $challan->id) }}"
                                                    class="w-10 h-10 md:w-8 md:h-8 rounded border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors"
                                                    title="Edit Challan">
                                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                        @endif

                                        {{-- Delete Button (Unrestricted as requested) --}}
                                        @if(has_permission('challans.delete'))
                                            <form action="{{ route('admin.challans.destroy', $challan->id) }}"
                                                method="POST" @submit.prevent="confirmDelete($event.target)"
                                                class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="w-10 h-10 md:w-8 md:h-8 rounded border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors"
                                                    title="Delete Challan">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="file-text" class="w-10 h-10 mb-3 opacity-20"></i>
                                        <p class="text-sm font-medium">No challans found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse ($challans as $challan)
                    @php
                        $colorMap = [
                            'gray'   => 'bg-gray-50 text-gray-600 border-gray-200',
                            'blue'   => 'bg-blue-50 text-blue-600 border-blue-200',
                            'indigo' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                            'cyan'   => 'bg-cyan-50 text-cyan-600 border-cyan-200',
                            'amber'  => 'bg-amber-50 text-amber-600 border-amber-200',
                            'teal'   => 'bg-teal-50 text-teal-600 border-teal-200',
                            'green'  => 'bg-green-50 text-green-700 border-green-200',
                            'lime'   => 'bg-lime-50 text-lime-700 border-lime-200',
                            'slate'  => 'bg-slate-50 text-slate-700 border-slate-200',
                            'red'    => 'bg-red-50 text-red-600 border-red-200',
                        ];
                        $c = $colorMap[$challan->status_color] ?? $colorMap['gray'];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">
                        
                        {{-- Header: Party & Total --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-800 text-[14px] truncate">
                                    {{ $challan->party_name ?: 'Unknown Party' }}
                                </p>
                                <p class="text-[11px] text-gray-400 mt-0.5 font-bold uppercase tracking-tighter">
                                    {{ $challan->party_state ?? 'No State' }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-black text-gray-800 text-[15px]">₹{{ number_format($challan->total_value, 2) }}</span>
                            </div>
                        </div>

                        {{-- Challan Details & Badges --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-center">
                                <a href="{{ route('admin.challans.show', $challan->id) }}" class="font-extrabold text-[#108c2a] text-[13px] hover:underline">
                                    {{ $challan->challan_number }}
                                </a>
                                <span class="text-[11px] text-gray-500 font-medium flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3"></i> {{ $challan->challan_date->format('d M, Y') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100/50">
                                <span class="text-[11px] font-bold text-gray-700">
                                    {{ $challan->type_label }}
                                </span>
                                <span class="text-gray-300">|</span>
                                <span class="text-[9px] font-black uppercase tracking-widest {{ $challan->direction === 'outward' ? 'text-blue-500' : 'text-purple-500' }}">
                                    <i data-lucide="{{ $challan->direction === 'outward' ? 'arrow-up-right' : 'arrow-down-left' }}" class="w-3 h-3 inline"></i> 
                                    {{ $challan->direction }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider border {{ $c }} ml-auto">
                                    {{ $challan->status_label }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1 flex-wrap">
                            @if(has_permission('challans.view'))
                                <a href="{{ route('admin.challans.show', $challan->id) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center transition-colors" title="View Challan">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if(has_permission('challan_returns.create') && $challan->is_returnable && $challan->status !== 'cancelled')
                                <a href="{{ route('admin.challan-returns.create', ['challan' => $challan->id]) }}" class="w-8 h-8 rounded-lg border border-amber-200 text-amber-600 hover:bg-amber-50 flex items-center justify-center transition-colors" title="Process Return">
                                    <i data-lucide="undo-2" class="w-4 h-4"></i>
                                </a>
                            @endif
                            
                            @if(has_permission('challans.change_status') && !in_array($challan->status, ['closed', 'cancelled']))
                                <button type="button" @click="openStatusModal({{ $challan->id }}, '{{ $challan->challan_number }}', '{{ $challan->status }}')" class="w-8 h-8 rounded-lg border border-indigo-200 text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors" title="Update Status">
                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if(has_permission('challans.update') && $challan->status !== 'cancelled')
                                <a href="{{ route('admin.challans.edit', $challan->id) }}" class="w-8 h-8 rounded-lg border border-blue-200 text-blue-500 hover:bg-blue-50 flex items-center justify-center transition-colors" title="Edit Challan">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                            @endif

                            @if(has_permission('challans.delete'))
                                <form action="{{ route('admin.challans.destroy', $challan->id) }}" method="POST" @submit.prevent="confirmDelete($event.target)" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-8 h-8 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 flex items-center justify-center transition-colors" title="Delete Challan">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400 bg-white">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="file-text" class="w-10 h-10 mb-3 opacity-20"></i>
                            <p class="font-medium text-gray-500">No challans found.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($challans->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $challans->links() }}
                </div>
            @endif
        </div>

        {{-- 🌟 QUICK STATUS MODAL --}}
        <div x-show="isStatusModalOpen" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity px-4">
            {{-- Fixed: Removed overflow-hidden so custom select options can overflow outside boundaries --}}
            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl flex flex-col"
                x-show="isStatusModalOpen" x-transition>

                {{-- Fixed: Added rounded-t-2xl to protect the header background rounding --}}
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <h3 class="text-[15px] font-bold text-gray-800">Update Status - <span x-text="activeChallan.number"></span></h3>
                    <button type="button" @click="closeStatusModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Dynamically bind the action URL to the specific challan ID --}}
                <form :action="`/admin/challans/${activeChallan.id}/status`" method="POST"
                    @submit="BizAlert.loading('Updating Status...')">
                    @csrf
                    @method('PATCH')
                    
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">New Status <span class="text-red-500">*</span></label>
                            <x-custom-select
                                name="status"
                                id="modal-challan-status"
                                placeholder="-- Select Status --"
                                :options="\App\Models\Challan::STATUS_LABELS"
                                required
                            />
                        </div>

                       <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-1.5">Tracking / Internal Notes</label>
                                <textarea name="notes" rows="3" placeholder="e.g., Courier picked up, LR# 12345..."
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-700 focus:border-[#108c2a] focus:ring-2 focus:ring-green-500/20 outline-none transition-all resize-none"></textarea>
                            </div>
                        </div>

                        {{-- Fixed: Added rounded-b-2xl to protect the footer background rounding --}}
                        <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-2xl">
                            
                        <button type="button" @click="closeStatusModal()"
                            class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-[#108c2a] text-white font-bold text-sm rounded-xl hover:bg-[#0c6b1f] transition-all shadow-md active:scale-95 flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        {{-- END MODAL --}}

    </div>
@endsection

@push('scripts')
    <script>
        function challanIndex() {
            return {
                // Status Modal State
                isStatusModalOpen: false,
                activeChallan: {
                    id: '',
                    number: '',
                    status: ''
                },

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitChallanForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById('challan-filter-form');
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== '');
                },

                submitForm() {
                    const form = document.getElementById('challan-filter-form');
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => { if (v) url.searchParams.set(k, v); });
                    
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById('challan-filter-form');
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], input[type="date"], select').forEach(el => {
                            el.value = '';                            
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }); 
                                                
                        this.fetchResults(form.action);   
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById('challans-list-container');
                    if (!targetContainer) return;

                    targetContainer.style.opacity = '0.5';
                    targetContainer.style.pointerEvents = 'none';

                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => res.text())
                        .then(html => {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const newContainer = doc.getElementById('challans-list-container');
                            
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

                openStatusModal(id, number, currentStatus) {
                    this.activeChallan = {
                        id: id,
                        number: number,
                        status: currentStatus
                    };
                    this.isStatusModalOpen = true;

                    // Sync the active row status state programmatically to the custom component layout
                    this.$nextTick(() => {
                        const selectEl = document.getElementById('modal-challan-status');
                        if (selectEl) {
                            selectEl.value = currentStatus;
                            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });

                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 50);
                },

                closeStatusModal() {
                    this.isStatusModalOpen = false;
                },

                confirmDelete(form) {
                    BizAlert.confirm(
                        'Delete Challan?',
                        'This action cannot be undone. Are you sure you want to permanently delete this challan?',
                        'Yes, Delete it',                        
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