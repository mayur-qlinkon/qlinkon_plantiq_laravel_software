@extends ('layouts.admin')

@section('title', 'Payment Methods')

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Payment Methods</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div x-data="paymentMethodApp()" x-init="boot()" class="pb-12">
        {{-- ── Main Table Card ── --}}
        <div class="pm-card">
            {{-- Card Header & Robust Search Bar --}}
            <div
                class="flex flex-col items-center justify-between gap-4 rounded-t-xl border-b border-gray-100 bg-white p-5 sm:flex-row">
                <span class="text-[16px] font-bold text-gray-900">All Methods</span>

                <div class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row">
                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" x-model="search" placeholder="Search methods…"
                            class="focus:ring-brand-500/20 focus:border-brand-500 block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-3 pl-9 text-sm text-gray-700 placeholder-gray-400 transition-colors focus:ring-2 focus:outline-none" />
                    </div>

                    {{-- Action Button --}}
                    @if (has_permission('payment_methods.create'))
                        <button @click="openModal()"
                            class="bg-brand-500 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-md transition-all active:scale-95 sm:w-auto">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add Method
                        </button>
                    @endif
                </div>
            </div>

            {{-- Table --}}
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="-mx-4 hidden overflow-x-auto sm:mx-0 md:block">
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th
                                class="w-12 px-5 py-3 text-center text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                #
                            </th>
                            <th class="px-5 py-3 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                Method Name
                            </th>
                            <th class="px-5 py-3 text-center text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                Type
                            </th>
                            <th class="px-5 py-3 text-center text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                Status
                            </th>
                            <th
                                class="px-5 py-3 pr-6 text-right text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    {{-- Alpine loop — each row is its own <tbody> (valid HTML, fixes parser bug) --}}
                    <template x-for="(row, index) in filteredMethods" :key="row.id">
                        <tbody class="border-b border-gray-100 transition-colors hover:bg-gray-50">
                            <tr>
                                {{-- Index Number --}}
                                <td class="px-5 py-4 text-center text-xs font-bold text-gray-400" x-text="index + 1"></td>

                                {{-- Name + Slug --}}
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-900" x-text="row.label"></div>
                                    <div class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-gray-500">
                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono" x-text="row.slug"></span>
                                    </div>
                                </td>

                                {{-- Online / Offline --}}
                                <td class="px-5 py-4 text-center">
                                    <template x-if="row.is_online">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-bold tracking-wider text-blue-700 uppercase">
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-blue-500 shadow-[0_0_0_2px_rgba(59,130,246,0.3)]"></span>
                                            Online
                                        </span>
                                    </template>
                                    <template x-if="!row.is_online">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold tracking-wider text-amber-700 uppercase">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Offline
                                        </span>
                                    </template>
                                </td>

                                {{-- Active / Inactive --}}
                                <td class="px-5 py-4 text-center">
                                    <template x-if="row.is_active">
                                        <span
                                            class="bg-brand-50 text-brand-600 border-brand-200 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[10px] font-bold tracking-wider uppercase">
                                            <span
                                                class="bg-brand-500 h-1.5 w-1.5 rounded-full shadow-[0_0_0_2px_rgba(16,185,129,0.25)]"></span>
                                            Active
                                        </span>
                                    </template>
                                    <template x-if="!row.is_active">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Inactive
                                        </span>
                                    </template>
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 pr-6 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if (has_permission('payment_methods.update'))
                                            <button @click="openModal(row)" title="Edit"
                                                class="text-brand-500 hover:bg-brand-500 hover:border-brand-500 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white transition-colors hover:text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                                    <path d="m15 5 4 4" />
                                                </svg>
                                            </button>
                                        @endif

                                        @if (has_permission('payment_methods.delete'))
                                            <button @click="deleteMethod(row.id)" title="Delete"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-red-500 transition-colors hover:border-red-500 hover:bg-red-500 hover:text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                    <line x1="10" x2="10" y1="11" y2="17" />
                                                    <line x1="14" x2="14" y1="11" y2="17" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    {{-- Empty state --}}
                    <tbody x-show="filteredMethods.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div
                                        class="bg-brand-50 border-brand-100 mb-2 flex h-16 w-16 items-center justify-center rounded-2xl border">
                                        <i data-lucide="credit-card" class="text-brand-500 h-8 w-8"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-[15px] font-bold text-gray-900"
                                            x-text="search ? 'No results found' : 'No payment methods yet'"></h3>
                                        <p class="mx-auto mt-1 max-w-xs text-sm text-gray-500"
                                            x-text="
                                                search
                                                    ? 'Try a different search term.'
                                                    : 'Click Add Method to create your first payment gateway.'
                                            ">
                                        </p>
                                    </div>
                                    <button x-show="!search" @click="openModal()"
                                        class="bg-brand-500 hover:bg-brand-600 mt-2 flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95">
                                        <i data-lucide="plus" class="h-4 w-4"></i> Add First Method
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="space-y-3 bg-gray-50/60 p-3 md:hidden">
                <template x-for="(row, index) in filteredMethods" :key="row.id">
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                        {{-- Header: Numbering, Name, Status --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                <span class="shrink-0 text-xs font-semibold text-gray-400"
                                    x-text="'#' + (index + 1) + '.'"></span>
                                <div class="truncate text-[14px] leading-tight font-bold text-gray-900" x-text="row.label"
                                    :title="row.label"></div>
                                <div class="mt-1 flex flex-wrap items-center gap-1 text-[11px] font-medium text-gray-500">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono" x-text="row.slug"></span>
                                    <template x-if="row.store">
                                        <span class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-gray-600"
                                            x-text="row.store.name"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <template x-if="row.is_active">
                                <span
                                    class="bg-brand-50 text-brand-600 border-brand-200 inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[9px] font-bold tracking-wider uppercase">
                                    <span
                                        class="bg-brand-500 h-1.5 w-1.5 rounded-full shadow-[0_0_0_2px_rgba(16,185,129,0.25)]"></span>
                                    Active
                                </span>
                            </template>
                            <template x-if="!row.is_active">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[9px] font-bold tracking-wider text-gray-500 uppercase">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Inactive
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Details: Gateway & Type --}}
                    <div
                        class="mt-1 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                        <div>
                            <template x-if="row.is_online">
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-blue-700 uppercase">
                                    Online
                                </span>
                            </template>
                            <template x-if="!row.is_online">
                                <span
                                    class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-amber-700 uppercase">
                                    Offline
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-1 flex items-center justify-end gap-2 border-t border-gray-50 pt-1">
                        @if (has_permission('payment_methods.update'))
                            <button @click="openModal(row)" title="Edit"
                                class="text-brand-500 hover:bg-brand-500 hover:border-brand-500 flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white transition-colors hover:text-white">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </button>
                        @endif
                        @if (has_permission('payment_methods.delete'))
                            <button @click="deleteMethod(row.id)" title="Delete"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-red-500 transition-colors hover:border-red-500 hover:bg-red-500 hover:text-white">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        @endif
                    </div>
            </div>
            </template>

            {{-- Mobile Empty state --}}
            <div x-show="filteredMethods.length === 0" class="bg-white p-8 text-center text-sm text-gray-400">
                <div class="flex flex-col items-center justify-center gap-2">
                    <div
                        class="bg-brand-50 border-brand-100 mb-1 flex h-12 w-12 items-center justify-center rounded-xl border">
                        <i data-lucide="credit-card" class="text-brand-500 h-6 w-6"></i>
                    </div>
                    <h3 class="text-[14px] font-bold text-gray-900"
                        x-text="search ? 'No results found' : 'No payment methods yet'"></h3>
                    <p class="text-xs text-gray-500"
                        x-text="
                            search
                                ? 'Try a different search term.'
                                : 'Click Add Method to create your first payment gateway.'
                        ">
                    </p>
                    <button x-show="!search" @click="openModal()"
                        class="bg-brand-500 hover:bg-brand-600 mt-3 flex items-center gap-1.5 rounded-xl px-4 py-2 text-[12px] font-bold text-white shadow-md transition-all active:scale-95">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add First Method
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         MODAL
    ══════════════════════════════════════ --}}
    <div x-cloak x-show="showModal" @keydown.escape.window="closeModal()"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
        @click.self="closeModal()" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-y-auto rounded-2xl bg-white shadow-xl"
            @click.stop x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            {{-- Header --}}
            <div
                class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-gray-100 bg-white px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="bg-brand-50 border-brand-100 flex h-10 w-10 items-center justify-center rounded-xl border">
                        <i data-lucide="credit-card" class="text-brand-500 h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900"
                            x-text="isEdit ? 'Edit Payment Method' : 'Add Payment Method'"></h3>
                        <p class="mt-0.5 text-xs text-gray-500"
                            x-text="isEdit ? 'Update gateway details and settings' : 'Configure a new payment gateway'">
                        </p>
                    </div>
                </div>
                <button @click="closeModal()" type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-500">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <template x-if="errorMessage">
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div class="mb-1 font-bold">Please fix the highlighted fields.</div>
                        <p x-text="errorMessage"></p>
                    </div>
                </template>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Display Label --}}
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold tracking-wide text-gray-700 uppercase">Display Label
                            <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.label"
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:outline-none"
                            placeholder="e.g. Credit / Debit Card" required @keydown.enter.prevent="saveMethod()" />
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wide text-gray-700 uppercase">URL Slug <span
                                class="font-medium text-gray-400 normal-case">(optional)</span></label>
                        <input type="text" x-model="form.slug"
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border bg-gray-50 px-3 py-2 text-sm focus:ring-2 focus:outline-none"
                            :class="hasError('slug') ? 'border-red-300 bg-red-50' : 'border-gray-200'"
                            placeholder="Auto-generated" />
                        <template x-if="fieldError('slug')">
                            <p class="mt-1 text-xs font-medium text-red-600" x-text="fieldError('slug')"></p>
                        </template>
                    </div>

                    {{-- Gateway --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-bold tracking-wide text-gray-700 uppercase">Gateway <span
                                class="font-medium text-gray-400 normal-case">(optional)</span></label>
                        <input type="text" x-model="form.gateway"
                            class="focus:ring-brand-500/20 focus:border-brand-500 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:outline-none"
                            placeholder="e.g. razorpay" />
                    </div>
                </div>

                <hr class="my-5 border-gray-100" />

                {{-- Toggles --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Online Gateway toggle (button-based) --}}
                    <button type="button" @click.prevent="form.is_online = !form.is_online"
                        class="group hover:border-brand-300 hover:bg-brand-50/50 flex w-full cursor-pointer items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3 text-left transition-colors"
                        :aria-pressed="form.is_online">
                        <span class="relative inline-flex h-6 w-10 shrink-0 items-center rounded-full transition-colors"
                            :class="form.is_online ? 'bg-brand-500' : 'bg-gray-200'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="form.is_online ? 'translate-x-4' : 'translate-x-1'"></span>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-gray-800">Online Gateway</span>
                            <span class="text-[11px] text-gray-500">Processes via internet</span>
                        </span>
                    </button>

                    {{-- Active Status toggle (button-based) --}}
                    <button type="button" @click.prevent="form.is_active = !form.is_active"
                        class="group hover:border-brand-300 hover:bg-brand-50/50 flex w-full cursor-pointer items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3 text-left transition-colors"
                        :aria-pressed="form.is_active">
                        <span class="relative inline-flex h-6 w-10 shrink-0 items-center rounded-full transition-colors"
                            :class="form.is_active ? 'bg-brand-500' : 'bg-gray-200'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="form.is_active ? 'translate-x-4' : 'translate-x-1'"></span>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-gray-800">Active Status</span>
                            <span class="text-[11px] text-gray-500">Visible to customers</span>
                        </span>
                    </button>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-gray-50 p-4">
                <button type="button"
                    class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-100"
                    @click="closeModal()" :disabled="isSaving">
                    Cancel
                </button>
                <button type="button"
                    class="bg-brand-500 hover:bg-brand-600 flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all active:scale-95 disabled:cursor-not-allowed disabled:opacity-70"
                    @click="saveMethod()" :disabled="isSaving">
                    <i data-lucide="loader-2" x-show="isSaving" class="h-4 w-4 animate-spin" style="display: none"></i>
                    <span x-text="isSaving ? 'Saving…' : isEdit ? 'Update Method' : 'Save Method'"></span>
                </button>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        function paymentMethodApp() {
            return {
                methods: @json ($paymentMethods ?? []),
                search: "",
                showModal: false,
                isEdit: false,
                isSaving: false,
                errorMessage: "",
                formErrors: {},

                form: {
                    id: null,
                    label: "",
                    slug: "",
                    gateway: "",
                    is_online: false,
                    is_active: true,
                },
                clearErrors() {
                    this.errorMessage = "";
                    this.formErrors = {};
                },
                fieldError(field) {
                    return this.formErrors?.[field]?.[0] ?? "";
                },
                hasError(field) {
                    return !!this.formErrors?.[field]?.length;
                },

                /* ── Computed filtered list ── */
                get filteredMethods() {
                    if (!this.search.trim()) return this.methods;
                    const q = this.search.toLowerCase();
                    return this.methods.filter(
                        (m) =>
                        (m.label && m.label.toLowerCase().includes(q)) ||
                        (m.slug && m.slug.toLowerCase().includes(q)) ||
                        (m.gateway && m.gateway.toLowerCase().includes(q)) ||
                        (m.store && m.store.name && m.store.name.toLowerCase().includes(q)),
                    );
                },

                /* ── Init ── */
                boot() {
                    // Force boolean types to fix Hostinger DB driver returning '0'/'1'
                    this.methods = this.methods.map((m) => ({
                        ...m,
                        is_online: Boolean(m.is_online == 1 || m.is_online === true || m.is_online === "1"),
                        is_active: Boolean(m.is_active == 1 || m.is_active === true || m.is_active === "1"),
                    }));
                },

                /* ── Modal ── */
                openModal(row = null) {
                    this.isEdit = !!row;
                    this.form = row ? {
                        ...row,
                        is_online: Boolean(row.is_online),
                        is_active: Boolean(row.is_active)
                    } : {
                        id: null,
                        label: "",
                        slug: "",
                        gateway: "",
                        is_online: false,
                        is_active: true,
                    };
                    this.clearErrors();
                    this.showModal = true;
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                closeModal() {
                    this.clearErrors();
                    this.showModal = false;
                    setTimeout(() => {
                        this.isSaving = false;
                    }, 300);
                },

                /* ── Save (create / update) ── */
                async saveMethod() {
                    if (!this.form.label.trim()) {
                        this.clearErrors();
                        this.formErrors = {
                            label: ["Display label is required."]
                        };
                        this.errorMessage = "Display label is required.";
                        return;
                    }

                    this.isSaving = true;
                    this.clearErrors();

                    const method = this.isEdit ? "PUT" : "POST";
                    const url = this.isEdit ? `/admin/payment-methods/${this.form.id}` : `/admin/payment-methods`;

                    try {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            },
                            body: JSON.stringify(this.form),
                        });

                        const result = await res.json().catch(() => ({}));

                        if (res.ok && result.success) {
                            result.data.is_online = Boolean(
                                result.data.is_online == 1 || result.data.is_online === true || result.data
                                .is_online === "1",
                            );
                            result.data.is_active = Boolean(
                                result.data.is_active == 1 || result.data.is_active === true || result.data
                                .is_active === "1",
                            );

                            if (this.isEdit) {
                                const idx = this.methods.findIndex((m) => m.id === this.form.id);
                                if (idx !== -1) {
                                    result.data.sort_order = this.methods[idx].sort_order;
                                    this.methods.splice(idx, 1, result.data);
                                }
                            } else {
                                this.methods.unshift(result.data);
                            }

                            this.$nextTick(() => {
                                if (typeof lucide !== "undefined") lucide.createIcons();
                            });

                            BizAlert.toast(result.message, "success");
                            this.closeModal();
                            return;
                        }

                        if (res.status === 422 && result.errors) {
                            this.formErrors = result.errors;
                            this.errorMessage = result.message || "Please correct the highlighted fields.";
                            return;
                        }

                        this.errorMessage = result.message || "Validation failed.";
                        BizAlert.toast(this.errorMessage, "error");
                    } catch (err) {
                        console.error("Save error:", err);
                        this.errorMessage = "Network error occurred.";
                        BizAlert.toast(this.errorMessage, "error");
                    } finally {
                        this.isSaving = false;
                    }
                },

                /* ── Delete ── */
                async deleteMethod(id) {
                    const confirm = await BizAlert.confirm(
                        "Delete Payment Method?",
                        "This action cannot be undone.",
                        "Yes, Delete It",
                        "warning",
                    );
                    if (!confirm.isConfirmed) return;

                    try {
                        const res = await fetch(`/admin/payment-methods/${id}`, {
                            method: "DELETE",
                            headers: {
                                Accept: "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            },
                        });
                        const result = await res.json();

                        if (res.ok && result.success) {
                            this.methods = this.methods.filter((m) => m.id !== id);
                            BizAlert.toast(result.message, "success");
                        } else {
                            BizAlert.toast(result.message || "Failed to delete.", "error");
                        }
                    } catch (err) {
                        BizAlert.toast("Network error occurred.", "error");
                    }
                },
            };
        }
    </script>
@endpush
