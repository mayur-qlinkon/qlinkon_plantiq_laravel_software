@extends ('layouts.admin')

@section ('title', 'Production Plans')

@section ('header-title')
    <h1 class="text-xs font-bold tracking-widest text-gray-400 uppercase sm:text-sm">Production Plans</h1>
@endsection

@push ('styles')
    <style>
        /* Ensure the modal body allows dropdowns to spill out visually without clipping */
        .modal-body-scroll {
            overflow-y: auto;
            overflow-x: visible;
        }
        .table-container-visible {
            overflow: visible !important;
        }
    </style>
@endpush

@section ('content')
    @php
        $purposes = \App\Models\Production\ProductionPlan::PURPOSE_LABELS;
        $statuses = \App\Models\Production\ProductionPlan::STATUS_LABELS;
        // Extracts the array of items from Laravel's Paginator
        $initialPlans = $plans->items() ?? [];
    @endphp

    <div class="space-y-4 pb-10 sm:space-y-6" x-data="productionPlanManager()">
        {{-- 🌟 RESPONSIVE HEADER --}}
        <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div class="w-full lg:w-auto">
                <p class="text-xs font-medium text-gray-500 sm:text-sm">Manage planting schedules and production intent.</p>
            </div>

            <div class="flex w-full flex-col items-center gap-3 sm:flex-row lg:w-auto">
                <div class="relative w-full shrink-0 sm:w-64">
                    <i data-lucide="search" class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search plans by title..."
                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-3 pl-9 text-sm text-gray-700 shadow-sm transition-all outline-none placeholder:text-gray-400 focus:ring-2"
                    />
                </div>

                <button
                    type="button"
                    @click="openModal('create')"
                    class="bg-brand-600 hover:bg-brand-700 flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-md transition-all active:scale-95 sm:w-auto"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i> Create Plan
                </button>
            </div>
        </div>

        {{-- 🌟 DESKTOP TABLE CARD (Hidden on mobile < md) --}}
        <div class="hidden flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm md:flex">
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-100 bg-[#f8fafc] text-[10px] font-bold tracking-wider text-gray-400 uppercase sm:text-[11px]"
                    >
                        <tr>
                            <th class="px-4 py-3 text-center sm:px-6 sm:py-4">NO.</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">PLAN DETAILS</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">STATUS</th>
                            <th class="px-4 py-3 sm:px-6 sm:py-4">REQUESTED PLANTS</th>
                            <th class="hidden px-4 py-3 sm:px-6 sm:py-4 md:table-cell">CREATED BY</th>
                            <th class="hidden px-4 py-3 text-right sm:px-6 sm:py-4 md:table-cell">TARGET DATE</th>
                            <th class="px-4 py-3 text-right sm:px-6 sm:py-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="(plan, planIndex) in filteredPlans" :key="plan.id">
                            <tr class="transition-colors hover:bg-gray-50/50">
                                <td
                                    class="px-4 py-3 text-center text-xs font-bold text-gray-400 sm:px-6 sm:py-4"
                                    x-text="planIndex + 1"
                                ></td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900" x-text="plan.title"></span>
                                        <span
                                            class="mt-0.5 text-[11px] font-medium text-gray-500"
                                            x-text="purposes[plan.purpose] || '—'"
                                        ></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[10px] font-black tracking-wider uppercase"
                                        :class="statusClasses(plan.status)"
                                    >
                                        <i :data-lucide="statusIcon(plan.status)" class="h-3 w-3"></i>
                                        <span x-text="plan.status"></span>
                                    </span>
                                </td>

                                <td class="px-4 py-3 sm:px-6 sm:py-4">
                                    <template x-if="!(plan.items && plan.items.length)">
                                        <span class="text-xs font-medium text-gray-400">—</span>
                                    </template>
                                    <template x-if="plan.items && plan.items.length === 1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                            >
                                                <i data-lucide="sprout" class="h-3.5 w-3.5"></i>
                                                <span x-text="plan.items[0].product?.name || 'Unknown'"></span>
                                            </span>
                                            <span
                                                class="text-[11px] font-medium text-gray-400"
                                                x-text="'Qty: ' + plan.items[0].target_quantity"
                                            ></span>
                                        </div>
                                    </template>
                                    <template x-if="plan.items && plan.items.length > 1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                            >
                                                <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                                                <span x-text="plan.items.length + ' Plants'"></span>
                                            </span>
                                            <button
                                                type="button"
                                                @click="openViewModal(plan)"
                                                class="text-brand-600 text-[11px] font-bold hover:underline"
                                            >
                                                View list
                                            </button>
                                        </div>
                                    </template>
                                </td>

                                <td
                                    class="hidden px-4 py-3 text-xs font-medium text-gray-600 sm:px-6 sm:py-4 md:table-cell"
                                    x-text="plan.created_by?.name || 'System'"
                                ></td>

                                <td class="hidden px-4 py-3 text-right sm:px-6 sm:py-4 md:table-cell">
                                    <span
                                        class="text-xs font-medium text-gray-600"
                                        x-text="formatDate(earliestTargetDate(plan))"
                                    ></span>
                                </td>

                                <td class="px-4 py-3 text-right sm:px-6 sm:py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openViewModal(plan)"
                                            class="hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition-all hover:bg-gray-50"
                                            title="View"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        <template x-if="plan.status === 'draft'">
                                            <button
                                                @click="openModal('edit', plan)"
                                                class="hover:text-brand-600 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition-all hover:bg-gray-50"
                                                title="Edit"
                                            >
                                                <i data-lucide="edit-2" class="h-4 w-4"></i>
                                            </button>
                                        </template>

                                        <template x-if="plan.status === 'draft' || plan.status === 'confirmed'">
                                            <button
                                                @click="cancelPlan(plan)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition-all hover:bg-red-100"
                                                title="Cancel Plan"
                                            >
                                                <i data-lucide="ban" class="h-4 w-4"></i>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-if="filteredPlans.length === 0">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i data-lucide="folder-open" class="mb-2 h-10 w-10 opacity-20"></i>
                                        <p class="font-medium">No plans found.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Standard Laravel Pagination links fallback --}}
            @if ($plans->hasPages())
                <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">{{ $plans->links() }}</div>
            @endif
        </div>

        {{-- 🌟 MOBILE CARDS VIEW (Visible only on mobile < md) --}}
        <div class="space-y-3 md:hidden">
            <template x-for="plan in filteredPlans" :key="plan.id">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition-all">
                    {{-- Header: Plan Title & Status Badge --}}
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-base font-bold text-gray-900" x-text="plan.title"></h3>
                            <p
                                class="mt-0.5 text-xs font-medium text-gray-500"
                                x-text="purposes[plan.purpose] || '—'"
                            ></p>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2.5 py-1 text-[10px] font-black tracking-wider uppercase"
                            :class="statusClasses(plan.status)"
                        >
                            <i :data-lucide="statusIcon(plan.status)" class="h-3 w-3"></i>
                            <span x-text="plan.status"></span>
                        </span>
                    </div>

                    {{-- Card Details Grid --}}
                    <div class="grid grid-cols-2 gap-3 py-3 text-xs">
                        <div class="col-span-2">
                            <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Requested Plants</span
                            >
                            <template x-if="!(plan.items && plan.items.length)">
                                <span class="mt-0.5 inline-block font-bold text-gray-400">—</span>
                            </template>
                            <template x-if="plan.items && plan.items.length === 1">
                                <div class="mt-0.5 flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                    >
                                        <i data-lucide="sprout" class="h-3.5 w-3.5"></i>
                                        <span x-text="plan.items[0].product?.name || 'Unknown'"></span>
                                    </span>
                                    <span
                                        class="text-[11px] font-medium text-gray-400"
                                        x-text="'Qty: ' + plan.items[0].target_quantity"
                                    ></span>
                                </div>
                            </template>
                            <template x-if="plan.items && plan.items.length > 1">
                                <div class="mt-0.5 flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                                    >
                                        <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                                        <span x-text="plan.items.length + ' Plants'"></span>
                                    </span>
                                    <button
                                        type="button"
                                        @click="openViewModal(plan)"
                                        class="text-brand-600 text-[11px] font-bold hover:underline"
                                    >
                                        View list
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Created By</span
                            >
                            <span
                                class="mt-0.5 block truncate font-bold text-gray-700"
                                x-text="plan.created_by?.name || 'System'"
                            ></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Target Date</span
                            >
                            <span
                                class="mt-0.5 block font-bold text-gray-700"
                                x-text="formatDate(earliestTargetDate(plan))"
                            ></span>
                        </div>
                    </div>

                    {{-- Action Buttons Footer --}}
                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                        <button
                            type="button"
                            @click="openViewModal(plan)"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 transition-all hover:bg-gray-50"
                        >
                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                            <span>View</span>
                        </button>

                        <template x-if="plan.status === 'draft'">
                            <button
                                type="button"
                                @click="openModal('edit', plan)"
                                class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-600 transition-all hover:bg-gray-50"
                            >
                                <i data-lucide="edit-2" class="h-3.5 w-3.5"></i> Edit
                            </button>
                        </template>

                        <template x-if="plan.status === 'draft' || plan.status === 'confirmed'">
                            <button
                                type="button"
                                @click="cancelPlan(plan)"
                                class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition-all hover:bg-red-100"
                            >
                                <i data-lucide="ban" class="h-3.5 w-3.5"></i> Cancel
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="filteredPlans.length === 0">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-xs font-medium text-gray-400"
                >
                    <i data-lucide="folder-open" class="mx-auto mb-2 h-8 w-8 opacity-20"></i>
                    <p class="font-medium">No plans found.</p>
                </div>
            </template>
        </div>

        {{-- ============================================================ --}}
        {{-- 🌟 CREATE / EDIT / VIEW MODAL --}}
        {{-- ============================================================ --}}
        <template x-teleport="body">
            <div
                x-show="modalOpen"
                x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center px-4"
                @keydown.escape.window="closeModal()"
            >
                <div
                    x-show="modalOpen"
                    x-transition.opacity
                    class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                    @click="closeModal()"
                ></div>

                <div
                    x-show="modalOpen"
                    x-transition.scale.95
                    class="relative flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    {{-- Header --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-6">
                        <h3 class="flex items-center gap-2 text-base font-black text-gray-800">
                            <i
                                :data-lucide="modalMode === 'view'
                                    ? 'eye'
                                    : modalMode === 'edit'
                                      ? 'edit-2'
                                      : 'clipboard-list'"
                                class="text-brand-600 h-5 w-5"
                            ></i>
                            <span
                                x-text="
                                    modalMode === 'create'
                                        ? 'Create Production Plan'
                                        : modalMode === 'edit'
                                          ? 'Edit Production Plan'
                                          : 'View Production Plan'
                                "
                            ></span>
                            <template x-if="activePlan">
                                <span
                                    class="ml-2 rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-500 uppercase"
                                    x-text="activePlan.status"
                                ></span>
                            </template>
                        </h3>
                        <button
                            type="button"
                            @click="closeModal()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        >
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>

                    {{-- Body --}}
                    {{-- FIX: Added pb-48 to ensure absolute dropdowns don't get clipped at the bottom of the modal --}}
                    <div class="modal-body-scroll flex-1 px-4 pt-5 pb-48 sm:px-6">
                        <div class="space-y-6">
                            {{-- PLAN MASTER DATA --}}
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold text-gray-600"
                                        >Plan Title <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="text"
                                        x-model="form.title"
                                        :disabled="!isEditable"
                                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full rounded-xl border px-3 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                        :class="errors.title ? 'border-red-300' : 'border-gray-200'"
                                        placeholder="e.g. Spring Seedlings 2026"
                                    />
                                    <p
                                        x-show="errors.title"
                                        x-text="errors.title"
                                        class="mt-1 text-[11px] font-medium text-red-500"
                                    ></p>
                                </div>
                                <div
                                    @change="form.purpose = $event.target.value"
                                    :class="!isEditable ? 'pointer-events-none opacity-60' : ''"
                                >
                                    <label class="mb-1.5 block text-xs font-bold text-gray-600">Purpose</label>
                                    <x-custom-select
                                        name="purpose"
                                        placeholder="Select Purpose..."
                                        :options="$purposes"
                                    />
                                    <p
                                        x-show="errors.purpose"
                                        x-text="errors.purpose"
                                        class="mt-1 text-[11px] font-medium text-red-500"
                                    ></p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-xs font-bold text-gray-600"
                                        >Notes
                                        <span class="font-medium text-gray-400 normal-case">(optional)</span></label
                                    >
                                    <textarea
                                        x-model="form.notes"
                                        :disabled="!isEditable"
                                        rows="2"
                                        class="focus:border-brand-500 focus:ring-brand-500/20 w-full resize-none rounded-xl border px-3 py-2.5 text-sm transition-all outline-none focus:ring-2"
                                        :class="errors.notes ? 'border-red-300' : 'border-gray-200'"
                                        placeholder="Any general instructions..."
                                    ></textarea>
                                </div>
                            </div>

                            <hr class="border-gray-100" />

                            {{-- PLAN ITEMS DYNAMIC TABLE --}}
                            <div>
                                <div class="mb-3 flex items-center justify-between">
                                    <h4 class="text-xs font-bold tracking-widest text-gray-500 uppercase">
                                        Plan Items (Products)
                                    </h4>
                                    <template x-if="isEditable">
                                        <button
                                            type="button"
                                            @click="addItem()"
                                            class="flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-200"
                                        >
                                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Item
                                        </button>
                                    </template>
                                </div>

                                {{-- Card-based item list — scalable, no column-width constraints,
                                     each item is a self-contained block so new fields can be
                                     added later without redesigning a table. --}}
                                <div class="space-y-3">
                                    <template x-for="(item, index) in form.items" :key="item._uid">
                                        <div
                                            class="rounded-xl border border-gray-200 bg-gray-50 p-3 shadow-sm md:bg-white"
                                            :class="item._dropdownOpen ? 'relative z-50' : 'relative z-10'"
                                        >
                                            {{-- Row 1: Product / Qty / Date / Remove --}}
                                            <div
                                                class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_130px_150px_36px] md:items-start"
                                            >
                                                {{-- PRODUCT SEARCH COMPONENT --}}
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                                        >Product / SKU <span class="text-red-500">*</span></label
                                                    >
                                                    <template x-if="!item.product_id && isEditable">
                                                        <div class="relative w-full">
                                                            <i
                                                                data-lucide="search"
                                                                class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                                                            ></i>
                                                            <input
                                                                type="text"
                                                                x-model="item._searchQuery"
                                                                @input.debounce.250ms="searchProducts(index)"
                                                                @focus="openPlantDropdown(index)"
                                                                @click.stop="openPlantDropdown(index)"
                                                                placeholder="Click to see recent, or type to search…"
                                                                class="focus:border-brand-500 w-full rounded-lg border px-2.5 py-2 pl-8 text-xs outline-none focus:ring-1"
                                                                :class="errors['items.' + index + '.product_id']
                                                                    ? 'border-red-300'
                                                                    : 'border-gray-200'"
                                                            />

                                                            <div
                                                                x-show="item._dropdownOpen"
                                                                x-cloak
                                                                @click.outside="item._dropdownOpen = false"
                                                                class="absolute right-0 left-0 z-[100] mt-1 max-h-56 min-h-[48px] overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-xl"
                                                            >
                                                                <div
                                                                    x-show="
                                                                        item._searching &&
                                                                        item._searchResults.length === 0
                                                                    "
                                                                    class="flex items-center justify-center p-3 text-xs text-gray-400"
                                                                >
                                                                    <i
                                                                        data-lucide="loader-2"
                                                                        class="mr-2 h-4 w-4 animate-spin"
                                                                    ></i>
                                                                    <span>Loading plants...</span>
                                                                </div>

                                                                <div
                                                                    x-show="
                                                                        !item._searching ||
                                                                        item._searchResults.length > 0
                                                                    "
                                                                >
                                                                    <template
                                                                        x-for="res in item._searchResults"
                                                                        :key="res.id"
                                                                    >
                                                                        <button
                                                                            type="button"
                                                                            @click="pickProduct(index, res)"
                                                                            class="hover:bg-brand-50 flex w-full items-center justify-between gap-2 border-b border-gray-50 px-3 py-2.5 text-left text-xs transition-colors last:border-0"
                                                                        >
                                                                            <span
                                                                                class="block truncate font-bold text-gray-800"
                                                                                x-text="res.label"
                                                                            ></span>
                                                                            <span
                                                                                class="shrink-0 text-[10px] font-medium text-gray-400"
                                                                                x-text="res.sublabel"
                                                                            ></span>
                                                                        </button>
                                                                    </template>

                                                                    <div
                                                                        x-show="
                                                                            !item._searching &&
                                                                            item._searchResults.length === 0
                                                                        "
                                                                        class="p-3 text-center text-[11px] text-gray-400"
                                                                    >
                                                                        No plants found.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Selected Product Chip --}}
                                                    <template x-if="item.product_id || !isEditable">
                                                        <div
                                                            class="border-brand-200 bg-brand-50/50 flex items-center justify-between rounded-lg border px-3 py-2"
                                                        >
                                                            <div class="min-w-0 flex-1">
                                                                <span
                                                                    class="block truncate text-xs font-bold text-gray-800"
                                                                    x-text="item._product_name"
                                                                ></span>
                                                            </div>
                                                            <template x-if="isEditable">
                                                                <button
                                                                    type="button"
                                                                    @click="clearProduct(index)"
                                                                    class="ml-2 text-gray-400 transition-colors hover:text-red-500"
                                                                >
                                                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <p
                                                        x-show="errors['items.' + index + '.product_id']"
                                                        x-text="errors['items.' + index + '.product_id']"
                                                        class="mt-1 text-[10px] font-medium text-red-500"
                                                    ></p>
                                                </div>

                                                {{-- QTY --}}
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                                        >Target Qty <span class="text-red-500">*</span></label
                                                    >
                                                    <input
                                                        type="number"
                                                        x-model.number="item.target_quantity"
                                                        :disabled="!isEditable"
                                                        min="1"
                                                        class="focus:border-brand-500 w-full rounded-lg border px-2.5 py-2 text-xs outline-none focus:ring-1"
                                                        :class="errors['items.' + index + '.target_quantity']
                                                            ? 'border-red-300'
                                                            : 'border-gray-200'"
                                                        placeholder="Qty"
                                                    />
                                                    <p
                                                        x-show="errors['items.' + index + '.target_quantity']"
                                                        x-text="errors['items.' + index + '.target_quantity']"
                                                        class="mt-1 text-[10px] font-medium text-red-500"
                                                    ></p>
                                                </div>

                                                {{-- DATE --}}
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                                        >Target Date <span class="text-red-500">*</span></label
                                                    >
                                                    <input
                                                        type="date"
                                                        x-model="item.target_date"
                                                        :disabled="!isEditable"
                                                        class="focus:border-brand-500 w-full rounded-lg border px-2.5 py-2 text-xs outline-none focus:ring-1"
                                                        :class="errors['items.' + index + '.target_date']
                                                            ? 'border-red-300'
                                                            : 'border-gray-200'"
                                                    />
                                                    <p
                                                        x-show="errors['items.' + index + '.target_date']"
                                                        x-text="errors['items.' + index + '.target_date']"
                                                        class="mt-1 text-[10px] font-medium text-red-500"
                                                    ></p>
                                                </div>

                                                {{-- REMOVE --}}
                                                <div class="flex justify-end md:justify-center md:pt-6">
                                                    <template x-if="isEditable">
                                                        <button
                                                            type="button"
                                                            @click="removeItem(index)"
                                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                            title="Remove item"
                                                        >
                                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Row 2: Remarks — full-width textarea, own line --}}
                                            <div class="mt-3 border-t border-gray-100 pt-3">
                                                <label
                                                    class="mb-1 block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                                    >Remarks</label
                                                >
                                                <textarea
                                                    x-model="item.remarks"
                                                    :disabled="!isEditable"
                                                    rows="2"
                                                    placeholder="Any notes for this plant…"
                                                    class="focus:border-brand-500 w-full resize-none rounded-lg border border-gray-200 px-2.5 py-2 text-xs outline-none focus:ring-1"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="form.items.length === 0">
                                        <div
                                            class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-xs font-medium text-gray-400"
                                        >
                                            No items added to this plan yet. Click "Add Item".
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-4 py-4 sm:px-6"
                    >
                        <button
                            type="button"
                            @click="closeModal()"
                            :disabled="submitting"
                            class="rounded-xl px-5 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-200 disabled:opacity-50"
                        >
                            <span x-text="isEditable ? 'Cancel' : 'Close'"></span>
                        </button>

                        <template x-if="isEditable">
                            <button
                                type="button"
                                @click="submitForm()"
                                :disabled="submitting"
                                class="bg-brand-600 hover:bg-brand-700 flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:active:scale-100"
                            >
                                <i x-show="submitting" data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                                <i x-show="!submitting" data-lucide="save" class="h-4 w-4"></i>
                                <span x-text="submitting ? 'Saving...' : 'Save Plan'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- ============================================================ --}}
        {{-- 🌟 VIEW MODAL (read-only, + Confirm / Mark closed actions) --}}
        {{-- ============================================================ --}}
        <template x-teleport="body">
            <div
                x-show="viewModalOpen"
                x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center px-4"
                @keydown.escape.window="closeViewModal()"
            >
                <div
                    x-show="viewModalOpen"
                    x-transition.opacity
                    class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                    @click="closeViewModal()"
                ></div>

                <div
                    x-show="viewModalOpen"
                    x-transition.scale.95
                    class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    <template x-if="viewPlan">
                        <div class="flex flex-1 flex-col overflow-hidden">
                            {{-- Header --}}
                            <div class="shrink-0 border-b border-gray-100 px-6 py-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-black text-gray-900" x-text="viewPlan.title"></h3>
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-black tracking-wider uppercase"
                                            :class="statusClasses(viewPlan.status)"
                                        >
                                            <i :data-lucide="statusIcon(viewPlan.status)" class="h-3 w-3"></i>
                                            <span x-text="viewPlan.status"></span>
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="closeViewModal()"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                    >
                                        <i data-lucide="x" class="h-5 w-5"></i>
                                    </button>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-4 text-xs font-medium text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="tag" class="h-3.5 w-3.5"></i>
                                        <span x-text="purposes[viewPlan.purpose] || '—'"></span>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                        <span x-text="formatDate(earliestTargetDate(viewPlan))"></span>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="user" class="h-3.5 w-3.5"></i>
                                        <span x-text="viewPlan.created_by?.name || 'System'"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Body --}}
                            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                                <div class="rounded-xl bg-gray-50 px-4 py-3">
                                    <span class="block text-[10px] font-bold tracking-widest text-gray-400 uppercase"
                                        >Global Notes</span
                                    >
                                    <p
                                        class="mt-1 text-sm font-medium text-gray-700"
                                        x-text="viewPlan.notes || 'No general notes provided for this plan.'"
                                    ></p>
                                </div>

                                <div>
                                    <h4 class="mb-3 flex items-center gap-1.5 text-sm font-bold text-gray-800">
                                        <i data-lucide="sprout" class="text-brand-600 h-4 w-4"></i>
                                        Requested Plants
                                    </h4>
                                    <div class="space-y-3">
                                        <template x-for="item in (viewPlan.items || [])" :key="item.id || item._uid">
                                            <div class="rounded-xl border border-gray-100 p-4">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <span
                                                            class="block text-sm font-bold text-gray-900"
                                                            x-text="item.product?.name || 'Unknown Plant'"
                                                        ></span>
                                                        <span
                                                            class="mt-1 flex items-center gap-1.5 text-xs font-medium text-gray-500"
                                                        >
                                                            <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                                            Target:
                                                            <span
                                                                x-text="
                                                                    item.target_date
                                                                        ? item.target_date.split('T')[0]
                                                                        : '—'
                                                                "
                                                            ></span>
                                                        </span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span
                                                            class="block text-xl font-black text-gray-900"
                                                            x-text="item.target_quantity"
                                                        ></span>
                                                        <span
                                                            class="text-[10px] font-bold tracking-widest text-gray-400 uppercase"
                                                            >Quantity</span
                                                        >
                                                    </div>
                                                </div>
                                                <p
                                                    class="mt-2 text-xs font-medium text-gray-500"
                                                    x-text="item.remarks || 'No specific remarks.'"
                                                ></p>
                                            </div>
                                        </template>

                                        <template x-if="!(viewPlan.items && viewPlan.items.length)">
                                            <p class="py-4 text-center text-xs font-medium text-gray-400">No plants added to this plan.</p>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div
                                class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4"
                            >
                                <button
                                    type="button"
                                    @click="closeViewModal()"
                                    :disabled="viewActionSubmitting"
                                    class="rounded-xl px-5 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-200 disabled:opacity-50"
                                >
                                    Close
                                </button>

                                <template x-if="viewPlan.status === 'draft'">
                                    <button
                                        type="button"
                                        @click="confirmPlan(viewPlan)"
                                        :disabled="viewActionSubmitting"
                                        class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-emerald-700 active:scale-95 disabled:opacity-60"
                                    >
                                        <i data-lucide="check" class="h-4 w-4"></i>
                                        Confirm Plan
                                    </button>
                                </template>

                                <template x-if="viewPlan.status === 'confirmed'">
                                    <button
                                        type="button"
                                        @click="closePlan(viewPlan)"
                                        :disabled="viewActionSubmitting"
                                        class="bg-brand-600 hover:bg-brand-700 flex items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 disabled:opacity-60"
                                    >
                                        <i data-lucide="check-check" class="h-4 w-4"></i>
                                        Mark Closed
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <script>
        function productionPlanManager() {
            return {
                plans: @json ($initialPlans),
                purposes: @json ($purposes),
                search: "",

                modalOpen: false,
                modalMode: "create", // 'create', 'edit'
                activePlan: null,
                submitting: false,
                errors: {},

                viewModalOpen: false,
                viewPlan: null,
                viewActionSubmitting: false,

                form: {
                    title: "",
                    purpose: "",
                    notes: "",
                    items: [],
                },

                init() {
                    // Initialize Lucide icons on page load
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                },

                updateIcons() {
                    // Utility to refresh Lucide icons after Alpine updates DOM
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                get filteredPlans() {
                    if (this.search.trim() === "") return this.plans;
                    const query = this.search.toLowerCase();
                    return this.plans.filter((p) => p.title.toLowerCase().includes(query));
                },

                get isEditable() {
                    return this.modalMode === "create" || (this.modalMode === "edit" && this.activePlan?.status === "draft");
                },

                statusClasses(status) {
                    const map = {
                        draft: "bg-gray-100 text-gray-700",
                        confirmed: "bg-emerald-100 text-emerald-700",
                        closed: "bg-blue-100 text-blue-700",
                        cancelled: "bg-red-100 text-red-700",
                    };
                    return map[status] || "bg-gray-100 text-gray-700";
                },

                statusIcon(status) {
                    const map = {
                        draft: "file-text",
                        confirmed: "check-circle",
                        closed: "check-check",
                        cancelled: "x-circle",
                    };
                    return map[status] || "file";
                },

                formatDate(dateString) {
                    if (!dateString) return "—";
                    const d = new Date(dateString);
                    return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
                },

                // --- MODAL HANDLING ---

                async openModal(mode, plan = null) {
                    this.modalMode = mode;
                    this.activePlan = plan;
                    this.errors = {};
                    this.form = { title: "", purpose: "", notes: "", items: [] };

                    if (plan) {
                        this.form.title = plan.title;
                        this.form.purpose = plan.purpose || "";
                        this.form.notes = plan.notes || "";

                        // Populate items if they exist
                        if (plan.items) {
                            this.form.items = plan.items.map((item) => ({
                                _uid: Math.random().toString(36).substr(2, 9),
                                id: item.id,
                                product_id: item.product_id,
                                _product_name: item.product?.name || "Unknown Product",
                                _searchQuery: "",
                                _searchResults: [],
                                _dropdownOpen: false,
                                _searching: false,
                                _searchToken: 0,
                                target_quantity: item.target_quantity,
                                target_date: item.target_date ? item.target_date.split("T")[0] : "",
                                remarks: item.remarks || "",
                            }));
                        }
                    } else {
                        // Create mode - auto add 1 blank row
                        this.addItem();
                    }

                    // Push values down into the DOM for the custom selects
                    this.$nextTick(() => {
                        const purposeSelect = document.querySelector('select[name="purpose"]');
                        if (purposeSelect) {
                            purposeSelect.value = this.form.purpose;
                            purposeSelect.dispatchEvent(new Event("change", { bubbles: true }));
                        }
                    });

                    this.modalOpen = true;
                    document.body.style.overflow = "hidden";
                    this.updateIcons();
                },

                closeModal() {
                    if (this.submitting) return;
                    this.modalOpen = false;
                    document.body.style.overflow = "";
                },

                // --- DYNAMIC ITEMS TABLE LOGIC ---

                addItem() {
                    const newIndex =
                        this.form.items.push({
                            _uid: Math.random().toString(36).substr(2, 9),
                            id: null,
                            product_id: null,
                            _product_name: "",
                            _searchQuery: "",
                            _searchResults: [],
                            _dropdownOpen: false,
                            _searching: false,
                            _searchToken: 0,
                            target_quantity: 1,
                            target_date: new Date().toISOString().split("T")[0],
                            remarks: "",
                        }) - 1;

                    // Pre-fetch recent products immediately so data is ready BEFORE the user clicks
                    this.searchProducts(newIndex);
                    this.updateIcons();
                },

                removeItem(index) {
                    this.form.items.splice(index, 1);
                },

                openPlantDropdown(index) {
                    const item = this.form.items[index];
                    // Check if other dropdowns are open and close them to keep UI clean
                    this.form.items.forEach((itm, idx) => {
                        if (idx !== index) itm._dropdownOpen = false;
                    });
                    item._dropdownOpen = true;
                    if (item._searchResults.length === 0) {
                        this.searchProducts(index);
                    }
                },

                async searchProducts(index) {
                    const item = this.form.items[index];
                    if (!item) return;

                    const query = item._searchQuery.trim();

                    if (item._searchResults.length === 0) {
                        item._searching = true;
                    }

                    const requestToken = ++item._searchToken;

                    try {
                        const params = new URLSearchParams();
                        if (query) params.set("q", query);

                        const res = await fetch(`{{ route('admin.production.plant-batches.plant-options') }}?${params}`, {
                            headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
                        });
                        const data = await res.json();

                        if (requestToken !== item._searchToken) return;
                        item._searchResults = data.options ?? [];
                    } catch (e) {
                        if (requestToken === item._searchToken) item._searchResults = [];
                    } finally {
                        if (requestToken === item._searchToken) item._searching = false;
                    }
                },
                pickProduct(index, product) {
                    const item = this.form.items[index];
                    item.product_id = product.id;
                    item._product_name = product.label;
                    item._searchQuery = "";
                    item._dropdownOpen = false;
                    delete this.errors[`items.${index}.product_id`];
                    this.updateIcons();
                },

                clearProduct(index) {
                    const item = this.form.items[index];
                    item.product_id = null;
                    item._product_name = "";
                    item._searchQuery = "";
                    this.updateIcons();
                },

                // --- CRUD API ACTIONS ---

                async submitForm() {
                    this.errors = {};
                    this.submitting = true;

                    // Ensure form values are strictly synced from DOM prior to payload assembly
                    this.form.purpose = document.querySelector('select[name="purpose"]')?.value || "";

                    const payload = {
                        title: this.form.title,
                        purpose: this.form.purpose || null,
                        notes: this.form.notes || null,
                        items: this.form.items.map((i, idx) => ({
                            product_id: i.product_id,
                            target_quantity: i.target_quantity,
                            target_date: i.target_date,
                            remarks: i.remarks || null,
                            sort_order: idx,
                        })),
                    };

                    const isEdit = this.modalMode === "edit";
                    const url = isEdit ? `/admin/production/plans/${this.activePlan.id}` : `/admin/production/plans`;
                    const method = isEdit ? "PUT" : "POST";

                    try {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": this.csrf(),
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();

                        if (res.status === 422) {
                            this.errors = data.errors || {};
                            BizAlert.toast("Please check the form for errors.", "error");
                            return;
                        }

                        if (!res.ok) throw new Error(data.message || "Something went wrong.");

                        BizAlert.toast(data.message, "success");

                        // Update local state without reload
                        if (isEdit) {
                            const idx = this.plans.findIndex((p) => p.id === this.activePlan.id);
                            if (idx !== -1) this.plans[idx] = data.plan;
                        } else {
                            this.plans.unshift(data.plan);
                        }

                        // ROOT FIX: Release the submitting lock BEFORE calling closeModal,
                        // otherwise closeModal's internal guard will block it from closing!
                        this.submitting = false;

                        this.closeModal();
                        this.updateIcons();
                    } catch (error) {
                        BizAlert.toast(error.message, "error");
                    } finally {
                        this.submitting = false;
                    }
                },

                async deletePlan(plan) {
                    const result = await BizAlert.confirm(
                        "Delete Plan",
                        `Are you sure you want to delete "${plan.title}"?`,
                        "Yes, delete it!",
                        "warning",
                    );

                    if (!result.isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/production/plans/${plan.id}`, {
                            method: "DELETE",
                            headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf() },
                        });
                        const data = await res.json();

                        if (!res.ok) throw new Error(data.message || "Failed to delete plan.");

                        BizAlert.toast(data.message, "success");
                        this.plans = this.plans.filter((p) => p.id !== plan.id);
                        this.updateIcons();
                    } catch (error) {
                        BizAlert.toast(error.message, "error");
                    }
                },

                async confirmPlan(plan) {
                    const result = await BizAlert.confirm(
                        "Confirm Plan",
                        `Ready to confirm "${plan.title}"? No further edits can be made.`,
                        "Yes, confirm it!",
                        "info",
                    );

                    if (!result.isConfirmed) return;

                    this.viewActionSubmitting = true;
                    try {
                        const res = await fetch(`/admin/production/plans/${plan.id}/confirm`, {
                            method: "POST",
                            headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf() },
                        });
                        const data = await res.json();

                        if (!res.ok) throw new Error(data.message || "Failed to confirm plan.");

                        BizAlert.toast(data.message, "success");
                        const idx = this.plans.findIndex((p) => p.id === plan.id);
                        if (idx !== -1) this.plans[idx] = data.plan;
                        if (this.viewPlan && this.viewPlan.id === plan.id) this.viewPlan = data.plan;
                        this.updateIcons();
                    } catch (error) {
                        BizAlert.toast(error.message, "error");
                    } finally {
                        this.viewActionSubmitting = false;
                    }
                },

                async cancelPlan(plan) {
                    const result = await BizAlert.confirm(
                        "Cancel Plan",
                        `Are you sure you want to cancel "${plan.title}"?`,
                        "Yes, cancel plan",
                        "warning",
                    );

                    if (!result.isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/production/plans/${plan.id}/cancel`, {
                            method: "POST",
                            headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf() },
                        });
                        const data = await res.json();

                        if (!res.ok) throw new Error(data.message || "Failed to cancel plan.");

                        BizAlert.toast(data.message, "success");
                        const idx = this.plans.findIndex((p) => p.id === plan.id);
                        if (idx !== -1) this.plans[idx] = data.plan;
                        this.updateIcons();
                    } catch (error) {
                        BizAlert.toast(error.message, "error");
                    }
                },

                earliestTargetDate(plan) {
                    if (!plan.items || !plan.items.length) return plan.created_at;
                    const dates = plan.items.map((i) => i.target_date).filter(Boolean);
                    if (!dates.length) return plan.created_at;
                    return dates.sort()[0];
                },

                openViewModal(plan) {
                    this.viewPlan = plan;
                    this.viewModalOpen = true;
                    document.body.style.overflow = "hidden";
                    this.updateIcons();
                },

                closeViewModal() {
                    if (this.viewActionSubmitting) return;
                    this.viewModalOpen = false;
                    document.body.style.overflow = "";
                },

                async closePlan(plan) {
                    const result = await BizAlert.confirm(
                        "Mark as Closed",
                        `Mark "${plan.title}" as closed? Do this once the plan's work is fully done.`,
                        "Yes, mark closed",
                        "info",
                    );

                    if (!result.isConfirmed) return;

                    this.viewActionSubmitting = true;
                    try {
                        const res = await fetch(`/admin/production/plans/${plan.id}/close`, {
                            method: "POST",
                            headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf() },
                        });
                        const data = await res.json();

                        if (!res.ok) throw new Error(data.message || "Failed to close plan.");

                        BizAlert.toast(data.message, "success");
                        const idx = this.plans.findIndex((p) => p.id === plan.id);
                        if (idx !== -1) this.plans[idx] = data.plan;
                        this.viewPlan = data.plan;
                        this.updateIcons();
                    } catch (error) {
                        BizAlert.toast(error.message, "error");
                    } finally {
                        this.viewActionSubmitting = false;
                    }
                },
            };
        }
    </script>
@endsection
