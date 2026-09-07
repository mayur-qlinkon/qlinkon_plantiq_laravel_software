@extends ('layouts.platform')

@section ('title', 'System Settings - Super Admin')
@section ('header', 'Platform Settings')

@section ('content')
    <div class="mx-auto max-w-7xl pb-12" x-data="{ tab: 'general' }">
        {{-- Page Header --}}
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Platform Settings</h1>
                <p class="mt-1 text-sm text-gray-500">Configure your core system parameters, branding, and security policies.</p>
            </div>
            <button
                type="submit"
                form="settings-form"
                class="bg-brand-600 hover:bg-brand-700 shadow-brand-500/20 flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all"
            >
                <i class="fa-solid fa-cloud-arrow-up"></i> Save Configuration
            </button>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-sm"
            >
                <i class="fa-solid fa-circle-check shrink-0 text-xl text-green-600"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm"
            >
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0 text-xl text-red-600"></i>
                <div>
                    <span class="font-bold">Please correct the following issues:</span>
                    <ul class="mt-1.5 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-8 md:flex-row">
            {{-- Modern Sidebar Navigation --}}
            <aside class="w-full shrink-0 md:w-64">
                <nav
                    class="hide-scrollbar sticky top-6 flex flex-row gap-1.5 overflow-x-auto pb-4 md:flex-col md:overflow-visible md:pb-0"
                >
                    @foreach ([
                        ['key' => 'general',  'icon' => 'sliders',        'label' => 'General Basics'],
                        ['key' => 'branding', 'icon' => 'palette',        'label' => 'Visual Branding'],
                        ['key' => 'seo',      'icon' => 'magnifying-glass','label' => 'SEO & Meta'],
                        ['key' => 'mail',     'icon' => 'paper-plane',    'label' => 'SMTP Configuration'],
                        ['key' => 'security', 'icon' => 'shield-halved',  'label' => 'Security Policies'],
                        ['key' => 'system',   'icon' => 'server',         'label' => 'System & Maintenance'],
                    ] as $t)
                        <button
                            type="button"
                            @click="tab = '{{ $t['key'] }}'"
                            :class="tab === '{{ $t['key'] }}' ? 'bg-brand-50 text-brand-700 font-bold' : 'text-gray-600 hover:bg-gray-100 font-medium'"
                            class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-sm whitespace-nowrap transition-all"
                        >
                            <i
                                class="fa-solid fa-{{ $t['icon'] }} w-5 text-center shrink-0"
                                :class="tab === '{{ $t['key'] }}' ? 'text-brand-600' : 'text-gray-400'"
                            ></i>
                            {{ $t['label'] }}
                        </button>
                    @endforeach
                </nav>
            </aside>

            {{-- Main Form Content --}}
            <div class="flex-1">
                <form
                    id="settings-form"
                    action="{{ route('platform.system.update') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="min-h-[500px] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    @csrf
                    @method ('PUT')

                    {{-- ── 1. GENERAL ── --}}
                    <div x-show="tab === 'general'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">General Basics</h2>
                            <p class="mt-1 text-sm text-gray-500">Core details that represent your platform everywhere.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Application Name</label>
                                <input
                                    type="text"
                                    name="app_name"
                                    value="{{ old('app_name', $flatSettings['app_name'] ?? 'Qlinkon') }}"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Default Timezone</label>
                                <select
                                    name="timezone"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                >
                                    @foreach (['Asia/Kolkata' => 'Asia/Kolkata (IST)', 'UTC' => 'UTC', 'Asia/Dubai' => 'Asia/Dubai (GST)', 'America/New_York' => 'America/New_York (EST)'] as $tz => $label)
                                        <option
                                            value="{{ $tz }}"
                                            @selected (old('timezone', $flatSettings['timezone'] ?? 'Asia/Kolkata') === $tz)
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Support Email Address</label
                                >
                                <input
                                    type="email"
                                    name="support_email"
                                    value="{{ old('support_email', $flatSettings['support_email'] ?? '') }}"
                                    placeholder="support@example.com"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Support Phone Number</label
                                >
                                <input
                                    type="text"
                                    name="support_phone"
                                    value="{{ old('support_phone', $flatSettings['support_phone'] ?? '') }}"
                                    placeholder="+91 9876543210"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- ── 2. BRANDING ── --}}
                    <div x-show="tab === 'branding'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Visual Branding</h2>
                            <p class="mt-1 text-sm text-gray-500">Make the platform yours. Upload assets and set colors.</p>
                        </div>

                        <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-2">
                            {{-- Logo Upload --}}
                            <div
                                x-data="{ preview: '{{ !empty($flatSettings['app_logo']) ? asset('storage/'.$flatSettings['app_logo']) : '' }}' }"
                            >
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Primary Logo</label>
                                <label
                                    class="group relative flex min-h-[140px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 transition-colors hover:bg-gray-100"
                                >
                                    <div
                                        x-show="preview"
                                        class="absolute inset-0 flex items-center justify-center bg-white p-4"
                                    >
                                        <img :src="preview" class="max-h-full object-contain" alt="Logo" />
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <span class="text-sm font-bold text-white"
                                                ><i class="fa-solid fa-pen mr-1"></i> Change</span
                                            >
                                        </div>
                                    </div>
                                    <div x-show="!preview" class="text-center">
                                        <div
                                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm"
                                        >
                                            <i class="fa-solid fa-cloud-arrow-up text-brand-600"></i>
                                        </div>
                                        <span class="block text-sm font-bold text-gray-700">Upload Logo</span>
                                        <span class="mt-1 block text-xs text-gray-400">PNG, SVG up to 2MB</span>
                                    </div>
                                    <input
                                        type="file"
                                        name="app_logo"
                                        accept="image/*"
                                        class="hidden"
                                        @change="
                                            if ($event.target.files.length)
                                                preview = URL.createObjectURL($event.target.files[0]);
                                        "
                                    />
                                </label>
                            </div>

                            {{-- Favicon Upload --}}
                            <div
                                x-data="{ preview: '{{ !empty($flatSettings['app_favicon']) ? asset('storage/'.$flatSettings['app_favicon']) : '' }}' }"
                            >
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Favicon (Tab Icon)</label>
                                <label
                                    class="group relative flex min-h-[140px] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 transition-colors hover:bg-gray-100"
                                >
                                    <div
                                        x-show="preview"
                                        class="absolute inset-0 flex items-center justify-center bg-white p-4"
                                    >
                                        <img :src="preview" class="h-12 w-12 rounded-md object-contain" alt="Favicon" />
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <span class="text-sm font-bold text-white"
                                                ><i class="fa-solid fa-pen"></i
                                            ></span>
                                        </div>
                                    </div>
                                    <div x-show="!preview" class="text-center">
                                        <div
                                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm"
                                        >
                                            <i class="fa-solid fa-icons text-brand-600"></i>
                                        </div>
                                        <span class="block text-sm font-bold text-gray-700">Upload Favicon</span>
                                        <span class="mt-1 block text-xs text-gray-400">ICO, PNG (32x32px)</span>
                                    </div>
                                    <input
                                        type="file"
                                        name="app_favicon"
                                        accept="image/*,.ico"
                                        class="hidden"
                                        @change="
                                            if ($event.target.files.length)
                                                preview = URL.createObjectURL($event.target.files[0]);
                                        "
                                    />
                                </label>
                            </div>
                        </div>

                        {{-- Default Plant Profile Image — drag & drop --}}
                        <div
                            class="mb-10 border-t border-gray-100 pt-6"
                            x-data="{
                                preview: '{{ !empty($flatSettings['default_plant_profile_image']) ? asset('storage/'.$flatSettings['default_plant_profile_image']) : '' }}',
                                dragging: false,
                                setFile(file) {
                                    if (!file) return;
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    this.$refs.defaultPlantInput.files = dt.files;
                                    this.preview = URL.createObjectURL(file);
                                }
                            }"
                        >
                            <label class="mb-1 block text-sm font-semibold text-gray-700"
                                >Default Plant Profile Image</label
                            >
                            <p class="mb-3 text-xs text-gray-400">Shown in the Plan Builder modal for any plant profile that has no image of its own.</p>

                            <label
                                class="group relative flex min-h-[160px] max-w-sm cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed p-6 transition-colors"
                                :class="dragging
                                    ? 'border-brand-500 bg-brand-50'
                                    : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                                @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false"
                                @drop.prevent="
                                    dragging = false;
                                    setFile($event.dataTransfer.files[0]);
                                "
                            >
                                <div
                                    x-show="preview"
                                    class="absolute inset-0 flex items-center justify-center bg-white p-4"
                                >
                                    <img
                                        :src="preview"
                                        class="max-h-full rounded-md object-contain"
                                        alt="Default plant profile"
                                    />
                                    <div
                                        class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                    >
                                        <span class="text-sm font-bold text-white"
                                            ><i class="fa-solid fa-pen mr-1"></i> Change</span
                                        >
                                    </div>
                                </div>

                                <div x-show="!preview" class="pointer-events-none text-center">
                                    <div
                                        class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm"
                                    >
                                        <i class="fa-solid fa-seedling text-brand-600"></i>
                                    </div>
                                    <span class="block text-sm font-bold text-gray-700"
                                        >Drag &amp; drop image here</span
                                    >
                                    <span class="mt-1 block text-xs text-gray-400"
                                        >or click to browse — PNG, JPG up to 2MB</span
                                    >
                                </div>

                                <input
                                    type="file"
                                    name="default_plant_profile_image"
                                    accept="image/*"
                                    class="hidden"
                                    x-ref="defaultPlantInput"
                                    @change="
                                        if ($event.target.files.length)
                                            preview = URL.createObjectURL($event.target.files[0]);
                                    "
                                />
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-6 border-t border-gray-100 pt-6 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Primary Color</label>
                                <div class="relative flex items-center">
                                    <input
                                        type="color"
                                        name="primary_color"
                                        value="{{ old('primary_color', $flatSettings['primary_color'] ?? '#0f766e') }}"
                                        class="absolute left-1.5 h-8 w-8 cursor-pointer overflow-hidden rounded border-0 p-0 shadow-sm"
                                        style="appearance: none; -webkit-appearance: none"
                                    />
                                    <input
                                        type="text"
                                        name="primary_color_hex"
                                        value="{{ old('primary_color', $flatSettings['primary_color'] ?? '#0f766e') }}"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-12 font-mono text-sm uppercase outline-none focus:ring-2"
                                        readonly
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Hover Color</label>
                                <div class="relative flex items-center">
                                    <input
                                        type="color"
                                        name="hover_color"
                                        value="{{ old('hover_color', $flatSettings['hover_color'] ?? '#115e59') }}"
                                        class="absolute left-1.5 h-8 w-8 cursor-pointer overflow-hidden rounded border-0 p-0 shadow-sm"
                                        style="appearance: none; -webkit-appearance: none"
                                    />
                                    <input
                                        type="text"
                                        name="hover_color_hex"
                                        value="{{ old('hover_color', $flatSettings['hover_color'] ?? '#115e59') }}"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-12 font-mono text-sm uppercase outline-none focus:ring-2"
                                        readonly
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Theme Preference</label>
                                <select
                                    name="theme_config"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                >
                                    <option
                                        value="light"
                                        @selected (old('theme_config', $flatSettings['theme_config'] ?? 'light') === 'light')
                                    >
                                        Light Mode Default
                                    </option>
                                    <option
                                        value="dark"
                                        @selected (old('theme_config', $flatSettings['theme_config'] ?? '') === 'dark')
                                    >
                                        Dark Mode Default
                                    </option>
                                    <option
                                        value="auto"
                                        @selected (old('theme_config', $flatSettings['theme_config'] ?? '') === 'auto')
                                    >
                                        System Auto (OS Match)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ── 3. SEO ── --}}
                    <div x-show="tab === 'seo'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">SEO & Metadata</h2>
                            <p class="mt-1 text-sm text-gray-500">Control how your platform appears on Google and social media.</p>
                        </div>
                        <div class="max-w-3xl space-y-6">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Meta Title</label>
                                <input
                                    type="text"
                                    name="seo_title"
                                    value="{{ old('seo_title', $flatSettings['seo_title'] ?? '') }}"
                                    placeholder="Qlinkon - Modern Nursery Management"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Meta Description</label>
                                <textarea
                                    name="seo_description"
                                    rows="4"
                                    placeholder="Craft a compelling summary of your services..."
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                    >{{ old('seo_description', $flatSettings['seo_description'] ?? '') }}</textarea
                                >
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Keywords</label>
                                <input
                                    type="text"
                                    name="seo_keywords"
                                    value="{{ old('seo_keywords', $flatSettings['seo_keywords'] ?? '') }}"
                                    placeholder="saas, software, nursery, plants (comma separated)"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                />
                            </div>

                            {{-- SEO Meta Image — Drag & Drop Component --}}
                            <div
                                class="border-t border-gray-100 pt-4"
                                x-data="{
                                    preview: '{{ !empty($flatSettings['seo_meta_image']) ? asset('storage/'.$flatSettings['seo_meta_image']) : '' }}',
                                    dragging: false,
                                    setFile(file) {
                                        if (!file) return;
                                        const dt = new DataTransfer();
                                        dt.items.add(file);
                                        this.$refs.seoImageInput.files = dt.files;
                                        this.preview = URL.createObjectURL(file);
                                    }
                                }"
                            >
                                <label class="mb-1 block text-sm font-semibold text-gray-700"
                                    >SEO Meta Image (OG Image)</label
                                >
                                <p class="mb-3 text-xs text-gray-400">This image appears when your platform link is shared on social media networks like Facebook, X, or WhatsApp.</p>

                                <label
                                    class="group relative flex min-h-[160px] max-w-md cursor-pointer flex-col items-center justify-center overflow-hidden rounded-xl border-2 border-dashed p-6 transition-colors"
                                    :class="dragging
                                        ? 'border-brand-500 bg-brand-50'
                                        : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="
                                        dragging = false;
                                        setFile($event.dataTransfer.files[0]);
                                    "
                                >
                                    <div
                                        x-show="preview"
                                        class="absolute inset-0 flex items-center justify-center bg-white p-4"
                                    >
                                        <img
                                            :src="preview"
                                            class="max-h-full rounded-md object-contain"
                                            alt="SEO Preview Image"
                                        />
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <span class="text-sm font-bold text-white"
                                                ><i class="fa-solid fa-pen mr-1"></i> Change Image</span
                                            >
                                        </div>
                                    </div>

                                    <div x-show="!preview" class="pointer-events-none text-center">
                                        <div
                                            class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white shadow-sm"
                                        >
                                            <i class="fa-solid fa-image text-brand-600"></i>
                                        </div>
                                        <span class="block text-sm font-bold text-gray-700"
                                            >Drag &amp; drop your SEO image here</span
                                        >
                                        <span class="mt-1 block text-xs text-gray-400"
                                            >or click to browse — PNG, JPG (1200x630px recommended)</span
                                        >
                                    </div>

                                    <input
                                        type="file"
                                        name="seo_meta_image"
                                        accept="image/*"
                                        class="hidden"
                                        x-ref="seoImageInput"
                                        @change="
                                            if ($event.target.files.length)
                                                preview = URL.createObjectURL($event.target.files[0]);
                                        "
                                    />
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ── 4. SMTP & MAIL ── --}}
                    <div x-show="tab === 'mail'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">SMTP Configuration</h2>
                            <p class="mt-1 text-sm text-gray-500">Setup outbound email delivery for invoices, OTPs, and welcome mails.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Mail Driver</label>
                                <select
                                    name="mail_driver"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                >
                                    <option
                                        value="smtp"
                                        @selected (old('mail_driver', $flatSettings['mail_driver'] ?? 'smtp') === 'smtp')
                                    >
                                        SMTP (Standard)
                                    </option>
                                    <option
                                        value="mailgun"
                                        @selected (old('mail_driver', $flatSettings['mail_driver'] ?? '') === 'mailgun')
                                    >
                                        Mailgun
                                    </option>
                                    <option
                                        value="ses"
                                        @selected (old('mail_driver', $flatSettings['mail_driver'] ?? '') === 'ses')
                                    >
                                        Amazon SES
                                    </option>
                                    <option
                                        value="log"
                                        @selected (old('mail_driver', $flatSettings['mail_driver'] ?? '') === 'log')
                                    >
                                        Log (Testing Mode)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Encryption Method</label>
                                <select
                                    name="mail_encryption"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                >
                                    <option
                                        value="tls"
                                        @selected (old('mail_encryption', $flatSettings['mail_encryption'] ?? 'tls') === 'tls')
                                    >
                                        TLS (Recommended)
                                    </option>
                                    <option
                                        value="ssl"
                                        @selected (old('mail_encryption', $flatSettings['mail_encryption'] ?? '') === 'ssl')
                                    >
                                        SSL
                                    </option>
                                    <option
                                        value=""
                                        @selected (old('mail_encryption', $flatSettings['mail_encryption'] ?? '') === '')
                                    >
                                        No Encryption
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">SMTP Host</label>
                                <input
                                    type="text"
                                    name="mail_host"
                                    value="{{ old('mail_host', $flatSettings['mail_host'] ?? '') }}"
                                    placeholder="smtp.mailtrap.io"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">SMTP Port</label>
                                <input
                                    type="number"
                                    name="mail_port"
                                    value="{{ old('mail_port', $flatSettings['mail_port'] ?? 587) }}"
                                    placeholder="587"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Username</label>
                                <input
                                    type="text"
                                    name="mail_username"
                                    value="{{ old('mail_username', $flatSettings['mail_username'] ?? '') }}"
                                    placeholder="postmaster@yourdomain.com"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block flex justify-between text-sm font-semibold text-gray-700">
                                    <span>Password</span>
                                    @if (!empty($flatSettings['mail_password']))
                                        <span class="text-xs font-normal text-green-600"
                                            >Saved (Leave blank to keep)</span
                                        >
                                    @endif
                                </label>
                                <input
                                    type="password"
                                    name="mail_password"
                                    placeholder="••••••••••••"
                                    autocomplete="new-password"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 font-mono text-sm outline-none focus:ring-2"
                                />
                            </div>
                            <div
                                class="col-span-1 mt-2 grid grid-cols-1 gap-8 border-t border-gray-100 pt-6 md:col-span-2 md:grid-cols-2"
                            >
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700">From Email</label>
                                    <input
                                        type="email"
                                        name="mail_from_email"
                                        value="{{ old('mail_from_email', $flatSettings['mail_from_email'] ?? '') }}"
                                        placeholder="no-reply@yourdomain.com"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2"
                                    />
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-700">From Name</label>
                                    <input
                                        type="text"
                                        name="mail_from_name"
                                        value="{{ old('mail_from_name', $flatSettings['mail_from_name'] ?? '') }}"
                                        placeholder="Qlinkon Billing"
                                        class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── 5. SECURITY ── --}}
                    <div x-show="tab === 'security'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Security Policies</h2>
                            <p class="mt-1 text-sm text-gray-500">Enforce strict rules for tenant registration and verifications.</p>
                        </div>
                        <div class="max-w-3xl space-y-5">
                            @foreach ([
                                ['key' => 'allow_public_registration', 'label' => 'Public Registration',  'desc' => 'Allow new companies to sign up via the public pricing page.'],
                                ['key' => 'force_email_verification',  'label' => 'Force Email Verification',   'desc' => 'Require email validation before granting dashboard access.'],
                                ['key' => 'enable_2fa',                'label' => 'Require 2FA',    'desc' => 'Force all platform users to setup Authenticator App 2FA.'],
                            ] as $toggle)
                                <label
                                    class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-5 transition-all hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $toggle['label'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ $toggle['desc'] }}</p>
                                    </div>

                                    {{-- Modern Pure Tailwind Toggle --}}
                                    <div class="relative ml-4 inline-flex cursor-pointer items-center">
                                        <input type="hidden" name="{{ $toggle['key'] }}" value="false" />
                                        <input
                                            type="checkbox"
                                            name="{{ $toggle['key'] }}"
                                            value="true"
                                            class="peer sr-only"
                                            @checked (filter_var(old($toggle['key'], $flatSettings[$toggle['key']] ?? false), FILTER_VALIDATE_BOOLEAN))
                                        />
                                        <div
                                            class="peer peer-checked:bg-brand-600 h-6 w-11 rounded-full bg-gray-200 peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
                                        ></div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-8 grid max-w-3xl grid-cols-1 gap-8 border-t border-gray-100 pt-8 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700"
                                    >Password Reset Expiry (Mins)</label
                                >
                                <input
                                    type="number"
                                    name="password_reset_expiry_minutes"
                                    value="{{ old('password_reset_expiry_minutes', $flatSettings['password_reset_expiry_minutes'] ?? 60) }}"
                                    min="5"
                                    max="1440"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2"
                                />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700"
                                    >OTP Length (Digits)</label
                                >
                                <input
                                    type="number"
                                    name="otp_length"
                                    value="{{ old('otp_length', $flatSettings['otp_length'] ?? 6) }}"
                                    min="4"
                                    max="8"
                                    class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:ring-2"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- ── 6. SYSTEM & MAINTENANCE ── --}}
                    <div x-show="tab === 'system'" x-cloak class="p-8">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-gray-900">System & Maintenance</h2>
                            <p class="mt-1 text-sm text-gray-500">Live server details and critical performance actions.</p>
                        </div>

                        {{-- Maintenance Mode Widget --}}
                        <label
                            class="mb-10 flex cursor-pointer flex-col justify-between rounded-xl border border-orange-200 bg-orange-50 p-6 transition-colors hover:bg-orange-100/50 sm:flex-row sm:items-center"
                        >
                            <div class="mb-4 sm:mb-0">
                                <div class="mb-1.5 flex items-center gap-2">
                                    <i class="fa-solid fa-triangle-exclamation text-orange-600"></i>
                                    <p class="text-base font-bold text-orange-900">Enable Maintenance Mode</p>
                                </div>
                                <p class="text-sm text-orange-700">Shuts down public facing tenant routes. Super Admin portal remains accessible.</p>
                            </div>
                            {{-- Modern Pure Tailwind Toggle --}}
                            <div class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="hidden" name="maintenance_mode" value="false" />
                                <input
                                    type="checkbox"
                                    name="maintenance_mode"
                                    value="true"
                                    class="peer sr-only"
                                    @checked (filter_var(old('maintenance_mode', $flatSettings['maintenance_mode'] ?? false), FILTER_VALIDATE_BOOLEAN))
                                />
                                <div
                                    class="peer h-7 w-14 rounded-full bg-orange-200 peer-checked:bg-orange-600 peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-6 after:w-6 after:rounded-full after:border after:border-orange-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
                                ></div>
                            </div>
                        </label>

                        {{-- Readonly Server Details Grid --}}
                        <div class="mb-10">
                            <h3 class="mb-4 text-xs font-bold tracking-widest text-gray-400 uppercase">
                                Live Server Diagnostics
                            </h3>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <span class="mb-1 block text-xs text-gray-500">Framework</span>
                                    <span class="flex items-center gap-2 font-bold text-gray-900"
                                        ><i class="fa-brands fa-laravel text-red-500"></i>
                                        {{ $systemInfo['Laravel'] ?? 'N/A' }}</span
                                    >
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <span class="mb-1 block text-xs text-gray-500">PHP Version</span>
                                    <span class="flex items-center gap-2 font-bold text-gray-900"
                                        ><i class="fa-brands fa-php text-indigo-500"></i>
                                        {{ $systemInfo['PHP'] ?? 'N/A' }}</span
                                    >
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <span class="mb-1 block text-xs text-gray-500">Environment</span>
                                    <span
                                        class="font-bold text-gray-900"
                                        >{{ ucfirst($systemInfo['Environment'] ?? 'N/A') }}</span
                                    >
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <span class="mb-1 block text-xs text-gray-500">Database</span>
                                    <span
                                        class="font-bold text-gray-900"
                                        >{{ ucfirst($systemInfo['Database'] ?? 'N/A') }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- Action Zone --}}
                        <div class="overflow-hidden rounded-xl border border-gray-200">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4"
                            >
                                <div>
                                    <h4 class="font-bold text-gray-900">Clear Application Cache</h4>
                                    <p class="mt-0.5 text-sm text-gray-500">Flush config, views, and route caches. Useful after deployments.</p>
                                </div>
                                <button
                                    type="button"
                                    onclick="
                                        bizAlert(
                                            'cache-form',
                                            'Clear Cache?',
                                            'This will clear all application caches. It is safe to do.',
                                            'Yes, Clear Cache',
                                            '#0f766e',
                                        )
                                    "
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                                >
                                    <i class="fa-solid fa-broom mr-1"></i> Clear Cache
                                </button>
                            </div>
                            <div class="flex items-center justify-between bg-red-50 px-6 py-4">
                                <div>
                                    <h4 class="font-bold text-red-900">Factory Reset</h4>
                                    <p class="mt-0.5 text-sm text-red-700">Wipe all custom settings and revert to default configurations.</p>
                                </div>
                                <button
                                    type="button"
                                    onclick="
                                        bizAlert(
                                            'reset-form',
                                            'Factory Reset?',
                                            'WARNING: This will permanently delete all your custom settings. You cannot undo this.',
                                            'Yes, Reset Now',
                                            '#dc2626',
                                        )
                                    "
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700"
                                >
                                    <i class="fa-solid fa-skull mr-1"></i> Reset Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Safe Standalone Forms for Buttons --}}
    <form id="cache-form" action="{{ route('platform.system.clear-cache') }}" method="POST" class="hidden">
        @csrf
    </form>
    <form id="reset-form" action="{{ route('platform.system.reset') }}" method="POST" class="hidden">
        @csrf
    </form>
@endsection

@section ('scripts')
    <script>
        // BizAlert Wrapper for SweetAlert2
        function bizAlert(formId, title, text, confirmBtnText, confirmColor) {
            Swal.fire({
                title: title,
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: "#6b7280",
                confirmButtonText: confirmBtnText,
                customClass: {
                    popup: "rounded-2xl",
                    confirmButton: "rounded-lg px-5 py-2.5 font-bold",
                    cancelButton: "rounded-lg px-5 py-2.5 font-bold",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }

        // Optional style tweak to hide Alpine flashing
        document.addEventListener("alpine:init", () => {
            let el = document.querySelector("[x-cloak]");
            if (el) el.removeAttribute("x-cloak");
        });
    </script>
@endsection
