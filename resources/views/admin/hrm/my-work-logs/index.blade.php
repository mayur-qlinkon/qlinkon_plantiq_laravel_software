@extends('layouts.admin')

@section('title', 'My Work Logs')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">My Work Logs</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Log your daily work activity</p>
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
            margin-bottom: 5px;
            display: block;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
            background: #fff;
            font-family: inherit;
            transition: border-color 150ms, box-shadow 150ms;
            color: #374151;
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
        }

        .log-row {
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
        }

        .log-row:hover {
            background: #fafbff;
        }

        .log-row:last-child {
            border-bottom: none;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid transparent;
        }

        .pill-draft {
            background: #f3f4f6;
            color: #6b7280;
            border-color: #e5e7eb;
        }

        .pill-submitted {
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .pill-approved {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }

        .pill-rejected {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
    </style>
@endpush

@section('content')
    <div x-data="myWorkLogs()" class="space-y-5 pb-10">

        {{-- Primary action. Previously the only way to open this modal was the
         empty-state button, so it disappeared the moment the first log existed
         and no further logs could be created. --}}
        <div class="flex justify-end">
            <button @click="openCreate()"
                class="flex items-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity"
                style="background: var(--brand-600)">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Log Work Activity
            </button>
        </div>

        {{-- Info note --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-start gap-2.5 text-xs text-blue-700">
            <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5 text-blue-400"></i>
            <span>
                <strong>Draft</strong> = saved but not submitted. You can edit or delete drafts.
                <strong>Submitted</strong> = sent to your manager for approval. Cannot be edited after submission.
            </span>
        </div>

        {{-- Logs Table --}}
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/60">
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">
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
                            <tr class="log-row">
                                <td class="px-5 py-3">
                                    <p class="text-[13px] font-semibold text-gray-800">{{ $log->log_date->format('d M Y') }}
                                    </p>
                                    @if ($log->start_time && $log->end_time)
                                        <p class="text-[10px] text-gray-400">
                                            {{ \Carbon\Carbon::parse($log->start_time)->format('h:i A') }} –
                                            {{ \Carbon\Carbon::parse($log->end_time)->format('h:i A') }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-[12px] text-gray-600">{{ $log->task?->title ?? '—' }}</td>
                                <td class="px-3 py-3">
                                    <p class="text-[12px] text-gray-600 truncate max-w-[200px]"
                                        title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </p>
                                    @if ($log->status === 'rejected' && $log->admin_remarks)
                                        <p class="text-[11px] text-red-500 mt-0.5 italic">
                                            <i data-lucide="message-circle" class="w-3 h-3 inline-block -mt-0.5"></i>
                                            {{ $log->admin_remarks }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <span
                                        class="text-[14px] font-black text-gray-800">{{ number_format($log->hours_worked, 1) }}</span>
                                    <span class="text-[10px] text-gray-400 ml-0.5">h</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @php
                                        $pillClass = match ($log->status) {
                                            'draft' => 'pill-draft',
                                            'submitted' => 'pill-submitted',
                                            'approved' => 'pill-approved',
                                            'rejected' => 'pill-rejected',
                                            default => 'pill-draft',
                                        };
                                    @endphp
                                    <span class="status-pill {{ $pillClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            style="background: {{ match ($log->status) {'submitted' => '#3b82f6','approved' => '#10b981','rejected' => '#ef4444',default => '#9ca3af'} }}">
                                        </span>
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $logData = [
                                            'date' => $log->log_date ? $log->log_date->format('d M Y') : '—',
                                            'time' =>
                                                $log->start_time && $log->end_time
                                                    ? \Carbon\Carbon::parse($log->start_time)->format('h:i A') .
                                                        ' – ' .
                                                        \Carbon\Carbon::parse($log->end_time)->format('h:i A')
                                                    : '',
                                            'task' => $log->task?->title ?? '—',
                                            'description' => $log->description ?? '—',
                                            'hours' => number_format($log->hours_worked, 1),
                                            'status' => $log->status,
                                            'admin_remarks' => $log->admin_remarks,
                                        ];
                                    @endphp
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openView(@js($logData))"
                                            class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                            title="View Details">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                        @if ($log->status === 'draft')
                                            <button type="button" @click="openEdit({{ $log->toJson() }})"
                                                class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 transition-colors"
                                                title="Edit">
                                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            </button>
                                            <button type="button" @click="confirmDelete({{ $log->id }})"
                                                class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 transition-colors"
                                                title="Delete">
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
                                            <i data-lucide="clock" class="w-7 h-7 text-gray-300"></i>
                                        </div>
                                        <p class="font-semibold text-gray-500 mb-1">No work logs yet</p>
                                        <p class="text-sm text-gray-400 mb-4">Log what you worked on today to keep your
                                            manager informed</p>
                                        <button @click="openCreate()"
                                            class="text-sm font-bold px-4 py-2 rounded-xl text-white"
                                            style="background: var(--brand-600)">
                                            Log Today's Work
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-100">
                @forelse($logs as $log)
                    <div class="p-4 flex flex-col gap-3 hover:bg-gray-50 transition-colors">

                        {{-- Header: Date & Status --}}
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[13px] font-semibold text-gray-800">{{ $log->log_date->format('d M Y') }}
                                </p>
                                @if ($log->start_time && $log->end_time)
                                    <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        {{ \Carbon\Carbon::parse($log->start_time)->format('h:i A') }} –
                                        {{ \Carbon\Carbon::parse($log->end_time)->format('h:i A') }}
                                    </p>
                                @endif
                            </div>
                            <div>
                                @php
                                    $pillClass = match ($log->status) {
                                        'draft' => 'pill-draft',
                                        'submitted' => 'pill-submitted',
                                        'approved' => 'pill-approved',
                                        'rejected' => 'pill-rejected',
                                        default => 'pill-draft',
                                    };
                                @endphp
                                <span class="status-pill {{ $pillClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        style="background: {{ match ($log->status) {'submitted' => '#3b82f6','approved' => '#10b981','rejected' => '#ef4444',default => '#9ca3af'} }}">
                                    </span>
                                    {{ ucfirst($log->status) }}
                                </span>
                            </div>
                        </div>

                        {{-- Context: Task --}}
                        @if ($log->task)
                            <div class="flex flex-wrap gap-2 mt-1">
                                <span
                                    class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold">{{ $log->task->title }}</span>
                            </div>
                        @endif

                        {{-- Description --}}
                        <div>
                            <p class="text-[12px] text-gray-600 leading-relaxed">{{ $log->description }}</p>
                            @if ($log->status === 'rejected' && $log->admin_remarks)
                                <div
                                    class="mt-2 p-2.5 bg-red-50 rounded-lg text-[11px] text-red-600 italic border border-red-100">
                                    <i data-lucide="message-circle" class="w-3.5 h-3.5 inline-block -mt-0.5 mr-1"></i>
                                    <strong>Manager Note:</strong> {{ $log->admin_remarks }}
                                </div>
                            @endif
                        </div>

                        {{-- Footer: Hours & Actions --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-50 mt-1">
                            <div class="flex items-center gap-1.5 text-gray-800">
                                <i data-lucide="hourglass" class="w-3.5 h-3.5 text-gray-400"></i>
                                <span class="text-[13px] font-black">{{ number_format($log->hours_worked, 1) }}<span
                                        class="text-[10px] text-gray-400 font-normal ml-0.5">hours</span></span>
                            </div>

                            @php
                                $logDataMobile = [
                                    'date' => $log->log_date ? $log->log_date->format('d M Y') : '—',
                                    'time' =>
                                        $log->start_time && $log->end_time
                                            ? \Carbon\Carbon::parse($log->start_time)->format('h:i A') .
                                                ' – ' .
                                                \Carbon\Carbon::parse($log->end_time)->format('h:i A')
                                            : '',
                                    'task' => $log->task?->title ?? '—',
                                    'description' => $log->description ?? '—',
                                    'hours' => number_format($log->hours_worked, 1),
                                    'status' => $log->status,
                                    'admin_remarks' => $log->admin_remarks,
                                ];
                            @endphp
                            <div class="flex items-center gap-2">
                                <button type="button" @click="openView(@js($logDataMobile))"
                                    class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                                    title="View Details">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                </button>
                                @if ($log->status === 'draft')
                                    <button type="button" @click="openEdit({{ $log->toJson() }})"
                                        class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-blue-50 text-blue-500 hover:bg-blue-100 transition-colors"
                                        title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button type="button" @click="confirmDelete({{ $log->id }})"
                                        class="w-[30px] h-[30px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 transition-colors"
                                        title="Delete">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center mb-3">
                            <i data-lucide="clock" class="w-6 h-6 text-gray-300"></i>
                        </div>
                        <p class="font-semibold text-gray-500 mb-1 text-[13px]">No work logs yet</p>
                        <p class="text-[11px] text-gray-400 mb-4">Log what you worked on today</p>
                        <button @click="openCreate()" class="text-[11px] font-bold px-4 py-2 rounded-lg text-white"
                            style="background: var(--brand-600)">
                            Log Today's Work
                        </button>
                    </div>
                @endforelse
            </div>

            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Create / Edit Modal --}}
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm pb-6"
            @keydown.escape.window="modalOpen = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden mx-4 flex flex-col max-h-[90vh]"
                @click.stop x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-black text-gray-800 text-sm"
                            x-text="isEditing ? 'Edit Work Log' : 'Log Work Activity'"></h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">Save as draft to finish later, or submit for manager
                            review</p>
                    </div>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 flex-1 overflow-y-auto">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Log Date --}}
                        <div class="sm:col-span-2">
                            <label class="field-label">Date <span class="text-red-400">*</span></label>
                            <input type="date" x-model="form.log_date" :max="today" class="field-input"
                                required>
                            <p class="field-error" x-show="errors.log_date" x-text="errors.log_date"></p>
                        </div>

                        {{-- Start Time --}}
                        <div>
                            <label class="field-label">Start Time <span
                                    class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="time" x-model="form.start_time" class="field-input">
                        </div>

                        {{-- End Time --}}
                        <div>
                            <label class="field-label">End Time <span
                                    class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="time" x-model="form.end_time" class="field-input">
                        </div>

                    </div>

                    {{-- Related Task (optional) --}}
                    <div>
                        <label class="field-label">Related Task <span
                                class="text-gray-400 font-normal">(optional)</span></label>
                        <select x-model="form.hrm_task_id" class="field-input">
                            <option value="">No specific task</option>
                            @foreach ($tasks as $task)
                                <option value="{{ $task->id }}">{{ $task->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-gray-400">Link this entry to one of your open tasks to track actual
                            hours against it.</p>
                        <p class="field-error" x-show="errors.hrm_task_id" x-text="errors.hrm_task_id"></p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="field-label">What did you work on? <span class="text-red-400">*</span></label>
                        <textarea x-model="form.description" class="field-input" rows="4"
                            placeholder="Describe what you accomplished during these hours. Be specific — your manager will review this."
                            required></textarea>
                        <p class="field-error" x-show="errors.description" x-text="errors.description"></p>
                    </div>

                </div>

                {{-- Modal Footer --}}
                <div
                    class="px-5 py-4 border-t border-gray-100 bg-gray-50 grid grid-cols-2 sm:flex sm:flex-row justify-end gap-2.5">

                    {{-- Cancel (Half width on mobile) --}}
                    <button type="button" @click="modalOpen = false"
                        class="col-span-1 px-4 py-2.5 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors order-2 sm:order-1">
                        Cancel
                    </button>

                    {{-- Draft (Half width on mobile) --}}
                    <button type="button" @click="submitForm('draft')" :disabled="saving"
                        class="col-span-1 px-4 py-2.5 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-colors disabled:opacity-50 order-3 sm:order-2 flex justify-center items-center">
                        <span x-show="!saving || submitAction !== 'draft'">Save Draft</span>
                        <span x-show="saving && submitAction === 'draft'">Saving…</span>
                    </button>

                    {{-- Submit (Full width on mobile, auto on desktop) --}}
                    <button type="button" @click="submitForm('submitted')" :disabled="saving"
                        class="col-span-2 sm:col-span-1 px-5 py-2.5 text-[13px] font-bold text-white rounded-xl hover:opacity-90 transition-opacity disabled:opacity-50 order-1 sm:order-3 flex justify-center items-center"
                        style="background: var(--brand-600)">
                        <span x-show="!saving || submitAction !== 'submitted'">Submit for Approval</span>
                        <span x-show="saving && submitAction === 'submitted'" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Submitting…
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- View Details Modal --}}
        <div x-show="viewModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm pb-6 px-4"
            @keydown.escape.window="viewModalOpen = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
                @click.stop x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                    <h3 class="font-black text-gray-800 text-sm uppercase tracking-widest">Work Log Details</h3>
                    <button @click="viewModalOpen = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto" x-show="selectedLog">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900" x-text="selectedLog?.date"></h4>
                            <p class="text-sm font-medium text-gray-500" x-show="selectedLog?.time"
                                x-text="selectedLog?.time"></p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-gray-800" x-text="selectedLog?.hours"></span>
                            <span class="text-sm font-bold text-gray-400">h</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-3 border border-gray-100 col-span-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</p>
                            <p class="text-[13px] font-semibold text-gray-800 capitalize" x-text="selectedLog?.status">
                            </p>
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

                    <template x-if="selectedLog?.status === 'rejected' && selectedLog?.admin_remarks">
                        <div class="mt-4 p-3 bg-red-50 rounded-xl border border-red-100">
                            <p
                                class="text-[10px] font-bold text-red-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> Manager Remarks
                            </p>
                            <p class="text-[12px] text-red-700 italic" x-text="selectedLog?.admin_remarks"></p>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end shrink-0">
                    <button type="button" @click="viewModalOpen = false"
                        class="px-5 py-2 text-[13px] font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors w-full sm:w-auto">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        window.myWorkLogs = function() {
            return {
                modalOpen: false,
                viewModalOpen: false,
                selectedLog: null,
                isEditing: false,
                editId: null,
                saving: false,
                submitAction: '',
                errors: {},
                today: new Date().toISOString().split('T')[0],
                form: {
                    log_date: '',
                    start_time: '',
                    end_time: '',
                    description: '',
                    hrm_task_id: '',
                },

                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                resetForm() {
                    this.form = {
                        log_date: this.today,
                        start_time: '',
                        end_time: '',
                        description: '',
                        hrm_task_id: '',
                    };
                    this.errors = {};
                },

                openView(log) {
                    this.selectedLog = log;
                    this.viewModalOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },

                openCreate() {
                    this.resetForm();
                    this.isEditing = false;
                    this.editId = null;
                    this.modalOpen = true;
                },

                openEdit(log) {
                    this.resetForm();
                    this.isEditing = true;
                    this.editId = log.id;
                    this.form.log_date = log.log_date ? log.log_date.split('T')[0] : '';

                    // 🌟 FIX: Strip seconds from model strings during payload initialization
                    this.form.start_time = log.start_time && log.start_time.split(':').length > 2 ? log.start_time
                        .split(':').slice(0, 2).join(':') : (log.start_time || '');
                    this.form.end_time = log.end_time && log.end_time.split(':').length > 2 ? log.end_time.split(':')
                        .slice(0, 2).join(':') : (log.end_time || '');

                    this.form.description = log.description || '';
                    // Empty string, not null — the select's placeholder option uses ''.
                    this.form.hrm_task_id = log.hrm_task_id ?? '';
                    this.modalOpen = true;
                },

                async submitForm(status) {
                    this.saving = true;
                    this.submitAction = status;
                    this.errors = {};

                    const url = this.isEditing ?
                        `{{ url('admin/hrm/my-work-logs') }}/${this.editId}` :
                        `{{ route('admin.hrm.my-work-logs.store') }}`;

                    const method = this.isEditing ? 'PUT' : 'POST';

                    // 🌟 FIX: HTML5 time inputs can pass seconds (H:i:s). Sanitize to strict 'H:i' format before fetch.
                    const sanitizedForm = {
                        ...this.form,
                        status
                    };
                    if (sanitizedForm.start_time && sanitizedForm.start_time.split(':').length > 2) {
                        sanitizedForm.start_time = sanitizedForm.start_time.split(':').slice(0, 2).join(':');
                    }
                    if (sanitizedForm.end_time && sanitizedForm.end_time.split(':').length > 2) {
                        sanitizedForm.end_time = sanitizedForm.end_time.split(':').slice(0, 2).join(':');
                    }

                    try {
                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(sanitizedForm),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (response.status === 422 && data.errors) {
                                // 🌟 FIX: Reset errors first, then safely map only valid fields
                                this.errors = {};
                                for (const [key, msgs] of Object.entries(data.errors)) {
                                    if (['log_date', 'start_time', 'end_time', 'description'].includes(key)) {
                                        this.errors[key] = msgs[0];
                                    }
                                }
                            } else {
                                BizAlert.toast(data.message || 'Something went wrong.', 'error');
                            }
                            this.saving = false; // 🌟 FIX: Release button freeze on validation errors
                            return;
                        }

                        BizAlert.toast(data.message, 'success');
                        this.modalOpen = false;
                        setTimeout(() => location.reload(), 600);
                    } catch {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                confirmDelete(id) {
                    BizAlert.confirm('Delete Log', 'Are you sure you want to delete this draft?', 'Delete').then(
                        async result => {
                            if (!result.isConfirmed) return;

                            try {
                                const response = await fetch(`{{ url('admin/hrm/my-work-logs') }}/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                });
                                const data = await response.json();
                                if (!response.ok) {
                                    BizAlert.toast(data.message || 'Cannot delete.', 'error');
                                    return;
                                }
                                BizAlert.toast(data.message, 'success');
                                setTimeout(() => location.reload(), 600);
                            } catch {
                                BizAlert.toast('Network error.', 'error');
                            }
                        });
                },
            };
        };
    </script>
@endpush
