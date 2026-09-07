@extends('layouts.admin')

@section('title', 'Manage Products — ' . $section->title)

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Manage Products</h1>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ════════════════════════════════════════
                   LAYOUT — Two-panel merchandising-style
                ════════════════════════════════════════ */
        .merch-wrap {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            height: auto;
        }

        @media (min-width: 1024px) {
            .merch-wrap {
                grid-template-columns: 1fr 1fr;
                height: calc(100vh - 200px);
                min-height: 520px;
            }
        }

        /* ── Panels ── */
        .panel {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 420px;
            max-height: 620px;
        }

        @media (min-width: 1024px) {
            .panel {
                min-height: auto;
                max-height: none;
            }
        }

        .panel-header {
            padding: 16px 18px;
            border-bottom: 1.5px solid #f3f4f6;
            background: #fafafa;
            flex-shrink: 0;
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .panel-body::-webkit-scrollbar {
            width: 3px;
        }

        .panel-body::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 4px;
        }

        /* ════════════════════════════════════════
                   LEFT — Assigned product rows
                ════════════════════════════════════════ */
        .product-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            border-bottom: 1px solid #f8fafc;
            transition: background 120ms ease;
            position: relative;
        }

        .product-row:hover {
            background: #fafafa;
        }

        .product-row.is-dragging {
            background: color-mix(in srgb, var(--brand-600) 5%, white);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            z-index: 10;
        }

        .sortable-ghost-row {
            opacity: 0.3;
            background: color-mix(in srgb, var(--brand-600) 8%, white) !important;
        }

        .drag-handle {
            color: #d1d5db;
            cursor: grab;
            flex-shrink: 0;
            padding: 4px;
            border-radius: 6px;
            transition: color 120ms ease;
        }

        .drag-handle:hover {
            color: var(--brand-600);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .prod-thumb {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            flex-shrink: 0;
        }

        .prod-name {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 280px;
        }

        .prod-meta {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
            margin-top: 1px;
        }

        .order-chip {
            font-size: 10px;
            font-weight: 800;
            color: #9ca3af;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 6px;
            padding: 2px 6px;
            min-width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .row-action {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: background 120ms ease, color 120ms ease, transform 80ms ease;
            flex-shrink: 0;
        }

        .row-action:active {
            transform: scale(0.88);
        }

        .btn-remove {
            background: #fff1f2;
            color: #f43f5e;
        }

        .btn-remove:hover {
            background: #ffe4e6;
            color: #e11d48;
        }

        /* ════════════════════════════════════════
                   RIGHT — Available product rows (multi-select)
                ════════════════════════════════════════ */
        .avail-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 14px;
            border-bottom: 1px solid #f8fafc;
            cursor: pointer;
            transition: background 120ms ease, opacity 120ms ease;
        }

        .avail-row:hover {
            background: #f8fafc;
        }

        .avail-row.selected {
            background: #ecfeff;
        }

        .avail-row.selected:hover {
            background: #cffafe;
        }

        .avail-row.disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .avail-row.disabled:hover {
            background: transparent;
        }

        .avail-thumb {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            flex-shrink: 0;
        }

        .avail-filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-bottom: 1.5px solid #f3f4f6;
            background: #fafafa;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .pick-checkbox {
            width: 16px;
            height: 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            background: #fff;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            cursor: pointer;
            transition: background 120ms ease, border-color 120ms ease;
        }

        .pick-checkbox.checked {
            background: var(--brand-600);
            border-color: var(--brand-600);
        }

        .selected-count-chip {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            background: #cffafe;
            color: #0e7490;
        }

        .btn-add-selected {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            background: var(--brand-600);
            color: #fff;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 120ms ease, opacity 120ms ease;
        }

        .btn-add-selected:hover:not(:disabled) {
            background: var(--brand-700, #0f766e);
        }

        .btn-add-selected:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ════════════════════════════════════════
                   SEARCH INPUT
                ════════════════════════════════════════ */
        .search-wrap {
            position: relative;
        }

        .search-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 14px 9px 36px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
            transition: border-color 150ms ease;
            background: #fff;
        }

        .search-input:focus {
            border-color: var(--brand-600);
        }

        .search-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        /* ════════════════════════════════════════
                   EMPTY + LOADING
                ════════════════════════════════════════ */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 220px;
            color: #9ca3af;
            text-align: center;
            padding: 32px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #e5e7eb;
            border-top-color: var(--brand-600);
            border-radius: 50%;
            animation: spin 600ms linear infinite;
        }

        .hidden-badge {
            font-size: 9px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 4px;
            background: #f3f4f6;
            color: #9ca3af;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .limit-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #fffbeb;
            border-bottom: 1.5px solid #fde68a;
            color: #b45309;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="pb-4" x-data="sectionProducts()" x-cloak>

        {{-- ── Breadcrumb ── --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
                <a href="{{ route('admin.storefront-sections.index') }}" class="hover:text-brand-600 transition-colors">
                    Storefront Sections
                </a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('admin.storefront-sections.edit', $section->id) }}"
                    class="hover:text-brand-600 transition-colors truncate max-w-[200px]">
                    {{ $section->title }}
                </a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-gray-700 font-semibold">Manage Products</span>
            </div>
            <a href="{{ route('admin.storefront-sections.edit', $section->id) }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Section
            </a>
        </div>

        {{-- ── Stats strip ── --}}
        <div class="mb-5 flex flex-wrap items-center gap-3 text-sm text-gray-500 font-medium">
            <span class="flex items-center gap-1.5">
                <i data-lucide="list-checks" class="w-4 h-4 text-brand-600"></i>
                <span x-text="assignedProducts.length + ' products in section'"></span>
            </span>
            <span class="text-gray-300">·</span>
            <span>Max <span class="font-bold text-gray-700">{{ $section->products_limit }}</span> allowed</span>
            <template x-if="atLimit">
                <span class="text-amber-600 font-semibold flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    Limit reached — remove items before adding more
                </span>
            </template>
            <template x-if="!atLimit && remainingSlots < productsLimit">
                <span class="text-gray-400">
                    · <span x-text="remainingSlots"></span> slot(s) remaining
                </span>
            </template>
        </div>

        {{-- ── Two-panel layout ── --}}
        <div class="merch-wrap">

            {{-- ════════════════════════════
                 LEFT — Assigned products
            ════════════════════════════ --}}
            <div class="panel">
                <div class="panel-header">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-800">Products in Section</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5"
                                x-text="`${assignedProducts.length} product${assignedProducts.length !== 1 ? 's' : ''} assigned`">
                            </p>
                        </div>
                        <div x-show="assignedProducts.length > 1" x-cloak
                            class="flex items-center gap-1.5 text-[11px] text-gray-400 font-medium bg-gray-50 px-3 py-1.5 rounded-lg">
                            <i data-lucide="grip-vertical" class="w-3.5 h-3.5"></i>
                            Drag to reorder
                        </div>
                    </div>
                </div>

                <div class="panel-body">
                    {{-- Loading ── --}}
                    <template x-if="loadingAssigned">
                        <div class="empty-state">
                            <div class="spinner mb-3"></div>
                            <p class="text-sm text-gray-400 font-medium">Loading products...</p>
                        </div>
                    </template>

                    {{-- Empty ── --}}
                    <template x-if="!loadingAssigned && assignedProducts.length === 0">
                        <div class="empty-state">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                                <i data-lucide="package-plus" class="w-6 h-6 text-gray-300"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-500">No products yet</p>
                            <p class="text-xs text-gray-400 mt-1">Search and add from the right panel</p>
                        </div>
                    </template>

                    {{-- List ── --}}
                    <template x-if="!loadingAssigned && assignedProducts.length > 0">
                        <div id="section-assigned-list">
                            <template x-for="(prod, index) in assignedProducts" :key="prod.product_id">
                                <div class="product-row" :data-product-id="prod.product_id"
                                    :class="{ 'opacity-60': removingIds.includes(prod.product_id) }">

                                    <div class="drag-handle sortable-handle">
                                        <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                                    </div>

                                    <span class="order-chip" x-text="index + 1"></span>

                                    <img :src="prod.image" class="prod-thumb"
                                        onerror="this.onerror=null; this.src='/assets/defaults/product.svg';">

                                    <div class="flex-1 min-w-0">
                                        <p class="prod-name" x-text="prod.name"></p>
                                        <p class="prod-meta">
                                            ₹<span x-text="parseFloat(prod.price || 0).toFixed(2)"></span>
                                            <template x-if="prod.sku">
                                                <span class="ml-1 opacity-60" x-text="'· ' + prod.sku"></span>
                                            </template>
                                        </p>
                                    </div>

                                    <button type="button" class="row-action btn-remove" title="Remove from section"
                                        @click="removeProduct(prod)" :disabled="removingIds.includes(prod.product_id)">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ════════════════════════════
                 RIGHT — Search & multi-select
            ════════════════════════════ --}}
            <div class="panel">
                <div class="panel-header">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold text-gray-800">Available Products</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                                Search and select products to add
                            </p>
                        </div>
                        <button type="button" class="btn-add-selected"
                            :disabled="selectedCount === 0 || isBulkAdding || atLimit" @click="addSelected()">
                            <template x-if="isBulkAdding">
                                <div class="spinner" style="width:12px;height:12px;border-width:2px;"></div>
                            </template>
                            <template x-if="!isBulkAdding">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            </template>
                            <span
                                x-text="isBulkAdding ? 'Adding...' : ('Add Selected' + (selectedCount > 0 ? ' (' + selectedCount + ')' : ''))"></span>
                        </button>
                    </div>

                    <div class="search-wrap">
                        <i data-lucide="search" class="w-4 h-4 search-icon"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.350ms="searchProducts()"
                            placeholder="Search by name or SKU..." class="search-input">
                    </div>
                </div>

                {{-- Limit banner ── --}}
                <template x-if="atLimit">
                    <div class="limit-banner">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        Section is full. Remove items on the left to free slots.
                    </div>
                </template>

                {{-- Select-all bar ── --}}
                <div class="avail-filter-bar" x-show="searchResults.length > 0" x-cloak>
                    <div class="flex items-center gap-2 cursor-pointer select-none" @click="toggleSelectAll()">
                        <span class="pick-checkbox" :class="{ 'checked': allSelected }">
                            <template x-if="allSelected">
                                <i data-lucide="check" class="w-3 h-3"></i>
                            </template>
                        </span>
                        <span class="text-xs font-bold text-gray-600"
                            x-text="allSelected ? 'Clear All' : 'Select All'"></span>
                    </div>
                    <span class="selected-count-chip" x-show="selectedCount > 0" x-cloak
                        x-text="selectedCount + ' selected'"></span>
                </div>

                <div class="panel-body">
                    {{-- Loading ── --}}
                    <template x-if="searching">
                        <div class="empty-state">
                            <div class="spinner mb-3"></div>
                            <p class="text-xs text-gray-400 font-medium">Searching...</p>
                        </div>
                    </template>

                    {{-- Results ── --}}
                    <template x-if="!searching && searchResults.length > 0">
                        <div>
                            <template x-for="prod in searchResults" :key="prod.product_id">
                                <div class="avail-row"
                                    :class="{
                                        'selected': selectedIds.includes(prod.product_id),
                                        'disabled': atLimit && !selectedIds.includes(prod.product_id)
                                    }"
                                    @click="atLimit && !selectedIds.includes(prod.product_id) ? null : toggleSelect(prod.product_id)">
                                    <span class="pick-checkbox"
                                        :class="{ 'checked': selectedIds.includes(prod.product_id) }">
                                        <template x-if="selectedIds.includes(prod.product_id)">
                                            <i data-lucide="check" class="w-3 h-3"></i>
                                        </template>
                                    </span>
                                    <img :src="prod.image" class="avail-thumb"
                                        onerror="this.onerror=null; this.src='/assets/defaults/product.svg';">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate" x-text="prod.name"></p>
                                        <p class="text-[11px] text-gray-400">
                                            ₹<span x-text="parseFloat(prod.price || 0).toFixed(2)"></span>
                                            <template x-if="prod.sku">
                                                <span class="ml-1 opacity-60" x-text="'· ' + prod.sku"></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- No results ── --}}
                    <template x-if="!searching && searchQuery.length > 0 && searchResults.length === 0">
                        <div class="empty-state">
                            <i data-lucide="search-x" class="w-8 h-8 mb-2 text-gray-200"></i>
                            <p class="text-sm font-bold text-gray-500">No products found</p>
                            <p class="text-xs text-gray-400 mt-1">Try a different search term</p>
                        </div>
                    </template>

                    {{-- Default prompt ── --}}
                    <template x-if="!searching && searchQuery.length === 0 && searchResults.length === 0">
                        <div class="empty-state">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                                <i data-lucide="search" class="w-6 h-6 text-gray-300"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-500">Search products</p>
                            <p class="text-xs text-gray-400 mt-1">Type a name or SKU to find products</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <script>
        function sectionProducts() {
            return {
                // ── Config ──
                sectionId: {{ $section->id }},
                productsLimit: {{ (int) $section->products_limit }},

                // ── Left panel state ──
                assignedProducts: [],
                loadingAssigned: false,
                removingIds: [],
                sortableInstance: null,

                // ── Right panel state ──
                searchQuery: '',
                searchResults: [],
                searching: false,
                selectedIds: [],
                isBulkAdding: false,

                // ── Computed ──
                get selectedCount() {
                    return this.selectedIds.length;
                },
                get allSelected() {
                    return this.searchResults.length > 0 &&
                        this.searchResults.every(p => this.selectedIds.includes(p.product_id));
                },
                get atLimit() {
                    return this.assignedProducts.length >= this.productsLimit;
                },
                get remainingSlots() {
                    return Math.max(0, this.productsLimit - this.assignedProducts.length);
                },

                // ════════════════════════════════════════
                //  INIT
                // ════════════════════════════════════════
                init() {
                    this.loadAssigned();

                    this.$watch('searchResults', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
                    this.$watch('assignedProducts', () => this.$nextTick(() => window.lucide && lucide.createIcons()));

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') this.selectedIds = [];
                    });

                    console.log('[SectionProducts] Init section #' + this.sectionId + ', limit=' + this.productsLimit);
                },

                // ════════════════════════════════════════
                //  LOAD ASSIGNED
                // ════════════════════════════════════════
                async loadAssigned() {
                    this.loadingAssigned = true;

                    try {
                        const res = await fetch(
                            '{{ route('admin.storefront-sections.products.load', $section->id) }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                            });
                        const data = await res.json();

                        if (data.success) {
                            this.assignedProducts = data.products;
                            this.$nextTick(() => {
                                this.$nextTick(() => {
                                    this.initSortable();
                                    window.lucide && lucide.createIcons();
                                });
                            });
                        } else {
                            BizAlert.toast('Failed to load products.', 'error');
                        }
                    } catch (err) {
                        console.error('[SectionProducts] Load error:', err);
                        BizAlert.toast('Network error loading products.', 'error');
                    } finally {
                        this.loadingAssigned = false;
                    }
                },

                // ════════════════════════════════════════
                //  SEARCH
                // ════════════════════════════════════════
                async searchProducts() {
                    const q = this.searchQuery.trim();

                    if (q.length === 0) {
                        this.searchResults = [];
                        this.selectedIds = [];
                        return;
                    }

                    this.searching = true;

                    try {
                        const url = '{{ route('admin.storefront-sections.products.search', $section->id) }}?q=' +
                            encodeURIComponent(q);
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                        });
                        const data = await res.json();

                        if (data.success) {
                            this.searchResults = data.products;
                            // Drop any stale selections no longer in results
                            const resultIds = new Set(data.products.map(p => p.product_id));
                            this.selectedIds = this.selectedIds.filter(id => resultIds.has(id));
                        }
                    } catch (err) {
                        console.error('[SectionProducts] Search error:', err);
                        BizAlert.toast('Search failed.', 'error');
                    } finally {
                        this.searching = false;
                    }
                },

                // ════════════════════════════════════════
                //  BULK SELECTION
                // ════════════════════════════════════════
                toggleSelect(pid) {
                    this.selectedIds = this.selectedIds.includes(pid) ?
                        this.selectedIds.filter(x => x !== pid) : [...this.selectedIds, pid];
                },

                toggleSelectAll() {
                    if (this.allSelected) {
                        this.selectedIds = [];
                    } else {
                        // Only auto-select up to remaining slots, to avoid confusion
                        const cap = this.remainingSlots;
                        const ids = this.searchResults.map(p => p.product_id).slice(0, cap);
                        this.selectedIds = [...new Set(ids)];
                    }
                },

                // ════════════════════════════════════════
                //  ADD SELECTED (sequential — no bulk endpoint exists)
                // ════════════════════════════════════════
                async addSelected() {
                    if (this.selectedCount === 0 || this.isBulkAdding) return;

                    if (this.atLimit) {
                        BizAlert.toast('Section is full. Remove items first.', 'warning');
                        return;
                    }

                    if (this.selectedCount > this.remainingSlots) {
                        BizAlert.toast(
                            `Only ${this.remainingSlots} slot(s) remaining. Deselect ${this.selectedCount - this.remainingSlots} to continue.`,
                            'warning'
                        );
                        return;
                    }

                    this.isBulkAdding = true;
                    const ids = [...this.selectedIds];
                    const addUrl = '{{ route('admin.storefront-sections.products.add', $section->id) }}';
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;

                    let added = 0;
                    const failed = [];

                    for (const pid of ids) {
                        try {
                            const res = await fetch(addUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    product_id: pid
                                }),
                            });
                            const data = await res.json();

                            if (data.success) {
                                this.assignedProducts.push({
                                    product_id: data.product_id,
                                    name: data.name,
                                    image: data.image,
                                    price: data.price,
                                    sku: data.sku ?? null,
                                });
                                added++;
                            } else {
                                failed.push(pid);
                            }
                        } catch (err) {
                            console.error('[SectionProducts] Add failed for', pid, err);
                            failed.push(pid);
                        }
                    }

                    // Remove successfully added from search results
                    const failedSet = new Set(failed);
                    this.searchResults = this.searchResults.filter(p =>
                        !ids.includes(p.product_id) || failedSet.has(p.product_id)
                    );
                    this.selectedIds = [];

                    this.$nextTick(() => {
                        this.initSortable();
                        window.lucide && lucide.createIcons();
                    });

                    if (added > 0) {
                        BizAlert.toast(
                            `${added} product${added !== 1 ? 's' : ''} added to section${failed.length ? ` (${failed.length} failed)` : ''}.`,
                            failed.length ? 'warning' : 'success'
                        );
                    } else {
                        BizAlert.toast('Failed to add products.', 'error');
                    }

                    this.isBulkAdding = false;
                },

                // ════════════════════════════════════════
                //  REMOVE
                // ════════════════════════════════════════
                async removeProduct(prod) {
                    const productId = prod.product_id;
                    const name = prod.name || 'this product';

                    const result = await BizAlert.confirm(
                        `Remove "${name}"?`,
                        'It will be removed from this section. The product itself remains in your catalog.',
                        'Yes, Remove'
                    );
                    if (!result.isConfirmed) return;

                    this.removingIds.push(productId);

                    try {
                        const res = await fetch(
                            `{{ url('admin/storefront-sections/' . $section->id . '/products') }}/${productId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                            }
                        );
                        const data = await res.json();

                        if (data.success) {
                            this.assignedProducts = this.assignedProducts.filter(p => p.product_id !== productId);
                            BizAlert.toast('Product removed.', 'success');

                            // Refresh search so the removed item becomes re-addable
                            if (this.searchQuery.trim().length > 0) {
                                this.searchProducts();
                            }
                        } else {
                            BizAlert.toast(data.message || 'Failed to remove.', 'error');
                        }
                    } catch (err) {
                        console.error('[SectionProducts] Remove error:', err);
                        BizAlert.toast('Network error.', 'error');
                    } finally {
                        this.removingIds = this.removingIds.filter(id => id !== productId);
                    }
                },

                // ════════════════════════════════════════
                //  SORTABLE — auto-save on drop
                // ════════════════════════════════════════
                initSortable() {
                    const el = document.getElementById('section-assigned-list');
                    if (!el) return;

                    if (this.sortableInstance) {
                        this.sortableInstance.destroy();
                        this.sortableInstance = null;
                    }

                    this.sortableInstance = Sortable.create(el, {
                        animation: 200,
                        handle: '.sortable-handle',
                        ghostClass: 'sortable-ghost-row',
                        dragClass: 'is-dragging',

                        onEnd: async (evt) => {
                            if (evt.oldIndex === evt.newIndex) return;

                            const moved = this.assignedProducts.splice(evt.oldIndex, 1)[0];
                            this.assignedProducts.splice(evt.newIndex, 0, moved);

                            const productIds = this.assignedProducts.map(p => p.product_id);

                            try {
                                const res = await fetch(
                                    '{{ route('admin.storefront-sections.products.reorder', $section->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content,
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            product_ids: productIds
                                        }),
                                    }
                                );
                                const data = await res.json();
                                if (data.success) {
                                    BizAlert.toast('Order saved!', 'success');
                                } else {
                                    BizAlert.toast('Failed to save order.', 'error');
                                }
                            } catch (err) {
                                console.error('[SectionProducts] Reorder error:', err);
                                BizAlert.toast('Network error saving order.', 'error');
                            }
                        },
                    });
                },
            };
        }
    </script>
@endpush
