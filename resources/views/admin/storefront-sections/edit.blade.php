@extends('layouts.admin')

@section('title', 'Edit Section — ' . $section->admin_label)

@section('header-title')
    <div>
        <h1 class="text-[17px] font-bold text-gray-800 leading-none">Edit Section</h1>
        <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $section->title }}</p>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13.5px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
            background: #fff;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .field-input:focus {
            border-color: var(--brand-600);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-600) 10%, transparent);
        }

        .field-input.error {
            border-color: #f43f5e;
        }

        select.field-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 36px;
            appearance: none;
        }

        textarea.field-input {
            resize: vertical;
            min-height: 90px;
        }

        .form-section {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 800;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding-bottom: 12px;
            margin-bottom: 20px;
            border-bottom: 1.5px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label i {
            color: var(--brand-600);
        }

        /* ── Type selector pills ── */
        .type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
        }

        .type-pill-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            cursor: pointer;
            transition: all 140ms ease;
        }

        .type-pill-label:hover {
            border-color: var(--brand-600);
            background: color-mix(in srgb, var(--brand-600) 4%, white);
        }

        .type-pill-label.selected {
            border-color: var(--brand-600);
            background: color-mix(in srgb, var(--brand-600) 8%, white);
        }

        .type-pill-label input {
            display: none;
        }

        .type-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .type-pill-label span {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            line-height: 1.2;
        }

        .type-pill-label.selected span {
            color: var(--brand-600);
        }

        /* ── Toggle row ── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 14px;
            background: #f8fafc;
            border: 1.5px solid #f1f5f9;
            border-radius: 10px;
        }

        .toggle-switch {
            position: relative;
            width: 40px;
            height: 22px;
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
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 200ms ease;
            pointer-events: none;
        }

        .toggle-switch input:checked~.toggle-thumb {
            transform: translateX(18px);
        }

        /* ── Submit button ── */
        .submit-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand-600);
            color: #fff;
            padding: 11px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background 150ms ease, transform 80ms ease, opacity 150ms ease;
            box-shadow: 0 2px 10px color-mix(in srgb, var(--brand-600) 30%, transparent);
        }

        .submit-btn:hover {
            background: var(--brand-700);
        }

        .submit-btn:active {
            transform: scale(0.97);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .field-error {
            font-size: 11px;
            font-weight: 600;
            color: #f43f5e;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .info-note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 500;
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        /* ── Columns visual picker ── */
        .col-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1.5px solid #e5e7eb;
            cursor: pointer;
            transition: all 140ms ease;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
        }

        .col-option:hover {
            border-color: var(--brand-600);
            color: var(--brand-600);
        }

        .col-option.active {
            border-color: var(--brand-600);
            background: color-mix(in srgb, var(--brand-600) 8%, white);
            color: var(--brand-600);
        }

        .col-option input {
            display: none;
        }

        .col-dots {
            display: flex;
            gap: 2px;
        }

        .col-dot {
            width: 8px;
            height: 8px;
            border-radius: 2px;
            background: currentColor;
        }

        /* ── Mini stat ── */
        .mini-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
        }

        /* ── Status badge ── */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-live {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-offline {
            background: #f3f4f6;
            color: #6b7280;
        }

        .badge-scheduled {
            background: #fef3c7;
            color: #b45309;
        }
    </style>
@endpush

@section('content')
    @php
        $currentType = old('type', $section->type);
        $currentLayout = old('layout', $section->layout);
        $currentColumns = old('columns', $section->columns);
        $isLive = $section->is_live_now;
        $isScheduled = $section->is_active && ($section->starts_at || $section->ends_at);
    @endphp

    <div class="pb-10" x-data="sectionEditForm()">

        {{-- ── Breadcrumb + Status ── --}}
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
                <a href="{{ route('admin.storefront-sections.index') }}"
                    class="hover:text-brand-600 transition-colors">Storefront Sections</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-gray-700 font-semibold truncate max-w-[200px]">{{ $section->title }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span
                    class="live-badge {{ $isLive ? 'badge-live' : ($isScheduled ? 'badge-scheduled' : 'badge-offline') }}">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $isLive ? 'bg-green-500' : ($isScheduled ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                    {{ $isLive ? 'Live' : ($isScheduled ? 'Scheduled' : 'Offline') }}
                </span>
                <a href="{{ route('admin.storefront-sections.index') }}"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </a>
            </div>
        </div>

        {{-- ── Validation Errors ── --}}
        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-red-800 mb-1">Please fix these errors:</p>
                    <ul class="text-xs text-red-600 space-y-0.5 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form id="section-form" method="POST" action="{{ route('admin.storefront-sections.update', $section->id) }}"
            @submit.prevent="submitForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- ════════════ LEFT ════════════ --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- 1. Section Type ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="layout-grid" class="w-4 h-4"></i>
                            Section Type <span class="text-red-500 ml-1">*</span>
                        </p>

                        <div class="type-grid">
                            @foreach ($formData['types'] as $value => $label)
                                @php
                                    $icons = [
                                        'category' => ['icon' => 'folder', 'bg' => '#dbeafe', 'color' => '#1d4ed8'],
                                        'featured' => ['icon' => 'star', 'bg' => '#fef3c7', 'color' => '#b45309'],
                                        'new_arrivals' => [
                                            'icon' => 'sparkles',
                                            'bg' => '#dcfce7',
                                            'color' => '#15803d',
                                        ],
                                        'best_sellers' => [
                                            'icon' => 'trending-up',
                                            'bg' => '#f3e8ff',
                                            'color' => '#7c3aed',
                                        ],
                                        'manual' => ['icon' => 'list-checks', 'bg' => '#e0f2fe', 'color' => '#0369a1'],
                                        'banner' => ['icon' => 'image', 'bg' => '#ffe4e6', 'color' => '#be123c'],
                                        'custom_html' => ['icon' => 'code-2', 'bg' => '#f1f5f9', 'color' => '#475569'],
                                    ];
                                    $ic = $icons[$value] ?? [
                                        'icon' => 'layout',
                                        'bg' => '#f1f5f9',
                                        'color' => '#6b7280',
                                    ];
                                @endphp
                                <label class="type-pill-label {{ $currentType === $value ? 'selected' : '' }}"
                                    @click="selectType('{{ $value }}')">
                                    <input type="radio" name="type" value="{{ $value }}"
                                        {{ $currentType === $value ? 'checked' : '' }}>
                                    <div class="type-icon" style="background: {{ $ic['bg'] }}">
                                        <i data-lucide="{{ $ic['icon'] }}" class="w-3.5 h-3.5"
                                            style="color: {{ $ic['color'] }}"></i>
                                    </div>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('type')
                            <p class="field-error mt-2">{{ $message }}</p>
                        @enderror

                        {{-- Conditional: Category selector ── --}}
                        <div class="mt-4" x-show="sectionType === 'category'" x-cloak>
                            <label class="field-label">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id"
                                class="field-input {{ $errors->has('category_id') ? 'error' : '' }}">
                                <option value="">Select a category</option>
                                @foreach ($formData['categories'] as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $section->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="field-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Conditional: Banner position ── --}}
                        <div class="mt-4" x-show="sectionType === 'banner'" x-cloak>
                            <label class="field-label">
                                Banner Position <span class="text-red-500">*</span>
                            </label>
                            <select name="banner_position"
                                class="field-input {{ $errors->has('banner_position') ? 'error' : '' }}">
                                <option value="">Select which banners to display</option>
                                @foreach ($formData['banner_positions'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('banner_position', $section->banner_position) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('banner_position')
                                <p class="field-error mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[11px] text-gray-400 mt-1.5">
                                Pulls active banners from the Banners manager for this position.
                            </p>
                        </div>

                        {{-- Conditional: Manual selection — manage products button ── --}}
                        @if ($section->type === 'manual')
                            <div class="mt-4" x-show="sectionType === 'manual'" x-cloak>
                                <a href="{{ route('admin.storefront-sections.products.index', $section->id) }}"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                                    style="background: var(--brand-600);">
                                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                                    Manage Products
                                </a>
                                <p class="text-[11px] text-gray-400 mt-2">
                                    Hand-pick exactly which products appear in this section and their order.
                                </p>
                            </div>
                        @endif

                        {{-- Conditional: Custom HTML ── --}}
                        <div class="mt-4" x-show="sectionType === 'custom_html'" x-cloak>
                            <label class="field-label">HTML Content</label>
                            <textarea name="custom_html" class="field-input" rows="5" placeholder="<div>Your custom HTML here...</div>">{{ old('custom_html', $section->custom_html) }}</textarea>
                            <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                <i data-lucide="info" class="w-3 h-3"></i>
                                Use for announcements, custom banners, or promotional blocks.
                            </p>
                        </div>

                        {{-- Info notes per type ── --}}
                        <div class="mt-4">
                            <div x-show="sectionType === 'featured'" x-cloak class="info-note">
                                <i data-lucide="star" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                                <p>Shows products marked as <strong>Featured</strong> in the Merchandising panel. No
                                    category selection needed.</p>
                            </div>
                            <div x-show="sectionType === 'new_arrivals'" x-cloak class="info-note">
                                <i data-lucide="sparkles" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                                <p>Automatically pulls the most recently added products, sorted by creation date.</p>
                            </div>
                            <div x-show="sectionType === 'best_sellers'" x-cloak class="info-note">
                                <i data-lucide="trending-up" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                                <p>Shows best-selling products. Requires sales data to be meaningful.</p>
                            </div>
                            <div x-show="sectionType === 'banner'" x-cloak class="info-note">
                                <i data-lucide="image" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                                <p>Renders banners from your Banners manager in this section slot.</p>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Identity ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="type" class="w-4 h-4"></i>
                            Section Identity
                        </p>
                        <div class="space-y-4">
                            <div>
                                <label class="field-label">Admin Label <span class="text-red-500">*</span><span
                                        class="text-gray-400 normal-case font-normal"> (internal only)</span></label>
                                <input type="text" name="admin_label" value="{{ old('admin_label', $section->admin_label) }}"
                                    placeholder="e.g. Monsoon Promo Row — shown only in admin"
                                    class="field-input {{ $errors->has('admin_label') ? 'error' : '' }}">
                                @error('admin_label')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Section Title</label>
                                <input type="text" name="title" value="{{ old('title', $section->title) }}"
                                    placeholder="e.g. Indoor Plants, New Arrivals"
                                    class="field-input {{ $errors->has('title') ? 'error' : '' }}">
                                @error('title')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Subtitle <span
                                        class="text-gray-400 normal-case font-normal">(optional)</span></label>
                                <input type="text" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}"
                                    placeholder="e.g. Handpicked selections for your home" class="field-input">
                            </div>
                        </div>
                    </div>

                    {{-- 3. Display Config ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                            Display Configuration
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                            <div>
                                <label class="field-label">Layout <span class="text-red-500">*</span></label>
                                <select name="layout" class="field-input" x-model="layout">
                                    @foreach ($formData['layouts'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ $currentLayout === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="field-label">
                                    Products to Show
                                    <span class="text-gray-400 normal-case font-normal ml-1">(max 48)</span>
                                </label>
                                <input type="number" name="products_limit"
                                    value="{{ old('products_limit', $section->products_limit) }}" min="1"
                                    max="48"
                                    class="field-input {{ $errors->has('products_limit') ? 'error' : '' }}">
                                @error('products_limit')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Columns visual picker ── --}}
                        <div x-show="layout === 'grid'" class="mb-5">
                            <label class="field-label mb-3">Grid Columns</label>
                            <div class="flex items-center gap-2 flex-wrap">
                                @foreach ([2, 3, 4, 5, 6] as $col)
                                    <label class="col-option {{ $currentColumns == $col ? 'active' : '' }}"
                                        @click="selectColumns({{ $col }})">
                                        <input type="radio" name="columns" value="{{ $col }}"
                                            {{ $currentColumns == $col ? 'checked' : '' }}>
                                        <div class="col-dots">
                                            @for ($i = 0; $i < $col; $i++)
                                                <div class="col-dot"></div>
                                            @endfor
                                        </div>
                                        <span>{{ $col }} col</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- View All ── --}}
                        <div>
                            <label class="field-label">Custom "View All" URL <span
                                    class="text-gray-400 normal-case font-normal">(optional)</span></label>
                            <input type="url" name="view_all_url"
                                value="{{ old('view_all_url', $section->view_all_url) }}"
                                placeholder="Leave empty to auto-generate from category" class="field-input">
                            @if ($section->resolved_view_all_url)
                                <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                    <i data-lucide="link" class="w-3 h-3"></i>
                                    Current: <span
                                        class="font-mono text-brand-600 truncate">{{ $section->resolved_view_all_url }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- 4. Visual Customization ── --}}
                    <div class="form-section" x-data="{ open: {{ $section->bg_color || $section->heading_color ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                            class="section-label w-full flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2">
                                <i data-lucide="palette" class="w-4 h-4" style="color: var(--brand-600)"></i>
                                Visual Customization
                                <span
                                    class="text-gray-400 normal-case font-normal text-[11px] font-medium">(optional)</span>
                            </span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform"
                                :class="{ 'rotate-180': open }"></i>
                        </button>

                        <div x-show="open" x-transition class="space-y-4 mt-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label">Section Background Color</label>
                                    <input type="color" name="bg_color"
                                        value="{{ old('bg_color', $section->bg_color ?? '#ffffff') }}"
                                        class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                                </div>
                                <div>
                                    <label class="field-label">Heading Color</label>
                                    <input type="color" name="heading_color"
                                        value="{{ old('heading_color', $section->heading_color ?? '#212538') }}"
                                        class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ════════════ RIGHT ════════════ --}}
                <div class="space-y-4">

                    {{-- Publish ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Publish
                        </p>

                        <div class="space-y-3 mb-5">
                            <div class="toggle-row">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Active</p>
                                    <p class="text-[11px] text-gray-400">Show on storefront</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_active" value="1"
                                        {{ old('is_active', $section->is_active ? '1' : '') === '1' ? 'checked' : '' }}>
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Show Section Title</p>
                                    <p class="text-[11px] text-gray-400">Display heading on storefront</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="show_section_title" value="1"
                                        {{ old('show_section_title', $section->show_section_title ? '1' : '') === '1' ? 'checked' : '' }}>
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">View All Button</p>
                                    <p class="text-[11px] text-gray-400">Show "View All →" link</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="show_view_all" value="1"
                                        {{ old('show_view_all', $section->show_view_all ? '1' : '') === '1' ? 'checked' : '' }}>
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="isSubmitting" class="submit-btn w-full justify-center">
                            <i data-lucide="loader-2" x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin"></i>
                            <i data-lucide="save" x-show="!isSubmitting" class="w-4 h-4"></i>
                            <span x-text="isSubmitting ? 'Saving...' : 'Save Changes'"></span>
                        </button>

                        <a href="{{ route('admin.storefront-sections.index') }}"
                            class="mt-3 w-full flex items-center justify-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                            Discard Changes
                        </a>
                    </div>

                    {{-- Analytics ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                            Performance
                        </p>
                        <div class="space-y-2">
                            <div class="mini-stat">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="eye" class="w-4 h-4 text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Views</p>
                                    <p class="text-lg font-black text-gray-900 leading-none">
                                        {{ number_format($section->view_count) }}</p>
                                </div>
                            </div>
                            <div class="mini-stat">
                                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Clicks</p>
                                    <p class="text-lg font-black text-gray-900 leading-none">
                                        {{ number_format($section->click_count) }}</p>
                                </div>
                            </div>
                            @if ($section->view_count > 0)
                                <div class="mini-stat">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="trending-up" class="w-4 h-4 text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">CTR</p>
                                        <p class="text-lg font-black text-gray-900 leading-none">{{ $section->ctr }}%</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Device Visibility ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="monitor-smartphone" class="w-4 h-4"></i>
                            Device Visibility
                        </p>
                        <div class="space-y-2.5">
                            <div class="toggle-row">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="monitor" class="w-4 h-4 text-gray-400"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Desktop</p>
                                        <p class="text-[11px] text-gray-400">Show on desktop</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="show_on_desktop" value="1"
                                        {{ old('show_on_desktop', $section->show_on_desktop ? '1' : '') === '1' ? 'checked' : '' }}>
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="smartphone" class="w-4 h-4 text-gray-400"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">Mobile</p>
                                        <p class="text-[11px] text-gray-400">Show on mobile</p>
                                    </div>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="show_on_mobile" value="1"
                                        {{ old('show_on_mobile', $section->show_on_mobile ? '1' : '') === '1' ? 'checked' : '' }}>
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Scheduling ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            Schedule
                        </p>
                        <div class="space-y-3">
                            <div>
                                <label class="field-label">Start Date & Time</label>
                                <input type="datetime-local" name="starts_at"
                                    value="{{ old('starts_at', $section->starts_at?->format('Y-m-d\TH:i')) }}"
                                    class="field-input {{ $errors->has('starts_at') ? 'error' : '' }}">
                                @error('starts_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">End Date & Time</label>
                                <input type="datetime-local" name="ends_at"
                                    value="{{ old('ends_at', $section->ends_at?->format('Y-m-d\TH:i')) }}"
                                    class="field-input {{ $errors->has('ends_at') ? 'error' : '' }}">
                                @error('ends_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="info-note mt-3">
                            <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                            <p>Clear both dates to run indefinitely when Active is on.</p>
                        </div>
                    </div>

                    {{-- Sort order ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                            Position
                        </p>
                        <label class="field-label">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order) }}"
                            min="0" class="field-input">
                        <p class="text-[11px] text-gray-400 mt-1.5">Lower = shown first. Use drag-drop on index for easier
                            reordering.</p>
                    </div>

                    {{-- Audit info ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="info" class="w-4 h-4"></i>
                            Record Info
                        </p>
                        <div class="space-y-2 text-xs text-gray-500">
                            <div class="flex justify-between">
                                <span class="font-semibold">Created by</span>
                                <span>{{ $section->creator?->name ?? 'System' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Created at</span>
                                <span>{{ $section->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Last updated</span>
                                <span>{{ $section->updated_at->diffForHumans() }}</span>
                            </div>
                            @if ($section->updater)
                                <div class="flex justify-between">
                                    <span class="font-semibold">Updated by</span>
                                    <span>{{ $section->updater->name }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="font-semibold">Section ID</span>
                                <span class="font-mono">#{{ $section->id }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
    <script>
        function sectionEditForm() {
            return {
                isSubmitting: false,
                sectionType: '{{ $currentType }}',
                layout: '{{ $currentLayout }}',

                selectType(val) {
                    this.sectionType = val;
                    document.querySelectorAll('input[name="type"]').forEach(r => {
                        r.checked = r.value === val;
                        r.closest('.type-pill-label').classList.toggle('selected', r.value === val);
                    });
                    console.log('[SectionEdit] Type changed to:', val);
                },

                selectColumns(val) {
                    document.querySelectorAll('input[name="columns"]').forEach(r => {
                        r.checked = r.value == val;
                        r.closest('.col-option').classList.toggle('active', r.value == val);
                    });
                    console.log('[SectionEdit] Columns changed to:', val);
                },

                submitForm() {
                    this.isSubmitting = true;
                    console.log('[SectionEdit] Submitting update for section #{{ $section->id }}', {
                        type: this.sectionType,
                        layout: this.layout,
                    });
                    document.getElementById('section-form').submit();
                },

                init() {
                    console.log('[SectionEdit] Initialized for section #{{ $section->id }}', {
                        type: '{{ $section->type }}',
                        layout: '{{ $section->layout }}',
                        is_active: {{ $section->is_active ? 'true' : 'false' }},
                        is_live_now: {{ $isLive ? 'true' : 'false' }},
                        views: {{ $section->view_count }},
                        clicks: {{ $section->click_count }},
                    });
                }
            }
        }
    </script>
@endpush
