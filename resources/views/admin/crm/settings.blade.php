@extends('layouts.admin')

@section('title', 'CRM Settings — Sources & Tags')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">CRM Settings</h1>        
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .field-input {
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 13px;
        color: #1f2937;
        outline: none;
        font-family: inherit;
        background: #fff;
        transition: border-color 150ms ease;
    }

    .field-input:focus { border-color: var(--brand-600); }

    .item-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 11px;
        transition: border-color 150ms, box-shadow 150ms;
    }

    .item-row:hover {
        border-color: #e2e8f0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }

    .edit-row {
        padding: 12px 14px;
        background: #f8fafc;
        border: 1.5px solid var(--brand-600);
        border-radius: 11px;
    }

    .color-swatch {
        width: 22px; height: 22px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 150ms, border-color 150ms;
        flex-shrink: 0;
    }

    .color-swatch:hover  { transform: scale(1.2); }
    .color-swatch.active { border-color: #1f2937; transform: scale(1.1); }

    .toggle-track {
        position: relative;
        width: 36px; height: 20px;
        background: #d1d5db;
        border-radius: 10px;
        cursor: pointer;
        transition: background 150ms;
        flex-shrink: 0;
    }

    .toggle-track.on { background: #22c55e; }

    .toggle-thumb {
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: #fff;
        transition: transform 150ms;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .toggle-track.on .toggle-thumb { transform: translateX(16px); }

    .tab-btn {
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #6b7280;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: background 120ms, color 120ms;
    }

    .tab-btn.active {
        background: var(--brand-600);
        color: #fff;
    }

    .tab-btn:not(.active):hover {
        background: #f3f4f6;
        color: #374151;
    }
</style>
@endpush

@section('content')

@php
    $tagColors = ['#6b7280','#3b82f6','#8b5cf6','#f59e0b','#f97316','#10b981','#ef4444','#ec4899','#06b6d4'];
@endphp

<div class="pb-10 w-full" x-data="crmSettingsPage()">

    {{-- ── Breadcrumb ── --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 font-medium mb-5">
        <a href="{{ route('admin.crm.pipelines.index') }}"
            class="hover:text-gray-700 transition-colors">Pipelines</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-gray-700 font-semibold">Settings</span>
    </div>

    {{-- ── Feedback ── --}}
    <template x-if="pageSuccess">
        <div class="mb-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-green-800" x-text="pageSuccess"></p>
        </div>
    </template>
    <template x-if="pageError">
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-red-700" x-text="pageError"></p>
        </div>
    </template>

    {{-- ── Search & Tabs Bar ── --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        
        {{-- Search Input (Left) --}}
        <div class="relative w-full max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
            </div>
            <input type="text" x-model="searchQuery" :placeholder="tab === 'sources' ? 'Search sources...' : 'Search tags...'"
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 sm:text-sm transition-colors">
        </div>

        {{-- Tabs (Right) --}}
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-xl p-1.5 w-full sm:w-auto shrink-0 overflow-x-auto">
            <button @click="tab = 'sources'; searchQuery = ''"
                class="tab-btn whitespace-nowrap"
                :class="tab === 'sources' ? 'active' : ''">
                <span class="flex items-center gap-2">
                    <i data-lucide="radio-tower" class="w-4 h-4"></i>
                    Sources
                    <span class="text-[11px] font-bold px-1.5 py-0.5 rounded-full"
                        :class="tab === 'sources' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'"
                        x-text="sources.length"></span>
                </span>
            </button>
            <button @click="tab = 'tags'; searchQuery = ''"
                class="tab-btn whitespace-nowrap"
                :class="tab === 'tags' ? 'active' : ''">
                <span class="flex items-center gap-2">
                    <i data-lucide="tag" class="w-4 h-4"></i>
                    Tags
                    <span class="text-[11px] font-bold px-1.5 py-0.5 rounded-full"
                        :class="tab === 'tags' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'"
                        x-text="tags.length"></span>
                </span>
            </button>
        </div>
        
    </div>

    {{-- ═══════════════════════════════
         SOURCES TAB
    ═══════════════════════════════ --}}
    <div x-show="tab === 'sources'" class="space-y-3">

        {{-- List ── --}}
        <div class="space-y-2">
            <template x-for="source in sources" :key="source.id">
                <div x-show="searchQuery === '' || source.name.toLowerCase().includes(searchQuery.toLowerCase())" x-transition.opacity>
                    {{-- View row ── --}}
                    <div x-show="sourceEditingId !== source.id" class="item-row">

                        {{-- Icon ── --}}
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            style="background: var(--brand-50)">
                            <i :data-lucide="source.icon || 'radio-tower'"
                                class="w-4 h-4"
                                style="color: var(--brand-600)"></i>
                        </div>

                        {{-- Name ── --}}
                        <span class="flex-1 text-[14px] font-bold text-gray-800"
                            x-text="source.name"></span>

                        {{-- Lead count ── --}}
                        <span class="text-[12px] text-gray-400 font-medium"
                            x-text="(source.leads_count ?? 0) + ' lead' + ((source.leads_count ?? 0) !== 1 ? 's' : '')">
                        </span>

                        {{-- Active toggle ── --}}
                        <div class="toggle-track"
                            :class="source.is_active ? 'on' : ''"
                            @click="toggleSourceActive(source)"
                            title="Toggle active">
                            <div class="toggle-thumb"></div>
                        </div>

                        {{-- Actions ── --}}
                        <div class="flex items-center gap-1">
                            @if(has_permission('crm_sources.update'))
                            <button @click="startEditSource(source)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                            @if(has_permission('crm_sources.delete'))
                            <button @click="deleteSource(source.id, source.name)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Inline edit ── --}}
                    <div x-show="sourceEditingId === source.id" x-cloak class="edit-row space-y-3">
                        <template x-if="sourceFormError">
                            <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                <p class="text-[12px] font-semibold text-red-600" x-text="sourceFormError"></p>
                            </div>
                        </template>
                        <div class="flex items-center gap-3 flex-wrap">
                            <input type="text"
                                x-model="sourceEditForm.name"
                                placeholder="Source name"
                                class="field-input flex-1 min-w-[140px]"
                                @keydown.enter.prevent="saveEditSource(source.id)"
                                @keydown.escape="cancelEditSource()">
                            <input type="text"
                                x-model="sourceEditForm.icon"
                                placeholder="lucide icon (e.g. phone)"
                                class="field-input w-44"
                                @keydown.enter.prevent="saveEditSource(source.id)">
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="saveEditSource(source.id)"
                                :disabled="sourceSaving || !sourceEditForm.name.trim()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[12px] font-bold text-white hover:opacity-90 transition-opacity"
                                style="background: var(--brand-600)"
                                :class="sourceSaving ? 'opacity-60 cursor-not-allowed' : ''">
                                <i data-lucide="loader-2" x-show="sourceSaving" class="w-3.5 h-3.5 animate-spin"></i>
                                <i data-lucide="save"     x-show="!sourceSaving" class="w-3.5 h-3.5"></i>
                                <span x-text="sourceSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                            <button @click="cancelEditSource()"
                                class="px-4 py-2 rounded-lg text-[12px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Add Source form ── --}}
        @if(has_permission('crm_sources.create'))
        <div class="bg-white border border-gray-100 rounded-2xl p-5 mt-2">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Add Lead Source</p>

            <template x-if="sourceAddError">
                <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-3">
                    <p class="text-[12px] font-semibold text-red-600" x-text="sourceAddError"></p>
                </div>
            </template>

            <div class="flex items-center gap-3 flex-wrap mb-3">
                <input type="text"
                    x-model="sourceAddForm.name"
                    placeholder="Source name (e.g. Instagram, Trade Show)"
                    class="field-input flex-1 min-w-[200px]"
                    @keydown.enter.prevent="addSource()">
                <input type="text"
                    x-model="sourceAddForm.icon"
                    placeholder="Icon (e.g. instagram)"
                    class="field-input w-44">
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button @click="addSource()"
                    :disabled="sourceAdding || !sourceAddForm.name.trim()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity"
                    style="background: var(--brand-600)"
                    :class="(sourceAdding || !sourceAddForm.name.trim()) ? 'opacity-50 cursor-not-allowed' : ''">
                    <i data-lucide="loader-2" x-show="sourceAdding" class="w-4 h-4 animate-spin"></i>
                    <i data-lucide="plus"     x-show="!sourceAdding" class="w-4 h-4"></i>
                    <span x-text="sourceAdding ? 'Adding...' : 'Add Source'"></span>
                </button>
                <p class="text-[11px] text-gray-400">
                    Icon: use any
                    <a href="https://lucide.dev/icons" target="_blank"
                        class="font-semibold" style="color: var(--brand-600)">Lucide icon name</a>
                </p>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════
         TAGS TAB
    ═══════════════════════════════ --}}
    <div x-show="tab === 'tags'" x-cloak class="space-y-3">

        {{-- List ── --}}
        <div class="space-y-2">
            <template x-for="tag in tags" :key="tag.id">
                <div x-show="searchQuery === '' || tag.name.toLowerCase().includes(searchQuery.toLowerCase())" x-transition.opacity>
                    {{-- View row ── --}}
                    <div x-show="tagEditingId !== tag.id" class="item-row">

                        {{-- Color badge ── --}}
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[12px] font-bold flex-shrink-0"
                            :style="`background: ${tag.color}18; color: ${tag.color}`">
                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                :style="`background: ${tag.color}`"></span>
                            <span x-text="tag.name"></span>
                        </span>
                        
                        {{-- Lead count ── --}}
                        <span class="flex-1 text-[12px] text-gray-400 font-medium"
                            x-text="(tag.leads_count ?? 0) + ' lead' + ((tag.leads_count ?? 0) !== 1 ? 's' : '')">
                        </span>

                        {{-- Actions ── --}}
                        <div class="flex items-center gap-1">
                            @if(has_permission('crm_tags.update'))
                            <button @click="startEditTag(tag)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            @endif

                            @if(has_permission('crm_tags.delete'))
                            <button @click="deleteTag(tag.id, tag.name)"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Inline edit ── --}}
                    <div x-show="tagEditingId === tag.id" x-cloak class="edit-row space-y-3">
                        <template x-if="tagFormError">
                            <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                <p class="text-[12px] font-semibold text-red-600" x-text="tagFormError"></p>
                            </div>
                        </template>
                        <div class="flex items-center gap-3 flex-wrap">
                            <input type="text"
                                x-model="tagEditForm.name"
                                placeholder="Tag name"
                                class="field-input flex-1 min-w-[140px]"
                                @keydown.enter.prevent="saveEditTag(tag.id)"
                                @keydown.escape="cancelEditTag()">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @foreach($tagColors as $color)
                                    <button type="button" class="color-swatch"
                                        style="background: {{ $color }}"
                                        :class="tagEditForm.color === '{{ $color }}' ? 'active' : ''"
                                        @click="tagEditForm.color = '{{ $color }}'">
                                    </button>
                                @endforeach
                                <input type="color" x-model="tagEditForm.color"
                                    class="w-6 h-6 rounded-full cursor-pointer border-0 p-0"
                                    style="background: transparent">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="saveEditTag(tag.id)"
                                :disabled="tagSaving || !tagEditForm.name.trim()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-[12px] font-bold text-white hover:opacity-90 transition-opacity"
                                style="background: var(--brand-600)"
                                :class="tagSaving ? 'opacity-60 cursor-not-allowed' : ''">
                                <i data-lucide="loader-2" x-show="tagSaving" class="w-3.5 h-3.5 animate-spin"></i>
                                <i data-lucide="save"     x-show="!tagSaving" class="w-3.5 h-3.5"></i>
                                <span x-text="tagSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                            <button @click="cancelEditTag()"
                                class="px-4 py-2 rounded-lg text-[12px] font-bold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Add Tag form ── --}}
        @if(has_permission('crm_tags.create'))
        <div class="bg-white border border-gray-100 rounded-2xl p-5 mt-2">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Add Tag</p>

            <template x-if="tagAddError">
                <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-3">
                    <p class="text-[12px] font-semibold text-red-600" x-text="tagAddError"></p>
                </div>
            </template>

            <div class="flex items-center gap-3 flex-wrap mb-3">
                <input type="text"
                    x-model="tagAddForm.name"
                    placeholder="Tag name (e.g. Hot Lead, VIP, Bulk Order)"
                    class="field-input flex-1 min-w-[200px]"
                    @keydown.enter.prevent="addTag()">
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach($tagColors as $color)
                        <button type="button" class="color-swatch"
                            style="background: {{ $color }}"
                            :class="tagAddForm.color === '{{ $color }}' ? 'active' : ''"
                            @click="tagAddForm.color = '{{ $color }}'">
                        </button>
                    @endforeach
                    <input type="color" x-model="tagAddForm.color"
                        class="w-6 h-6 rounded-full cursor-pointer border-0 p-0"
                        style="background: transparent">
                </div>
            </div>

            <button @click="addTag()"
                :disabled="tagAdding || !tagAddForm.name.trim()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-opacity"
                style="background: var(--brand-600)"
                :class="(tagAdding || !tagAddForm.name.trim()) ? 'opacity-50 cursor-not-allowed' : ''">
                <i data-lucide="loader-2" x-show="tagAdding" class="w-4 h-4 animate-spin"></i>
                <i data-lucide="plus"     x-show="!tagAdding" class="w-4 h-4"></i>
                <span x-text="tagAdding ? 'Adding...' : 'Add Tag'"></span>
            </button>
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function crmSettingsPage() {
    return {
        tab:         '{{ request()->has("tab") ? request("tab") : "sources" }}',
        searchQuery: '',
        pageSuccess: null,
        pageError:   null,

        // ── Sources ──
        sources:         @json($sources),
        sourceEditingId: null,
        sourceSaving:    false,
        sourceAdding:    false,
        sourceFormError: null,
        sourceAddError:  null,
        sourceEditForm:  { name: '', icon: '' },
        sourceAddForm:   { name: '', icon: '' },

        // ── Tags ──
        tags:         @json($tags),
        tagEditingId: null,
        tagSaving:    false,
        tagAdding:    false,
        tagFormError: null,
        tagAddError:  null,
        tagEditForm:  { name: '', color: '#6b7280' },
        tagAddForm:   { name: '', color: '#3b82f6' },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        // ═══════════ SOURCES ═══════════

        startEditSource(source) {
            this.sourceEditingId = source.id;
            this.sourceFormError = null;
            this.sourceEditForm  = { name: source.name, icon: source.icon ?? '' };
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        cancelEditSource() {
            this.sourceEditingId = null;
            this.sourceFormError = null;
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        async toggleSourceActive(source) {
            const original = source.is_active;
            source.is_active = !source.is_active;
            try {
                const res  = await this.ajax('PUT', `/admin/crm/sources/${source.id}`, {
                    name:      source.name,
                    icon:      source.icon,
                    is_active: source.is_active,
                });
                if (!res.success) {
                    source.is_active = original;
                    this.pageError   = res.message;
                }
            } catch(e) {
                source.is_active = original;
                this.pageError   = 'Network error.';
            }
        },

        async saveEditSource(id) {
            if (!this.sourceEditForm.name.trim() || this.sourceSaving) return;
            this.sourceSaving    = true;
            this.sourceFormError = null;
            try {
                const data = await this.ajax('PUT', `/admin/crm/sources/${id}`, this.sourceEditForm);
                if (!data.success) { this.sourceFormError = data.message; return; }
                const idx = this.sources.findIndex(s => s.id === id);
                if (idx !== -1) this.sources[idx] = { ...this.sources[idx], ...data.source };
                this.sourceEditingId = null;
                this.flash(data.message);
            } catch(e) { this.sourceFormError = 'Network error.'; }
            finally    { this.sourceSaving = false; }
        },

        async addSource() {
            if (!this.sourceAddForm.name.trim() || this.sourceAdding) return;
            this.sourceAdding = true;
            this.sourceAddError = null;
            try {
                const data = await this.ajax('POST', '/admin/crm/sources', this.sourceAddForm);
                if (!data.success) { this.sourceAddError = data.message; return; }
                this.sources.push(data.source);
                this.sourceAddForm = { name: '', icon: '' };
                this.flash(data.message);
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            } catch(e) { this.sourceAddError = 'Network error.'; }
            finally    { this.sourceAdding = false; }
        },

        async deleteSource(id, name) {
            const c = await Swal.fire({
                title: 'Delete Source?',
                text:  `"${name}" will be removed.`,
                icon:  'warning', showCancelButton: true,
                confirmButtonText: 'Delete', confirmButtonColor: '#ef4444',
            });
            if (!c.isConfirmed) return;
            try {
                const data = await this.ajax('DELETE', `/admin/crm/sources/${id}`);
                if (data.success) {
                    this.sources = this.sources.filter(s => s.id !== id);
                    this.flash(data.message);
                } else {
                    Swal.fire('Cannot Delete', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Network error.', 'error'); }
        },

        // ═══════════ TAGS ═══════════

        startEditTag(tag) {
            this.tagEditingId = tag.id;
            this.tagFormError = null;
            this.tagEditForm  = { name: tag.name, color: tag.color ?? '#6b7280' };
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        cancelEditTag() {
            this.tagEditingId = null;
            this.tagFormError = null;
        },

        async saveEditTag(id) {
            if (!this.tagEditForm.name.trim() || this.tagSaving) return;
            this.tagSaving    = true;
            this.tagFormError = null;
            try {
                const data = await this.ajax('PUT', `/admin/crm/tags/${id}`, this.tagEditForm);
                if (!data.success) { this.tagFormError = data.message; return; }
                const idx = this.tags.findIndex(t => t.id === id);
                if (idx !== -1) this.tags[idx] = { ...this.tags[idx], ...data.tag };
                this.tagEditingId = null;
                this.flash(data.message);
            } catch(e) { this.tagFormError = 'Network error.'; }
            finally    { this.tagSaving = false; }
        },

        async addTag() {
            if (!this.tagAddForm.name.trim() || this.tagAdding) return;
            this.tagAdding   = true;
            this.tagAddError = null;
            try {
                const data = await this.ajax('POST', '/admin/crm/tags', this.tagAddForm);
                if (!data.success) { this.tagAddError = data.message; return; }
                this.tags.push(data.tag);
                this.tagAddForm = { name: '', color: this.tagAddForm.color };
                this.flash(data.message);
            } catch(e) { this.tagAddError = 'Network error.'; }
            finally    { this.tagAdding = false; }
        },

        async deleteTag(id, name) {
            const c = await Swal.fire({
                title: 'Delete Tag?',
                text:  `"${name}" will be removed from all leads.`,
                icon:  'warning', showCancelButton: true,
                confirmButtonText: 'Delete', confirmButtonColor: '#ef4444',
            });
            if (!c.isConfirmed) return;
            try {
                const data = await this.ajax('DELETE', `/admin/crm/tags/${id}`);
                if (data.success) {
                    this.tags = this.tags.filter(t => t.id !== id);
                    this.flash(data.message);
                } else {
                    Swal.fire('Cannot Delete', data.message, 'error');
                }
            } catch(e) { Swal.fire('Error', 'Network error.', 'error'); }
        },

        // ═══════════ HELPERS ═══════════

        async ajax(method, url, body = null) {
            const opts = {
                method,
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };
            if (body) {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(body);
            }
            const res  = await fetch(url, opts);
            return await res.json();
        },

        flash(msg) {
            this.pageSuccess = msg;
            setTimeout(() => this.pageSuccess = null, 3000);
        },
    }
}
</script>
@endpush