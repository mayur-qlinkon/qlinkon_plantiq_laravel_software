@extends('layouts.admin')

@section('title', 'Storefront Sections')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Storefront Sections</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Design your homepage layout · Drag to reorder</p> --}}
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ════════════════════════════════════════
               TYPE COLORS — each section type has identity
            ════════════════════════════════════════ */
        .type-chip {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            flex-shrink: 0;
        }

        .type-category {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .type-featured {
            background: #fef3c7;
            color: #b45309;
        }

        .type-new_arrivals {
            background: #dcfce7;
            color: #15803d;
        }

        .type-best_sellers {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .type-manual {
            background: #e0f2fe;
            color: #0369a1;
        }

        .type-banner {
            background: #ffe4e6;
            color: #be123c;
        }

        .type-custom_html {
            background: #f1f5f9;
            color: #475569;
        }

        /* ════════════════════════════════════════
               SECTION CARD
            ════════════════════════════════════════ */
        .section-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 100ms ease;
            position: relative;
            overflow: hidden;
        }

        .section-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
        }

        .section-card.inactive {
            opacity: 0.6;
        }

        .section-card.sortable-ghost {
            opacity: 0.3;
            border: 2px dashed var(--brand-600) !important;
            background: color-mix(in srgb, var(--brand-600) 5%, white) !important;
        }

        .section-card.sortable-drag {
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18) !important;
            transform: rotate(1deg) scale(1.02) !important;
            border-color: var(--brand-600) !important;
            z-index: 50;
        }

        /* ── Position number badge ── */
        .position-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 24px;
            height: 24px;
            background: #f8fafc;
            border: 1.5px solid #e5e7eb;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 800;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: background 150ms ease, color 150ms ease;
        }

        /* ── Drag handle ── */
        .drag-handle {
            cursor: grab;
            color: #d1d5db;
            padding: 6px;
            border-radius: 8px;
            transition: color 120ms ease, background 120ms ease;
            flex-shrink: 0;
        }

        .drag-handle:hover {
            color: var(--brand-600);
            background: color-mix(in srgb, var(--brand-600) 8%, white);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        /* ── Card top accent bar — visual identity ── */
        .card-accent {
            height: 3px;
            border-radius: 16px 16px 0 0;
            flex-shrink: 0;
        }

        .accent-category {
            background: #3b82f6;
        }

        .accent-featured {
            background: #f59e0b;
        }

        .accent-new_arrivals {
            background: #22c55e;
        }

        .accent-best_sellers {
            background: #a855f7;
        }

        .accent-manual {
            background: #0ea5e9;
        }

        .accent-banner {
            background: #f43f5e;
        }

        .accent-custom_html {
            background: #94a3b8;
        }

        /* ── Toggle switch ── */
        .toggle-switch {
            position: relative;
            width: 34px;
            height: 19px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            display: none;
        }

        .toggle-track {
            position: absolute;
            inset: 0;
            background: #e5e7eb;
            border-radius: 20px;
            cursor: pointer;
            transition: background 200ms ease;
        }

        .toggle-switch input:checked+.toggle-track {
            background: var(--brand-600);
        }

        .toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 15px;
            height: 15px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 200ms ease;
            pointer-events: none;
        }

        .toggle-switch input:checked~.toggle-thumb {
            transform: translateX(15px);
        }

        /* ── Action buttons ── */
        .card-action {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 120ms ease, color 120ms ease, transform 80ms ease;
        }

        .card-action:active {
            transform: scale(0.88);
        }

        .btn-edit {
            background: #eff6ff;
            color: #3b82f6;
        }

        .btn-edit:hover {
            background: #dbeafe;
        }

        .btn-copy {
            background: #f0fdf4;
            color: #22c55e;
        }

        .btn-copy:hover {
            background: #dcfce7;
        }

        .btn-delete {
            background: #fff1f2;
            color: #f43f5e;
        }

        .btn-delete:hover {
            background: #ffe4e6;
        }

        /* ── Status indicator ── */
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-live {
            background: #22c55e;
            box-shadow: 0 0 0 2px #dcfce7;
        }

        .dot-offline {
            background: #d1d5db;
        }

        .dot-scheduled {
            background: #f59e0b;
            box-shadow: 0 0 0 2px #fef3c7;
        }

        /* ── Layout preview chip ── */
        .layout-chip {
            font-size: 9px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            background: #f8fafc;
            color: #6b7280;
            border: 1px solid #f1f5f9;
        }

        /* ── Stats row ── */
        .mini-stat {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
        }

        /* ── Sort save bar ── */
        #sort-save-bar {
            display: none;
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: #fff;
            padding: 12px 20px;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
            z-index: 999;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        #sort-save-bar.visible {
            display: flex;
        }

        /* ── Card entrance animation ── */
        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-enter {
            animation: cardIn 280ms ease both;
        }

        /* ── Empty state ── */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .float-anim {
            animation: float 3s ease-in-out infinite;
        }

        /* ── Reorder mode ── */
        body.reorder-mode .section-card {
            cursor: grab;
            border-color: color-mix(in srgb, var(--brand-600) 30%, #f1f5f9) !important;
        }

        body.reorder-mode .section-card:active {
            cursor: grabbing;
        }

        body.reorder-mode .card-action,
        body.reorder-mode .toggle-switch {
            pointer-events: none;
            opacity: 0.4;
        }

        /* ── Scheduled badge ── */
        .scheduled-chip {
            font-size: 9px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            background: #fef3c7;
            color: #b45309;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="storefrontSections()">

        {{-- ── Page Header ── --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                {{-- <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Storefront Sections</h1> --}}
                <p class="text-sm text-gray-400 font-medium mt-0.5">
                    Drag to reorder · Toggle visibility · Build your homepage layout
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(has_permission('storefront_sections.reorder'))
                <button type="button" id="reorder-btn" onclick="toggleReorderMode()"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                    <span id="reorder-label">Reorder</span>
                </button>
                @endif
                @if(has_permission('storefront_sections.create'))
                <a href="{{ route('admin.storefront-sections.create') }}"
                    class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Section
                </a>
                @endif
            </div>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-gray-500"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</p>
                    <p class="text-xl font-black text-gray-900 leading-none mt-0.5">{{ $stats['total'] }}</p>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye" class="w-4 h-4 text-green-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Live</p>
                    <p class="text-xl font-black text-green-600 leading-none mt-0.5">{{ $stats['live'] }}</p>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye-off" class="w-4 h-4 text-gray-400"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Inactive</p>
                    <p class="text-xl font-black text-gray-500 leading-none mt-0.5">{{ $stats['inactive'] }}</p>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Scheduled</p>
                    <p class="text-xl font-black text-amber-600 leading-none mt-0.5">{{ $stats['scheduled'] }}</p>
                </div>
            </div>
        </div>

        {{-- ── Info Bar ── --}}
        <div class="mb-5 flex items-center gap-2 text-xs text-gray-400 font-medium">
            <i data-lucide="info" class="w-3.5 h-3.5 flex-shrink-0"></i>
            Sections are displayed on your storefront in the order shown below. Position #1 appears first on the homepage.
        </div>

        {{-- ── Section Cards Grid ── --}}
        @if ($sections->isEmpty())
            <div
                class="bg-white border border-gray-100 rounded-2xl py-20 flex flex-col items-center justify-center text-center">
                <div class="float-anim mb-5">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center">
                        <i data-lucide="layout-dashboard" class="w-10 h-10 text-gray-300"></i>
                    </div>
                </div>
                <p class="text-base font-bold text-gray-500 mb-1">No sections yet</p>
                <p class="text-sm text-gray-400 mb-6 max-w-xs">
                    Create your first storefront section to start building your homepage layout.
                </p>
                <a href="{{ route('admin.storefront-sections.create') }}"
                    class="inline-flex items-center gap-2 bg-brand-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-brand-700 transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Create First Section
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="sections-grid">

                @foreach ($sections as $index => $section)
                    @php
                        $isLive = $section->is_live_now;
                        $isScheduled = $section->is_active && ($section->starts_at || $section->ends_at);
                        $typeKey = $section->type;
                    @endphp

                    <div class="section-card card-enter {{ !$section->is_active ? 'inactive' : '' }}"
                        style="animation-delay: {{ $index * 35 }}ms" id="section-card-{{ $section->id }}"
                        data-id="{{ $section->id }}">

                        {{-- Accent bar ── --}}
                        <div class="card-accent accent-{{ $typeKey }}"></div>

                        {{-- Position badge ── --}}
                        <div class="position-badge" id="position-{{ $section->id }}">{{ $index + 1 }}</div>

                        {{-- Card body ── --}}
                        <div class="p-4 pt-10">

                            {{-- Top row — drag + type chip + status ── --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="drag-handle" title="Drag to reorder">
                                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="type-chip type-{{ $typeKey }}">
                                        {{ $section->type_label }}
                                    </span>
                                    @if ($isScheduled)
                                        <span class="scheduled-chip">Scheduled</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="status-dot {{ $isLive ? 'dot-live' : ($isScheduled ? 'dot-scheduled' : 'dot-offline') }}"></span>
                                    <span class="text-[10px] font-bold text-gray-500">
                                        {{ $isLive ? 'Live' : ($isScheduled ? 'Sched' : 'Off') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Title (admin-only label, falls back to storefront title) ── --}}
                            <div class="mb-1">
                                <p class="text-sm font-bold text-gray-900 truncate leading-tight">{{ $section->display_admin_label }}</p>
                                @if ($section->admin_label && $section->title && $section->admin_label !== $section->title)
                                    <p class="text-[10px] text-gray-400 truncate mt-0.5">Storefront: {{ $section->title }}</p>
                                @elseif ($section->subtitle)
                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $section->subtitle }}</p>
                                @endif
                            </div>

                            {{-- Category name if type = category ── --}}
                            @if ($section->type === 'category' && $section->category)
                                <p class="text-[10px] font-semibold text-blue-500 mb-2 flex items-center gap-1">
                                    <i data-lucide="folder" class="w-3 h-3"></i>
                                    {{ $section->category->name }}
                                </p>
                            @endif

                            {{-- Display config chips ── --}}
                            <div class="flex items-center gap-1.5 flex-wrap mb-3">
                                <span class="layout-chip">{{ $section->layout_label }}</span>
                                <span class="layout-chip">{{ $section->products_limit }} items</span>
                                <span class="layout-chip">{{ $section->columns }} col</span>
                                @if (!$section->show_on_mobile)
                                    <span class="layout-chip text-amber-600"
                                        style="background: #fef3c7; border-color: #fde68a">Desktop only</span>
                                @endif
                                @if (!$section->show_on_desktop)
                                    <span class="layout-chip text-blue-600"
                                        style="background: #dbeafe; border-color: #bfdbfe">Mobile only</span>
                                @endif
                            </div>

                            {{-- Analytics ── --}}
                            <div class="flex items-center gap-3 border-t border-gray-50 pt-2.5 mb-3">
                                <div class="mini-stat">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    {{ number_format($section->view_count) }}
                                </div>
                                <div class="mini-stat">
                                    <i data-lucide="mouse-pointer-click" class="w-3 h-3"></i>
                                    {{ number_format($section->click_count) }}
                                </div>
                                @if ($section->view_count > 0)
                                    <span class="ml-auto text-[10px] font-bold text-gray-300">
                                        {{ $section->ctr }}% CTR
                                    </span>
                                @endif
                            </div>

                            {{-- Action row ── --}}
                            <div class="flex items-center justify-between">

                                {{-- Active toggle ── --}}
                                <label class="toggle-switch"
                                    title="{{ $section->is_active ? 'Deactivate' : 'Activate' }}">
                                    <input type="checkbox" {{ $section->is_active ? 'checked' : '' }}
                                        onchange="toggleSection({{ $section->id }}, this)">
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>

                                {{-- Action buttons ── --}}
                                <div class="flex items-center gap-1.5">

                                    {{-- Duplicate ── --}}
                                    @if(has_permission('storefront_sections.duplicate'))
                                    <button type="button"
                                        onclick="duplicateSection({{ $section->id }}, '{{ addslashes($section->title) }}')"
                                        class="card-action btn-copy" title="Duplicate">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif

                                    {{-- Edit ── --}}
                                    @if(has_permission('storefront_sections.update'))
                                    <a href="{{ route('admin.storefront-sections.edit', $section->id) }}"
                                        class="card-action btn-edit" title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif

                                    {{-- Delete ── --}}
                                    @if(has_permission('storefront_sections.delete'))
                                    <button type="button"
                                        onclick="deleteSection({{ $section->id }}, '{{ addslashes($section->title) }}')"
                                        class="card-action btn-delete" title="Delete">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Scheduling info ── --}}
                            @if ($section->starts_at || $section->ends_at)
                                <div class="mt-2.5 flex items-center gap-1.5 text-[10px] text-amber-600 font-semibold">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    @if ($section->starts_at)
                                        From {{ $section->starts_at->format('d M Y') }}
                                    @endif
                                    @if ($section->ends_at)
                                        → {{ $section->ends_at->format('d M Y') }}
                                    @endif
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach

            </div>
        @endif

        {{-- ── Delete forms container ── --}}
        <div id="delete-forms-container"></div>

    </div>

    {{-- ── Sort Save Bar ── --}}
    <div id="sort-save-bar">
        <i data-lucide="grip-vertical" class="w-4 h-4 text-gray-400"></i>
        <span>Drag sections to reorder homepage layout</span>
        <button onclick="saveSectionOrder()"
            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
            <i data-lucide="check" class="w-3.5 h-3.5"></i>
            Save Order
        </button>
        <button onclick="cancelReorderMode()"
            class="text-gray-400 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
            Cancel
        </button>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        function storefrontSections() {
            return {};
        }

        /* ════════════════════════════════════════
           REORDER
        ════════════════════════════════════════ */
        window.sortableInstance = window.sortableInstance ?? null;
        window.reorderModeActive = window.reorderModeActive ?? false;
        window.originalOrder = window.originalOrder ?? [];

        function toggleReorderMode() {
            window.reorderModeActive ? cancelReorderMode() : enterReorderMode();
        }

        function enterReorderMode() {
            window.reorderModeActive = true;
            document.body.classList.add('reorder-mode');

            const btn = document.getElementById('reorder-btn');
            const label = document.getElementById('reorder-label');
            btn.classList.add('border-brand-600', 'text-brand-600');
            btn.classList.remove('border-gray-200', 'text-gray-600');
            label.textContent = 'Exit Reorder';

            const grid = document.getElementById('sections-grid');
            if (!grid) {
                console.warn('[Sections] Grid not found');
                return;
            }

            // Save current order for cancel
            window.originalOrder = [...grid.querySelectorAll('.section-card')].map(el => el.dataset.id);

            window.sortableInstance = Sortable.create(grid, {
                animation: 220,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.drag-handle',
                onEnd: () => {
                    // Update position badges live
                    [...grid.querySelectorAll('.section-card')].forEach((el, i) => {
                        const badge = document.getElementById('position-' + el.dataset.id);
                        if (badge) badge.textContent = i + 1;
                    });
                    console.log('[Sections] Order changed');
                },
            });

            document.getElementById('sort-save-bar').classList.add('visible');
            window.initIcons && window.initIcons(document.getElementById('sort-save-bar'));
            console.log('[Sections] Reorder mode ON. Original:', window.originalOrder);
        }

        function cancelReorderMode() {
            const grid = document.getElementById('sections-grid');
            if (grid && window.originalOrder.length) {
                window.originalOrder.forEach(id => {
                    const el = document.getElementById('section-card-' + id);
                    if (el) grid.appendChild(el);
                });
                // Restore position badges
                [...grid.querySelectorAll('.section-card')].forEach((el, i) => {
                    const badge = document.getElementById('position-' + el.dataset.id);
                    if (badge) badge.textContent = i + 1;
                });
            }
            cleanupReorderMode();
            console.log('[Sections] Reorder cancelled, order restored.');
        }

        async function saveSectionOrder() {
            const grid = document.getElementById('sections-grid');
            if (!grid) return;

            const ids = [...grid.querySelectorAll('.section-card')]
                .map(el => parseInt(el.dataset.id))
                .filter(Boolean);

            console.log('[Sections] Saving order:', ids);

            const saveBtn = document.querySelector('#sort-save-bar button:first-of-type');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const res = await fetch('{{ route('admin.storefront-sections.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ids
                    }),
                });
                const data = await res.json();

                if (data.success) {
                    BizAlert.toast('Section order saved!', 'success');
                    window.originalOrder = ids.map(String);
                    console.log('[Sections] Order saved:', ids);
                } else {
                    BizAlert.toast(data.message || 'Failed to save order.', 'error');
                }
            } catch (err) {
                BizAlert.toast('Network error. Please try again.', 'error');
                console.error('[Sections] Reorder error:', err);
            } finally {
                cleanupReorderMode();
            }
        }

        function cleanupReorderMode() {
            window.reorderModeActive = false;
            document.body.classList.remove('reorder-mode');

            if (window.sortableInstance) {
                window.sortableInstance.destroy();
                window.sortableInstance = null;
            }

            const btn = document.getElementById('reorder-btn');
            const label = document.getElementById('reorder-label');
            if (btn) {
                btn.classList.remove('border-brand-600', 'text-brand-600');
                btn.classList.add('border-gray-200', 'text-gray-600');
            }
            if (label) label.textContent = 'Reorder';

            document.getElementById('sort-save-bar')?.classList.remove('visible');

            const saveBtn = document.querySelector('#sort-save-bar button:first-of-type');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Save Order';
            }
            window.initIcons && window.initIcons(document.getElementById('sort-save-bar'));
        }

        /* ════════════════════════════════════════
           TOGGLE ACTIVE
        ════════════════════════════════════════ */
        async function toggleSection(id, checkbox) {
            const card = document.getElementById('section-card-' + id);
            checkbox.disabled = true;

            try {
                const res = await fetch(`/admin/storefront-sections/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    card?.classList.toggle('inactive', !data.is_active);
                    BizAlert.toast(data.message, 'success');
                    console.log('[Sections] Toggled:', id, '→', data.is_active);
                } else {
                    checkbox.checked = !checkbox.checked; // revert
                    BizAlert.toast(data.message || 'Toggle failed.', 'error');
                }
            } catch (err) {
                checkbox.checked = !checkbox.checked;
                BizAlert.toast('Network error.', 'error');
                console.error('[Sections] Toggle error:', err);
            } finally {
                checkbox.disabled = false;
            }
        }

        /* ════════════════════════════════════════
           DUPLICATE
        ════════════════════════════════════════ */
        async function duplicateSection(id, title) {
            try {
                const res = await fetch(`/admin/storefront-sections/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    const result = await BizAlert.confirm(
                        'Section Duplicated!',
                        `"${data.message}" — Want to edit it now?`,
                        'Edit Now'
                    );
                    if (result.isConfirmed && data.edit_url) {
                        window.location.href = data.edit_url;
                    } else {
                        window.location.reload();
                    }
                } else {
                    BizAlert.toast(data.message || 'Duplicate failed.', 'error');
                }
            } catch (err) {
                BizAlert.toast('Network error.', 'error');
                console.error('[Sections] Duplicate error:', err);
            }
        }

        /* ════════════════════════════════════════
           DELETE
        ════════════════════════════════════════ */
        async function deleteSection(id, title) {
            const result = await BizAlert.confirm(
                `Delete "${title}"?`,
                'The section will be removed from your storefront. This cannot be undone.',
                'Yes, Delete'
            );
            if (!result.isConfirmed) return;

            BizAlert.loading('Deleting...');

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/storefront-sections/${id}`;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);

            document.getElementById('delete-forms-container').appendChild(form);
            form.submit();
        }
    </script>
@endpush
