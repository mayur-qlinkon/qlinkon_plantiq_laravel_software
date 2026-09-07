@extends('layouts.admin')

@section('title', 'My Tasks')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">My Tasks</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Manage your assigned work and update progress</p>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* ── Hide Scrollbar for Board ── */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* ── Custom Range Slider ── */
    input[type=range] { -webkit-appearance: none; background: transparent; }
    input[type=range]::-webkit-slider-thumb {
        -webkit-appearance: none; height: 16px; width: 16px; border-radius: 50%;
        background: #fff; border: 2px solid var(--brand-600); cursor: pointer; margin-top: -6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    input[type=range]::-webkit-slider-runnable-track {
        width: 100%; height: 6px; cursor: pointer; background: #e5e7eb; border-radius: 99px;
    }
</style>
@endpush

@section('content')

@php
    $statusLabels = \App\Models\Hrm\HrmTask::STATUS_LABELS;
    $statusColors = \App\Models\Hrm\HrmTask::STATUS_COLORS;
    $priorityLabels = \App\Models\Hrm\HrmTask::PRIORITY_LABELS;
    $priorityColors = \App\Models\Hrm\HrmTask::PRIORITY_COLORS;
    $boardCols = [
        'pending'     => ['label' => 'Assigned',    'hbg' => '#eff6ff', 'htxt' => '#1e40af', 'cbg' => '#dbeafe'],
        'in_progress' => ['label' => 'In Progress', 'hbg' => '#f5f3ff', 'htxt' => '#5b21b6', 'cbg' => '#ede9fe'],
        'in_review'   => ['label' => 'In Review',   'hbg' => '#fffbeb', 'htxt' => '#92400e', 'cbg' => '#fef3c7'],
        'completed'   => ['label' => 'Completed',   'hbg' => '#ecfdf5', 'htxt' => '#065f46', 'cbg' => '#d1fae5'],
        'cancelled'   => ['label' => 'Rejected',    'hbg' => '#fef2f2', 'htxt' => '#991b1b', 'cbg' => '#fee2e2'],
        'on_hold'     => ['label' => 'On Hold',     'hbg' => '#f3f4f6', 'htxt' => '#374151', 'cbg' => '#e5e7eb'],
    ];
@endphp

