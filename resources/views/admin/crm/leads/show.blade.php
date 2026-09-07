@extends('layouts.admin')

@section('title', $lead->name . ' — CRM Lead')

@section('header-title')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.crm.leads.index') }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round">
                <path d="M19 12H5M12 5l-7 7 7 7" />
            </svg>
        </a>
        <div>
            <h1 class="text-[17px] font-bold text-gray-800 leading-none">{{ $lead->name }}</h1>
            <p class="text-xs text-gray-400 font-medium mt-0.5">Lead Detail</p>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .detail-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            overflow: hidden;
        }

        .card-title {
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 14px 18px;
            border-bottom: 1px solid #f8fafc;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 18px;
            border-bottom: 1px solid #f8fafc;
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-key {
            color: #94a3b8;
            font-weight: 500;
            flex-shrink: 0;
            font-size: 12px;
        }

        .info-val {
            color: #1e293b;
            font-weight: 600;
            text-align: right;
            word-break: break-word;
            max-width: 65%;
        }

        /* Stage pipeline ── */
        .stage-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            flex: 1;
            min-width: 0;
        }

        .stage-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            transition: all 150ms ease;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .stage-circle.current {
            border-color: var(--stage-color);
            background: var(--stage-color);
            box-shadow: 0 0 0 4px var(--stage-color-ring);
        }

        .stage-circle.past {
            border-color: var(--stage-color);
            background: var(--stage-color);
            opacity: 0.5;
        }

        .stage-circle.future {
            border-color: #e2e8f0;
            background: #f8fafc;
        }

        .stage-circle:hover {
            transform: scale(1.1);
        }

        .stage-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-align: center;
            line-height: 1.3;
        }

        .stage-label.current {
            color: #1e293b;
        }

        .stage-connector {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin-top: -18px;
            position: relative;
            z-index: 0;
        }

        .stage-connector.done {
            background: var(--connector-color);
            opacity: 0.5;
        }

        /* Timeline ── */
        .timeline-item {
            display: flex;
            gap: 14px;
            padding: 10px 18px;
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
        }

        .timeline-item:hover {
            background: #fafbfc;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Tasks ── */
        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid #f8fafc;
            transition: background 100ms;
        }

        .task-item:hover {
            background: #fafbfc;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        /* Field input ── */
        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms;
        }

        .field-input:focus {
            border-color: var(--brand-600);
        }

        .field-select {
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms;
        }

        .field-select:focus {
            border-color: var(--brand-600);
        }

        /* Priority ── */
        .prio-btn {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 120ms;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1.5px solid #e5e7eb;
            color: #374151;
            background: #fff;
            cursor: pointer;
            transition: all 120ms;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .action-btn.primary {
            background: var(--brand-600);
            border-color: var(--brand-600);
            color: #fff;
        }

        .action-btn.primary:hover {
            opacity: 0.9;
        }

        .action-btn.danger {
            color: #dc2626;
            border-color: #fecaca;
        }

        .action-btn.danger:hover {
            background: #fef2f2;
        }

        .action-btn.success {
            color: #15803d;
            border-color: #bbf7d0;
        }

        .action-btn.success:hover {
            background: #f0fdf4;
        }
    </style>
@endpush

@section('content')

    @php
        $priorityColors = [
            'hot' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
            'high' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa'],
            'medium' => ['bg' => '#fefce8', 'text' => '#a16207', 'border' => '#fef08a'],
            'low' => ['bg' => '#f9fafb', 'text' => '#6b7280', 'border' => '#e5e7eb'],
        ];
        $pColor = $priorityColors[$lead->priority] ?? $priorityColors['medium'];

        // Avatar
        $avatarColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
        $avatarBg = $avatarColors[crc32($lead->name) % count($avatarColors)];
    @endphp

    {{-- Arguments encoded by the js directive. In an attribute the browser decodes
         entities before Alpine parses the expression, so a stage named Won't Buy
         arrived as an unescaped apostrophe and broke the whole call — leaving the
         page with no Alpine scope at all. --}}
    <div class="pb-10" x-data="leadDetail({{ $lead->id }}, @js($lead->priority), {{ (int) $lead->score }}, @js($lead->stage?->name), @js($lead->stage?->color ?? '#6b7280'))">

        {{-- ════ TOP ACTION BAR ════ --}}
        <div class="flex items-center justify-between flex-wrap gap-3 mb-5">

            <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">

                {{-- WhatsApp ── --}}
                @if ($lead->phone)
                    <a href="{{ $lead->whatsapp_url }}" target="_blank"
                        class="action-btn success flex-1 sm:flex-none justify-center">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347" />
                        </svg>
                        WhatsApp
                    </a>
                @endif

                {{-- Edit ── --}}
                @if (has_permission('crm_leads.update'))
                    <a href="{{ route('admin.crm.leads.edit', $lead->id) }}"
                        class="action-btn flex-1 sm:flex-none justify-center">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                        Edit Lead
                    </a>
                @endif

                {{-- Convert ── --}}
                @if (!$lead->is_converted)
                    @if (has_permission('crm_leads.convert'))
                        <button @click="convertLead()" class="action-btn success w-full sm:w-auto justify-center"
                            :disabled="converting">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <span x-text="converting ? 'Converting...' : 'Convert to Client'"></span>
                        </button>
                    @endif
                @else
                    @if (has_permission('clients.view'))
                        <a href="{{ route('admin.clients.index', ['search' => $lead->name]) }}" class="action-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                            View Client
                        </a>
                    @endif
                @endif

                {{-- Delete ── --}}
                @if (has_permission('crm_leads.delete'))
                    <button @click="deleteLead()" class="action-btn danger">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                        Delete
                    </button>
                @endif
            </div>

            {{-- Converted badge ── --}}
            @if ($lead->is_converted)
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-50 text-green-700 text-[12px] font-bold border border-green-100">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Converted {{ $lead->converted_at?->format('d M Y') }}
                </span>
            @endif
        </div>

        {{-- ════ FEEDBACK ════ --}}
        <template x-if="pageSuccess">
            <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"
                    stroke-linecap="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <p class="text-sm font-semibold text-green-800" x-text="pageSuccess"></p>
            </div>
        </template>

        <template x-if="pageError">
            <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"
                    stroke-linecap="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <p class="text-sm font-semibold text-red-700" x-text="pageError"></p>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-5 xl:grid-cols-3 gap-5">

            {{-- ════════════════════════════════════
             LEFT COLUMN — Profile + Stage
        ════════════════════════════════════ --}}
            <div class="lg:col-span-2 xl:col-span-1 space-y-4">

                {{-- Profile card ── --}}
                <div class="detail-card">
                    <div class="px-5 py-5 flex flex-col items-center text-center border-b border-gray-50">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-black text-white mb-3 shadow-sm"
                            style="background: {{ $avatarBg }}">
                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                        </div>
                        <h2 class="text-[16px] font-bold text-gray-900 mb-1">{{ $lead->name }}</h2>
                        @if ($lead->company_name)
                            <p class="text-[12px] text-gray-400 font-medium mb-1">{{ $lead->company_name }}</p>
                        @endif

                        {{-- Priority selector ── --}}
                        <div class="grid grid-cols-2 gap-1.5 mt-3 w-full">
                            @foreach (['low', 'medium', 'high', 'hot'] as $p)
                                @php $pc = $priorityColors[$p]; @endphp
                                <button @click="setPriority('{{ $p }}')" class="prio-btn"
                                    style="background: {{ $lead->priority === $p ? $pc['bg'] : '#fff' }};
                                       color: {{ $lead->priority === $p ? $pc['text'] : '#9ca3af' }};
                                       border-color: {{ $lead->priority === $p ? $pc['border'] : '#f1f5f9' }}"
                                    :style="currentPriority === '{{ $p }}'
                                        ?
                                        'background:{{ $pc['bg'] }};color:{{ $pc['text'] }};border-color:{{ $pc['border'] }}' :
                                        'background:#fff;color:#9ca3af;border-color:#f1f5f9'">
                                    {{ $p === 'hot' ? '🔥' : '' }} {{ ucfirst($p) }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Score ── --}}
                        <div class="mt-3 flex items-center gap-2">
                            <span class="text-[11px] text-gray-400 font-medium">Score:</span>
                            <span class="text-[13px] font-black" x-text="score"
                                :style="score >= 50 ? 'color:#ef4444' : (score >= 20 ? 'color:#f59e0b' : 'color:#6b7280')">
                                {{ $lead->score }}
                            </span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :style="score >= 50 ? 'background:#fef2f2;color:#dc2626' : (score >= 20 ?
                                    'background:#fefce8;color:#a16207' : 'background:#f9fafb;color:#6b7280')"
                                x-text="score >= 50 ? 'Hot' : (score >= 20 ? 'Warm' : 'Cold')">
                            </span>
                        </div>
                    </div>

                    {{-- Contact info ── --}}
                    @if ($lead->phone)
                        <div class="info-row">
                            <span class="info-key">Phone</span>
                            <a href="tel:{{ $lead->phone }}" class="info-val" style="color: var(--brand-600)">
                                {{ $lead->phone }}
                            </a>
                        </div>
                    @endif
                    @if ($lead->email)
                        <div class="info-row">
                            <span class="info-key">Email</span>
                            <a href="mailto:{{ $lead->email }}" class="info-val"
                                style="color: var(--brand-600); font-size: 11px">
                                {{ $lead->email }}
                            </a>
                        </div>
                    @endif
                    @if ($lead->source)
                        <div class="info-row">
                            <span class="info-key">Source</span>
                            <span class="info-val">{{ $lead->source->name }}</span>
                        </div>
                    @endif
                    @if ($lead->lead_value)
                        <div class="info-row">
                            <span class="info-key">Value</span>
                            <span class="info-val">₹{{ number_format($lead->lead_value, 2) }}</span>
                        </div>
                    @endif
                    @if ($lead->city || $lead->state)
                        <div class="info-row">
                            <span class="info-key">Location</span>
                            <span class="info-val">
                                {{ implode(', ', array_filter([$lead->city, $lead->state])) }}
                            </span>
                        </div>
                    @endif
                    @if ($lead->next_followup_at)
                        <div class="info-row">
                            <span class="info-key">Reminder</span>
                            <span class="info-val {{ $lead->is_overdue ? 'text-red-600' : '' }}">
                                {{ $lead->is_overdue ? '⚠ ' : '' }}{{ $lead->next_followup_at->format('d M Y') }}
                            </span>
                        </div>
                    @endif
                    @if ($lead->last_contacted_at)
                        <div class="info-row">
                            <span class="info-key">Last Contact</span>
                            <span class="info-val">{{ $lead->last_contacted_at->diffForHumans() }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-key">Added</span>
                        <span class="info-val">{{ $lead->created_at->format('d M Y') }}</span>
                    </div>
                    @if ($lead->assignees->isNotEmpty())
                        <div class="info-row">
                            <span class="info-key">Assigned To</span>
                            <span class="info-val">{{ $lead->assignees->first()->name }}</span>
                        </div>
                    @endif

                    {{-- Social links ── --}}
                    @if ($lead->instagram_id || $lead->facebook_id || $lead->website)
                        <div class="px-4 py-3 flex items-center gap-2 flex-wrap">
                            @if ($lead->instagram_id)
                                <a href="https://instagram.com/{{ $lead->instagram_id }}" target="_blank"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-pink-50 text-pink-600">
                                    Instagram
                                </a>
                            @endif
                            @if ($lead->website)
                                <a href="{{ $lead->website }}" target="_blank"
                                    class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600">
                                    Website
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Tags card ── --}}
                @if ($lead->tags->isNotEmpty())
                    <div class="detail-card">
                        <div class="card-title">Tags</div>
                        <div class="px-4 py-3 flex flex-wrap gap-2">
                            @foreach ($lead->tags as $tag)
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                    style="background: {{ $tag->color }}18; color: {{ $tag->color }}">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        style="background: {{ $tag->color }}"></span>
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Description ── --}}
                @if ($lead->description)
                    <div class="detail-card">
                        <div class="card-title">Notes</div>
                        <div class="px-4 py-3 text-[13px] text-gray-600 leading-relaxed">
                            {{ $lead->description }}
                        </div>
                    </div>
                @endif

            </div>

            {{-- ════════════════════════════════════
             RIGHT COLUMN — Stage + Activities + Tasks
        ════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- ── STAGE PIPELINE ── --}}
                @if ($lead->pipeline)
                    <div class="detail-card">
                        <div class="card-title">Pipeline — {{ $lead->pipeline->name }}</div>
                        <div class="px-5 py-5">

                            {{-- Stage flow ── --}}
                            @if (has_permission('crm_leads.change_stage'))
                                <div class="flex items-start">
                                    @php $stages = $lead->pipeline->stages; @endphp
                                    @foreach ($stages as $i => $stage)
                                        @php
                                            $stageIds = $stages->pluck('id')->toArray();
                                            $currentIdx = array_search($lead->crm_stage_id, $stageIds);
                                            $thisIdx = $i;
                                            $isCurrent = $stage->id === $lead->crm_stage_id;
                                            $isPast = $thisIdx < $currentIdx;
                                        @endphp

                                        <div class="stage-step"
                                            @click="moveToStage({{ $stage->id }}, @js($stage->name))">
                                            <div class="stage-circle {{ $isCurrent ? 'current' : ($isPast ? 'past' : 'future') }}"
                                                style="--stage-color: {{ $stage->color }};
                                               --stage-color-ring: {{ $stage->color }}22">
                                                @if ($isCurrent)
                                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                                        fill="#fff" stroke="#fff" stroke-width="0">
                                                        <circle cx="12" cy="12" r="5" />
                                                    </svg>
                                                @elseif($isPast)
                                                    <svg width="12" height="12" viewBox="0 0 24 24"
                                                        fill="none" stroke="#fff" stroke-width="3"
                                                        stroke-linecap="round">
                                                        <polyline points="20 6 9 17 4 12" />
                                                    </svg>
                                                @else
                                                    <svg width="10" height="10" viewBox="0 0 24 24"
                                                        fill="#e2e8f0" stroke="#e2e8f0" stroke-width="0">
                                                        <circle cx="12" cy="12" r="5" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <span class="stage-label {{ $isCurrent ? 'current' : '' }}"
                                                style="{{ $isCurrent ? 'color:' . $stage->color : '' }}">
                                                {{ Str::limit($stage->name, 10) }}
                                            </span>
                                        </div>

                                        @if (!$loop->last)
                                            <div class="stage-connector {{ $isPast ? 'done' : '' }} mt-4"
                                                style="--connector-color: {{ $stage->color }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Current stage label ── --}}
                            <div class="mt-4 flex items-center gap-2">
                                <span class="text-[11px] text-gray-400 font-medium">Current stage:</span>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold"
                                    :style="`background: ${currentStageColor}18; color: ${currentStageColor}`">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="`background: ${currentStageColor}`"></span>
                                    <span x-text="currentStageName">{{ $lead->stage?->name }}</span>
                                </span>
                                <span x-show="stageMoving"
                                    class="text-[11px] text-gray-400 font-medium animate-pulse">Moving...</span>
                            </div>

                        </div>
                    </div>
                @endif

                {{-- ── TASKS ── --}}
                @if (has_permission('crm_tasks.view'))
                    <div class="detail-card flex flex-col" x-data="tasksPanel(@js($lead->tasks->map(fn($t) => app(\App\Http\Controllers\Admin\Crm\CrmLeadController::class)->formatTaskPublic($t))), {{ $lead->id }})">
                        {{-- Header --}}
                        <div class="card-title flex items-center justify-between border-b border-gray-50 bg-white">
                            <div class="flex items-center gap-2">
                                <span>Tasks</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-brand-50 text-brand-600"
                                    x-text="tasks.filter(t => ['pending','in_progress'].includes(t.status)).length">
                                </span>
                            </div>
                            @if (has_permission('crm_tasks.create'))
                                <button @click="addOpen = !addOpen"
                                    class="text-[11px] font-bold px-3 py-1.5 rounded-lg text-white shadow-sm hover:shadow-md transition-all active:scale-95"
                                    style="background: var(--brand-600)">
                                    + Add Task
                                </button>
                            @endif
                        </div>

                        {{-- Modern Add Task Form --}}
                        <div x-show="addOpen" x-transition x-cloak class="p-4 border-b border-gray-100 bg-slate-50/80">
                            <template x-if="taskAddError">
                                <div
                                    class="mb-3 bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 flex items-center gap-2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                        stroke="#dc2626" stroke-width="2.5" stroke-linecap="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <p class="text-[12px] font-bold text-red-600" x-text="taskAddError"></p>
                                </div>
                            </template>

                            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-3.5">
                                {{-- Task Title --}}
                                <div>
                                    <label
                                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1 block">Task
                                        Title <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="taskForm.title" placeholder="What needs to be done?"
                                        class="field-input w-full font-semibold placeholder-gray-400"
                                        style="border-color: #e2e8f0;">
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label
                                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1 block">Description
                                        <span class="text-gray-300 font-medium">(Optional)</span></label>
                                    <textarea x-model="taskForm.description" rows="2" placeholder="Add extra details or instructions..."
                                        class="field-input w-full resize-none placeholder-gray-400" style="border-color: #e2e8f0;"></textarea>
                                </div>

                                {{-- Metadata Grid --}}
                                <div>
                                    <label
                                        class="text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1.5 block">Task
                                        Configurations</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-gray-400 pl-0.5">Task Type</span>
                                            <select x-model="taskForm.type"
                                                class="field-select w-full font-semibold text-gray-700 cursor-pointer shadow-sm"
                                                style="border-color: #e2e8f0; padding: 7px 12px;">
                                                @foreach (\App\Models\CrmTask::TYPES as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-gray-400 pl-0.5">Priority</span>
                                            <select x-model="taskForm.priority"
                                                class="field-select w-full font-semibold text-gray-700 cursor-pointer shadow-sm"
                                                style="border-color: #e2e8f0; padding: 7px 12px;">
                                                <option value="medium">Medium Priority</option>
                                                <option value="high">High Priority</option>
                                                <option value="low">Low Priority</option>
                                            </select>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-gray-400 pl-0.5">Due Date & Time <span
                                                    class="text-red-500">*</span></span>
                                            <input type="datetime-local" x-model="taskForm.due_at"
                                                class="field-input w-full font-semibold text-gray-700 shadow-sm"
                                                style="border-color: #e2e8f0; padding: 6px 12px;">
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-gray-400 pl-0.5">Assignee</span>
                                            <x-user-picker name="task_assigned_to" :users="$users"
                                                x-model="taskForm.assigned_to" placeholder="Assign to..." />
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                                    <button @click="addOpen = false; taskAddError = null"
                                        class="px-4 py-2 rounded-xl text-[12px] font-bold text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                                        Cancel
                                    </button>
                                    <button @click="addTask()"
                                        :disabled="taskAdding || !taskForm.title.trim() || !taskForm.due_at"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-bold text-white transition-all shadow-sm"
                                        style="background: var(--brand-600);"
                                        :class="(!taskForm.title.trim() || !taskForm.due_at) ? 'opacity-40 cursor-not-allowed' :
                                        (taskAdding ? 'opacity-75 cursor-wait' : 'hover:shadow-md active:scale-[0.98]')">
                                        <span x-text="taskAdding ? 'Saving Task...' : 'Save Task'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Empty State --}}
                        <template x-if="tasks.length === 0">
                            <div class="px-6 py-12 flex flex-col items-center justify-center text-center">
                                <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        stroke="#cbd5e1" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <p class="text-[14px] font-bold text-gray-600 mb-1">No tasks created</p>
                                <p class="text-[12px] text-gray-400 font-medium">Keep track of your next moves by adding a
                                    task.</p>
                            </div>
                        </template>

                        {{-- Task List --}}
                        <div class="flex flex-col divide-y divide-gray-50/80">
                            <template x-for="task in tasks" :key="task.id">
                                <div class="group flex items-start gap-3.5 p-5 hover:bg-slate-50/40 transition-colors">

                                    {{-- Complete Toggle --}}
                                    @if (has_permission('crm_tasks.complete'))
                                        <button @click="completeTask(task)"
                                            :disabled="task.status === 'completed' || task.status === 'cancelled'"
                                            class="relative mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[6px] border-2 transition-all"
                                            :class="task.status === 'completed' ?
                                                'border-green-500 bg-green-500 cursor-default' : (task.is_overdue ?
                                                    'border-red-400 hover:border-red-500 hover:bg-red-50' :
                                                    'border-gray-300 hover:border-green-500 hover:bg-green-50')"
                                            :title="task.status === 'completed' ? 'Completed' : 'Mark as completed'">
                                            <svg x-show="task.status === 'completed'" width="10" height="10"
                                                viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="4"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12" />
                                            </svg>
                                        </button>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                {{-- Title & Status --}}
                                                <div class="flex items-center gap-2.5 mb-1">
                                                    <p class="text-[14px] font-bold text-gray-800 truncate transition-colors"
                                                        :class="task.status === 'completed' ? 'text-gray-400 line-through' : ''"
                                                        x-text="task.title"></p>
                                                    <span
                                                        class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded flex-shrink-0"
                                                        :style="`background: ${task.status_color}1A; color: ${task.status_color}`"
                                                        x-text="task.status_label"></span>
                                                </div>

                                                {{-- Description (Now Visible) --}}
                                                <p x-show="task.description"
                                                    class="text-[12px] text-gray-500 leading-relaxed mb-2.5 line-clamp-2"
                                                    x-text="task.description"></p>

                                                {{-- Meta Badges --}}
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    {{-- Due Date --}}
                                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gray-50 border border-gray-100"
                                                        :class="task.is_overdue && task.status !== 'completed' ?
                                                            'bg-red-50 border-red-100' : ''">
                                                        <svg width="12" height="12" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                                            stroke-linecap="round"
                                                            :class="task.is_overdue && task.status !== 'completed' ?
                                                                'text-red-500' : 'text-gray-400'">
                                                            <rect x="3" y="4" width="18" height="18"
                                                                rx="2" ry="2"></rect>
                                                            <line x1="16" y1="2" x2="16"
                                                                y2="6"></line>
                                                            <line x1="8" y1="2" x2="8"
                                                                y2="6"></line>
                                                            <line x1="3" y1="10" x2="21"
                                                                y2="10"></line>
                                                        </svg>
                                                        <span class="text-[10px] font-bold"
                                                            :class="task.is_overdue && task.status !== 'completed' ?
                                                                'text-red-600' : 'text-gray-600'"
                                                            x-text="task.due_at"></span>
                                                    </div>

                                                    {{-- Assignee --}}
                                                    <div x-show="task.assignee_name"
                                                        class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gray-50 border border-gray-100">
                                                        <svg width="12" height="12" viewBox="0 0 24 24"
                                                            fill="none" stroke="#9ca3af" stroke-width="2.5"
                                                            stroke-linecap="round">
                                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                            <circle cx="12" cy="7" r="4"></circle>
                                                        </svg>
                                                        <span class="text-[10px] font-bold text-gray-600"
                                                            x-text="task.assignee_name"></span>
                                                    </div>

                                                    {{-- Type --}}
                                                    <div
                                                        class="flex items-center gap-1.5 px-2 py-1 rounded-md bg-gray-50 border border-gray-100">
                                                        <span class="text-[10px] font-bold text-gray-500"
                                                            x-text="task.type_label"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Delete task (Hidden on Desktop until hover, visible on mobile) --}}
                                            <button @click="deleteTask(task.id)" x-show="task.status !== 'completed'"
                                                class="p-2 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 focus:opacity-100">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                                    <polyline points="3 6 5 6 21 6" />
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>
                @endif

                {{-- ── FOLLOW-UP DATE ── --}}
                <div class="detail-card" x-data="{
                    editing: false,
                    saving: false,
                    value: '{{ $lead->next_followup_at ? $lead->next_followup_at->format('Y-m-d\TH:i') : '' }}',
                    label: @js($lead->followup_label ?? ''),
                    note: @js($lead->followup_note ?? ''),
                    display: '{{ $lead->next_followup_at ? $lead->next_followup_at->format('d M Y, h:i A') : '' }}',
                    isOverdue: {{ $lead->is_overdue ? 'true' : 'false' }},
                    error: null,
                    success: null,
                    presets: ['Next Reminder', 'Follow-up Call', 'Send Proposal', 'Check In', 'Meeting Scheduled', 'Send Quote', 'Demo Scheduled'],
                    async save() {
                        if (!this.value) { this.error = 'Date is required.'; return; }
                        this.saving = true;
                        this.error = null;
                        try {
                            const res = await fetch('/admin/crm/leads/{{ $lead->id }}/followup', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    next_followup_at: this.value || null,
                                    followup_label: this.label || null,
                                    followup_note: this.note || null,
                                }),
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.display = data.display;
                                this.isOverdue = data.is_overdue;
                                this.editing = false;
                                this.success = data.message;
                                setTimeout(() => this.success = null, 3000);
                            } else {
                                this.error = data.message;
                            }
                        } catch (e) {
                            this.error = 'Network error. Please try again.';
                        } finally {
                            this.saving = false;
                        }
                    },
                    clearDate() {
                        this.value = '';
                        this.label = '';
                        this.note = '';
                        this.save();
                    }
                }">
                    <div class="card-title flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            <span>Set Reminder</span>
                        </div>
                        <button x-show="!editing" @click="editing = true"
                            class="text-[11px] font-bold text-blue-500 hover:text-blue-700 px-2 py-1 rounded-lg hover:bg-blue-50 transition-colors">
                            Edit
                        </button>
                    </div>

                    <div class="px-4 py-4">

                        {{-- Feedback --}}
                        <template x-if="success">
                            <p class="text-[12px] font-semibold text-green-600 mb-3" x-text="success"></p>
                        </template>
                        <template x-if="error">
                            <p class="text-[12px] font-semibold text-red-500 mb-3" x-text="error"></p>
                        </template>

                        {{-- ── Display mode ── --}}
                        <div x-show="!editing" class="space-y-1.5">
                            <template x-if="display">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span :class="isOverdue ? 'text-red-600 font-bold' : 'text-gray-800 font-bold'"
                                            class="text-[13px]">
                                            <span x-show="isOverdue">⚠ </span><span x-text="display"></span>
                                        </span>
                                    </div>
                                    <template x-if="label">
                                        <span
                                            class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-600"
                                            x-text="label"></span>
                                    </template>
                                    <template x-if="note">
                                        <p class="text-[12px] text-gray-500 font-medium mt-1" x-text="note"></p>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!display">
                                <p class="text-[12px] text-gray-400 font-medium italic">No follow-up scheduled</p>
                            </template>
                        </div>

                        {{-- ── Edit mode ── --}}
                        <div x-show="editing" x-cloak class="space-y-3">

                            {{-- Date & Time --}}
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1 block">Date
                                    & Time <span class="text-red-400">*</span></label>
                                <input type="datetime-local" x-model="value" class="field-input w-full">
                            </div>

                            {{-- Label with presets --}}
                            <div>
                                <label
                                    class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1 block">Label</label>
                                <input type="text" x-model="label" list="followup-presets"
                                    placeholder="e.g. Next Reminder, Follow-up Call..." class="field-input w-full">
                                <datalist id="followup-presets">
                                    <template x-for="p in presets" :key="p">
                                        <option :value="p"></option>
                                    </template>
                                </datalist>
                            </div>

                            {{-- Note --}}
                            <div>
                                <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-1 block">Note
                                    <span class="text-gray-300 font-medium">(optional)</span></label>
                                <textarea x-model="note" rows="2" placeholder="Any context for this follow-up..."
                                    class="field-input w-full resize-none"></textarea>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 pt-1">
                                <button @click="save()" :disabled="saving"
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-white transition-opacity"
                                    style="background: var(--brand-600)"
                                    :class="saving ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90'">
                                    <span x-text="saving ? 'Saving...' : 'Save'"></span>
                                </button>
                                <button @click="editing = false; error = null"
                                    class="px-3 py-1.5 rounded-lg text-[12px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button x-show="display" @click="clearDate()"
                                    class="ml-auto text-[11px] font-semibold text-red-400 hover:text-red-600 transition-colors">
                                    Clear
                                </button>
                            </div>
                        </div>

                    </div>
                </div>


                {{-- ── ACTIVITY LOG ── --}}
                <div class="detail-card" x-data="activityPanel({{ $lead->id }})">
                    <div class="card-title">Activity Timeline</div>

                    {{-- Log activity form ── --}}
                    <div class="px-4 py-4 border-b border-gray-50 space-y-3">

                        {{-- Type tabs ── --}}
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @foreach ($activityTypes as $aType)
                                <button @click="actForm.type = '{{ $aType['key'] }}'"
                                    class="text-[11px] font-bold px-3 py-1.5 rounded-lg border transition-colors"
                                    :class="actForm.type === '{{ $aType['key'] }}' ?
                                        'text-white border-transparent' :
                                        'text-gray-500 border-gray-200 hover:bg-gray-50'"
                                    :style="actForm.type === '{{ $aType['key'] }}' ? 'background: var(--brand-600)' : ''">
                                    {{ $aType['label'] }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Description ── --}}
                        <textarea x-model="actForm.description" rows="2"
                            placeholder="Add a note, log a call, record a WhatsApp conversation..." class="field-input resize-none w-full"></textarea>

                        <template x-if="actError">
                            <p class="text-[12px] font-semibold text-red-500" x-text="actError"></p>
                        </template>

                        <button @click="logActivity()" :disabled="actLogging || !actForm.description.trim()"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-bold text-white hover:opacity-90 transition-opacity"
                            style="background: var(--brand-600)"
                            :class="(actLogging || !actForm.description.trim()) ? 'opacity-50 cursor-not-allowed' : ''">
                            <span x-text="actLogging ? 'Logging...' : 'Log Activity'"></span>
                        </button>
                    </div>

                    {{-- Timeline ── --}}
                    @php
                        $typeColors = [
                            'note' => ['bg' => '#f8fafc', 'text' => '#64748b'],
                            'call' => ['bg' => '#eff6ff', 'text' => '#2563eb'],
                            'whatsapp' => ['bg' => '#f0fdf4', 'text' => '#16a34a'],
                            'email' => ['bg' => '#faf5ff', 'text' => '#7c3aed'],
                            'meeting' => ['bg' => '#fff7ed', 'text' => '#c2410c'],
                            'stage_change' => ['bg' => '#fefce8', 'text' => '#a16207'],
                            'lead_created' => ['bg' => '#f0fdf4', 'text' => '#15803d'],
                            'converted' => ['bg' => '#f0fdf4', 'text' => '#15803d'],
                            'task_completed' => ['bg' => '#eff6ff', 'text' => '#2563eb'],
                            'score_changed' => ['bg' => '#faf5ff', 'text' => '#7c3aed'],
                        ];
                    @endphp

                    <div class="max-h-[450px] overflow-y-auto custom-scroll">
                        <div id="activity-timeline">
                            {{-- New activities prepended here by JS ── --}}
                        </div>

                        @if ($lead->activities->isEmpty())
                            <div class="px-5 py-8 text-center text-[13px] text-gray-400 font-medium">
                                No activity yet. Log a call, note, or WhatsApp message.
                            </div>
                        @else
                            @foreach ($lead->activities as $act)
                                @php $tc = $typeColors[$act->type] ?? ['bg' => '#f8fafc', 'text' => '#64748b']; @endphp
                                <div class="timeline-item">
                                    <div class="timeline-dot"
                                        style="background: {{ $tc['bg'] }}; color: {{ $tc['text'] }}">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            @if ($act->type === 'call')
                                                <path
                                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                                            @elseif($act->type === 'whatsapp')
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                            @elseif($act->type === 'email')
                                                <path
                                                    d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                                <polyline points="22,6 12,13 2,6" />
                                            @elseif($act->type === 'meeting')
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                                <circle cx="9" cy="7" r="4" />
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                            @elseif($act->type === 'stage_change')
                                                <polyline points="9 18 15 12 9 6" />
                                            @else
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <polyline points="14 2 14 8 20 8" />
                                            @endif
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <span class="text-[11px] font-bold"
                                                style="color: {{ $tc['text'] }}">{{ $act->type_label }}</span>
                                            <span class="text-[10px] text-gray-400 font-medium flex-shrink-0"
                                                title="{{ $act->created_at->format('d M Y, h:i A') }}">
                                                {{ $act->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-[13px] text-gray-700 leading-relaxed">{{ $act->description }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $act->is_auto ? 'System' : $act->user?->name ?? 'Unknown' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // ── Activity type colors for JS-prepended items ──
        window.actTypeColors = window.actTypeColors || {
            note: {
                bg: '#f8fafc',
                text: '#64748b'
            },
            call: {
                bg: '#eff6ff',
                text: '#2563eb'
            },
            whatsapp: {
                bg: '#f0fdf4',
                text: '#16a34a'
            },
            email: {
                bg: '#faf5ff',
                text: '#7c3aed'
            },
            meeting: {
                bg: '#fff7ed',
                text: '#c2410c'
            },
            stage_change: {
                bg: '#fefce8',
                text: '#a16207'
            },
            lead_created: {
                bg: '#f0fdf4',
                text: '#15803d'
            },
            converted: {
                bg: '#f0fdf4',
                text: '#15803d'
            },
            task_completed: {
                bg: '#eff6ff',
                text: '#2563eb'
            },
            score_changed: {
                bg: '#faf5ff',
                text: '#7c3aed'
            },
        };

        // ── Main lead detail component ──
        function leadDetail(leadId, initialPriority, initialScore, initialStageName, initialStageColor) {
            return {
                // Taken from the arguments the x-data call already passes in. These
                // five were re-embedded from Blade instead, so the parameters were
                // dead — and inside a <script> block the browser never decodes
                // entities, so a stage named Won't Buy rendered as Won&#039;t Buy.
                leadId: leadId,
                currentPriority: initialPriority,
                currentStageName: initialStageName,
                currentStageColor: initialStageColor,
                stageMoving: false,
                converting: false,
                score: initialScore,
                pageSuccess: null,
                pageError: null,

                init() {},

                flash(msg, type = 'success') {
                    if (type === 'success') {
                        this.pageSuccess = msg;
                        setTimeout(() => this.pageSuccess = null, 4000);
                    } else {
                        this.pageError = msg;
                        setTimeout(() => this.pageError = null, 5000);
                    }
                },

                async ajax(method, url, body = null) {
                    const opts = {
                        method,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    };
                    if (body) {
                        opts.headers['Content-Type'] = 'application/json';
                        opts.body = JSON.stringify(body);
                    }
                    const res = await fetch(url, opts);
                    return await res.json();
                },

                // ── Stage move ──
                async moveToStage(stageId, stageName) {
                    if (this.stageMoving) return;
                    const c = await Swal.fire({
                        title: `Move to "${stageName}"?`,
                        input: 'text',
                        inputPlaceholder: 'Optional note (reason for moving)',
                        showCancelButton: true,
                        confirmButtonText: 'Move',
                        confirmButtonColor: 'var(--brand-600)',
                    });
                    if (!c.isConfirmed) return;
                    this.stageMoving = true;
                    try {
                        const data = await this.ajax('POST', `/admin/crm/leads/{{ $lead->id }}/stage`, {
                            stage_id: stageId,
                            note: c.value || null,
                        });
                        if (data.success) {
                            this.currentStageName = data.stage.name;
                            this.currentStageColor = data.stage.color;
                            this.flash(data.message);
                            // Reload to update stage dots — simplest and most reliable
                            setTimeout(() => location.reload(), 800);
                        } else {
                            this.flash(data.message, 'error');
                        }
                    } catch (e) {
                        this.flash('Network error.', 'error');
                    } finally {
                        this.stageMoving = false;
                    }
                },

                // ── Priority ──
                async setPriority(priority) {
                    this.currentPriority = priority;
                    try {
                        const data = await this.ajax('PUT', `/admin/crm/leads/{{ $lead->id }}`, {
                            // The name is sent straight back to the update endpoint, so a
                            // mangled value is not just a display problem — it overwrites
                            // the stored record. Inside a <script> block the browser never
                            // decodes HTML entities, so e() left the apostrophe in O'Brien
                            // as a literal &#039; and addslashes put a backslash in front
                            // of it; every priority change corrupted the name a little more.
                            name: @js($lead->name),
                            priority: priority,
                        });
                        if (data.success) this.flash('Priority updated.');
                        else this.flash(data.message, 'error');
                    } catch (e) {
                        this.flash('Network error.', 'error');
                    }
                },

                // ── Convert ──
                async convertLead() {
                    const c = await Swal.fire({
                        title: 'Convert Lead to Client?',
                        text: 'A client record will be created from this lead.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Convert',
                        confirmButtonColor: '#10b981',
                    });
                    if (!c.isConfirmed) return;
                    this.converting = true;
                    try {
                        const data = await this.ajax('POST', `/admin/crm/leads/{{ $lead->id }}/convert`);
                        if (data.success) {
                            this.flash(data.message);
                            setTimeout(() => {
                                if (data.client_url) {
                                    // Use your SPA engine to transition smoothly, fallback to hard redirect if missing
                                    if (typeof navigate === 'function') {
                                        navigate(data.client_url);
                                    } else {
                                        window.location.href = data.client_url;
                                    }
                                } else {
                                    location.reload();
                                }
                            }, 800); // Slightly faster redirect feels snappier
                        } else {
                            this.flash(data.message, 'error');
                        }
                    } catch (e) {
                        this.flash('Network error.', 'error');
                    } finally {
                        this.converting = false;
                    }
                },

                // ── Delete ──
                async deleteLead() {
                    const c = await Swal.fire({
                        title: 'Delete Lead?',
                        text: 'This lead will be soft deleted.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        confirmButtonColor: '#ef4444',
                    });
                    if (!c.isConfirmed) return;
                    try {
                        const data = await this.ajax('DELETE', `/admin/crm/leads/{{ $lead->id }}`);
                        if (data.success) {
                            window.location.href = '{{ route('admin.crm.leads.index') }}';
                        } else {
                            this.flash(data.message, 'error');
                        }
                    } catch (e) {
                        this.flash('Network error.', 'error');
                    }
                },
            }
        }

        // ── Tasks panel ──
        function tasksPanel(initialTasks, leadId) {
            return {
                // The x-data call already passes both of these. Re-embedding the
                // task list here ran the controller-backed formatter a second time
                // for every task on every page load, and the arguments were thrown
                // away.
                tasks: initialTasks ?? [],
                leadId: leadId,
                addOpen: false,
                taskAdding: false,
                taskAddError: null,
                taskForm: {
                    title: '',
                    type: 'follow_up',
                    priority: 'medium',
                    due_at: '',
                    assigned_to: '',
                    description: ''
                },

                async addTask() {
                    if (!this.taskForm.title.trim() || !this.taskForm.due_at || this.taskAdding) return;
                    this.taskAdding = true;
                    this.taskAddError = null;
                    try {
                        const res = await fetch(`/admin/crm/leads/{{ $lead->id }}/tasks`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(this.taskForm),
                        });
                        const data = await res.json();
                        if (res.status === 422) {
                            this.taskAddError = data.errors?.due_at?.[0] ?? data.message;
                            return;
                        }
                        if (!data.success) {
                            this.taskAddError = data.message;
                            return;
                        }
                        this.tasks.unshift(data.task);
                        this.taskForm = {
                            title: '',
                            type: 'follow_up',
                            priority: 'medium',
                            due_at: '',
                            assigned_to: '',
                            description: ''
                        };
                        this.addOpen = false;
                    } catch (e) {
                        this.taskAddError = 'Network error.';
                    } finally {
                        this.taskAdding = false;
                    }
                },

                async completeTask(task) {
                    if (task.status === 'completed' || task.status === 'cancelled') return;
                    const c = await Swal.fire({
                        title: 'Complete Task?',
                        input: 'text',
                        inputPlaceholder: 'Completion note (optional)',
                        showCancelButton: true,
                        confirmButtonText: 'Mark Complete',
                        confirmButtonColor: '#10b981',
                    });
                    if (!c.isConfirmed) return;
                    try {
                        const res = await fetch(`/admin/crm/leads/{{ $lead->id }}/tasks/${task.id}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                completion_note: c.value || ''
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            const idx = this.tasks.findIndex(t => t.id === task.id);
                            if (idx !== -1) this.tasks[idx] = data.task;
                        }
                    } catch (e) {}
                },

                async deleteTask(taskId) {
                    const c = await Swal.fire({
                        title: 'Delete Task?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        confirmButtonColor: '#ef4444',
                    });
                    if (!c.isConfirmed) return;
                    try {
                        const res = await fetch(`/admin/crm/leads/{{ $lead->id }}/tasks/${taskId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                        });
                        const data = await res.json();
                        if (data.success) this.tasks = this.tasks.filter(t => t.id !== taskId);
                    } catch (e) {}
                },
            }
        }

        // ── Activity panel ──
        function activityPanel(leadId) {
            return {
                leadId: leadId,
                actForm: {
                    type: 'note',
                    description: ''
                },
                actLogging: false,
                actError: null,

                async logActivity() {
                    if (!this.actForm.description.trim() || this.actLogging) return;
                    this.actLogging = true;
                    this.actError = null;
                    try {
                        const res = await fetch(`/admin/crm/leads/{{ $lead->id }}/activity`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(this.actForm),
                        });
                        const data = await res.json();
                        if (!data.success) {
                            this.actError = data.message;
                            return;
                        }

                        // Prepend to timeline
                        const act = data.activity;
                        const tc = actTypeColors[act.type] || {
                            bg: '#f8fafc',
                            text: '#64748b'
                        };
                        const html = `
                    <div class="timeline-item" style="animation: fadeIn 200ms ease">
                        <div class="timeline-dot" style="background:${tc.bg};color:${tc.text}">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                <span class="text-[11px] font-bold" style="color:${tc.text}">${act.type_label}</span>
                                <span class="text-[10px] text-gray-400 font-medium">${act.created_at}</span>
                            </div>
                            <p class="text-[13px] text-gray-700 leading-relaxed">${act.description}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">${act.user_name}</p>
                        </div>
                    </div>`;

                        const timeline = document.getElementById('activity-timeline');
                        if (timeline) timeline.insertAdjacentHTML('afterbegin', html);

                        this.actForm.description = '';
                    } catch (e) {
                        this.actError = 'Network error.';
                    } finally {
                        this.actLogging = false;
                    }
                },
            }
        }
    </script>
@endpush
