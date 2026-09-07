@extends('layouts.admin')

@section('title', 'Leave Applications')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Leave Applications</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage employee leave requests</p> --}}
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
            transition: box-shadow 150ms, border-color 150ms;
        }

        .stat-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
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

        .type-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f0f9ff;
            color: #0369a1;
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
            white-space: nowrap;
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
        $statusColors = \App\Models\Hrm\Leave::STATUS_COLORS;
        $statusLabels = \App\Models\Hrm\Leave::STATUS_LABELS;

        $totalCount = $leaves->total();
        $pendingCount = $leaves->where('status', 'pending')->count();
        $approvedCount = $leaves->where('status', 'approved')->count();
        $rejectedCount = $leaves->where('status', 'rejected')->count();
    @endphp

    <div class="pb-10" x-data="leaveIndex()" x-cloak>

        {{-- ════════ STATS BAR ════════ --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">

            <a href="{{ route('admin.hrm.leaves.index') }}" class="stat-card block">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-black text-gray-900">{{ number_format($totalCount) }}</p>
            </a>

            <a href="{{ route('admin.hrm.leaves.index', ['status' => 'pending']) }}" class="stat-card block">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pending</p>
                <p class="text-2xl font-black text-amber-600">{{ number_format($pendingCount) }}</p>
            </a>

            <a href="{{ route('admin.hrm.leaves.index', ['status' => 'approved']) }}" class="stat-card block">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Approved</p>
                <p class="text-2xl font-black text-green-600">{{ number_format($approvedCount) }}</p>
            </a>

            <a href="{{ route('admin.hrm.leaves.index', ['status' => 'rejected']) }}" class="stat-card block">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Rejected</p>
                <p class="text-2xl font-black text-red-600">{{ number_format($rejectedCount) }}</p>
            </a>

        </div>

        {{-- ════════ TOOLBAR ════════ --}}
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <form method="GET" action="{{ route('admin.hrm.leaves.index') }}" id="filter-form">

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-wrap">

                    {{-- Search ── --}}
                    <div class="relative w-full sm:flex-1 sm:min-w-[180px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input type="text" name="employee_name" value="{{ request('employee_name') }}"
                            placeholder="Search employee name..." class="filter-input search-input pl-8 w-full">
                    </div>

                    {{-- Status ── --}}
                    <div class="w-full sm:w-auto sm:min-w-[140px]">
                        <x-custom-select name="status" placeholder="All Status" :options="$statusLabels"
                            selected="{{ request('status') }}" onchange="this.form.submit()" />
                    </div>

                    {{-- From Date ── --}}
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="filter-input"
                        placeholder="From">

                    {{-- To Date ── --}}
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="filter-input"
                        placeholder="To">

                    {{-- Search submit ── --}}
                    <button type="submit"
                        class="text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        Filter
                    </button>

                    {{-- Clear filters ── --}}
                    @if (request()->hasAny(['employee_id', 'status', 'from_date', 'to_date']))
                        <a href="{{ route('admin.hrm.leaves.index') }}"
                            class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
                            Clear
                        </a>
                    @endif

                    {{-- Apply Leave ── --}}
                    {{-- <a href="{{ route('admin.hrm.leaves.create') }}"
                    class="ml-auto inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                    style="background: var(--brand-600)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Apply Leave
                </a> --}}

                </div>
            </form>
        </div>

        {{-- ════════ TABLE ════════ --}}
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

            {{-- Table header ── --}}
            <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between flex-wrap gap-2">
                <p class="text-[12px] font-bold text-gray-500">
                    {{ $leaves->total() }} application{{ $leaves->total() !== 1 ? 's' : '' }}
                    @if (request()->hasAny(['employee_id', 'status', 'from_date', 'to_date']))
                        <span class="text-gray-400 font-medium">— filtered</span>
                    @endif
                </p>
            </div>

            @if ($leaves->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                        <i data-lucide="calendar-off" style="width:28px;height:28px;color:#d1d5db"></i>
                    </div>
                    <p class="font-semibold text-gray-500 mb-1">No leave applications found</p>
                    <p class="text-sm text-gray-400 mb-4">
                        @if (request()->hasAny(['employee_id', 'status', 'from_date', 'to_date']))
                            Try adjusting your filters
                        @else
                            No leave requests have been submitted yet
                        @endif
                    </p>
                    {{-- <a href="{{ route('admin.hrm.leaves.create') }}"
                    class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                    style="background: var(--brand-600)">
                    Apply First Leave
                </a> --}}
                </div>
            @else
                {{-- 🖥️ DESKTOP VIEW (TABLE) ── --}}
                <div class="hidden md:block overflow-x-auto w-full pb-2">
                    <table class="w-full min-w-[1000px]">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th
                                    class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                    #</th>
                                <th
                                    class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[220px]">
                                    Employee</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Leave Type</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    From — To</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Days</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Day Type</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Applied On</th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaves as $leave)
                                @php
                                    $empName = $leave->employee?->user?->name ?? 'Unknown';
                                    $empCode = $leave->employee?->employee_code ?? '';
                                    $initials = strtoupper(substr($empName, 0, 1));
                                    $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
                                    $avatarBg = $avatarColors[crc32($empName) % count($avatarColors)];
                                    $sColor = $statusColors[$leave->status] ?? $statusColors['cancelled'];
                                    $dayTypeLabels = [
                                        'full_day' => 'Full Day',
                                        'first_half' => '1st Half',
                                        'second_half' => '2nd Half',
                                    ];
                                @endphp
                                <tr class="table-row">

                                    {{-- # ── --}}
                                    <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                        {{ $leaves->firstItem() + $loop->index }}
                                    </td>

                                    {{-- Employee ── --}}
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="emp-avatar" style="background: {{ $avatarBg }}">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[13px] font-bold text-gray-900 truncate max-w-[140px]">
                                                    {{ $empName }}
                                                </p>
                                                <p class="text-[11px] text-gray-400 font-medium truncate">
                                                    {{ $empCode ?: '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Leave Type ── --}}
                                    <td class="px-3 py-3">
                                        <span class="type-badge">
                                            {{ $leave->leaveType?->name ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- From — To ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] text-gray-600 font-medium">
                                            {{ $leave->from_date?->format('d M') }} —
                                            {{ $leave->to_date?->format('d M Y') }}
                                        </span>
                                    </td>

                                    {{-- Days ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] font-bold text-gray-700">
                                            {{ $leave->total_days }}
                                        </span>
                                    </td>

                                    {{-- Day Type ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] text-gray-500 font-medium">
                                            {{ $dayTypeLabels[$leave->day_type] ?? ucfirst(str_replace('_', ' ', $leave->day_type ?? '—')) }}
                                        </span>
                                    </td>

                                    {{-- Status ── --}}
                                    <td class="px-3 py-3">
                                        <span class="status-badge"
                                            style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                style="background: {{ $sColor['dot'] }}"></span>
                                            {{ $statusLabels[$leave->status] ?? ucfirst($leave->status) }}
                                        </span>
                                    </td>

                                    {{-- Applied On ── --}}
                                    <td class="px-3 py-3">
                                        <span class="text-[12px] text-gray-500 font-medium">
                                            {{ $leave->created_at?->format('d M Y') }}
                                        </span>
                                    </td>

                                    {{-- Actions ── --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1 justify-end">

                                            {{-- View ── --}}
                                            @if (has_permission('leaves.view'))
                                                <a href="{{ route('admin.hrm.leaves.show', $leave->id) }}"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                    title="View">
                                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                        <circle cx="12" cy="12" r="3" />
                                                    </svg>
                                                </a>
                                            @endif

                                            @if ($leave->status === 'pending' && has_permission('leaves.approve'))
                                                {{-- Approve ── --}}
                                                <button @click="handleAction({{ $leave->id }}, 'approve')"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                                                    title="Approve">
                                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2.5"
                                                        stroke-linecap="round">
                                                        <polyline points="20 6 9 17 4 12" />
                                                    </svg>
                                                </button>

                                                {{-- Reject ── --}}
                                                <button @click="handleAction({{ $leave->id }}, 'reject')"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                    title="Reject">
                                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2.5"
                                                        stroke-linecap="round">
                                                        <line x1="18" y1="6" x2="6"
                                                            y2="18" />
                                                        <line x1="6" y1="6" x2="18"
                                                            y2="18" />
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

                {{-- 📱 MOBILE VIEW (CARDS) ── --}}
                <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                    @foreach ($leaves as $leave)
                        @php
                            $empName = $leave->employee?->user?->name ?? 'Unknown';
                            $empCode = $leave->employee?->employee_code ?? '';
                            $initials = strtoupper(substr($empName, 0, 1));
                            $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
                            $avatarBg = $avatarColors[crc32($empName) % count($avatarColors)];
                            $sColor = $statusColors[$leave->status] ?? $statusColors['cancelled'];
                            $dayTypeLabels = [
                                'full_day' => 'Full Day',
                                'first_half' => '1st Half',
                                'second_half' => '2nd Half',
                            ];
                        @endphp
                        <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">

                            {{-- Header: Employee & Status --}}
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-full text-white flex items-center justify-center text-sm font-bold shrink-0"
                                        style="background: {{ $avatarBg }}">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-[14px] text-gray-900 truncate">{{ $empName }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5 truncate">{{ $empCode ?: '—' }}</p>
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <span class="status-badge px-2 py-0.5 text-[9px] flex items-center gap-1"
                                        style="background: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            style="background: {{ $sColor['dot'] }}"></span>
                                        {{ $statusLabels[$leave->status] ?? ucfirst($leave->status) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Details: Leave Type & Dates --}}
                            <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                                <div class="flex justify-between items-center">
                                    <span
                                        class="text-[12px] font-bold text-gray-800">{{ $leave->leaveType?->name ?? '—' }}</span>
                                    <span class="text-[11px] font-bold text-gray-700">
                                        {{ $leave->total_days }} Day(s) <span
                                            class="text-gray-400 font-medium ml-1">({{ $dayTypeLabels[$leave->day_type] ?? ucfirst(str_replace('_', ' ', $leave->day_type ?? '—')) }})</span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-gray-100/50 mt-1">
                                    <span class="text-[11px] text-gray-500 font-medium">
                                        <span
                                            class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">From:</span>
                                        {{ $leave->from_date?->format('d M y') }}
                                    </span>
                                    <i data-lucide="arrow-right" class="w-3 h-3 text-gray-300"></i>
                                    <span class="text-[11px] text-gray-500 font-medium">
                                        <span
                                            class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">To:</span>
                                        {{ $leave->to_date?->format('d M y') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Footer: Date Applied & Actions --}}
                            <div class="flex items-center justify-between pt-1 border-t border-gray-50 mt-1">
                                <span class="text-[10px] text-gray-400 font-medium">Applied:
                                    {{ $leave->created_at?->format('d M Y') }}</span>
                                <div class="flex items-center justify-end gap-2">
                                    @if (has_permission('leaves.view'))
                                        <a href="{{ route('admin.hrm.leaves.show', $leave->id) }}"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-100 transition-colors"
                                            title="View">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                    @endif

                                    @if ($leave->status === 'pending' && has_permission('leaves.approve'))
                                        <button @click="handleAction({{ $leave->id }}, 'reject')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 transition-colors"
                                            title="Reject">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>

                                        <button @click="handleAction({{ $leave->id }}, 'approve')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 transition-colors"
                                            title="Approve">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

                {{-- Pagination ── --}}
                @if ($leaves->hasPages())
                    <div
                        class="px-5 py-4 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-center sm:justify-between flex-wrap gap-4 text-center">
                        <p class="text-[12px] text-gray-400 font-medium">
                            Showing {{ $leaves->firstItem() }}–{{ $leaves->lastItem() }} of {{ $leaves->total() }}
                        </p>
                        <div class="flex items-center gap-1">
                            {{-- Prev ── --}}
                            @if ($leaves->onFirstPage())
                                <span
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-300 cursor-not-allowed">&larr;
                                    Prev</span>
                            @else
                                <a href="{{ $leaves->previousPageUrl() }}"
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-600 hover:bg-gray-100 transition-colors">&larr;
                                    Prev</a>
                            @endif

                            {{-- Pages ── --}}
                            @foreach ($leaves->getUrlRange(max(1, $leaves->currentPage() - 2), min($leaves->lastPage(), $leaves->currentPage() + 2)) as $page => $url)
                                <a href="{{ $url }}"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-[12px] font-bold transition-colors
                                {{ $page == $leaves->currentPage() ? 'text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                    style="{{ $page == $leaves->currentPage() ? 'background: var(--brand-600)' : '' }}">
                                    {{ $page }}
                                </a>
                            @endforeach

                            {{-- Next ── --}}
                            @if ($leaves->hasMorePages())
                                <a href="{{ $leaves->nextPageUrl() }}"
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-600 hover:bg-gray-100 transition-colors">Next
                                    &rarr;</a>
                            @else
                                <span
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-300 cursor-not-allowed">Next
                                    &rarr;</span>
                            @endif
                        </div>
                    </div>
                @endif

            @endif
        </div>

        {{-- ════════ REMARKS MODAL ════════ --}}
        <template x-if="showModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showModal = false">
                <div class="absolute inset-0 bg-black/30" @click="showModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6" @click.stop>
                    <h3 class="text-[15px] font-bold text-gray-800 mb-1"
                        x-text="actionType === 'approve' ? 'Approve Leave' : 'Reject Leave'"></h3>
                    <p class="text-[12px] text-gray-400 mb-4">Add optional remarks for this action.</p>

                    <textarea x-model="remarks" rows="3" placeholder="Enter remarks (optional)..."
                        class="w-full border-1.5 border-gray-200 rounded-xl p-3 text-[13px] text-gray-700 outline-none focus:border-blue-400 resize-none"
                        style="border: 1.5px solid #e5e7eb;"></textarea>

                    <div class="flex items-center justify-end gap-3 mt-4">
                        <button @click="showModal = false"
                            class="px-4 py-2 rounded-xl text-[13px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button @click="submitAction()" :disabled="processing"
                            class="px-5 py-2 rounded-xl text-[13px] font-bold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                            :style="actionType === 'approve' ? 'background: #16a34a' : 'background: #dc2626'"
                            x-text="processing ? 'Processing...' : (actionType === 'approve' ? 'Approve' : 'Reject')">
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>
@endsection

@push('scripts')
    <script>
        function leaveIndex() {
            return {
                showModal: false,
                actionType: '',
                actionLeaveId: null,
                remarks: '',
                processing: false,

                handleAction(leaveId, type) {
                    this.actionLeaveId = leaveId;
                    this.actionType = type;
                    this.remarks = '';
                    this.showModal = true;
                },

                async submitAction() {
                    this.processing = true;
                    try {
                        const url = `{{ url('admin/hrm/leaves') }}/${this.actionLeaveId}/${this.actionType}`;
                        const res = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                remarks: this.remarks
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            BizAlert.toast(data.message || 'Action failed', 'error');
                            return;
                        }
                        BizAlert.toast(data.message, 'success');
                        this.showModal = false;
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    } finally {
                        this.processing = false;
                    }
                }
            };
        }
    </script>
@endpush
