@extends('layouts.admin')

@section('title', 'Holidays')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Holidays</h1>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ── Form fields ── */
        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13.5px;
            outline: none;
            transition: border-color 150ms ease, box-shadow 150ms ease;
            font-family: inherit;
            background: #fff;
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        .field-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Toggle switch ── */
        .toggle-switch {
            position: relative;
            width: 36px;
            height: 20px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: #e5e7eb;
            border-radius: 20px;
            cursor: pointer;
            transition: background 200ms ease;
        }

        .toggle-switch input:checked+.toggle-track {
            background: var(--brand-600);
        }

        .toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 200ms ease;
            pointer-events: none;
        }

        .toggle-switch input:checked~.toggle-thumb {
            transform: translateX(16px);
        }

        /* ── Stat cards ── */
        .stat-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 14px;
            padding: 14px 16px;
            transition: box-shadow 150ms, border-color 150ms;
        }

        .stat-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        /* ── Table rows ── */
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

        /* ── Calendar grid ── */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .cal-header-cell {
            padding: 10px 4px;
            text-align: center;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            border-bottom: 1.5px solid #f1f5f9;
        }

        .cal-cell {
            min-height: 100px;
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            padding: 6px;
            position: relative;
            transition: background 120ms;
        }

        .cal-cell:nth-child(7n) {
            border-right: none;
        }

        .cal-cell.other-month {
            background: #fafbfc;
        }

        .cal-cell.today {
            background: color-mix(in srgb, var(--brand-600) 5%, #fff);
        }

        .cal-cell.has-holiday {
            cursor: pointer;
        }

        .cal-cell.has-holiday:hover {
            background: #f8fafc;
        }

        .cal-cell.empty-clickable {
            cursor: pointer;
        }

        .cal-cell.empty-clickable:hover {
            background: color-mix(in srgb, var(--brand-600) 4%, #fff);
        }

        .cal-day-number {
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            line-height: 1;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 4px;
        }

        .cal-day-number.today-num {
            background: var(--brand-600);
            color: #fff;
        }

        .cal-day-number.weekend {
            color: #f87171;
        }

        .cal-day-number.weekend.today-num {
            background: #ef4444;
            color: #fff;
        }

        .cal-badge {
            display: block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 5px;
            border: 1px solid;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
            cursor: pointer;
            transition: opacity 120ms;
            line-height: 1.4;
        }

        .cal-badge:hover {
            opacity: 0.75;
        }

        .cal-more {
            font-size: 9px;
            font-weight: 800;
            color: #9ca3af;
            padding: 1px 4px;
            cursor: pointer;
            transition: color 120ms;
        }

        .cal-more:hover {
            color: var(--brand-600);
        }

        /* ── View toggle ── */
        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            transition: all 120ms;
        }

        .view-btn.active {
            background: var(--brand-600);
            color: #fff;
        }

        .view-btn:not(.active) {
            background: #fff;
            color: #6b7280;
            border: 1.5px solid #e5e7eb;
        }

        .view-btn:not(.active):hover {
            border-color: var(--brand-600);
            color: var(--brand-600);
        }

        /* ── Calendar overflow popup ── */
        .overflow-popup {
            position: absolute;
            z-index: 30;
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px;
            min-width: 160px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        /* ── Month nav ── */
        .month-nav-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            transition: all 120ms;
            color: #6b7280;
        }

        .month-nav-btn:hover {
            border-color: var(--brand-600);
            color: var(--brand-600);
        }
    </style>
@endpush

@php
    $typeColors = \App\Models\Hrm\Holiday::TYPE_COLORS;
    $typeLabels = \App\Models\Hrm\Holiday::TYPE_LABELS;
    $currentYear = now()->year;
    $currentMonth = now()->month;
@endphp

@section('content')

    <div class="pb-10" x-data="holidayPage()" x-cloak>

        {{-- ════════════════════════════════════════════════════════
         STAT CARDS
    ════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Upcoming</p>
                <p class="text-2xl font-black text-green-600">{{ $stats['upcoming'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">National</p>
                <p class="text-2xl font-black text-red-600">{{ $stats['national'] }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Company</p>
                <p class="text-2xl font-black text-blue-600">{{ $stats['company'] }}</p>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
         TOOLBAR  (filters + view toggle + add button)
    ════════════════════════════════════════════════════════ --}}
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <form method="GET" action="{{ route('admin.hrm.holidays.index') }}" id="holiday-filter-form">
                <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">

                    {{-- Filters --}}
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full xl:w-auto">
                        <div class="relative w-full sm:w-auto sm:flex-1 min-w-[200px]">
                            <i data-lucide="search"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                            <input type="text" x-model="searchQuery" placeholder="Search holidays…"
                                class="w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2.5 text-[13px] text-gray-700 focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a] outline-none transition-all placeholder-gray-400 bg-white">
                        </div>

                        <div class="w-full sm:w-[110px]">
                            <x-alpine-select 
                                name="year" 
                                model="selectedYear"
                                items="yearOptions"
                                on-change="document.getElementById('holiday-filter-form').submit()"
                                :allow-empty="false" 
                            />
                        </div>

                        <div class="w-full sm:w-[140px]">
                            <x-alpine-select 
                                name="month" 
                                model="selectedMonth"
                                items="monthOptions"
                                on-change="document.getElementById('holiday-filter-form').submit()"
                                :allow-empty="false" 
                            />
                        </div>

                        <div class="w-full sm:w-[160px]">
                            <x-alpine-select 
                                name="type" 
                                model="selectedType"
                                items="typeOptions"
                                placeholder="All Types"
                                on-change="document.getElementById('holiday-filter-form').submit()"
                                :allow-empty="true" 
                            />
                        </div>

                        @if ($selectedType || $selectedMonth)
                            <a href="{{ route('admin.hrm.holidays.index', ['year' => $selectedYear]) }}"
                                class="text-[12px] font-bold px-3 py-2.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors whitespace-nowrap flex items-center justify-center gap-1.5">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear
                            </a>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
                            <button type="button" @click="view = 'calendar'"
                                :class="view === 'calendar' ? 'bg-white shadow-sm text-brand-600 font-bold' :
                                    'text-gray-500 hover:text-gray-700'"
                                class="px-3 py-2 rounded-md text-sm transition-all flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Calendar</span>
                            </button>
                            <button type="button" @click="view = 'list'"
                                :class="view === 'list' ? 'bg-white shadow-sm text-brand-600 font-bold' :
                                    'text-gray-500 hover:text-gray-700'"
                                class="px-3 py-2 rounded-md text-sm transition-all flex items-center gap-1.5">
                                <i data-lucide="list" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">List</span>
                            </button>
                        </div>

                        @if (has_permission('holidays.create'))
                            <button type="button" @click="openCreate()"
                                class="inline-flex items-center gap-1.5 text-[13px] font-bold px-4 py-2.5 rounded-lg text-white hover:opacity-90 transition-opacity whitespace-nowrap"
                                style="background: var(--brand-600)">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Add Holiday</span>
                                <span class="sm:hidden">Add</span>
                            </button>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- ════════════════════════════════════════════════════════
         CALENDAR VIEW
    ════════════════════════════════════════════════════════ --}}
        <div x-show="view === 'calendar'" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

            {{-- Month Navigator ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl mb-3 px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" class="month-nav-btn" @click="prevMonth()">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                    <div class="text-center min-w-[160px]">
                        <p class="text-[15px] font-black text-gray-800" x-text="currentMonthLabel"></p>
                        <p class="text-[10px] font-bold text-gray-400" x-text="calYear"></p>
                    </div>
                    <button type="button" class="month-nav-btn" @click="nextMonth()">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Today button ── --}}
                <button type="button" @click="goToday()"
                    class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:border-brand-600 hover:text-brand-600 transition-colors"
                    style="transition: border-color 120ms, color 120ms">
                    Today
                </button>
            </div>

            {{-- Calendar Grid ── --}}
            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

                {{-- Day-of-week headers ── --}}
                <div class="cal-grid border-b border-gray-100">
                    <div class="cal-header-cell" style="color:#ef4444">Sun</div>
                    <div class="cal-header-cell">Mon</div>
                    <div class="cal-header-cell">Tue</div>
                    <div class="cal-header-cell">Wed</div>
                    <div class="cal-header-cell">Thu</div>
                    <div class="cal-header-cell">Fri</div>
                    <div class="cal-header-cell" style="color:#f59e0b">Sat</div>
                </div>

                {{-- Day cells ── --}}
                <div class="cal-grid">
                    <template x-for="(cell, idx) in calendarCells" :key="idx">
                        <div class="cal-cell"
                            :class="{
                                'other-month': !cell.inMonth,
                                'today': cell.isToday,
                                'has-holiday': cell.inMonth && cell.holidays.length > 0,
                                'empty-clickable': cell.inMonth && cell.holidays.length === 0
                            }"
                            @click="cell.inMonth && cell.holidays.length === 0 && openCreate(cell.dateStr)">

                            {{-- Day number ── --}}
                            <div class="cal-day-number"
                                :class="{
                                    'today-num': cell.isToday,
                                    'weekend': cell.isWeekend && !cell.isToday
                                }"
                                x-text="cell.day">
                            </div>

                            {{-- Holiday badges (max 2 visible) ── --}}
                            <template x-for="(h, hi) in cell.holidays.slice(0, 2)" :key="h.id">
                                <span class="cal-badge"
                                    :style="`background:${h.type_color.bg}; color:${h.type_color.text}; border-color:${h.type_color.border}`"
                                    @click.stop="openEdit(h)" x-text="h.name">
                                </span>
                            </template>

                            {{-- "+X more" overflow trigger ── --}}
                            <template x-if="cell.holidays.length > 2">
                                <div class="relative">
                                    <span class="cal-more" @click.stop="toggleOverflow(cell.dateStr, $event)"
                                        x-text="`+${cell.holidays.length - 2} more`">
                                    </span>

                                    {{-- Overflow popup ── --}}
                                    <div class="overflow-popup" x-show="overflowDate === cell.dateStr" x-cloak
                                        @click.outside="overflowDate = null">
                                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1"
                                            x-text="formatDateLabel(cell.dateStr)">
                                        </p>
                                        <template x-for="h in cell.holidays" :key="h.id">
                                            <span class="cal-badge mb-1"
                                                :style="`background:${h.type_color.bg}; color:${h.type_color.text}; border-color:${h.type_color.border}`"
                                                @click.stop="openEdit(h); overflowDate = null" x-text="h.name">
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </template>
                </div>
            </div>

            {{-- Legend ── --}}
            <div class="flex items-center gap-4 flex-wrap mt-3 px-1">
                @foreach ($typeColors as $type => $colors)
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm border flex-shrink-0"
                            style="background: {{ $colors['bg'] }}; border-color: {{ $colors['border'] }}"></span>
                        <span class="text-[11px] font-semibold text-gray-500">{{ $typeLabels[$type] }}</span>
                    </div>
                @endforeach
                <div class="flex items-center gap-1.5 ml-auto">
                    <span class="w-5 h-5 rounded-full flex-shrink-0" style="background: var(--brand-600)"></span>
                    <span class="text-[11px] font-semibold text-gray-500">Today</span>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
         LIST VIEW
    ════════════════════════════════════════════════════════ --}}
        <div x-show="view === 'list'" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

            <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

                {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th
                                    class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                    #</th>
                                <th
                                    class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Holiday</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Day</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Type</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Paid</th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($holidays as $holiday)
                                @php $tc = $typeColors[$holiday->type] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#e5e7eb']; @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors"
                                    x-show="matchesSearch('{{ strtolower(addslashes($holiday->name)) }}')">
                                    <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                        {{ $holidays->firstItem() + $loop->index }}</td>
                                    <td class="px-5 py-3">
                                        <p class="text-[13px] font-bold text-gray-800">{{ $holiday->name }}</p>
                                        @if ($holiday->description)
                                            <p class="text-[11px] text-gray-400 mt-0.5 truncate max-w-[250px]">
                                                {{ $holiday->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <p class="text-[12px] font-bold text-gray-700">
                                            {{ $holiday->date->format('d M Y') }}</p>
                                        @if ($holiday->end_date)
                                            <p class="text-[10px] text-gray-400">to
                                                {{ $holiday->end_date->format('d M Y') }} ({{ $holiday->total_days }}
                                                days)</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center text-[12px] text-gray-600">
                                        {{ $holiday->date->format('l') }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <span
                                            class="inline-flex text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md border"
                                            style="background: {{ $tc['bg'] }}; color: {{ $tc['text'] }}; border-color: {{ $tc['border'] }}">
                                            {{ $holiday->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if ($holiday->is_paid)
                                            <span
                                                class="text-[10px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded">Yes</span>
                                        @else
                                            <span
                                                class="text-[10px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded">No</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if (has_permission('holidays.update'))
                                                <button @click="openEdit({{ $holiday->toJson() }})"
                                                    class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endif
                                            @if (has_permission('holidays.delete'))
                                                <button
                                                    @click="confirmDelete({{ $holiday->id }}, '{{ addslashes($holiday->name) }}')"
                                                    class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="flex flex-col items-center justify-center py-20 text-center">
                                            <div
                                                class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                                <i data-lucide="calendar-heart" class="w-7 h-7 text-gray-300"></i>
                                            </div>
                                            <p class="font-semibold text-gray-500 mb-1">No holidays found</p>
                                            <p class="text-sm text-gray-400 mb-4">Add public and company holidays for
                                                {{ $selectedYear }}</p>
                                            @if (has_permission('holidays.create'))
                                                <button @click="openCreate()"
                                                    class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                                    style="background: var(--brand-600)">Add Holiday</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-gray-50">
                    @forelse($holidays as $holiday)
                        @php $tc = $typeColors[$holiday->type] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#e5e7eb']; @endphp
                        <div class="p-4 bg-white hover:bg-gray-50/50 transition-colors"
                            x-show="matchesSearch('{{ strtolower(addslashes($holiday->name)) }}')">

                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 text-[15px] leading-tight">{{ $holiday->name }}
                                    </h4>
                                    <div class="text-[12px] text-gray-600 font-semibold mt-1">
                                        {{ $holiday->date->format('d M Y') }} <span
                                            class="text-gray-400 font-medium">({{ $holiday->date->format('l') }})</span>
                                    </div>
                                    @if ($holiday->end_date)
                                        <p class="text-[11px] text-gray-400 mt-0.5">to
                                            {{ $holiday->end_date->format('d M Y') }} ({{ $holiday->total_days }} days)
                                        </p>
                                    @endif
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1.5">
                                    <span
                                        class="inline-flex text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md border"
                                        style="background: {{ $tc['bg'] }}; color: {{ $tc['text'] }}; border-color: {{ $tc['border'] }}">
                                        {{ $holiday->type_label }}
                                    </span>
                                    @if ($holiday->is_paid)
                                        <span
                                            class="text-[9px] font-extrabold uppercase tracking-wider text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded-md">Paid:
                                            Yes</span>
                                    @else
                                        <span
                                            class="text-[9px] font-extrabold uppercase tracking-wider text-gray-500 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-md">Paid:
                                            No</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-3 mt-3 border-t border-gray-100/50">
                                @if (has_permission('holidays.update'))
                                    <button @click="openEdit({{ $holiday->toJson() }})"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                        title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                @endif
                                @if (has_permission('holidays.delete'))
                                    <button
                                        @click="confirmDelete({{ $holiday->id }}, '{{ addslashes($holiday->name) }}')"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors"
                                        title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="p-8 bg-white text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i data-lucide="calendar-heart" class="w-10 h-10 mb-3 text-gray-300 opacity-50"></i>
                                <p class="font-semibold text-gray-500 text-sm mb-1">No holidays found</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($holidays->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">{{ $holidays->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════
         MODAL (Create / Edit)
    ════════════════════════════════════════════════════════ --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white w-[95%] sm:w-full max-w-lg max-h-[90vh] flex flex-col rounded-xl shadow-2xl overflow-hidden m-4"
                @click.away="modalOpen = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                {{-- Modal header ── --}}
                <div
                    class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                            style="background: var(--brand-600)">
                            <i data-lucide="calendar-plus" class="w-3.5 h-3.5 text-white"></i>
                        </div>
                        <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm"
                            x-text="isEditing ? 'Edit Holiday' : 'New Holiday'"></h3>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Modal form ── --}}
                <form @submit.prevent="submitForm()" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">

                        {{-- Type selector (visual pill buttons) ── --}}
                        <div>
                            <label class="field-label">Type <span class="text-red-400">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($typeColors as $type => $colors)
                                    <button type="button" @click="form.type = '{{ $type }}'"
                                        :class="form.type === '{{ $type }}' ? 'ring-2' :
                                            'opacity-60 hover:opacity-100'"
                                        class="inline-flex items-center gap-1 text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md border transition-all"
                                        style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border-color: {{ $colors['border'] }}; --tw-ring-color: {{ $colors['border'] }}">
                                        {{ $typeLabels[$type] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="field-error" x-show="errors.type" x-text="errors.type"></p>
                        </div>

                        {{-- Name ── --}}
                        <div>
                            <label class="field-label">Holiday Name <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.name" class="field-input"
                                placeholder="e.g. Republic Day" required>
                            <p class="field-error" x-show="errors.name" x-text="errors.name"></p>
                        </div>

                        {{-- Dates ── --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Start Date <span class="text-red-400">*</span></label>
                                <input type="date" x-model="form.date" class="field-input" required>
                                <p class="field-error" x-show="errors.date" x-text="errors.date"></p>
                            </div>
                            <div>
                                <label class="field-label">End Date <span
                                        class="text-[10px] text-gray-400 font-normal normal-case">(optional)</span></label>
                                <input type="date" x-model="form.end_date" class="field-input"
                                    :min="form.date">
                            </div>
                        </div>

                        {{-- Description ── --}}
                        <div>
                            <label class="field-label">Description</label>
                            <textarea x-model="form.description" class="field-input !py-2.5" rows="2" placeholder="Optional description"></textarea>
                        </div>

                        {{-- Toggles ── --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Paid</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_paid">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Recurring</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_recurring">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5">
                                <span class="text-[12px] font-bold text-gray-600">Active</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" x-model="form.is_active">
                                    <span class="toggle-track"></span><span class="toggle-thumb"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Modal footer ── --}}
                    <div
                        class="px-5 sm:px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0">
                        <div>
                            <template x-if="isEditing">
                                @if (has_permission('holidays.delete'))
                                    <button type="button" @click="modalOpen = false; confirmDelete(editId, form.name)"
                                        class="inline-flex items-center gap-1.5 text-[12px] font-bold text-red-400 hover:text-red-600 transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        Delete
                                    </button>
                                @endif
                            </template>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="modalOpen = false"
                                class="px-4 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="saving"
                                class="px-5 py-2 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
                                style="background: var(--brand-600)">
                                <span x-show="!saving" x-text="isEditing ? 'Update' : 'Create'"></span>
                                <span x-show="saving" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    Saving…
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>{{-- end x-data ── --}}

@endsection

@push('scripts')
    <script>
        window.holidayPage = function() {

            // ── All holidays passed from controller (for calendar) ──
            const ALL_HOLIDAYS = @json($calendarHolidays);

            // ── Today reference ──
            const TODAY = new Date();
            const TODAY_STR = TODAY.toISOString().slice(0, 10); // YYYY-MM-DD

            const MONTH_NAMES = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            return {
                // ── View state ──
                view: 'calendar', // 'calendar' | 'list'
                searchQuery: '',

                // ── Filter States & Options ──
                selectedYear: '{{ $selectedYear }}',
                selectedMonth: '{{ $selectedMonth }}',
                selectedType: '{{ $selectedType ?? "" }}',
                
                yearOptions: [
                    @for ($y = now()->year - 1; $y <= now()->year + 2; $y++)
                        { id: '{{ $y }}', name: '{{ $y }}' },
                    @endfor
                ],
                monthOptions: [
                    { id: '0', name: 'All Months' },
                    @foreach (['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $mi => $mn)
                        { id: '{{ $mi + 1 }}', name: '{{ $mn }}' },
                    @endforeach
                ],
                typeOptions: @js(
                    collect($typeLabels)->map(fn($label, $val) => ['id' => $val, 'name' => $label])->values()
                ),

                // ── Calendar nav ──
                calYear: {{ $selectedYear }},
                calMonth: {{ $selectedMonth > 0 ? $selectedMonth - 1 : 'new Date().getMonth()' }}, // 0-based

                // ── Modal state ──
                modalOpen: false,
                isEditing: false,
                editId: null,
                saving: false,
                errors: {},
                overflowDate: null,

                // ── Form data ──
                form: {
                    name: '',
                    date: '',
                    end_date: '',
                    type: 'company',
                    description: '',
                    is_paid: true,
                    is_recurring: false,
                    is_active: true,
                },

                // ──────────────────────────────────────────────────────────────
                // INIT
                // ──────────────────────────────────────────────────────────────
                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                // ──────────────────────────────────────────────────────────────
                // COMPUTED: month label
                // ──────────────────────────────────────────────────────────────
                get currentMonthLabel() {
                    return MONTH_NAMES[this.calMonth];
                },

                // ──────────────────────────────────────────────────────────────
                // COMPUTED: calendar cells array
                // ──────────────────────────────────────────────────────────────
                get calendarCells() {
                    const year = this.calYear;
                    const month = this.calMonth; // 0-based

                    const firstDay = new Date(year, month, 1).getDay(); // 0=Sun
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const daysInPrev = new Date(year, month, 0).getDate();

                    const cells = [];

                    // Leading cells from previous month
                    for (let i = firstDay - 1; i >= 0; i--) {
                        const d = daysInPrev - i;
                        const m = month === 0 ? 11 : month - 1;
                        const y = month === 0 ? year - 1 : year;
                        const str = this._dateStr(y, m, d);
                        cells.push({
                            day: d,
                            inMonth: false,
                            isToday: false,
                            isWeekend: false,
                            dateStr: str,
                            holidays: []
                        });
                    }

                    // Current month cells
                    for (let d = 1; d <= daysInMonth; d++) {
                        const str = this._dateStr(year, month, d);
                        const dow = new Date(year, month, d).getDay();
                        cells.push({
                            day: d,
                            inMonth: true,
                            isToday: str === TODAY_STR,
                            isWeekend: dow === 0 || dow === 6,
                            dateStr: str,
                            holidays: this._getHolidaysForDate(str),
                        });
                    }

                    // Trailing cells to complete last row
                    const remainder = cells.length % 7;
                    if (remainder !== 0) {
                        for (let d = 1; d <= 7 - remainder; d++) {
                            const m = month === 11 ? 0 : month + 1;
                            const y = month === 11 ? year + 1 : year;
                            const str = this._dateStr(y, m, d);
                            cells.push({
                                day: d,
                                inMonth: false,
                                isToday: false,
                                isWeekend: false,
                                dateStr: str,
                                holidays: []
                            });
                        }
                    }

                    return cells;
                },

                // ──────────────────────────────────────────────────────────────
                // HELPERS
                // ──────────────────────────────────────────────────────────────
                _dateStr(year, month, day) {
                    return `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                },

                _getHolidaysForDate(dateStr) {
                    return ALL_HOLIDAYS.filter(h => {
                        if (!h.end_date) return h.date === dateStr;
                        // Multi-day holiday: show badge on each day in range
                        return dateStr >= h.date && dateStr <= h.end_date;
                    });
                },

                formatDateLabel(dateStr) {
                    const d = new Date(dateStr + 'T00:00:00');
                    return d.toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        weekday: 'short'
                    });
                },

                // ──────────────────────────────────────────────────────────────
                // MONTH NAVIGATION
                // ──────────────────────────────────────────────────────────────
                prevMonth() {
                    if (this.calMonth === 0) {
                        this.calMonth = 11;
                        this.calYear--;
                    } else {
                        this.calMonth--;
                    }

                    window.location.href = `?year=${this.calYear}&month=${this.calMonth + 1}`;
                },

                nextMonth() {
                    if (this.calMonth === 11) {
                        this.calMonth = 0;
                        this.calYear++;
                    } else {
                        this.calMonth++;
                    }

                    window.location.href = `?year=${this.calYear}&month=${this.calMonth + 1}`;
                },

                goToday() {
                    const today = new Date();
                    this.calMonth = today.getMonth();
                    this.calYear = today.getFullYear();

                    window.location.href = `?year=${this.calYear}&month=${this.calMonth + 1}`;
                },

                toggleOverflow(dateStr, e) {
                    e.stopPropagation();
                    this.overflowDate = this.overflowDate === dateStr ? null : dateStr;
                },

                // ──────────────────────────────────────────────────────────────
                // SEARCH (list view)
                // ──────────────────────────────────────────────────────────────
                matchesSearch(nameLower) {
                    if (!this.searchQuery) return true;
                    return nameLower.includes(this.searchQuery.toLowerCase());
                },

                // ──────────────────────────────────────────────────────────────
                // MODAL OPEN/CLOSE
                // ──────────────────────────────────────────────────────────────
                resetForm() {
                    this.form = {
                        name: '',
                        date: '',
                        end_date: '',
                        type: 'company',
                        description: '',
                        is_paid: true,
                        is_recurring: false,
                        is_active: true,
                    };
                    this.errors = {};
                },

                openCreate(prefillDate = '') {
                    this.resetForm();
                    this.isEditing = false;
                    this.editId = null;
                    if (prefillDate) this.form.date = prefillDate;
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openEdit(item) {
                    this.resetForm();
                    this.isEditing = true;
                    this.editId = item.id;
                    this.form = {
                        name: item.name,
                        date: item.date?.split('T')[0] || '',
                        end_date: item.end_date?.split('T')[0] || '',
                        type: item.type,
                        description: item.description || '',
                        is_paid: item.is_paid,
                        is_recurring: item.is_recurring,
                        is_active: item.is_active,
                    };
                    this.modalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                // ──────────────────────────────────────────────────────────────
                // SUBMIT (create / update)
                // ──────────────────────────────────────────────────────────────
                async submitForm() {
                    this.saving = true;
                    this.errors = {};

                    const url = this.isEditing ?
                        `{{ url('admin/hrm/holidays') }}/${this.editId}` :
                        `{{ route('admin.hrm.holidays.store') }}`;

                    const payload = {
                        ...this.form,
                        is_paid: this.form.is_paid ? 1 : 0,
                        is_recurring: this.form.is_recurring ? 1 : 0,
                        is_active: this.form.is_active ? 1 : 0,
                    };

                    try {
                        const resp = await fetch(url, {
                            method: this.isEditing ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await resp.json();

                        if (!resp.ok) {
                            if (resp.status === 422 && data.errors) {
                                for (const [k, m] of Object.entries(data.errors)) {
                                    this.errors[k] = m[0];
                                }
                            } else {
                                BizAlert.toast(data.message || 'Something went wrong', 'error');
                            }
                            return;
                        }

                        BizAlert.toast(data.message, 'success');
                        this.modalOpen = false;
                        setTimeout(() => window.location.reload(), 600);

                    } catch (e) {
                        BizAlert.toast('Network error', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                // ──────────────────────────────────────────────────────────────
                // DELETE
                // ──────────────────────────────────────────────────────────────
                confirmDelete(id, name) {
                    BizAlert.confirm('Delete Holiday', `Delete "${name}"?`, 'Delete').then(async (r) => {
                        if (!r.isConfirmed) return;
                        try {
                            const resp = await fetch(`{{ url('admin/hrm/holidays') }}/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await resp.json();
                            if (!resp.ok) {
                                BizAlert.toast(data.message || 'Cannot delete', 'error');
                                return;
                            }
                            BizAlert.toast(data.message, 'success');
                            setTimeout(() => window.location.reload(), 600);
                        } catch (e) {
                            BizAlert.toast('Network error', 'error');
                        }
                    });
                },
            };
        };
    </script>
@endpush
