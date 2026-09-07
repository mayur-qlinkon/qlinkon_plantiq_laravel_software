@extends('layouts.admin')

@section('title', 'System Audit Trail')

@section('header-title')
    <div>
        <h1 class="text-[17px] font-bold text-gray-800 leading-none">Audit Trail</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">Immutable record of all system activity</p>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ── Timeline line ── */
        .timeline-line {
            position: absolute;
            left: 19px;
            top: 44px;
            bottom: 0;
            width: 1.5px;
            background: linear-gradient(to bottom, #e5e7eb 0%, transparent 100%);
        }

        /* ── Log row entrance animation ── */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .log-row {
            animation: slideIn 200ms ease both;
        }

        /* ── Event dot ── */
        .event-dot {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .dot-created {
            background: #dcfce7;
            color: #16a34a;
        }

        .dot-updated {
            background: #dbeafe;
            color: #2563eb;
        }

        .dot-deleted {
            background: #fee2e2;
            color: #dc2626;
        }

        .dot-default {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ── Event badge ── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .badge-created {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-updated {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-deleted {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-default {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* ── Module chip ── */
        .module-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #374151;
        }

        /* ── Filter pill ── */
        .filter-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            transition: all 140ms ease;
            white-space: nowrap;
        }

        .filter-pill:hover {
            border-color: var(--brand-600);
            color: var(--brand-600);
        }

        .filter-pill.active {
            background: var(--brand-600);
            border-color: var(--brand-600);
            color: #fff;
        }

        /* ── Stats card ── */
        .stat-card {
            background: #fff;
            border: 1.5px solid #f3f4f6;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── Diff modal ── */
        .diff-key {
            font-family: 'Fira Code', 'Cascadia Code', monospace;
            font-size: 11px;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            color: #475569;
            font-weight: 600;
        }

        .diff-old {
            font-size: 12px;
            color: #dc2626;
            background: #fef2f2;
            padding: 2px 6px;
            border-radius: 4px;
            text-decoration: line-through;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        .diff-new {
            font-size: 12px;
            color: #16a34a;
            background: #f0fdf4;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
    </style>
@endpush

@section('content')
    @php
        $totalLogs = $logs->total();
        $todayCount = \Spatie\Activitylog\Models\Activity::whereDate('created_at', today())->count();
        $createdCount = \Spatie\Activitylog\Models\Activity::where('event', 'created')->count();
        $updatedCount = \Spatie\Activitylog\Models\Activity::where('event', 'updated')->count();
        $deletedCount = \Spatie\Activitylog\Models\Activity::where('event', 'deleted')->count();
    @endphp

    <div class="pb-10" x-data="auditLogViewer()">

        {{-- ── Page Header ── --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#212538] tracking-tight">System Audit Trail</h1>
                <p class="text-sm text-gray-400 font-medium mt-0.5">
                    Every creation, update and deletion — immutably logged
                </p>
            </div>
            <a href="{{ route('admin.settings.index') }}"
                class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                <i data-lucide="settings" class="w-4 h-4"></i> Back to Settings
            </a>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="stat-card">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="activity" class="w-4 h-4 text-gray-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Logs</p>
                    <p class="text-xl font-black text-gray-900">{{ number_format($totalLogs) }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Today</p>
                    <p class="text-xl font-black text-gray-900">{{ $todayCount }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-green-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Created</p>
                    <p class="text-xl font-black text-green-700">{{ $createdCount }}</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Deleted</p>
                    <p class="text-xl font-black text-red-600">{{ $deletedCount }}</p>
                </div>
            </div>
        </div>

        {{-- ── Filter Bar ── --}}
        <div
            class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 px-5 py-3.5 flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mr-1">Filter:</span>
            <button class="filter-pill" :class="{ active: activeFilter === 'all' }" @click="activeFilter = 'all'">
                All Events
            </button>
            <button class="filter-pill" :class="{ active: activeFilter === 'created' }" @click="activeFilter = 'created'">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span> Created
            </button>
            <button class="filter-pill" :class="{ active: activeFilter === 'updated' }" @click="activeFilter = 'updated'">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span> Updated
            </button>
            <button class="filter-pill" :class="{ active: activeFilter === 'deleted' }" @click="activeFilter = 'deleted'">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> Deleted
            </button>

            <div class="ml-auto flex items-center gap-2">
                <span class="text-xs text-gray-400 font-medium">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}
                </span>
            </div>
        </div>

        {{-- ── Timeline ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            @forelse($logs as $index => $log)
                @php
                    $event = $log->event ?? 'updated';
                    $dotClass = match ($event) {
                        'created' => 'dot-created',
                        'deleted' => 'dot-deleted',
                        'updated' => 'dot-updated',
                        default => 'dot-default',
                    };
                    $badgeClass = match ($event) {
                        'created' => 'badge-created',
                        'deleted' => 'badge-deleted',
                        'updated' => 'badge-updated',
                        default => 'badge-default',
                    };
                    $iconName = match ($event) {
                        'created' => 'plus',
                        'deleted' => 'trash-2',
                        'updated' => 'pencil',
                        default => 'activity',
                    };
                    $moduleName = class_basename($log->subject_type ?? 'Unknown');
                    $hasChanges = isset($log->properties['old']) || isset($log->properties['attributes']);
                    $attrs = $log->properties['attributes'] ?? [];
                    $old = $log->properties['old'] ?? [];
                @endphp

                <div class="log-row relative flex gap-4 px-5 py-4 border-b border-gray-50 hover:bg-gray-50/60 transition-colors"
                    style="animation-delay: {{ $index * 20 }}ms"
                    x-show="activeFilter === 'all' || activeFilter === '{{ $event }}'">

                    {{-- Timeline connector line (not on last item) --}}
                    @if (!$loop->last)
                        <div class="timeline-line"></div>
                    @endif

                    {{-- Event Dot --}}
                    <div class="event-dot {{ $dotClass }} mt-0.5">
                        <i data-lucide="{{ $iconName }}" class="w-4 h-4"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-start justify-between gap-2">

                            {{-- Left — description + meta --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <span class="badge {{ $badgeClass }}">{{ $event }}</span>
                                    <span class="module-chip">
                                        <i data-lucide="box" class="w-3 h-3"></i>
                                        {{ $moduleName }}
                                        @if ($log->subject_id)
                                            <span class="text-gray-400">#{{ $log->subject_id }}</span>
                                        @endif
                                    </span>
                                </div>

                                <p class="text-sm font-semibold text-gray-800 truncate">
                                    {{ $log->description }}
                                </p>

                                {{-- Inline diff preview — show up to 3 changed fields ── --}}
                                @if ($hasChanges && count($attrs) > 0)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach (array_slice($attrs, 0, 3, true) as $key => $newVal)
                                            <div class="flex items-center gap-1.5">
                                                <span class="diff-key">{{ $key }}</span>
                                                @if (isset($old[$key]))
                                                    <span class="diff-old" title="{{ $old[$key] }}">
                                                        {{ Str::limit($old[$key], 25) }}
                                                    </span>
                                                    <i data-lucide="arrow-right"
                                                        class="w-3 h-3 text-gray-300 flex-shrink-0"></i>
                                                @endif
                                                <span class="diff-new" title="{{ $newVal }}">
                                                    {{ Str::limit($newVal, 25) }}
                                                </span>
                                            </div>
                                        @endforeach
                                        @if (count($attrs) > 3)
                                            <span class="text-[11px] text-gray-400 font-medium self-center">
                                                +{{ count($attrs) - 3 }} more
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Right — user + time + action ── --}}
                            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                <div class="text-right">
                                    <p class="text-xs font-bold text-gray-800">
                                        {{ $log->causer?->name ?? 'System' }}
                                    </p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ $log->created_at->format('d M Y, h:i A') }}
                                    </p>
                                    <p class="text-[10px] text-gray-300 mt-0.5">
                                        {{ $log->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                @if ($hasChanges)
                                    <button
                                        @click="viewChanges({{ json_encode($log->properties) }}, '{{ addslashes($log->description) }}', '{{ $event }}')"
                                        class="text-xs font-bold px-3 py-1.5 rounded-lg border transition-colors
                                        {{ $event === 'deleted'
                                            ? 'border-red-200 text-red-600 bg-red-50 hover:bg-red-100'
                                            : ($event === 'created'
                                                ? 'border-green-200 text-green-700 bg-green-50 hover:bg-green-100'
                                                : 'border-indigo-200 text-indigo-600 bg-indigo-50 hover:bg-indigo-100') }}">
                                        View Diff
                                    </button>
                                @else
                                    <span class="text-[11px] text-gray-300 italic">No payload</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <div class="py-20 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="shield-check" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-500">No activity logged yet</p>
                    <p class="text-xs text-gray-400 mt-1">Changes to your system will appear here automatically</p>
                </div>
            @endforelse

        </div>

        {{-- ── Pagination ── --}}
        @if ($logs->hasPages())
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif

        {{-- ══════════════════════════════════════════
         DIFF MODAL
    ══════════════════════════════════════════ --}}
        <div x-show="isOpen" x-cloak class="modal-backdrop" @click.self="isOpen = false"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                            :class="{
                                'bg-green-50 text-green-600': modalEvent === 'created',
                                'bg-blue-50 text-blue-600': modalEvent === 'updated',
                                'bg-red-50 text-red-600': modalEvent === 'deleted',
                                'bg-gray-100 text-gray-600': !['created', 'updated', 'deleted'].includes(modalEvent)
                            }">
                            <i data-lucide="git-compare" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900" x-text="modalTitle"></p>
                            <p class="text-[11px] text-gray-400">Field-level change diff</p>
                        </div>
                    </div>
                    <button @click="isOpen = false"
                        class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                {{-- Diff Body --}}
                <div class="p-6 max-h-[65vh] overflow-y-auto">

                    {{-- Smart Diff Table --}}
                    <template x-if="hasDiff">
                        <div>
                            <div
                                class="grid grid-cols-[1fr_auto_1fr] gap-x-3 gap-y-2 items-center mb-3 pb-2 border-b border-gray-100">
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest">Before</p>
                                <p class="text-[10px] text-gray-300">→</p>
                                <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">After</p>
                            </div>

                            <template x-for="(newVal, key) in properties.attributes || {}" :key="key">
                                <div
                                    class="grid grid-cols-[auto_1fr_auto_1fr] gap-x-2 gap-y-1 items-center py-2 border-b border-gray-50">
                                    <span
                                        class="col-span-4 text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1"
                                        x-text="key"></span>
                                    <div class="col-start-1 col-span-1 text-right">
                                        {{-- empty --}}
                                    </div>
                                    <div class="col-start-1 col-span-2 flex items-center gap-2">
                                        <span
                                            class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded-md font-mono max-w-[200px] truncate block"
                                            x-text="(properties.old && properties.old[key] !== undefined) ? String(properties.old[key]).slice(0, 80) || '(empty)' : '(new field)'"
                                            :class="(properties.old && properties.old[key] !== undefined) ?
                                            'line-through opacity-70' : 'italic text-gray-400 bg-gray-50'">
                                        </span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-300 flex-shrink-0"></i>
                                        <span
                                            class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-md font-mono font-semibold max-w-[200px] truncate block"
                                            x-text="String(newVal).slice(0, 80) || '(empty)'">
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Raw JSON fallback (created / deleted with no old values) ── --}}
                    <template x-if="!hasDiff">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-2">Old Values
                                </p>
                                <pre class="text-[11px] bg-red-50 text-red-800 p-3 rounded-xl overflow-x-auto font-mono leading-relaxed"
                                    x-text="JSON.stringify(properties.old || {}, null, 2)"></pre>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-green-500 uppercase tracking-widest mb-2">New Values
                                </p>
                                <pre class="text-[11px] bg-green-50 text-green-800 p-3 rounded-xl overflow-x-auto font-mono leading-relaxed"
                                    x-text="JSON.stringify(properties.attributes || {}, null, 2)"></pre>
                            </div>
                        </div>
                    </template>

                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3 bg-gray-50/70 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3 h-3"></i>
                        Audit logs are immutable and cannot be modified
                    </p>
                    <button @click="isOpen = false"
                        class="px-4 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function auditLogViewer() {
            return {
                isOpen: false,
                properties: {},
                modalTitle: '',
                modalEvent: '',
                activeFilter: 'all',

                get hasDiff() {
                    return this.properties.old !== undefined &&
                        this.properties.attributes !== undefined &&
                        Object.keys(this.properties.old).length > 0;
                },

                viewChanges(props, title, event) {
                    this.properties = props;
                    this.modalTitle = title;
                    this.modalEvent = event;
                    this.isOpen = true;

                    // Re-init lucide icons inside modal after next tick
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    });
                },

                init() {
                    // Keyboard close
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && this.isOpen) this.isOpen = false;
                    });
                }
            }
        }
    </script>
@endpush
