@extends('layouts.platform')

@section('title', $tenant->name . ' — Tenant Detail')
@section('header', 'tenant Detail')

@section('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .dl-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f9fafb;
        }

        .dl-row:last-child {
            border-bottom: none;
        }

        .dl-label {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .04em;
            min-width: 120px;
            flex-shrink: 0;
            padding-top: 1px;
        }

        .dl-value {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
            text-align: right;
            word-break: break-all;
        }

        .card {
            background: #fff;
            border: 1px solid #f3f4f6;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }

        .card-head {
            padding: 14px 20px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }
    </style>
@endsection

@section('content')

    @if (session('default_user'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="font-semibold text-amber-900">Starter staff login created</p>
            <p class="mt-1 text-sm text-amber-800">
                Shown once only. Share it with the tenant and ask them to change the password.
            </p>
            <dl class="mt-3 grid grid-cols-1 gap-1 text-sm sm:grid-cols-2">
                <div>
                    <dt class="inline text-amber-700">Email:</dt>
                    <dd class="inline font-mono">{{ session('default_user.email') }}</dd>
                </div>
                <div>
                    <dt class="inline text-amber-700">Password:</dt>
                    <dd class="inline font-mono">{{ session('default_user.password') }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @php
        $sub = $tenant->subscription;
        $plan = $sub?->plan;
        $now = \Carbon\Carbon::now();
        $expiryDate = $sub?->expires_at ? \Carbon\Carbon::parse($sub->expires_at)->endOfDay() : null;
        $isExpired = $expiryDate && $expiryDate->isPast();
        $daysLeft = $expiryDate && !$isExpired ? (int) $now->diffInDays($expiryDate, false) : null;
    @endphp

    <div class="w-full pb-10 space-y-5">

        {{-- ── Breadcrumb ── --}}
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('platform.tenants.index') }}" class="hover:text-brand-600 font-medium">tenants</a>
            <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="text-gray-800 font-semibold truncate">{{ $tenant->name }}</span>
        </div>

        {{-- ── Flash ── --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 text-sm font-medium px-4 py-3 rounded-xl">
                <i class="fas fa-circle-check shrink-0"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded-xl">
                <i class="fas fa-circle-exclamation shrink-0"></i> {{ session('error') }}
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════
         HERO ROW — Company identity + action buttons
    ══════════════════════════════════════════════════ --}}
        <div class="card">
            <div class="card-body flex flex-col sm:flex-row sm:items-center gap-5">

                {{-- Avatar + name --}}
                <div
                    class="w-14 h-14 rounded-2xl bg-brand-600/10 text-brand-700 font-black text-2xl flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-xl font-bold text-gray-900 truncate">{{ $tenant->name }}</h2>
                        {{-- Company status --}}
                        @if ($tenant->is_active)
                            <span
                                class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-[11px] font-bold px-2.5 py-1 rounded-full border border-green-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 bg-red-50 text-red-600 text-[11px] font-bold px-2.5 py-1 rounded-full border border-red-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Inactive
                            </span>
                        @endif
                        {{-- Subscription status badge --}}
                        @if (!$sub)
                            <span
                                class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-[11px] font-bold px-2.5 py-1 rounded-full">No
                                Plan</span>
                        @elseif (!$sub->is_active)
                            <span
                                class="inline-flex items-center gap-1 bg-red-100 text-red-600 text-[11px] font-bold px-2.5 py-1 rounded-full">Sub
                                Inactive</span>
                        @elseif ($isExpired)
                            <span
                                class="inline-flex items-center gap-1 bg-orange-100 text-orange-600 text-[11px] font-bold px-2.5 py-1 rounded-full">Expired</span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 text-[11px] font-bold px-2.5 py-1 rounded-full border border-brand-100">
                                <i class="fas fa-circle-check text-[8px]"></i> Subscribed
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400">
                        <span><i class="fas fa-envelope mr-1"></i>{{ $tenant->email }}</span>
                        <code class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md font-mono">{{ $tenant->slug }}</code>
                        <span class="text-gray-300">|</span>
                        <span><i class="fas fa-users mr-1"></i>{{ $tenant->users->count() }} users</span>
                        <span><i class="fas fa-shop mr-1"></i>{{ $tenant->stores->count() }} stores</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('platform.tenants.edit', $tenant) }}"
                        class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:border-brand-400 text-gray-700 hover:text-brand-600 text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                        <i class="fas fa-pen text-xs"></i> Edit
                    </a>
                    <form id="del-company-{{ $tenant->id }}" method="POST"
                        action="{{ route('platform.tenants.destroy', $tenant) }}" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                    <button type="button"
                        onclick="confirmTerminate({{ $tenant->id }}, '{{ addslashes($tenant->name) }}')"
                        class="inline-flex items-center gap-1.5 bg-white border border-gray-200 hover:border-red-400 text-gray-700 hover:text-red-600 text-sm font-semibold px-4 py-2 rounded-xl transition-colors">
                        <i class="fas fa-trash text-xs"></i> Terminate
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
         STAT PILLS ROW
    ══════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $statItems = [
                    [
                        'icon' => 'fa-users',
                        'color' => 'brand',
                        'label' => 'Total Users',
                        'value' => $tenant->users->count(),
                    ],
                    ['icon' => 'fa-shop', 'color' => 'blue', 'label' => 'Stores', 'value' => $tenant->stores->count()],
                    ['icon' => 'fa-layer-group', 'color' => 'brand', 'label' => 'Plan', 'value' => $plan?->name ?? '—'],
                    [
                        'icon' => 'fa-cubes',
                        'color' => 'brand',
                        'label' => 'Modules',
                        'value' => $plan ? $plan->modules->count() . ' active' : '—',
                    ],
                ];
                $colors = [
                    'brand' => 'bg-brand-50 text-brand-600',
                    'blue' => 'bg-blue-50 text-blue-600',
                ];
            @endphp
            @foreach ($statItems as $s)
                <div class="card">
                    <div class="card-body flex items-center gap-3 py-4">
                        <div
                            class="w-10 h-10 rounded-xl {{ $colors[$s['color']] }} flex items-center justify-center shrink-0">
                            <i class="fas {{ $s['icon'] }} text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-lg font-black text-gray-800 leading-tight truncate">{{ $s['value'] }}</p>
                            <p class="text-[11px] text-gray-400 font-medium">{{ $s['label'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════
         MAIN GRID — 2 col layout
    ══════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- ── Company Info ── --}}
            <div class="card">
                <div class="card-head bg-gray-50/60">
                    <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-building text-white text-xs"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm">Company Info</h3>
                </div>
                <div class="card-body">
                    @php
                        $companyRows = [
                            ['Email', $tenant->email],
                            ['Phone', $tenant->phone ?? '—'],
                            ['City', $tenant->city ?? '—'],
                            ['State', $tenant->state?->name ?? '—'],
                            ['GST', $tenant->gst_number ?? '—'],
                            ['Subdomain', $tenant->subdomain ?? '—'],
                            ['Domain', $tenant->domain ?? '—'],
                            ['Onboarded', $tenant->created_at->format('d M Y')],
                        ];
                    @endphp
                    @foreach ($companyRows as [$label, $value])
                        <div class="dl-row">
                            <span class="dl-label">{{ $label }}</span>
                            <span class="dl-value">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Primary Owner ── --}}
            <div class="card border-orange-100/80">
                <div class="card-head bg-orange-50/40 border-orange-100/80">
                    <div class="w-7 h-7 rounded-lg bg-orange-500 flex items-center justify-center shrink-0">
                        <i class="fas fa-user text-white text-xs"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm">Primary Owner</h3>
                </div>
                @if ($owner)
                    <div class="card-body">
                        {{-- Owner avatar row --}}
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-50">
                            <div
                                class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 font-bold text-sm flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($owner->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 truncate">{{ $owner->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $owner->email }}</p>
                            </div>
                            <span
                                class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $owner->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ ucfirst($owner->status ?? 'active') }}
                            </span>
                        </div>
                        @php
                            $ownerRows = [
                                ['Phone', $owner->phone ?? '—'],
                                ['Role', $owner->user_type->label()],
                                ['Joined', $owner->created_at->format('d M Y')],
                            ];
                        @endphp
                        @foreach ($ownerRows as [$label, $value])
                            <div class="dl-row">
                                <span class="dl-label">{{ $label }}</span>
                                <span class="dl-value">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card-body py-10 text-center text-sm text-gray-400">
                        <i class="fas fa-user-slash text-2xl mb-2 block text-gray-300"></i>
                        No owner found for this company.
                    </div>
                @endif
            </div>

        </div>{{-- end 2-col --}}

        {{-- ══════════════════════════════════════════════════
         SUBSCRIPTION + PLAN — full width card
    ══════════════════════════════════════════════════ --}}
        @if ($sub)
            <div class="card border-brand-100/60">
                <div class="card-head bg-brand-50/30 border-brand-100/60 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center shrink-0">
                            <i class="fas fa-calendar-check text-white text-xs"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">Subscription &amp; Plan</h3>
                    </div>
                    {{-- Sub status pill --}}
                    @if (!$sub->is_active)
                        <span
                            class="text-[11px] font-bold px-3 py-1 rounded-full bg-red-100 text-red-600 uppercase tracking-wider">Inactive</span>
                    @elseif ($isExpired)
                        <span
                            class="text-[11px] font-bold px-3 py-1 rounded-full bg-orange-100 text-orange-600 uppercase tracking-wider">Expired</span>
                    @elseif ($expiryDate)
                        <span
                            class="text-[11px] font-bold px-3 py-1 rounded-full bg-green-100 text-green-700 uppercase tracking-wider">
                            Active
                            @if ($daysLeft !== null && $daysLeft <= 7)
                                · <span class="text-orange-600">{{ $daysLeft }}d left</span>
                            @else
                                · {{ $daysLeft }}d left
                            @endif
                        </span>
                    @else
                        <span
                            class="text-[11px] font-bold px-3 py-1 rounded-full bg-green-100 text-green-700 uppercase tracking-wider">
                            Active · Lifetime
                        </span>
                    @endif
                </div>

                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Sub dates / meta --}}
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Subscription
                                Details</p>
                            @php
                                $subRows = [
                                    ['Plan', $plan?->name ?? '—'],
                                    [
                                        'Price',
                                        $plan
                                            ? '₹' . number_format($plan->price, 0) . ' / ' . $plan->billing_cycle
                                            : '—',
                                    ],
                                    [
                                        'Starts',
                                        $sub->starts_at ? \Carbon\Carbon::parse($sub->starts_at)->format('d M Y') : '—',
                                    ],
                                    [
                                        'Expires',
                                        $sub->expires_at
                                            ? \Carbon\Carbon::parse($sub->expires_at)->format('d M Y')
                                            : 'Lifetime / No Expiry',
                                    ],
                                    ['Trial', $plan?->trial_days > 0 ? $plan->trial_days . ' days' : 'None'],
                                ];
                            @endphp
                            @foreach ($subRows as [$label, $value])
                                <div class="dl-row">
                                    <span class="dl-label">{{ $label }}</span>
                                    <span class="dl-value">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Plan resource limits --}}
                        @if ($plan)
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Resource
                                    Limits</p>
                                @php
                                    $limitRows = [
                                        ['fa-users', 'Users', $plan->user_limit],
                                        ['fa-shop', 'Stores', $plan->store_limit],
                                        ['fa-box-open', 'Products', $plan->product_limit],
                                        ['fa-id-badge', 'Employees', $plan->employee_limit],
                                        [
                                            'fa-expand',
                                            'OCR Scans/day',
                                            $plan->ocr_scan_limit === 0
                                                ? 'Unlimited'
                                                : number_format($plan->ocr_scan_limit),
                                        ],
                                        [
                                            'fa-robot',
                                            'AI Chats/day',
                                            $plan->ai_chat_daily_limit === 0
                                                ? 'Unlimited'
                                                : number_format($plan->ai_chat_daily_limit),
                                        ],
                                        [
                                            'fa-microchip',
                                            'AI Tokens/day',
                                            $plan->ai_token_daily_limit === -1
                                                ? 'Unlimited'
                                                : number_format($plan->ai_token_daily_limit),
                                        ],
                                    ];
                                @endphp
                                @foreach ($limitRows as [$icon, $label, $value])
                                    <div class="dl-row">
                                        <span class="dl-label flex items-center gap-1.5">
                                            <i class="fas {{ $icon }} text-gray-300 w-3 text-center"></i>
                                            {{ $label }}
                                        </span>
                                        <span class="dl-value font-bold">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>

                    {{-- ── Modules grid ── --}}
                    @if ($plan && $plan->modules->isNotEmpty())
                        <div class="mt-5 pt-5 border-t border-gray-50">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Assigned Modules
                                </p>
                                <span
                                    class="text-[11px] font-bold bg-brand-50 text-brand-700 px-2.5 py-1 rounded-lg border border-brand-100">
                                    {{ $plan->modules->count() }} modules
                                </span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                @foreach ($plan->modules as $module)
                                    @php
                                        $license = $licenses->get($module->id);
                                    @endphp
                                    <div
                                        class="flex items-center gap-2 px-3 py-2 rounded-xl border {{ $license ? 'bg-brand-50/40 border-brand-100/60' : 'bg-amber-50/60 border-amber-200' }}">
                                        <i
                                            class="fas {{ $license ? 'fa-circle-check text-brand-500' : 'fa-triangle-exclamation text-amber-500' }} text-[11px] shrink-0"></i>
                                        <span
                                            class="text-xs font-semibold text-gray-700 truncate">{{ $module->name }}</span>
                                        @unless ($license)
                                            <span class="text-[9px] font-bold text-amber-600 ml-auto shrink-0">NOT
                                                LICENSED</span>
                                        @endunless
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($plan && $plan->modules->isEmpty())
                        <div class="mt-5 pt-5 border-t border-gray-50 text-center py-6 text-sm text-gray-400">
                            <i class="fas fa-cubes text-2xl mb-1 block text-gray-300"></i>
                            No modules assigned to this plan.
                        </div>
                    @endif

                </div>
            </div>
        @else
            {{-- No subscription yet --}}
            <div class="card border-dashed border-gray-200">
                <div class="card-body py-10 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-layer-group text-gray-300 text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 mb-1">No subscription assigned yet.</p>
                    <p class="text-xs text-gray-400 mb-4">Edit this tenant to add a plan and activate their subscription.
                    </p>
                    <a href="{{ route('platform.tenants.edit', $tenant) }}"
                        class="inline-flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-5 py-2 rounded-xl transition-colors">
                        <i class="fas fa-plus text-xs"></i> Assign Plan
                    </a>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════
         STORES LIST
    ══════════════════════════════════════════════════ --}}
        @if ($tenant->stores->isNotEmpty())
            <div class="card">
                <div class="card-head bg-gray-50/60">
                    <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
                        <i class="fas fa-shop text-white text-xs"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm">Stores ({{ $tenant->stores->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach ($tenant->stores as $store)
                        <div class="flex items-center justify-between px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($store->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $store->name }}</p>
                                    <code class="text-[11px] text-gray-400 font-mono">{{ $store->slug }}</code>
                                </div>
                            </div>
                            <span
                                class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $store->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $store->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════
             USAGE HEALTH
             Read entirely from the nightly snapshot on companies.
             Nothing here touches activity_log, which is far too
             large to query while rendering a page.
        ══════════════════════════════════════════════════ --}}
        @php
            $health = $tenant->health();
            $idle = $tenant->idleDays();
            $bitmap = $tenant->activityBitmap();
            $activeDays = count(array_filter($bitmap));
            $totalUsers = (int) $tenant->usageStat('total_users');
            $activeUsers = (int) $tenant->usageStat('active_users_30');
            $actions = (int) $tenant->usageStat('actions_30');
            $onlineNow = $tenant->onlineUserCount();

            // Everything the markup needs, resolved up here. Ternaries inside
            // an x-data attribute mix two languages in one string and are a
            // poor place to hunt for a missing brace.
            $openDefault = $health->needsAttention() ? 'true' : 'false';
            $needsCall = $health->needsAttention();
            $neverStarted = $health === \App\Enums\UsageHealth::NeverStarted;
            $lastActiveLabel = $tenant->lastActiveAt()
                ? $tenant->lastActiveAt()->diffForHumans(null, true) . ' ago'
                : 'Never';
            $quietLine = $neverStarted
                ? 'This tenant was onboarded but has never used the software. A setup call would probably rescue the account.'
                : 'Quiet for ' . $idle . ' ' . \Illuminate\Support\Str::plural('day', $idle) . '.';
        @endphp

        <div class="card" x-data="{ open: {{ $openDefault }} }">
            <button type="button" @click="open = !open"
                class="card-head bg-gray-50/60 w-full text-left hover:bg-gray-100/60 transition-colors">
                <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
                    <i class="fas fa-heart-pulse text-white text-xs"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-sm">Usage Health</h3>

                <span class="ml-auto text-[11px] font-bold px-2.5 py-1 rounded-full border {{ $health->badgeClasses() }}">
                    <i class="fas {{ $health->icon() }} mr-1 text-[9px]"></i>{{ $health->label() }}
                </span>

                @if ($onlineNow > 0)
                    <span
                        class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">
                        {{ $onlineNow }} online
                    </span>
                @endif

                <i class="fas fa-chevron-down text-gray-400 text-xs shrink-0"></i>
            </button>

            <div class="card-body" x-show="open" x-cloak>

                @unless ($tenant->usage_computed_at)
                    <p class="text-[13px] text-gray-500">
                        No usage data yet. The nightly job has not run since this was switched on.
                    </p>
                @endunless

                @isset($tenant->usage_computed_at)
                    <p class="text-[13px] text-gray-500 mb-5">{{ $health->description() }}</p>

                    {{-- Four figures, no names. A tenant with two hundred staff
                         would otherwise turn this card into a directory. --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Last active</p>
                            <p class="text-sm font-bold text-gray-800">{{ $lastActiveLabel }}</p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Active days</p>
                            <p class="text-sm font-bold text-gray-800">{{ $activeDays }} / 30</p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Staff using it</p>
                            <p class="text-sm font-bold text-gray-800">{{ $activeUsers }} / {{ $totalUsers }}</p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-3.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Actions</p>
                            <p class="text-sm font-bold text-gray-800">{{ number_format($actions) }}</p>
                        </div>
                    </div>

                    {{-- Thirty cells, oldest on the left. Fits any tenant size
                         because it counts days, not people. --}}
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Last 30 days</p>
                    <div class="flex items-center gap-[3px] mb-2">
                        @foreach ($bitmap as $wasActive)
                            <div class="h-6 flex-1 rounded-[3px] {{ $wasActive ? 'bg-indigo-500' : 'bg-gray-100' }}"></div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-400 font-medium">
                        <span>30 days ago</span>
                        <span>Today</span>
                    </div>

                    @if ($needsCall)
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3">
                            <p class="text-[13px] font-bold text-gray-800 mb-1">Worth reaching out</p>
                            <p class="text-[12px] text-gray-600 leading-snug">{{ $quietLine }}</p>
                            <p class="text-[12px] mt-1">
                                <a href="mailto:{{ $tenant->email }}"
                                    class="text-brand-600 font-semibold hover:underline">{{ $tenant->email }}</a>
                                @if ($tenant->phone)
                                    <span class="text-gray-300 mx-1">|</span>
                                    <a href="tel:{{ $tenant->phone }}"
                                        class="text-brand-600 font-semibold hover:underline">{{ $tenant->phone }}</a>
                                @endif
                            </p>
                        </div>
                    @endif

                    <p class="mt-5 text-[11px] text-gray-400">
                        Figures updated {{ $tenant->usage_computed_at->diffForHumans() }}. Online count is live.
                    </p>
                @endisset

            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
            DANGER ZONE
        ══════════════════════════════════════════════════ --}}
        <div class="card border-red-100">
            <div class="card-head bg-red-50/40 border-red-100">
                <div class="w-7 h-7 rounded-lg bg-red-500 flex items-center justify-center shrink-0">
                    <i class="fas fa-triangle-exclamation text-white text-xs"></i>
                </div>
                <h3 class="font-bold text-red-700 text-sm">Danger Zone</h3>
            </div>
            <div class="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-700 mb-0.5">Terminate this company</p>
                    <p class="text-xs text-gray-400">Permanently deletes all stores, users, and data. This cannot be
                        undone.</p>
                </div>
                <button type="button"
                    onclick="confirmTerminate({{ $tenant->id }}, '{{ addslashes($tenant->name) }}')"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors shrink-0">
                    <i class="fas fa-trash text-xs"></i> Terminate Company
                </button>
            </div>
        </div>

    </div>


    <script>
        function confirmTerminate(id, name) {
            Swal.fire({
                title: 'Terminate Company?',
                html: `<p style="color:#4b5563;font-size:14px;margin-top:4px;">
                    You are about to permanently terminate:<br>
                    <strong style="color:#111827">${name}</strong>
               </p>
               <p style="color:#ef4444;font-size:13px;margin-top:10px;">
                   All stores, users, and data will be deleted.<br>
                   <strong>This cannot be undone.</strong>
               </p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Terminate',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('del-company-' + id).submit();
                }
            });
        }
    </script>
@endsection
