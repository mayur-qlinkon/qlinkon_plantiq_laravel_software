@extends('layouts.admin')

@section('title', 'Edit Task')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.hrm.tasks.show', $task) }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </a>
        <div>
            <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit Task</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5 truncate max-w-xs">{{ $task->title }}</p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .form-section {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .section-head {
        padding: 13px 18px;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-icon {
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .section-title { font-size: 12px; font-weight: 800; color: #374151; letter-spacing: 0.03em; }
    .section-body { padding: 18px; }

    .field-label {
        display: block;
        font-size: 11px; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px;
    }
    .field-input {
        width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
        padding: 9px 30px; font-size: 13px; color: #1f2937;
        outline: none; font-family: inherit; background: #fff;
        transition: border-color 150ms ease, box-shadow 150ms ease;
    }
    .field-input:focus {
        border-color: var(--brand-600);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
    }
    select.field-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center;
        padding-right: 36px; cursor: pointer;
    }
    .field-input.has-error { border-color: #f43f5e; }
    .field-error { font-size: 11px; font-weight: 600; color: #f43f5e; margin-top: 4px; }

    .sticky-footer {
        position: sticky; bottom: 0; background: #fff;
        border-top: 1.5px solid #f1f5f9; padding: 14px 24px;
        display: flex; align-items: center; justify-content: flex-end;
        gap: 12px; z-index: 20; border-radius: 0 0 16px 16px;
    }

    .priority-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 9px; border: 2px solid transparent;
        font-size: 12px; font-weight: 700; cursor: pointer;
        transition: all 120ms ease; user-select: none;
    }
    .priority-pill:hover { opacity: 0.85; }
    .priority-pill .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

    .assignee-list {
        border: 1.5px solid #e5e7eb; border-radius: 10px;
        max-height: 200px; overflow-y: auto; background: #fff;
    }
    .assignee-list::-webkit-scrollbar { width: 4px; }
    .assignee-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    .assignee-item {
        display: flex; align-items: center; padding: 9px 13px;
        border-bottom: 1px solid #f8fafc; cursor: pointer;
        transition: background 100ms; gap: 10px;
    }
    .assignee-item:last-child { border-bottom: none; }
    .assignee-item:hover { background: #f9fafb; }
    .assignee-item input[type="checkbox"] { accent-color: var(--brand-600); width: 14px; height: 14px; cursor: pointer; flex-shrink: 0; }
    .assignee-item label { font-size: 12.5px; color: #374151; cursor: pointer; }
    .assignee-item .emp-code { font-size: 10px; color: #9ca3af; font-weight: 600; }
</style>
@endpush

@section('content')

@php
    $currentAssigneeIds  = $task->assignees->pluck('id')->toArray();
    $primaryAssigneeId   = $task->assignments->where('is_primary', true)->first()?->employee_id;
@endphp

<div class="pb-10" x-data="taskEdit()">

    <form method="POST" action="{{ route('admin.hrm.tasks.update', $task) }}">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-start gap-3">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p class="text-sm font-semibold text-red-700">Please fix the errors below.</p>
            </div>
        @endif

        {{-- Section 1 — Task Details --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #eff6ff">
                    <i data-lucide="clipboard" style="width:14px;height:14px;color:#3b82f6"></i>
                </div>
                <span class="section-title">Task Details</span>
            </div>
            <div class="section-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">

                    {{-- Title --}}
                    <div class="sm:col-span-2">
                        <label class="field-label">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $task->title) }}"
                            placeholder="e.g. Prepare Q1 payroll report"
                            class="field-input {{ $errors->has('title') ? 'has-error' : '' }}">
                        @error('title') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Project --}}
                    <div>
                        <label class="field-label">Project</label>
                        <input type="text" name="project" value="{{ old('project', $task->project) }}"
                            placeholder="e.g. Payroll Management"
                            class="field-input {{ $errors->has('project') ? 'has-error' : '' }}">
                        @error('project') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="field-label">Category</label>
                        <input type="text" name="category" value="{{ old('category', $task->category) }}"
                            placeholder="e.g. Finance, HR, IT"
                            class="field-input {{ $errors->has('category') ? 'has-error' : '' }}">
                        @error('category') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Priority --}}
                    <div class="sm:col-span-2">
                        <label class="field-label">Priority <span class="text-red-500">*</span></label>
                        <input type="hidden" name="priority" x-model="priority">
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach(\App\Models\Hrm\HrmTask::PRIORITY_LABELS as $val => $label)
                                @php $pc = \App\Models\Hrm\HrmTask::PRIORITY_COLORS[$val]; @endphp
                                <button type="button"
                                    @click="priority = '{{ $val }}'"
                                    :class="priority === '{{ $val }}' ? 'ring-2 ring-offset-1' : 'opacity-60'"
                                    class="priority-pill"
                                    style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}"
                                    :style="priority === '{{ $val }}' ? 'border-color: {{ $pc['dot'] ?? $pc['text'] }}; opacity: 1;' : 'border-color: transparent;'">
                                    <span class="dot" style="background: {{ $pc['dot'] ?? $pc['text'] }}"></span>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @error('priority') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <label class="field-label">Start Date</label>
                        <input type="date" name="start_date"
                            value="{{ old('start_date', $task->start_date?->format('Y-m-d')) }}"
                            class="field-input {{ $errors->has('start_date') ? 'has-error' : '' }}">
                        @error('start_date') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="field-label">Due Date</label>
                        <input type="date" name="due_date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                            class="field-input {{ $errors->has('due_date') ? 'has-error' : '' }}">
                        @error('due_date') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Progress --}}
                    <div class="sm:col-span-2">
                        <label class="field-label">Progress <span class="text-gray-400 font-normal">({{ $task->progress_percent }}% current)</span></label>
                        <div class="flex items-center gap-3">
                            <input type="range" name="progress_percent"
                                x-model="progress"
                                min="0" max="100" step="5"
                                class="flex-1 h-2 rounded-lg cursor-pointer accent-brand-600"
                                style="accent-color: var(--brand-600)">
                            <span class="text-sm font-bold text-gray-700 w-10 text-right" x-text="progress + '%'"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Section 2 — Description --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #f9fafb">
                    <i data-lucide="file-text" style="width:14px;height:14px;color:#6b7280"></i>
                </div>
                <span class="section-title">Description</span>
            </div>
            <div class="section-body">
                <textarea name="description" rows="5"
                    placeholder="Describe the task in detail..."
                    class="field-input resize-y {{ $errors->has('description') ? 'has-error' : '' }}">{{ old('description', $task->description) }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Section 3 — Assignees --}}
        <div class="form-section">
            <div class="section-head">
                <div class="section-icon" style="background: #faf5ff">
                    <i data-lucide="users" style="width:14px;height:14px;color:#a855f7"></i>
                </div>
                <span class="section-title">Assignees</span>
            </div>
            <div class="section-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">

                    {{-- Primary Assignee --}}
                    <div>
                        <label class="field-label">Primary Assignee</label>
                        <div class="{{ $errors->has('primary_assignee') ? 'ring-1 ring-red-500 rounded-xl' : '' }}">
                            <x-alpine-select 
                                name="primary_assignee" 
                                model="primary_assignee"
                                items="employeeOptions"
                                placeholder="Select primary assignee" 
                                :allow-empty="true" 
                            />
                        </div>
                        @error('primary_assignee') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Additional Assignees --}}
                    <div>
                        <label class="field-label">Additional Assignees</label>
                        <div class="mb-2">
                            <div class="relative">
                                {{-- <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i> --}}
                                <input type="text" x-model="assigneeSearch"
                                    placeholder="Filter employees..."
                                    class="field-input pl-9 !py-2 !text-[12px]">
                            </div>
                        </div>
                        <div class="assignee-list">
                            @foreach($employees as $emp)
                                <label class="assignee-item"
                                    x-show="!assigneeSearch || '{{ strtolower($emp->employee_code . ' ' . $emp->user->name) }}'.includes(assigneeSearch.toLowerCase())">
                                    <input type="checkbox" name="assignees[]" value="{{ $emp->id }}"
                                        {{ in_array($emp->id, old('assignees', $currentAssigneeIds)) ? 'checked' : '' }}>
                                    <div>
                                        <div class="text-[12.5px] font-semibold text-gray-700">{{ $emp->user->name }}</div>
                                        <div class="emp-code">{{ $emp->employee_code }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('assignees') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Sticky Footer --}}
        <div class="sticky-footer">
            <a href="{{ route('admin.hrm.tasks.show', $task) }}"
                class="flex items-center justify-center px-5 py-2.5 rounded-xl text-[13px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-[14px] font-bold text-white transition-opacity hover:opacity-90"
                style="background: var(--brand-600)">
                <i data-lucide="check" style="width:16px;height:16px"></i>
                Save Changes
            </button>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
window.taskEdit = function() {
    return {
        priority: '{{ old('priority', $task->priority) }}',
        progress: {{ old('progress_percent', $task->progress_percent ?? 0) }},
        assigneeSearch: '',
        primary_assignee: '{{ old('primary_assignee', $primaryAssigneeId) }}',
        
        employeeOptions: @js(collect($employees)->map(fn($emp) => ['id' => $emp->id, 'name' => $emp->employee_code . ' – ' . ($emp->user->name ?? 'Unknown')])->values()),

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
    };
};
</script>
@endpush
