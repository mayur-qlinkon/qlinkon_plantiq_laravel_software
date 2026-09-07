@extends ('layouts.admin')

@section('title', 'Attendance Report')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Attendance Report</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Filter and review attendance records across your team</p> --}}
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
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

        .filter-input:focus {
            border-color: var(--brand-600);
        }

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
            padding: 10px 30px;
            font-size: 13.5px;
            outline: none;
            transition:
                border-color 150ms ease,
                box-shadow 150ms ease;
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

        .status-badge {
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

        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f0f9ff;
            color: #0369a1;
        }

        .override-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #fef3c7;
            color: #92400e;
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

        .emp-avatar {
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
        $statusColors = \App\Models\Hrm\Attendance::STATUS_COLORS;
        $statusLabels = \App\Models\Hrm\Attendance::STATUS_LABELS;
    @endphp

    <div class="pb-10" x-data="attendanceReport()">
        {{-- ════════ FILTER BAR ════════ --}}
        <div class="mb-4 rounded-2xl border border-gray-100 bg-white px-4 py-3">
            {{-- The @change listener on the form catches bubbling events from any custom-select inside --}}
            <form id="report-filter-form" method="GET" action="{{ route('admin.hrm.attendance.report') }}"
                @change="$event.target.closest('form').submit()">
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Date From ── --}}
                    <div>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="filter-input"
                            placeholder="From Date" />
                    </div>

                    {{-- Date To ── --}}
                    <div>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="filter-input"
                            placeholder="To Date" />
                    </div>

                    {{-- Department ── --}}
                    <div class="w-full shrink-0 sm:w-[180px]">
                        <x-custom-select name="department_id" placeholder="All Departments" :options="collect($departments ?? [])
                            ->pluck('name', 'id')
                            ->toArray()"
                            selected="{{ $filters['department_id'] ?? '' }}" />
                    </div>

                    {{-- Status ── --}}
                    <div class="w-full shrink-0 sm:w-[150px]">
                        <x-custom-select name="status" placeholder="All Status" :options="$statusLabels"
                            selected="{{ $filters['status'] ?? '' }}" />
                    </div>

                    {{-- Employee Auto-Suggest Combobox ── --}}
                    <div class="relative flex min-w-[250px] flex-1 items-center gap-2">
                        {{-- Hidden input sends the actual ID to the backend --}}
                        <input type="hidden" name="employee_id" x-model="selectedEmployeeId" />

                        <div class="relative w-full" @click.away="empSearchOpen = false">
                            {{-- Visible Input for searching --}}
                            <input type="text" x-model="employeeSearchQuery" @focus="empSearchOpen = true"
                                @input="
                                    empSearchOpen = true;
                                    selectedEmployeeId = '';
                                "
                                placeholder="Search & select employee..." class="filter-input w-full cursor-text bg-white"
                                autocomplete="off" />

                            {{-- Scrollable Dropdown List --}}
                            <div x-show="empSearchOpen" x-cloak x-transition.opacity.duration.200ms
                                class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl">
                                <template x-for="emp in filteredEmployees" :key="emp.id">
                                    <div @click="selectEmployee(emp.id, emp.name)"
                                        class="group flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-2.5 transition-colors last:border-0 hover:bg-blue-50">
                                        <p class="group-hover:text-brand-600 text-[12px] font-bold text-gray-800"
                                            x-text="emp.name"></p>
                                        <p class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-500"
                                            x-text="emp.code"></p>
                                    </div>
                                </template>

                                <div x-show="filteredEmployees.length === 0" class="px-4 py-4 text-center text-gray-400">
                                    <i data-lucide="user-x" class="mx-auto mb-1 h-6 w-6 opacity-50"></i>
                                    <p class="text-[11px] font-bold">No employees found</p>
                                </div>
                            </div>
                        </div>

                        {{-- Global Clear Filters --}}
                        <button type="button" @click="clearFilters" x-show="hasActiveFilters || selectedEmployeeId" x-cloak
                            class="flex shrink-0 items-center gap-1 rounded-lg border border-red-200 px-3 py-2 text-[11px] font-bold text-red-500 transition-colors hover:bg-red-50">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ════════ TABLE ════════ --}}
        <div id="attendance-list-container" class="overflow-hidden rounded-2xl border border-gray-100 bg-white"
            @click="handlePaginationClick($event)">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-50 px-5 py-3">
                <p class="text-[12px] font-bold text-gray-500">
                    {{ $report->total() }} record{{ $report->total() !== 1 ? 's' : '' }}
                    @if (request()->hasAny(['date_from', 'date_to', 'department_id', 'store_id', 'status', 'q']))
                        <span class="font-medium text-gray-400">&mdash; filtered</span>
                    @endif
                </p>
                <button
                    @click="
                        exportModal = true;
                        $nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    "
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-[12px] font-bold text-white transition-opacity hover:opacity-90 active:scale-95"
                    style="background: var(--brand-600)">
                    <i data-lucide="download" class="h-3.5 w-3.5"></i>
                    Export Report
                </button>
            </div>

            @if ($report->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                        <i data-lucide="calendar-x" class="h-7 w-7 text-gray-300"></i>
                    </div>
                    <p class="mb-1 font-semibold text-gray-500">No attendance records found</p>
                    <p class="text-sm text-gray-400">
                        @if (request()->hasAny(['date_from', 'date_to', 'department_id', 'store_id', 'status', 'q']))
                            Try adjusting your filters
                        @else
                            Attendance records will appear here
                        @endif
                    </p>
                </div>
            @else
                {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th
                                    class="w-[50px] px-5 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    #
                                </th>
                                <th
                                    class="w-[220px] px-5 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Employee
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Date
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Check In
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Check Out
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Worked
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Overtime
                                </th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black tracking-wider text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report as $att)
                                @php
                                    $emp = $att->employee;
                                    $empName = $emp->user->name ?? 'Unknown';
                                    $initials = strtoupper(substr($empName, 0, 1));
                                    $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
                                    $avatarBg = $avatarColors[crc32($empName) % count($avatarColors)];
                                    $sColor = $statusColors[$att->status] ?? $statusColors['present'];
                                @endphp
                                <tr class="table-row">
                                    {{-- # ── --}}
                                    <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                        {{ $report->firstItem() + $loop->index }}
                                    </td>

                                    {{-- Employee ── --}}
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="emp-avatar" style="background: {{ $avatarBg }}">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[140px] truncate text-[13px] font-bold text-gray-900">
                                                    {{ $empName }}
                                                </p>
                                                <p class="truncate text-[11px] font-medium text-gray-400">
                                                    {{ $emp->employee_code ?? '---' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Date ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] font-bold text-gray-700">
                                            {{ $att->date->format('d M Y') }}
                                        </span>
                                        <p class="text-[10px] text-gray-400">{{ $att->date->format('l') }}</p>
                                    </td>

                                    {{-- Check In ── --}}
                                    <td class="px-3 py-3">
                                        @if ($att->check_in_time)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[12px] font-bold text-gray-700">
                                                    {{ $att->check_in_time->format('h:i A') }}
                                                </span>
                                                @if ($att->check_in_method)
                                                    <span
                                                        class="method-badge">{{ strtoupper($att->check_in_method) }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[12px] text-gray-300">---</span>
                                        @endif
                                    </td>

                                    {{-- Check Out ── --}}
                                    <td class="px-3 py-3">
                                        @if ($att->check_out_time)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[12px] font-bold text-gray-700">
                                                    {{ $att->check_out_time->format('h:i A') }}
                                                </span>
                                                @if ($att->check_out_method)
                                                    <span
                                                        class="method-badge">{{ strtoupper($att->check_out_method) }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[12px] text-gray-300">---</span>
                                        @endif
                                    </td>

                                    {{-- Worked Hours ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] font-bold text-gray-700">
                                            {{ $att->worked_hours ? number_format($att->worked_hours, 1) . 'h' : '---' }}
                                        </span>
                                    </td>

                                    {{-- Overtime ── --}}
                                    <td class="px-3 py-3">
                                        <span
                                            class="text-[12px] font-bold {{ $att->overtime_hours > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                            {{ $att->overtime_hours ? number_format($att->overtime_hours, 1) . 'h' : '---' }}
                                        </span>
                                    </td>

                                    {{-- Status ── --}}
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="status-badge"
                                                style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                                <span class="h-1.5 w-1.5 rounded-full"
                                                    style="background: {{ $sColor['dot'] }}"></span>
                                                {{ $statusLabels[$att->status] ?? ucfirst($att->status) }}
                                            </span>
                                            @if ($att->is_overridden)
                                                <span class="override-badge"
                                                    title="Overridden: {{ $att->override_reason }}">
                                                    <i data-lucide="shield-check" class="h-3 w-3"></i>
                                                    Override
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Actions ── --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                @click="openOverride({{ $att->id }}, '{{ addslashes($empName) }}', '{{ $att->date->format('d M Y') }}', '{{ $att->status }}', '{{ $att->check_in_time ? $att->check_in_time->format('Y-m-d H:i:s') : '' }}', '{{ $att->check_out_time ? $att->check_out_time->format('Y-m-d H:i:s') : '' }}', {{ json_encode($att->override_reason ?? '') }})"
                                                class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                                title="Override Attendance">
                                                <i data-lucide="pencil-line" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="divide-y divide-gray-50 border-t border-gray-50 md:hidden">
                    @foreach ($report as $att)
                        @php
                            $emp = $att->employee;
                            $empName = $emp->user->name ?? 'Unknown';
                            $initials = strtoupper(substr($empName, 0, 1));
                            $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
                            $avatarBg = $avatarColors[crc32($empName) % count($avatarColors)];
                            $sColor = $statusColors[$att->status] ?? $statusColors['present'];
                        @endphp
                        <div class="p-4 transition-colors hover:bg-gray-50/50">
                            {{-- Header: Employee & Action --}}
                            <div class="mb-3 flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="emp-avatar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                        style="background: {{ $avatarBg }}">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <p class="text-[13px] leading-tight font-bold text-gray-900">{{ $empName }}
                                        </p>
                                        <p class="text-[11px] font-medium text-gray-400">
                                            {{ $emp->employee_code ?? '---' }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="openOverride({{ $att->id }}, '{{ addslashes($empName) }}', '{{ $att->date->format('d M Y') }}', '{{ $att->status }}', '{{ $att->check_in_time ? $att->check_in_time->format('Y-m-d H:i:s') : '' }}', '{{ $att->check_out_time ? $att->check_out_time->format('Y-m-d H:i:s') : '' }}', {{ json_encode($att->override_reason ?? '') }})"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-amber-50 hover:text-amber-600"
                                    title="Override Attendance">
                                    <i data-lucide="pencil-line" class="h-4 w-4"></i>
                                </button>
                            </div>

                            {{-- Date & Status --}}
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <span
                                        class="text-[12px] font-bold text-gray-700">{{ $att->date->format('d M Y') }}</span>
                                    <span class="ml-1 text-[10px] text-gray-400">{{ $att->date->format('l') }}</span>
                                </div>
                                <div class="flex flex-col items-end gap-1 text-right">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-extrabold tracking-wider uppercase"
                                        style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            style="background: {{ $sColor['dot'] }}"></span>
                                        {{ $statusLabels[$att->status] ?? ucfirst($att->status) }}
                                    </span>
                                    @if ($att->is_overridden)
                                        <span
                                            class="flex inline-block items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-600">
                                            <i data-lucide="shield-check" class="h-3 w-3"></i> Edited
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Check-in / Check-out --}}
                            <div
                                class="mb-3 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                                <div>
                                    <p
                                        class="mb-0.5 flex items-center gap-1 text-[9px] font-bold tracking-wider text-gray-400 uppercase">
                                        Check In
                                        @if ($att->check_in_method)
                                            <span
                                                class="rounded bg-gray-200 px-1 text-[8px] text-gray-500">{{ strtoupper(substr($att->check_in_method, 0, 3)) }}</span>
                                        @endif
                                    </p>
                                    <p class="text-[12px] font-bold text-gray-700">
                                        {{ $att->check_in_time ? $att->check_in_time->format('h:i A') : '—' }}</p>
                                </div>
                                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-300"></i>
                                <div class="text-right">
                                    <p
                                        class="mb-0.5 flex items-center justify-end gap-1 text-[9px] font-bold tracking-wider text-gray-400 uppercase">
                                        @if ($att->check_out_method)
                                            <span
                                                class="rounded bg-gray-200 px-1 text-[8px] text-gray-500">{{ strtoupper(substr($att->check_out_method, 0, 3)) }}</span>
                                        @endif
                                        Check Out
                                    </p>
                                    <p class="text-[12px] font-bold text-gray-700">
                                        {{ $att->check_out_time ? $att->check_out_time->format('h:i A') : '—' }}</p>
                                </div>
                            </div>

                            {{-- Footer: Hours & Overtime --}}
                            <div class="flex items-center justify-between pt-1 text-[11px]">
                                <div>
                                    <span class="font-medium text-gray-400">Worked:</span>
                                    <span
                                        class="ml-0.5 font-bold text-gray-700">{{ $att->worked_hours ? number_format($att->worked_hours, 1) . ' hrs' : '—' }}</span>
                                </div>
                                @if ($att->overtime_hours > 0)
                                    <div>
                                        <span class="font-medium text-gray-400">OT:</span>
                                        <span
                                            class="ml-0.5 font-black text-blue-600">{{ number_format($att->overtime_hours, 1) }}
                                            hrs</span>
                                    </div>
                                @else
                                    <div class="font-medium text-gray-400">OT: —</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination ── --}}
                @if ($report->hasPages())
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-50 px-5 py-4">
                        <p class="text-[12px] font-medium text-gray-400">
                            Showing {{ $report->firstItem() }}&ndash;{{ $report->lastItem() }} of {{ $report->total() }}
                        </p>
                        <div class="flex items-center gap-1">
                            @if ($report->onFirstPage())
                                <span
                                    class="cursor-not-allowed rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-300">&larr;
                                    Prev</span>
                            @else
                                <a href="{{ $report->previousPageUrl() }}"
                                    class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-100">&larr;
                                    Prev</a>
                            @endif

                            @foreach ($report->getUrlRange(max(1, $report->currentPage() - 2), min($report->lastPage(), $report->currentPage() + 2)) as $page => $url)
                                <a href="{{ $url }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-[12px] font-bold transition-colors
                                {{ $page == $report->currentPage() ? 'text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                    style="{{ $page == $report->currentPage() ? 'background: var(--brand-600)' : '' }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            @if ($report->hasMorePages())
                                <a href="{{ $report->nextPageUrl() }}"
                                    class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-600 transition-colors hover:bg-gray-100">Next
                                    &rarr;</a>
                            @else
                                <span
                                    class="cursor-not-allowed rounded-lg px-3 py-1.5 text-[12px] font-bold text-gray-300">Next
                                    &rarr;</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- ════════ EXPORT MODAL ════════ --}}
        <div x-show="exportModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-md overflow-visible rounded-xl bg-white shadow-2xl" @click.away="exportModal = false"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100">
                {{-- Modal Header ── --}}
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4 rounded-t-xl">
                    <div>
                        <h3 class="text-sm font-black tracking-widest text-gray-800 uppercase">Export Attendance</h3>
                        <p class="mt-0.5 text-[11px] text-gray-400">Choose period and download as PDF or Excel</p>
                    </div>
                    <button @click="exportModal = false" class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    {{-- Period Selector ── --}}
                    <div>
                        <p class="mb-3 text-[11px] font-black tracking-wider text-gray-500 uppercase">Select Period</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $val => $lbl)
                                <button type="button" @click="exportPeriod = '{{ $val }}'"
                                    :class="exportPeriod === '{{ $val }}'
                                        ?
                                        'border-2 font-black text-white' :
                                        'border border-gray-200 font-bold text-gray-600 hover:border-gray-300 bg-white'"
                                    :style="exportPeriod === '{{ $val }}' ?
                                        'border-color: var(--brand-600); background: var(--brand-600)' : ''"
                                    class="rounded-lg px-3 py-2.5 text-center text-[12px] transition-all">
                                    {{ $lbl }}
                                </button>
                            @endforeach
                            {{-- Custom Range — full width --}}
                            <button type="button" @click="exportPeriod = 'custom'"
                                :class="exportPeriod === 'custom'
                                    ?
                                    'border-2 font-black text-white' :
                                    'border border-gray-200 font-bold text-gray-600 hover:border-gray-300 bg-white'"
                                :style="exportPeriod === 'custom'
                                    ?
                                    'border-color: var(--brand-600); background: var(--brand-600)' :
                                    ''"
                                class="col-span-2 flex items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-center text-[12px] transition-all">
                                <i data-lucide="calendar-range" class="h-3.5 w-3.5"></i>
                                Custom Date Range
                            </button>
                        </div>

                        {{-- Custom date inputs (shown only when custom is selected) ── --}}
                        <div x-show="exportPeriod === 'custom'" x-transition class="mt-3 grid grid-cols-2 gap-2">
                            <div>
                                <label
                                    class="mb-1 block text-[10px] font-black tracking-wider text-gray-500 uppercase">Start
                                    Date</label>
                                <input type="date" x-model="exportDateFrom" class="filter-input w-full text-sm"
                                    :max="exportDateTo || ''" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-black tracking-wider text-gray-500 uppercase">End
                                    Date</label>
                                <input type="date" x-model="exportDateTo" class="filter-input w-full text-sm"
                                    :min="exportDateFrom || ''" />
                            </div>
                        </div>
                    </div>

                    {{-- Optional Filters ── --}}
                    <div>
                        <p class="mb-3 text-[11px] font-black tracking-wider text-gray-500 uppercase">Optional Filters</p>
                        <div class="space-y-2.5">
                            {{-- Employee search (mirrors the main filter bar combobox) --}}
                            <div class="relative" @click.away="exportEmpSearchOpen = false">
                                <input type="text" x-model="exportEmployeeSearchQuery"
                                    @focus="exportEmpSearchOpen = true"
                                    @input="
                                        exportEmpSearchOpen = true;
                                        exportEmployeeId = '';
                                    "
                                    placeholder="All Employees (search to filter)"
                                    class="filter-input w-full cursor-text bg-white" autocomplete="off" />

                                <div x-show="exportEmpSearchOpen" x-cloak x-transition.opacity.duration.200ms
                                    class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl">
                                    <div @click="
                                            exportEmployeeId = '';
                                            exportEmployeeSearchQuery = '';
                                            exportEmpSearchOpen = false;
                                        "
                                        class="cursor-pointer border-b border-gray-50 px-4 py-2.5 text-[12px] font-bold text-gray-500 hover:bg-blue-50">
                                        All Employees
                                    </div>
                                    <template x-for="emp in exportFilteredEmployees" :key="emp.id">
                                        <div @click="
                                                exportEmployeeId = emp.id;
                                                exportEmployeeSearchQuery = emp.name;
                                                exportEmpSearchOpen = false;
                                            "
                                            class="group flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-2.5 last:border-0 hover:bg-blue-50">
                                            <p class="group-hover:text-brand-600 text-[12px] font-bold text-gray-800"
                                                x-text="emp.name"></p>
                                            <p class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-500"
                                                x-text="emp.code"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <x-alpine-select name="export_dept_id" model="exportDeptId" items="exportDeptOptions"
                                placeholder="All Departments" :allow-empty="true" />

                            <x-alpine-select name="export_status" model="exportStatus" items="statusOptions"
                                placeholder="All Statuses" :allow-empty="true" />
                        </div>
                    </div>
                </div>

                {{-- Modal Footer — Download Buttons ── --}}
                <div class="flex gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 rounded-b-xl">
                    <button type="button" @click="triggerExport('excel')"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 py-2.5 text-[13px] font-bold text-white transition-colors hover:bg-emerald-700">
                        <i data-lucide="table-2" class="h-4 w-4"></i>
                        Download Excel
                    </button>
                    <button type="button" @click="triggerExport('pdf')"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-500 py-2.5 text-[13px] font-bold text-white transition-colors hover:bg-red-600">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Download PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════ OVERRIDE MODAL ════════ --}}
        <div x-show="overrideModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-lg overflow-visible rounded-xl bg-white shadow-2xl"
                @click.away="overrideModal = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4 rounded-t-xl">
                    <h3 class="text-sm font-black tracking-widest text-gray-800 uppercase">Override Attendance</h3>
                    <button @click="overrideModal = false" class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="submitOverride()">
                    <div class="space-y-4 p-6">
                        {{-- Employee info ── --}}
                        <div class="rounded-lg bg-gray-50 p-3">
                            <p class="text-[12px] text-gray-500">
                                <span class="font-bold text-gray-700" x-text="overrideEmployee"></span>
                                &mdash; <span x-text="overrideDate"></span>
                            </p>
                        </div>

                        {{-- Status ── --}}
                        <div>
                            <label class="field-label">Status</label>
                            <x-alpine-select name="override_status" model="overrideForm.status" items="statusOptions"
                                placeholder="Keep Current" :allow-empty="true" />
                        </div>

                        {{-- Check In / Check Out ── --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Check In Time</label>
                                <input type="datetime-local" x-model="overrideForm.check_in_time" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Check Out Time</label>
                                <input type="datetime-local" x-model="overrideForm.check_out_time" class="field-input" />
                            </div>
                        </div>

                        {{-- Reason ── --}}
                        <div>
                            <label class="field-label">Reason <span class="text-red-400">*</span></label>
                            <textarea x-model="overrideForm.reason" class="field-input" rows="3"
                                placeholder="Explain why this attendance is being overridden" required></textarea>
                            <p class="field-error" x-show="overrideErrors.reason" x-text="overrideErrors.reason"></p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 rounded-b-xl">
                        <button type="button" @click="overrideModal = false"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-[13px] font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="overrideSaving"
                            class="rounded-lg px-5 py-2 text-[13px] font-bold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                            style="background: var(--brand-600)">
                            <span x-show="!overrideSaving">Save Override</span>
                            <span x-show="overrideSaving" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.attendanceReport = function() {
            // Prep the employees data securely for the JS environment
            const rawEmployees = @js($employees->map(fn($e) => ['id' => $e->id, 'name' => $e->user->name ?? 'Unknown', 'code' => $e->employee_code ?? 'N/A'])->values());

            return {
                // ── Select Data Options ──
                exportDeptOptions: @js(
    collect($departments ?? [])
        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
        ->values(),
),
                statusOptions: @js(collect($statusLabels)->map(fn($label, $val) => ['id' => $val, 'name' => $label])->values()),

                // ── Employee Combobox State ──
                employees: rawEmployees,
                selectedEmployeeId: "{{ request('employee_id', '') }}",
                employeeSearchQuery: "",
                empSearchOpen: false,

                // ── SPA-Safe Search & Filter Logic ──
                hasActiveFilters: false,

                // Computed property to filter the dropdown live
                get filteredEmployees() {
                    if (this.employeeSearchQuery.trim() === "") return this.employees;
                    const q = this.employeeSearchQuery.toLowerCase();

                    // If query exactly matches selected name, don't filter out the list, show all
                    const selected = this.employees.find((e) => e.id == this.selectedEmployeeId);
                    if (selected && selected.name.toLowerCase() === q) return this.employees;

                    // Search by name or code
                    return this.employees.filter((e) => e.name.toLowerCase().includes(q) || e.code.toLowerCase()
                        .includes(q));
                },

                // Triggered when an employee is clicked from the dropdown
                selectEmployee(id, name) {
                    this.selectedEmployeeId = id;
                    this.employeeSearchQuery = name;
                    this.empSearchOpen = false;
                    this.submitForm(); // Instantly fetch results for this user
                },

                init() {
                    // Pre-fill the search box if an employee was already selected in the URL
                    if (this.selectedEmployeeId) {
                        const emp = this.employees.find((e) => e.id == this.selectedEmployeeId);
                        if (emp) this.employeeSearchQuery = emp.name;
                    }

                    this.checkActiveFilters();
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("report-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(
                        ([k, v]) => k !== "page" && v && String(v).trim() !== "",
                    );
                },

                submitForm() {
                    // 🌟 SMART UX FIX: If they typed a name but didn't click the dropdown, auto-select the best match!
                    if (this.employeeSearchQuery.trim() !== "" && !this.selectedEmployeeId) {
                        if (this.filteredEmployees.length > 0) {
                            this.selectedEmployeeId = this.filteredEmployees[0].id;
                            this.employeeSearchQuery = this.filteredEmployees[0].name;
                        }
                    } else if (this.employeeSearchQuery.trim() === "") {
                        // Ensure ID is cleared if they backspaced everything
                        this.selectedEmployeeId = "";
                    }

                    // Force sync the Alpine state to the hidden DOM element before submission
                    const hiddenInput = document.querySelector('input[name="employee_id"]');
                    if (hiddenInput) hiddenInput.value = this.selectedEmployeeId;
                    this.empSearchOpen = false; // Close the dropdown

                    const form = document.getElementById("report-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    // Reset the combobox specifically
                    this.selectedEmployeeId = "";
                    this.employeeSearchQuery = "";
                    this.empSearchOpen = false;

                    const form = document.getElementById("report-filter-form");
                    if (form) {
                        // Clear text inputs, dates, and selects
                        form.querySelectorAll("input, select").forEach((el) => (el.value = ""));
                    }
                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("attendance-list-container");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    // Explicitly request HTML so Laravel's wantsJson() is bypassed
                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "text/html"
                            }
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("attendance-list-container");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            if (typeof lucide !== "undefined") lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                        });
                },

                // ── Export ──
                exportModal: false,
                exportPeriod: "today",
                exportDateFrom: "",
                exportDateTo: "",
                exportDeptId: "",
                exportStatus: "",
                exportEmployeeId: "",
                exportEmployeeSearchQuery: "",
                exportEmpSearchOpen: false,

                get exportFilteredEmployees() {
                    if (this.exportEmployeeSearchQuery.trim() === "") return this.employees;
                    const q = this.exportEmployeeSearchQuery.toLowerCase();
                    return this.employees.filter((e) => e.name.toLowerCase().includes(q) || e.code.toLowerCase()
                        .includes(q));
                },

                // Pre-fill the export modal with whatever is already filtered on the page,
                // so "export" matches what the admin is currently looking at by default.
                openExportModal() {
                    this.exportEmployeeId = this.selectedEmployeeId || "";
                    const emp = this.employees.find((e) => e.id == this.exportEmployeeId);
                    this.exportEmployeeSearchQuery = emp ? emp.name : "";

                    const form = document.getElementById("report-filter-form");
                    if (form) {
                        const fd = new FormData(form);
                        this.exportDeptId = fd.get("department_id") || "";
                        this.exportStatus = fd.get("status") || "";
                    }

                    this.exportModal = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                triggerExport(type) {
                    if (this.exportPeriod === "custom" && (!this.exportDateFrom || !this.exportDateTo)) {
                        BizAlert.toast("Please select both start and end dates.", "error");
                        return;
                    }

                    const base =
                        type === "pdf" ? "{{ route('admin.hrm.attendance.export.pdf') }}" :
                        "{{ route('admin.hrm.attendance.export.excel') }}";

                    const params = new URLSearchParams({
                        period: this.exportPeriod
                    });

                    if (this.exportPeriod === "custom") {
                        params.set("date_from", this.exportDateFrom);
                        params.set("date_to", this.exportDateTo);
                    }

                    if (this.exportDeptId) params.set("department_id", this.exportDeptId);
                    if (this.exportStatus) params.set("status", this.exportStatus);
                    if (this.exportEmployeeId) params.set("employee_id", this.exportEmployeeId);

                    window.location.href = base + "?" + params.toString();
                    this.exportModal = false;
                },

                // ── Override ──
                overrideModal: false,
                overrideSaving: false,
                overrideId: null,
                overrideEmployee: "",
                overrideDate: "",
                overrideErrors: {},
                overrideForm: {
                    status: "",
                    check_in_time: "",
                    check_out_time: "",
                    reason: "",
                },

                // 🌟 ADD THIS HELPER FUNCTION
                // Converts "2026-04-04 14:30:00" to "2026-04-04T14:30"
                formatDateForInput(dateStr) {
                    if (!dateStr) return "";
                    return dateStr.replace(" ", "T").substring(0, 16);
                },

                // 🌟 UPDATE THIS FUNCTION to accept the new time variables
                openOverride(id, empName, date, currentStatus, checkIn, checkOut, existingReason = "") {
                    this.overrideId = id;
                    this.overrideEmployee = empName;
                    this.overrideDate = date;
                    this.overrideErrors = {};

                    this.overrideForm = {
                        status: currentStatus || "",
                        // Pass the raw dates through our new formatter!
                        check_in_time: this.formatDateForInput(checkIn),
                        check_out_time: this.formatDateForInput(checkOut),
                        reason: existingReason || "",
                    };

                    this.overrideModal = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                async submitOverride() {
                    this.overrideSaving = true;
                    this.overrideErrors = {};

                    const payload = {};
                    if (this.overrideForm.status) payload.status = this.overrideForm.status;
                    if (this.overrideForm.check_in_time)
                        payload.check_in_time = this.overrideForm.check_in_time.replace("T", " ") + ":00";
                    if (this.overrideForm.check_out_time)
                        payload.check_out_time = this.overrideForm.check_out_time.replace("T", " ") + ":00";
                    payload.reason = this.overrideForm.reason;

                    try {
                        const res = await fetch(`{{ url('admin/hrm/attendance') }}/${this.overrideId}/override`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();

                        if (!res.ok) {
                            if (res.status === 422 && data.errors) {
                                this.overrideErrors = {};
                                for (const [key, messages] of Object.entries(data.errors)) {
                                    this.overrideErrors[key] = messages[0];
                                }
                            } else {
                                BizAlert.toast(data.message || "Failed to override", "error");
                            }
                            return;
                        }

                        BizAlert.toast(data.message || "Attendance overridden successfully", "success");
                        this.overrideModal = false;
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    } finally {
                        this.overrideSaving = false;
                    }
                },
            };
        };
    </script>
@endpush
