@extends ('layouts.admin')

@section('title', 'Create Role')
@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Create / Roles</h1>
@endsection

@section('content')
    @php
        // Process the 2-level grouped permissions for Alpine.js logic
        $jsMatrix = [];

        foreach ($permissions as $moduleGroup => $features) {
            $jsMatrix[$moduleGroup] = [
                'all_ids' => [],
                'features' => [],
            ];

            foreach ($features as $featureName => $perms) {
                // 🐛 BUG FIX: $perms is an array of objects, so we need to wrap it in collect()
                // before we can use Laravel's pluck() method.
        $featureIds = collect($perms)->pluck('id')->toArray();

        // Add feature IDs to the module's total IDs
                $jsMatrix[$moduleGroup]['all_ids'] = array_merge($jsMatrix[$moduleGroup]['all_ids'], $featureIds);

                // Store feature specific IDs, plus the lowercased text the
                // filter searches against. Built once here so nothing has to be
                // assembled while the user is typing.
                $permLabels = [];

                foreach ($perms as $perm) {
                    $action = explode('.', $perm->slug)[1] ?? $perm->name;
                    $permLabels[$perm->id] = strtolower(str_replace('_', ' ', $action));
                }

                $featureLabel = strtolower(str_replace('_', ' ', $featureName));

                $jsMatrix[$moduleGroup]['features'][$featureName] = [
                    'all_ids' => $featureIds,
                    'label' => $featureLabel,
                    'perm_labels' => $permLabels,
                    'haystack' => $featureLabel . ' ' . implode(' ', $permLabels),
                ];
            }

            $moduleLabel =
                $moduleGroup === 'core' ? 'core system features' : strtolower(str_replace('_', ' ', $moduleGroup));

            $jsMatrix[$moduleGroup]['label'] = $moduleLabel;
            $jsMatrix[$moduleGroup]['haystack'] =
                $moduleLabel . ' ' . implode(' ', array_column($jsMatrix[$moduleGroup]['features'], 'haystack'));
        }
    @endphp

    <div class="pb-10" x-data="rolePermissionsForm(@js(old('permissions', [])), @js($jsMatrix))">
        {{-- HEADER --}}
        <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-[#212538]">Create New Role</h2>
                <p class="mt-1 text-[13px] font-medium text-gray-500">Define exact access permissions based on active module
                    licenses.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.roles.index') }}"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" form="role-form"
                    class="flex items-center gap-2 rounded-lg bg-[#108c2a] px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0d7322] active:scale-95">
                    <i data-lucide="save" class="h-4 w-4"></i> Save Role
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <div class="mb-2 font-bold">Please fix the following errors:</div>
                <ul class="list-inside list-disc text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="role-form" action="{{ route('admin.roles.store') }}" method="POST"
            @submit="BizAlert.loading('Creating Role...')">
            @csrf

            {{-- Role Name Input Card --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="max-w-md">
                    <label class="mb-2 block text-[12px] font-bold tracking-wider text-gray-700 uppercase">Role Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        placeholder="e.g. Sales Manager"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-800 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                </div>
            </div>

            {{-- Permission search --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="relative max-w-lg">
                    <i data-lucide="search"
                        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="search" placeholder="Search permissions, features or modules…"
                        class="w-full rounded-lg border border-gray-300 py-2.5 pr-10 pl-10 text-sm font-medium text-gray-800 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    <button type="button" x-show="search" x-cloak @click="search = ''"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-700"
                        title="Clear search">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                {{-- Hidden checkboxes still post, so filtering never silently
                     drops a permission the user already ticked. --}}
                <p x-show="search" x-cloak class="mt-2 text-[12px] font-medium text-gray-500">
                    Filtering the list only — anything already selected stays selected.
                </p>
            </div>

            {{-- Permissions Grid --}}
            <div class="space-y-6">
                <div x-show="!hasAnyMatch()" x-cloak
                    class="rounded-xl border border-dashed border-gray-300 bg-gray-50/50 py-12 text-center">
                    <i data-lucide="search-x" class="mx-auto h-8 w-8 text-gray-300"></i>
                    <p class="mt-3 text-sm font-bold text-gray-600">No permissions found</p>
                    <p class="mt-1 text-[13px] text-gray-400">Try a different search term.</p>
                </div>
                @php
                    // Ensure 'core' is always at the top of the loop
                    $orderedPermissions = [];
                    if (isset($permissions['core'])) {
                        $orderedPermissions['core'] = $permissions['core'];
                    }
                    foreach ($permissions as $k => $v) {
                        if ($k !== 'core') {
                            $orderedPermissions[$k] = $v;
                        }
                    }
                @endphp

                @foreach ($orderedPermissions as $moduleGroup => $features)
                    {{-- 🌟 Dynamic Section Headings 🌟 --}}
                    @if ($moduleGroup === 'core')
                        <div class="pt-2 pb-1" x-show="moduleVisible('core')" x-cloak>
                            <h3 class="flex items-center gap-2 text-lg font-extrabold text-gray-900">
                                <i data-lucide="server" class="h-5 w-5 text-gray-500"></i> Base System Access
                            </h3>
                            <p class="mt-1 text-[13px] text-gray-500">Core functionalities required for general system
                                usage.</p>
                        </div>
                    @elseif ($loop->index === (isset($orderedPermissions['core']) ? 1 : 0))
                        <div class="mt-4 border-t border-gray-200 pt-6 pb-1" x-show="hasLicensedMatch()" x-cloak>
                            <h3 class="flex items-center gap-2 text-lg font-extrabold text-gray-900">
                                <i data-lucide="blocks" class="h-5 w-5 text-[#108c2a]"></i> Licensed Modules Access
                            </h3>
                            <p class="mt-1 text-[13px] text-gray-500">Permissions based on your active company
                                subscriptions.</p>
                        </div>
                    @endif

                    {{-- Module Container Card (Slightly darker for Core to stand out) --}}
                    <div x-show="moduleVisible('{{ $moduleGroup }}')" x-cloak
                        class="rounded-xl border {{ $moduleGroup === 'core' ? 'border-gray-300 bg-gray-50/30' : 'border-gray-200 bg-white' }} shadow-sm overflow-hidden">
                        {{-- Module Header --}}
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 {{ $moduleGroup === 'core' ? 'bg-gray-100/50' : 'bg-gray-50/50' }} px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-100 bg-white text-gray-500 shadow-sm">
                                    <i data-lucide="{{ $moduleGroup === 'core' ? 'cpu' : 'box' }}" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 capitalize">
                                        {{ $moduleGroup === 'core' ? 'Core System Features' : str_replace('_', ' ', $moduleGroup) }}
                                    </h3>
                                    <p class="text-[12px] font-medium text-gray-500">
                                        <span x-text="getModuleSelectedCount('{{ $moduleGroup }}')"></span>
                                        / {{ count($jsMatrix[$moduleGroup]['all_ids']) }} Permissions
                                    </p>
                                </div>
                            </div>

                            <label class="mt-4 flex cursor-pointer items-center gap-2 sm:mt-0">
                                <span class="text-[13px] font-bold text-gray-600">Select Entire Module</span>
                                <input type="checkbox" @change="toggleModule('{{ $moduleGroup }}')"
                                    :checked="isModuleSelected('{{ $moduleGroup }}')"
                                    class="h-5 w-5 cursor-pointer rounded border-gray-300 text-[#108c2a] transition-colors focus:ring-[#108c2a]" />
                            </label>
                        </div>

                        {{-- Features Grid --}}
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($features as $featureName => $perms)
                                    {{-- Feature Card --}}
                                    <div x-show="featureVisible('{{ $moduleGroup }}', '{{ $featureName }}')" x-cloak
                                        class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                        {{-- Feature Header --}}
                                        <div
                                            class="mb-4 flex items-center justify-between border-b border-gray-200/60 pb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-1.5 rounded-full bg-gray-400"></div>
                                                <h4 class="text-[14px] font-bold text-gray-800 capitalize">
                                                    {{ str_replace('_', ' ', $featureName) }}
                                                </h4>
                                            </div>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <span
                                                    class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Select
                                                    All</span>
                                                <input type="checkbox"
                                                    @change="toggleFeature('{{ $moduleGroup }}', '{{ $featureName }}')"
                                                    :checked="isFeatureSelected('{{ $moduleGroup }}', '{{ $featureName }}')"
                                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#108c2a] transition-colors focus:ring-[#108c2a]" />
                                            </label>
                                        </div>

                                        {{-- Individual Permissions --}}
                                        <div class="flex flex-col gap-3">
                                            @foreach ($perms as $permission)
                                                @php
                                                    // Extract the specific action name (e.g., 'view', 'history', 'delete' from 'ocr_scanner.view')
                                                    $actionName =
                                                        explode('.', $permission->slug)[1] ?? $permission->name;
                                                @endphp
                                                <label
                                                    x-show="permVisible('{{ $moduleGroup }}', '{{ $featureName }}', {{ $permission->id }})"
                                                    x-cloak class="group flex cursor-pointer items-center gap-3">
                                                    <input type="checkbox" name="permissions[]"
                                                        value="{{ $permission->id }}" x-model.number="selected"
                                                        class="h-4.5 w-4.5 cursor-pointer rounded border-gray-300 text-[#108c2a] transition-colors focus:ring-[#108c2a]" />
                                                    <span
                                                        class="text-[13px] font-medium text-gray-600 capitalize transition-colors group-hover:text-gray-900">
                                                        {{ str_replace('_', ' ', $actionName) }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer actions — mirrors the header pair so the buttons are in
                 reach after scrolling a long permission list. --}}
            <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6">

                <a href="{{ route('admin.roles.index') }}"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                    class="flex items-center gap-2 rounded-lg bg-[#108c2a] px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0d7322] active:scale-95">
                    <i data-lucide="save" class="h-4 w-4"></i> Save Role
                </button>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
    <script>
        function rolePermissionsForm(oldSelected = [], matrixData = {}) {
            return {
                selected: oldSelected.map(Number),
                matrix: matrixData,
                search: "",

                // ── Search ──
                // Matching cascades downward: a module name hit reveals all its
                // features, a feature name hit reveals all its permissions. Without
                // that, searching "invoicing" would show an empty module card.
                query() {
                    return this.search.trim().toLowerCase();
                },

                moduleVisible(moduleGroup) {
                    const q = this.query();
                    if (!q) return true;
                    return (this.matrix[moduleGroup].haystack || "").includes(q);
                },

                featureVisible(moduleGroup, featureName) {
                    const q = this.query();
                    if (!q) return true;
                    if ((this.matrix[moduleGroup].label || "").includes(q)) return true;
                    return (this.matrix[moduleGroup].features[featureName].haystack || "").includes(q);
                },

                permVisible(moduleGroup, featureName, permissionId) {
                    const q = this.query();
                    if (!q) return true;

                    const feature = this.matrix[moduleGroup].features[featureName];

                    if ((this.matrix[moduleGroup].label || "").includes(q)) return true;
                    if ((feature.label || "").includes(q)) return true;

                    return (feature.perm_labels[permissionId] || "").includes(q);
                },

                hasAnyMatch() {
                    if (!this.query()) return true;
                    return Object.keys(this.matrix).some((group) => this.moduleVisible(group));
                },

                hasLicensedMatch() {
                    if (!this.query()) return true;
                    return Object.keys(this.matrix).some(
                        (group) => group !== "core" && this.moduleVisible(group)
                    );
                },

                // Check if all permissions in a specific feature (sub-card) are selected
                isFeatureSelected(moduleGroup, featureName) {
                    let ids = this.matrix[moduleGroup].features[featureName].all_ids;
                    return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                },

                // Toggle all permissions in a specific feature
                toggleFeature(moduleGroup, featureName) {
                    let ids = this.matrix[moduleGroup].features[featureName].all_ids;
                    if (this.isFeatureSelected(moduleGroup, featureName)) {
                        // Deselect all
                        this.selected = this.selected.filter((id) => !ids.includes(id));
                    } else {
                        // Select all
                        ids.forEach((id) => {
                            if (!this.selected.includes(id)) this.selected.push(id);
                        });
                    }
                },

                // Check if all permissions in a full module (main card) are selected
                isModuleSelected(moduleGroup) {
                    let ids = this.matrix[moduleGroup].all_ids;
                    return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                },

                // Toggle all permissions in a full module
                toggleModule(moduleGroup) {
                    let ids = this.matrix[moduleGroup].all_ids;
                    if (this.isModuleSelected(moduleGroup)) {
                        // Deselect all
                        this.selected = this.selected.filter((id) => !ids.includes(id));
                    } else {
                        // Select all
                        ids.forEach((id) => {
                            if (!this.selected.includes(id)) this.selected.push(id);
                        });
                    }
                },

                // Get count of selected permissions within a specific module
                getModuleSelectedCount(moduleGroup) {
                    let ids = this.matrix[moduleGroup].all_ids;
                    return ids.filter((id) => this.selected.includes(id)).length;
                },
            };
        }
    </script>
@endpush
