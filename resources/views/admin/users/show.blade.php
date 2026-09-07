@extends ('layouts.admin')

@section ('title', 'User Details')

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">USER DETAILS</h1>
@endsection

@section ('content')
    <div class="w-full pb-10">
        {{-- 1. Header & Actions --}}
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div class="flex items-center gap-4">
                <a
                    href="{{ route('admin.users.index') }}"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-800"
                >
                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                </a>
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-gray-900">User Profile</h2>
                    <p class="mt-0.5 text-[13px] font-medium text-gray-500">Viewing details and access level for <strong>{{ $user->name }}</strong>.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if (has_module('hrm') && $user->employee && has_permission('employees.view'))
                    <a
                        href="{{ route('admin.hrm.employees.show', $user->employee->id) }}"
                        class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        <i data-lucide="id-card" class="h-4 w-4"></i> HR Profile
                    </a>
                @endif

                @if (has_permission('users.update'))
                    <a
                        href="{{ route('admin.users.edit', $user->id) }}"
                        class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        <i data-lucide="pencil" class="h-4 w-4"></i> Edit Profile
                    </a>
                @endif

                @if (auth()->id() !== $user->id && ! $user->isCompanyAdmin())
                    <form
                        action="{{ route('admin.users.destroy', $user->id) }}"
                        method="POST"
                        class="inline-block"
                        onsubmit="return confirm('Are you sure you want to remove {{ addslashes($user->name) }}? This action cannot be undone.');"
                    >
                        @csrf
                        @method ('DELETE')
                        <button
                            type="submit"
                            class="flex items-center gap-2 rounded-xl border border-red-100 bg-red-50 px-5 py-2.5 text-sm font-bold text-red-600 shadow-sm transition-colors hover:bg-red-100"
                        >
                            <i data-lucide="trash-2" class="h-4 w-4"></i> Remove
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- 2. Main Content Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
            {{-- ================= LEFT COLUMN: Profile Card (5 Cols) ================= --}}
            <div class="space-y-6 lg:col-span-5">
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    {{-- Decorative Top Banner --}}
                    <div class="h-28 bg-gradient-to-r from-emerald-500 to-teal-400"></div>

                    <div class="relative px-6 pb-8 text-center">
                        {{-- Avatar / Image --}}
                        <div class="relative inline-block">
                            @if ($user->image)
                                <img
                                    src="{{ asset('storage/' . $user->image) }}"
                                    alt="{{ $user->name }}"
                                    class="relative z-10 -mt-12 h-24 w-24 rounded-full border-4 border-white bg-white object-cover shadow-md"
                                />
                            @else
                                <div
                                    class="relative z-10 mx-auto -mt-12 flex h-24 w-24 items-center justify-center rounded-full border-4 border-white bg-[#e8f5ec] bg-white text-3xl font-extrabold text-[#108c2a] shadow-md"
                                >
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            {{-- Quick Status Indicator on Avatar --}}
                            <div
                                class="absolute bottom-1 right-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center z-20 {{ $user->status === 'active' ? 'bg-green-500' : 'bg-red-500' }}"
                                title="{{ ucfirst($user->status) }}"
                            ></div>
                        </div>

                        {{-- Basic Info --}}
                        <div class="mt-3">
                            <h3 class="text-xl font-extrabold text-gray-900">{{ $user->name }}</h3>
                            <div class="mt-2 flex flex-col items-center justify-center gap-1.5">
                                <a
                                    href="mailto:{{ $user->email }}"
                                    class="flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition-colors hover:text-emerald-600"
                                >
                                    <i data-lucide="mail" class="h-3.5 w-3.5"></i> {{ $user->email }}
                                </a>
                                @if ($user->phone)
                                    <a
                                        href="tel:{{ $user->phone }}"
                                        class="flex items-center gap-1.5 text-[13px] font-medium text-gray-500 transition-colors hover:text-emerald-600"
                                    >
                                        <i data-lucide="phone" class="h-3.5 w-3.5"></i> {{ $user->phone }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Badges --}}
                        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                            <span
                                class="inline-flex items-center rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-600 shadow-sm"
                            >
                                <i data-lucide="shield" class="mr-1.5 h-3.5 w-3.5"></i>
                                {{ $user->roles->first()->name ?? 'No Role Assigned' }}
                            </span>
                            @if ($user->status === 'active')
                                <span
                                    class="inline-flex items-center rounded-lg border border-green-100 bg-[#e8f5ec] px-3 py-1.5 text-xs font-bold text-[#108c2a] shadow-sm"
                                >
                                    <i data-lucide="check-circle" class="mr-1.5 h-3.5 w-3.5"></i> Active
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-lg border border-red-100 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 shadow-sm"
                                >
                                    <i data-lucide="x-circle" class="mr-1.5 h-3.5 w-3.5"></i> Suspended
                                </span>
                            @endif
                        </div>

                        {{-- System Details --}}
                        <div class="mt-8 space-y-4 border-t border-gray-100 pt-6 text-left">
                            <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                                <div class="flex items-center gap-2 text-[13px] font-medium text-gray-500">
                                    <i data-lucide="hash" class="h-4 w-4 text-gray-400"></i> System ID
                                </div>
                                <span class="font-bold text-gray-800"
                                    >#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span
                                >
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                                <div class="flex items-center gap-2 text-[13px] font-medium text-gray-500">
                                    <i data-lucide="calendar-days" class="h-4 w-4 text-gray-400"></i> Date Joined
                                </div>
                                <span class="font-bold text-gray-800">{{ $user->created_at->format('d M, Y') }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                                <div class="flex items-center gap-2 text-[13px] font-medium text-gray-500">
                                    <i data-lucide="clock" class="h-4 w-4 text-gray-400"></i> Last Updated
                                </div>
                                <span class="font-bold text-gray-800">{{ $user->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================= RIGHT COLUMN: Access & Permissions (7 Cols) ================= --}}
            <div class="space-y-6 lg:col-span-7">
                {{-- Store Access Card --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-white p-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <i data-lucide="store" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Assigned Stores</h3>
                                <p class="mt-0.5 text-[12px] font-medium text-gray-500">Locations this staff member manages.</p>
                            </div>
                        </div>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-700"
                        >
                            {{ $user->stores->count() }}
                        </div>
                    </div>

                    <div class="bg-slate-50/50 p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @forelse ($user->stores as $store)
                                <div
                                    class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-300"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-500 transition-colors group-hover:bg-blue-500 group-hover:text-white"
                                    >
                                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[14px] leading-tight font-bold text-gray-800">
                                            {{ $store->name }}
                                        </h4>
                                        <p class="mt-1 flex items-center gap-1 text-[11px] font-bold text-green-600">
                                            <i data-lucide="check" class="h-3 w-3"></i> Access Granted
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-10 text-center"
                                >
                                    <div
                                        class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50"
                                    >
                                        <i data-lucide="store-icon" class="h-6 w-6 text-gray-400"></i>
                                    </div>
                                    <h4 class="mb-1 text-sm font-bold text-gray-800">No Stores Assigned</h4>
                                    <p class="text-xs text-gray-500">This staff member cannot access any store data.</p>
                                    @if (auth()->id() !== $user->id && ($user->roles->first()->slug ?? '') !== 'owner')
                                        <a
                                            href="{{ route('admin.users.edit', $user->id) }}"
                                            class="mt-4 flex items-center gap-1 text-[13px] font-bold text-emerald-600 hover:text-emerald-700"
                                        >
                                            Assign Stores <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Module Access Card --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 bg-white p-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                            >
                                <i data-lucide="boxes" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Module Licenses</h3>
                                <p class="mt-0.5 text-[12px] font-medium text-gray-500">Features unlocked for this user account.</p>
                            </div>
                        </div>
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-700"
                        >
                            {{ $user->modules->count() }}
                        </div>
                    </div>

                    <div class="bg-slate-50/50 p-6">
                        @if (false)
                            <div
                                class="flex items-center gap-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 p-5 text-white shadow-sm"
                            >
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/20"
                                >
                                    <i data-lucide="star" class="h-6 w-6 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold">Full System Access</h4>
                                    <p class="mt-0.5 text-[13px] font-medium text-emerald-50">As a company admin, this user has unrestricted access to all active modules.</p>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @forelse ($user->modules as $module)
                                    <div
                                        class="group flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-3.5 shadow-sm transition-colors hover:border-emerald-300"
                                    >
                                        <div class="flex items-center gap-3 truncate">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e8f5ec] text-[#108c2a]"
                                            >
                                                <i data-lucide="box" class="h-4 w-4"></i>
                                            </div>
                                            <span
                                                class="truncate text-[13px] font-bold text-gray-800"
                                                >{{ $module->name }}</span
                                            >
                                        </div>
                                        <i data-lucide="check-circle-2" class="h-4 w-4 shrink-0 text-emerald-500"></i>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-8 text-center"
                                    >
                                        <i data-lucide="shield-off" class="mb-2 h-8 w-8 text-gray-300"></i>
                                        <h4 class="mb-1 text-sm font-bold text-gray-800">No Modules Assigned</h4>
                                        <p class="text-xs text-gray-500">This user relies strictly on base system permissions.</p>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
