@extends('layouts.admin')

@section('title', 'Storefront Builder')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Storefront Builder</h1>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* ── Layout ── */
    #builder-root {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 64px);
        overflow: hidden;
        background: #f8fafc;
    }
    @media (min-width: 1024px) {
        #builder-root { flex-direction: row; }
    }

    /* ── Left preview panel ── */
    #preview-panel {
        flex: 1;
        min-height: 40vh;
        flex-direction: column;
        border-bottom: 1.5px solid #e2e8f0;
        background: #fff;
    }
    @media (min-width: 1024px) {
        #preview-panel {
            flex: 0 0 55%;
            min-height: auto;
            border-bottom: none;
            border-right: 1.5px solid #e2e8f0;
        }
    }
    #preview-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        border-bottom: 1px solid #f1f5f9;
        background: #fafafa;
        font-size: 12px;
        flex-shrink: 0;
    }
    #preview-bar .url-pill {
        flex: 1;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 11px;
        color: #64748b;
        font-family: monospace;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #storefront-iframe {
        flex: 1;
        border: none;
        width: 100%;
    }

    /* ── Right sections panel ── */
    #sections-panel {
        flex: 1;
        flex-direction: column;
        overflow: hidden;
    }
    @media (min-width: 1024px) {
        #sections-panel { flex: 0 0 45%; }
    }
    #sections-header {
        padding: 14px 20px 10px;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }
    #sections-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px 16px 80px;
    }

    /* ── Section card ── */
    .section-card {
        background: #fff;
        border: 1.5px solid #f1f5f9;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: border-color 140ms, box-shadow 140ms;
        cursor: default;
    }
    .section-card:hover { border-color: #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .section-card.active-editing { border-color:var(--brand-500); box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
    .section-card.sortable-ghost { opacity: 0.4; background: #f0f4ff; }
    .section-card.sortable-drag { box-shadow: 0 8px 24px rgba(0,0,0,0.14); transform: rotate(1deg); }

    .drag-handle {
        cursor: grab;
        color: #cbd5e1;
        padding: 4px 6px;
        border-radius: 4px;
        transition: color 120ms;
    }
    .drag-handle:hover { color: #94a3b8; }
    .drag-handle:active { cursor: grabbing; }

    /* ── Type chips ── */
    .type-chip {
        font-size: 9px; font-weight: 800; letter-spacing: 0.07em;
        text-transform: uppercase; padding: 2px 7px; border-radius: 5px;
        display: inline-flex; align-items: center; gap: 3px; flex-shrink: 0;
    }
    .type-category    { background: #dbeafe; color: #1d4ed8; }
    .type-featured    { background: #fef3c7; color: #b45309; }
    .type-new_arrivals { background: #dcfce7; color: #15803d; }
    .type-best_sellers { background: #f3e8ff; color: #7c3aed; }
    .type-manual      { background: #e0f2fe; color: #0369a1; }
    .type-banner      { background: #ffe4e6; color: #be123c; }
    .type-custom_html { background: #f1f5f9; color: #475569; }

    /* ── Toggle switch ── */
    .toggle-wrap { position: relative; display: inline-block; width: 36px; height: 20px; cursor: pointer; }
    .toggle-wrap input { opacity: 0; width: 0; height: 0; position: absolute; }
    .toggle-track {
        position: absolute; inset: 0; background: #e2e8f0; border-radius: 10px;
        transition: background 200ms;
    }
    .toggle-wrap input:checked ~ .toggle-track { background: var(--brand-700); }
    .toggle-thumb {
        position: absolute; width: 14px; height: 14px; background: #fff;
        border-radius: 50%; top: 3px; left: 3px;
        transition: transform 200ms; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-wrap input:checked ~ .toggle-thumb { transform: translateX(16px); }

    /* ── Slide-over ── */
    #slide-over-backdrop {
        position: fixed; inset: 0; background: rgba(15,23,42,0.35);
        z-index: 40; backdrop-filter: blur(2px);
    }
    #slide-over {
        position: fixed; top: 0; right: 0; bottom: 0;
        width: 100%;
        background: #fff; z-index: 41;
        display: flex; flex-direction: column;
        box-shadow: -8px 0 32px rgba(0,0,0,0.12);
    }
    @media (min-width: 640px) {
        #slide-over { width: 480px; max-width: 100vw; }
    }
    #slide-over-header {
        display: flex; align-items: center; gap: 12px;
        padding: 18px 20px 14px;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }
    #slide-over-body {
        flex: 1; overflow-y: auto; padding: 20px;
    }
    #slide-over-footer {
        padding: 14px 20px;
        border-top: 1px solid #f1f5f9;
        display: flex; gap: 10px;
        flex-shrink: 0;
    }

    /* ── Form fields ── */
    .field-label {
        display: block; font-size: 11px; font-weight: 700;
        letter-spacing: 0.05em; text-transform: uppercase;
        color: #94a3b8; margin-bottom: 5px;
    }
    .field-input {
        width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px;
        padding: 8px 12px; font-size: 13px; color: #1e293b;
        background: #fff; transition: border-color 150ms;
        outline: none;
    }
    .field-input:focus { border-color: var(--brand-700); }
    .field-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 30px; }

    .layout-btn {
        padding: 10px 6px 8px; font-size: 10px; font-weight: 700; letter-spacing: 0.02em;
        border: 1.5px solid #e2e8f0; border-radius: 10px; color: #94a3b8;
        background: #fff; cursor: pointer; transition: all 140ms; text-align: center;
        display: flex; flex-direction: column; align-items: center; gap: 5px; line-height: 1.3;
    }
    .layout-btn svg { transition: color 140ms; }
    .layout-btn.active {
        border-color: var(--brand-500); color: var(--brand-500); background: #eef2ff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }
    .layout-btn:hover:not(.active) { border-color: #c7d2fe; color: var(--brand-500); background: #f5f3ff; }

    /* ── Banner list ── */
    .banner-row {
        display: flex; align-items: center; gap: 10px;
        padding: 10px; border: 1.5px solid #f1f5f9; border-radius: 8px;
        margin-bottom: 8px; background: #fff;
    }
    .banner-thumb {
        width: 56px; height: 36px; border-radius: 5px;
        object-fit: cover; background: #f1f5f9; flex-shrink: 0;
    }

    /* ── Product rows ── */
    .product-row {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; border: 1.5px solid #f1f5f9; border-radius: 8px;
        margin-bottom: 6px; background: #fff;
    }
    .product-thumb {
        width: 36px; height: 36px; border-radius: 5px;
        object-fit: cover; background: #f1f5f9; flex-shrink: 0;
    }

    /* ── Toast notifications ── */
    #toast-stack {
        position: fixed; bottom: 24px; right: 24px;
        z-index: 9999; display: flex; flex-direction: column; gap: 8px;
        pointer-events: none;
    }
    .toast {
        padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 500;
        min-width: 240px; max-width: 340px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        pointer-events: auto;
        animation: toast-in 200ms ease;
    }
    @keyframes toast-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .toast-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .toast-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

    /* ── Add section button ── */
    #add-section-btn {
        position: sticky; bottom: 0; margin: 0 -16px -80px;
        padding: 16px 16px 20px;
        background: linear-gradient(to top, #f8fafc 60%, transparent);
    }

    /* ── Section divider ── */
    .section-field-group { margin-bottom: 18px; }

    /* ── Upload zone ── */
    .upload-zone {
        border: 2px dashed #e2e8f0; border-radius: 10px;
        padding: 20px; text-align: center; cursor: pointer;
        transition: border-color 150ms, background 150ms;
    }
    .upload-zone:hover, .upload-zone.drag-over {
        border-color: #6366f1; background: #f0f4ff;
    }

    /* ── Scrollbar styling ── */
    #sections-list::-webkit-scrollbar, #slide-over-body::-webkit-scrollbar { width: 4px; }
    #sections-list::-webkit-scrollbar-track, #slide-over-body::-webkit-scrollbar-track { background: transparent; }
    #sections-list::-webkit-scrollbar-thumb, #slide-over-body::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
</style>
@endpush

@php
    $companySlug = auth()->user()->company?->slug;
    $storefrontUrl = $companySlug ? route('storefront.index', ['slug' => $companySlug]) : null;
@endphp

@section('content')
<div id="builder-root"
    x-data="Object.assign(storefrontBuilder(), { activeView: 'sections' })"
   
    @keydown.escape.window="closeSlideOver()">
    {{-- Mobile/Tablet Premium View Switcher --}}
    <div class="lg:hidden p-3 bg-white border-b border-gray-200 flex-shrink-0 z-10 shadow-sm">
        <div class="flex p-1 bg-slate-100 rounded-lg gap-1 border border-slate-200/60">
            <button type="button" @click="activeView = 'sections'"
                :class="activeView === 'sections' ? 'bg-white text-brand-600 shadow font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                class="flex-1 py-1.5 text-xs rounded-md transition-all outline-none">
                Manage Sections
            </button>
            <button type="button" @click="activeView = 'preview'"
                :class="activeView === 'preview' ? 'bg-white text-brand-600 shadow font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                class="flex-1 py-1.5 text-xs rounded-md transition-all outline-none">
                Live Preview
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         LEFT PANEL — Live storefront preview
    ══════════════════════════════════════════════ --}}
    <div id="preview-panel" :class="activeView === 'preview' ? 'flex' : 'hidden lg:flex'">
        <div id="preview-bar">
            <span style="font-size:11px;color:#94a3b8;font-weight:600;flex-shrink:0;">PREVIEW</span>
            <span class="url-pill">{{ $storefrontUrl ?? 'Storefront not configured' }}</span>
            @if($storefrontUrl)
            <a href="{{ $storefrontUrl }}" target="_blank"
                class="flex-shrink-0 flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-600 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6m0 0v6m0-6L10 14"/></svg>
                Live
            </a>
            <button @click="refreshPreview()"
                title="Refresh preview"
                class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-brand-500 hover:bg-brand-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
            @endif
        </div>

        @if($storefrontUrl)
        <iframe id="storefront-iframe"
            :src="previewUrl"
            :key="previewKey"
            allow="same-origin">
        </iframe>
        @else
        <div class="flex flex-col items-center justify-center h-full gap-3 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <p class="text-sm font-medium">No storefront configured</p>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         RIGHT PANEL — Sections manager
    ══════════════════════════════════════════════ --}}
    <div id="sections-panel" :class="activeView === 'sections' ? 'flex' : 'hidden lg:flex'">
        <div id="sections-header" class="bg-white z-10 relative">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[15px] font-extrabold text-slate-800">Homepage Layout</p>
                    <p class="text-[11px] font-semibold text-slate-500 mt-0.5 tracking-wide">
                        <span x-text="sections.length"></span> SECTIONS ·
                        <span x-text="sections.filter(s => s.is_active).length" class="text-brand-600"></span> ACTIVE
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-md hidden sm:block">Drag to reorder</span>
                </div>
            </div>
        </div>

        <div id="sections-list">
            {{-- Drag-sortable list --}}
            <div id="sortable-sections">
                <template x-for="section in sections" :key="section.id">
                    <div class="section-card"
                        :class="{ 'active-editing': activeSection && activeSection.id === section.id }"
                        :data-id="section.id">

                        <div class="flex items-center gap-3 p-3">
                            {{-- Drag handle --}}
                            <div class="drag-handle flex-shrink-0" title="Drag to reorder">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5a2 2 0 100 4 2 2 0 000-4zM9 11a2 2 0 100 4 2 2 0 000-4zM9 17a2 2 0 100 4 2 2 0 000-4zM15 5a2 2 0 100 4 2 2 0 000-4zM15 11a2 2 0 100 4 2 2 0 000-4zM15 17a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </div>

                            {{-- Type icon --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                                :class="{
                                    'bg-blue-50':   section.type === 'category',
                                    'bg-amber-50':  section.type === 'featured' || section.type === 'best_sellers',
                                    'bg-green-50':  section.type === 'new_arrivals',
                                    'bg-sky-50':    section.type === 'manual',
                                    'bg-rose-50':   section.type === 'banner',
                                    'bg-slate-50':  section.type === 'custom_html',
                                }">
                                {{-- category --}}
                                <svg x-show="section.type === 'category'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                {{-- featured / best_sellers --}}
                                <svg x-show="section.type === 'featured' || section.type === 'best_sellers'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                {{-- new_arrivals --}}
                                <svg x-show="section.type === 'new_arrivals'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                {{-- manual --}}
                                <svg x-show="section.type === 'manual'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                {{-- banner --}}
                                <svg x-show="section.type === 'banner'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{-- custom_html --}}
                                <svg x-show="section.type === 'custom_html'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-2">
                                    <span class="text-sm font-bold text-slate-800 truncate block w-full" x-text="section.display_admin_label"></span>
                                    <span class="type-chip w-max" :class="'type-' + section.type" x-text="section.type_label"></span>
                                </div>
                                <p class="text-[11px] font-medium text-slate-400 mt-1 truncate block w-full">
                                    <span x-show="section.type === 'category' && section.category_name" x-text="section.category_name"></span>
                                    <span x-show="section.type === 'banner' && section.banner_position" x-text="section.banner_position?.replace('_', ' ')"></span>
                                    <span x-show="!['category','banner'].includes(section.type)" x-text="section.layout_label + ' · ' + section.products_limit + ' products'"></span>
                                </p>
                            </div>
                            {{-- Actions --}}
                            <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0 border-l border-slate-100 pl-3 sm:pl-4 ml-1">
                                {{-- Toggle --}}
                                <label class="toggle-wrap flex-shrink-0 m-0" :title="section.is_active ? 'Active — click to deactivate' : 'Inactive — click to activate'"
                                    @click.stop>
                                    <input type="checkbox" :checked="section.is_active"
                                        @change="toggleSection(section)">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                                {{-- Edit button --}}
                                <button @click="openSlideOver(section)"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                    title="Edit section">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="sections.length === 0" class="text-center py-16 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <p class="text-sm font-medium">No sections yet</p>
                    <p class="text-xs mt-1">Click "Add section" to start building your storefront</p>
                </div>
            </div>

            {{-- Add section button (sticky footer) --}}
            <div id="add-section-btn">
                <button @click="openNewSection()"
                    class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 border-dashed border-brand-200 text-brand-500 font-semibold text-sm hover:border-brand-400 hover:bg-brand-50 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add section
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         SLIDE-OVER — Section editor (no page navigation)
    ══════════════════════════════════════════════ --}}
    <template x-if="slideOverOpen">
        <div>
            {{-- Backdrop --}}
            <div id="slide-over-backdrop" @click="closeSlideOver()"></div>

            {{-- Panel --}}
            <div id="slide-over">
                {{-- Header --}}
                <div id="slide-over-header" class="bg-slate-50/50">
                    <button @click="closeSlideOver()"
                        class="p-2 rounded-full text-slate-400 hover:text-slate-800 hover:bg-slate-200 transition-colors flex-shrink-0"
                        title="Close editor">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </button>
                    <div class="flex-1 min-w-0">
                        <p class="text-[15px] font-extrabold text-slate-800 truncate" x-text="isNewSection ? 'Add new section' : 'Editing: ' + (form.admin_label || form.title || 'Section')"></p>
                        <p class="text-[11px] font-semibold text-brand-600 mt-0.5 uppercase tracking-wide" x-text="isNewSection ? 'Choose a type to get started' : form.type_label"></p>
                    </div>
                    {{-- Delete (only for existing) --}}
                    <button x-show="!isNewSection" @click="deleteSection()"
                        class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors flex-shrink-0"
                        title="Delete section">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div id="slide-over-body">

                    {{-- ── Section type selector ── --}}
                    <div class="section-field-group">
                        <label class="field-label">Section type</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(\App\Models\StorefrontSection::TYPE_LABELS as $typeKey => $typeLabel)
                            <button type="button"
                                @click="form.type = '{{ $typeKey }}'; form.type_label = '{{ $typeLabel }}'"
                                :class="form.type === '{{ $typeKey }}' ? 'border-brand-500 bg-brand-50 text-brand-700 shadow-[0_0_0_2px_rgba(99,102,241,0.1)]' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                class="flex items-center justify-center text-center px-2 py-2.5 rounded-lg border border-solid text-[11px] leading-tight font-bold transition-all">
                                <span>{{ $typeLabel }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Section title & label ── --}}
                    <div class="section-field-group">
                        <label class="field-label">Section title <span class="text-gray-400 normal-case font-normal">(shown on storefront)</span></label>
                        <input type="text" x-model="form.title" class="field-input" placeholder="e.g. Featured Picks">
                    </div>

                    <div class="section-field-group">
                        <label class="field-label">Admin label <span class="text-gray-400 normal-case font-normal">(internal only)</span></label>
                        <input type="text" x-model="form.admin_label" class="field-input" placeholder="e.g. Homepage hero row">
                    </div>

                    {{-- ── Category selector (type = category) ── --}}
                    <div class="section-field-group" x-show="form.type === 'category'">
                        <label class="field-label">Category <span class="text-red-400">*</span></label>
                        <select x-model="form.category_id" class="field-input field-select">
                            <option value="">— Select category —</option>
                            @foreach($formData['categories'] as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ── Custom HTML (type = custom_html) ── --}}
                    <div class="section-field-group" x-show="form.type === 'custom_html'">
                        <label class="field-label">HTML content</label>
                        <textarea x-model="form.custom_html" rows="6" class="field-input font-mono text-xs"
                            placeholder="<div>Your HTML here...</div>"></textarea>
                    </div>

                    {{-- ── Banner position (type = banner) ── --}}
                    <div x-show="form.type === 'banner'">
                        <div class="section-field-group">
                            <label class="field-label">Banner position <span class="text-red-400">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($formData['banner_positions'] as $posKey => $posLabel)
                                <button type="button"
                                    @click="form.banner_position = '{{ $posKey }}'; loadBannersForPosition('{{ $posKey }}')"
                                    :class="form.banner_position === '{{ $posKey }}' ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                    class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all">
                                    {{ $posLabel }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Banners for this position --}}
                        <div x-show="form.banner_position" class="section-field-group">
                            <label class="field-label">Banners in this position</label>

                            {{-- Existing banners --}}
                            <div>
                                <template x-for="banner in currentPositionBanners" :key="banner.id">
                                    <div class="banner-row">
                                        <img :src="banner.image_url" :alt="banner.display_admin_label" class="banner-thumb">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-700 truncate" x-text="banner.display_admin_label"></p>
                                            <p class="text-xs text-gray-400" x-text="banner.is_active ? 'active' : 'inactive'"></p>
                                        </div>
                                        <label class="toggle-wrap flex-shrink-0">
                                            <input type="checkbox" :checked="banner.is_active"
                                                @change="toggleBanner(banner)">
                                            <span class="toggle-track"></span>
                                            <span class="toggle-thumb"></span>
                                        </label>
                                        <a :href="'{{ url('admin/banners') }}/' + banner.id + '/edit'"
                                            class="p-1.5 rounded text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors flex-shrink-0"
                                            title="Edit banner details">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <button @click="deleteBanner(banner)"
                                            class="p-1.5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <p x-show="currentPositionBanners.length === 0" class="text-xs text-gray-400 text-center py-3">No banners in this position yet.</p>
                            </div>

                            {{-- Quick add banner --}}
                            <div class="mt-3">
                                <button type="button" @click="showBannerUpload = !showBannerUpload"
                                    class="flex items-center gap-2 text-xs font-semibold text-brand-500 hover:text-brand-700 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <span x-text="showBannerUpload ? 'Cancel' : 'Upload new banner'"></span>
                                </button>

                                <div x-show="showBannerUpload" class="mt-3 p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                                    <div>
                                        <label class="field-label">Label <span class="text-red-400">*</span></label>
                                        <input type="text" x-model="bannerForm.admin_label" class="field-input" placeholder="Summer Sale Hero">
                                    </div>
                                    <div>
                                        <label class="field-label">Image <span class="text-red-400">*</span> <span class="text-gray-400 normal-case font-normal">JPG/PNG/WEBP · max 5 MB</span></label>
                                        <label class="upload-zone block"
                                            :class="bannerForm.imagePreview ? 'border-solid border-brand-200 p-0 overflow-hidden' : ''">
                                            <input type="file" accept="image/*" class="hidden" @change="onBannerImageChange($event)">
                                            <img x-show="bannerForm.imagePreview" :src="bannerForm.imagePreview" class="w-full h-32 object-cover rounded-lg">
                                            <div x-show="!bannerForm.imagePreview" class="py-6">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <p class="text-xs text-gray-400">Click to upload banner image</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="field-label">Link URL <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                                        <input type="url" x-model="bannerForm.link" class="field-input" placeholder="https://...">
                                    </div>
                                    <div>
                                        <label class="field-label">Button text <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                                        <input type="text" x-model="bannerForm.button_text" class="field-input" placeholder="Shop Now">
                                    </div>
                                    <button type="button" @click="uploadBanner()"
                                        :disabled="bannerUploading"
                                        class="w-full py-2 rounded-lg bg-brand-500 text-white text-xs font-bold hover:bg-brand-600 disabled:opacity-50 transition-colors">
                                        <span x-text="bannerUploading ? 'Uploading...' : 'Upload banner'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Layout (not for banner/custom_html types) ── --}}
                    <div class="section-field-group" x-show="!['banner', 'custom_html'].includes(form.type)">
                        <label class="field-label">Layout</label>
                        @php
                        $layoutMeta = [
                            'grid' => [
                                'label' => 'Grid',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>',
                            ],
                            'list' => [
                                'label' => 'List',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
                            ],
                            'carousel' => [
                                'label' => 'Carousel / Slider',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M1 12h2M21 12h2M7 9l-3 3 3 3M17 9l3 3-3 3"/>',
                            ],
                            'horizontal_scroll' => [
                                'label' => 'Horizontal Scroll',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V8m0 0L4 11m3-3l3 3M17 8v8m0 0l3-3m-3 3l-3-3"/>',
                            ],
                        ];
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach(\App\Models\StorefrontSection::LAYOUT_LABELS as $layoutKey => $layoutLabel)
                            <button type="button"
                                @click="form.layout = '{{ $layoutKey }}'"
                                :class="form.layout === '{{ $layoutKey }}' ? 'border-brand-500 text-brand-600 bg-brand-50 shadow-[0_0_0_2px_rgba(99,102,241,0.1)]' : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                                class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border border-solid text-[10px] font-bold transition-all">
                                {!! $layoutMeta[$layoutKey]['icon'] ?? '' !!}</svg>
                                <span class="text-center leading-tight">{{ $layoutMeta[$layoutKey]['label'] ?? $layoutLabel }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── Products & columns (not for banner/custom_html) ── --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 section-field-group" x-show="!['banner', 'custom_html'].includes(form.type)">
                        <div>
                            <label class="field-label">Products shown</label>
                            <select x-model.number="form.products_limit" class="field-input field-select">
                                <option value="4">4</option><option value="8">8</option>
                                <option value="12">12</option><option value="16">16</option>
                                <option value="24">24</option><option value="48">48</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Columns</label>
                            <select x-model.number="form.columns" class="field-input field-select">
                                <option value="2">2</option><option value="3">3</option>
                                <option value="4">4</option><option value="5">5</option><option value="6">6</option>
                            </select>
                        </div>
                    </div>

                    {{-- ── Manual products ── --}}
                    <div x-show="form.type === 'manual' && !isNewSection" class="section-field-group">
                        <label class="field-label">Products in this section</label>

                        {{-- Search --}}
                        <div class="relative mb-3">
                            <input type="text" x-model="productQuery"
                                @input.debounce.400ms="searchProducts()"
                                class="field-input pl-8" placeholder="Search and add products…">                            
                        </div>

                        {{-- Search results dropdown --}}
                        <div x-show="productResults.length > 0" class="mb-3 border border-gray-200 rounded-lg overflow-hidden">
                            <template x-for="product in productResults" :key="product.product_id">
                                <button type="button" @click="addProduct(product)"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-left hover:bg-brand-50 transition-colors border-b border-gray-100 last:border-0">
                                    <img :src="product.image" class="w-8 h-8 rounded object-cover bg-gray-100 flex-shrink-0">
                                    <span class="text-xs font-medium text-gray-700 truncate flex-1" x-text="product.name"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </template>
                        </div>

                        {{-- Current products --}}
                        <div>
                            <template x-for="product in sectionProducts" :key="product.product_id">
                                <div class="product-row">
                                    <img :src="product.image" class="product-thumb" :alt="product.name">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-gray-700 truncate" x-text="product.name"></p>
                                    </div>
                                    <button @click="removeProduct(product.product_id)"
                                        class="p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                            <p x-show="sectionProducts.length === 0" class="text-xs text-gray-400 text-center py-3">No products yet. Search above to add.</p>
                        </div>
                    </div>

                    <div x-show="form.type === 'manual' && isNewSection" class="section-field-group">
                        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            Save the section first, then you'll be able to add products to it.
                        </div>
                    </div>

                    {{-- ── Toggles ── --}}
                    <div class="section-field-group">
                        <label class="field-label">Visibility & options</label>
                        <div class="space-y-2.5">
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm text-gray-600">Active (visible on storefront)</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" x-model="form.is_active">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                            </label>
                            <label class="flex items-center justify-between cursor-pointer" x-show="!['banner','custom_html'].includes(form.type)">
                                <span class="text-sm text-gray-600">Show section title</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" x-model="form.show_section_title">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                            </label>
                            <label class="flex items-center justify-between cursor-pointer" x-show="form.type === 'category'">
                                <span class="text-sm text-gray-600">Show "View all" link</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" x-model="form.show_view_all">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                            </label>
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm text-gray-600">Show on mobile</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" x-model="form.show_on_mobile">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                            </label>
                            <label class="flex items-center justify-between cursor-pointer">
                                <span class="text-sm text-gray-600">Show on desktop</span>
                                <label class="toggle-wrap">
                                    <input type="checkbox" x-model="form.show_on_desktop">
                                    <span class="toggle-track"></span>
                                    <span class="toggle-thumb"></span>
                                </label>
                            </label>
                        </div>
                    </div>

                    {{-- ── Scheduling (collapsible) ── --}}
                    <div class="section-field-group">
                        <button type="button" @click="showScheduling = !showScheduling"
                            class="flex items-center gap-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition-colors w-full text-left mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Scheduling
                            <svg class="w-3 h-3 ml-auto transition-transform" :class="showScheduling ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="showScheduling" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="field-label">Start date</label>
                                <input type="datetime-local" x-model="form.starts_at" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">End date</label>
                                <input type="datetime-local" x-model="form.ends_at" class="field-input">
                            </div>
                        </div>
                    </div>

                </div>{{-- /slide-over-body --}}

                {{-- Footer --}}
                <div id="slide-over-footer">
                    <button @click="saveSection()" :disabled="saving"
                        class="flex-1 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 disabled:opacity-50 transition-colors">
                        <span x-text="saving ? 'Saving...' : (isNewSection ? 'Create section' : 'Save changes')"></span>
                    </button>
                    <button @click="closeSlideOver()"
                        class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ══════════════════════════════════════════════
         TOAST NOTIFICATIONS
    ══════════════════════════════════════════════ --}}
    <div id="toast-stack">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast" :class="toast.type === 'success' ? 'toast-success' : 'toast-error'">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function storefrontBuilder() {
    return {
        // ── State ──
        sections: @json($sectionsJson),
        formData: @json($formData),
        bannersByPosition: @json($bannersByPosition),

        // ── UI state ──
        slideOverOpen: false,
        isNewSection: false,
        saving: false,
        showScheduling: false,
        showBannerUpload: false,
        bannerUploading: false,
        activeSection: null,

        // ── Preview ──
        previewUrl: '{{ $storefrontUrl ?? '' }}',
        previewKey: 0,

        // ── Toast ──
        toasts: [],
        toastCounter: 0,

        // ── Form (mirrors active section) ──
        form: {},

        // ── Banners ──
        currentPositionBanners: [],
        bannerForm: { admin_label: '', link: '', button_text: '', imageFile: null, imagePreview: null },

        // ── Products ──
        productQuery: '',
        productResults: [],
        sectionProducts: [],

        // ════════════════════════════════════════
        //  INIT
        // ════════════════════════════════════════

        init() {
            this.$nextTick(() => this.initSortable());
        },

        initSortable() {
            const el = document.getElementById('sortable-sections');
            if (!el || typeof Sortable === 'undefined') return;

            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: () => this.persistOrder(),
            });
        },

        // ════════════════════════════════════════
        //  PREVIEW
        // ════════════════════════════════════════

        refreshPreview() {
            this.previewKey++;
            const frame = document.getElementById('storefront-iframe');
            if (frame) { frame.src = frame.src; }
        },

        // ════════════════════════════════════════
        //  SLIDE-OVER
        // ════════════════════════════════════════

        openSlideOver(section) {
            this.activeSection = section;
            this.isNewSection = false;
            this.showScheduling = !!(section.starts_at || section.ends_at);
            this.showBannerUpload = false;
            this.productResults = [];
            this.productQuery = '';

            this.form = { ...section };
            this.form.is_active = !!section.is_active;
            this.form.show_section_title = !!section.show_section_title;
            this.form.show_view_all = !!section.show_view_all;
            this.form.show_on_mobile = !!section.show_on_mobile;
            this.form.show_on_desktop = !!section.show_on_desktop;

            // Load position banners
            if (section.type === 'banner' && section.banner_position) {
                this.loadBannersForPosition(section.banner_position);
            }

            // Load manual products
            if (section.type === 'manual') {
                this.loadSectionProducts(section.id);
            }

            this.slideOverOpen = true;
        },

        openNewSection() {
            this.activeSection = null;
            this.isNewSection = true;
            this.showScheduling = false;
            this.showBannerUpload = false;
            this.productResults = [];
            this.productQuery = '';
            this.currentPositionBanners = [];

            this.form = {
                type: 'category',
                type_label: 'Category Products',
                title: '',
                admin_label: '',
                subtitle: '',
                category_id: '',
                banner_position: '',
                layout: 'grid',
                products_limit: 8,
                columns: 4,
                is_active: true,
                show_section_title: true,
                show_view_all: true,
                show_on_mobile: true,
                show_on_desktop: true,
                view_all_url: '',
                bg_color: '',
                heading_color: '',
                custom_html: '',
                starts_at: '',
                ends_at: '',
            };

            this.slideOverOpen = true;
        },

        closeSlideOver() {
            this.slideOverOpen = false;
            this.activeSection = null;
            this.productResults = [];
        },

        // ════════════════════════════════════════
        //  SECTION CRUD
        // ════════════════════════════════════════

        async saveSection() {
            this.saving = true;

            const url = this.isNewSection
                ? '{{ route('admin.storefront-builder.sections.store') }}'
                : `{{ url('admin/storefront-builder/sections') }}/${this.activeSection.id}`;

            const method = this.isNewSection ? 'POST' : 'PATCH';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (data.success) {
                    this.toast(data.message, 'success');

                    if (this.isNewSection) {
                        this.sections.push(data.section);
                    } else {
                        const idx = this.sections.findIndex(s => s.id === data.section.id);
                        if (idx !== -1) this.sections[idx] = { ...data.section };
                    }

                    // Re-open slide-over with updated data (so products work after create)
                    if (this.isNewSection && data.section.type === 'manual') {
                        this.openSlideOver(data.section);
                    } else {
                        this.closeSlideOver();
                    }

                    this.refreshPreview();
                } else {
                    this.toast(data.message || 'Save failed.', 'error');
                    if (data.errors) {
                        const first = Object.values(data.errors)[0];
                        this.toast(Array.isArray(first) ? first[0] : first, 'error');
                    }
                }
            } catch (e) {
                this.toast('Network error. Please try again.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteSection() {
            if (!confirm(`Delete "${this.activeSection.display_admin_label}"? This cannot be undone.`)) return;

            try {
                const res = await fetch(`{{ url('admin/storefront-builder/sections') }}/${this.activeSection.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    this.sections = this.sections.filter(s => s.id !== this.activeSection.id);
                    this.toast(data.message, 'success');
                    this.closeSlideOver();
                    this.refreshPreview();
                } else {
                    this.toast(data.message, 'error');
                }
            } catch (e) {
                this.toast('Delete failed.', 'error');
            }
        },

        async toggleSection(section) {
            // Optimistic update
            const prev = section.is_active;
            section.is_active = !prev;

            try {
                const res = await fetch(`{{ url('admin/storefront-sections') }}/${section.id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    section.is_active = data.is_active;
                    this.refreshPreview();
                } else {
                    section.is_active = prev; // rollback
                    this.toast('Toggle failed.', 'error');
                }
            } catch (e) {
                section.is_active = prev;
                this.toast('Network error.', 'error');
            }
        },

        async persistOrder() {
            const ids = [...document.querySelectorAll('#sortable-sections .section-card')]
                .map(el => parseInt(el.dataset.id));

            if (!ids.length) return;

            try {
                await fetch('{{ route('admin.storefront-sections.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ids }),
                });

                this.refreshPreview();
            } catch (e) {
                this.toast('Reorder save failed.', 'error');
            }
        },

        // ════════════════════════════════════════
        //  BANNER MANAGEMENT (inline)
        // ════════════════════════════════════════

        loadBannersForPosition(position) {
            this.form.banner_position = position;
            const raw = this.bannersByPosition[position] || [];
            this.currentPositionBanners = Array.isArray(raw) ? raw : Object.values(raw);
        },

        onBannerImageChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.bannerForm.imageFile = file;
            this.bannerForm.imagePreview = URL.createObjectURL(file);
        },

        async uploadBanner() {
            if (!this.bannerForm.admin_label.trim()) {
                this.toast('Banner label is required.', 'error'); return;
            }
            if (!this.bannerForm.imageFile) {
                this.toast('Please select an image.', 'error'); return;
            }

            this.bannerUploading = true;

            const fd = new FormData();
            fd.append('admin_label', this.bannerForm.admin_label);
            fd.append('position', this.form.banner_position);
            fd.append('image', this.bannerForm.imageFile);
            if (this.bannerForm.link) fd.append('link', this.bannerForm.link);
            if (this.bannerForm.button_text) fd.append('button_text', this.bannerForm.button_text);
            fd.append('is_active', '1');

            try {
                const res = await fetch('{{ route('admin.storefront-builder.banners.store') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: fd,
                });

                const data = await res.json();

                if (data.success) {
                    this.currentPositionBanners.push(data.banner);

                    // Update bannersByPosition cache
                    if (!this.bannersByPosition[this.form.banner_position]) {
                        this.bannersByPosition[this.form.banner_position] = [];
                    }
                    this.bannersByPosition[this.form.banner_position].push(data.banner);

                    this.bannerForm = { admin_label: '', link: '', button_text: '', imageFile: null, imagePreview: null };
                    this.showBannerUpload = false;
                    this.toast(data.message, 'success');
                    this.refreshPreview();
                } else {
                    this.toast(data.message || 'Upload failed.', 'error');
                }
            } catch (e) {
                this.toast('Upload failed.', 'error');
            } finally {
                this.bannerUploading = false;
            }
        },

        async toggleBanner(banner) {
            const prev = banner.is_active;
            banner.is_active = !prev;

            try {
                const res = await fetch(`{{ url('admin/storefront-builder/banners') }}/${banner.id}/toggle`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });

                const data = await res.json();

                if (data.success) {
                    banner.is_active = data.is_active;
                    this.refreshPreview();
                } else {
                    banner.is_active = prev;
                }
            } catch (e) {
                banner.is_active = prev;
            }
        },

        async deleteBanner(banner) {
            if (!confirm(`Delete banner "${banner.display_admin_label}"?`)) return;

            try {
                const res = await fetch(`{{ url('admin/storefront-builder/banners') }}/${banner.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });

                const data = await res.json();

                if (data.success) {
                    this.currentPositionBanners = this.currentPositionBanners.filter(b => b.id !== banner.id);
                    this.toast(data.message, 'success');
                    this.refreshPreview();
                } else {
                    this.toast(data.message, 'error');
                }
            } catch (e) {
                this.toast('Delete failed.', 'error');
            }
        },

        // ════════════════════════════════════════
        //  PRODUCT MANAGEMENT (manual sections)
        // ════════════════════════════════════════

        async loadSectionProducts(sectionId) {
            try {
                const res = await fetch(`{{ url('admin/storefront-sections') }}/${sectionId}/products/load`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) this.sectionProducts = data.products;
            } catch (e) {
                this.sectionProducts = [];
            }
        },

        async searchProducts() {
            if (!this.productQuery.trim() || !this.activeSection) {
                this.productResults = []; return;
            }

            try {
                const res = await fetch(
                    `{{ url('admin/storefront-sections') }}/${this.activeSection.id}/products/search?q=${encodeURIComponent(this.productQuery)}`,
                    { headers: { 'Accept': 'application/json' } }
                );
                const data = await res.json();
                this.productResults = data.success ? (data.products || []) : [];
            } catch (e) {
                this.productResults = [];
            }
        },

        async addProduct(product) {
            try {
                const res = await fetch(`{{ url('admin/storefront-sections') }}/${this.activeSection.id}/products`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ product_id: product.product_id }),
                });

                const data = await res.json();

                if (data.success) {
                    this.sectionProducts.push(product);
                    this.productResults = this.productResults.filter(p => p.product_id !== product.product_id);
                    this.toast('Product added.', 'success');
                } else {
                    this.toast(data.message || 'Could not add product.', 'error');
                }
            } catch (e) {
                this.toast('Failed to add product.', 'error');
            }
        },

        async removeProduct(productId) {
            try {
                const res = await fetch(`{{ url('admin/storefront-sections') }}/${this.activeSection.id}/products/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    this.sectionProducts = this.sectionProducts.filter(p => p.product_id !== productId);
                    this.toast('Product removed.', 'success');
                } else {
                    this.toast('Could not remove product.', 'error');
                }
            } catch (e) {
                this.toast('Failed to remove product.', 'error');
            }
        },

        // ════════════════════════════════════════
        //  TOAST
        // ════════════════════════════════════════

        toast(message, type = 'success') {
            const id = ++this.toastCounter;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3200);
        },
    };
}
</script>
@endpush