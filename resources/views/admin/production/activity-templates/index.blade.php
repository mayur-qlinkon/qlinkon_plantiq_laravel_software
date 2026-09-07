@extends ('layouts.admin')

@section('title', 'Activity Templates - PlantIQ')

@php
    // Prepare Enum metadata for JavaScript to easily render labels, icons, and colors
    $activityMeta = [];
    foreach (\App\Enums\Production\ActivityType::cases() as $case) {
        $activityMeta[$case->value] = [
            'label' => $case->label(),
            'icon' => $case->icon(),
            'color' => $case->color(),
        ];
    }
@endphp

@section('header-title')
    <div class="flex w-full items-center justify-between">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">Activity Templates</h1>
                <p class="text-sm text-gray-500">Define standardized care schedules for your plant batches.</p>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-7xl" x-data="activityTemplatesSPA()">
        {{-- Toolbar --}}
        <div class="mb-5 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            {{-- Left Side: Search (Client-side) --}}
            <div class="flex w-full items-center gap-3 md:max-w-lg">
                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                        <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <input type="text" x-model="searchQuery" placeholder="Search by activity type or species..."
                        class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none placeholder:text-gray-400 focus:ring-4" />
                </div>
            </div>

            {{-- Right Side: Create Action --}}
            <button type="button" @click="openModal('create')"
                class="bg-brand-600 hover:bg-brand-700 inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:shadow active:scale-95 md:w-auto">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Create Template
            </button>
        </div>

        {{-- Main Data Presentation --}}
        <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            {{-- Empty State --}}
            <div x-show="filteredTemplates.length === 0"
                class="flex flex-col items-center justify-center px-6 py-20 text-center" x-cloak>
                <div
                    class="mb-5 flex h-20 w-20 items-center justify-center rounded-full border-8 border-white bg-gray-50 text-gray-300 shadow-sm">
                    <i data-lucide="calendar-clock" class="h-8 w-8"></i>
                </div>
                <h3 class="mb-1 text-lg font-bold text-gray-900">No Templates Found</h3>
                <p class="mb-6 max-w-sm text-sm text-gray-500">You haven't defined any activity templates yet. Create global
                    or species-specific tasks.</p>
                <button type="button" @click="openModal('create')"
                    class="bg-brand-50 text-brand-700 hover:bg-brand-100 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold transition-colors">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create First Template
                </button>
            </div>

            {{-- Data Table (Desktop) --}}
            <div x-show="filteredTemplates.length > 0" class="hidden overflow-x-auto md:block" x-cloak>
                <table class="min-w-full divide-y divide-gray-100 text-left align-middle">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                Scope / Species
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                Activity Type
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                Timing & Rules
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-right text-[11px] font-black tracking-wider text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        <template x-for="template in filteredTemplates" :key="template.id">
                            <tr class="group transition-colors hover:bg-gray-50/50">
                                {{-- Scope / Species --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template x-if="template.product_id === null">
                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-2.5 py-1 text-[12px] font-bold text-white shadow-sm">
                                            <i data-lucide="globe" class="h-3.5 w-3.5"></i>
                                            Global Default
                                        </div>
                                    </template>
                                    <template x-if="template.product_id !== null">
                                        <div class="flex items-center gap-2 text-[14px] font-bold text-gray-900">
                                            <i data-lucide="leaf" class="h-4 w-4 text-emerald-500"></i>
                                            <span x-text="template.product?.name || 'Unknown Species'"></span>
                                        </div>
                                    </template>
                                </td>

                                {{-- Activity Type --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[12px] font-bold"
                                        :style="`background-color: ${activityMeta[template.activity_type]?.color?.bg}; color: ${activityMeta[template.activity_type]?.color?.text}`">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :style="`background-color: ${activityMeta[template.activity_type]?.color?.dot}`"></span>
                                        <span x-text="activityMeta[template.activity_type]?.label"></span>
                                    </span>
                                </td>

                                {{-- Timing & Rules --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 text-[13px]">
                                        <div class="flex items-center gap-1.5 font-medium text-gray-700">
                                            <i data-lucide="timer" class="h-3.5 w-3.5 text-gray-400"></i>
                                            Start:
                                            <span class="font-bold"
                                                x-text="
                                                    template.start_after_days == 0
                                                        ? 'Immediately'
                                                        : `Day ${template.start_after_days}`
                                                "></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 font-medium text-gray-700">
                                            <i data-lucide="repeat" class="h-3.5 w-3.5 text-gray-400"></i>
                                            Freq:
                                            <span class="font-bold capitalize" x-text="formatFrequency(template)"></span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status & Required --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        <span
                                            class="inline-flex w-fit items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-bold"
                                            :class="template.is_active ?
                                                'bg-green-50 text-green-700' :
                                                'bg-gray-100 text-gray-600'">
                                            <span x-text="template.is_active ? 'Active' : 'Inactive'"></span>
                                        </span>
                                        <template x-if="template.is_required">
                                            <span
                                                class="inline-flex w-fit items-center gap-1 rounded-md bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-700">
                                                Required Task
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openModal('edit', template)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-amber-600"
                                            title="Edit Template">
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>
                                        <button @click="deleteTemplate(template.id)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                            title="Delete Template">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards View (< md) --}}
            <div x-show="filteredTemplates.length > 0"
                class="block space-y-3 border-t border-gray-100 bg-gray-50/50 p-4 md:hidden" x-cloak>
                <template x-for="template in filteredTemplates" :key="'mobile-' + template.id">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition-all">
                        {{-- Top row: Activity Badge & Status --}}
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                :style="`background-color: ${activityMeta[template.activity_type]?.color?.bg}; color: ${activityMeta[template.activity_type]?.color?.text}`">
                                <span class="h-1.5 w-1.5 rounded-full"
                                    :style="`background-color: ${activityMeta[template.activity_type]?.color?.dot}`"></span>
                                <span x-text="activityMeta[template.activity_type]?.label"></span>
                            </span>

                            <div class="flex flex-col items-end gap-1">
                                <span
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                                    :class="template.is_active ?
                                        'bg-green-50 text-green-700' :
                                        'bg-gray-100 text-gray-600'">
                                    <span x-text="template.is_active ? 'Active' : 'Inactive'"></span>
                                </span>
                                <template x-if="template.is_required">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-red-700 uppercase">
                                        Required
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Details Grid --}}
                        <div class="grid grid-cols-2 gap-4 py-3 text-[12px]">
                            <div>
                                <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Scope /
                                    Species</span>
                                <template x-if="template.product_id === null">
                                    <div
                                        class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-2 py-0.5 text-[11px] font-bold text-white shadow-sm">
                                        <i data-lucide="globe" class="h-3 w-3"></i> Global
                                    </div>
                                </template>
                                <template x-if="template.product_id !== null">
                                    <div class="mt-1 flex items-center gap-1.5 font-bold text-gray-900">
                                        <i data-lucide="leaf" class="h-3.5 w-3.5 text-emerald-500"></i>
                                        <span x-text="template.product?.name || 'Unknown Species'"></span>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <span class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase">Timing &
                                    Rules</span>
                                <div class="mt-1 flex items-center gap-1.5 font-medium text-gray-700">
                                    <i data-lucide="timer" class="h-3 w-3 text-gray-400"></i>
                                    Start:
                                    <span class="font-bold"
                                        x-text="
                                            template.start_after_days == 0
                                                ? 'Immediately'
                                                : `Day ${template.start_after_days}`
                                        "></span>
                                </div>
                                <div class="flex items-center gap-1.5 font-medium text-gray-700">
                                    <i data-lucide="repeat" class="h-3 w-3 text-gray-400"></i>
                                    Freq: <span class="font-bold capitalize" x-text="formatFrequency(template)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Actions Footer --}}
                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-3">
                            <button @click="openModal('edit', template)"
                                class="flex h-8 items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 text-[11px] font-bold text-gray-600 transition-colors hover:bg-gray-50 hover:text-amber-600">
                                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i> Edit
                            </button>
                            <button @click="deleteTemplate(template.id)"
                                class="flex h-8 items-center justify-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 text-[11px] font-bold text-red-600 transition-colors hover:bg-red-100">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Delete
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- MODAL: CREATE / EDIT --}}
        {{-- ============================================================== --}}
        <div x-show="isModalOpen" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog"
            aria-modal="true" @keydown.escape.window="closeModal()">
            <div x-show="isModalOpen" x-transition.opacity
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="isModalOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title"
                                x-text="modalMode === 'create' ? 'Create Activity Template' : 'Edit Activity Template'">
                            </h3>
                            <button @click="closeModal()" class="text-gray-400 transition-colors hover:text-gray-600">
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>

                        <div class="px-6 py-5">
                            <form @submit.prevent="submitForm" class="space-y-5">
                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    {{-- Scope / Species --}}
                                    <div @change="form.product_id = $event.target.value">
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Target Species
                                            <span class="text-red-500">*</span></label>
                                        @php
                                            $productOptions = collect($products)->pluck('name', 'id')->toArray();
                                        @endphp
                                        <x-custom-select name="product_id" placeholder="Global Default (Applies to all)"
                                            :options="$productOptions" />
                                        <template x-if="errors.product_id">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.product_id[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Activity Type --}}
                                    <div @change="form.activity_type = $event.target.value">
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Activity Type <span
                                                class="text-red-500">*</span></label>
                                        @php
                                            $activityOptions = collect($activityMeta)
                                                ->mapWithKeys(fn($m, $k) => [$k => $m['label']])
                                                ->toArray();
                                            $activityIcons = collect($activityMeta)
                                                ->mapWithKeys(fn($m, $k) => [$k => $m['icon']])
                                                ->toArray();
                                        @endphp
                                        <x-custom-select name="activity_type" placeholder="Select Activity"
                                            :options="$activityOptions" :icons="$activityIcons" required="true" />
                                        <template x-if="errors.activity_type">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.activity_type[0]"></p>
                                        </template>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                    {{-- Start After Days --}}
                                    <div>
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Start After (Days)
                                            <span class="text-red-500">*</span></label>
                                        <input type="number" min="0" x-model="form.start_after_days" required
                                            class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none"
                                            placeholder="e.g. 0" />
                                        <template x-if="errors.start_after_days">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.start_after_days[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Frequency Type --}}
                                    <div @change="form.frequency_type = $event.target.value">
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Frequency
                                            Type</label>
                                        <x-custom-select name="frequency_type" placeholder="Select Frequency"
                                            :options="[
                                                'interval' => 'Interval (Days)',
                                                'daily' => 'Daily',
                                                'weekly' => 'Weekly',
                                                'monthly' => 'Monthly',
                                            ]" />
                                        <template x-if="errors.frequency_type">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.frequency_type[0]"></p>
                                        </template>
                                    </div>

                                    {{-- Frequency Value --}}
                                    <div x-show="form.frequency_type === 'interval'">
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Every X Days <span
                                                class="text-red-500">*</span></label>
                                        <input type="number" min="1" x-model="form.frequency_value"
                                            class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none"
                                            placeholder="e.g. 3" />
                                        <template x-if="errors.frequency_value">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.frequency_value[0]"></p>
                                        </template>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    {{-- Sort Order --}}
                                    <div>
                                        <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Checklist Sort
                                            Order</label>
                                        <input type="number" min="0" x-model="form.sort_order"
                                            class="focus:border-brand-500 focus:ring-brand-500/10 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none"
                                            placeholder="e.g. 10" />
                                        <template x-if="errors.sort_order">
                                            <p class="mt-1 text-[12px] font-medium text-red-500"
                                                x-text="errors.sort_order[0]"></p>
                                        </template>
                                    </div>
                                </div>

                                {{-- Toggles --}}
                                <div
                                    class="flex flex-col items-start gap-4 rounded-xl border border-gray-100 bg-gray-50/50 p-4 md:flex-row md:items-center md:gap-6">
                                    <label class="flex cursor-pointer items-center gap-2.5">
                                        <input type="checkbox" x-model="form.is_required"
                                            class="text-brand-600 focus:ring-brand-500 h-4 w-4 rounded border-gray-300" />
                                        <span class="text-[13px] font-bold text-gray-700">Required Task (Cannot be
                                            skipped)</span>
                                    </label>

                                    <label class="flex cursor-pointer items-center gap-2.5">
                                        <input type="checkbox" x-model="form.is_active"
                                            class="text-brand-600 focus:ring-brand-500 h-4 w-4 rounded border-gray-300" />
                                        <span class="text-[13px] font-bold text-gray-700">Active Template</span>
                                    </label>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="mb-1.5 block text-[13px] font-bold text-gray-700">Notes & Instructions
                                        (Optional)</label>
                                    <textarea x-model="form.notes" rows="2"
                                        class="focus:border-brand-500 focus:ring-brand-500/10 w-full resize-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-800 shadow-sm transition-all outline-none"
                                        placeholder="Specific instructions for employees..."></textarea>
                                </div>
                            </form>
                        </div>

                        <div
                            class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50/80 px-6 py-4">
                            <button type="button" @click="closeModal()"
                                class="rounded-xl px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-200"
                                :disabled="isSaving">
                                Cancel
                            </button>
                            <button type="button" @click="submitForm()"
                                class="bg-brand-600 hover:bg-brand-700 inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all"
                                :disabled="isSaving">
                                <i x-show="isSaving" data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                                <span x-text="isSaving ? 'Saving...' : 'Save Template'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global Window Function Pattern matching layouts.admin architecture
        window.activityTemplatesSPA = function() {
            return {
                templates: @json ($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                products: @json ($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                activityMeta: @json ($activityMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),

                searchQuery: "",
                isSaving: false,
                isModalOpen: false,
                modalMode: "create",
                errors: {},

                form: {
                    id: null,
                    product_id: "",
                    activity_type: "",
                    frequency_type: "interval",
                    frequency_value: 1,
                    start_after_days: 0,
                    sort_order: 0,
                    is_required: true,
                    is_active: true,
                    notes: "",
                },

                get filteredTemplates() {
                    if (!this.searchQuery) {
                        return this.templates;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.templates.filter((t) => {
                        const speciesMatch =
                            t.product?.name?.toLowerCase().includes(q) ||
                            (t.product_id === null && "global default".includes(q));
                        const activityMatch = this.activityMeta[t.activity_type]?.label?.toLowerCase()
                            .includes(q);
                        return speciesMatch || activityMatch;
                    });
                },

                formatFrequency(template) {
                    if (template.frequency_type === "interval") {
                        return template.frequency_value == 1 ? "Every Day" : `Every ${template.frequency_value} Days`;
                    }
                    return template.frequency_type;
                },

                openModal(mode, template = null) {
                    this.modalMode = mode;
                    this.errors = {};

                    if (mode === "edit" && template) {
                        this.form = {
                            id: template.id,
                            product_id: template.product_id || "",
                            activity_type: template.activity_type,
                            frequency_type: template.frequency_type || "interval",
                            frequency_value: template.frequency_value || 1,
                            start_after_days: template.start_after_days || 0,
                            sort_order: template.sort_order || 0,
                            is_required: Boolean(template.is_required),
                            is_active: Boolean(template.is_active),
                            notes: template.notes || "",
                        };
                    } else {
                        this.form = {
                            id: null,
                            product_id: "",
                            activity_type: "",
                            frequency_type: "interval",
                            frequency_value: 1,
                            start_after_days: 0,
                            sort_order: 0,
                            is_required: true,
                            is_active: true,
                            notes: "",
                        };
                    }

                    // Push values down into the DOM for the custom selects
                    this.$nextTick(() => {
                        const syncSelect = (name, val) => {
                            const el = document.querySelector(`select[name="${name}"]`);
                            if (el) {
                                el.value = val;
                                el.dispatchEvent(new Event("change", {
                                    bubbles: true
                                }));
                            }
                        };
                        syncSelect("product_id", this.form.product_id);
                        syncSelect("activity_type", this.form.activity_type);
                        syncSelect("frequency_type", this.form.frequency_type);
                    });

                    this.isModalOpen = true;
                },

                closeModal() {
                    this.isModalOpen = false;
                },

                async submitForm() {
                    this.isSaving = true;
                    this.errors = {};

                    // Ensure form values are strictly synced from DOM prior to payload assembly
                    this.form.product_id = document.querySelector('select[name="product_id"]')?.value || "";
                    this.form.activity_type = document.querySelector('select[name="activity_type"]')?.value || "";
                    this.form.frequency_type = document.querySelector('select[name="frequency_type"]')?.value ||
                        "interval";

                    const isEdit = this.modalMode === "edit";
                    const url = isEdit ?
                        `/admin/production/activity-templates/${this.form.id}` :
                        "{{ route('admin.production.activity-templates.store') }}";

                    const method = isEdit ? "PUT" : "POST";

                    const payload = {
                        ...this.form
                    };
                    if (payload.product_id === "") payload.product_id = null;

                    try {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    ?.content ?? "",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify(payload),
                        });

                        const result = await response.json();

                        if (response.status === 422) {
                            this.errors = result.errors || {
                                general: [result.message]
                            };
                            if (result.message && !result.errors && typeof Swal !== "undefined") {
                                Swal.fire({
                                    icon: "warning",
                                    title: "Duplicate detected",
                                    text: result.message,
                                    confirmButtonColor: "#1f2937",
                                });
                            }
                            return;
                        }

                        if (result.success) {
                            if (typeof Swal !== "undefined") {
                                Swal.fire({
                                    icon: "success",
                                    title: isEdit ? "Updated!" : "Created!",
                                    text: result.message,
                                    timer: 1200,
                                    showConfirmButton: false,
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                window.location.reload();
                            }
                        } else {
                            throw new Error(result.message || "Something went wrong.");
                        }
                    } catch (error) {
                        console.error("Save error:", error);
                        if (typeof Swal !== "undefined") {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: error.message || "Failed to save template.",
                                confirmButtonColor: "#1f2937",
                            });
                        }
                    } finally {
                        this.isSaving = false;
                    }
                },

                async deleteTemplate(id) {
                    if (typeof Swal === "undefined") return;

                    const confirm = await Swal.fire({
                        title: "Are you sure?",
                        text: "This activity template will be deleted permanently.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#ef4444",
                        cancelButtonColor: "#6b7280",
                        confirmButtonText: "Yes, delete it!",
                    });

                    if (confirm.isConfirmed) {
                        try {
                            const response = await fetch(`/admin/production/activity-templates/${id}`, {
                                method: "DELETE",
                                headers: {
                                    Accept: "application/json",
                                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                        ?.content ?? "",
                                    "X-Requested-With": "XMLHttpRequest",
                                },
                            });

                            const result = await response.json();

                            if (result.success) {
                                Swal.fire({
                                    icon: "success",
                                    title: "Deleted!",
                                    text: result.message,
                                    timer: 1200,
                                    showConfirmButton: false,
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(result.message);
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: error.message || "Failed to delete template.",
                                confirmButtonColor: "#1f2937",
                            });
                        }
                    }
                },
            };
        };
    </script>
@endsection
