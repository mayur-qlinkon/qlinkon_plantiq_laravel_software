@extends ('layouts.admin')

@section('title', 'Settings')

@section('header-title')
    <div>
        <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Settings</h1>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 36px;
            height: 36px;
            padding: 0;
            cursor: pointer;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 50%;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: all 150ms ease;
            cursor: pointer;
            outline: none;
            background: transparent;
            border-top: none;
            border-left: none;
            border-right: none;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .tab-btn:hover {
            color: #334155;
            background: rgba(241, 245, 249, 0.5);
        }

        .tab-btn.active {
            color: var(--brand-600);
            border-bottom-color: var(--brand-600);
            background: #ffffff;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .tab-btn.active .tab-icon {
            color: var(--brand-600);
        }

        .tab-icon {
            color: #94a3b8;
            transition: color 150ms ease;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
            color: #1e293b;
            outline: none;
            transition: all 200ms ease;
            background: #f8fafc;
            font-family: inherit;
        }

        .field-input:focus {
            background: #ffffff;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-500) 15%, transparent);
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
            min-height: 80px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 1.5px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--brand-600);
        }

        .upload-zone {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
            transition: border-color 150ms ease;
        }

        .upload-zone:hover {
            border-color: var(--brand-600);
        }

        .toggle-wrap {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            border: 1.5px solid #f3f4f6;
            border-radius: 12px;
            background: #fafafa;
            gap: 14px;
        }

        .color-preview-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        /* 🌟 Custom Multi-Select Styles */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f3f4f6;
            /* gray-100 */
            border: 1px solid #e5e7eb;
            /* gray-200 */
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            /* gray-700 */
        }

        .chip button {
            color: #9ca3af;
            transition: color 150ms;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .chip button:hover {
            color: #ef4444;
            /* red-500 */
        }

        .multi-select-container {
            position: relative;
            width: 100%;
        }

        .multi-select-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 50;
            max-height: 200px;
            overflow-y: auto;
        }

        .multi-select-option {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 13px;
            transition: background 150ms;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .multi-select-option:hover {
            background: #f9fafb;
            /* gray-50 */
        }

        .save-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--brand-600);
            color: #fff;
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background 150ms ease, transform 80ms ease, opacity 150ms ease;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--brand-600) 35%, transparent);
        }

        .save-btn:hover {
            background: var(--brand-700);
        }

        .save-btn:active {
            transform: scale(0.97);
        }

        .save-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .danger-zone {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 14px;
            padding: 20px;
        }

        .warning-zone {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 14px;
            padding: 20px;
        }

        .success-zone {
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 14px;
            padding: 20px;
        }

        .banner-link-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border: 1.5px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            transition: border-color 150ms, box-shadow 150ms;
            text-decoration: none;
        }

        .banner-link-card:hover {
            border-color: var(--brand-600);
            box-shadow: 0 2px 12px color-mix(in srgb, var(--brand-600) 12%, transparent);
        }
    </style>
@endpush

