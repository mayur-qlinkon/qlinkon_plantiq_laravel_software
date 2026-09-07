@extends('layouts.admin')

@section('title', 'Banners & Promotions')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Storefront Banners</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Manage homepage, promo and popup banners</p> --}}
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ── Type badge colors ── */
        .type-hero {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .type-promo {
            background: #fef3c7;
            color: #d97706;
        }

        .type-ad {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .type-category {
            background: #dcfce7;
            color: #15803d;
        }

        .type-popup {
            background: #fee2e2;
            color: #dc2626;
        }

        /* ── Banner card ── */
        .banner-card {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            overflow: hidden;
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 120ms ease;
            position: relative;
        }

        .banner-card:hover {
            border-color: #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
            transform: translateY(-1px);
        }

        .banner-card.inactive {
            opacity: 0.65;
        }

        /* ── Image area ── */
        .banner-thumb {
            width: 100%;
            height: 140px;
            object-fit: cover;
            background: #f8fafc;
            display: block;
        }

        .banner-thumb-placeholder {
            width: 100%;
            height: 140px;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }

        /* ── Status dot ── */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .status-dot.live {
            background: #22c55e;
            box-shadow: 0 0 0 3px #dcfce7;
        }

        .status-dot.offline {
            background: #d1d5db;
        }

        .status-dot.scheduled {
            background: #f59e0b;
            box-shadow: 0 0 0 3px #fef3c7;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        /* ── Action button ── */
        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: background 120ms ease, color 120ms ease, transform 80ms ease;
            flex-shrink: 0;
        }

        .action-btn:active {
            transform: scale(0.9);
        }

        .action-btn.edit {
            background: #eff6ff;
            color: #3b82f6;
        }

        .action-btn.edit:hover {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .action-btn.copy {
            background: #f0fdf4;
            color: #22c55e;
        }

        .action-btn.copy:hover {
            background: #dcfce7;
            color: #16a34a;
        }

        .action-btn.del {
            background: #fff1f2;
            color: #f43f5e;
        }

        .action-btn.del:hover {
            background: #ffe4e6;
            color: #e11d48;
        }

        /* ── Toggle switch ── */
        .toggle-switch {
            position: relative;
            width: 36px;
            height: 20px;
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
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 200ms ease;
            pointer-events: none;
        }

        .toggle-switch input:checked~.toggle-thumb {
            transform: translateX(16px);
        }

        /* ── Scheduling badge ── */
        .scheduled-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 5px;
            background: #fef3c7;
            color: #b45309;
            letter-spacing: 0.04em;
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

        /* ── Card entrance ── */
        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-enter {
            animation: cardIn 250ms ease both;
        }

        /* ── Sort Mode ── */
        body.sort-mode .banner-card {
            cursor: grab;
            border-color: var(--brand-600) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--brand-600) 20%, transparent) !important;
        }

        body.sort-mode .banner-card:active {
            cursor: grabbing;
        }

        body.sort-mode .action-btn,
        body.sort-mode .toggle-switch {
            pointer-events: none;
            opacity: 0.4;
        }

        .sortable-ghost {
            opacity: 0.35;
            background: color-mix(in srgb, var(--brand-600) 8%, white) !important;
            border: 2px dashed var(--brand-600) !important;
        }

        .sortable-drag {
            opacity: 0.95;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15) !important;
            transform: rotate(1.5deg) scale(1.02) !important;
        }

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
    </style>
@endpush