<div x-data="myTasks()" class="w-full pb-10 space-y-6">

    {{-- ── Toolbar ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
        
        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-auto flex-1 sm:flex-none">
                <i data-lucide="filter" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <select x-model="filterStatus" @change="applyFilters()" class="w-full sm:w-auto pl-9 pr-8 py-2 text-sm font-semibold text-gray-600 bg-gray-50 border border-transparent hover:border-gray-200 rounded-xl outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    @foreach($statusLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="relative w-full sm:w-auto flex-1 sm:flex-none">
                <i data-lucide="flag" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <select x-model="filterPriority" @change="applyFilters()" class="w-full sm:w-auto pl-9 pr-8 py-2 text-sm font-semibold text-gray-600 bg-gray-50 border border-transparent hover:border-gray-200 rounded-xl outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Priorities</option>
                    @foreach($priorityLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <button x-show="filterStatus || filterPriority" @click="clearFilters()" class="text-sm font-bold text-red-500 hover:text-red-600 px-2 transition-colors">
                Clear
            </button>
        </div>

        {{-- View Toggle --}}
        <div class="flex items-center bg-gray-100 p-1 rounded-xl w-full sm:w-auto">
            <button @click="view = 'list'" :class="view === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all">
                <i data-lucide="list" class="w-4 h-4"></i> List
            </button>
            <button @click="view = 'board'" :class="view === 'board' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Board
            </button>
        </div>
    </div>

    {{-- ══════════════ LIST VIEW ══════════════ --}}
    <div x-show="view === 'list'" x-cloak class="transition-opacity duration-300">
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Task</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-5 py-4 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($tasks as $task)
                        @php
                            $pc = $priorityColors[$task->priority];
                            $sc = $statusColors[$task->status];
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors task-row cursor-pointer" data-status="{{ $task->status }}" data-priority="{{ $task->priority }}" @click="openPanel({{ $task->id }})">
                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-gray-900">{{ $task->title }}</p>
                                @if($task->project)
                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-1"><i data-lucide="folder" class="w-3 h-3"></i> {{ $task->project }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider" style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}">
                                    {{ $priorityLabels[$task->priority] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm font-medium {{ $task->is_overdue ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                @if($task->due_date)
                                    {{ $task->due_date->format('d M Y') }}
                                    @if($task->is_overdue) <span class="ml-1 text-[10px] uppercase bg-red-100 px-1.5 py-0.5 rounded-md">Late</span> @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border-color: {{ $sc['dot'] }}40">
                                    {{ $statusLabels[$task->status] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 min-w-[140px]">
                                <div class="flex items-center gap-3">
                                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $task->progress_percent }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-700 w-8">{{ $task->progress_percent }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300"></i>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                    <i data-lucide="check-circle" class="w-8 h-8 text-gray-300"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-1">You're all caught up!</h3>
                                <p class="text-sm text-gray-500">No tasks assigned to you right now.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="md:hidden divide-y divide-gray-50 border-t border-gray-50">
                @forelse($tasks as $task)
                @php
                    $pc = $priorityColors[$task->priority];
                    $sc = $statusColors[$task->status];
                @endphp
                <div class="p-4 hover:bg-gray-50/80 transition-colors cursor-pointer task-row" data-status="{{ $task->status }}" data-priority="{{ $task->priority }}" @click="openPanel({{ $task->id }})">
                    
                    {{-- Header: Priority & Due Date --}}
                    <div class="flex justify-between items-start mb-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}">
                            {{ $priorityLabels[$task->priority] }}
                        </span>
                        <span class="text-[11px] font-medium {{ $task->is_overdue ? 'text-red-600 font-bold' : 'text-gray-500' }} flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            @if($task->due_date)
                                {{ $task->due_date->format('d M Y') }}
                                @if($task->is_overdue) <span class="text-[9px] uppercase bg-red-100 text-red-600 px-1 rounded ml-0.5">Late</span> @endif
                            @else
                                No Date
                            @endif
                        </span>
                    </div>

                    {{-- Task Title & Project --}}
                    <div class="mb-3.5 pr-4">
                        <p class="text-[13px] font-bold text-gray-900 leading-snug">{{ $task->title }}</p>
                        @if($task->project)
                            <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1.5">
                                <i data-lucide="folder" class="w-3 h-3"></i> {{ $task->project }}
                            </p>
                        @endif
                    </div>

                    {{-- Footer: Status & Progress --}}
                    <div class="flex items-center justify-between pt-3 border-t border-gray-50 mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider border" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border-color: {{ $sc['dot'] }}40">
                            {{ $statusLabels[$task->status] }}
                        </span>
                        
                        <div class="flex items-center gap-2 w-32">
                            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $task->progress_percent }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-gray-600 w-8 text-right">{{ $task->progress_percent }}%</span>
                        </div>
                    </div>

                </div>
                @empty
                <div class="p-8 text-center text-sm text-gray-400">
                    <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3 border border-gray-100">
                        <i data-lucide="check-circle" class="w-6 h-6 text-gray-300"></i>
                    </div>
                    <p class="font-bold text-gray-900 mb-1">You're all caught up!</p>
                    <p class="text-xs text-gray-500">No tasks assigned to you right now.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════════ BOARD VIEW ══════════════ --}}
    <div x-show="view === 'board'" x-cloak class="transition-opacity duration-300">
        {{-- Mobile snap scrolling container --}}
        <div class="flex gap-5 overflow-x-auto hide-scrollbar snap-x snap-mandatory pb-6 pt-2 w-full h-[calc(100vh-220px)] min-h-[500px] items-start">
            @foreach($boardCols as $status => $col)
            @php $colTasks = $board[$status] ?? collect(); @endphp
            
            {{-- Column (Width optimized for swiping on mobile, normal on desktop) --}}
            <div class="flex-shrink-0 w-[85vw] sm:w-[300px] snap-center flex flex-col h-full bg-gray-50/50 rounded-2xl border border-gray-100">
                
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-white/50 rounded-t-2xl">
                    <h3 class="text-sm font-black tracking-wide" style="color: {{ $col['htxt'] }}">{{ $col['label'] }}</h3>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full" style="background: {{ $col['cbg'] }}; color: {{ $col['htxt'] }}">{{ $colTasks->count() }}</span>
                </div>

                {{-- Cards Container --}}
                <div class="p-3 space-y-3 overflow-y-auto flex-1 task-board-col" data-status="{{ $status }}">
                    @forelse($colTasks as $task)
                    @php $pc = $priorityColors[$task->priority]; @endphp
                    
                    {{-- Card --}}
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:border-gray-300 hover:shadow-md transition-all cursor-pointer task-card"
                         data-id="{{ $task->id }}" data-status="{{ $task->status }}" data-priority="{{ $task->priority }}"
                         @click="openPanel({{ $task->id }})">
                        
                        <div class="flex items-start justify-between mb-3 gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" style="background: {{ $pc['bg'] }}; color: {{ $pc['text'] }}">
                                {{ $priorityLabels[$task->priority] }}
                            </span>
                            @if($task->is_overdue)
                                <i data-lucide="triangle-alert" class="w-4 h-4 text-red-500 flex-shrink-0" title="Overdue"></i>
                            @endif
                        </div>
                        
                        <h4 class="text-sm font-bold text-gray-900 leading-snug mb-3 line-clamp-2">{{ $task->title }}</h4>
                        
                        <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
                            <div class="flex items-center gap-1.5 {{ $task->is_overdue ? 'text-red-500 font-bold' : '' }}">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                {{ $task->due_date ? $task->due_date->format('d M') : 'No Date' }}
                            </div>
                            <span>{{ $task->progress_percent }}%</span>
                        </div>
                        
                        <div class="mt-2 w-full h-1 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $task->progress_percent }}%"></div>
                        </div>
                    </div>
                    @empty
                    <div class="border-2 border-dashed border-gray-200/60 rounded-xl p-6 text-center h-24 flex items-center justify-center">
                        <p class="text-xs font-semibold text-gray-400">No tasks</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     TASK DETAIL SLIDE PANEL
══════════════════════════════════════════ --}}
<div x-data="taskPanel()">
    {{-- Backdrop (Fades in) --}}
    <div x-show="open" x-transition.opacity.duration.300ms class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-40" @click="close()"></div>

    {{-- Panel (Slides in securely using Tailwind classes) --}}
    <div class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col transition-transform duration-300 ease-in-out"
         :class="open ? 'translate-x-0' : 'translate-x-full'">

        {{-- ── 1. FIXED HEADER ── --}}
        <div class="flex-shrink-0 px-6 py-5 border-b border-gray-100 bg-white" x-show="task">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider mb-2"
                        :style="`background: ${priorityColor?.bg}; color: ${priorityColor?.text}`"
                        x-text="task?.priority?.toUpperCase() + ' PRIORITY'"></span>
                    <h2 class="text-lg font-black text-gray-900 leading-tight" x-text="task?.title"></h2>
                    <p class="text-xs mt-2 flex items-center gap-1.5" :class="task?.is_overdue ? 'text-red-600 font-bold' : 'text-gray-500 font-medium'">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        <span x-text="task?.due_date ? 'Due ' + formatDate(task.due_date) : 'No due date'"></span>
                    </p>
                </div>
                <button @click="close()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex items-center gap-6 mt-6 border-b border-gray-100">
                <button @click="tab = 'overview'" class="pb-2.5 text-sm font-bold border-b-2 transition-colors" :class="tab === 'overview' ? 'text-blue-600 border-blue-600' : 'text-gray-400 border-transparent hover:text-gray-600'">Overview</button>
                <button @click="tab = 'comments'" class="pb-2.5 text-sm font-bold border-b-2 transition-colors flex items-center gap-1.5" :class="tab === 'comments' ? 'text-blue-600 border-blue-600' : 'text-gray-400 border-transparent hover:text-gray-600'">
                    Comments <span x-show="task?.all_comments?.length" class="px-1.5 py-0.5 rounded-full bg-gray-100 text-[9px] text-gray-600" x-text="task?.all_comments?.length"></span>
                </button>
                <button @click="tab = 'files'" class="pb-2.5 text-sm font-bold border-b-2 transition-colors flex items-center gap-1.5" :class="tab === 'files' ? 'text-blue-600 border-blue-600' : 'text-gray-400 border-transparent hover:text-gray-600'">
                    Files <span x-show="task?.attachments?.length" class="px-1.5 py-0.5 rounded-full bg-gray-100 text-[9px] text-gray-600" x-text="task?.attachments?.length"></span>
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" class="flex-1 flex items-center justify-center">
            <div class="w-8 h-8 border-2 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>

        {{-- ── 2. SCROLLABLE BODY ── --}}
        <div x-show="task && !loading" class="flex-1 overflow-y-auto bg-gray-50/30 p-6">
            
            {{-- OVERVIEW TAB --}}
            <div x-show="tab === 'overview'" class="space-y-6">
                <div x-show="task?.description" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Description</p>
                    <div class="text-sm text-gray-700 leading-relaxed" x-html="renderMd(task?.description || '')"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div x-show="task?.project" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Project</p>
                        <p class="text-sm font-bold text-gray-800" x-text="task?.project"></p>
                    </div>
                    <div x-show="task?.category" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Category</p>
                        <p class="text-sm font-bold text-gray-800" x-text="task?.category"></p>
                    </div>
                </div>
            </div>

            {{-- COMMENTS TAB (Using Reusable Partial) --}}
            <div x-show="tab === 'comments'" class="h-full pt-4">
                {{-- taskId is an Alpine expression, evaluated inside the partial
                     against the drawer's selected task. commentBase points at
                     the employee routes — the admin ones are behind
                     permission:tasks.* and 403 for staff. --}}
                @include('admin.hrm.tasks.partials._comments', [
                    'taskId' => 'task?.id',
                    'commentBase' => '/admin/hrm/my-tasks',
                ])
            </div>

            {{-- FILES TAB --}}
            <div x-show="tab === 'files'" class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors"
                    @click="$refs.fileInput.click()">
                    <i data-lucide="upload-cloud" class="w-8 h-8 mx-auto text-blue-500 mb-2"></i>
                    <p class="text-sm font-bold text-gray-900">Click to upload a file</p>
                    <p class="text-xs text-gray-400 mt-1">Max 10MB per file</p>
                    <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect($event)">
                </div>

                <div x-show="uploading" class="flex items-center gap-3 bg-blue-50 text-blue-700 p-3 rounded-lg text-sm font-bold">
                    <i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Uploading...
                </div>

                <div class="space-y-2">
                    <template x-for="att in task?.attachments ?? []" :key="att.id">
                        <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="file" class="w-4 h-4 text-gray-400"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate" x-text="att.file_name"></p>
                                    <p class="text-[10px] text-gray-400" x-text="timeAgo(att.created_at)"></p>
                                </div>
                            </div>
                            <a :href="`/admin/hrm/my-tasks/attachments/${att.id}/download`" class="p-2 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-lg transition-colors flex-shrink-0">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── 3. STICKY FOOTER (ACTIONS) ── --}}
        <div x-show="task && !loading" class="flex-shrink-0 bg-white border-t border-gray-200 p-5 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] z-10">
            
            {{-- Overview Footer (Sliders) --}}
            <div x-show="tab === 'overview'" class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-bold text-gray-500">My Progress</label>
                        <span class="text-sm font-black text-emerald-600" x-text="`${progressVal}%`"></span>
                    </div>
                    <input type="range" x-model="progressVal" min="0" max="100" step="5" class="w-full accent-emerald-500">
                </div>
                
                <div x-show="allowedTransitions?.length > 0" class="flex gap-3">
                    <select x-model="newStatus" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold text-gray-700 outline-none focus:border-blue-500">
                        <option value="">Keep current status</option>
                        <template x-for="s in allowedTransitions" :key="s">
                            <option :value="s" x-text="statusLabels[s]"></option>
                        </template>
                    </select>
                    <button @click="saveProgress()" :disabled="saving" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl px-4 py-2.5 transition-colors disabled:opacity-50">
                        <span x-text="saving ? 'Saving...' : 'Update Task'"></span>
                    </button>
                </div>
                <div x-show="allowedTransitions?.length === 0">
                    <button @click="saveProgress()" :disabled="saving" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl px-4 py-2.5 transition-colors disabled:opacity-50">
                        <span x-text="saving ? 'Saving...' : 'Update Progress'"></span>
                    </button>
                </div>
            </div>

            {{-- Comments Footer removed (Handled inside the reusable partial) --}}
            
            {{-- Files Footer --}}
            <div x-show="tab === 'files'" class="text-center">
                <p class="text-xs text-gray-400 font-medium">Use the upload box above to add files.</p>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
