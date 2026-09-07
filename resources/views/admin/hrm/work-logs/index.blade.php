@extends('layouts.admin')

@section('title', 'Work Logs')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Work Logs</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Review and approve employee work logs</p>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
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
            transition: border-color 150ms ease, box-shadow 150ms ease;
            font-family: inherit;
            background: #fff;
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        select.field-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
            cursor: pointer;
        }

        .field-error {
            font-size: 11px;
            color: #dc2626;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
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
    </style>
@endpush

@section('content')

    <div class="pb-10" x-data="workLogPage()">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Hours</p>
                <p class="text-2xl font-black text-gray-900">
                    {{ number_format($logs->getCollection()->sum('hours_worked'), 1) }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Approved</p>
                <p class="text-2xl font-black text-green-600">
                    {{ $logs->getCollection()->where('status', 'approved')->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pending</p>
                <p class="text-2xl font-black text-blue-500">
                    {{ $logs->getCollection()->where('status', 'submitted')->count() }}</p>
            </div>
            <div class="stat-card">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Rejected</p>
                <p class="text-2xl font-black text-red-500">
                    {{ $logs->getCollection()->where('status', 'rejected')->count() }}</p>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3 mb-4">
            <form method="GET" action="{{ route('admin.hrm.work-logs.index') }}">
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="min-w-[160px]">
                        <x-custom-select name="employee_id" placeholder="All Employees" :options="$employees->mapWithKeys(
                            fn($emp) => [
                                $emp->id => ($emp->user?->name ?? 'Unknown') . ' (' . $emp->employee_code . ')',
                            ],
                        )"
                            selected="{{ request('employee_id') }}" />
                    </div>
                    <div class="min-w-[130px]">
                        <x-custom-select name="status" placeholder="All Status" :options="[
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ]"
                            selected="{{ request('status') }}" />
                    </div>
                    <div>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="field-input !py-2 !text-[13px]" placeholder="From">
                    </div>
                    <div>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="field-input !py-2 !text-[13px]" placeholder="To">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity"
                        style="background: var(--brand-600)">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        Filter
                    </button>
                    @if (request()->hasAny(['employee_id', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('admin.hrm.work-logs.index') }}"
                            class="inline-flex items-center gap-1.5 text-[12px] font-bold px-4 py-2 rounded-lg text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th
                                class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider w-[50px]">
                                #</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Employee</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Date</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Task</th>
                            <th class="px-3 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Description</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Hours</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Status</th>
                            <th class="px-4 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $statusBadge = match ($log->status) {
                                    'approved' => 'text-green-700 bg-green-50 border-green-200',
                                    'submitted' => 'text-blue-700 bg-blue-50 border-blue-200',
                                    'rejected' => 'text-red-700 bg-red-50 border-red-200',
                                    default => 'text-gray-500 bg-gray-50 border-gray-200',
                                };
                                $statusLabels = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Submitted',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ];
                            @endphp
                            <tr class="table-row">
                                <td class="px-5 py-3 text-[12px] font-bold text-gray-400">
                                    {{ $logs->firstItem() + $loop->index }}</td>
                                <td class="px-5 py-3">
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-800">
                                            {{ $log->employee?->user?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400">{{ $log->employee?->employee_code }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-[12px] text-gray-600">
                                    {{ $log->log_date ? \Carbon\Carbon::parse($log->log_date)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-3 py-3 text-[12px] text-gray-600">
                                    @if ($log->task)
                                        <a href="{{ route('admin.hrm.tasks.show', $log->task->id) }}"
                                            class="font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                                            {{ $log->task->title }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <p class="text-[12px] text-gray-600 truncate max-w-[180px]"
                                        title="{{ $log->description }}">
                                        {{ $log->description ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="text-[13px] font-black text-gray-800">{{ number_format($log->hours_worked, 1) }}</span>
                                    <span class="text-[10px] text-gray-400 ml-0.5">h</span>
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="inline-flex items-center text-[10px] font-extrabold uppercase tracking-wider border px-2.5 py-1 rounded-md {{ $statusBadge }}">
                                        {{ $statusLabels[$log->status] ?? $log->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $logData = [
                                            'id' => $log->id,
                                            'employee_name' => $log->employee?->user?->name ?? '—',
                                            'employee_code' => $log->employee?->employee_code ?? '',
                                            'date' => $log->log_date
                                                ? \Carbon\Carbon::parse($log->log_date)->format('d M Y')
                                                : '—',
                                            'task' => $log->task?->title ?? '—',
                                            'description' => $log->description ?? '—',
                                            'hours' => number_format($log->hours_worked, 1),
                                            'status' => $log->status,
                                            'status_label' => $statusLabels[$log->status] ?? $log->status,
                                            'can_approve' =>
                                                $log->status === 'submitted' && has_permission('work_logs.approve'),
                                        ];
                                    @endphp
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="viewLog(@js($logData))"
                                            class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                            title="View Details">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @if ($log->status === 'submitted' && has_permission('work_logs.approve'))
                                            <button @click="approveLog({{ $log->id }})"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-green-50 text-green-500 hover:bg-green-100 hover:text-green-700 transition-colors"
                                                title="Approve">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button @click="rejectLog({{ $log->id }})"
                                                class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors"
                                                title="Reject">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        @else
                                            <span class="text-[11px] text-gray-300 italic pr-1">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="flex flex-col items-center justify-center py-20 text-center">
                                        <div
                                            class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                            <i data-lucide="clock" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No work logs found</p>
                                        <p class="text-sm text-gray-400">Employees submit their daily work logs for your
                                            review.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50 bg-white">
                @forelse($logs as $log)
                    @php
                        $statusBadge = match ($log->status) {
                            'approved' => 'text-green-700 bg-green-50 border-green-200',
                            'submitted' => 'text-blue-700 bg-blue-50 border-blue-200',
                            'rejected' => 'text-red-700 bg-red-50 border-red-200',
                            default => 'text-gray-500 bg-gray-50 border-gray-200',
                        };
                        $statusLabels = [
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ];
                    @endphp
                    <div class="p-4 hover:bg-gray-50/50 transition-colors flex flex-col gap-3">

                        {{-- Header: Employee & Hours --}}
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="text-[14px] font-bold text-gray-900 leading-tight truncate">
                                    {{ $log->employee?->user?->name ?? '—' }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 font-medium">
                                    {{ $log->employee?->employee_code }}</p>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end">
                                <span
                                    class="font-black text-gray-800 text-[15px]">{{ number_format($log->hours_worked, 1) }}<span
                                        class="text-[10px] text-gray-400 ml-0.5 font-bold">h</span></span>
                            </div>
                        </div>

                        {{-- Context: Task & Date --}}
                        <div class="flex flex-col gap-2 bg-gray-50/80 px-3 py-2.5 rounded-lg border border-gray-100">
                            <div class="flex justify-between items-start gap-2">
                                @if ($log->task)
                                    <a href="{{ route('admin.hrm.tasks.show', $log->task->id) }}"
                                        class="text-[12px] font-bold text-brand-600 hover:underline">
                                        {{ $log->task->title }}
                                    </a>
                                @else
                                    <span class="text-[12px] font-bold text-gray-700">No Task Linked</span>
                                @endif
                                <span
                                    class="text-[10px] text-gray-500 font-medium shrink-0 flex items-center gap-1 mt-0.5">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                    {{ $log->log_date ? \Carbon\Carbon::parse($log->log_date)->format('d M y') : '—' }}
                                </span>
                            </div>
                            @if ($log->description)
                                <div class="text-[11px] text-gray-600 border-t border-gray-100/50 pt-1.5 line-clamp-2">
                                    {{ $log->description }}
                                </div>
                            @endif
                            <div class="flex items-center gap-2 pt-1 border-t border-gray-100/50 mt-1">
                                <span
                                    class="ml-auto inline-flex items-center text-[9px] font-extrabold uppercase tracking-wider border px-1.5 py-0.5 rounded {{ $statusBadge }}">
                                    {{ $statusLabels[$log->status] ?? $log->status }}
                                </span>
                            </div>
                        </div>

                        @php
                            $logDataMobile = [
                                'id' => $log->id,
                                'employee_name' => $log->employee?->user?->name ?? '—',
                                'employee_code' => $log->employee?->employee_code ?? '',
                                'date' => $log->log_date ? \Carbon\Carbon::parse($log->log_date)->format('d M Y') : '—',
                                'task' => $log->task?->title ?? '—',
                                'description' => $log->description ?? '—',
                                'hours' => number_format($log->hours_worked, 1),
                                'status' => $log->status,
                                'status_label' => $statusLabels[$log->status] ?? $log->status,
                                'can_approve' => $log->status === 'submitted' && has_permission('work_logs.approve'),
                            ];
                        @endphp
                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-50 mt-1">
                            <button type="button" @click="viewLog(@js($logDataMobile))"
                                class="w-8 h-8 rounded-lg flex items-center justify-center border border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100 transition-colors"
                                title="View Details">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            @if ($log->status === 'submitted' && has_permission('work_logs.approve'))
                                <button @click="rejectLog({{ $log->id }})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center border border-red-200 bg-red-50 text-red-500 hover:bg-red-100 transition-colors"
                                    title="Reject">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                                <button @click="approveLog({{ $log->id }})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 transition-colors"
                                    title="Approve">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="p-8 text-center bg-white">
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div
                                class="w-14 h-14 bg-gray-50 border border-gray-100 rounded-2xl flex items-center justify-center mb-3">
                                <i data-lucide="clock" class="w-7 h-7 text-gray-300"></i>
                            </div>
                            <p class="font-bold text-gray-600 mb-1 text-sm">No work logs found</p>
                            <p class="text-xs text-gray-400">Employees submit their daily work logs for your review.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Reject Remarks Modal --}}
        <div x-show="rejectModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white w-full max-w-md rounded-xl shadow-2xl overflow-hidden mx-4"
                @click.away="rejectModalOpen = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Reject Work Log</h3>
                    <button @click="rejectModalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="field-label">Rejection Remarks</label>
                        <textarea x-model="rejectRemarks" class="field-input" rows="3" placeholder="Reason for rejection..."></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                    <button type="button" @click="rejectModalOpen = false"
                        class="px-4 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="submitReject()" :disabled="saving"
                        class="px-5 py-2 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 bg-red-600">
                        <span x-show="!saving">Confirm Reject</span>
                        <span x-show="saving" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- View Log Details Modal --}}
        <div x-show="viewModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 sm:p-0"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
                @click.away="viewModalOpen = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                    <h3 class="font-black text-gray-800 uppercase tracking-widest text-sm">Work Log Details</h3>
                    <button @click="viewModalOpen = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto" x-show="selectedLog">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900" x-text="selectedLog?.employee_name"></h4>
                            <p class="text-sm font-medium text-gray-500" x-text="selectedLog?.employee_code"></p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-gray-800" x-text="selectedLog?.hours"></span>
                            <span class="text-sm font-bold text-gray-400">h</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 col-span-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Date</p>
                            <p class="text-[13px] font-semibold text-gray-800" x-text="selectedLog?.date"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 col-span-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Task</p>
                            <p class="text-[13px] font-semibold text-gray-800" x-text="selectedLog?.task"></p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Description</p>
                        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 text-[13px] text-gray-700 leading-relaxed whitespace-pre-wrap break-words"
                            x-text="selectedLog?.description"></div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0">
                    <template x-if="selectedLog?.can_approve">
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="button" @click="rejectLogFromModal()"
                                class="flex-1 sm:flex-none px-5 py-2 text-[13px] font-bold text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="x" class="w-4 h-4"></i> Reject
                            </button>
                            <button type="button" @click="approveLogFromModal()"
                                class="flex-1 sm:flex-none px-5 py-2 text-[13px] font-bold text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <i data-lucide="check" class="w-4 h-4"></i> Approve
                            </button>
                        </div>
                    </template>
                    <template x-if="!selectedLog?.can_approve">
                        <button type="button" @click="viewModalOpen = false"
                            class="px-5 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            Close
                        </button>
                    </template>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        window.workLogPage = function() {
            return {
                rejectModalOpen: false,
                rejectId: null,
                rejectRemarks: '',
                saving: false,

                viewModalOpen: false,
                selectedLog: null,

                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                async approveLog(id) {
                    try {
                        const response = await fetch(`{{ url('admin/hrm/work-logs') }}/${id}/approve`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'approve'
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            BizAlert.toast(data.message || 'Something went wrong', 'error');
                            return;
                        }

                        BizAlert.toast(data.message || 'Work log approved', 'success');
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    }
                },

                viewLog(log) {
                    this.selectedLog = log;
                    this.viewModalOpen = true;

                    // Wait for Alpine to inject the <template x-if> DOM elements, then render icons
                    this.$nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                },

                approveLogFromModal() {
                    if (!this.selectedLog) return;
                    this.viewModalOpen = false;
                    this.approveLog(this.selectedLog.id);
                },

                rejectLogFromModal() {
                    if (!this.selectedLog) return;
                    this.viewModalOpen = false;

                    // Allow the view modal to close gracefully before opening the reject modal
                    setTimeout(() => {
                        this.rejectLog(this.selectedLog.id);
                    }, 250);
                },

                rejectLog(id) {
                    this.rejectId = id;
                    this.rejectRemarks = '';
                    this.rejectModalOpen = true;
                },

                async submitReject() {
                    this.saving = true;
                    try {
                        const response = await fetch(`{{ url('admin/hrm/work-logs') }}/${this.rejectId}/approve`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'reject',
                                remarks: this.rejectRemarks
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            BizAlert.toast(data.message || 'Something went wrong', 'error');
                            return;
                        }

                        BizAlert.toast(data.message || 'Work log rejected', 'success');
                        this.rejectModalOpen = false;
                        setTimeout(() => window.location.reload(), 600);
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                confirmDelete(id) {
                    BizAlert.confirm('Delete Work Log', 'Are you sure you want to delete this work log?', 'Delete')
                        .then(async (result) => {
                            if (!result.isConfirmed) return;

                            try {
                                const response = await fetch(`{{ url('admin/hrm/work-logs') }}/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                });

                                const data = await response.json();

                                if (!response.ok) {
                                    BizAlert.toast(data.message || 'Cannot delete', 'error');
                                    return;
                                }

                                BizAlert.toast(data.message, 'success');
                                setTimeout(() => window.location.reload(), 600);
                            } catch (e) {
                                BizAlert.toast('Network error. Please try again.', 'error');
                            }
                        });
                },
            };
        };
    </script>
@endpush
