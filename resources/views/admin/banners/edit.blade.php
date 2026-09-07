@extends('layouts.admin')

@section('title', 'Edit Banner — ' . $banner->display_admin_label)

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Edit Banner</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">{{ $banner->display_admin_label }}</p> --}}
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
            transition: border-color 150ms ease, box-shadow 150ms ease;
            background: #fff;
            font-family: inherit;
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
            min-height: 76px;
        }

        .form-section {
            background: #fff;
            border: 1.5px solid #f1f5f9;
            border-radius: 16px;
            padding: 16px; /* Mobile-friendly padding */
            margin-bottom: 16px;
        }
        @media (min-width: 768px) {
            .form-section {
                padding: 24px; /* Restored for iPad/Desktop */
            }
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

        /* ── Image upload zone ── */
        .upload-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            transition: border-color 200ms ease, background 200ms ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--brand-600);
            background: color-mix(in srgb, var(--brand-600) 4%, white);
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-preview {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
        }

        .upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 32px 20px;
            color: #94a3b8;
            text-align: center;
        }

        /* ── Existing image overlay ── */
        .existing-image-wrap {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }

        .existing-image-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 200ms ease;
            border-radius: 12px;
        }

        .existing-image-wrap:hover .existing-image-overlay {
            background: rgba(0, 0, 0, 0.45);
        }

        .overlay-btn {
            opacity: 0;
            transform: translateY(4px);
            transition: opacity 200ms ease, transform 200ms ease;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .existing-image-wrap:hover .overlay-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .overlay-btn.change {
            background: #fff;
            color: #374151;
        }

        .overlay-btn.remove {
            background: #ef4444;
            color: #fff;
        }

        /* ── Toggle switch ── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
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

        /* ── Type pill selector ── */
        .type-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            transition: all 140ms ease;
            white-space: nowrap;
            user-select: none;
        }

        .type-pill:hover {
            border-color: var(--brand-600);
            color: var(--brand-600);
        }

        .type-pill.selected {
            background: var(--brand-600);
            border-color: var(--brand-600);
            color: #fff;
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

        .preview-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
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

        /* ── Analytics mini cards ── */
        .mini-stat {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Status indicator ── */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .live-badge.live {
            background: #dcfce7;
            color: #15803d;
        }

        .live-badge.offline {
            background: #f3f4f6;
            color: #6b7280;
        }

        .live-badge.scheduled {
            background: #fef3c7;
            color: #b45309;
        }
    </style>
@endpush

@section('content')
    @php
        $isLive =
            $banner->is_active &&
            (!$banner->starts_at || $banner->starts_at->lte(now())) &&
            (!$banner->ends_at || $banner->ends_at->gte(now()));
        $isScheduled = $banner->is_active && $banner->starts_at && $banner->starts_at->gt(now());
        $currentType = old('type', $banner->type);
    @endphp

    <div class="pb-10" x-data="bannerEdit()">

        {{-- ── Breadcrumb ── --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
                <a href="{{ route('admin.banners.index') }}" class="hover:text-brand-600 transition-colors">Banners</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span
                    class="text-gray-700 font-semibold truncate max-w-[200px]">{{ $banner->display_admin_label }}</span>
            </div>
            <div class="flex items-center gap-2">
                {{-- Current status ── --}}
                <span class="live-badge {{ $isLive ? 'live' : ($isScheduled ? 'scheduled' : 'offline') }}">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $isLive ? 'bg-green-500' : ($isScheduled ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                    {{ $isLive ? 'Live' : ($isScheduled ? 'Scheduled' : 'Offline') }}
                </span>
                <a href="{{ route('admin.banners.index') }}"
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

        <form id="banner-form" method="POST" action="{{ route('admin.banners.update', $banner->id) }}"
            enctype="multipart/form-data" @submit.prevent="submitForm">
            @csrf
            @method('PUT')

            {{-- Hidden field for mobile image removal ── --}}
            <input type="hidden" name="remove_mobile_image" :value="removeMobileImage ? '1' : '0'">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7">

                {{-- ════════════════════════════
                 LEFT COLUMN
            ════════════════════════════ --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- 1. Basic Info ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="type" class="w-4 h-4"></i>
                            Basic Information
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="md:col-span-2">
                                <label class="field-label">Admin Label <span class="text-rose-500">*</span></label>
                                <input type="text" name="admin_label"
                                    value="{{ old('admin_label', $banner->admin_label) }}"
                                    placeholder="e.g. Home Hero – May Sale" required
                                    class="field-input {{ $errors->has('admin_label') ? 'error' : '' }}">
                                @error('admin_label')
                                    <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Banner Title <span
                                        class="text-gray-400 normal-case font-normal">(shown on storefront, optional)</span></label>
                                <input type="text" name="title" value="{{ old('title', $banner->title) }}"
                                    placeholder="e.g. Summer Sale — 50% Off"
                                    class="field-input {{ $errors->has('title') ? 'error' : '' }}">
                                @error('title')
                                    <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Subtitle</label>
                                <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}"
                                    placeholder="e.g. Shop now and save big on all plants" class="field-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Alt Text <span class="text-gray-400 normal-case font-normal">(SEO
                                        & accessibility)</span></label>
                                <input type="text" name="alt_text" value="{{ old('alt_text', $banner->alt_text) }}"
                                    placeholder="Describe the image for screen readers and Google" class="field-input">
                            </div>
                        </div>

                        {{-- Type selector ── 'promo' => 'Promo Offer', 'ad' => 'Advertisement', 'popup' => 'Popup'  --}}
                        <div class="mb-4">
                            <label class="field-label">Banner Type <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach (['hero' => 'Hero Slider', 'category' => 'Category'] as $val => $label)
                                    <label class="type-pill {{ $currentType === $val ? 'selected' : '' }}"
                                        @click="selectType('{{ $val }}')">
                                        <input type="radio" name="type" value="{{ $val }}"
                                            {{ $currentType === $val ? 'checked' : '' }} class="sr-only">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('type')
                                <p class="field-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Position ── --}}
                        <div>
                            <label class="field-label">Display Position <span class="text-red-500">*</span></label>
                            <select name="position" class="field-input {{ $errors->has('position') ? 'error' : '' }}">
                                <option value="">Select where to display</option>
                                @foreach ([
                                    'home_top' => 'Home — Top (Hero area)',
                                    'home_middle' => 'Home — Middle section',
                                    'home_bottom' => 'Home — Bottom section',
                                    'category_page' => 'Category Page',                                    
                                ] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ old('position', $banner->position) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('position')
                                <p class="field-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- 2. Images ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="image" class="w-4 h-4"></i>
                            Banner Images
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Desktop image ── --}}
                            <div>
                                <label class="field-label">
                                    Desktop Image
                                    <span class="text-gray-400 normal-case font-normal ml-1">Leave empty to keep
                                        current</span>
                                </label>

                                {{-- Show existing image if no new preview ── --}}
                                <template x-if="!previews.desktop && hasExistingImage">
                                    <div class="existing-image-wrap mb-2">
                                        <img src="{{ $banner->image_url }}"
                                            alt="{{ $banner->alt_text ?? $banner->title }}" class="upload-preview"
                                            onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.webp') }}'">
                                        <div class="existing-image-overlay">
                                            <label for="desktop-image-input" class="overlay-btn change">
                                                <i data-lucide="upload" class="w-3 h-3"></i> Change
                                            </label>
                                        </div>
                                        <span class="preview-badge">Current</span>
                                    </div>
                                </template>

                                {{-- New image preview ── --}}
                                <template x-if="previews.desktop">
                                    <div class="relative mb-2">
                                        <img :src="previews.desktop" class="upload-preview" alt="New desktop image">
                                        <span class="preview-badge" style="background: rgba(22,163,74,0.8)">New</span>
                                        <button type="button"
                                            @click.stop="clearPreview('desktop', 'desktop-image-input')"
                                            class="absolute top-2 left-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </template>

                                <div class="upload-zone" :class="(previews.desktop || hasExistingImage) ? 'mt-2' : ''"
                                    @dragover.prevent="$el.classList.add('dragover')"
                                    @dragleave="$el.classList.remove('dragover')"
                                    @drop.prevent="handleDrop($event, 'desktop')">

                                    <input type="file" name="image"
                                        accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                        @change="previewImage($event, 'desktop')" id="desktop-image-input">

                                    <div class="upload-placeholder" style="padding: 16px 20px;">
                                        <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                                        <p class="text-xs font-semibold text-gray-500">
                                            {{ $banner->image ? 'Upload new image to replace' : 'Drop or click to upload' }}
                                        </p>
                                        <p class="text-[10px] text-gray-400">JPG, PNG, WebP, SVG · Max 5MB</p>
                                    </div>
                                </div>

                                @error('image')
                                    <p class="field-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Mobile image ── --}}
                            <div>
                                <label class="field-label">
                                    Mobile Image
                                    <span class="text-gray-400 normal-case font-normal ml-1">Optional</span>
                                </label>

                                {{-- Existing mobile image ── --}}
                                <template x-if="!previews.mobile && hasExistingMobile && !removeMobileImage">
                                    <div class="existing-image-wrap mb-2">
                                        <img src="{{ $banner->mobile_image_url }}" alt="Mobile banner"
                                            class="upload-preview"
                                            onerror="this.onerror=null; this.src='{{ asset('assets/images/placeholder.webp') }}'">
                                        <div class="existing-image-overlay">
                                            <label for="mobile-image-input" class="overlay-btn change">
                                                <i data-lucide="upload" class="w-3 h-3"></i> Change
                                            </label>
                                            <button type="button"
                                                @click="removeMobileImage = true; hasExistingMobile = false"
                                                class="overlay-btn remove">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i> Remove
                                            </button>
                                        </div>
                                        <span class="preview-badge">Current Mobile</span>
                                    </div>
                                </template>

                                {{-- New mobile preview ── --}}
                                <template x-if="previews.mobile">
                                    <div class="relative mb-2">
                                        <img :src="previews.mobile" class="upload-preview" alt="New mobile image">
                                        <span class="preview-badge" style="background: rgba(22,163,74,0.8)">New</span>
                                        <button type="button" @click.stop="clearPreview('mobile', 'mobile-image-input')"
                                            class="absolute top-2 left-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </template>

                                {{-- Removed state ── --}}
                                <template x-if="removeMobileImage && !previews.mobile">
                                    <div
                                        class="mb-2 p-3 bg-red-50 border border-red-100 rounded-xl flex items-center gap-2">
                                        <i data-lucide="trash-2" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-xs font-semibold text-red-600">Mobile image will be removed on
                                            save</span>
                                        <button type="button"
                                            @click="removeMobileImage = false; hasExistingMobile = {{ $banner->mobile_image ? 'true' : 'false' }}"
                                            class="ml-auto text-xs text-gray-500 hover:text-gray-800 font-semibold underline">Undo</button>
                                    </div>
                                </template>

                                <div class="upload-zone" @dragover.prevent="$el.classList.add('dragover')"
                                    @dragleave="$el.classList.remove('dragover')"
                                    @drop.prevent="handleDrop($event, 'mobile')">

                                    <input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp"
                                        @change="previewImage($event, 'mobile')" id="mobile-image-input">

                                    <div class="upload-placeholder" style="padding: 16px 20px;">
                                        <i data-lucide="smartphone" class="w-7 h-7"></i>
                                        <p class="text-xs font-semibold text-gray-500">
                                            {{ $banner->mobile_image ? 'Upload new mobile image' : 'Optional mobile version' }}
                                        </p>
                                        <p class="text-[10px] text-gray-400">800×600px · Max 5MB</p>
                                    </div>
                                </div>

                                @error('mobile_image')
                                    <p class="field-error mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- 3. Action / Link ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="link" class="w-4 h-4"></i>
                            Call to Action
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="field-label">Link URL</label>
                                <input type="url" name="link" value="{{ old('link', $banner->link) }}"
                                    placeholder="https://yourstore.com/category/sale"
                                    class="field-input {{ $errors->has('link') ? 'error' : '' }}">
                                @error('link')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Button Text</label>
                                <input type="text" name="button_text"
                                    value="{{ old('button_text', $banner->button_text) }}" placeholder="e.g. Shop Now"
                                    class="field-input">
                            </div>
                            <div>
                                <label class="field-label">Link Target</label>
                                <select name="target" class="field-input">
                                    <option value="_self"
                                        {{ old('target', $banner->target) === '_self' ? 'selected' : '' }}>Same Tab
                                        (_self)</option>
                                    <option value="_blank"
                                        {{ old('target', $banner->target) === '_blank' ? 'selected' : '' }}>New Tab
                                        (_blank)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Targeting ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="target" class="w-4 h-4"></i>
                            Targeting
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Target Category</label>
                                <select name="category_id" class="field-input">
                                    <option value="">No specific category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id', $banner->category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                           
                        </div>
                    </div>

                </div>

                {{-- ════════════════════════════
                 RIGHT COLUMN
            ════════════════════════════ --}}
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
                                        {{ old('is_active', $banner->is_active ? '1' : '') === '1' ? 'checked' : '' }}>
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

                        <a href="{{ route('admin.banners.index') }}"
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
                                        {{ number_format($banner->view_count) }}</p>
                                </div>
                            </div>
                            <div class="mini-stat">
                                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Clicks</p>
                                    <p class="text-lg font-black text-gray-900 leading-none">
                                        {{ number_format($banner->click_count) }}</p>
                                </div>
                            </div>
                            @if ($banner->view_count > 0)
                                <div class="mini-stat">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="trending-up" class="w-4 h-4 text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">CTR</p>
                                        <p class="text-lg font-black text-gray-900 leading-none">
                                            {{ round(($banner->click_count / $banner->view_count) * 100, 1) }}%
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Sort order ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                            Display Order
                        </p>
                        <label class="field-label">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}"
                            min="0" class="field-input">
                        <p class="text-[11px] text-gray-400 mt-1.5">Lower = shown first within same type.</p>
                    </div>

                    {{-- Scheduling ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            Schedule
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="field-label">Start Date & Time</label>
                                <input type="datetime-local" name="starts_at"
                                    value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}"
                                    class="field-input {{ $errors->has('starts_at') ? 'error' : '' }}">
                                @error('starts_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">End Date & Time</label>
                                <input type="datetime-local" name="ends_at"
                                    value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}"
                                    class="field-input {{ $errors->has('ends_at') ? 'error' : '' }}">
                                @error('ends_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-note mt-3">
                            <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                            <p>Clear both dates to run indefinitely when Active.</p>
                        </div>
                    </div>

                    {{-- Advanced Meta ── --}}
                    <div class="form-section" x-data="{ open: false }">
                        <button type="button" @click="open = !open"
                            class="section-label w-full flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2">
                                <i data-lucide="sliders-horizontal" class="w-4 h-4" style="color: var(--brand-600)"></i>
                                Advanced Meta
                            </span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 transition-transform"
                                :class="{ 'rotate-180': open }"></i>
                        </button>

                        <div x-show="open" x-cloak x-transition class="space-y-4 mt-2">
                            <div>
                                <label class="field-label">Background Color</label>
                                <input type="color" name="meta[bg_color]"
                                    value="{{ old('meta.bg_color', $banner->meta['bg_color'] ?? '#ffffff') }}"
                                    class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="field-label">Text Color</label>
                                <input type="color" name="meta[text_color]"
                                    value="{{ old('meta.text_color', $banner->meta['text_color'] ?? '#000000') }}"
                                    class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="field-label">Animation</label>
                                <select name="meta[animation]" class="field-input">
                                    @foreach (['none' => 'None', 'fade' => 'Fade', 'slide' => 'Slide', 'zoom' => 'Zoom'] as $val => $label)
                                        <option value="{{ $val }}"
                                            {{ old('meta.animation', $banner->meta['animation'] ?? 'none') === $val ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
                                <span>{{ $banner->creator?->name ?? 'System' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Created at</span>
                                <span>{{ $banner->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Last updated</span>
                                <span>{{ $banner->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Banner ID</span>
                                <span class="font-mono">#{{ $banner->id }}</span>
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
        function bannerEdit() {
            return {
                isSubmitting: false,
                removeMobileImage: false,
                hasExistingImage: {{ $banner->image ? 'true' : 'false' }},
                hasExistingMobile: {{ $banner->mobile_image ? 'true' : 'false' }},

                previews: {
                    desktop: null,
                    mobile: null,
                },

                selectType(val) {
                    document.querySelectorAll('input[name="type"]').forEach(r => {
                        r.checked = r.value === val;
                        r.closest('.type-pill').classList.toggle('selected', r.value === val);
                    });
                    console.log('[BannerEdit] Type changed to:', val);
                },

                previewImage(event, key) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const maxMB = 5;
                    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml'];

                    if (!allowed.includes(file.type)) {
                        BizAlert.toast(`Invalid file type: ${file.type}`, 'error');
                        event.target.value = '';
                        console.warn('[BannerEdit] Invalid file type:', file.type);
                        return;
                    }

                    if (file.size > maxMB * 1024 * 1024) {
                        BizAlert.toast(`File too large. Max ${maxMB}MB allowed.`, 'error');
                        event.target.value = '';
                        console.warn('[BannerEdit] File too large:', (file.size / 1024 / 1024).toFixed(2) + 'MB');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previews[key] = e.target.result;
                        // If user uploads new mobile, cancel any remove intent
                        if (key === 'mobile') this.removeMobileImage = false;
                        console.log('[BannerEdit] New preview set for:', key, file.name, (file.size / 1024).toFixed(1) +
                            'KB');
                    };
                    reader.readAsDataURL(file);
                },

                handleDrop(event, key) {
                    event.currentTarget.classList.remove('dragover');
                    const file = event.dataTransfer.files[0];
                    if (!file) return;

                    const inputId = key === 'desktop' ? 'desktop-image-input' : 'mobile-image-input';
                    const input = document.getElementById(inputId);
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;

                    this.previewImage({
                        target: input
                    }, key);
                    console.log('[BannerEdit] File dropped onto:', key);
                },

                clearPreview(key, inputId) {
                    this.previews[key] = null;
                    const input = document.getElementById(inputId);
                    if (input) input.value = '';
                    console.log('[BannerEdit] Preview cleared for:', key);
                },

                async submitForm() {
                    this.isSubmitting = true;
                    console.log('[BannerEdit] Submitting update...', {
                        removeMobileImage: this.removeMobileImage,
                        hasNewDesktop: !!this.previews.desktop,
                        hasNewMobile: !!this.previews.mobile,
                    });

                    try {
                        document.getElementById('banner-form').submit();
                    } catch (err) {
                        console.error('[BannerEdit] Submit error:', err);
                        BizAlert.toast('Submission failed. Please try again.', 'error');
                        this.isSubmitting = false;
                    }
                },

                init() {
                    console.log('[BannerEdit] Initialized for banner #{{ $banner->id }}', {
                        type: '{{ $banner->type }}',
                        position: '{{ $banner->position }}',
                        is_active: {{ $banner->is_active ? 'true' : 'false' }},
                        hasImage: {{ $banner->image ? 'true' : 'false' }},
                        hasMobile: {{ $banner->mobile_image ? 'true' : 'false' }},
                        views: {{ $banner->view_count }},
                        clicks: {{ $banner->click_count }},
                    });
                }
            }
        }
    </script>
@endpush
