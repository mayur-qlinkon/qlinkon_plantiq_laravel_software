@extends ('layouts.admin')

@section('title', 'Edit Staff Member')

@section('header-title')
    {{-- Page Header & Back Button --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users.index') }}"
            class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-800">
            <i data-lucide="arrow-left" class="h-5 w-5"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Edit Staff Member</h1>
            <p class="mt-1 text-sm text-gray-500">Update account details and store access for
                <strong>{{ $user->name }}</strong>.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div
            class="mb-6 flex items-center gap-2 rounded-xl border border-green-100 bg-green-50 px-5 py-4 text-sm font-bold text-green-700 shadow-sm">
            <i data-lucide="check-circle" class="h-5 w-5"></i> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div
            class="mb-6 flex items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-5 py-4 text-sm font-bold text-red-700 shadow-sm">
            <i data-lucide="alert-octagon" class="h-5 w-5"></i> {{ session('error') }}
        </div>
    @endif
    {{-- Alpine.js Data & Main Wrapper --}}
    <div class="w-full pb-10" x-data="{
        role_id: '{{ old('role_id', $user->roles->first()->id ?? '') }}',
        roleOptions: @js(collect($roles)->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values()),
        statusActive: {{ old('status', $user->status) === 'active' ? 'true' : 'false' }},
        showPassword: false,
        avatarPreview: {!! $user->image ? "'" . asset('storage/' . $user->image) . "'" : 'null' !!},
        handleImageUpload(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { this.avatarPreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        }
    }">
        {{-- Validation Errors --}}
        @if ($errors->any())
            <div
                class="mb-6 rounded-xl border border-red-100 bg-[#fee2e2] px-5 py-4 text-sm font-bold text-[#ef4444] shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i> Please fix the following requirements:
                </div>
                <ul class="list-inside list-disc space-y-1 pl-7 text-xs font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Form Container --}}
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data"
            class="w-full">
            @csrf
            @method ('PUT')

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
                {{-- ================= LEFT COLUMN (5 Columns) ================= --}}
                <div class="space-y-6 lg:col-span-5">
                    {{-- Personal Information Card --}}
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-4">
                            <i data-lucide="user" class="h-5 w-5 text-emerald-600"></i>
                            <h3 class="text-base font-semibold text-gray-800">Personal Information</h3>
                        </div>

                        {{-- Image Upload --}}
                        <div class="mb-6 flex flex-col items-center justify-center">
                            <label for="avatar-upload" class="group relative cursor-pointer">
                                <div
                                    class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-slate-50 transition-all group-hover:border-emerald-500">
                                    {{-- Uses x-show to avoid Lucide re-rendering infinite loops --}}
                                    <img x-show="avatarPreview" :src="avatarPreview" class="h-full w-full object-cover"
                                        x-cloak />

                                    <div x-show="!avatarPreview" class="flex flex-col items-center text-gray-400">
                                        <i data-lucide="user-plus" class="mb-1 h-8 w-8"></i>
                                        <span class="text-[10px] font-medium">Upload Photo</span>
                                    </div>
                                </div>
                                <input id="avatar-upload" type="file" name="image" class="hidden" accept="image/*"
                                    @change="handleImageUpload" />
                            </label>
                        </div>

                        <div class="space-y-4">
                            {{-- Full Name --}}
                            <div>
                                <label class="mb-2 block text-[13px] font-bold text-gray-700">Full Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                    placeholder="e.g. John Doe"
                                    class="w-full rounded-xl border border-gray-200 bg-slate-50/50 px-4 py-3 text-sm shadow-sm transition focus:border-transparent focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
                            </div>

                            {{-- Email Address --}}
                            <div>
                                <label class="mb-2 block text-[13px] font-bold text-gray-700">Email Address <span
                                        class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                    placeholder="john@company.com"
                                    class="w-full rounded-xl border border-gray-200 bg-slate-50/50 px-4 py-3 text-sm shadow-sm transition focus:border-transparent focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
                            </div>

                            {{-- Phone Number --}}
                            <div>
                                <label class="mb-2 block text-[13px] font-bold text-gray-700">Phone Number</label>
                                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                                    placeholder="9876543210" maxlength="10" minlength="10" inputmode="numeric"
                                    pattern="[0-9]{10}" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
                                    class="w-full rounded-xl border border-gray-200 bg-slate-50/50 px-4 py-3 text-sm shadow-sm transition focus:border-transparent focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
                            </div>

                            {{-- Password Field with Toggle --}}
                            <div class="relative">
                                <label class="mb-2 block text-[13px] font-bold text-gray-700">Password</label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" name="password"
                                        placeholder="Leave blank to keep current"
                                        class="w-full rounded-xl border border-gray-200 bg-slate-50/50 px-4 py-3 pr-10 text-sm shadow-sm transition focus:border-transparent focus:ring-2 focus:ring-emerald-500 focus:outline-none" />
                                    <button @click="showPassword = !showPassword" type="button"
                                        class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <i data-lucide="eye" class="h-4 w-4" x-show="!showPassword"></i>
                                        <i data-lucide="eye-off" class="h-4 w-4" x-show="showPassword" x-cloak></i>
                                    </button>
                                </div>
                                <p class="mt-1.5 text-[11px] text-gray-400"><i data-lucide="info"
                                        class="inline h-3 w-3"></i> Only fill this if you want to change their password.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Account Status Card (Toggle Switch) --}}
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Account Status</h3>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <span
                                        x-text="
                                            statusActive
                                                ? 'Active - User can login immediately'
                                                : 'Inactive - User is blocked'
                                        "></span>
                                </p>
                            </div>
                            <div class="relative flex items-center">
                                <button @click="statusActive = !statusActive" type="button"
                                    class="relative inline-flex h-8 w-14 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="statusActive ? 'bg-emerald-600' : 'bg-gray-200'">
                                    <span class="sr-only">Toggle Status</span>
                                    <span aria-hidden="true"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="statusActive ? 'translate-x-6' : 'translate-x-0'"></span>
                                </button>
                                <input type="hidden" name="status" :value="statusActive ? 'active' : 'inactive'" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= RIGHT COLUMN (7 Columns) ================= --}}
                <div class="space-y-6 lg:col-span-7">
                    {{-- Role & Stores Card --}}
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-4">
                            <i data-lucide="shield" class="h-5 w-5 text-emerald-600"></i>
                            <h3 class="text-base font-semibold text-gray-800">Role & Store Access</h3>
                        </div>

                        {{-- <div class="mb-6">
                            <label class="mb-2 block text-[13px] font-bold text-gray-700">
                                User Type <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="user_type"
                                class="w-full appearance-none rounded-xl border border-gray-200 bg-slate-50/50 px-4 py-3 text-sm shadow-sm transition focus:border-transparent focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                required
                            >
                                @foreach (\App\Enums\Auth\UserType::assignable() as $type)
                                    <option
                                        value="{{ $type->value }}"
                                        @selected (old('user_type', $user->user_type->value) === $type->value)
                                    >
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Company Admin can manage users, roles and settings. Module access is assigned separately for both types.</p>
                        </div> --}}

                        {{-- Assign Role --}}
                        <div class="mb-6">
                            <label class="mb-2 block text-[13px] font-bold text-gray-700">Assign Role <span
                                    class="text-red-500">*</span></label>
                            <x-alpine-select name="role_id" model="role_id" items="roleOptions"
                                placeholder="Select a Role" :required="true" :allow-empty="true" />
                        </div>

                        {{-- Assign Stores (Clickable Cards) --}}
                        @php
                            $userStoreIds = $user->stores->pluck('id')->toArray();
                            // Safely format selected stores as an array of strings for Alpine JS
                            $preSelectedStores = collect(old('store_ids', $userStoreIds))
                                ->map(fn($id) => (string) $id)
                                ->values()
                                ->all();
                        @endphp
                        <div x-data="{ selectedStores: @js($preSelectedStores) }">
                            <label class="mb-2 block text-[13px] font-bold text-gray-700">Assign Stores <span
                                    class="text-red-500">*</span></label>
                            <p class="mb-3 text-xs text-gray-500">Click a store to grant access. Multiple selection
                                allowed.</p>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @forelse ($stores as $store)
                                    <label class="group relative block cursor-pointer">
                                        <input type="checkbox" name="store_ids[]" value="{{ $store->id }}"
                                            class="peer sr-only" x-model="selectedStores"
                                            :required="selectedStores.length === 0" />

                                        <div class="relative flex flex-col items-start justify-between rounded-2xl border-2 p-5 shadow-sm transition-all duration-200 hover:border-emerald-300"
                                            :class="selectedStores.includes('{{ $store->id }}') ?
                                                'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 bg-white'">
                                            {{-- Top Right Checkbox (Inline SVG fixes the Lucide DOM issue) --}}
                                            <div class="absolute top-4 right-4 flex h-5 w-5 items-center justify-center rounded border transition-colors"
                                                :class="selectedStores.includes('{{ $store->id }}') ?
                                                    'border-emerald-600 bg-emerald-600 text-white' :
                                                    'border-gray-300 bg-white text-transparent'">
                                                <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </div>

                                            {{-- Icon --}}
                                            <div class="mb-3 rounded-xl p-3 transition-colors"
                                                :class="selectedStores.includes('{{ $store->id }}') ?
                                                    'bg-emerald-100 text-emerald-600' : 'bg-gray-50 text-gray-400'">
                                                <i data-lucide="store" class="h-6 w-6"></i>
                                            </div>

                                            {{-- Store Name --}}
                                            <span class="text-sm font-bold text-gray-800">{{ $store->name }}</span>
                                        </div>
                                    </label>
                                @empty
                                    <div
                                        class="col-span-full rounded-2xl border border-dashed border-gray-200 bg-white py-8 text-center">
                                        <i data-lucide="store" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                        <p class="text-sm font-bold text-gray-500">No stores available to assign.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div> {{-- 👈 Role & Stores Card is safely closed here now --}}

                    {{-- Module Access Card (Scalable List) --}}
                    @if ($assignableModules->isNotEmpty())
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div class="mb-1 flex items-center gap-2">
                                <i data-lucide="boxes" class="h-5 w-5 text-emerald-600"></i>
                                <h3 class="text-base font-semibold text-gray-800">Module Access</h3>
                            </div>

                            <p class="mb-4 text-xs text-gray-500">Only modules included in your current plan are shown.
                                Employees can only access modules assigned here.</p>

                            @php
                                $userModuleIds = old('module_ids', $user->modules->pluck('id')->all());
                            @endphp

                            {{-- Toolbar: filtering and bulk selection. With a
                                     dozen-plus modules, ticking one at a time was
                                     the slowest part of creating a user. --}}
                            <div x-data="moduleAccess()">
                                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <div class="relative flex-1">
                                        <i data-lucide="search"
                                            class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"></i>
                                        <input type="text" x-model="query" @keydown.escape="query = ''"
                                            placeholder="Filter modules..." autocomplete="off"
                                            class="w-full rounded-lg border border-gray-200 py-2 pr-3 pl-9 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <button type="button" @click="setAll(true)"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50">
                                            Select all
                                        </button>
                                        <button type="button" @click="setAll(false)"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50">
                                            Clear
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-2 flex items-center justify-between text-[11px] font-bold text-gray-400">
                                    <span x-text="selected + ' of ' + total + ' selected'"></span>
                                    <span x-show="query" x-cloak x-text="shown + ' shown'"></span>
                                </div>

                                {{-- Scrollable Container for scalability --}}
                                <div x-ref="list"
                                    class="module-scroll max-h-80 overflow-y-auto rounded-xl border border-gray-100 bg-slate-50/50 pr-1">
                                    @foreach ($assignableModules as $module)
                                        @php
                                            $license = $licenses->get($module->id);
                                            $isChecked = in_array($module->id, $userModuleIds);
                                            $seatLimit = $license?->seat_limit;
                                            $seatUsed = $seatsUsed[$module->id] ?? 0;
                                            // A seat is full if limit is reached AND this user doesn't already have it checked
                                            $seatFull =
                                                $license &&
                                                !is_null($seatLimit) &&
                                                $seatUsed >= $seatLimit &&
                                                !$isChecked;
                                        @endphp
                                        <label data-module-name="{{ Str::lower($module->name) }}"
                                            x-show="matches('{{ Str::lower($module->name) }}')"
                                            class="group flex cursor-pointer items-center justify-between gap-3 border-b border-gray-100 p-3 transition last:border-0 hover:bg-white">
                                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                                <input type="checkbox" name="module_ids[]" value="{{ $module->id }}"
                                                    @change="recount()"
                                                    class="h-5 w-5 shrink-0 cursor-pointer rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                                    @checked ($isChecked) @disabled ($seatFull) />
                                                <span
                                                    class="text-sm font-medium text-gray-700 truncate {{ $seatFull ? 'line-through opacity-60' : '' }}">{{ $module->name }}</span>
                                            </div>
                                            @if ($license && !is_null($seatLimit))
                                                <span
                                                    class="shrink-0 text-[10px] bg-gray-100 text-gray-500 px-2.5 py-0.5 rounded-full border border-gray-200 {{ $seatFull ? '!bg-red-100 !text-red-600 !border-red-200' : '' }}">
                                                    {{ $seatFull ? 'Seat Limit Reached' : "$seatUsed/$seatLimit" }}
                                                </span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                <div x-show="shown === 0" x-cloak class="py-6 text-center">
                                    <p class="text-xs font-semibold text-gray-400">No modules match that filter.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Form Footer Actions --}}
                    <div class="mt-8 flex flex-col-reverse justify-end gap-3 border-t border-gray-200 pt-6 sm:flex-row">
                        <a href="{{ route('admin.users.index') }}"
                            class="rounded-xl border border-gray-300 bg-white px-8 py-3 text-center text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-8 py-3 text-sm font-bold text-white shadow-md transition-colors hover:bg-emerald-700">
                            <i data-lucide="save" class="h-4 w-4"></i> Update Staff Member
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Custom Styling for Scrollbar --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        .module-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .module-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .module-scroll::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 8px;
        }

        .module-scroll::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>

    <script>
        function moduleAccess() {
            return {
                query: "",
                selected: 0,
                total: 0,
                shown: 0,

                init() {
                    this.total = this.boxes().length;
                    this.recount();
                    this.$watch("query", () => this.recountShown());
                    this.recountShown();
                },

                // Live NodeList of every module checkbox, disabled ones included.
                boxes() {
                    return Array.from(this.$refs.list.querySelectorAll('input[type="checkbox"]'));
                },

                matches(name) {
                    const q = this.query.trim().toLowerCase();
                    return q === "" || name.includes(q);
                },

                recount() {
                    this.selected = this.boxes().filter((b) => b.checked).length;
                },

                // Counts rows currently passing the filter, so the "N shown" hint
                // and the empty state stay in step with what is visible.
                recountShown() {
                    const q = this.query.trim().toLowerCase();
                    this.shown = Array.from(this.$refs.list.querySelectorAll("[data-module-name]")).filter(
                        (el) => q === "" || el.dataset.moduleName.includes(q),
                    ).length;
                },

                // Only touches rows the filter is showing, and never a seat-limited
                // one — those are disabled because the licence has no seats left.
                setAll(state) {
                    this.boxes().forEach((box) => {
                        if (box.disabled) return;

                        const row = box.closest("[data-module-name]");
                        if (!this.matches(row.dataset.moduleName)) return;

                        box.checked = state;
                    });

                    this.recount();
                },
            };
        }
    </script>
@endsection
