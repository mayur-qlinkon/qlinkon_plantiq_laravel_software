@extends ('layouts.admin')

@section('title', 'User Management')

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">USERS</h1>
@endsection

@section('content')
    <div class="pb-10">
        {{-- 1. HEADER & ACTIONS --}}
        <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <h2 class="text-[28px] font-extrabold tracking-tight text-[#1f2937]">User Management</h2>
                <p class="mt-1 text-[14px] font-medium text-gray-500">Manage your cashiers, managers, and their store
                    assignments.</p>
            </div>
            <div class="flex items-center gap-2">
                @if (check_plan_limit('users'))
                    <a href="{{ route('admin.users.create') }}"
                        class="flex items-center gap-2 rounded-lg bg-[#108c2a] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0d7322] active:scale-95">
                        <i data-lucide="user-plus" class="h-4 w-4"></i> Add Staff Member
                    </a>
                @else
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-red-100 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600">
                            <i data-lucide="alert-triangle" class="h-3.5 w-3.5"></i>
                            User Limit Reached
                        </span>
                        @if (has_permission('users.create'))
                            <button type="button"
                                class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-bold text-gray-400"
                                title="You have reached your staff limit. Upgrade your plan to add more users.">
                                <i data-lucide="user-plus" class="h-4 w-4"></i>
                                Add Staff Member
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

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

        <div id="users-list-container" x-data="usersIndex()" @click="handlePaginationClick($event)">
            {{-- 2. TOOLBAR (Search & Filters) --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <form id="user-filter-form" method="GET" action="{{ route('admin.users.index') }}"
                    @submit.prevent="submitForm" @change="submitForm"
                    class="flex flex-col gap-4 md:flex-row md:items-center">
                    {{-- Search Input --}}
                    <div class="relative flex-1">
                        <i data-lucide="search"
                            class="absolute top-1/2 left-3.5 h-4.5 w-4.5 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            @input.debounce.400ms="submitForm" placeholder="Search name or email..."
                            class="w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>

                    {{-- Clear Filters Button --}}
                    <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                        class="flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        title="Clear Filters">
                        <i data-lucide="x" class="h-4 w-4"></i> Clear
                    </button>
                </form>
            </div>

            {{-- 3. ACTIVE COMPANY MODULE LICENSES (ACCORDION) --}}
            @if ($licenses->isNotEmpty())
                @php
                    $totalLimit = 0;
                    $hasUnlimited = false;
                    foreach ($licenses as $l) {
                        if (is_null($l->seat_limit)) {
                            $hasUnlimited = true;
                        } else {
                            $totalLimit += $l->seat_limit;
                        }
                    }
                @endphp

                {{-- Alpine Component wrapper for Accordion --}}
                <div x-data="{ showLicenses: false }"
                    class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300">
                    {{-- Clickable Header --}}
                    <div @click="showLicenses = !showLicenses"
                        class="flex cursor-pointer flex-col justify-between gap-4 p-6 transition-colors hover:bg-gray-50/50 sm:flex-row sm:items-center">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-extrabold text-gray-900">Active Company Module Licenses</h3>
                                <i data-lucide="chevron-down"
                                    class="h-5 w-5 text-gray-400 transition-transform duration-300"
                                    :class="showLicenses ? 'rotate-180' : ''"></i>
                            </div>
                            <p class="mt-0.5 text-[13px] font-medium text-gray-500">Displaying only modules with valid seat
                                allocations.</p>
                        </div>
                        <div
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-[12px] font-medium text-gray-600 shadow-sm">
                            Total Seats:
                            <span
                                class="font-extrabold text-[#108c2a]">{{ $hasUnlimited ? 'Unlimited' : $totalLimit }}</span>
                        </div>
                    </div>

                    {{-- Accordion Content --}}
                    <div x-show="showLicenses" x-collapse style="display: none"
                        class="border-t border-gray-100 bg-gray-50/30 p-6 pt-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($licenses as $license)
                                @php
                                    $used = $seatsUsed[$license->module_id] ?? 0;
                                    $limit = $license->seat_limit;
                                    $isUnlimited = is_null($limit);
                                    $available = $isUnlimited ? '∞' : max(0, $limit - $used);
                                    $percent = $isUnlimited ? 0 : ($limit > 0 ? min(100, ($used / $limit) * 100) : 0);
                                @endphp

                                <div
                                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                                    {{-- Card Header --}}
                                    <div class="mb-4 flex items-start justify-between gap-2">
                                        <h4 class="text-[14px] leading-tight font-bold text-gray-800">
                                            {{ $license->module->name ?? 'Unkown Module' }}
                                        </h4>
                                        <span
                                            class="inline-flex shrink-0 items-center rounded-full bg-[#e8f5ec] px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-[#108c2a]">
                                            {{ $isUnlimited ? 'Unlimited' : $limit . ' Seats' }}
                                        </span>
                                    </div>

                                    {{-- Stats --}}
                                    <div class="mb-2 flex items-center justify-between text-[12px]">
                                        <div class="text-gray-500">
                                            <span class="font-bold text-gray-800">{{ $used }}</span> Used
                                        </div>
                                        <div class="text-gray-500">
                                            <span class="font-bold text-gray-800">{{ $available }}</span> Available
                                        </div>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-[#108c2a] transition-all duration-500"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- 4. DATA TABLE --}}
            <div id="users-table-wrapper" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                {{-- 🖥️ DESKTOP VIEW --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th
                                    class="w-16 px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    #
                                </th>
                                <th class="px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Staff Member
                                </th>
                                <th class="px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Phone
                                </th>
                                <th class="px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Assigned Role
                                </th>
                                <th class="px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Joined At
                                </th>
                                <th class="px-6 py-4 text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-[11px] font-extrabold tracking-widest text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="usersTbody" class="divide-y divide-gray-100">
                            @forelse ($users as $index => $user)
                                <tr class="group transition-colors hover:bg-gray-50/50">
                                    <td class="px-6 py-4 text-[13px] font-bold text-gray-400">
                                        {{ $users->firstItem() + $index }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8f5ec] text-[13px] font-extrabold text-[#108c2a]">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="text-[14px] font-bold text-gray-800">{{ $user->name }}</div>
                                                <div class="text-[13px] text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-[13px] font-medium text-gray-600">
                                        {{ $user->phone ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $roleName = $user->roles->first()->name ?? 'No Role';
                                            // Optional: dynamic coloring based on role name just to match the colorful screenshot vibe
                                            $roleBg = match (strtolower($roleName)) {
                                                'store manager' => 'bg-blue-100 text-blue-700',
                                                'cashier' => 'bg-green-100 text-green-700',
                                                'inventory lead' => 'bg-indigo-100 text-indigo-700',
                                                default => 'bg-purple-100 text-purple-700',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center rounded-full {{ $roleBg }} px-2.5 py-1 text-[11px] font-bold tracking-wide">
                                            {{ $roleName }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-[13px] font-medium text-gray-600">
                                        {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @if ($user->status === 'active')
                                            <span
                                                class="inline-flex items-center rounded-full bg-[#e8f5ec] px-2.5 py-1 text-[11px] font-bold tracking-wide text-[#108c2a]">
                                                Active
                                            </span>
                                        @elseif ($user->status === 'pending')
                                            <span
                                                class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-1 text-[11px] font-bold tracking-wide text-yellow-700">
                                                Pending
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold tracking-wide text-red-700">
                                                Suspended
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div
                                            class="flex items-center justify-end gap-1.5 opacity-60 transition-opacity group-hover:opacity-100">
                                            @if (has_permission('users.view'))
                                                <a href="{{ route('admin.users.show', $user->id) }}"
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-800"
                                                    title="View">
                                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                                </a>
                                            @endif

                                            @if (has_permission('users.update'))
                                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-800"
                                                    title="Edit">
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </a>
                                            @endif
                                            @if (auth()->id() !== $user->id && !$user->isCompanyAdmin() && has_permission('users.delete'))
                                                <form action="{{ route('admin.users.destroy', $user->id) }}"
                                                    method="POST" class="delete-staff-form inline-block"
                                                    data-name="{{ addslashes($user->name) }}">
                                                    @csrf
                                                    @method ('DELETE')
                                                    <button type="submit"
                                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                                                        title="Delete">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i data-lucide="users" class="mb-3 h-12 w-12 text-gray-300"></i>
                                            <h3 class="mb-1 text-lg font-bold text-gray-800">No Staff Members Found</h3>
                                            <p class="text-sm font-medium">Try adjusting your search or add a new staff
                                                member.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- 📱 MOBILE VIEW (CARDS) --}}
                <div class="divide-y divide-gray-100 border-t border-gray-100 bg-white md:hidden">
                    @forelse ($users as $index => $user)
                        <div class="p-4 transition-colors hover:bg-gray-50/50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f5ec] text-[13px] font-extrabold text-[#108c2a]">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-[14px] font-bold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-[12px] text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <div>
                                    @if ($user->status === 'active')
                                        <span
                                            class="inline-flex rounded-full bg-[#e8f5ec] px-2 py-0.5 text-[10px] font-bold tracking-wide text-[#108c2a]">Active</span>
                                    @elseif ($user->status === 'pending')
                                        <span
                                            class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-yellow-700">Pending</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-red-700">Suspended</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-3 text-[12px]">
                                <div>
                                    <span
                                        class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Role</span>
                                    <span
                                        class="mt-0.5 block font-medium text-gray-800">{{ $user->roles->first()->name ?? 'No Role' }}</span>
                                </div>
                                <div>
                                    <span
                                        class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Joined</span>
                                    <span
                                        class="mt-0.5 block font-medium text-gray-800">{{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                                @if (has_permission('users.view'))
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                @if (has_permission('users.update'))
                                    <a href="{{ route('admin.users.edit', $user->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>
                                @endif
                                @if (auth()->id() !== $user->id && !$user->isCompanyAdmin() && has_permission('users.delete'))
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                        class="delete-staff-form" data-name="{{ addslashes($user->name) }}">
                                        @csrf
                                        @method ('DELETE')
                                        <button type="submit"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            <i data-lucide="users" class="mx-auto mb-3 h-10 w-10 opacity-50"></i>
                            <p class="text-sm font-bold text-gray-600">No Staff Members Found</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($users->hasPages())
                    <div class="border-t border-gray-200 p-4">{{ $users->appends(request()->query())->links() }}</div>
                @endif
            </div>
        </div>
        {{-- End users-list-container --}}
    </div>
@endsection

@push('scripts')
    <script>
        function usersIndex() {
            return {
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    window.submitUserForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("user-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    const form = document.getElementById("user-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("user-filter-form");
                    if (form) {
                        form.querySelectorAll('input[type="text"], input[type="search"], select').forEach((el) => {
                            el.value = "";
                            el.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                        });
                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("users-table-wrapper");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                                Accept: "text/html"
                            }
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("users-table-wrapper");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;
                            }

                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            if (typeof lucide !== "undefined") lucide.createIcons();
                        })
                        .catch(() => {
                            targetContainer.style.opacity = "1";
                            targetContainer.style.pointerEvents = "auto";
                        });
                },
            };
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.addEventListener("submit", function(e) {
                if (e.target && e.target.classList.contains("delete-staff-form")) {
                    e.preventDefault();

                    const form = e.target;
                    const staffName = form.getAttribute("data-name");

                    BizAlert.confirm(
                        "Remove Staff Member?",
                        `Are you sure you want to remove ${staffName}`,
                        "Yes, remove them!",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Removing...");
                            form.submit();
                        }
                    });
                }
            });
        });
    </script>
@endpush