@section('content')
    @php
        $companyId = auth()->user()->company_id;
        $totalBanners = \App\Models\Banner::where('company_id', $companyId)->count();
        $liveBanners = \App\Models\Banner::where('company_id', $companyId)->where('is_active', true)->count();
        $scheduled = \App\Models\Banner::where('company_id', $companyId)->whereNotNull('starts_at')->count();
    @endphp

    <div class="pb-10" x-data="bannerIndex()">

        {{-- ── Page Header ── --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Banners & Promotions</h1>
                <p class="text-sm text-gray-400 font-medium mt-0.5">Control what appears on your public storefront</p>
            </div>
            <div class="flex items-center gap-2">
                @if(has_permission('banners.reorder'))
                <button type="button" id="sort-mode-btn" onclick="toggleSortMode()"
                    class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                    <span id="sort-mode-label">Reorder</span>
                </button>
                @endif
                @if(has_permission('banners.create'))
                <a href="{{ route('admin.banners.create') }}"
                    class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add Banner
                </a>
                @endif
            </div>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="layout-template" class="w-4 h-4 text-gray-500"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</p>
                    <p class="text-xl font-black text-gray-900 leading-none mt-0.5">{{ $totalBanners }}</p>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="eye" class="w-4 h-4 text-green-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Live</p>
                    <p class="text-xl font-black text-green-600 leading-none mt-0.5">{{ $liveBanners }}</p>
                </div>
            </div>
            <div class="bg-white border border-gray-100 rounded-2xl px-4 py-3.5 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Scheduled</p>
                    <p class="text-xl font-black text-amber-600 leading-none mt-0.5">{{ $scheduled }}</p>
                </div>
            </div>
        </div>

        {{-- ── Filter Bar ── --}}
        <div class="bg-white rounded-2xl border border-gray-100 px-4 py-3 mb-5 flex flex-wrap items-center gap-2 shadow-sm">

            {{-- Type filters ── --}}
            <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest mr-1">Type</span>

            <a href="{{ route('admin.banners.index') }}" class="filter-pill {{ !$type ? 'active' : '' }}">
                All
            </a>
            @foreach ($types as $t)
                <a href="{{ route('admin.banners.index', ['type' => $t, 'position' => $position]) }}"
                    class="filter-pill {{ $type === $t ? 'active' : '' }}">
                    {{ ucfirst($t) }}
                </a>
            @endforeach

            <div class="w-px h-5 bg-gray-200 mx-1"></div>

            {{-- Position filter ── --}}
            <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest mr-1">Position</span>
            <select
                onchange="window.location.href='{{ route('admin.banners.index') }}?type={{ $type }}&position=' + this.value"
                class="text-xs font-semibold border border-gray-200 rounded-lg px-3 py-1.5 outline-none text-gray-600 bg-white">
                <option value="">All Positions</option>
                @foreach ($positions as $pos)
                    <option value="{{ $pos }}" {{ $position === $pos ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', ucwords($pos, '_')) }}
                    </option>
                @endforeach
            </select>

            <div class="ml-auto text-xs text-gray-400 font-medium">
                {{ $logs->total() }} banner{{ $logs->total() !== 1 ? 's' : '' }}
            </div>
        </div>

        {{-- ── Banner Grid ── --}}
        @if ($logs->isEmpty())
            {{-- Empty State --}}
            <div
                class="bg-white rounded-2xl border border-gray-100 py-20 flex flex-col items-center justify-center text-center">
                <div class="float-anim mb-5">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center">
                        <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
                    </div>
                </div>
                <p class="text-base font-bold text-gray-500 mb-1">No banners yet</p>
                <p class="text-sm text-gray-400 mb-6">
                    {{ $type ? "No '{$type}' banners found." : 'Create your first banner to promote products on your storefront.' }}
                </p>
                <a href="{{ route('admin.banners.create') }}"
                    class="inline-flex items-center gap-2 bg-brand-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-brand-700 transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Create First Banner
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                @foreach ($logs as $index => $banner)
                    @php
                        $isLive =
                            $banner->is_active &&
                            (!$banner->starts_at || $banner->starts_at->lte(now())) &&
                            (!$banner->ends_at || $banner->ends_at->gte(now()));
                        $isScheduled = $banner->is_active && $banner->starts_at && $banner->starts_at->gt(now());
                        $typeColor = "type-{$banner->type}";
                    @endphp

                    <div class="banner-card card-enter {{ !$banner->is_active ? 'inactive' : '' }}"
                        style="animation-delay: {{ $index * 40 }}ms" id="banner-card-{{ $banner->id }}">

                        {{-- Image ── --}}
                        <div class="relative">
                            @if ($banner->image)
                                <img src="{{ asset('storage/' . $banner->image) }}"
                                    alt="{{ $banner->alt_text ?? $banner->title }}" class="banner-thumb" loading="lazy"
                                   onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'banner-thumb-placeholder\'><svg xmlns=\'http://www.w3.org/2000/svg\' width=\'32\' height=\'32\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1.5\'><rect width=\'18\' height=\'18\' x=\'3\' y=\'3\' rx=\'2\' ry=\'2\'/><circle cx=\'9\' cy=\'9\' r=\'2\'/><path d=\'m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21\'/></svg></div>'">
                            @else
                                <div class="banner-thumb-placeholder">
                                    <i data-lucide="image" class="w-8 h-8"></i>
                                </div>
                            @endif

                            {{-- Type badge — top left ── --}}
                            <span
                                class="absolute top-2 left-2 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md {{ $typeColor }}">
                                {{ $banner->type }}
                            </span>

                            {{-- Status dot — top right ── --}}
                            <div
                                class="absolute top-2.5 right-2.5 flex items-center gap-1.5 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg">
                                <span
                                    class="status-dot {{ $isLive ? 'live' : ($isScheduled ? 'scheduled' : 'offline') }}"></span>
                                <span class="text-[10px] font-bold text-gray-700">
                                    {{ $isLive ? 'Live' : ($isScheduled ? 'Scheduled' : 'Off') }}
                                </span>
                            </div>
                        </div>

                        {{-- Card Body ── --}}
                        <div class="p-3.5">

                            {{-- Admin label + subtitle ── --}}
                            <div class="mb-2.5">
                                <p class="text-sm font-bold text-gray-900 truncate leading-tight">
                                    {{ $banner->display_admin_label }}
                                </p>
                                @if ($banner->subtitle)
                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $banner->subtitle }}</p>
                                @endif
                            </div>

                            {{-- Meta row ── --}}
                            <div class="flex items-center gap-1.5 mb-3 flex-wrap">
                                <span class="text-[10px] font-semibold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md">
                                    {{ str_replace('_', ' ', $banner->position) }}
                                </span>
                                @if ($banner->starts_at)
                                    <span class="scheduled-badge">
                                        {{ $banner->starts_at->format('d M') }}
                                        @if ($banner->ends_at)
                                            – {{ $banner->ends_at->format('d M') }}
                                        @endif
                                    </span>
                                @endif
                                @if ($banner->link)
                                    <span
                                        class="text-[10px] text-blue-500 font-semibold flex items-center gap-0.5 truncate max-w-[100px]">
                                        <i data-lucide="link" class="w-3 h-3 flex-shrink-0"></i>
                                        Linked
                                    </span>
                                @endif
                            </div>

                            {{-- Analytics row ── --}}
                            <div
                                class="flex items-center gap-3 text-[11px] text-gray-400 font-medium mb-3 border-t border-gray-50 pt-2.5">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3"></i>
                                    {{ number_format($banner->view_count) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="mouse-pointer-click" class="w-3 h-3"></i>
                                    {{ number_format($banner->click_count) }}
                                </span>
                                @if ($banner->view_count > 0)
                                    <span class="ml-auto text-[10px] font-bold text-gray-300">
                                        {{ round(($banner->click_count / $banner->view_count) * 100, 1) }}% CTR
                                    </span>
                                @endif
                            </div>

                            {{-- Actions row ── --}}
                            <div class="flex items-center justify-between">

                                {{-- Toggle active ── --}}
                                @if(has_permission('banners.toggle_status'))
                                <label class="toggle-switch" title="{{ $banner->is_active ? 'Deactivate' : 'Activate' }}">
                                    <input type="checkbox" {{ $banner->is_active ? 'checked' : '' }}
                                        onchange="toggleBannerActive({{ $banner->id }}, this)">
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                                @endif

                                {{-- Action buttons ── --}}
                                <div class="flex items-center gap-1.5">

                                    {{-- Duplicate ── --}}
                                    @if(has_permission('banners.duplicate'))
                                    <button type="button" onclick="duplicateBanner({{ $banner->id }})"
                                        class="action-btn copy" title="Duplicate">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif

                                    {{-- Edit ── --}}
                                    @if(has_permission('banners.update'))
                                    <a href="{{ route('admin.banners.edit', $banner->id) }}" class="action-btn edit"
                                        title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </a>
                                    @endif

                                    {{-- Delete ── --}}
                                    @if(has_permission('banners.delete'))
                                    <button type="button"
                                        onclick="deleteBanner({{ $banner->id }}, '{{ addslashes($banner->display_admin_label) }}')"
                                        class="action-btn del" title="Delete">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                    @endif

                                </div>
                            </div>

                            {{-- Sort order hint ── --}}
                            <p class="text-[10px] text-gray-300 font-medium mt-2 text-right">
                                #{{ $banner->sort_order + 1 }} in order
                            </p>

                        </div>
                    </div>
                @endforeach

            </div>

            {{-- ── Pagination ── --}}
            @if ($logs->hasPages())
                <div class="mt-6">
                    {{ $logs->appends(request()->query())->links() }}
                </div>
            @endif

        @endif

        {{-- ── Hidden delete forms ── --}}
        <div id="delete-forms-container"></div>

    </div>

    {{-- ── Sort Save Bar ── --}}
    <div id="sort-save-bar">
        <i data-lucide="grip-vertical" class="w-4 h-4 text-gray-400"></i>
        <span>Drag to reorder — </span>
        <button onclick="saveSortOrder()"
            class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
            <i data-lucide="check" class="w-3.5 h-3.5"></i>
            Save Order
        </button>
        <button onclick="cancelSortMode()"
            class="text-gray-400 hover:text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
            Cancel
        </button>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
        function bannerIndex() {
            return {};
        }

        /* ── Sort Mode ── */
        // ✅ window properties — safe to re-declare across SPA navigations
        window.sortable = window.sortable ?? null;
        window.sortModeActive = window.sortModeActive ?? false;
        window.originalOrder = window.originalOrder ?? [];

        function toggleSortMode() {
            window.sortModeActive ? cancelSortMode() : enterSortMode();
        }

        function enterSortMode() {
            window.sortModeActive = true;
            document.body.classList.add('sort-mode');

            const btn = document.getElementById('sort-mode-btn');
            const label = document.getElementById('sort-mode-label');
            btn.classList.replace('border-gray-200', 'border-brand-600');
            btn.classList.replace('text-gray-600', 'text-brand-600');
            label.textContent = 'Exit Reorder';

            const grid = document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2');
            if (!grid) {
                console.warn('[Sort] Grid not found');
                return;
            }

            // Save current order for cancel
            window.originalOrder = [...grid.querySelectorAll('.banner-card')].map(el => el.id.replace('banner-card-', ''));

            window.sortable = Sortable.create(grid, {
                animation: 200,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                handle: '.banner-card',
                onEnd: () => console.log('[Sort] Order changed'),
            });

            document.getElementById('sort-save-bar').classList.add('visible');
            window.initIcons && window.initIcons(document.getElementById('sort-save-bar'));
            console.log('[Sort] Sort mode enabled. Original order:', originalOrder);
        }

        function cancelSortMode() {
            if (!window.sortable) return;

            const grid = document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2');
            if (grid && window.originalOrder.length) {
                window.originalOrder.forEach(id => {
                    const el = document.getElementById('banner-card-' + id);
                    if (el) grid.appendChild(el);
                });
            }

            cleanupSortMode();
            console.log('[Sort] Sort cancelled, order restored.');
        }

        async function saveSortOrder() {
            const grid = document.querySelector('.grid.grid-cols-1.sm\\:grid-cols-2');
            if (!grid) return;

            const ids = [...grid.querySelectorAll('.banner-card')]
                .map(el => el.id.replace('banner-card-', ''))
                .filter(Boolean)
                .map(Number);

            console.log('[Sort] Saving order:', ids);

            const saveBtn = document.querySelector('#sort-save-bar button:first-of-type');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            try {
                const res = await fetch('/admin/banners/reorder', {
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
                    BizAlert.toast('Banner order saved!', 'success');
                    // Update sort order hints on cards
                    ids.forEach((id, index) => {
                        const hint = document.querySelector(`#banner-card-${id} .text-gray-300.font-medium`);
                        if (hint) hint.textContent = `#${index + 1} in order`;
                    });
                    window.originalOrder = ids.map(String);
                    // update baseline
                    console.log('[Sort] Order saved successfully:', ids);
                } else {
                    BizAlert.toast(data.message || 'Failed to save order.', 'error');
                    console.error('[Sort] Save failed:', data);
                }

            } catch (err) {
                BizAlert.toast('Network error. Please try again.', 'error');
                console.error('[Sort] Network error:', err);
            } finally {
                cleanupSortMode();
            }
        }

        function cleanupSortMode() {
            window.sortModeActive = false;
            document.body.classList.remove('sort-mode');

            if (window.sortable) {
                window.sortable.destroy();
                window.sortable = null;
            }

            const btn = document.getElementById('sort-mode-btn');
            const label = document.getElementById('sort-mode-label');
            if (btn) {
                btn.classList.replace('border-brand-600', 'border-gray-200');
                btn.classList.replace('text-brand-600', 'text-gray-600');
            }
            if (label) label.textContent = 'Reorder';

            document.getElementById('sort-save-bar')?.classList.remove('visible');
        }

        /* ── Toggle Active ── */
        async function toggleBannerActive(id, checkbox) {
            const card = document.getElementById('banner-card-' + id);
            checkbox.disabled = true;

            try {
                const res = await fetch(`/admin/banners/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    // Update card opacity
                    if (data.is_active) {
                        card?.classList.remove('inactive');
                    } else {
                        card?.classList.add('inactive');
                    }
                    BizAlert.toast(data.message, 'success');
                } else {
                    // Revert toggle
                    checkbox.checked = !checkbox.checked;
                    BizAlert.toast(data.message || 'Toggle failed.', 'error');
                }

            } catch (err) {
                checkbox.checked = !checkbox.checked;
                BizAlert.toast('Network error. Please try again.', 'error');
            } finally {
                checkbox.disabled = false;
            }
        }

        /* ── Duplicate ── */
        async function duplicateBanner(id) {
            try {
                const res = await fetch(`/admin/banners/${id}/duplicate`, {
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
                        'Banner Duplicated!',
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
                BizAlert.toast('Network error. Please try again.', 'error');
            }
        }

        /* ── Delete ── */
        async function deleteBanner(id, title) {
            const result = await BizAlert.confirm(
                `Delete "${title}"?`,
                'The banner will be moved to trash. It can be restored later.',
                'Yes, Delete'
            );

            if (!result.isConfirmed) return;

            BizAlert.loading('Deleting...');

            // Create and submit a hidden form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/banners/${id}`;

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
