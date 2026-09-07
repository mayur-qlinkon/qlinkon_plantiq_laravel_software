@extends('layouts.admin')

@section('title', 'Create Banner')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Create Banner</h1>
        {{-- <p class="text-xs text-gray-400 font-medium mt-0.5">Add a new banner to your storefront</p> --}}
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ── Field styles ── */
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

        /* ── Section card ── */        
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

        /* ── Error message ── */
        .field-error {
            font-size: 11px;
            font-weight: 600;
            color: #f43f5e;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap-4px;
        }

        /* ── Preview badge ── */
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
            backdrop-blur: 4px;
        }

        /* ── Scheduling info ── */
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
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="bannerCreate()">

        {{-- ── Breadcrumb + Header ── --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm text-gray-400 font-medium">
                <a href="{{ route('admin.banners.index') }}" class="hover:text-brand-600 transition-colors">Banners</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-gray-700 font-semibold">Create New</span>
            </div>
            <a href="{{ route('admin.banners.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
            </a>
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

        <form id="banner-form" method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data"
            @submit.prevent="submitForm">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7">

                {{-- ════════════════════════════
                 LEFT COLUMN — main fields
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
                                <input type="text" name="admin_label" value="{{ old('admin_label') }}"
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
                                <input type="text" name="title" value="{{ old('title') }}"
                                    placeholder="e.g. Summer Sale — 50% Off"
                                    class="field-input {{ $errors->has('title') ? 'error' : '' }}">
                                @error('title')
                                    <p class="field-error"><i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        {{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Subtitle <span
                                        class="text-gray-400 normal-case font-normal">(optional)</span></label>
                                <input type="text" name="subtitle" value="{{ old('subtitle') }}"
                                    placeholder="e.g. Shop now and save big on all plants" class="field-input">
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Alt Text <span class="text-gray-400 normal-case font-normal">(for
                                        SEO & accessibility)</span></label>
                                <input type="text" name="alt_text" value="{{ old('alt_text') }}"
                                    placeholder="Describe the image for screen readers and Google" class="field-input">
                            </div>
                        </div>

                        {{-- Type selector ── 'promo' => 'Promo Offer', 'ad' => 'Advertisement', 'popup' => 'Popup' Removed--}}
                        <div class="mb-4">
                            <label class="field-label">Banner Type <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach (['hero' => 'Hero Slider', 'category' => 'Category'] as $val => $label) 
                                    <label class="type-pill {{ old('type', 'hero') === $val ? 'selected' : '' }}"
                                        @click="selectType('{{ $val }}')">
                                        <input type="radio" name="type" value="{{ $val }}"
                                            {{ old('type', 'hero') === $val ? 'checked' : '' }} class="sr-only">
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
                                    <option value="{{ $val }}" {{ old('position') === $val ? 'selected' : '' }}>
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
                                    Desktop Image <span class="text-red-500">*</span>
                                    <span class="text-gray-400 normal-case font-normal ml-1">1920×600px recommended</span>
                                </label>
                                <div class="upload-zone" @dragover.prevent="$el.classList.add('dragover')"
                                    @dragleave="$el.classList.remove('dragover')"
                                    @drop.prevent="handleDrop($event, 'desktop')">

                                    <input type="file" name="image"
                                        accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                        @change="previewImage($event, 'desktop')" id="desktop-image-input">

                                    <template x-if="!previews.desktop">
                                        <div class="upload-placeholder">
                                            <i data-lucide="upload-cloud" class="w-10 h-10"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-600">Drop image here or click to
                                                    browse</p>
                                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP, SVG · Max 5MB</p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="previews.desktop">
                                        <div class="relative">
                                            <img :src="previews.desktop" class="upload-preview" alt="Desktop preview">
                                            <span class="preview-badge">Desktop</span>
                                            <button type="button"
                                                @click.stop="clearPreview('desktop', 'desktop-image-input')"
                                                class="absolute top-2 left-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center hover:bg-red-600 transition-colors">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                @error('image')
                                    <p class="field-error mt-2">{{ $message }}</p>
                                @enderror
                                <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                                    <i data-lucide="info" class="w-3 h-3"></i>
                                    Automatically converted to WebP for performance
                                </p>
                            </div>

                            {{-- Mobile image ── --}}
                            <div>
                                <label class="field-label">
                                    Mobile Image
                                    <span class="text-gray-400 normal-case font-normal ml-1">800×600px recommended</span>
                                </label>
                                <div class="upload-zone" @dragover.prevent="$el.classList.add('dragover')"
                                    @dragleave="$el.classList.remove('dragover')"
                                    @drop.prevent="handleDrop($event, 'mobile')">

                                    <input type="file" name="mobile_image" accept="image/jpeg,image/png,image/webp"
                                        @change="previewImage($event, 'mobile')" id="mobile-image-input">

                                    <template x-if="!previews.mobile">
                                        <div class="upload-placeholder">
                                            <i data-lucide="smartphone" class="w-10 h-10"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-600">Optional mobile version</p>
                                                <p class="text-xs text-gray-400 mt-1">If empty, desktop image is used</p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="previews.mobile">
                                        <div class="relative">
                                            <img :src="previews.mobile" class="upload-preview" alt="Mobile preview">
                                            <span class="preview-badge">Mobile</span>
                                            <button type="button"
                                                @click.stop="clearPreview('mobile', 'mobile-image-input')"
                                                class="absolute top-2 left-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center hover:bg-red-600 transition-colors">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </div>
                                    </template>
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
                                <input type="url" name="link" value="{{ old('link') }}"
                                    placeholder="https://yourstore.com/category/sale"
                                    class="field-input {{ $errors->has('link') ? 'error' : '' }}">
                                @error('link')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">Button Text</label>
                                <input type="text" name="button_text" value="{{ old('button_text') }}"
                                    placeholder="e.g. Shop Now, Learn More" class="field-input">
                            </div>
                            <div>
                                <label class="field-label">Link Target</label>
                                <select name="target" class="field-input">
                                    <option value="_self" {{ old('target', '_self') === '_self' ? 'selected' : '' }}>
                                        Same Tab (_self)
                                    </option>
                                    <option value="_blank" {{ old('target') === '_blank' ? 'selected' : '' }}>
                                        New Tab (_blank)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Targeting ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="target" class="w-4 h-4"></i>
                            Targeting
                            <span class="text-gray-400 normal-case font-normal text-[11px] font-medium ml-1">Link to a
                                specific category</span>
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Target Category</label>
                                <select name="category_id" class="field-input">
                                    <option value="">No specific category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                           
                        </div>
                    </div>

                </div>

                {{-- ════════════════════════════
                 RIGHT COLUMN — sidebar settings
            ════════════════════════════ --}}
                <div class="space-y-4">

                    {{-- Publish card ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Publish
                        </p>

                        <div class="space-y-3 mb-5">
                            <div class="toggle-row">
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Active</p>
                                    <p class="text-[11px] text-gray-400">Show on storefront immediately</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_active" value="1"
                                        {{ old('is_active', '1') === '1' ? 'checked' : '' }} x-model="isActive">
                                    <div class="toggle-track"></div>
                                    <div class="toggle-thumb"></div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="isSubmitting" class="submit-btn w-full justify-center">
                            <i data-lucide="loader-2" x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin"></i>
                            <i data-lucide="check-circle" x-show="!isSubmitting" class="w-4 h-4"></i>
                            <span x-text="isSubmitting ? 'Creating...' : 'Create Banner'"></span>
                        </button>

                        <a href="{{ route('admin.banners.index') }}"
                            class="mt-3 w-full flex items-center justify-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 py-2 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                    </div>

                    {{-- Sort order ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
                            Display Order
                        </p>
                        <label class="field-label">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                            class="field-input">
                        <p class="text-[11px] text-gray-400 mt-1.5">Lower number = shown first. Leave 0 to auto-append.</p>
                    </div>

                    {{-- Scheduling ── --}}
                    <div class="form-section">
                        <p class="section-label">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            Schedule
                            <span class="text-gray-400 normal-case font-normal text-[11px] font-medium">(optional)</span>
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="field-label">Start Date & Time</label>
                                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                                    class="field-input {{ $errors->has('starts_at') ? 'error' : '' }}">
                                @error('starts_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="field-label">End Date & Time</label>
                                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
                                    class="field-input {{ $errors->has('ends_at') ? 'error' : '' }}">
                                @error('ends_at')
                                    <p class="field-error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="info-note mt-3">
                            <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                            <p>If dates are set, the banner only appears during the scheduled window — even if Active is on.
                            </p>
                        </div>
                    </div>

                    {{-- Meta (advanced) ── --}}
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
                                    value="{{ old('meta.bg_color', '#ffffff') }}"
                                    class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="field-label">Text Color</label>
                                <input type="color" name="meta[text_color]"
                                    value="{{ old('meta.text_color', '#000000') }}"
                                    class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="field-label">Animation</label>
                                <select name="meta[animation]" class="field-input">
                                    <option value="none"
                                        {{ old('meta.animation', 'none') === 'none' ? 'selected' : '' }}>None</option>
                                    <option value="fade" {{ old('meta.animation') === 'fade' ? 'selected' : '' }}>Fade
                                    </option>
                                    <option value="slide" {{ old('meta.animation') === 'slide' ? 'selected' : '' }}>
                                        Slide</option>
                                    <option value="zoom" {{ old('meta.animation') === 'zoom' ? 'selected' : '' }}>Zoom
                                    </option>
                                </select>
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
        function bannerCreate() {
            return {
                isSubmitting: false,
                isActive: true,

                previews: {
                    desktop: null,
                    mobile: null,
                },

                selectType(val) {
                    // Update all radio buttons and pill styles
                    document.querySelectorAll('input[name="type"]').forEach(r => {
                        r.checked = r.value === val;
                        r.closest('.type-pill').classList.toggle('selected', r.value === val);
                    });
                    console.log('[BannerCreate] Type selected:', val);
                },

                previewImage(event, key) {
                    const file = event.target.files[0];
                    if (!file) return;

                    // Client-side validation
                    const maxMB = 5;
                    const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml'];

                    if (!allowed.includes(file.type)) {
                        BizAlert.toast(`Invalid file type: ${file.type}`, 'error');
                        event.target.value = '';
                        console.warn('[BannerCreate] Invalid file type:', file.type);
                        return;
                    }

                    if (file.size > maxMB * 1024 * 1024) {
                        BizAlert.toast(`File too large. Max ${maxMB}MB allowed.`, 'error');
                        event.target.value = '';
                        console.warn('[BannerCreate] File too large:', (file.size / 1024 / 1024).toFixed(2) + 'MB');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previews[key] = e.target.result;
                        console.log('[BannerCreate] Preview set for:', key, file.name, (file.size / 1024).toFixed(1) +
                            'KB');
                    };
                    reader.readAsDataURL(file);
                },

                handleDrop(event, key) {
                    event.currentTarget.classList.remove('dragover');
                    const file = event.dataTransfer.files[0];
                    if (!file) return;

                    // Inject into the hidden input
                    const inputId = key === 'desktop' ? 'desktop-image-input' : 'mobile-image-input';
                    const input = document.getElementById(inputId);

                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;

                    // Trigger the preview
                    this.previewImage({
                        target: input
                    }, key);
                    console.log('[BannerCreate] Dropped file onto:', key);
                },

                clearPreview(key, inputId) {
                    this.previews[key] = null;
                    const input = document.getElementById(inputId);
                    if (input) input.value = '';
                    console.log('[BannerCreate] Preview cleared for:', key);
                },

                async submitForm() {
                    // Validate required image
                    if (!this.previews.desktop) {
                        BizAlert.toast('Please upload a desktop banner image.', 'error');
                        console.warn('[BannerCreate] Submit blocked: no desktop image');
                        return;
                    }

                    this.isSubmitting = true;
                    console.log('[BannerCreate] Submitting form...');

                    try {
                        document.getElementById('banner-form').submit();
                    } catch (err) {
                        console.error('[BannerCreate] Form submit error:', err);
                        BizAlert.toast('Submission failed. Please try again.', 'error');
                        this.isSubmitting = false;
                    }
                },

                init() {
                    console.log('[BannerCreate] Component initialized');

                    // Restore old values after validation error
                    @if (old('type'))
                        this.selectType('{{ old('type') }}');
                    @endif
                }
            }
        }
    </script>
@endpush