const STATUS_LABELS = @json(\App\Models\Hrm\HrmTask::STATUS_LABELS);
const STATUS_COLORS = @json(\App\Models\Hrm\HrmTask::STATUS_COLORS);
const PRIORITY_COLORS = @json(\App\Models\Hrm\HrmTask::PRIORITY_COLORS);

function myTasks() {
    return {
        view: 'board', // Defaulted to board since it's beautiful now!
        filterStatus: '',
        filterPriority: '',

        init() {
            if (window.lucide) lucide.createIcons();
        },

        applyFilters() {
            const elements = document.querySelectorAll('.task-row, .task-card');
            elements.forEach(el => {
                const s = el.dataset.status;
                const p = el.dataset.priority;
                const matchS = !this.filterStatus   || s === this.filterStatus;
                const matchP = !this.filterPriority || p === this.filterPriority;
                el.style.display = (matchS && matchP) ? '' : 'none';
            });
        },

        clearFilters() {
            this.filterStatus = '';
            this.filterPriority = '';
            this.applyFilters();
        },

        openPanel(id) {
            window.dispatchEvent(new CustomEvent('open-task-panel', { detail: { id } }));
        }
    };
}

function taskPanel() {
    return {
        open: false,
        loading: false,
        task: null,
        tab: 'overview',
        allowedTransitions: [],
        progressVal: 0,
        newStatus: '',
        saving: false,
        newComment: '',
        postingComment: false,
        uploading: false,
        statusLabels: STATUS_LABELS,

        get statusColor() { return STATUS_COLORS[this.task?.status] ?? null; },
        get statusLabel() { return STATUS_LABELS[this.task?.status] ?? this.task?.status; },
        get priorityColor() { return PRIORITY_COLORS[this.task?.priority] ?? null; },

        init() {
            window.addEventListener('open-task-panel', (e) => this.openPanel(e.detail.id));
        },

        async openPanel(id) {
            this.open = true;
            this.loading = true;
            this.task = null;
            this.tab = 'overview';
            
            try {
                const res = await fetch(`/admin/hrm/my-tasks/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                if (data.success) {
                    this.task = data.data;
                    this.allowedTransitions = data.allowed_transitions ?? [];
                    this.progressVal = this.task.progress_percent ?? 0;
                    this.newStatus = '';
                }
            } catch(e) { }

            this.loading = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        close() {
            this.open = false;
            // Delay nullifying task so the slide-out animation stays smooth
            setTimeout(() => { this.task = null; }, 300);
        },

        async saveProgress() {
            if (!this.task) return;
            this.saving = true;
            try {
                const body = { progress_percent: parseInt(this.progressVal) };
                if (this.newStatus) body.status = this.newStatus;

                const res = await fetch(`/admin/hrm/my-tasks/${this.task.id}/progress`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.success) {
                    BizAlert.toast('Task updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 500); // Quick reload ensures board arrays update perfectly
                } else {
                    BizAlert.toast(data.message, 'error');
                }
            } catch(e) { BizAlert.toast('Network error.', 'error'); }
            this.saving = false;
        },

        async postComment() {
            if (!this.newComment.trim() || !this.task) return;
            this.postingComment = true;
            try {
                const res = await fetch(`/admin/hrm/my-tasks/${this.task.id}/comments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ body: this.newComment }),
                });
                const data = await res.json();
                if (data.success) {
                    if (!this.task.all_comments) this.task.all_comments = [];
                    this.task.all_comments.unshift(data.data); // Add to top
                    this.newComment = '';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            } catch(e) {}
            this.postingComment = false;
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) this.uploadFile(file);
            e.target.value = '';
        },

        async uploadFile(file) {
            if (!this.task) return;
            if (file.size > 10 * 1024 * 1024) { BizAlert.toast('File must be under 10MB.', 'error'); return; }

            this.uploading = true;
            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(`/admin/hrm/my-tasks/${this.task.id}/attachments`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    if (!this.task.attachments) this.task.attachments = [];
                    this.task.attachments.unshift(data.data);
                    BizAlert.toast('File uploaded.', 'success');
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    BizAlert.toast(data.message || 'Upload failed.', 'error');
                }
            } catch(e) { BizAlert.toast('Network error.', 'error'); }
            this.uploading = false;
        },

        /* ── Markdown renderer (bold, italic, bullet lists) ── */
        inlineToHtml(t) {
            t = t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            t = t.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
            t = t.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');
            return t;
        },

        renderMd(text) {
            if (!text || !text.trim()) return '';
            const lines = text.split('\n');
            let html = '';
            let inList = false;
            lines.forEach(line => {
                if (/^- /.test(line)) {
                    if (!inList) { html += '<ul style="list-style:disc;padding-left:1.1rem;margin:4px 0">'; inList = true; }
                    html += `<li style="margin:2px 0">${this.inlineToHtml(line.slice(2))}</li>`;
                } else {
                    if (inList) { html += '</ul>'; inList = false; }
                    if (line.trim() === '') {
                        html += '<div style="height:6px"></div>';
                    } else {
                        html += `<p style="margin:2px 0">${this.inlineToHtml(line)}</p>`;
                    }
                }
            });
            if (inList) html += '</ul>';
            return html;
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        timeAgo(d) {
            if (!d) return '';
            const diff = Date.now() - new Date(d).getTime();
            const mins = Math.floor(diff / 60000);
            if (mins < 1) return 'just now';
            if (mins < 60) return `${mins}m ago`;
            const hrs = Math.floor(mins / 60);
            if (hrs < 24) return `${hrs}h ago`;
            return `${Math.floor(hrs / 24)}d ago`;
        },
    };
}
</script>
@endpush