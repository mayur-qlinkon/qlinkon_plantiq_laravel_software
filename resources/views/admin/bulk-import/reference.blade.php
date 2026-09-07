@extends('layouts.admin')

@section('title', 'Import Reference')

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Import Reference</h1>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .ref-tab-active   { border-bottom: 2.5px solid var(--brand-600); color: #111827; font-weight: 900; }
    .ref-tab-inactive { color: #6b7280; font-weight: 700; }
    .ref-tab-inactive:hover { color: #374151; background: #f9fafb; }

    .copy-val {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 5px;
        padding: 2px 7px;
        cursor: pointer;
        transition: background 120ms, border-color 120ms;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        user-select: none;
    }
    .copy-val:hover  { background: #e0f2fe; border-color: #7dd3fc; }
    .copy-val.copied { background: #d1fae5; border-color: #6ee7b7; color: #065f46; }

    .ref-table th { position: sticky; top: 0; background: #f9fafb; z-index: 2; }
    .ref-scroll   { max-height: calc(100vh - 240px); overflow-y: auto; }

    @media (max-width: 640px) {
        .ref-scroll { max-height: calc(100vh - 200px); }
    }
</style>
@endpush

@section('content')

@php
    $catJson  = json_encode($categories->values(),  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $unitJson = json_encode($units->values(),       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $whJson   = json_encode($warehouses->values(),  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $prodJson = json_encode($products->values(),    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    $attrJson = json_encode($attributes->values(),  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp

<div x-data="referencePanel(
    {{ $catJson }},
    {{ $unitJson }},
    {{ $whJson }},
    {{ $prodJson }},
    {{ $attrJson }}
)" x-cloak class="w-full">

    {{-- ── Top bar ── --}}
    <div class="flex items-center justify-between mb-4 gap-3">
        <p class="text-[12px] text-gray-500 leading-relaxed max-w-xl">
            Keep this page open while writing your CSV.
            <strong class="text-gray-700">Click any value</strong> to copy it instantly.
        </p>
        <a href="{{ route('admin.bulk-import.index') }}"
            class="shrink-0 inline-flex items-center gap-1.5 text-[12px] font-bold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to Import
        </a>
    </div>

    {{-- ── Main card ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Tabs + search row ── --}}
        <div class="flex flex-col sm:flex-row sm:items-center border-b border-gray-100 gap-0">

            {{-- Tab buttons ── --}}
            <div class="flex overflow-x-auto no-scrollbar shrink-0">
                <template x-for="tab in tabs" :key="tab.key">
                    <button @click="switchTab(tab.key)"
                        class="flex items-center gap-1.5 px-4 py-3.5 text-[12px] whitespace-nowrap transition-colors"
                        :class="activeTab === tab.key ? 'ref-tab-active' : 'ref-tab-inactive'">
                        <i :data-lucide="tab.icon" class="w-3.5 h-3.5"></i>
                        <span x-text="tab.label"></span>
                        <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                            :class="activeTab === tab.key
                                ? 'text-white'
                                : 'bg-gray-100 text-gray-500'"
                            :style="activeTab === tab.key ? 'background: var(--brand-600)' : ''"
                            x-text="tab.count"></span>
                    </button>
                </template>
            </div>

            {{-- Search ── --}}
            <div class="flex-1 px-4 py-2 sm:border-l border-t sm:border-t-0 border-gray-100">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"></i>
                    <input x-model="search" type="text" placeholder="Search…"
                        class="w-full pl-8 pr-8 py-1.5 text-[12px] bg-gray-50 border border-gray-200 rounded-lg outline-none focus:border-blue-300 focus:bg-white transition-colors"
                        @keydown.escape="search = ''">
                    <button x-show="search" @click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>

        </div>

        {{-- ── CATEGORIES ── --}}
        <div x-show="activeTab === 'categories'" class="ref-scroll">
            <div x-show="filteredCategories.length === 0" class="py-12 text-center text-[12px] text-gray-400">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                No categories match "<span x-text="search"></span>"
            </div>
            <table class="ref-table w-full text-left" x-show="filteredCategories.length > 0">
                <thead>
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-4 py-2.5 border-b border-gray-100">Name</th>
                        <th class="px-4 py-2.5 border-b border-gray-100">slug <span class="normal-case font-normal text-gray-400">(use in CSV)</span></th>
                        <th class="px-4 py-2.5 border-b border-gray-100">Parent slug</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(cat, i) in filteredCategories" :key="i">
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-2.5 text-[12px] font-bold text-gray-700" x-text="cat.name"></td>
                            <td class="px-4 py-2.5">
                                <button class="copy-val" :class="copiedKey === 'cat-slug-'+i ? 'copied' : ''"
                                    @click="copy(cat.slug, 'cat-slug-'+i)" :title="'Click to copy: ' + cat.slug">
                                    <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                    <span x-text="cat.slug"></span>
                                </button>
                            </td>
                            <td class="px-4 py-2.5">
                                <template x-if="cat.parent_slug">
                                    <button class="copy-val" :class="copiedKey === 'cat-par-'+i ? 'copied' : ''"
                                        @click="copy(cat.parent_slug, 'cat-par-'+i)">
                                        <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                        <span x-text="cat.parent_slug"></span>
                                    </button>
                                </template>
                                <template x-if="!cat.parent_slug">
                                    <span class="text-[11px] text-gray-300">—</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ── UNITS ── --}}
        <div x-show="activeTab === 'units'" class="ref-scroll">
            <div x-show="filteredUnits.length === 0" class="py-12 text-center text-[12px] text-gray-400">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                No units match "<span x-text="search"></span>"
            </div>
            <table class="ref-table w-full text-left" x-show="filteredUnits.length > 0">
                <thead>
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-4 py-2.5 border-b border-gray-100">Name</th>
                        <th class="px-4 py-2.5 border-b border-gray-100">short_name <span class="normal-case font-normal text-gray-400">(use in CSV)</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(unit, i) in filteredUnits" :key="i">
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-2.5 text-[12px] font-bold text-gray-700" x-text="unit.name"></td>
                            <td class="px-4 py-2.5">
                                <template x-if="unit.short_name">
                                    <button class="copy-val" :class="copiedKey === 'unit-'+i ? 'copied' : ''"
                                        @click="copy(unit.short_name, 'unit-'+i)" :title="'Click to copy: ' + unit.short_name">
                                        <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                        <span x-text="unit.short_name"></span>
                                    </button>
                                </template>
                                <template x-if="!unit.short_name">
                                    <span class="text-[11px] text-gray-300 italic">no short name</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ── WAREHOUSES ── --}}
        <div x-show="activeTab === 'warehouses'" class="ref-scroll">
            <div x-show="filteredWarehouses.length === 0" class="py-12 text-center text-[12px] text-gray-400">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                No warehouses match "<span x-text="search"></span>"
            </div>
            <table class="ref-table w-full text-left" x-show="filteredWarehouses.length > 0">
                <thead>
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-4 py-2.5 border-b border-gray-100">warehouse_name <span class="normal-case font-normal text-gray-400">(use in CSV — exact spelling)</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(wh, i) in filteredWarehouses" :key="i">
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-2.5">
                                <button class="copy-val" :class="copiedKey === 'wh-'+i ? 'copied' : ''"
                                    @click="copy(wh.name, 'wh-'+i)" :title="'Click to copy: ' + wh.name">
                                    <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                    <span x-text="wh.name"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ── PRODUCTS ── --}}
        <div x-show="activeTab === 'products'" class="ref-scroll">
            <div x-show="filteredProducts.length === 0" class="py-12 text-center text-[12px] text-gray-400">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                No products match "<span x-text="search"></span>"
            </div>

            <table class="ref-table w-full text-left" x-show="filteredProducts.length > 0">
                <thead>
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-4 py-2.5 border-b border-gray-100 w-8"></th>
                        <th class="px-4 py-2.5 border-b border-gray-100">Product name</th>
                        <th class="px-4 py-2.5 border-b border-gray-100">slug <span class="normal-case font-normal text-gray-400">(use in CSV)</span></th>
                        <th class="px-4 py-2.5 border-b border-gray-100">SKUs</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(prod, pi) in filteredProducts" :key="pi">
                        <tbody class="contents">
                            {{-- Product row ── --}}
                            <tr class="hover:bg-gray-50/40 transition-colors border-t border-gray-100 cursor-pointer"
                                @click="toggleProduct(pi)">
                                <td class="px-4 py-2.5 text-gray-400">
                                    <i :data-lucide="expandedProducts.includes(pi) ? 'chevron-down' : 'chevron-right'"
                                        class="w-3.5 h-3.5 transition-transform duration-150"></i>
                                </td>
                                <td class="px-4 py-2.5 text-[12px] font-bold text-gray-800" x-text="prod.name"></td>
                                <td class="px-4 py-2.5">
                                    <button class="copy-val" :class="copiedKey === 'prod-slug-'+pi ? 'copied' : ''"
                                        @click.stop="copy(prod.slug, 'prod-slug-'+pi)"
                                        :title="'Click to copy: ' + prod.slug">
                                        <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                        <span x-text="prod.slug"></span>
                                    </button>
                                </td>
                                <td class="px-4 py-2.5 text-[11px] text-gray-400">
                                    <span x-show="prod.skus.length > 0"
                                        x-text="prod.skus.length + ' SKU' + (prod.skus.length > 1 ? 's' : '')"
                                        class="dep-pill bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full font-bold">
                                    </span>
                                    <span x-show="prod.skus.length === 0" class="text-gray-300">no SKUs</span>
                                </td>
                            </tr>

                            {{-- Expanded SKU rows ── --}}
                            <template x-if="expandedProducts.includes(pi) && prod.skus.length > 0">
                                <template x-for="(sku, si) in prod.skus" :key="si">
                                    <tr class="bg-gray-50/70 border-t border-dashed border-gray-100">
                                        <td class="px-4 py-1.5"></td>
                                        <td class="px-4 py-1.5">
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">SKU</span>
                                        </td>
                                        <td class="px-4 py-1.5">
                                            <button class="copy-val text-[10px]"
                                                :class="copiedKey === 'sku-code-'+pi+'-'+si ? 'copied' : ''"
                                                @click.stop="copy(sku.sku, 'sku-code-'+pi+'-'+si)"
                                                :title="'Click to copy: ' + sku.sku">
                                                <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                                <span x-text="sku.sku"></span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-1.5">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-[11px] font-bold text-gray-500"
                                                    x-text="'₹' + Number(sku.price).toLocaleString()"></span>
                                                <template x-if="sku.attrs">
                                                    <span class="text-[10px] text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full"
                                                        x-text="sku.attrs"></span>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </template>
                </tbody>
            </table>
        </div>


        {{-- ── ATTRIBUTES ── --}}
        <div x-show="activeTab === 'attributes'" class="ref-scroll">
            <div x-show="filteredAttributes.length === 0" class="py-12 text-center text-[12px] text-gray-400">
                <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-gray-200"></i>
                No attributes match "<span x-text="search"></span>"
            </div>
            <table class="ref-table w-full text-left" x-show="filteredAttributes.length > 0">
                <thead>
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-4 py-2.5 border-b border-gray-100">Attribute Name</th>
                        <th class="px-4 py-2.5 border-b border-gray-100">Type</th>
                        <th class="px-4 py-2.5 border-b border-gray-100">Values</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(attr, ai) in filteredAttributes" :key="ai">
                        <tr class="hover:bg-gray-50/60 transition-colors align-top">
                            <td class="px-4 py-2.5 text-[12px] font-bold text-gray-700" x-text="attr.name"></td>
                            <td class="px-4 py-2.5">
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
                                    x-text="attr.type || '—'"></span>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="(val, vi) in attr.values" :key="vi">
                                        <div class="flex items-center gap-1">
                                            <template x-if="val.color_code">
                                                <span class="w-3 h-3 rounded-full border border-gray-200 shrink-0"
                                                    :style="'background:' + val.color_code"></span>
                                            </template>
                                            <button class="copy-val"
                                                :class="copiedKey === 'attr-val-'+ai+'-'+vi ? 'copied' : ''"
                                                @click="copy(val.value, 'attr-val-'+ai+'-'+vi)"
                                                :title="'Click to copy: ' + val.value">
                                                <i data-lucide="copy" class="w-3 h-3 shrink-0 opacity-40"></i>
                                                <span x-text="val.value"></span>
                                            </button>
                                        </div>
                                    </template>
                                    <span x-show="attr.values.length === 0" class="text-[11px] text-gray-300">No values</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Footer hint ── --}}
        <div class="border-t border-gray-100 px-4 py-2.5 flex items-center gap-2 bg-gray-50/50">
            <i data-lucide="mouse-pointer-click" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
            <p class="text-[11px] text-gray-400">Click any <code class="bg-gray-100 px-1 rounded text-[10px]">monospace</code> value to copy it to clipboard.</p>
            {{-- Toast ── --}}
            <div x-show="toastVisible" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="ml-auto flex items-center gap-1.5 bg-emerald-500 text-white text-[11px] font-bold px-3 py-1 rounded-full">
                <i data-lucide="check" class="w-3 h-3"></i>
                Copied!
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function referencePanel(categories, units, warehouses, products, attributes) {
    return {
        activeTab: 'categories',
        search: '',
        copiedKey: null,
        toastVisible: false,
        toastTimer: null,
        expandedProducts: [],

        tabs: [
            { key: 'categories', label: 'Categories', icon: 'layers',    count: categories.length  },
            { key: 'units',      label: 'Units',      icon: 'ruler',     count: units.length       },
            { key: 'products',   label: 'Products',   icon: 'package',   count: products.length    },
            { key: 'attributes', label: 'Attributes', icon: 'tag',       count: attributes.length  },
            { key: 'warehouses', label: 'Warehouses', icon: 'warehouse', count: warehouses.length  },
        ],

        allCategories:  categories,
        allUnits:       units,
        allWarehouses:  warehouses,
        allProducts:    products,
        allAttributes:  attributes,

        switchTab(key) {
            this.activeTab = key;
            this.search = '';
            this.expandedProducts = [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        toggleProduct(index) {
            const pos = this.expandedProducts.indexOf(index);
            if (pos === -1) {
                this.expandedProducts.push(index);
            } else {
                this.expandedProducts.splice(pos, 1);
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get filteredCategories() {
            if (!this.search.trim()) return this.allCategories;
            const q = this.search.toLowerCase();
            return this.allCategories.filter(c =>
                c.name?.toLowerCase().includes(q) ||
                c.slug?.toLowerCase().includes(q) ||
                c.parent_slug?.toLowerCase().includes(q)
            );
        },
        get filteredUnits() {
            if (!this.search.trim()) return this.allUnits;
            const q = this.search.toLowerCase();
            return this.allUnits.filter(u =>
                u.name?.toLowerCase().includes(q) ||
                u.short_name?.toLowerCase().includes(q)
            );
        },
        get filteredWarehouses() {
            if (!this.search.trim()) return this.allWarehouses;
            const q = this.search.toLowerCase();
            return this.allWarehouses.filter(w => w.name?.toLowerCase().includes(q));
        },
        get filteredAttributes() {
            if (!this.search.trim()) return this.allAttributes;
            const q = this.search.toLowerCase();
            return this.allAttributes.filter(a =>
                a.name?.toLowerCase().includes(q) ||
                a.type?.toLowerCase().includes(q) ||
                a.values?.some(v => v.value?.toLowerCase().includes(q))
            );
        },
        get filteredProducts() {
            if (!this.search.trim()) return this.allProducts;
            const q = this.search.toLowerCase();
            return this.allProducts.filter(p =>
                p.name?.toLowerCase().includes(q) ||
                p.slug?.toLowerCase().includes(q) ||
                p.skus?.some(s =>
                    s.sku?.toLowerCase().includes(q) ||
                    s.attrs?.toLowerCase().includes(q)
                )
            );
        },

        async copy(text, key) {
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                // fallback
                const el = document.createElement('textarea');
                el.value = text; el.style.position = 'fixed'; el.style.opacity = '0';
                document.body.appendChild(el); el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            this.copiedKey = key;
            this.toastVisible = true;
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastVisible = false;
                this.copiedKey    = null;
            }, 1500);
        },
    };
}
</script>
@endpush