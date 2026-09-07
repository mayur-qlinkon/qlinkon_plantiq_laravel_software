@extends ('layouts.admin')

@section('title', 'CRM Leads')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">CRM Leads</h1>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .stat-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 14px;
            padding: 14px 16px;
            transition:
                box-shadow 150ms,
                border-color 150ms;
        }

        .stat-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .score-bar {
            height: 4px;
            border-radius: 2px;
            background: #f1f5f9;
            overflow: hidden;
            width: 48px;
        }

        .score-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 300ms ease;
        }

        .filter-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 9px;
            padding: 7px 10px;
            font-size: 12px;
            color: #374151;
            outline: none;
            background: #fff;
            font-family: inherit;
            transition: border-color 150ms;
        }

        .search-input {
            padding-left: 30px;
        }

        .filter-input:focus {
            border-color: var(--brand-600);
        }

        .table-row {
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
        }

        .table-row:hover {
            background: #fafbfc;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .lead-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            flex-shrink: 0;
            color: #fff;
        }
    </style>
@endpush

@section('content')
    @php
        $priorityColors = [
            'hot' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'dot' => '#ef4444'],
            'high' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'dot' => '#f97316'],
            'medium' => ['bg' => '#fefce8', 'text' => '#a16207', 'dot' => '#eab308'],
            'low' => ['bg' => '#f9fafb', 'text' => '#6b7280', 'dot' => '#9ca3af'],
        ];
    @endphp

    <div class="pb-10" x-data="leadsPage()">
        {{-- ════════ STATS BAR ════════ --}}
        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6">
            <a href="{{ route('admin.crm.leads.index') }}" class="stat-card block">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Total</p>
                <p class="text-2xl font-black text-gray-900">{{ number_format($stats['total']) }}</p>
            </a>

            <a href="{{ route('admin.crm.leads.index', ['converted' => 0]) }}" class="stat-card block">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Active</p>
                <p class="text-2xl font-black text-gray-900">
                    {{ number_format($stats['total'] - $stats['converted']) }}
                </p>
            </a>

            <a href="{{ route('admin.crm.leads.index', ['priority' => 'hot']) }}" class="stat-card block">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Hot 🔥</p>
                <p class="text-2xl font-black text-red-600">{{ number_format($stats['hot']) }}</p>
            </a>

            <a href="{{ route('admin.crm.leads.index', ['overdue' => 1]) }}" class="stat-card block">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Overdue</p>
                <p class="text-2xl font-black {{ $stats['overdue'] > 0 ? 'text-orange-600' : 'text-gray-900' }}">
                    {{ number_format($stats['overdue']) }}
                </p>
            </a>

            <a href="{{ route('admin.crm.leads.index', ['converted' => 1]) }}" class="stat-card block">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Converted</p>
                <p class="text-2xl font-black text-green-600">{{ number_format($stats['converted']) }}</p>
            </a>

            <div class="stat-card">
                <p class="mb-1 text-[11px] font-bold tracking-wider text-gray-400 uppercase">Pipeline Value</p>
                <p class="text-lg font-black text-gray-900">₹{{ number_format($stats['total_value'] / 1000, 1) }}k</p>
            </div>
        </div>

        {{-- ════════ TOOLBAR ════════ --}}
        <div class="mb-4 rounded-2xl border border-gray-100 bg-white px-4 py-3">
            <form method="GET" action="{{ route('admin.crm.leads.index') }}" id="filter-form"
                @submit.prevent="submitForm" @change="submitForm">
                <div class="flex w-full flex-wrap items-center gap-3">
                    {{-- Search & Clear Group with stable structural vertical centering wrapper --}}
                    <div class="flex w-full flex-row items-center gap-2 sm:min-w-[180px] sm:flex-1">
                        <div class="relative flex-1">
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Search name, phone, email..." @input.debounce.400ms="submitForm"
                                class="filter-input h-[38px] w-full" />
                        </div>

                        <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                            class="flex h-[38px] shrink-0 items-center justify-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                            title="Clear Filters">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                        </button>
                    </div>

                    {{-- Filtering by assignee only makes sense when more than one
                     person's leads are on screen. --}}
                    @if ($canSeeAll)
                        <div class="w-full shrink-0 sm:w-[170px]">
                            <x-user-picker name="assigned_to" :users="$users" :selected="$filters['assigned_to'] ?? ''"
                                placeholder="All Assignees" />
                        </div>
                    @endif

                    {{-- Stage Custom Select --}}
                    <div class="w-full shrink-0 sm:w-[150px]">
                        <x-custom-select name="stage_id" placeholder="All Stages" :options="collect($stages)->pluck('name', 'id')->toArray()"
                            selected="{{ $filters['stage_id'] ?? '' }}" />
                    </div>

                    {{-- Priority Custom Select --}}
                    <div class="w-full shrink-0 sm:w-[140px]">
                        <x-custom-select name="priority" placeholder="All Priority" :options="[
                            'hot' => '🔥 Hot',
                            'high' => 'High',
                            'medium' => 'Medium',
                            'low' => 'Low',
                        ]"
                            selected="{{ $filters['priority'] ?? '' }}" />
                    </div>

                    {{-- Source Custom Select --}}
                    <div class="w-full shrink-0 sm:w-[140px]">
                        <x-custom-select name="source_id" placeholder="All Sources" :options="collect($sources)->pluck('name', 'id')->toArray()"
                            selected="{{ $filters['source_id'] ?? '' }}" />
                    </div>

                    {{-- Quick filters ── --}}
                    <div class="grid grid-cols-2 items-center gap-2 sm:flex">
                        @if ($canSeeAll)
                            <a href="{{ route('admin.crm.leads.index', array_merge($filters, ['mine' => 1])) }}"
                                class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-colors
                        {{ !empty($filters['mine']) ? 'border-brand text-white' : 'border-gray-200 text-gray-500 hover:bg-gray-50' }}"
                                style="{{ !empty($filters['mine']) ? 'background: var(--brand-600); border-color: var(--brand-600)' : '' }}">
                                My Leads
                            </a>
                            <a href="{{ route('admin.crm.leads.index', array_merge($filters, ['unassigned' => 1])) }}"
                                class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-colors
                        {{ !empty($filters['unassigned']) ? 'bg-gray-700 border-gray-700 text-white' : 'border-gray-200 text-gray-500 hover:bg-gray-50' }}">
                                Unassigned
                            </a>
                        @endif
                    </div>

                    {{-- Add lead ── --}}
                    @if (has_permission('crm_leads.create'))
                        <a href="{{ route('admin.crm.leads.create') }}"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-[12px] font-bold text-white transition-opacity hover:opacity-90 sm:ml-auto sm:w-auto"
                            style="background: var(--brand-600)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Add Lead
                        </a>
                    @endif

                    @if (has_permission('crm_leads.import'))
                        <a href="{{ route('admin.crm.leads.import') }}"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-50 sm:w-auto">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="17 8 12 3 7 8" />
                                <line x1="12" y1="3" x2="12" y2="15" />
                            </svg>
                            Import
                        </a>
                    @endif

                    @if (has_permission('crm_leads.export'))
                        <a href="{{ route('admin.crm.leads.export', request()->query()) }}" target="_blank"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-50 sm:w-auto">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Export
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ════════ TABLE ════════ --}}
        <div id="leads-list-container" class="overflow-hidden rounded-2xl border border-gray-100 bg-white"
            @click="handleLinkClick($event)">
            {{-- Table header ── --}}
            <div class="flex flex-col justify-between gap-4 border-b border-gray-50 px-5 py-3 md:flex-row md:items-center">
                <div class="flex items-center gap-3">
                    <p class="text-[12px] font-bold text-gray-500">
                        {{ $leads->total() }} lead{{ $leads->total() !== 1 ? 's' : '' }}
                        @if (array_filter($filters))
                            <span class="font-medium text-gray-400">— filtered</span>
                        @endif
                    </p>

                    {{-- Bulk Actions Button --}}
                    <div x-show="selectedLeads.length > 0" x-cloak x-transition.opacity>
                        <button @click="bulkModal = true"
                            class="bg-brand-50 text-brand-700 hover:bg-brand-100 border-brand-200 inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-[11px] font-bold transition-colors">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            Bulk Actions (<span x-text="selectedLeads.length"></span>)
                        </button>
                    </div>
                </div>

                {{-- Sort ── --}}
                <div class="no-scrollbar -mx-2 flex items-center gap-2 overflow-x-auto px-2 pb-1">
                    <span class="text-[11px] font-medium text-gray-400">Sort:</span>
                    @foreach (['created_at' => 'Newest', 'score' => 'Score', 'lead_value' => 'Value', 'next_followup_at' => 'Follow-up'] as $field => $label)
                        <a href="{{ route('admin.crm.leads.index', array_merge($filters, ['sort' => $field, 'dir' => ($filters['sort'] ?? '') === $field && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc'])) }}"
                            class="text-[11px] font-bold px-2.5 py-1 rounded-lg transition-colors
                        {{ ($filters['sort'] ?? 'created_at') === $field ? 'text-white' : 'text-gray-400 hover:bg-gray-100' }}"
                            style="{{ ($filters['sort'] ?? 'created_at') === $field ? 'background: var(--brand-600)' : '' }}">
                            {{ $label }}
                            @if (($filters['sort'] ?? 'created_at') === $field)
                                {{ ($filters['dir'] ?? 'desc') === 'desc' ? '↓' : '↑' }}
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($leads->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d1d5db"
                            stroke-width="1.5" stroke-linecap="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <p class="mb-1 font-semibold text-gray-500">No leads found</p>
                    <p class="mb-4 text-sm text-gray-400">
                        @if (array_filter($filters))
                            Try adjusting your filters
                        @else
                            Start adding leads to your pipeline
                        @endif
                    </p>

                    @if (has_permission('crm_leads.create'))
                        <a href="{{ route('admin.crm.leads.create') }}"
                            class="rounded-xl px-4 py-2 text-sm font-bold text-white"
                            style="background: var(--brand-600)">
                            Add First Lead
                        </a>
                    @endif
                </div>
            @else
                {{-- Table ── --}}
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px]">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="w-[40px] px-5 py-3 text-left">
                                    <input type="checkbox" @click="toggleAll($event)"
                                        :checked="selectedLeads.length > 0 &&
                                            selectedLeads.length === currentViewLeadIds.length"
                                        class="text-brand-600 focus:ring-brand-500 h-3.5 w-3.5 cursor-pointer rounded border-gray-300" />
                                </th>
                                <th
                                    class="w-[50px] px-2 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    #
                                </th>
                                <th
                                    class="w-[260px] px-5 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Lead
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Stage
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Source
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Priority
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Score
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Assigned
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Reminder
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Tasks
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leads as $lead)
                                @php
                                    $pColor = $priorityColors[$lead->priority] ?? $priorityColors['medium'];
                                    $initials = strtoupper(substr($lead->name, 0, 1));
                                    $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
                                    $avatarBg = $avatarColors[crc32($lead->name) % count($avatarColors)];
                                @endphp
                                <tr class="table-row">
                                    {{-- Checkbox --}}
                                    <td class="px-5 py-3">
                                        <input type="checkbox" value="{{ $lead->id }}" x-model="selectedLeads"
                                            class="text-brand-600 focus:ring-brand-500 h-3.5 w-3.5 cursor-pointer rounded border-gray-300" />
                                    </td>

                                    {{-- 🌟 Added Dynamic Continuous Numbering --}}
                                    <td class="px-2 py-3 text-[12px] font-bold text-gray-400">
                                        {{ $leads->firstItem() + $loop->index }}
                                    </td>

                                    {{-- Lead ── --}}
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <a href="{{ route('admin.crm.leads.show', $lead->id) }}">
                                                <div class="lead-avatar" style="background: {{ $avatarBg }}">
                                                    {{ $initials }}
                                                </div>
                                            </a>
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.crm.leads.show', $lead->id) }}"
                                                    class="block max-w-[160px] truncate text-[13px] font-bold text-gray-900 hover:underline">
                                                    {{ $lead->name }}
                                                </a>
                                                <p class="truncate text-[11px] font-medium text-gray-400">
                                                    {{ $lead->phone ?? ($lead->email ?? '—') }}
                                                </p>
                                                @if ($lead->company_name)
                                                    <p class="max-w-[160px] truncate text-[11px] text-gray-400">
                                                        {{ $lead->company_name }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Stage Update Dropdown --}}
                                    <td class="px-3 py-3">
                                        {{-- 1. Added max-w-32 (128px) and title attribute for the tooltip --}}
                                        @php
                                            $relevantStages = $stages->where('crm_pipeline_id', $lead->crm_pipeline_id);
                                            $canChangeStage = has_permission('crm_leads.change_stage');
                                        @endphp

                                        {{-- The native <select> stays in the DOM, invisible. updateLeadStage()
                                             reads select.value, select.options[i].dataset.color,
                                             select.closest("tr") and Alpine.$data(select.closest("[x-data]")),
                                             and rolls back with select.value on failure — so replacing the
                                             element outright would have broken all four. The visible control
                                             writes into it and dispatches change instead, leaving that
                                             function untouched.

                                             The panel is position:fixed because two ancestors clip it: the
                                             list container sets overflow-hidden and the table wrapper sets
                                             overflow-x-auto. --}}
                                        <div x-data="{
                                            currentStageColor: @js($lead->stage?->color ?? '#9ca3af'),
                                            open: false,
                                            above: false,
                                            value: @js((string) $lead->crm_stage_id),
                                            top: 0,
                                            left: 0,
                                            width: 0,
                                            place() {
                                                const r = this.$refs.trigger.getBoundingClientRect();
                                                this.left = r.left;
                                                this.width = Math.max(r.width, 160);
                                                this.above = (window.innerHeight - r.bottom) < 260;
                                                this.top = this.above ? r.top - 6 : r.bottom + 6;
                                            },
                                            toggle() {
                                                if (this.open) { this.open = false; return; }
                                                this.place();
                                                this.open = true;
                                            },
                                            pick(id) {
                                                this.open = false;
                                                this.value = String(id);
                                                this.$refs.nativeSelect.value = String(id);
                                                this.$refs.nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                            },
                                        }" @stage-reverted="value = $refs.nativeSelect.value"
                                            @keydown.escape.window="open = false" {{-- Capture on window sees the panel's OWN scroll as well, so
                                                 closing unconditionally made the list impossible to scroll.
                                                 Scrolls starting inside the panel are ignored; anything else
                                                 repositions it so it stays attached to its row. --}}
                                            @scroll.window.capture="if (open && !$refs.panel.contains($event.target)) place()"
                                            @resize.window="open = false" class="group relative inline-block w-[130px]"
                                            title="{{ $lead->stage?->name ?? '' }}">

                                            <select x-ref="nativeSelect"
                                                @if ($canChangeStage) @change="updateLeadStage($event, {{ $lead->id }}, @js((string) $lead->crm_stage_id))" @endif
                                                class="pointer-events-none absolute inset-0 h-full w-full opacity-0"
                                                aria-hidden="true">
                                                @foreach ($relevantStages as $st)
                                                    <option value="{{ $st->id }}"
                                                        {{ $lead->crm_stage_id == $st->id ? 'selected' : '' }}
                                                        data-color="{{ $st->color }}">
                                                        {{ $st->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="button" x-ref="trigger" role="combobox" :aria-expanded="open"
                                                @if ($canChangeStage) @click="toggle()" @endif
                                                class="flex w-full items-center gap-1 rounded-full border-none py-1 pr-2 pl-3 text-[11px] font-bold transition-all @if ($canChangeStage) cursor-pointer @endif"
                                                :style="`background: ${currentStageColor}18; color: ${currentStageColor}`">
                                                <span class="flex-1 truncate text-left">
                                                    @foreach ($relevantStages as $st)
                                                        <span
                                                            x-show="value === @js((string) $st->id)">{{ $st->name }}</span>
                                                    @endforeach
                                                </span>
                                                @if ($canChangeStage)
                                                    <svg class="h-3.5 w-3.5 shrink-0 opacity-50" width="8"
                                                        height="8" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="4" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path d="m6 9 6 6 6-6" />
                                                    </svg>
                                                @endif
                                            </button>

                                            @if ($canChangeStage)
                                                <div x-cloak x-show="open" x-ref="panel" @click.away="open = false"
                                                    role="listbox"
                                                    :style="`top:${top}px; left:${left}px; width:${width}px;` + (above ?
                                                        ' transform: translateY(-100%);' : '')"
                                                    class="fixed z-[70] max-h-64 overflow-y-auto rounded-xl border border-gray-100 bg-white p-1 shadow-xl"
                                                    style="display: none;">
                                                    @foreach ($relevantStages as $st)
                                                        <button type="button" role="option"
                                                            @click="pick(@js((string) $st->id))"
                                                            :class="value === @js((string) $st->id) ? 'bg-gray-50' :
                                                                'hover:bg-gray-50'"
                                                            class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-[12px] font-semibold text-gray-700 transition-colors">
                                                            <span class="h-3.5 w-3.5 shrink-0 rounded-full"
                                                                style="background: {{ $st->color }}"></span>
                                                            <span class="flex-1 truncate">{{ $st->name }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Source ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] font-medium text-gray-500">
                                            {{ $lead->source?->name ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- Priority ── --}}
                                    <td class="px-3 py-3">
                                        <span class="priority-badge"
                                            style="background: {{ $pColor['bg'] }}; color: {{ $pColor['text'] }}">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                style="background: {{ $pColor['dot'] }}"></span>
                                            {{ ucfirst($lead->priority) }}
                                        </span>
                                    </td>

                                    {{-- Score ── --}}
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[12px] font-black text-gray-700">
                                                {{ $lead->score }}
                                                <span class="text-[10px] font-semibold text-gray-400">
                                                    {{ $lead->score_label }}
                                                </span>
                                            </span>
                                            <div class="score-bar">
                                                <div class="score-fill"
                                                    style="width: {{ min(100, $lead->score) }}%;
                                                background: {{ $lead->score >= 50 ? '#ef4444' : ($lead->score >= 20 ? '#f59e0b' : '#9ca3af') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Assigned ── --}}
                                    <td class="px-3 py-3">
                                        @php $assignee = $lead->assignees->first(); @endphp
                                        @if ($assignee)
                                            <div class="flex items-center gap-1.5">
                                                <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                                    style="background: var(--brand-600)">
                                                    {{ strtoupper(substr($assignee->name, 0, 1)) }}
                                                </div>
                                                <span
                                                    class="max-w-[80px] truncate text-[11px] font-semibold text-gray-600">
                                                    {{ $assignee->name }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-[11px] font-medium text-gray-300">Unassigned</span>
                                        @endif
                                    </td>

                                    {{-- Follow-up ── --}}
                                    <td class="px-3 py-3">
                                        @if ($lead->next_followup_at)
                                            <span
                                                class="text-[11px] font-semibold
                                            {{ $lead->is_overdue ? 'text-red-600' : 'text-gray-600' }}">
                                                {{ $lead->is_overdue ? '⚠ ' : '' }}{{ $lead->next_followup_at->format('d M') }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Tasks ── --}}
                                    <td class="px-3 py-3">
                                        @if ($lead->pending_tasks_count > 0)
                                            <span
                                                class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-600">
                                                {{ $lead->pending_tasks_count }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Actions ── --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- WhatsApp ── --}}
                                            @if ($lead->phone)
                                                <a href="{{ $lead->whatsapp_url }}" target="_blank"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-green-50 hover:text-green-600"
                                                    title="WhatsApp">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M20.52 3.48A11.8 11.8 0 0 0 12.07 0C5.5 0 .16 5.34.16 11.91c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.9 11.9 0 0 0 5.77 1.47h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.47-8.43zM12.08 21.8a9.9 9.9 0 0 1-5.05-1.39l-.36-.21-3.74.98 1-3.64-.24-.37a9.87 9.87 0 0 1-1.52-5.26c0-5.46 4.45-9.91 9.92-9.91 2.65 0 5.13 1.03 7 2.91a9.86 9.86 0 0 1 2.9 7c0 5.47-4.44 9.91-9.9 9.91zm5.44-7.42c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.42.25-.69.25-1.28.17-1.41-.07-.12-.27-.2-.57-.35z" />
                                                    </svg>
                                                </a>
                                            @endif

                                            {{-- Edit ── --}}
                                            @if (has_permission('crm_leads.update'))
                                                <a href="{{ route('admin.crm.leads.edit', $lead->id) }}"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                                    title="Edit">
                                                    <svg width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round">
                                                        <path
                                                            d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                </a>
                                            @endif

                                            {{-- Delete ── --}}
                                            @if (has_permission('crm_leads.delete'))
                                                <button
                                                    @click="deleteLead({{ $lead->id }}, @js($lead->name))"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                    title="Delete">
                                                    <svg width="16" height="16" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round">
                                                        <polyline points="3 6 5 6 21 6" />
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                        <path d="M10 11v6" />
                                                        <path d="M14 11v6" />
                                                        <path d="M9 6V4h6v2" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination ── --}}
                @if ($leads->hasPages())
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-50 px-5 py-4">
                        <p class="text-[12px] font-medium text-gray-400">
                            Showing {{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}
                        </p>
                        <div class="flex items-center gap-1">
                            {{-- Prev ── --}}
                            @if ($leads->onFirstPage())
                                <span
                                    class="cursor-not-allowed rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-300">←
                                    Prev</span>
                            @else
                                <a href="{{ $leads->previousPageUrl() }}"
                                    class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-100">←
                                    Prev</a>
                            @endif

                            {{-- Pages ── --}}
                            @foreach ($leads->getUrlRange(max(1, $leads->currentPage() - 2), min($leads->lastPage(), $leads->currentPage() + 2)) as $page => $url)
                                <a href="{{ $url }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-[12px] font-bold transition-colors
                                {{ $page == $leads->currentPage() ? 'text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                    style="{{ $page == $leads->currentPage() ? 'background: var(--brand-600)' : '' }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            {{-- Next ── --}}
                            @if ($leads->hasMorePages())
                                <a href="{{ $leads->nextPageUrl() }}"
                                    class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-100">Next
                                    →</a>
                            @else
                                <span
                                    class="cursor-not-allowed rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-300">Next
                                    →</span>
                            @endif
                        </div>
                    </div>
                @endif

            @endif
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{--  BULK ACTIONS MODAL                             --}}
        {{-- ═══════════════════════════════════════════════ --}}
        <div x-show="bulkModal" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
            style="background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(2px)">
            <div @click.outside="bulkModal = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="flex w-full flex-col rounded-t-3xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl"
                style="max-height: 92vh">
                {{-- ── Header (Rounded top added to preserve container corner background layouts) ── --}}
                <div
                    class="flex items-center gap-3 rounded-t-3xl border-b border-gray-100 px-5 pt-5 pb-4 sm:rounded-t-2xl">
                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl"
                        style="background: var(--brand-50, #eff6ff)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="var(--brand-600, #2563eb)" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-[14px] leading-tight font-black text-gray-900">Bulk Actions</h3>
                        <p class="mt-0.5 text-[11px] font-medium text-gray-400">Applying to <span
                                class="font-black text-gray-600" x-text="selectedLeads.length"></span> selected lead(s)
                        </p>
                    </div>
                    <button @click="bulkModal = false"
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- ── Body (scrollable) ── --}}
                <div class="flex-1 space-y-1 overflow-y-auto px-5 py-4">
                    {{-- Move to Stage --}}
                    <div class="group">
                        <button type="button" @click="bulkOpen.stage = !bulkOpen.stage"
                            class="flex w-full items-center gap-3 py-3 text-left">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-purple-50">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c3aed"
                                    stroke-width="2" stroke-linecap="round">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </span>
                            <span class="flex-1">
                                <span class="block text-[12px] font-black text-gray-800">Move to Stage</span>
                                <span class="text-[10px] font-medium text-gray-400"
                                    x-text="bulk.stage ? 'Stage selected ✓' : 'Not set'"></span>
                            </span>
                            <svg class="h-4 w-4 text-gray-300 transition-transform"
                                :class="bulkOpen.stage ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <div x-show="bulkOpen.stage" x-collapse class="pb-3 pl-11">
                            <select x-model="bulk.stage"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-[12px] font-semibold text-gray-700 transition-all outline-none focus:border-purple-400 focus:bg-white">
                                <option value="">Select a stage</option>
                                @foreach ($stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="h-px bg-gray-50"></div>

                    {{-- Set Priority --}}
                    <div>
                        <button type="button" @click="bulkOpen.priority = !bulkOpen.priority"
                            class="flex w-full items-center gap-3 py-3 text-left">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-orange-50">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c"
                                    stroke-width="2" stroke-linecap="round">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
                                    <line x1="4" y1="22" x2="4" y2="15" />
                                </svg>
                            </span>
                            <span class="flex-1">
                                <span class="block text-[12px] font-black text-gray-800">Set Priority</span>
                                <span class="text-[10px] font-medium text-gray-400"
                                    x-text="
                                        bulk.priority
                                            ? bulk.priority.charAt(0).toUpperCase() +
                                              bulk.priority.slice(1) +
                                              ' selected ✓'
                                            : 'Not set'
                                    "></span>
                            </span>
                            <svg class="h-4 w-4 text-gray-300 transition-transform"
                                :class="bulkOpen.priority ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <div x-show="bulkOpen.priority" x-collapse class="pb-3 pl-11">
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ([
            'hot' => ['🔥', '#ef4444', '#fef2f2'],
            'high' => ['⬆️', '#f97316', '#fff7ed'],
            'medium' => ['➡️', '#eab308', '#fefce8'],
            'low' => ['⬇️', '#3b82f6', '#eff6ff'],
        ] as $p => [$icon, $color, $bg])
                                    <button type="button"
                                        @click="bulk.priority = (bulk.priority === '{{ $p }}' ? '' : '{{ $p }}')"
                                        class="relative flex flex-col items-center gap-1 rounded-xl border-2 py-2.5 text-[11px] font-bold transition-all"
                                        :style="bulk.priority === '{{ $p }}' ?
                                            'background: {{ $bg }}; border-color: {{ $color }}; color: {{ $color }}' :
                                            'background: #f9fafb; border-color: #f3f4f6; color: #6b7280'">
                                        <span class="text-base leading-none">{{ $icon }}</span>
                                        <span>{{ ucfirst($p) }}</span>
                                        <span x-show="bulk.priority === '{{ $p }}'"
                                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-black text-white"
                                            style="background: {{ $color }}">✓</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="h-px bg-gray-50"></div>

                    {{-- Change Source --}}
                    <div>
                        <button type="button" @click="bulkOpen.source = !bulkOpen.source"
                            class="flex w-full items-center gap-3 py-3 text-left">
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-cyan-50">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0891b2"
                                    stroke-width="2" stroke-linecap="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="2" y1="12" x2="22" y2="12" />
                                    <path
                                        d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                                </svg>
                            </span>
                            <span class="flex-1">
                                <span class="block text-[12px] font-black text-gray-800">Change Source</span>
                                <span class="text-[10px] font-medium text-gray-400"
                                    x-text="bulk.source ? 'Source selected ✓' : 'Not set'"></span>
                            </span>
                            <svg class="h-4 w-4 text-gray-300 transition-transform"
                                :class="bulkOpen.source ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <div x-show="bulkOpen.source" x-collapse class="pb-3 pl-11">
                            <select x-model="bulk.source"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-[12px] font-semibold text-gray-700 transition-all outline-none focus:border-cyan-400 focus:bg-white">
                                <option value="">Select a source</option>
                                @foreach ($sources as $src)
                                    <option value="{{ $src->id }}">{{ $src->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="h-px bg-gray-50"></div>

                    {{-- Assign To — reassigning someone else's work is a
                         manager action, so it is not offered to a rep who only
                         sees their own leads. --}}
                    @if ($canSeeAll)
                        <div>
                            <button type="button" @click="bulkOpen.assign = !bulkOpen.assign"
                                class="flex w-full items-center gap-3 py-3 text-left">
                                <span
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-50">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="#16a34a" stroke-width="2" stroke-linecap="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                </span>
                                <span class="flex-1">
                                    <span class="block text-[12px] font-black text-gray-800">Assign To</span>
                                    <span class="text-[10px] font-medium text-gray-400"
                                        x-text="bulk.assign ? 'User selected ✓' : 'Not set'"></span>
                                </span>
                                <svg class="h-4 w-4 text-gray-300 transition-transform"
                                    :class="bulkOpen.assign ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <div x-show="bulkOpen.assign" x-collapse class="space-y-2 pb-3 pl-11">
                                <x-user-picker name="bulk_assign" :users="$users" x-model="bulk.assign"
                                    placeholder="Search team member..." />
                                <button type="button" x-show="bulk.assign" x-cloak @click="bulk.assign = ''"
                                    class="text-[10px] font-bold text-gray-400 hover:text-red-500">
                                    Clear selection
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if ($tags->isNotEmpty())
                        <div class="h-px bg-gray-50"></div>
                        <div>
                            <button type="button" @click="bulkOpen.tags = !bulkOpen.tags"
                                class="flex w-full items-center gap-3 py-3 text-left">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-pink-50">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="#db2777" stroke-width="2" stroke-linecap="round">
                                        <path
                                            d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z" />
                                        <line x1="7" y1="7" x2="7.01" y2="7" />
                                    </svg>
                                </span>
                                <span class="flex-1">
                                    <span class="block text-[12px] font-black text-gray-800">Add Tags</span>
                                    <span class="text-[10px] font-medium text-gray-400"
                                        x-text="bulk.tags.length ? bulk.tags.length + ' tag(s) selected ✓' : 'Not set'"></span>
                                </span>
                                <svg class="h-4 w-4 text-gray-300 transition-transform"
                                    :class="bulkOpen.tags ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <div x-show="bulkOpen.tags" x-collapse class="pb-3 pl-11">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($tags as $tag)
                                        <button type="button"
                                            @click="bulk.tags.includes({{ $tag->id }}) ? bulk.tags.splice(bulk.tags.indexOf({{ $tag->id }}),1) : bulk.tags.push({{ $tag->id }})"
                                            class="relative rounded-xl border px-3 py-1.5 text-[11px] font-bold transition-all"
                                            :style="bulk.tags.includes({{ $tag->id }}) ?
                                                'background: {{ $tag->color }}22; border-color: {{ $tag->color }}; color: {{ $tag->color }}' :
                                                'background: #f9fafb; border-color: #e5e7eb; color: #6b7280'">
                                            <span x-show="bulk.tags.includes({{ $tag->id }})"
                                                class="mr-1">✓</span>#{{ $tag->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── Danger Zone ── --}}
                    @if (has_permission('crm_leads.update') || has_permission('crm_leads.delete'))
                        <div class="mt-2 space-y-2 border-t border-dashed border-gray-200 pt-3">
                            <p class="px-1 text-[10px] font-black tracking-widest text-gray-300 uppercase">Danger Zone</p>

                            @if (has_permission('crm_leads.update'))
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-3 transition-colors"
                                    :class="bulk.mark_lost ?
                                        'bg-orange-50 border-orange-200' :
                                        'bg-gray-50 border-gray-100 hover:bg-orange-50 hover:border-orange-100'">
                                    <div class="relative flex-shrink-0">
                                        <input type="checkbox" x-model="bulk.mark_lost" class="sr-only" />
                                        <div class="flex h-5 w-5 items-center justify-center rounded-md border-2 transition-all"
                                            :style="bulk.mark_lost ?
                                                'background:#f97316; border-color:#f97316' :
                                                'border-color:#d1d5db; background:white'">
                                            <svg x-show="bulk.mark_lost" width="10" height="10"
                                                viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3.5"
                                                stroke-linecap="round">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-[12px] font-bold"
                                            :class="bulk.mark_lost ? 'text-orange-700' : 'text-gray-600'">Mark as
                                            Lost</span>
                                        <span class="text-[10px] text-gray-400">These leads will be flagged as lost</span>
                                    </div>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="#f97316" stroke-width="2" stroke-linecap="round"
                                        x-show="bulk.mark_lost">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="15" y1="9" x2="9" y2="15" />
                                        <line x1="9" y1="9" x2="15" y2="15" />
                                    </svg>
                                </label>
                            @endif

                            @if (has_permission('crm_leads.delete'))
                                <label
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-3 transition-colors"
                                    :class="bulk.delete ?
                                        'bg-red-50 border-red-200' :
                                        'bg-gray-50 border-gray-100 hover:bg-red-50 hover:border-red-100'">
                                    <div class="relative flex-shrink-0">
                                        <input type="checkbox" x-model="bulk.delete" class="sr-only" />
                                        <div class="flex h-5 w-5 items-center justify-center rounded-md border-2 transition-all"
                                            :style="bulk.delete ?
                                                'background:#ef4444; border-color:#ef4444' :
                                                'border-color:#d1d5db; background:white'">
                                            <svg x-show="bulk.delete" width="10" height="10" viewBox="0 0 24 24"
                                                fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <span class="block text-[12px] font-bold"
                                            :class="bulk.delete ? 'text-red-700' : 'text-gray-600'">Delete Leads</span>
                                        <span class="text-[10px] text-gray-400">Permanently removes selected leads</span>
                                    </div>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="#ef4444" stroke-width="2" stroke-linecap="round" x-show="bulk.delete">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                    </svg>
                                </label>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ── Active Summary Bar ── --}}
                <div x-show="
                        bulk.stage ||
                        bulk.priority ||
                        bulk.source ||
                        bulk.assign ||
                        bulk.tags.length ||
                        bulk.mark_lost ||
                        bulk.delete
                    "
                    class="border-t border-gray-100 bg-gray-50 px-5 py-2.5">
                    <p class="flex flex-wrap gap-1.5 text-[10px] font-semibold text-gray-400">
                        <span class="font-black text-gray-500">Will apply:</span>
                        <span x-show="bulk.stage"
                            class="rounded-md bg-purple-100 px-2 py-0.5 font-bold text-purple-700">Stage</span>
                        <span x-show="bulk.priority"
                            class="rounded-md bg-orange-100 px-2 py-0.5 font-bold text-orange-700">Priority</span>
                        <span x-show="bulk.source"
                            class="rounded-md bg-cyan-100 px-2 py-0.5 font-bold text-cyan-700">Source</span>
                        @if ($canSeeAll)
                            <span x-show="bulk.assign"
                                class="rounded-md bg-green-100 px-2 py-0.5 font-bold text-green-700">Assign</span>
                        @endif
                        <span x-show="bulk.tags.length"
                            class="rounded-md bg-pink-100 px-2 py-0.5 font-bold text-pink-700">Tags</span>
                        <span x-show="bulk.mark_lost"
                            class="rounded-md bg-orange-100 px-2 py-0.5 font-bold text-orange-700">Mark Lost</span>
                        <span x-show="bulk.delete"
                            class="rounded-md bg-red-100 px-2 py-0.5 font-bold text-red-700">Delete</span>
                    </p>
                </div>

                {{-- ── Footer ── --}}
                <div class="flex gap-2.5 border-t border-gray-100 px-5 py-4">
                    <button @click="bulkModal = false"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="executeBulkAction()" :disabled="bulkLoading"
                        class="bg-brand-500 flex-1 rounded-xl py-2.5 text-[12px] font-bold text-white transition-all"
                        :style="bulkLoading ? 'opacity:0.65; cursor:not-allowed' : 'cursor:pointer'">
                        <span x-show="!bulkLoading" class="flex items-center justify-center gap-1.5">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Apply Actions
                        </span>
                        <span x-show="bulkLoading" class="flex items-center justify-center gap-1.5">
                            <svg class="animate-spin" width="12" height="12" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function leadsPage() {
            return {
                selectedLeads: [],
                bulkModal: false,
                bulkLoading: false,
                bulk: {
                    stage: "",
                    source: "",
                    assign: "",
                    tags: [],
                    priority: "",
                    mark_lost: false,
                    delete: false
                },
                bulkOpen: {
                    stage: false,
                    priority: false,
                    source: false,
                    assign: false,
                    tags: false
                },
                currentViewLeadIds: @json ($leads->pluck('id')),

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,
                suspendAutoSubmit: false,
                requestSeq: 0,

                init() {
                    this.checkActiveFilters();
                    window.submitLeadForm = () => this.submitForm();
                },

                handleLinkClick(e) {
                    // "?page=" misses every pagination link once a filter is applied,
                    // because page is then no longer the first parameter.
                    const pageOrSortLink = e.target.closest('a[href*="page="], a[href*="sort="]');
                    if (!pageOrSortLink || pageOrSortLink.closest(".stat-card")) return;

                    e.preventDefault();
                    // The layout's SPA link interceptor listens on document and does not
                    // check defaultPrevented, so without this the click fires twice.
                    e.stopPropagation();

                    this.fetchResults(pageOrSortLink.href);
                },

                checkActiveFilters() {
                    const form = document.getElementById("filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    if (this.suspendAutoSubmit) return;

                    const form = document.getElementById("filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("filter-form");
                    if (!form) return;

                    // Each field used to fire a change event as it was cleared, and the
                    // form's @change re-submitted while the remaining fields still held
                    // their old values. Those stale requests raced the clean one.
                    this.suspendAutoSubmit = true;

                    form.querySelectorAll('input[type="text"], input[type="search"], select').forEach((el) => {
                        el.value = "";
                        el.dispatchEvent(new Event("change", {
                            bubbles: true
                        }));
                    });

                    // Components that keep their value in Alpine state rather than in a
                    // native field cannot be cleared by writing to the DOM — the binding
                    // puts the old value straight back. They listen for this instead.
                    window.dispatchEvent(new CustomEvent("filters-cleared"));

                    this.suspendAutoSubmit = false;

                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("leads-list-container");
                    if (!targetContainer) return;

                    // Debounced typing leaves several requests in flight. Only the
                    // newest may paint, or a slow early response overwrites a newer
                    // one and puts a stale URL in the address bar.
                    const seq = ++this.requestSeq;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    // Clear selections when fetching new data
                    this.selectedLeads = [];

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            if (seq !== this.requestSeq) return;

                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("leads-list-container");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            // The swapped rows carry Alpine bindings for the stage
                            // select and the delete buttons, so the new nodes have
                            // to be walked or those controls go dead.
                            if (window.Alpine?.initTree) window.Alpine.initTree(targetContainer);
                            if (typeof lucide !== "undefined") lucide.createIcons();
                        })
                        .catch(() => {
                            if (seq !== this.requestSeq) return;
                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                        });
                },

                toggleAll(event) {
                    if (event.target.checked) {
                        this.selectedLeads = [...this.currentViewLeadIds];
                    } else {
                        this.selectedLeads = [];
                    }
                },

                async executeBulkAction() {
                    const b = this.bulk;
                    const ids = this.selectedLeads;

                    const actions = [];
                    if (b.stage) actions.push({
                        action: "stage",
                        value: b.stage
                    });
                    if (b.priority) actions.push({
                        action: "priority",
                        value: b.priority
                    });
                    if (b.source) actions.push({
                        action: "source",
                        value: b.source
                    });
                    if (b.assign) actions.push({
                        action: "assign",
                        value: b.assign
                    });
                    if (b.tags.length) actions.push({
                        action: "tags",
                        value: b.tags
                    });
                    if (b.mark_lost) actions.push({
                        action: "mark_lost",
                        value: null
                    });
                    if (b.delete) actions.push({
                        action: "delete",
                        value: null
                    });

                    if (actions.length === 0) {
                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 2000,
                            icon: "info",
                            title: "No action selected.",
                        });
                        return;
                    }

                    if (b.delete) {
                        const c = await BizAlert.confirm(
                            "Delete Selected Leads?",
                            `${ids.length} lead(s) will be permanently deleted.`,
                            "Yes, delete them",
                        );
                        if (!c.isConfirmed) return;
                    }

                    this.bulkLoading = true;
                    const headers = {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "X-Requested-With": "XMLHttpRequest",
                    };
                    const results = [];

                    for (const act of actions) {
                        try {
                            const url =
                                act.action === "delete" ?
                                `{{ route('admin.crm.leads.bulk_destroy') }}` :
                                `{{ route('admin.crm.leads.bulk_action') }}`;

                            const body = act.action === "delete" ? {
                                ids
                            } : {
                                ids,
                                action: act.action,
                                value: act.value
                            };

                            const res = await fetch(url, {
                                method: "POST",
                                headers,
                                body: JSON.stringify(body)
                            });
                            const data = await res.json();
                            results.push({
                                action: act.action,
                                ok: data.success,
                                msg: data.message
                            });
                        } catch (e) {
                            results.push({
                                action: act.action,
                                ok: false,
                                msg: "Network error"
                            });
                        }
                    }

                    this.bulkLoading = false;
                    this.bulkModal = false;
                    this.bulk = {
                        stage: "",
                        source: "",
                        assign: "",
                        tags: [],
                        priority: "",
                        mark_lost: false,
                        delete: false
                    };

                    const allOk = results.every((r) => r.ok);
                    const anyOk = results.some((r) => r.ok);
                    const msgs = results.map((r) => `${r.ok ? "✅" : "❌"} ${r.msg}`).join("<br>");

                    if (allOk) {
                        BizAlert.toast(results.map((r) => r.msg).join(" · "), "success");
                    } else {
                        await Swal.fire({
                            ...BizAlert.baseConfig,
                            icon: anyOk ? "warning" : "error",
                            title: anyOk ? "Partial Success" : "Action Failed",
                            html: `<div class="text-left space-y-1 mt-1">${msgs}</div>`,
                        });
                    }

                    if (anyOk) window.location.reload();
                },

                async bulkDeleteLeads() {
                    if (this.selectedLeads.length === 0) return;

                    const c = await Swal.fire({
                        title: "Delete Selected Leads?",
                        text: `You are about to delete ${this.selectedLeads.length} lead(s). This can be recovered.`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, delete them",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#ef4444",
                    });

                    if (!c.isConfirmed) return;

                    try {
                        const res = await fetch(`{{ route('admin.crm.leads.bulk_destroy') }}`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({
                                ids: this.selectedLeads
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                icon: "success",
                                title: data.message,
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire("Error", data.message, "error");
                        }
                    } catch (e) {
                        Swal.fire("Error", "Network error. Please try again.", "error");
                    }
                },

                async updateLeadStage(event, leadId, oldStageId) {
                    const select = event.target;
                    const newStageId = select.value;
                    const newColor = select.options[select.selectedIndex].dataset.color;

                    // UI Feedback: Dim the row while saving
                    const row = select.closest("tr");
                    row.style.opacity = "0.6";

                    try {
                        const res = await fetch(`/admin/crm/leads/${leadId}/stage`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({
                                stage_id: newStageId,
                                note: "Quick stage update from list view",
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            // 1. Update the color in Alpine state
                            // Since we used x-data on the container, we find it via the event
                            const alpineData = Alpine.$data(select.closest("[x-data]"));
                            alpineData.currentStageColor = newColor;

                            // Move the revert target forward. It was captured once at
                            // page load, so a failure after an earlier successful change
                            // rolled the dropdown back to the original stage and left it
                            // disagreeing with what the database actually holds.
                            oldStageId = newStageId;

                            // 2. Success Toast
                            Swal.fire({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 2000,
                                icon: "success",
                                title: data.message,
                            });
                        } else {
                            throw new Error(data.message);
                        }
                    } catch (error) {
                        // Revert on error
                        select.value = oldStageId;

                        // Setting .value fires no event, so the visible trigger would
                        // keep showing the stage that failed to save. A plain "change"
                        // would re-enter this same handler, hence a custom one.
                        select.dispatchEvent(new CustomEvent("stage-reverted", {
                            bubbles: true
                        }));

                        Swal.fire("Update Failed", error.message || "Server error", "error");
                    } finally {
                        row.style.opacity = "1";
                    }
                },

                async deleteLead(id, name) {
                    const c = await Swal.fire({
                        title: "Delete Lead?",
                        text: `"${name}" will be soft deleted. This can be recovered.`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, delete",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#ef4444",
                    });

                    if (!c.isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/crm/leads/${id}`, {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        });
                        const data = await res.json();
                        if (data.success) {
                            // Remove row from table
                            const row = document.querySelector(`button[\\@click*="${id}"]`)?.closest("tr");
                            if (row) {
                                row.style.opacity = "0";
                                row.style.transition = "opacity 200ms ease";
                                setTimeout(() => row.remove(), 200);
                            }
                        } else {
                            Swal.fire("Error", data.message, "error");
                        }
                    } catch (e) {
                        Swal.fire("Error", "Network error. Please try again.", "error");
                    }
                },
            };
        }
    </script>
@endpush
