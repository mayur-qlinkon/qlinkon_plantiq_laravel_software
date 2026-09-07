@extends('layouts.admin')

@section('title', 'HRM Dashboard')

@section('header-title')
    <div class="flex items-center gap-3">
        <div>
            <h1 class="text-lg font-bold text-gray-900 leading-tight">HRM Dashboard</h1>
            <p class="text-xs text-gray-500 font-medium">Company-wide Human Resources Overview</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- ═══════════════════════════════════════════
             AI CHATBOT POPUP
        ═══════════════════════════════════════════ --}}
        <x-modals.ai-chatbot-popup />

        {{-- ════════════════════════════════════════════════════════════
             TOP STATS ROW: EMPLOYEES & ATTENDANCE
        ════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Employee Stats --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-[15px] font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-brand-500"></i>
                        Employee Headcount
                    </h2>
                    @if (Route::has('admin.hrm.employees.index'))
                        <a href="{{ route('admin.hrm.employees.index') }}"
                            class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                            View All <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-3 md:gap-4 flex-1">
                    <div class="bg-gray-50 rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Total</p>
                        <p class="text-2xl md:text-3xl font-black text-gray-800">{{ $employeeStats['total'] ?? 0 }}</p>
                    </div>
                    <div
                        class="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-green-600 font-bold uppercase tracking-wider mb-1">Active</p>
                        <p class="text-2xl md:text-3xl font-black text-green-700">{{ $employeeStats['active'] ?? 0 }}</p>
                    </div>
                    <div
                        class="bg-[#fef2f2] border border-[#fee2e2] rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-red-600 font-bold uppercase tracking-wider mb-1">Terminated
                        </p>
                        <p class="text-2xl md:text-3xl font-black text-red-700">{{ $employeeStats['terminated'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Today's Attendance --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-[15px] font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-brand-500"></i>
                        Today's Attendance
                        <span
                            class="text-[10px] bg-brand-50 text-brand-600 px-2 py-0.5 rounded-full font-bold uppercase ml-2 tracking-wide">{{ now()->format('d M, Y') }}</span>
                    </h2>
                    @if (Route::has('admin.hrm.attendance.index'))
                        <a href="{{ route('admin.hrm.attendance.index') }}"
                            class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                            Details <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-3 md:gap-4 flex-1">
                    <div
                        class="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-green-600 font-bold uppercase tracking-wider mb-1">Present</p>
                        <p class="text-2xl md:text-3xl font-black text-green-700">{{ $attendanceStats['present'] ?? 0 }}</p>
                    </div>
                    <div
                        class="bg-[#fffbeb] border border-[#fef3c7] rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-amber-600 font-bold uppercase tracking-wider mb-1">Late</p>
                        <p class="text-2xl md:text-3xl font-black text-amber-700">{{ $attendanceStats['late'] ?? 0 }}</p>
                    </div>
                    <div
                        class="bg-[#fef2f2] border border-[#fee2e2] rounded-xl p-3 md:p-4 flex flex-col justify-center text-center">
                        <p class="text-[10px] md:text-xs text-red-600 font-bold uppercase tracking-wider mb-1">Absent</p>
                        <p class="text-2xl md:text-3xl font-black text-red-700">{{ $attendanceStats['absent'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════════
             TASK OVERVIEW ROW
        ════════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-[15px] font-bold text-gray-800 flex items-center gap-2">
                    <i data-lucide="check-square" class="w-4 h-4 text-brand-500"></i>
                    Task Overview
                </h2>
                @if (Route::has('admin.hrm.tasks.index'))
                    <a href="{{ route('admin.hrm.tasks.index') }}"
                        class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                        All Tasks <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-100">
                    <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Tasks</p>
                    <p class="text-2xl font-black text-gray-800">{{ $taskStats['total'] ?? 0 }}</p>
                </div>
                <div class="bg-[#eff6ff] rounded-xl p-4 text-center border border-[#dbeafe]">
                    <p class="text-[11px] text-blue-600 font-bold uppercase tracking-wider mb-1">In Progress</p>
                    <p class="text-2xl font-black text-blue-700">{{ $taskStats['in_progress'] ?? 0 }}</p>
                </div>
                <div class="bg-[#fef2f2] rounded-xl p-4 text-center border border-[#fee2e2] relative overflow-hidden">
                    <div class="absolute inset-0 bg-red-50 opacity-50"></div>
                    <div class="relative z-10">
                        <p
                            class="text-[11px] text-red-600 font-bold uppercase tracking-wider mb-1 flex items-center justify-center gap-1">
                            <i data-lucide="alert-circle" class="w-3 h-3"></i> Overdue
                        </p>
                        <p class="text-2xl font-black text-red-700">{{ $taskStats['overdue'] ?? 0 }}</p>
                    </div>
                </div>
                <div class="bg-[#f0fdf4] rounded-xl p-4 text-center border border-[#dcfce7]">
                    <p class="text-[11px] text-green-600 font-bold uppercase tracking-wider mb-1">Completed</p>
                    <p class="text-2xl font-black text-green-700">{{ $taskStats['completed'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════════
             LIST PANELS (PRIORITY TASKS, LEAVES, PAYROLL)
        ════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            {{-- Priority Tasks --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="siren" class="w-4 h-4 text-red-500"></i> Priority Tasks
                    </h2>
                </div>
                <div class="flex-1 overflow-y-auto" style="max-height: 400px;">
                    @forelse($priorityTasks as $task)
                        <div
                            class="px-5 py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors flex justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-[13px] font-semibold text-gray-900 truncate">{{ $task['title'] ?? '—' }}
                                </h3>
                                <p class="text-[11px] text-gray-500 mt-1 truncate">
                                    <span class="font-medium">Assignees:</span>
                                    {{ !empty($task['assignees']) ? (collect($task['assignees'])->pluck('name')->filter()->implode(', ') ?: 'Unassigned') : 'Unassigned' }}
                                </p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span
                                        class="text-[10px] px-2 py-0.5 rounded-md font-bold uppercase tracking-wide
                                        {{ strtolower($task['priority'] ?? '') === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ $task['priority'] ?? 'HIGH' }}
                                    </span>
                                    <span
                                        class="text-[10px] text-gray-400 capitalize bg-gray-100 px-2 py-0.5 rounded-md font-medium">
                                        {{ str_replace('_', ' ', $task['status'] ?? '') }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p
                                    class="text-[11px] font-semibold {{ $task['is_overdue'] ? 'text-red-600' : 'text-gray-500' }}">
                                    Due: {{ $task['due_date'] ?? 'N/A' }}
                                </p>
                                @if (!empty($task['is_overdue']))
                                    <div class="mt-1">
                                        <span
                                            class="inline-flex items-center gap-1 text-[9px] font-bold text-red-600 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded uppercase tracking-wider">
                                            Overdue
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center flex flex-col items-center justify-center">
                            <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                <i data-lucide="check-circle" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">No high priority tasks open.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Leave Requests --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="calendar-off" class="w-4 h-4 text-brand-500"></i> Recent Leaves
                    </h2>
                    @if (Route::has('admin.hrm.leaves.index'))
                        <a href="{{ route('admin.hrm.leaves.index') }}"
                            class="text-[11px] font-semibold text-brand-600 hover:underline">Manage</a>
                    @endif
                </div>
                <div class="flex-1 overflow-y-auto" style="max-height: 400px;">
                    @forelse($leaveRequests as $leave)
                        @php
                            $status = strtolower($leave['status'] ?? 'pending');
                            $statusClasses = match ($status) {
                                'approved' => 'bg-[#f0fdf4] text-green-700 border-green-100',
                                'rejected' => 'bg-[#fef2f2] text-red-700 border-red-100',
                                default => 'bg-[#fffbeb] text-amber-700 border-amber-100',
                            };
                        @endphp
                        <div class="px-5 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $leave['employee'] ?? '—' }}
                                </p>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border {{ $statusClasses }}">
                                    {{ $status }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-600 font-medium mb-1">{{ $leave['type'] ?? 'Leave' }}</p>
                            <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                <i data-lucide="calendar-range" class="w-3 h-3"></i>
                                {{ $leave['from_date'] ?? '-' }} to {{ $leave['to_date'] ?? '-' }}
                                <span class="w-1 h-1 rounded-full bg-gray-300 inline-block mx-0.5"></span>
                                <span class="font-semibold text-gray-600">{{ $leave['total_days'] ?? 0 }} day(s)</span>
                            </p>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center flex flex-col items-center justify-center">
                            <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                <i data-lucide="inbox" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">No recent leave requests.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Payroll --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="banknote" class="w-4 h-4 text-brand-500"></i> Recent Payroll
                    </h2>
                    @if (Route::has('admin.hrm.payroll.index'))
                        <a href="{{ route('admin.hrm.payroll.index') }}"
                            class="text-[11px] font-semibold text-brand-600 hover:underline">Manage</a>
                    @endif
                </div>
                <div class="flex-1 overflow-y-auto" style="max-height: 400px;">
                    @forelse($recentPayroll as $slip)
                        @php
                            $status = strtolower($slip['status'] ?? 'pending');
                            $statusBadge = match ($status) {
                                'paid' => 'text-green-600 bg-green-50',
                                'generated' => 'text-blue-600 bg-blue-50',
                                default => 'text-gray-600 bg-gray-100',
                            };
                        @endphp
                        <div
                            class="px-5 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors flex items-center justify-between">
                            <div class="min-w-0 pr-3">
                                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $slip['employee'] ?? '—' }}</p>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-500">
                                    <span class="font-medium text-gray-700">{{ $slip['period'] ?? '—' }}</span>
                                    <span class="w-1 h-1 rounded-full bg-gray-300 inline-block"></span>
                                    <span>#{{ $slip['slip_number'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[13px] font-black text-gray-900">
                                    ₹{{ number_format($slip['net_salary'] ?? 0, 2) }}
                                </p>
                                <p class="mt-1">
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md {{ $statusBadge }}">
                                        {{ $status }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-12 text-center flex flex-col items-center justify-center">
                            <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                <i data-lucide="receipt" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">No recent salary slips.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
@endsection
