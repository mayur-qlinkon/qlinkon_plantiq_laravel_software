@extends('layouts.admin')

@section('title', 'CRM Dashboard')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">CRM Dashboard</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Overview of your pipeline — {{ now()->format('d M Y') }}</p> --}}
    </div>
@endsection


@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .custom-scroll::-webkit-scrollbar { width: 4px; }

    .stat-card {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 14px;
        padding: 16px 18px;
        transition: box-shadow 150ms, border-color 150ms;
        text-decoration: none;
        display: block;
    }

    .stat-card:hover {
        border-color: #e2e8f0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }

    .stat-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-bottom: 10px;
    }

    .widget {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 16px;
        overflow: hidden;
    }

    .widget-title {
        font-size: 11px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        padding: 14px 18px;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .activity-item {
        display: flex;
        gap: 12px;
        padding: 10px 18px;
        border-bottom: 1px solid #f8fafc;
        transition: background 100ms;
    }

    .activity-item:hover { background: #fafbfc; }
    .activity-item:last-child { border-bottom: none; }

    .activity-dot {
        width: 26px; height: 26px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .task-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 18px;
        border-bottom: 1px solid #f8fafc;
        transition: background 100ms;
    }

    .task-row:hover { background: #fafbfc; }
    .task-row:last-child { border-bottom: none; }

    .lead-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 18px;
        border-bottom: 1px solid #f8fafc;
        text-decoration: none;
        transition: background 100ms;
    }

    .lead-row:hover { background: #fafbfc; }
    .lead-row:last-child { border-bottom: none; }

    .avatar-sm {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }

    .stage-bar {
        height: 6px;
        border-radius: 3px;
        background: #f1f5f9;
        overflow: hidden;
        flex: 1;
    }

    .stage-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 500ms ease;
    }
</style>
@endpush

@section('content')

@php
    $avatarColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4'];
    $typeColors = [
        'note'           => ['bg' => '#f8fafc', 'text' => '#64748b'],
        'call'           => ['bg' => '#eff6ff', 'text' => '#2563eb'],
        'whatsapp'       => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
        'email'          => ['bg' => '#faf5ff', 'text' => '#7c3aed'],
        'meeting'        => ['bg' => '#fff7ed', 'text' => '#c2410c'],
        'stage_change'   => ['bg' => '#fefce8', 'text' => '#a16207'],
        'lead_created'   => ['bg' => '#f0fdf4', 'text' => '#15803d'],
        'converted'      => ['bg' => '#f0fdf4', 'text' => '#15803d'],
        'task_completed' => ['bg' => '#eff6ff', 'text' => '#2563eb'],
        'score_changed'  => ['bg' => '#faf5ff', 'text' => '#7c3aed'],
    ];
@endphp

<div class="pb-10">
    
    {{-- ═══════════════════════════════════════════
             AI CHATBOT POPUP
        ═══════════════════════════════════════════ --}}        
            <x-modals.ai-chatbot-popup />

    {{-- ════════ STATS BAR ════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">

        <a href="{{ route('admin.crm.leads.index') }}" class="stat-card">
            <div class="stat-icon bg-blue-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <p class="text-xl sm:text-2xl font-black text-gray-900 truncate">{{ number_format($stats['total']) }}</p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Total Leads</p>
        </a>

        <a href="{{ route('admin.crm.leads.index', ['converted' => 0]) }}" class="stat-card">
            <div class="stat-icon bg-purple-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <p class="text-2xl font-black text-gray-900">{{ number_format($stats['total'] - $stats['converted']) }}</p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Active</p>
        </a>

        <a href="{{ route('admin.crm.leads.index', ['priority' => 'hot']) }}" class="stat-card">
            <div class="stat-icon bg-red-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <p class="text-2xl font-black text-red-600">{{ number_format($stats['hot']) }}</p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Hot 🔥</p>
        </a>

        <a href="{{ route('admin.crm.leads.index', ['overdue' => 1]) }}" class="stat-card">
            <div class="stat-icon bg-orange-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <p class="text-2xl font-black {{ $stats['overdue'] > 0 ? 'text-orange-600' : 'text-gray-900' }}">
                {{ number_format($stats['overdue']) }}
            </p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Overdue</p>
        </a>

        <a href="{{ route('admin.crm.leads.index', ['converted' => 1]) }}" class="stat-card">
            <div class="stat-icon bg-green-50">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <p class="text-2xl font-black text-green-600">{{ number_format($stats['converted']) }}</p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Converted</p>
        </a>

        <div class="stat-card">
            <div class="stat-icon bg-amber-50">
                <i data-lucide="indian-rupee" class="w-5 h-5"></i>
            </div>
            <p class="text-lg font-black text-gray-900">
                ₹{{ number_format(($stats['total_value'] ?? 0) / 1000, 1) }}k
            </p>
            <p class="text-[11px] font-bold text-gray-400 mt-0.5">Pipeline Value</p>
        </div>

    </div>

    {{-- ════════ MY TASKS STRIP ════════ --}}
    @if($myTasks->isNotEmpty())
        <div class="widget mb-5">
            <div class="widget-title">
                <span>My Tasks Today & Overdue</span>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 normal-case tracking-normal">
                    {{ $myTasks->count() }}
                </span>
            </div>
            <div class="overflow-x-auto">
                @foreach($myTasks as $task)
                    <div class="task-row">
                        <div class="w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center
                            {{ $task->is_overdue ? 'border-red-400' : 'border-gray-300' }}">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-bold text-gray-800 truncate">{{ $task->title }}</p>
                            <p class="text-[11px] text-gray-400">
                                {{ $task->lead?->name ?? 'Unknown Lead' }} ·
                                <span class="{{ $task->is_overdue ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                                    {{ $task->is_overdue ? '⚠ Overdue — ' : '' }}{{ $task->due_at->format('d M, h:i A') }}
                                </span>
                            </p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                            style="background: {{ $task->status_color }}15; color: {{ $task->status_color }}">
                            {{ $task->type_label }}
                        </span>
                        @if($task->lead)
                            <a href="{{ route('admin.crm.leads.show', $task->lead->id) }}"
                                class="text-[11px] font-bold px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors flex-shrink-0">
                                View Lead
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ════════ MAIN GRID ════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 xl:grid-cols-3 gap-5">

        {{-- ══ LEFT — Pipeline Funnel + Source Chart ══ --}}
        <div class="lg:col-span-3 xl:col-span-2 space-y-5">

            {{-- Pipeline funnel ── --}}
            <div class="widget">
                <div class="widget-title">
                    <span>Pipeline Funnel</span>
                    @if($pipelines->count() > 1)
                        <select id="pipeline-select"
                            onchange="updateFunnel(this.value)"
                            class="text-[11px] font-bold border border-gray-200 rounded-lg px-2 py-1 outline-none bg-white text-gray-600 normal-case tracking-normal">
                            @foreach($pipelines as $pl)
                                <option value="{{ $pl->id }}" {{ $pl->is_default ? 'selected' : '' }}>
                                    {{ $pl->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                @php
                    $defaultPipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
                    $funnelData = collect($stageStats[$defaultPipeline?->id] ?? []);
                    $maxCount   = max(1, $funnelData->max('count') ?? 1);
                @endphp

                <div class="p-5">
                    @if($funnelData->isEmpty())
                        <p class="text-center text-gray-400 text-sm py-8">No stages configured for this pipeline.</p>
                    @else
                        <div class="space-y-3" id="funnel-bars">
                            @foreach($funnelData as $item)
                                <div class="flex items-center gap-3">
                                    <div class="w-24 flex-shrink-0">
                                        <p class="text-[12px] font-bold text-gray-700 truncate">{{ $item['stage'] }}</p>
                                    </div>
                                    <div class="stage-bar flex-1">
                                        <div class="stage-bar-fill"
                                            style="width: {{ $maxCount > 0 ? round(($item['count'] / $maxCount) * 100) : 0 }}%;
                                                   background: {{ $item['color'] }}">
                                        </div>
                                    </div>
                                    <div class="w-20 flex-shrink-0 flex items-center justify-end gap-2">
                                        <span class="text-[13px] font-black text-gray-800">{{ $item['count'] }}</span>
                                        @if($item['count'] > 0 && $item['value'] > 0)
                                            <span class="text-[10px] text-gray-400 font-medium">
                                                ₹{{ number_format($item['value'] / 1000, 0) }}k
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Source breakdown ── --}}
            <div class="widget">
                <div class="widget-title">Lead Source Breakdown</div>
                <div class="p-5">
                    @if($sourceStats->isEmpty())
                        <p class="text-center text-gray-400 text-sm py-6">No source data yet.</p>
                    @else
                        @php $totalLeads = max(1, $sourceStats->sum('count')); @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-3">
                            @foreach($sourceStats as $src)
                                @php
                                    $pct    = round(($src['count'] / $totalLeads) * 100);
                                    $colors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#f97316'];
                                    $color  = $colors[$loop->index % count($colors)];
                                @endphp
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                        style="background: {{ $color }}18">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-bold text-gray-700 truncate">
                                            {{ $src['source'] ?? 'Unknown' }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="stage-bar flex-1" style="height: 4px">
                                                <div class="stage-bar-fill"
                                                    style="width: {{ $pct }}%; background: {{ $color }}">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold text-gray-500 flex-shrink-0">
                                                {{ $pct }}%
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-[13px] font-black text-gray-800 flex-shrink-0">
                                        {{ $src['count'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Priority distribution ── --}}
            <div class="widget">
                <div class="widget-title">Priority Distribution</div>
                <div class="p-5">
                    @php
                        $priorityData = [
                            'hot'    => ['label' => '🔥 Hot',   'color' => '#ef4444', 'count' => $priorityStats['hot']    ?? 0],
                            'high'   => ['label' => 'High',     'color' => '#f97316', 'count' => $priorityStats['high']   ?? 0],
                            'medium' => ['label' => 'Medium',   'color' => '#eab308', 'count' => $priorityStats['medium'] ?? 0],
                            'low'    => ['label' => 'Low',      'color' => '#9ca3af', 'count' => $priorityStats['low']    ?? 0],
                        ];
                        $totalPriority = max(1, array_sum(array_column($priorityData, 'count')));
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-3">
                        @foreach($priorityData as $p)
                            <div class="text-center p-3 rounded-xl"
                                style="background: {{ $p['color'] }}10">
                                <p class="text-2xl font-black mb-1"
                                    style="color: {{ $p['color'] }}">{{ $p['count'] }}</p>
                                <p class="text-[11px] font-bold text-gray-500">{{ $p['label'] }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $totalPriority > 0 ? round(($p['count'] / $totalPriority) * 100) : 0 }}%
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ RIGHT — Hot Leads + Activity Feed ══ --}}
        <div class="lg:col-span-2 xl:col-span-1 space-y-5">

            {{-- ── Followup Calendar ── --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[13px] font-black text-gray-800">📅 Follow-up Calendar</h3>
                    <span class="text-[11px] text-gray-400 font-medium" id="cal-month-label"></span>
                </div>

                {{-- Nav --}}
                <div class="flex items-center justify-between mb-3">
                    <button onclick="calNav(-1)"
                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="text-[12px] font-bold text-gray-700" id="cal-title"></span>
                    <button onclick="calNav(1)"
                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                {{-- Day headers --}}
                <div class="grid grid-cols-7 mb-1">
                    @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d)
                        <div class="text-center text-[10px] font-black text-gray-400 py-1">{{ $d }}</div>
                    @endforeach
                </div>

                {{-- Calendar grid --}}
                <div class="grid grid-cols-7 gap-y-1" id="cal-grid"></div>

                {{-- Tooltip / lead list --}}
                <div id="cal-leads" class="mt-3 hidden">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2" id="cal-leads-date"></p>
                    <div id="cal-leads-list" class="space-y-1.5"></div>
                </div>
            </div>

            {{-- Hot & Overdue leads ── --}}
            <div class="widget">
                <div class="widget-title">
                    <span>Hot Leads</span>
                    <a href="{{ route('admin.crm.leads.index', ['priority' => 'hot']) }}"
                        class="text-[10px] font-bold normal-case tracking-normal"
                        style="color: var(--brand-600)">View all →</a>
                </div>
                @if($hotLeads->isEmpty())
                    <div class="px-5 py-8 text-center text-[13px] text-gray-400 font-medium">
                        No hot leads right now.
                    </div>
                @else
                    @foreach($hotLeads as $lead)
                        @php $bg = $avatarColors[crc32($lead->name) % count($avatarColors)]; @endphp
                        <a href="{{ route('admin.crm.leads.show', $lead->id) }}" class="lead-row">
                            <div class="avatar-sm" style="background: {{ $bg }}">
                                {{ strtoupper(substr($lead->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $lead->name }}</p>
                                <p class="text-[11px] text-gray-400 truncate">
                                    {{ $lead->stage?->name }} · Score {{ $lead->score }}
                                </p>
                            </div>
                            @if($lead->phone)
                                <a href="{{ $lead->whatsapp_url }}" target="_blank"
                                    @click.prevent.stop
                                    class="w-6 h-6 flex items-center justify-center rounded-full bg-green-50 text-green-600 flex-shrink-0 hover:bg-green-100 transition-colors">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
                                </a>
                            @endif
                        </a>
                    @endforeach
                @endif
            </div>

            {{-- Overdue followups ── --}}
            @if($overdueLeads->isNotEmpty())
                <div class="widget">
                    <div class="widget-title">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            Overdue Follow-ups
                        </span>
                        <a href="{{ route('admin.crm.leads.index', ['overdue' => 1]) }}"
                            class="text-[10px] font-bold normal-case tracking-normal text-orange-500">View all →</a>
                    </div>
                    @foreach($overdueLeads as $lead)
                        @php $bg = $avatarColors[crc32($lead->name) % count($avatarColors)]; @endphp
                        <a href="{{ route('admin.crm.leads.show', $lead->id) }}" class="lead-row">
                            <div class="avatar-sm" style="background: {{ $bg }}">
                                {{ strtoupper(substr($lead->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $lead->name }}</p>
                                <p class="text-[11px] text-red-500 font-semibold">
                                    ⚠ {{ $lead->next_followup_at?->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Recent activity feed ── --}}
            <div class="widget">
                <div class="widget-title">Recent Activity</div>
                <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                    @if($recentActivities->isEmpty())
                        <div class="px-5 py-8 text-center text-[13px] text-gray-400 font-medium">
                            No activity logged yet.
                        </div>
                    @else
                        @foreach($recentActivities as $act)
                            @php $tc = $typeColors[$act->type] ?? ['bg' => '#f8fafc', 'text' => '#64748b']; @endphp
                            <div class="activity-item">
                                <div class="activity-dot"
                                    style="background: {{ $tc['bg'] }}; color: {{ $tc['text'] }}">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-bold" style="color: {{ $tc['text'] }}">
                                            {{ $act->type_label }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 flex-shrink-0">
                                            {{ $act->created_at->diffForHumans(short: true) }}
                                        </span>
                                    </div>
                                    <p class="text-[12px] text-gray-700 leading-snug truncate">
                                        {{ $act->description }}
                                    </p>
                                    @if($act->lead)
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $act->lead->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ════════ ASSIGNEE PERFORMANCE ════════ --}}
    @if($assigneeStats->isNotEmpty())
        <div class="widget mt-5">
            <div class="widget-title">Team Performance</div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-50">
                            <th class="px-5 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Assigned</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Converted</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Conv. Rate</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Hot</th>
                            <th class="px-3 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-wider">Overdue</th>
                            <th class="px-3 py-3 text-right text-[10px] font-black text-gray-400 uppercase tracking-wider">Pipeline Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assigneeStats as $stat)
                            @php
                                $rate = $stat['assigned'] > 0
                                    ? round(($stat['converted'] / $stat['assigned']) * 100)
                                    : 0;
                            @endphp
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors last:border-none">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold text-white flex-shrink-0"
                                            style="background: var(--brand-600)">
                                            {{ strtoupper(substr($stat['name'], 0, 1)) }}
                                        </div>
                                        <span class="text-[13px] font-bold text-gray-800">{{ $stat['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-center text-[13px] font-bold text-gray-700">
                                    {{ $stat['assigned'] }}
                                </td>
                                <td class="px-3 py-3 text-center text-[13px] font-bold text-green-600">
                                    {{ $stat['converted'] }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                        {{ $rate >= 50 ? 'bg-green-50 text-green-700' : ($rate >= 25 ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                                        {{ $rate }}%
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center text-[13px] font-bold text-red-600">
                                    {{ $stat['hot'] }}
                                </td>
                                <td class="px-3 py-3 text-center text-[13px] font-bold
                                    {{ $stat['overdue'] > 0 ? 'text-orange-600' : 'text-gray-300' }}">
                                    {{ $stat['overdue'] > 0 ? $stat['overdue'] : '—' }}
                                </td>
                                <td class="px-3 py-3 text-right text-[13px] font-bold text-gray-700">
                                    {{ $stat['value'] > 0 ? '₹' . number_format($stat['value'] / 1000, 1) . 'k' : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
// ── Funnel data for multi-pipeline switching ──
const allFunnelData = @json($stageStats);

function updateFunnel(pipelineId) {
    const data    = allFunnelData[pipelineId] || [];
    const maxCount = Math.max(1, ...data.map(d => d.count));
    const container = document.getElementById('funnel-bars');
    if (!container) return;

    container.innerHTML = data.map(item => `
        <div class="flex items-center gap-3">
            <div class="w-24 flex-shrink-0">
                <p class="text-[12px] font-bold text-gray-700 truncate">${item.stage}</p>
            </div>
            <div class="stage-bar flex-1">
                <div class="stage-bar-fill"
                    style="width: ${Math.round((item.count / maxCount) * 100)}%;
                           background: ${item.color}">
                </div>
            </div>
            <div class="w-20 flex-shrink-0 flex items-center justify-end gap-2">
                <span class="text-[13px] font-black text-gray-800">${item.count}</span>
                ${item.value > 0 ? `<span class="text-[10px] text-gray-400 font-medium">₹${(item.value/1000).toFixed(0)}k</span>` : ''}
            </div>
        </div>
    `).join('');
}
</script>

<script>
(function () {
    // ── Followup data from PHP ──
    const followups = @json($followupDates);

    const priorityColors = {
        hot:    '#ef4444',
        high:   '#f97316',
        medium: '#eab308',
        low:    '#3b82f6',
    };

    let current = new Date();
    current.setDate(1);

    function render() {
        const year  = current.getFullYear();
        const month = current.getMonth(); // 0-indexed

        const monthNames = ['January','February','March','April','May','June',
                            'July','August','September','October','November','December'];

        document.getElementById('cal-title').textContent = `${monthNames[month]} ${year}`;

        const grid    = document.getElementById('cal-grid');
        const today   = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

        grid.innerHTML = '';

        const firstDay  = new Date(year, month, 1).getDay(); // 0=Sun
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            grid.insertAdjacentHTML('beforeend', '<div></div>');
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const leads   = followups[dateStr] || [];
            const isToday = dateStr === todayStr;
            const hasDot  = leads.length > 0;

            // Determine dot color by highest priority
            const priorityOrder = ['hot','high','medium','low'];
            let dotColor = '#3b82f6';
            for (const p of priorityOrder) {
                if (leads.some(l => l.priority === p)) { dotColor = priorityColors[p]; break; }
            }

            grid.insertAdjacentHTML('beforeend', `
                <div class="flex flex-col items-center cursor-pointer group" onclick="calDayClick('${dateStr}')">
                    <span class="w-7 h-7 flex items-center justify-center rounded-full text-[11px] font-bold
                        transition-colors
                        ${isToday ? 'bg-brand-500 text-white' : 'text-gray-700 group-hover:bg-gray-100'}">
                        ${d}
                    </span>
                    ${hasDot ? `<span class="w-1.5 h-1.5 rounded-full mt-0.5" style="background:${dotColor}"></span>` : '<span class="w-1.5 h-1.5 mt-0.5"></span>'}
                </div>
            `);
        }

        // Hide lead panel when month changes
        document.getElementById('cal-leads').classList.add('hidden');
    }

    window.calNav = function(dir) {
        current.setMonth(current.getMonth() + dir);
        render();
    };

    window.calDayClick = function(dateStr) {
        const leads = followups[dateStr] || [];
        const panel = document.getElementById('cal-leads');
        const list  = document.getElementById('cal-leads-list');
        const label = document.getElementById('cal-leads-date');

        if (!leads.length) { panel.classList.add('hidden'); return; }

        const d = new Date(dateStr + 'T00:00:00');
        label.textContent = d.toLocaleDateString('en-IN', { weekday:'long', day:'numeric', month:'long' });

        list.innerHTML = leads.map(l => `
            <a href="/admin/crm/leads/${l.id}"
               class="flex items-center gap-2 px-2.5 py-2 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50 transition-colors group">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:${priorityColors[l.priority] || '#94a3b8'}"></span>
                <span class="text-[11px] font-semibold text-gray-700 group-hover:text-gray-900 truncate">${l.name}</span>
            </a>
        `).join('');

        panel.classList.remove('hidden');
    };

    render();
})();
</script>
@endpush