@section('content')
    <div class="mx-auto w-full min-w-0 max-w-5xl px-4 sm:px-6 lg:px-8 py-6 pb-12" x-data="settingsApp()">
        {{-- ── Top Bar ── --}}
        <div class="mb-8">
            <p class="text-sm font-medium text-slate-500 mt-1">Manage legal details, branding, billing and system
                configurations.</p>
        </div>

        {{-- ── Main Card ── --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            {{-- Tab Bar --}}
            <div
                class="hide-scrollbar flex w-full min-w-0 overflow-x-auto rounded-t-2xl border-b border-slate-100 bg-slate-50/50 px-2 pt-2">
                <template x-for="tab in tabs" :key="tab.id">
                    <button type="button" class="tab-btn" :class="{ active: activeTab === tab.id }"
                        @click="activeTab = tab.id">
                        <i :data-lucide="tab.icon" class="tab-icon h-4 w-4"></i>
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </div>

            <form id="settings-form" @submit.prevent="submitForm">
                @csrf
                <div class="p-6 sm:p-8" x-show="activeTab !== 'notifications'">
                    {{-- ════════════════════════════════
                        TAB 1 — COMPANY
                    ════════════════════════════════ --}}
                    <div x-show="activeTab === 'company'" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0">
                        <p class="section-title"><i data-lucide="building-2" class="h-4 w-4"></i> Legal Entity Details</p>

                        <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="field-label">Company / Legal Name <span class="text-red-500">*</span></label>
                                <input type="text" name="company_name" value="{{ $company->name ?? '' }}"
                                    placeholder="As registered with ROC / GST" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Company Slug / URL Handle <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="company_slug" value="{{ $company->slug ?? '' }}"
                                    placeholder="e.g. unique-store-handle" class="field-input lowercase" required />
                            </div>
                            <div>
                                <label class="field-label">GSTIN <span class="text-red-500">*</span></label>
                                <input type="text" name="gst_number" value="{{ $company->gst_number ?? '' }}"
                                    placeholder="15-digit GSTIN" maxlength="15" class="field-input uppercase" />
                                <p class="mt-1.5 text-[11px] text-gray-400">Used on all tax invoices and e-way bills</p>
                            </div>
                            <div>
                                <label class="field-label">PAN Number</label>
                                <input type="text" name="pan_number" value="{{ get_setting('pan_number') }}"
                                    placeholder="10-digit PAN" maxlength="10" class="field-input uppercase" />
                            </div>
                            @php
                                $registrationTypeOptions = [
                                    'regular' => 'Regular',
                                    'composition' => 'Composition',
                                    'unregistered' => 'Unregistered',
                                    'sez' => 'SEZ',
                                ];
                                $fyStartOptions = [
                                    'april' => 'April (Standard — Indian FY)',
                                    'january' => 'January',
                                ];
                                $currencyOptions = [
                                    'INR' => '₹ INR — Indian Rupee',
                                ];

                                // String keys — an integer-keyed array makes
                                // x-custom-select reset to index 0 on render.
                                $stateIdOptions = [];
                                foreach ($states ?? [] as $state) {
                                    $stateIdOptions[(string) $state->id] = $state->name . ' (' . $state->code . ')';
                                }
                            @endphp

                            <div>
                                <label class="field-label">Registration Type</label>
                                {{-- No default here: the old markup showed "Regular"
                                     whenever the setting was unset, which read as a
                                     saved choice when nothing was actually stored. --}}
                                <x-custom-select name="registration_type" :options="$registrationTypeOptions" :selected="get_setting('registration_type')"
                                    placeholder="Select type" />
                            </div>
                            <div>
                                <label class="field-label">Financial Year Start</label>
                                <x-custom-select name="fy_start" :options="$fyStartOptions" :selected="get_setting('fy_start', 'april')"
                                    placeholder="Select month" />
                                <p class="mt-1.5 text-[11px] text-gray-400">Affects reports and GST return periods</p>
                            </div>
                            <div>
                                <label class="field-label">Company Email</label>
                                <input type="email" name="company_email" value="{{ $company->email ?? '' }}"
                                    placeholder="billing@company.com" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Company Phone</label>
                                <input type="tel" name="company_phone" value="{{ $company->phone ?? '' }}"
                                    placeholder="10-digit number" maxlength="10" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Currency</label>
                                <x-custom-select name="currency" :options="$currencyOptions" :selected="$company->currency ?? 'INR'"
                                    placeholder="Select currency" />
                            </div>
                            <div>
                                <label class="field-label">Default State (Place of Supply)</label>
                                <x-custom-select name="state_id" :options="$stateIdOptions" :selected="(string) ($company->state_id ?? '')"
                                    placeholder="Select State" />
                                <p class="mt-1.5 text-[11px] text-gray-400">Default place of supply on new invoices</p>
                            </div>
                        </div>

                        <p class="section-title"><i data-lucide="map-pin" class="h-4 w-4"></i> Registered Address</p>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                            <div class="md:col-span-3">
                                <label class="field-label">Street Address</label>
                                <textarea name="address" class="field-input" rows="2"
                                    placeholder="Office / factory address as per GST registration">{{ $company->address ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="field-label">City</label>
                                <input type="text" name="city" value="{{ $company->city ?? '' }}"
                                    placeholder="e.g. Ahmedabad" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">PIN Code</label>
                                <input type="text" name="zip_code" value="{{ $company->zip_code ?? '' }}"
                                    placeholder="6-digit PIN" maxlength="6" class="field-input" />
                            </div>
                            <div>
                                <label class="field-label">Country</label>
                                <input type="text" name="country" value="{{ $company->country ?? 'India' }}"
                                    class="field-input" readonly />
                            </div>
                        </div>
                    </div>

                    {{-- ════════════════════════════════
                 TAB 2 — BRANDING
            ════════════════════════════════ --}}
                    <div x-show="activeTab === 'branding'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                            <div
                                class="w-9 h-9 rounded-xl bg-pink-50 flex items-center justify-center border border-pink-100">
                                <i data-lucide="paint-bucket" class="w-4 h-4 text-pink-600"></i>
                            </div>
                            <h2 class="text-base font-bold text-slate-800">Theme Colors</h2>
                        </div>

                        <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Primary Color Card -->
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-[var(--brand-500)] transition-colors">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Primary Brand Color</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Buttons, active nav, badges</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono font-medium text-slate-400 uppercase"
                                        x-text="theme.primary"></span>
                                    <input type="color" name="primary_color" x-model="theme.primary"
                                        @input="livePreviewColors()">
                                </div>
                            </div>

                            <!-- Hover Color Card -->
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl border border-slate-200 bg-slate-50/50 hover:border-[var(--brand-500)] transition-colors">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Accent / Hover Color</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Hover states, deep accents</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-mono font-medium text-slate-400 uppercase"
                                        x-text="theme.hover"></span>
                                    <input type="color" name="primary_hover_color" x-model="theme.hover"
                                        @input="livePreviewColors()">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                            <div
                                class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100">
                                <i data-lucide="image" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <h2 class="text-base font-bold text-slate-800">Identity Assets</h2>
                        </div>

                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Favicon Upload -->
                            <div>
                                <label class="field-label">Browser Favicon</label>
                                <div
                                    class="relative flex items-center gap-4 p-4 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-[var(--brand-400)] transition-all group cursor-pointer overflow-hidden">
                                    <input type="file" name="favicon" @change="previewFile($event, 'favicon')"
                                        accept="image/png,image/x-icon,image/svg+xml"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                    <div
                                        class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 shadow-sm p-2 group-hover:shadow">
                                        <img :src="previews.favicon ||
                                            '{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/images/placeholder.webp') }}'"
                                            class="w-full h-full object-contain" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">Upload Favicon</p>
                                        <p class="text-[11px] text-slate-500 mt-1">ICO or PNG, 32x32px</p>
                                        <p class="text-[11px] font-medium text-[var(--brand-600)] mt-1">Browse files</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ════════════════════════════════
                            TAB 5 — SYSTEM
                        ════════════════════════════════ --}}
                    <div x-show="activeTab === 'system'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                            <div
                                class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center border border-teal-100">
                                <i data-lucide="settings-2" class="w-4 h-4 text-teal-600"></i>
                            </div>
                            <h2 class="text-base font-bold text-slate-800">Preferences</h2>
                        </div>

                        <!-- Batch Tracking Toggle -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-5 rounded-2xl border border-slate-200 bg-white mb-5 gap-4">
                            <div class="pr-4">
                                <p class="text-sm font-bold text-slate-800">Enable Batch / Lot Tracking</p>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Track stock by expiry date and apply
                                    FIFO during sales automatically. Recommended for medical and food businesses.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center flex-shrink-0">
                                <input type="checkbox" name="enable_batch_tracking" value="1"
                                    {{ get_setting('enable_batch_tracking', 0) ? 'checked' : '' }} class="peer sr-only" />
                                <div
                                    class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[var(--brand-600)] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--brand-500)]/30">
                                </div>
                            </label>
                        </div>

                        @if (has_module('plant_education'))
                            <!-- Storefront Pricing Toggle -->
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between p-5 rounded-2xl border border-slate-200 bg-white mb-8 gap-4">
                                <div class="pr-4">
                                    <p class="text-sm font-bold text-slate-800">Display Prices on Storefront</p>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">When disabled, product prices
                                        are hidden on all public storefront pages. Turn this off if you run an inquiry-based
                                        or B2B business.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center flex-shrink-0">
                                    <input type="checkbox" name="enable_product_pricing" value="1"
                                        {{ get_setting('enable_product_pricing', 1) ? 'checked' : '' }}
                                        class="peer sr-only" />
                                    <div
                                        class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[var(--brand-600)] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--brand-500)]/30">
                                    </div>
                                </label>
                            </div>
                        @else
                            <div class="mb-8"></div>
                        @endif

                        @if (has_permission('settings.clear_cache'))
                            <div
                                class="flex flex-col items-start sm:flex-row sm:items-center justify-between p-5 rounded-2xl border border-amber-100 bg-amber-50/50 mb-5 gap-4">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                        <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-amber-900">System Cache</p>
                                        <p class="text-xs text-amber-700/80 mt-1">If recent changes are not reflecting,
                                            purge the cache.</p>
                                    </div>
                                </div>
                                <button type="button" @click="clearCache()" :disabled="isClearingCache"
                                    class="flex-shrink-0 whitespace-nowrap px-4 py-2 bg-white border border-amber-200 text-amber-700 text-xs font-bold rounded-xl shadow-sm hover:bg-amber-600 hover:text-white hover:border-amber-600 transition-colors disabled:opacity-50">
                                    <i data-lucide="loader-2" x-show="isClearingCache"
                                        class="h-3.5 w-3.5 inline-block mr-1 animate-spin"></i>
                                    <span x-text="isClearingCache ? 'Clearing...' : 'Purge Cache'"></span>
                                </button>
                            </div>
                        @endif

                        @if (has_permission('settings.audit'))
                            <a href="{{ route('admin.settings.audit') }}"
                                class="flex flex-col items-start sm:flex-row sm:items-center justify-between p-5 rounded-2xl border border-slate-200 bg-white hover:border-[var(--brand-500)] transition-all mb-8 gap-4 group">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <i data-lucide="history" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">Settings Change History</p>
                                        <p class="text-xs text-slate-500 mt-1">View who changed what and when — full audit
                                            trail</p>
                                    </div>
                                </div>
                                <i data-lucide="arrow-right"
                                    class="h-4 w-4 text-slate-300 group-hover:text-[var(--brand-600)] transition-colors"></i>
                            </a>
                        @endif

                        @if (has_permission('settings.reset'))
                            <div class="flex items-center gap-3 border-b border-red-100 pb-4 mb-6">
                                <div
                                    class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center border border-red-100">
                                    <i data-lucide="shield-alert" class="w-4 h-4 text-red-600"></i>
                                </div>
                                <h2 class="text-base font-bold text-red-600">Danger Zone</h2>
                            </div>

                            <div
                                class="bg-red-50/50 border border-red-100 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-bold text-red-800">Factory Reset Settings</h4>
                                    <p class="text-xs text-red-600/80 mt-1">This will reset all preferences back to
                                        defaults. Company data is safe.</p>
                                </div>
                                <button type="button" @click="confirmReset()"
                                    class="flex-shrink-0 whitespace-nowrap px-5 py-2.5 bg-white border border-red-200 text-red-600 text-sm font-bold rounded-xl shadow-sm hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors">
                                    Reset Now
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- ════════════════════════════════
                        TAB 7 — APPOINTMENTS
                    ════════════════════════════════ --}}
                    @if (has_module('appointments'))
                        <div x-show="activeTab === 'appointments'" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0">
                            <p class="section-title">
                                <i data-lucide="calendar-check" class="h-4 w-4"></i>
                                Appointment Booking
                            </p>

                            <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="field-label">Enable Appointment Booking</label>
                                    <div class="toggle-wrap">
                                        <label class="relative inline-flex flex-shrink-0 cursor-pointer items-center">
                                            <input type="hidden" name="appointment_enabled" value="0" />
                                            <input type="checkbox" name="appointment_enabled" value="1"
                                                {{ get_setting('appointment_enabled', 0) ? 'checked' : '' }}
                                                class="peer sr-only" />
                                            <div
                                                class="peer peer-checked:bg-brand-600 h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full">
                                            </div>
                                        </label>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-700">Show Booking Section on
                                                Storefront</p>
                                            <p class="text-[11px] text-gray-400">Displays a "Book Appointment" section on
                                                your public storefront homepage.</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label">Section Heading</label>
                                    <textarea name="appointment_heading" rows="2"
                                        placeholder="e.g. Solutions for Garden Maintenance,&#10;Book Your Slot" class="field-input resize-y">{{ get_setting('appointment_heading', 'Book Your Appointment') }}</textarea>
                                    <p class="mt-1.5 text-[11px] text-gray-400">Main title shown on the storefront booking
                                        section. Press Enter to wrap text into the next line.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="field-label">Section Subheading</label>
                                    <textarea name="appointment_subheading" rows="2"
                                        placeholder="e.g. Schedule a visit with our experts at your convenience." class="field-input resize-y">{{ get_setting('appointment_subheading', '') }}</textarea>
                                    <p class="mt-1.5 text-[11px] text-gray-400">Secondary description printed on the
                                        storefront banner layer.</p>
                                </div>
                            </div>

                            <div class="flex gap-2 rounded-xl border border-blue-100 bg-blue-50 p-3">
                                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-blue-500"></i>
                                <p class="text-xs text-blue-700">Manage <strong>Services</strong> and <strong>Time
                                        Slots</strong> from
                                    <a href="{{ route('admin.appointments.services.index') }}"
                                        class="font-semibold underline">Appointments → Services</a>
                                    and
                                    <a href="{{ route('admin.appointments.slots.index') }}"
                                        class="font-semibold underline">Appointments → Slots</a>.
                                </p>
                            </div>
                        </div>
                    @endif
                    {{-- /has_module('appointments') --}}
                </div>
            </form>


            {{-- ════════════════════════════════
                 TAB: NOTIFICATIONS (own form / save, outside main form)

                 Self-contained: it carries its own x-data so it no longer
                 depends on the parent's msConfig, which existed only for the
                 old role pickers.
            ════════════════════════════════ --}}
            <div x-show="activeTab === 'notifications'" x-cloak x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                x-data="notificationSettings(@js($notificationConfig), @js($users))" class="p-6 sm:p-8">
                <div class="mb-8 flex flex-col gap-1.5 border-b border-gray-100 pb-5">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-gray-800">
                        <i data-lucide="bell-ring" class="h-5 w-5 text-[var(--brand-600)]"></i>
                        Notification Recipients
                    </h2>
                    <p class="text-[13px] leading-relaxed text-gray-500">
                        Decide who hears about each event, and how. Untick both boxes to stop notifying someone without
                        removing them.
                    </p>
                </div>

                <template x-if="events.length === 0">
                    <div class="rounded-2xl border border-dashed border-gray-200 py-12 text-center">
                        <i data-lucide="bell-off" class="mx-auto mb-3 h-8 w-8 text-gray-300"></i>
                        <p class="text-sm font-bold text-gray-500">No configurable events</p>
                        <p class="mt-1 text-[12px] text-gray-400">Events appear here as you enable more modules.</p>
                    </div>
                </template>

                {{-- ── Event cards ── --}}
                <template x-for="(event, eIndex) in events" :key="event.event">
                    <div :class="event.pickerOpen ? 'relative z-20' : 'relative z-0'"
                        class="mb-6 rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/5 transition-all hover:shadow-md">

                        {{-- Card header --}}
                        <div class="flex items-center gap-4 border-b border-gray-100 bg-slate-50/50 px-5 py-4 sm:px-6">
                            <div
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                                <i :data-lucide="event.icon" class="h-4 w-4 text-gray-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-gray-800" x-text="event.label"></h4>
                                <p class="mt-0.5 text-[12px] leading-relaxed text-gray-500" x-text="event.description">
                                </p>
                            </div>
                            <span
                                class="hidden flex-shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider sm:inline-block"
                                :class="activeCount(event) > 0 ?
                                    'bg-emerald-50 text-emerald-600' :
                                    'bg-gray-100 text-gray-400'"
                                x-text="activeCount(event) > 0 ? activeCount(event) + ' notified' : 'Off'"></span>
                        </div>

                        {{-- Column headings — the two channels are the whole point of this screen --}}
                        <div class="hidden items-center border-b border-gray-100 px-5 py-2 sm:flex">
                            <span
                                class="flex-1 text-[10px] font-black uppercase tracking-wider text-gray-400">Recipient</span>
                            <span
                                class="w-20 text-center text-[10px] font-black uppercase tracking-wider text-gray-400">In-app</span>
                            <span
                                class="w-20 text-center text-[10px] font-black uppercase tracking-wider text-gray-400">Email</span>
                            <span class="w-8"></span>
                        </div>

                        {{-- Recipient rows --}}
                        <div class="divide-y divide-gray-50">
                            <template x-for="(row, rIndex) in event.recipients" :key="row.type + ':' + row.value">
                                <div
                                    class="flex flex-col gap-3 px-5 py-3 transition-colors hover:bg-gray-50/50 sm:flex-row sm:items-center sm:gap-0">

                                    {{-- Who --}}
                                    <div class="flex min-w-0 flex-1 items-center gap-3">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-[11px] font-black"
                                            :class="row.type === 'permission' ?
                                                'bg-indigo-50 text-indigo-500' :
                                                'bg-emerald-50 text-emerald-600'">
                                            <template x-if="row.type === 'permission'">
                                                <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                            </template>
                                            <template x-if="row.type === 'user'">
                                                <span x-text="initials(row.value)"></span>
                                            </template>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-[13px] font-bold text-gray-800"
                                                x-text="rowTitle(event, row)"></p>
                                            <p class="truncate text-[11px] text-gray-400"
                                                x-text="rowSubtitle(event, row)"></p>
                                        </div>
                                    </div>

                                    {{-- Channels --}}
                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-6 pl-11 sm:mt-0 sm:flex-nowrap sm:gap-0 sm:pl-0">
                                        {{-- In-App Toggle --}}
                                        <label class="flex w-20 cursor-pointer items-center justify-center gap-2"
                                            title="In-App Notification">
                                            <div class="relative inline-flex cursor-pointer items-center">
                                                <input type="checkbox" class="peer sr-only"
                                                    :checked="row.channels.includes('database')"
                                                    @change="toggle(row, 'database')">
                                                <div
                                                    class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[var(--brand-600)] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--brand-500)]/30">
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-bold text-gray-500 sm:hidden">In-app</span>
                                        </label>

                                        {{-- Email Toggle --}}
                                        <label class="flex w-20 items-center justify-center gap-2"
                                            :class="row.type === 'permission' ? 'cursor-not-allowed opacity-50' :
                                                'cursor-pointer'"
                                            :title="row.type === 'permission' ?
                                                'Email is only available for named recipients' : 'Email Notification'">
                                            <div class="relative inline-flex items-center"
                                                :class="row.type === 'permission' ? 'pointer-events-none' : ''">
                                                <input type="checkbox" class="peer sr-only"
                                                    :disabled="row.type === 'permission'"
                                                    :checked="row.channels.includes('mail')"
                                                    @change="toggle(row, 'mail')">
                                                <div
                                                    class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-[var(--brand-600)] peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[var(--brand-500)]/30">
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-bold text-gray-500 sm:hidden">Email</span>
                                        </label>

                                        {{-- The permission row cannot be removed: it is the safety net
                                             that keeps someone in charge of this event. --}}
                                        <div class="flex w-8 justify-center">
                                            <template x-if="row.type === 'user'">
                                                <button type="button" @click="removeRecipient(eIndex, rIndex)"
                                                    class="rounded-lg p-1.5 text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500"
                                                    title="Remove recipient">
                                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add recipient --}}
                        <div class="rounded-b-2xl border-t border-gray-50 bg-gray-50/30 px-5 py-3 sm:px-6"
                            @click.outside="event.pickerOpen = false">
                            <div class="relative">
                                <button type="button" @click="event.pickerOpen = !event.pickerOpen; event.search = ''"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-[12px] font-bold text-gray-700 shadow-sm transition-all hover:border-[var(--brand-500)] hover:text-[var(--brand-600)] hover:shadow">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    Add Recipient
                                </button>

                                <div x-show="event.pickerOpen" x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-2"
                                    class="absolute left-0 top-full z-50 mt-2 w-[calc(100vw-4rem)] sm:w-80 max-w-xs overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl ring-1 ring-black ring-opacity-5">
                                    <div class="border-b border-gray-100 bg-gray-50/50 p-3">
                                        <div class="relative">
                                            <i data-lucide="search"
                                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                            <input type="text" x-model="event.search" x-init="$watch('event.pickerOpen', val => { if (val) setTimeout(() => $el.focus(), 100) })"
                                                placeholder="Search users by name or email..."
                                                class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-[13px] text-gray-800 focus:border-[var(--brand-500)] focus:outline-none focus:ring-2 focus:ring-[var(--brand-500)]/20">
                                        </div>
                                    </div>
                                    <div class="max-h-52 overflow-y-auto">
                                        <template x-for="user in availableUsers(event)" :key="user.id">
                                            <button type="button" @click="addRecipient(eIndex, user.id)"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left transition-colors hover:bg-gray-50">
                                                <span
                                                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-emerald-50 text-[10px] font-black text-emerald-600"
                                                    x-text="initials(user.id)"></span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-[12px] font-bold text-gray-800"
                                                        x-text="user.name"></span>
                                                    <span class="block truncate text-[11px] text-gray-400"
                                                        x-text="user.email"></span>
                                                </span>
                                            </button>
                                        </template>
                                        <template x-if="availableUsers(event).length === 0">
                                            <p class="px-3 py-4 text-center text-[12px] text-gray-400">Everyone is already
                                                listed</p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end">
                    @if (has_permission('settings.update_notifications'))
                        <button type="button" @click="save()" :disabled="saving"
                            class="save-btn w-full justify-center sm:w-auto">
                            <i data-lucide="loader-2" x-show="saving" x-cloak class="h-4 w-4 animate-spin"></i>
                            <i data-lucide="bell" x-show="!saving" class="h-4 w-4"></i>
                            <span x-text="saving ? 'Saving...' : 'Save Notification Settings'"></span>
                        </button>
                    @endif
                </div>
            </div>
            {{-- /notifications tab --}}


            {{-- Bottom Save Bar — hidden on the Notifications tab (which has its own save button) --}}
            <div x-show="activeTab !== 'notifications'"
                class="flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-4 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-6 py-4 md:px-8">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                    <i data-lucide="check-circle-2" class="h-4 w-4 text-slate-300"></i>
                    Last saved: {{ get_setting('_last_saved') ?? 'Never' }}
                </p>
                @if (has_permission('settings.update'))
                    <button type="button" @click="submitForm()" :disabled="isSaving"
                        class="flex w-full sm:w-auto justify-center items-center gap-2 bg-[var(--brand-600)] text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-[0_2px_8px_-2px_rgba(16,185,129,0.5)] hover:bg-[var(--brand-700)] hover:-translate-y-0.5 transition-all active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed">
                        <i data-lucide="loader-2" x-show="isSaving" x-cloak class="h-4 w-4 animate-spin"></i>
                        <i data-lucide="save" x-show="!isSaving" class="h-4 w-4"></i>
                        <span x-text="isSaving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function settingsApp() {
            return {
                activeTab: "company",
                isSaving: false,
                isClearingCache: false,

                tabs: @json ($tabs),

                theme: {
                    primary: "{{ get_setting('primary_color', '#008a62') }}",
                    hover: "{{ get_setting('primary_hover_color', '#007050') }}",
                },

                previews: {
                    logo: null,
                    icon: null,
                    favicon: null,
                },

                previewFile(event, key) {
                    const file = event.target.files[0];
                    if (file) this.previews[key] = URL.createObjectURL(file);
                },

                livePreviewColors() {
                    const root = document.documentElement;
                    root.style.setProperty("--brand-500", this.theme.primary);
                    root.style.setProperty("--brand-600", this.theme.hover);
                    root.style.setProperty("--brand-700", this.theme.hover);
                },

                async submitForm() {
                    this.isSaving = true;
                    const form = document.getElementById("settings-form");
                    const data = new FormData(form);

                    // Append theme values explicitly (Alpine models don't auto-submit)
                    data.set("primary_color", this.theme.primary);
                    data.set("primary_hover_color", this.theme.hover);

                    try {
                        const res = await fetch("{{ route('admin.settings.update') }}", {
                            method: "POST",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "application/json",
                            },
                            body: data,
                        });
                        const result = await res.json();

                        if (result.success) {
                            BizAlert.toast(result.message || "Settings saved!", "success");
                            // Reload so layout picks up new colors/logo from DB
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            BizAlert.toast(result.message || "Error saving settings.", "error");
                        }
                    } catch (err) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    } finally {
                        this.isSaving = false;
                    }
                },

                async clearCache() {
                    this.isClearingCache = true;
                    try {
                        const res = await fetch("{{ route('admin.settings.clear-cache') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                Accept: "application/json",
                            },
                        });
                        const result = await res.json();
                        BizAlert.toast(result.message || "Cache cleared!", result.success ? "success" : "error");
                    } catch {
                        BizAlert.toast("Failed to clear cache.", "error");
                    } finally {
                        this.isClearingCache = false;
                    }
                },

                async confirmReset() {
                    const result = await BizAlert.confirm(
                        "Reset All Settings?",
                        "This will restore factory defaults. Your company data, invoices and transactions are NOT affected.",
                        "Yes, Reset Everything",
                    );

                    if (!result.isConfirmed) return;

                    BizAlert.loading("Resetting...");

                    try {
                        const res = await fetch("{{ route('admin.settings.reset') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                        });
                        const data = await res.json();

                        if (data.success) {
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            BizAlert.toast(data.message, "error");
                        }
                    } catch {
                        BizAlert.toast("Reset failed. Please try again.", "error");
                    }
                },

                init() {
                    // Re-render lucide icons when tab changes (dynamic :data-lucide in tabs)
                    this.$watch("activeTab", () => {
                        this.$nextTick(() => {
                            if (typeof lucide !== "undefined") lucide.createIcons();
                        });
                    });
                },
            };
        }
    </script>

    <script>
        /**
         * Settings → Notifications.
         *
         * Rows are edited in place and posted as the complete desired state; the
         * server replaces each event's rows wholesale rather than diffing.
         */
        window.notificationSettings = function(events, users) {
            return {
                users: users,
                saving: false,

                init() {
                    // Cards render behind an x-show, so their :data-lucide
                    // bindings need a pass once they are actually in the DOM.
                    this.$nextTick(() => window.lucide && lucide.createIcons());
                },

                // pickerOpen/search are view state, added here so the markup can
                // bind to them without a second nested component per card.
                events: events.map(e => ({
                    ...e,
                    pickerOpen: false,
                    search: ''
                })),

                userById(id) {
                    return this.users.find(u => String(u.id) === String(id));
                },

                initials(userId) {
                    const name = this.userById(userId)?.name || '?';
                    return name.trim().charAt(0).toUpperCase();
                },

                rowTitle(event, row) {
                    return row.type === 'permission' ?
                        event.defaultPermissionLabel :
                        (this.userById(row.value)?.name || 'Unknown user');
                },

                rowSubtitle(event, row) {
                    return row.type === 'permission' ?
                        'Updates automatically as staff change · in-app only' :
                        (this.userById(row.value)?.email || '');
                },

                /** People not already listed for this event. */
                availableUsers(event) {
                    const taken = event.recipients
                        .filter(r => r.type === 'user')
                        .map(r => String(r.value));

                    const term = (event.search || '').toLowerCase();

                    return this.users.filter(u =>
                        !taken.includes(String(u.id)) &&
                        (term === '' || (u.name || '').toLowerCase().includes(term))
                    );
                },

                activeCount(event) {
                    return event.recipients.filter(r => r.channels.length > 0).length;
                },

                toggle(row, channel) {
                    // Mirrors NotificationPreference::allowedChannelsFor().
                    if (row.type === 'permission' && channel === 'mail') return;

                    const at = row.channels.indexOf(channel);
                    if (at === -1) {
                        row.channels.push(channel);
                    } else {
                        row.channels.splice(at, 1);
                    }
                },

                addRecipient(eIndex, userId) {
                    const event = this.events[eIndex];

                    // New people start on both channels — the common intent, and
                    // one untick away from anything else.
                    event.recipients.push({
                        type: 'user',
                        value: String(userId),
                        channels: ['database', 'mail'],
                    });

                    event.pickerOpen = false;
                    event.search = '';
                    this.$nextTick(() => window.lucide && lucide.createIcons());
                },

                removeRecipient(eIndex, rIndex) {
                    this.events[eIndex].recipients.splice(rIndex, 1);
                },

                async save() {
                    this.saving = true;

                    const payload = {};
                    this.events.forEach(e => {
                        payload[e.event] = e.recipients.map(r => ({
                            type: r.type,
                            value: r.value,
                            channels: r.channels,
                        }));
                    });

                    try {
                        const res = await fetch("{{ route('admin.settings.notifications.update') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                events: payload
                            }),
                        });

                        const data = await res.json();

                        BizAlert.toast(data.message || 'Saved!', data.success ? 'success' : 'error');
                    } catch (e) {
                        BizAlert.toast('Network error. Please try again.', 'error');
                    }

                    this.saving = false;
                },
            };
        };
    </script>
@endpush
