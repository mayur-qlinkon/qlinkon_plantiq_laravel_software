@extends ('layouts.platform')

@section('title', 'Edit ' . $tenant->name)
@section('header', 'Edit Company')

@section('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .fi {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            width: 100%;
            outline: none;
            transition: border-color 0.15s;
            background: #fff;
        }

        .fi:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.08);
        }

        .fi.err {
            border-color: #f87171;
        }

        .fi:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .flabel {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .section-card {
            background: #fff;
            border: 1px solid #f3f4f6;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .section-head {
            padding: 14px 20px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-body {
            padding: 20px;
        }
    </style>
@endsection

@section('content')
    @php
        $sub = $tenant->subscription;
        $plan = $sub?->plan;
    @endphp

    <div class="w-full pb-10" x-data="editForm(
        '{{ route('platform.tenants.slug-check.edit', $tenant) }}',
        @js($modules->pluck('id')->all()),
        @js($moduleDependencies),
        @js($moduleNames)
    )">
        {{-- Breadcrumb --}}
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('platform.tenants.index') }}" class="hover:text-brand-600 font-medium">Tenants</a>
            <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
            <a href="{{ route('platform.tenants.show', $tenant) }}"
                class="hover:text-brand-600 font-medium">{{ $tenant->name }}</a>
            <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="font-semibold text-gray-800">Edit</span>
        </div>

        {{-- Flash errors --}}
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <div class="mb-2 flex items-center gap-2 font-bold">
                    <i class="fas fa-triangle-exclamation"></i> Please fix the following errors:
                </div>
                <ul class="list-inside list-disc space-y-0.5 pl-2 text-xs font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                <i class="fas fa-circle-exclamation shrink-0"></i> {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}" class="space-y-5">
            @csrf
            @method ('PUT')

            {{-- This form always renders the module section, and says so here.
                 An empty checkbox group sends nothing, so "every module off"
                 and "modules were not part of this submission" arrive at the
                 server identically. This marker is what tells them apart. --}}
            <input type="hidden" name="modules_submitted" value="1">

            {{-- ═══════════════════════════════════════════════
             SECTION 1 — Company Details
        ═══════════════════════════════════════════════ --}}
            <div class="section-card">
                <div class="section-head bg-gray-50/60">
                    <div class="bg-brand-600 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg">
                        <i class="fas fa-building text-xs text-white"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Company Details</h3>
                </div>
                <div class="section-body grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- Company Name --}}
                    <div>
                        <label class="flabel">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $tenant->name) }}"
                            class="fi {{ $errors->has('company_name') ? 'err' : '' }}" placeholder="Acme Corporation"
                            required />
                        @error('company_name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Company Email --}}
                    <div>
                        <label class="flabel">Company Email <span class="text-red-500">*</span></label>
                        <input type="email" name="company_email" value="{{ old('company_email', $tenant->email) }}"
                            class="fi {{ $errors->has('company_email') ? 'err' : '' }}" placeholder="contact@acme.com"
                            required />
                        @error('company_email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="md:col-span-2">
                        <label class="flabel">Company Slug <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="slug" :value="slug"
                                @input="
                                    slug = $event.target.value;
                                    checkSlug();
                                "
                                class="fi font-mono pr-32 {{ $errors->has('slug') ? 'err' : '' }}"
                                placeholder="acme-corporation" />
                            <div class="absolute top-1/2 right-3 -translate-y-1/2 text-xs font-semibold">
                                <template x-if="slugStatus === 'checking'">
                                    <span class="flex items-center gap-1 text-gray-400">
                                        <i class="fas fa-circle-notch fa-spin text-[10px]"></i> Checking…
                                    </span>
                                </template>
                                <template x-if="slugStatus === 'available'">
                                    <span class="flex items-center gap-1 text-green-600">
                                        <i class="fas fa-check text-[10px]"></i> Available
                                    </span>
                                </template>
                                <template x-if="slugStatus === 'taken'">
                                    <span class="flex items-center gap-1 text-red-500">
                                        <i class="fas fa-xmark text-[10px]"></i> Taken
                                    </span>
                                </template>
                                <template x-if="slugStatus === 'current'">
                                    <span class="text-brand-600 flex items-center gap-1">
                                        <i class="fas fa-circle-check text-[10px]"></i> Current
                                    </span>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-gray-400">Lowercase letters, numbers and hyphens only.</p>
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Subdomain --}}
                    <div>
                        <label class="flabel">Subdomain</label>
                        <div class="flex items-stretch">
                            <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}"
                                class="fi rounded-r-none border-r-0 font-mono {{ $errors->has('subdomain') ? 'err' : '' }}"
                                placeholder="acme" />
                            <span
                                class="flex items-center rounded-r-[10px] border border-gray-200 bg-gray-50 px-3 font-mono text-xs whitespace-nowrap text-gray-400">
                                .{{ config('app.central_domain') ?: parse_url(config('app.url'), PHP_URL_HOST) }}
                            </span>
                        </div>
                        @error('subdomain')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Custom Domain --}}
                    <div>
                        <label class="flabel">Custom Domain</label>
                        <input type="text" name="domain" value="{{ old('domain', $tenant->domain) }}"
                            class="fi font-mono {{ $errors->has('domain') ? 'err' : '' }}" placeholder="tenantshop.com" />
                        <p class="mt-1.5 text-[11px] text-gray-400">Optional — only if this company uses its own domain.</p>
                        @error('domain')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="flabel">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="fi"
                            placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                        @error('phone')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- GST --}}
                    <div>
                        <label class="flabel">GST Number</label>
                        <input type="text" name="gst_number" value="{{ old('gst_number', $tenant->gst_number) }}"
                            class="fi font-mono" placeholder="22AAAAA0000A1Z5" />
                        @error('gst_number')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- State --}}
                    <div>
                        <label class="flabel">State</label>
                        <select name="state_id" class="fi {{ $errors->has('state_id') ? 'err' : '' }}">
                            <option value="">Select state…</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ old('state_id', $tenant->state_id) == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('state_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="flabel">Company Status</label>
                        <div class="mt-1 flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="is_active" value="1"
                                    {{ old('is_active', $tenant->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                    class="accent-brand-600" />
                                <span class="text-sm font-semibold text-gray-700">Active</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="is_active" value="0"
                                    {{ old('is_active', $tenant->is_active ? '1' : '0') === '0' ? 'checked' : '' }}
                                    class="accent-red-500" />
                                <span class="text-sm font-semibold text-gray-700">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════
             SECTION 2 — Primary Owner (read-only display)
        ═══════════════════════════════════════════════ --}}
            @php
                $owner = $tenant->users->first(fn($u) => $u->isCompanyAdmin());
            @endphp
            @if ($owner)
                <div class="section-card border-orange-100">
                    <div class="section-head border-orange-100 bg-orange-50/40">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-orange-500">
                            <i class="fas fa-user text-xs text-white"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">Primary Owner Account</h3>
                        <span
                            class="ml-auto rounded-lg border border-orange-100 bg-orange-50 px-2.5 py-1 text-[11px] font-medium text-orange-400">
                            <i class="fas fa-lock mr-1 text-[9px]"></i> Read-only — manage from user panel
                        </span>
                    </div>
                    <div class="section-body">
                        <div class="flex items-center gap-4 rounded-xl border border-orange-100/60 bg-orange-50/30 p-4">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-base font-bold text-orange-600">
                                {{ strtoupper(substr($owner->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-gray-800">{{ $owner->name }}</p>
                                <p class="truncate text-xs text-gray-400">{{ $owner->email }}</p>
                            </div>
                            <span
                                class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0
                        {{ $owner->status === 'active' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-gray-100 text-gray-500' }}">
                                {{ ucfirst($owner->status ?? 'active') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════════════
             SECTION 3 — Subscription Plan
        ═══════════════════════════════════════════════ --}}
            <div class="section-card border-brand-100/60">
                <div class="section-head bg-brand-50/30 border-brand-100/60">
                    <div class="bg-brand-600 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg">
                        <i class="fas fa-layer-group text-xs text-white"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">
                        {{ $plan ? 'Edit Subscription Plan' : 'Create Subscription Plan' }}
                    </h3>
                    @if (!$plan)
                        <span class="ml-auto text-[11px] font-medium text-gray-400">Optional — leave blank to skip</span>
                    @endif
                </div>
                <div class="section-body space-y-6">
                    {{-- 1. Identity & Pricing --}}
                    <div>
                        <p
                            class="mb-4 border-b border-gray-100 pb-2 text-[10px] font-black tracking-widest text-gray-400 uppercase">
                            1. Identity &amp; Pricing</p>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="flabel">Plan Name</label>
                                <input type="text" name="plan_name" value="{{ old('plan_name', $plan?->name) }}"
                                    class="fi {{ $errors->has('plan_name') ? 'err' : '' }}"
                                    placeholder="e.g. Premium Plan" />
                                @error('plan_name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="flabel">Short Description</label>
                                <input type="text" name="plan_description"
                                    value="{{ old('plan_description', $plan?->description) }}" class="fi"
                                    placeholder="e.g. Best for growing businesses" />
                            </div>

                            <div>
                                <label class="flabel">Price (₹) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" name="plan_price"
                                    value="{{ old('plan_price', $plan?->price ?? 0) }}" min="0" class="fi" />
                            </div>

                            <div>
                                <label class="flabel">Billing Cycle <span class="text-red-500">*</span></label>
                                <select name="billing_cycle" class="fi">
                                    <option value="monthly"
                                        {{ old('billing_cycle', $plan?->billing_cycle) === 'monthly' ? 'selected' : '' }}>
                                        Monthly
                                    </option>
                                    <option value="yearly"
                                        {{ old('billing_cycle', $plan?->billing_cycle) === 'yearly' ? 'selected' : '' }}>
                                        Yearly
                                    </option>
                                    <option value="lifetime"
                                        {{ old('billing_cycle', $plan?->billing_cycle) === 'lifetime' ? 'selected' : '' }}>
                                        Lifetime (One-time)
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="flabel">Free Trial Days</label>
                                <input type="number" name="trial_days"
                                    value="{{ old('trial_days', $plan?->trial_days ?? 0) }}" min="0"
                                    class="fi" />
                            </div>

                            <div class="mt-4 flex items-center">
                                <label class="flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" name="plan_is_active" value="1"
                                        {{ old('plan_is_active', $plan ? ($plan->is_active ? '1' : '') : '1') ? 'checked' : '' }}
                                        class="text-brand-600 focus:ring-brand-500 h-4 w-4 rounded border-gray-300" />
                                    <span class="text-sm font-bold text-gray-700">Plan is Active</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Resource Limits --}}
                    <div>
                        <p
                            class="mb-4 border-b border-gray-100 pb-2 text-[10px] font-black tracking-widest text-gray-400 uppercase">
                            2. Resource Limits</p>
                        <div class="grid grid-cols-2 gap-5 md:grid-cols-3">
                            <div>
                                <label class="flabel">User Limit</label>
                                <input type="number" name="user_limit"
                                    value="{{ old('user_limit', $plan?->user_limit ?? 1) }}" min="1"
                                    class="fi" />
                            </div>
                            <div>
                                <label class="flabel">Store Limit</label>
                                <input type="number" name="store_limit"
                                    value="{{ old('store_limit', $plan?->store_limit ?? 1) }}" min="1"
                                    class="fi" />
                            </div>
                            <div>
                                <label class="flabel">Product Limit</label>
                                <input type="number" name="product_limit"
                                    value="{{ old('product_limit', $plan?->product_limit ?? 50) }}" min="1"
                                    class="fi" />
                            </div>
                            <div>
                                <label class="flabel">Employee Limit</label>
                                <input type="number" name="employee_limit"
                                    value="{{ old('employee_limit', $plan?->employee_limit ?? 50) }}" min="1"
                                    class="fi" />
                            </div>
                            <div>
                                <label class="flabel">Daily OCR Scans</label>
                                <input type="number" name="ocr_scan_limit"
                                    value="{{ old('ocr_scan_limit', $plan?->ocr_scan_limit ?? 50) }}" min="0"
                                    class="fi" />
                            </div>
                            <div>
                                <label class="flabel">AI Chat Daily Limit</label>
                                <input type="number" name="ai_chat_daily_limit"
                                    value="{{ old('ai_chat_daily_limit', $plan?->ai_chat_daily_limit ?? 50) }}"
                                    min="0" class="fi" />
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label class="flabel">AI Token Daily Limit</label>
                                <input type="number" name="ai_token_daily_limit"
                                    value="{{ old('ai_token_daily_limit', $plan?->ai_token_daily_limit ?? 5000) }}"
                                    min="-1" class="fi" />
                                <p class="mt-1.5 text-[11px] text-gray-400">0 = no AI, -1 = unlimited.</p>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Assigned Modules --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-2">
                            <p class="text-[10px] font-black tracking-widest text-gray-400 uppercase">3. Assigned Modules
                            </p>
                            <button type="button" @click="toggleAllModules()" x-show="allModuleIds.length > 0"
                                class="rounded-lg border px-2.5 py-1 text-[10px] font-bold transition-colors"
                                :class="areAllSelected() ?
                                    'bg-gray-100 text-gray-600 border-gray-200' :
                                    'bg-brand-50 text-brand-600 border-brand-100 hover:bg-brand-100'">
                                <span x-text="areAllSelected() ? 'Deselect All' : 'Select All'"></span>
                            </button>
                        </div>

                        <div x-show="dependencyNotice" x-cloak
                            class="mb-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-semibold text-amber-800">
                            <i class="fas fa-circle-info mt-0.5 shrink-0"></i>
                            <span x-text="dependencyNotice"></span>
                        </div>

                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                            @forelse ($modules as $module)
                                <div class="flex items-center gap-2 rounded-xl border p-3 transition-all"
                                    :class="isSelected({{ $module->id }}) ? 'border-brand-400 bg-brand-50/30' :
                                        'border-gray-200'">
                                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 hover:bg-gray-50">
                                        <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                                            x-model="selectedModules" @change="onModuleToggle({{ $module->id }})"
                                            class="text-brand-600 focus:ring-brand-500 h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300" />
                                        <span
                                            class="truncate text-sm font-semibold text-gray-700">{{ $module->name }}</span>
                                        @if (in_array($module->id, $licensedModuleIds))
                                            <span x-show="!isSelected({{ $module->id }})"
                                                class="ml-auto shrink-0 rounded bg-red-50 px-1.5 py-0.5 text-[9px] font-bold text-red-600"
                                                title="Unchecking removes access for users already using this module">IN
                                                USE</span>
                                        @endif
                                    </label>
                                    <input type="number" min="1"
                                        x-model.number="moduleSeats[{{ $module->id }}]"
                                        :name="'module_seats[{{ $module->id }}]'"
                                        :disabled="!isSelected({{ $module->id }})" placeholder="Seats"
                                        title="Number of users allowed on this module. Required — saving with this blank removes the license."
                                        class="focus:ring-brand-500/20 focus:border-brand-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none w-24 shrink-0 [appearance:textfield] rounded-lg border border-gray-300 px-2 py-1 text-[11px] outline-none focus:ring-2 disabled:bg-gray-50 disabled:text-gray-300" />
                                </div>
                            @empty
                                <p class="col-span-full text-sm text-gray-400">No active modules found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════
             SECTION 4 — Subscription Activation
        ═══════════════════════════════════════════════ --}}
            <div class="section-card border-blue-100/60">
                <div class="section-head border-blue-100/60 bg-blue-50/30">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600">
                        <i class="fas fa-calendar-check text-xs text-white"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800">Subscription Activation</h3>
                    <span class="ml-auto text-[11px] font-medium text-gray-400">Applies only if a plan is set above</span>
                </div>
                <div class="section-body grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="flabel">Starts At</label>
                        <input type="date" name="starts_at"
                            value="{{ old('starts_at', $sub?->starts_at ? \Carbon\Carbon::parse($sub->starts_at)->format('Y-m-d') : date('Y-m-d')) }}"
                            class="fi" />
                        @error('starts_at')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flabel">Expires At</label>
                        <input type="date" name="expires_at"
                            value="{{ old('expires_at', $sub?->expires_at ? \Carbon\Carbon::parse($sub->expires_at)->format('Y-m-d') : '') }}"
                            class="fi" />
                        <p class="mt-1.5 text-[11px] text-gray-400">Leave blank for a lifetime / no-expiry subscription.
                        </p>
                        @error('expires_at')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex w-max cursor-pointer items-center gap-3">
                            <div class="relative flex items-center">
                                <input type="checkbox" name="sub_is_active" value="1"
                                    {{ old('sub_is_active', $sub?->is_active ?? true) ? 'checked' : '' }}
                                    id="sub_is_active_toggle" class="peer sr-only" />
                                <div
                                    class="peer peer-checked:bg-brand-600 peer-focus:ring-brand-300 h-5 w-10 rounded-full bg-gray-200 peer-focus:ring-2 after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-5 peer-checked:after:border-white">
                                </div>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Subscription is Active</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════
             Footer actions
        ═══════════════════════════════════════════════ --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('platform.tenants.show', $tenant) }}"
                    class="flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition-colors hover:text-gray-800">
                    <i class="fas fa-arrow-left text-xs"></i> Cancel
                </a>
                <button type="submit"
                    class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-xl px-7 py-2.5 text-sm font-bold text-white shadow-sm transition-colors">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function editForm(slugCheckUrl, allModuleIds, moduleDependencies, moduleNames) {
            return {
                // ── Slug checker state ──
                slug: "{{ old('slug', $tenant->slug) }}",
                originalSlug: "{{ $tenant->slug }}",
                slugStatus: "current", // starts as "current" since it's the existing slug
                _slugTimer: null,

                allModuleIds: allModuleIds,
                // { moduleId: [requiredModuleId, ...] } — mirrors the server's
                // guardModuleDependencies() so both refuse the same combinations.
                moduleDependencies: moduleDependencies,
                moduleNames: moduleNames,
                dependencyNotice: "",
                _noticeTimer: null,
                selectedModules: @json (old('modules', $plan?->modules?->pluck('id')->all() ?? [])).map(Number),
                moduleSeats: Object.assign({}, @json (old('module_seats', $moduleSeats))),

                /**
                 * A licence saved with a NULL seat_limit arrives here as a
                 * checked module with an empty seat box. Saving in that state
                 * deletes the licence, because blank seats mean "not licensed"
                 * server-side — so every already-selected module is given a
                 * seat before the form is ever touched.
                 */
                init() {
                    this.selectedModules.forEach((id) => this.ensureSeat(id));
                },

                // ── Slug methods ──
                checkSlug() {
                    if (!this.slug) {
                        this.slugStatus = "";
                        return;
                    }

                    // If unchanged from original, mark as "current" — no API call needed
                    if (this.slug === this.originalSlug) {
                        this.slugStatus = "current";
                        return;
                    }

                    this.slugStatus = "checking";
                    clearTimeout(this._slugTimer);
                    this._slugTimer = setTimeout(async () => {
                        try {
                            const res = await fetch(slugCheckUrl + "?slug=" + encodeURIComponent(this.slug), {
                                headers: {
                                    Accept: "application/json",
                                    "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                                        .content,
                                },
                            });
                            if (!res.ok) {
                                this.slugStatus = "";
                                return;
                            }
                            const data = await res.json();
                            this.slugStatus = data.available ? "available" : "taken";
                            if (data.available) this.slug = data.slug;
                        } catch (e) {
                            this.slugStatus = "";
                        }
                    }, 450);
                },

                // ── Module methods ──
                /**
                 * x-model on a checkbox collects el.value, which the DOM always
                 * hands over as a string — while every ID coming from PHP is a
                 * number. Comparing the two with includes() silently fails, so
                 * membership is tested through here and nowhere else.
                 */
                isSelected(moduleId) {
                    return this.selectedModules.some((id) => Number(id) === Number(moduleId));
                },

                /** Collapse the array back to numbers after any x-model write. */
                normaliseSelection() {
                    this.selectedModules = [...new Set(this.selectedModules.map(Number))];
                },

                areAllSelected() {
                    return this.allModuleIds.length > 0 && this.selectedModules.length === this.allModuleIds.length;
                },

                toggleAllModules() {
                    if (this.areAllSelected()) {
                        this.selectedModules = [];
                        this.moduleSeats = {};
                        this.dependencyNotice = "";
                        return;
                    }

                    this.selectedModules = [...this.allModuleIds];
                    this.allModuleIds.forEach((id) => this.ensureSeat(id));
                },

                /**
                 * Runs on every checkbox change — the single entry point for
                 * both seat defaulting and dependency enforcement.
                 */
                onModuleToggle(moduleId) {
                    // x-model has just written a string into the array; fix that
                    // before any comparison depends on it.
                    this.normaliseSelection();

                    if (this.isSelected(moduleId)) {
                        this.ensureSeat(moduleId);
                        this.pullInDependencies(moduleId);
                    } else {
                        this.releaseModule(moduleId);
                    }
                },

                /** A licensed module always carries at least one seat. */
                ensureSeat(moduleId) {
                    const current = this.moduleSeats[moduleId];

                    if (!current || Number(current) < 1) {
                        this.moduleSeats[moduleId] = 1;
                    }
                },

                /**
                 * Tick everything the chosen module needs, following the chain
                 * so a dependency that itself has dependencies is covered too.
                 */
                pullInDependencies(moduleId) {
                    const added = [];
                    const queue = [...(this.moduleDependencies[moduleId] || [])];
                    const seen = new Set([Number(moduleId)]);

                    while (queue.length) {
                        const requiredId = Number(queue.shift());

                        if (seen.has(requiredId)) continue;
                        seen.add(requiredId);

                        if (!this.isSelected(requiredId)) {
                            this.selectedModules.push(requiredId);
                            added.push(this.moduleNames[requiredId] || "another module");
                        }

                        this.ensureSeat(requiredId);
                        queue.push(...(this.moduleDependencies[requiredId] || []));
                    }

                    if (added.length) {
                        this.notify(
                            `${this.moduleNames[moduleId]} needs ${added.join(", ")} — added automatically.`
                        );
                    }
                },

                /**
                 * Unticking is refused while something still selected depends on
                 * this module. Silently allowing it would only move the failure
                 * to the server guard after the whole form was submitted.
                 */
                releaseModule(moduleId) {
                    const dependents = this.selectedModules.filter((selectedId) =>
                        (this.moduleDependencies[selectedId] || []).some(
                            (requiredId) => Number(requiredId) === Number(moduleId)
                        )
                    );

                    if (dependents.length) {
                        this.selectedModules.push(Number(moduleId));

                        const names = dependents.map((id) => this.moduleNames[id] || "a selected module");

                        this.notify(
                            `${this.moduleNames[moduleId]} cannot be removed — ${names.join(", ")} depends on it.`
                        );

                        return;
                    }

                    delete this.moduleSeats[moduleId];
                    this.dependencyNotice = "";
                },

                notify(message) {
                    this.dependencyNotice = message;

                    clearTimeout(this._noticeTimer);
                    this._noticeTimer = setTimeout(() => (this.dependencyNotice = ""), 6000);
                },
            };
        }
    </script>
@endsection
