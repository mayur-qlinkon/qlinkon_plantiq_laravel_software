@extends ('layouts.admin')

@section ('title', 'Access Control & Roles')

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Roles & Permissions</h1>
@endsection

@section ('content')
    <div class="w-full space-y-6 pb-10" x-data="roleIndex()">
        {{-- Toast Notifications --}}
        @if (session('success'))
            <script>
                document.addEventListener("DOMContentLoaded", () => BizAlert.toast("{{ session('success') }}", "success"));
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener("DOMContentLoaded", () => BizAlert.toast("{{ session('error') }}", "error"));
            </script>
        @endif

        {{-- ── Header & Search Bar ── --}}
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-[28px] font-extrabold tracking-tight text-[#1f2937]">Access Control</h2>
                <p class="mt-1 text-[14px] font-medium text-gray-500">Manage user groups and define exactly what they can access.</p>
            </div>
            <div class="flex w-full items-center gap-3 sm:w-auto">
                <div class="relative w-full shrink-0 sm:w-64">
                    <i
                        data-lucide="search"
                        class="absolute top-1/2 left-3.5 h-4.5 w-4.5 -translate-y-1/2 text-gray-400"
                    ></i>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search roles..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                    />
                </div>
                <a
                    href="{{ route('admin.roles.create') }}"
                    class="flex shrink-0 items-center gap-2 rounded-lg bg-[#108c2a] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0d7322] active:scale-95"
                >
                    <i data-lucide="shield-plus" class="h-4 w-4"></i> New Role
                </a>
            </div>
        </div>

        {{-- ── Roles Grid ── --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
            @forelse ($roles as $role)
                <div
                    class="flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:border-[#108c2a]/30"
                    x-show="matchesSearch('{{ strtolower($role->name) }}')"
                    x-transition
                >
                    {{-- Card Header --}}
                    <div class="p-6 pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-green-100 bg-[#e8f5ec] text-[#108c2a]"
                                >
                                    <i data-lucide="shield-check" class="h-6 w-6"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl leading-tight font-extrabold text-gray-900">
                                        {{ $role->name }}
                                    </h3>
                                    <span
                                        class="mt-1 block text-[11px] font-bold tracking-widest text-gray-400 uppercase"
                                    >
                                        System ID: {{ $role->slug }}
                                    </span>
                                </div>
                            </div>

                            {{-- Permissions Count Badge --}}
                            <div
                                class="flex shrink-0 flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5"
                            >
                                <span class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Access</span>
                                <span
                                    class="text-sm font-extrabold text-gray-800"
                                    >{{ $role->permissions->count() }}</span
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Clean Action Footer --}}
                    <div
                        class="mt-auto flex items-center justify-between rounded-b-2xl border-t border-gray-100 bg-gray-50/50 px-6 py-3.5"
                    >
                        <span class="text-xs font-bold tracking-wider text-gray-400 uppercase">Role Actions</span>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('admin.roles.edit', $role->id) }}"
                                title="Edit Role"
                                class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-600 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50"
                            >
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                            </a>
                            <form
                                action="{{ route('admin.roles.destroy', $role->id) }}"
                                method="POST"
                                @submit.prevent="confirmDelete($event.target)"
                            >
                                @csrf
                                @method ('DELETE')
                                <button
                                    type="submit"
                                    title="Delete Role"
                                    class="flex items-center justify-center rounded-lg border border-gray-200 bg-white p-1.5 text-red-500 shadow-sm transition-colors hover:border-red-200 hover:bg-red-50"
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Accordion Content (Grouped Permissions) --}}
                    <div
                        x-show="showPerms"
                        x-transition.opacity.duration.300ms
                        style="display: none"
                        class="module-scroll max-h-[300px] overflow-y-auto rounded-b-2xl border-t border-gray-100 bg-white p-6"
                    >
                        @php
                            // Group permissions by module_group in Blade
                            $groupedPerms = $role->permissions->groupBy('module_group');
                        @endphp

                        @if ($groupedPerms->isEmpty())
                            <div class="py-4 text-center">
                                <i data-lucide="shield-off" class="mx-auto mb-2 h-8 w-8 text-gray-300"></i>
                                <p class="text-sm font-bold text-gray-500">No specific access granted.</p>
                            </div>
                        @else
                            <div class="space-y-5">
                                @foreach ($groupedPerms as $module => $perms)
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                        <h4
                                            class="mb-3 flex items-center gap-2 text-[12px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            <div class="h-1.5 w-1.5 rounded-full bg-[#108c2a]"></div>
                                            {{ $module === 'core' ? 'Core System Features' : str_replace('_', ' ', $module ?: 'General') }}
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($perms as $p)
                                                @php 
                                                    // Extract action part (e.g., 'create' from 'users.create')
                                                    $action = explode('.', $p->slug)[1] ?? $p->name; 
                                                @endphp
                                                <span
                                                    class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-700 capitalize shadow-sm"
                                                >
                                                    {{ str_replace('_', ' ', $action) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white py-20 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-50">
                        <i data-lucide="shield-alert" class="h-8 w-8 text-gray-400"></i>
                    </div>
                    <h3 class="mb-1 text-lg font-extrabold text-gray-800">No Roles Configured</h3>
                    <p class="text-sm font-medium text-gray-500">Create your first role to start managing access.</p>
                    <a
                        href="{{ route('admin.roles.create') }}"
                        class="mt-4 inline-flex items-center gap-2 font-bold text-[#108c2a] hover:underline"
                    >
                        Create Role <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Custom Styling for Accordion Scrollbar --}}
    <style>
        .module-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .module-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .module-scroll::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 8px;
        }
        .module-scroll::-webkit-scrollbar-thumb:hover {
            background: #d1d5db;
        }
    </style>
@endsection

@push ('scripts')
    <script>
        function roleIndex() {
            return {
                search: "",
                matchesSearch(name) {
                    return this.search === "" || name.includes(this.search.toLowerCase());
                },
                confirmDelete(form) {
                    BizAlert.confirm(
                        "Delete Role?",
                        "Are you sure? This will permanently remove access for all users currently assigned to this role.",
                        "Yes, Delete It!",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Removing Role...");
                            form.submit();
                        }
                    });
                },
            };
        }
    </script>
@endpush
