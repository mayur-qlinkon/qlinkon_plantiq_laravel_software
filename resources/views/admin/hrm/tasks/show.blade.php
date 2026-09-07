@extends('layouts.admin')

@section('title', $task->title)

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.tasks.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round">
                <path d="M19 12H5M12 5l-7 7 7 7" />
            </svg>
        </a>
        <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">{{ $task->title }}</h1>
            <a href="{{ route('admin.hrm.tasks.edit', $task) }}"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
            </a>

            @php
                $pc =
                    \App\Models\Hrm\HrmTask::PRIORITY_COLORS[$task->priority] ??
                    \App\Models\Hrm\HrmTask::PRIORITY_COLORS['low'];
                $sc =
                    \App\Models\Hrm\HrmTask::STATUS_COLORS[$task->status] ??
                    \App\Models\Hrm\HrmTask::STATUS_COLORS['pending'];
            @endphp

            <span
                class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md"
                style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}">
                <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $pc['dot'] ?? $pc['text'] }}"></span>
                {{ \App\Models\Hrm\HrmTask::PRIORITY_LABELS[$task->priority] ?? $task->priority }}
            </span>

            <span
                class="inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md"
                style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}">
                <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $sc['dot'] ?? $sc['text'] }}"></span>
                {{ \App\Models\Hrm\HrmTask::STATUS_LABELS[$task->status] ?? $task->status }}
            </span>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .info-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .card-head {
            padding: 13px 18px;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-title {
            font-size: 12px;
            font-weight: 800;
            color: #374151;
            letter-spacing: 0.03em;
        }

        .card-body {
            padding: 18px;
        }

        .meta-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .meta-row:last-child {
            border-bottom: none;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            min-width: 110px;
            flex-shrink: 0;
            padding-top: 1px;
        }

        .meta-value {
            font-size: 13px;
            color: #374151;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 5px;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 13px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms ease, box-shadow 150ms ease;
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

        /* Progress bar */
        .progress-track {
            height: 8px;
            background: #f1f5f9;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--brand-600);
            transition: width 400ms ease;
        }

        /* Avatar */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            color: #3b82f6;
            flex-shrink: 0;
            text-transform: uppercase;
        }

        /* Comment bubble */
        .comment-bubble {
            background: #f9fafb;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 12px 14px;
        }

        /* Reply bubble */
        .reply-bubble {
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 10px 12px;
        }

        /* Attachment row */
        .attachment-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .attachment-row:last-child {
            border-bottom: none;
        }

        .file-icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')

    @php
        $allowedTransitions = \App\Models\Hrm\HrmTask::STATUS_TRANSITIONS[$task->status] ?? [];
        $statusLabels = \App\Models\Hrm\HrmTask::STATUS_LABELS;
        $statusColors = \App\Models\Hrm\HrmTask::STATUS_COLORS;
    @endphp

    <div class="pb-10" x-data="taskShow()">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- ── Left / Main Column ── --}}
            <div class="lg:col-span-2 space-y-4 order-2 lg:order-1">

                {{-- Task Info Card --}}
                <div class="info-card">
                    <div class="card-head">
                        <div class="card-icon" style="background: #eff6ff">
                            <i data-lucide="info" style="width:14px;height:14px;color:#3b82f6"></i>
                        </div>
                        <span class="card-title">Task Information</span>

                        <a href="{{ route('admin.hrm.tasks.index') }}?status={{ $task->status }}"
                            class="ml-auto text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors">
                            View all tasks
                        </a>
                    </div>
                    <div class="card-body">

                        {{-- Progress --}}
                        @if ($task->progress_percent !== null)
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span
                                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Progress</span>
                                    <span
                                        class="text-[13px] font-black text-gray-700">{{ $task->progress_percent }}%</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: {{ $task->progress_percent }}%"></div>
                                </div>
                            </div>
                        @endif

                        <div class="divide-y divide-gray-50">
                            @if ($task->project)
                                <div class="meta-row">
                                    <span class="meta-label">Project</span>
                                    <span class="meta-value font-semibold">{{ $task->project }}</span>
                                </div>
                            @endif

                            @if ($task->category)
                                <div class="meta-row">
                                    <span class="meta-label">Category</span>
                                    <span class="meta-value">{{ $task->category }}</span>
                                </div>
                            @endif

                            <div class="meta-row">
                                <span class="meta-label">Start Date</span>
                                <span
                                    class="meta-value">{{ $task->started_at ? $task->started_at->format('d M Y, h:i A') : ($task->start_date ? $task->start_date->format('d M Y') : '—') }}</span>
                            </div>

                            <div class="meta-row">
                                <span class="meta-label">Due Date</span>
                                <span class="meta-value {{ $task->is_overdue ? 'text-red-600 font-semibold' : '' }}">
                                    {{ $task->due_date ? $task->due_date->format('d M Y') : '—' }}
                                    @if ($task->is_overdue)
                                        <span
                                            class="ml-1 text-[10px] font-extrabold text-red-500 bg-red-50 px-1.5 py-0.5 rounded">OVERDUE</span>
                                    @endif
                                </span>
                            </div>

                            @if ($task->completed_at)
                                <div class="meta-row">
                                    <span class="meta-label">Completed</span>
                                    <span
                                        class="meta-value text-green-600 font-semibold">{{ $task->completed_at->format('d M Y, h:i A') }}</span>
                                </div>
                            @endif

                            <div class="meta-row">
                                <span class="meta-label">Created By</span>
                                <span class="meta-value">{{ $task->createdByUser?->name ?? '—' }}</span>
                            </div>

                            <div class="meta-row">
                                <span class="meta-label">Created At</span>
                                <span class="meta-value">{{ $task->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description Card --}}
                @if ($task->description)
                    <div class="info-card">
                        <div class="card-head">
                            <div class="card-icon" style="background: #f9fafb">
                                <i data-lucide="file-text" style="width:14px;height:14px;color:#6b7280"></i>
                            </div>
                            <span class="card-title">Description</span>
                        </div>
                        <div class="card-body">
                            <div x-data="taskDescMd(@js($task->description))" x-html="rendered"
                                class="text-[13px] text-gray-700 leading-relaxed">
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Comments Section (Using Reusable Partial) --}}
                <div class="info-card" id="comments-section">
                    <div class="card-body">
                        @include('admin.hrm.tasks.partials._comments', ['taskId' => $task->id])
                    </div>
                </div>

                {{-- Activity Log Section --}}
                <div class="info-card">
                    <div class="card-head">
                        <div class="card-icon" style="background: #f0f9ff">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="card-title">Activity Log</span>
                    </div>
                    <div class="card-body">
                        @if ($task->activities->isEmpty())
                            <div class="text-center py-6">
                                <div
                                    class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-[12px] text-gray-400">No activity yet.</p>
                            </div>
                        @else
                            <div class="relative">
                                {{-- Timeline line --}}
                                <div class="absolute left-[13px] top-0 bottom-0 w-px bg-gray-100"></div>

                                <div class="space-y-0">
                                    @foreach ($task->activities as $activity)
                                        @php
                                            $eventColors = [
                                                'status_changed' => ['bg' => '#eff6ff', 'text' => '#3b82f6'],
                                                'task_created' => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                                                'file_uploaded' => ['bg' => '#faf5ff', 'text' => '#a855f7'],
                                                'file_deleted' => ['bg' => '#fef2f2', 'text' => '#ef4444'],
                                                'assignee_added' => ['bg' => '#fffbeb', 'text' => '#d97706'],
                                                'assignee_removed' => ['bg' => '#fef2f2', 'text' => '#ef4444'],
                                                'comment_added' => ['bg' => '#f0fdfa', 'text' => '#14b8a6'],
                                            ];
                                            $ec = $eventColors[$activity->event] ?? [
                                                'bg' => '#f9fafb',
                                                'text' => '#6b7280',
                                            ];
                                        @endphp
                                        <div class="flex items-start gap-3 pb-4 relative">
                                            {{-- Dot --}}
                                            <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black z-10"
                                                style="background: {{ $ec['bg'] }}; color: {{ $ec['text'] }}">
                                                {{ strtoupper(substr($activity->user->name ?? 'S', 0, 1)) }}
                                            </div>
                                            {{-- Content --}}
                                            <div
                                                class="flex-1 min-w-0 bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 mt-0.5">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="text-[12.5px] font-semibold text-gray-700">
                                                        {{ $activity->description }}</p>
                                                    <span
                                                        class="text-[10px] text-gray-400 whitespace-nowrap flex-shrink-0">{{ $activity->created_at->format('h:i A') }}</span>
                                                </div>
                                                <p class="text-[11px] text-gray-400 mt-0.5">
                                                    {{ $activity->user->name ?? 'System' }} &middot;
                                                    {{ $activity->created_at->format('d M Y') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Attachments Section --}}
                <div class="info-card">
                    <div class="card-head">
                        <div class="card-icon" style="background: #f0fdf4">
                            <i data-lucide="paperclip" style="width:14px;height:14px;color:#16a34a"></i>
                        </div>
                        <span class="card-title">Attachments</span>
                        <span class="ml-2 text-[10px] font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ $task->attachments->count() }}
                        </span>
                    </div>
                    <div class="card-body">

                        {{-- Attachment List --}}
                        <div id="attachmentsList">
                            @if ($task->attachments->count())
                                <div class="mb-4">
                                    @foreach ($task->attachments as $attachment)
                                        <div class="attachment-row" id="att-{{ $attachment->id }}">
                                            <div class="file-icon-wrap">
                                                <i data-lucide="file" style="width:16px;height:16px;color:#3b82f6"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[12.5px] font-semibold text-gray-800 truncate">
                                                    {{ $attachment->original_name ?? $attachment->file_name }}</p>
                                                <p class="text-[10px] text-gray-400">
                                                    {{ $attachment->uploadedByUser?->name ?? 'Unknown' }}
                                                    · {{ $attachment->created_at->format('d M Y') }}
                                                    @if ($attachment->file_size)
                                                        · {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                                @if (has_permission('hrm_tasks.download_attachment'))
                                                    <a href="{{ route('admin.hrm.tasks.attachments.download', $attachment) }}"
                                                        target="_blank"
                                                        class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                                                        title="Download">
                                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                @endif
                                                @if (has_permission('hrm_tasks.delete_attachment'))
                                                    <button type="button"
                                                        onclick="deleteAttachment({{ $attachment->id }}, '{{ addslashes($attachment->original_name ?? $attachment->file_name) }}')"
                                                        class="w-[28px] h-[28px] rounded-lg flex items-center justify-center bg-red-50 text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors"
                                                        title="Delete">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div id="noAttachments" class="text-center py-6 mb-4">
                                    <div
                                        class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                                        <i data-lucide="paperclip" class="w-5 h-5 text-gray-300"></i>
                                    </div>
                                    <p class="text-[12px] text-gray-400">No attachments yet.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Upload Attachment --}}
                        <div class="border-t border-gray-100 pt-4" x-data="attachmentUpload()">
                            <label class="field-label block mb-2">Upload Attachment</label>

                            <div class="flex flex-col gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between"
                                :class="selectedFile ? 'border-teal-400 bg-teal-50/40' : ''">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-full bg-white border border-gray-200 shadow-sm">
                                        <i data-lucide="paperclip" class="h-5 w-5 text-gray-500"></i>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800">Attach a file</p>
                                        <p class="text-xs text-gray-500 truncate" x-show="!selectedFile" x-cloak>
                                            PNG, JPG, PDF, DOC up to your allowed size
                                        </p>
                                        <p class="text-xs text-gray-600 truncate" x-show="selectedFile" x-cloak>
                                            Selected: <span x-text="selectedFile?.name" class="font-semibold"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="file" x-ref="fileInput" @change="fileSelected($event)"
                                        class="hidden" id="fileInputElem">

                                    <button type="button" @click="$refs.fileInput.click()"
                                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <i data-lucide="upload" class="h-4 w-4"></i>
                                        Choose file
                                    </button>

                                    @if (has_permission('hrm_tasks.add_attachment'))
                                        <button type="button" @click="upload({{ $task->id }})"
                                            :disabled="!selectedFile || uploading"
                                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                                            style="background: var(--brand-600)">
                                            <i data-lucide="upload" class="h-4 w-4"></i>
                                            <span x-show="!uploading">Upload</span>
                                            <span x-show="uploading">Uploading...</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            {{-- ── Right / Sidebar Column ── --}}
            <div class="space-y-4 order-1 lg:order-2">

                {{-- Status Update Card --}}
                @if (count($allowedTransitions))
                    <div class="info-card">
                        <div class="card-head">
                            <div class="card-icon" style="background: #fffbeb">
                                <i data-lucide="refresh-cw" style="width:14px;height:14px;color:#d97706"></i>
                            </div>
                            <span class="card-title">Update Status</span>
                        </div>
                        <div class="card-body" x-data="statusUpdate()">
                            <div class="mb-3">
                                <label class="field-label">Move to</label>
                                <select x-model="newStatus" class="field-input">
                                    <option value="">Select new status</option>
                                    @foreach ($allowedTransitions as $s)
                                        @php $c = $statusColors[$s] ?? $statusColors['pending']; @endphp
                                        <option value="{{ $s }}">{{ $statusLabels[$s] ?? $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="field-label">Note (optional)</label>
                                <textarea x-model="note" rows="2" placeholder="Add a note about this status change..."
                                    class="field-input resize-none !text-[12px]"></textarea>
                            </div>
                            <button type="button" @click="update({{ $task->id }})"
                                :disabled="!newStatus || updating"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-bold text-white rounded-lg hover:opacity-90 transition-opacity disabled:opacity-40"
                                style="background: var(--brand-600)">
                                <i data-lucide="check" style="width:14px;height:14px"></i>
                                <span x-show="!updating">Update Status</span>
                                <span x-show="updating">Updating...</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="info-card">
                        <div class="card-head">
                            <div class="card-icon" style="background: #f9fafb">
                                <i data-lucide="lock" style="width:14px;height:14px;color:#9ca3af"></i>
                            </div>
                            <span class="card-title">Status</span>
                        </div>
                        <div class="card-body">
                            <p class="text-[12px] text-gray-400 text-center py-2">
                                This task is <span
                                    class="font-bold text-gray-600">{{ $statusLabels[$task->status] }}</span> — no further
                                transitions available.
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Assignees Card --}}
                <div class="info-card">
                    <div class="card-head">
                        <div class="card-icon" style="background: #faf5ff">
                            <i data-lucide="users" style="width:14px;height:14px;color:#a855f7"></i>
                        </div>
                        <span class="card-title">Assignees</span>
                    </div>
                    <div class="card-body">
                        @if ($task->assignees->count())
                            <div class="space-y-2">
                                @foreach ($task->assignees as $emp)
                                    <div class="flex items-center gap-3">
                                        <div class="user-avatar">{{ strtoupper(substr($emp->user->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-[12.5px] font-semibold text-gray-800">{{ $emp->user->name }}
                                            </p>
                                            <p class="text-[10px] text-gray-400">
                                                {{ $emp->employee_code }}
                                                @if ($emp->pivot->is_primary)
                                                    <span
                                                        class="ml-1 text-[9px] font-extrabold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded">PRIMARY</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-[12px] text-gray-400 text-center py-3">No assignees yet.</p>
                        @endif
                    </div>
                </div>

                {{-- Quick Info Summary --}}
                <div class="info-card">
                    <div class="card-head">
                        <div class="card-icon" style="background: #f0fdfa">
                            <i data-lucide="bar-chart-2" style="width:14px;height:14px;color:#14b8a6"></i>
                        </div>
                        <span class="card-title">Summary</span>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Comments</span>
                                <span class="text-[13px] font-black text-gray-700">{{ $task->comments->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span
                                    class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Attachments</span>
                                <span
                                    class="text-[13px] font-black text-gray-700">{{ $task->attachments->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Assignees</span>
                                <span class="text-[13px] font-black text-gray-700">{{ $task->assignees->count() }}</span>
                            </div>
                            @if ($task->progress_percent !== null)
                                <div class="flex justify-between items-center">
                                    <span
                                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wide">Progress</span>
                                    <span
                                        class="text-[13px] font-black text-gray-700">{{ $task->progress_percent }}%</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        window.taskShow = function() {
            return {


                init() {
                    this.$nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                },


            };
        };

        window.statusUpdate = function() {
            return {
                newStatus: '',
                note: '',
                updating: false,

                async update(taskId) {
                    if (!this.newStatus) return;
                    this.updating = true;
                    try {
                        const res = await fetch(`{{ url('admin/hrm/tasks') }}/${taskId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                status: this.newStatus,
                                note: this.note
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            BizAlert.toast(data.message || 'Failed to update status', 'error');
                            return;
                        }
                        BizAlert.toast(data.message, 'success');
                        setTimeout(() => window.location.reload(), 500);
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    } finally {
                        this.updating = false;
                    }
                },
            };
        };

        window.attachmentUpload = function() {
            return {
                selectedFile: null,
                uploading: false,

                fileSelected(event) {
                    this.selectedFile = event.target.files[0] || null;
                },

                async upload(taskId) {
                    if (!this.selectedFile) return;
                    this.uploading = true;

                    const formData = new FormData();
                    formData.append('file', this.selectedFile);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    try {
                        const res = await fetch(`{{ url('admin/hrm/tasks') }}/${taskId}/attachments`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json'
                            },
                            body: formData,
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            BizAlert.toast(data.message || 'Upload failed', 'error');
                            return;
                        }
                        BizAlert.toast('Attachment uploaded.', 'success');
                        setTimeout(() => window.location.reload(), 500);
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    } finally {
                        this.uploading = false;
                    }
                },
            };
        };

        // ── Delete attachment (outside Alpine for global scope) ──
        function deleteAttachment(id, name) {
            BizAlert.confirm('Delete Attachment', `Remove "${name}" from this task?`, 'Delete').then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const res = await fetch(`{{ url('admin/hrm/tasks/attachments') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        BizAlert.toast(data.message || 'Cannot delete', 'error');
                        return;
                    }
                    BizAlert.toast(data.message, 'success');
                    const el = document.getElementById(`att-${id}`);
                    if (el) el.remove();
                } catch (e) {
                    BizAlert.toast('Network error. Please try again.', 'error');
                }
            });
        }

        /* ── Task description markdown Alpine component ──────────────────
           Uses Alpine.js instead of DOMContentLoaded so it re-runs correctly
           on every AJAX navigation (the layout calls Alpine.initTree() on each
           soft navigation — DOMContentLoaded does NOT fire on those).
        ──────────────────────────────────────────────────────────────── */
        window.taskDescMd = function(raw) {
            return {
                rendered: '',
                init() {
                    this.rendered = this._render(raw || '');
                },
                _inline(t) {
                    t = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    t = t.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
                    t = t.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
                    return t;
                },
                _render(text) {
                    if (!text || !text.trim()) return '';
                    const lines = text.split('\n');
                    let html = '';
                    let inList = false;
                    lines.forEach(line => {
                        if (/^- /.test(line)) {
                            if (!inList) {
                                html += '<ul style="list-style:disc;padding-left:1.1rem;margin:4px 0">';
                                inList = true;
                            }
                            html += `<li style="margin:2px 0">${this._inline(line.slice(2))}</li>`;
                        } else {
                            if (inList) {
                                html += '</ul>';
                                inList = false;
                            }
                            if (line.trim() === '') {
                                html += '<div style="height:6px"></div>';
                            } else {
                                html += `<p style="margin:2px 0">${this._inline(line)}</p>`;
                            }
                        }
                    });
                    if (inList) html += '</ul>';
                    return html;
                },
            };
        };
    </script>
@endpush
