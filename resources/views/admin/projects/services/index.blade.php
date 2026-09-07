@extends ('layouts.admin')

@section('title', 'Service Catalog - ' . config('app.name'))

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Service Catalog</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="serviceCatalog()">
        {{-- SEARCH & FILTER BAR --}}
        <div class="rounded-t-xl border border-b-0 border-gray-100 bg-white p-4 shadow-sm">
            <form id="catalog-filter-form" action="{{ route('admin.services.index') }}" method="GET"
                class="flex w-full flex-wrap items-center gap-3" @submit.prevent="submitForm" @change="submitForm">
                {{-- Search Group --}}
                <div class="flex w-full max-w-md min-w-[250px] flex-1 flex-row items-center gap-2">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search service name..." @input.debounce.400ms="submitForm"
                            class="w-full rounded-lg border border-gray-200 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>

                    <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        title="Clear Filters">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                    </button>
                </div>

                @php
                    $statusFilterOptions = ['active' => 'Active', 'inactive' => 'Inactive'];

                    // $cycles arrives either flat or nested, so it is normalised
                    // once here instead of inside each loop.
                    $cycleOptions = [];
                    foreach ($cycles as $key => $cycle) {
                        $val = is_array($cycle) ? $cycle['value'] ?? ($cycle['id'] ?? $key) : $key;
                        $lbl = is_array($cycle) ? $cycle['label'] ?? ($cycle['name'] ?? $val) : $cycle;
                        $cycleOptions[(string) $val] = $lbl;
                    }
                @endphp

                {{-- Status Filter --}}
                <div class="w-full shrink-0 lg:w-40">
                    {{-- The form's own @change picks up the change event bubbling
                         from the hidden select, so the filter still fires on its
                         own. The window listener is only for clearFilters(), which
                         blanks the hidden value but cannot reach the trigger label. --}}
                    <x-custom-select name="status" :options="$statusFilterOptions" :selected="request('status')" placeholder="All Statuses"
                        @reset-catalog-filters.window="value = ''" />
                </div>

                {{-- Billing Cycle Filter --}}
                <div class="w-full shrink-0 lg:w-48">
                    <x-custom-select name="billing_cycle" :options="$cycleOptions" :selected="(string) request('billing_cycle')"
                        placeholder="All Billing Cycles" @reset-catalog-filters.window="value = ''" />
                </div>

                {{-- Add Service Button --}}
                @if (has_permission('project_services.create'))
                    <div class="ml-auto flex w-full shrink-0 sm:w-auto">
                        <button type="button" @click="openModal()"
                            class="bg-brand-500 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors sm:w-auto">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add Service
                        </button>
                    </div>
                @endif
            </form>
        </div>

        {{-- DATA TABLE CONTAINER --}}
        <div id="catalog-list-container"
            class="flex flex-col overflow-hidden rounded-b-xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-4">SERVICE NAME</th>
                            <th class="px-6 py-4">TYPE</th>
                            <th class="px-6 py-4">BILLING CYCLE</th>
                            <th class="px-6 py-4 text-right">DEFAULT PRICE</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($services as $service)
                            <tr class="group transition-colors hover:bg-gray-50/50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800">{{ $service->name }}</div>
                                    @if ($service->description)
                                        <div class="mt-0.5 max-w-xs truncate text-[11px] text-gray-400"
                                            title="{{ $service->description }}">
                                            {{ $service->description }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="text-[11px] font-bold text-gray-500 uppercase">{{ $service->service_type ?: 'General' }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="rounded bg-gray-100 px-2 py-1 text-[10px] font-extrabold tracking-wider text-gray-600 uppercase">
                                        {{ str_replace('_', ' ', $service->billing_cycle->value ?? $service->billing_cycle) }}
                                        @if (($service->billing_cycle->value ?? $service->billing_cycle) === 'custom' && $service->duration_days)
                                            ({{ $service->duration_days }} days)
                                        @endif
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="font-extrabold text-[#108c2a]">
                                        ₹{{ number_format($service->price, 2) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        +{{ number_format($service->tax_rate, 2) }}% Tax
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if ($service->is_active)
                                        <span
                                            class="rounded-md border border-green-200 bg-green-50 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-green-700 uppercase">Active</span>
                                    @else
                                        <span
                                            class="rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-gray-600 uppercase">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (has_permission('project_services.update'))
                                            <button type="button" @click="openModal({{ $service->toJson() }})"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-blue-200 text-blue-500 transition-colors hover:bg-blue-50"
                                                title="Edit Service">
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if (has_permission('project_services.delete'))
                                            <button type="button" @click="deleteService({{ $service->id }})"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                                title="Delete Service">
                                                <i data-lucide="trash" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="package" class="mb-3 h-10 w-10 opacity-20"></i>
                                        <p class="text-sm font-medium">No services found in catalog.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($services->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">{{ $services->links() }}</div>
            @endif
        </div>

        {{-- ADD / EDIT MODAL --}}
        <div x-show="modals.form" x-cloak @keydown.escape.window="closeModal()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl animate-[slideUp_0.3s_ease-out] overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900" x-text="editing ? 'Edit Master Service' : 'Add New Service'">
                        </h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Editing only affects future assignments, not existing
                            clients.</p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 transition-colors hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="saveService" class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Service Name *</label>
                            <input type="text" x-model="form.name" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Service Type *</label>
                            <input type="text" x-model="form.service_type" required placeholder="e.g. hosting, seo"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Billing Cycle *</label>
                            {{-- x-alpine-select, not x-custom-select: this modal
                                 reopens for each service, and this one reads
                                 form.billing_cycle directly instead of holding its
                                 own value, so no sync event is needed. --}}
                            <x-alpine-select model="form.billing_cycle" items="cycleOptions" item-key="value"
                                item-label="item.label" placeholder="Select cycle" :required="true" :allow-empty="false" />
                        </div>

                        <div x-show="form.billing_cycle === 'custom'" x-cloak
                            class="rounded-lg border border-blue-100 bg-blue-50 p-3 md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-blue-800">Duration in Days *</label>
                            <input type="number" x-model.number="form.duration_days"
                                :required="form.billing_cycle === 'custom'" min="1"
                                class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                            <p class="mt-1 text-[10px] text-blue-600">Specify how many days this custom cycle lasts.</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Default Price (₹) *</label>
                            <input type="number" step="0.01" x-model.number="form.price" required min="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Tax Rate (%)</label>
                            <input type="number" step="0.01" x-model.number="form.tax_rate" min="0"
                                max="100" value="0" placeholder="18.00"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Description</label>
                            <textarea x-model="form.description" rows="2"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"></textarea>
                        </div>

                        <div class="flex items-center gap-2 border-t border-gray-100 pt-2 md:col-span-2">
                            <input type="checkbox" id="is_active" x-model="form.is_active"
                                class="text-brand-600 focus:ring-brand-500 h-4 w-4 rounded border-gray-300" />
                            <label for="is_active" class="cursor-pointer text-sm font-bold text-gray-700">Service is
                                active and available for new sales</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="bg-brand-500 hover:bg-brand-600 flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50">
                            <i data-lucide="loader-2" x-show="isProcessing" class="h-4 w-4 animate-spin"></i>
                            <span x-text="editing ? 'Update Service' : 'Save Service'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function serviceCatalog() {
            return {
                hasActiveFilters: false,
                isProcessing: false,
                editing: null,

                modals: {
                    form: false,
                },

                form: {
                    id: "",
                    name: "",
                    service_type: "",
                    billing_cycle: "yearly",
                    duration_days: "",
                    price: "",
                    tax_rate: 0,
                    description: "",
                    is_active: true,
                },

                init() {
                    this.checkActiveFilters();
                },

                // --- AJAX Pagination & Filters (Mirroring invoices logic) ---
                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("catalog-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                // Same list the filter dropdown uses, in the shape x-alpine-select
                // expects. Kept on the component so the modal reads it from scope.
                cycleOptions: @js(collect($cycleOptions)->map(fn($label, $value) => ['value' => (string) $value, 'label' => $label])->values()),

                submitForm() {
                    const form = document.getElementById("catalog-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });
                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("catalog-filter-form");
                    if (form) {
                        form.querySelectorAll('input[type="text"], select').forEach((el) => {
                            el.value = "";
                            el.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                        });

                        // Blanking the hidden select does not reach the custom
                        // select's own trigger, which would keep showing the
                        // cleared filter's label.
                        window.dispatchEvent(new CustomEvent("reset-catalog-filters"));

                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("catalog-list-container");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("catalog-list-container");

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

                // --- CRUD Modals & Logic ---
                openModal(service = null) {
                    this.editing = service;
                    if (service) {
                        this.form = {
                            id: service.id,
                            name: service.name,
                            service_type: service.service_type || "",
                            billing_cycle: service.billing_cycle,
                            duration_days: service.duration_days || "",
                            price: service.price,
                            // service.tax_rate can legitimately be 0 (GST-free);
                            // `|| ""` treated 0 as falsy and blanked the field.
                            tax_rate: service.tax_rate ?? 0,
                            description: service.description || "",
                            is_active: Boolean(service.is_active),
                        };
                    } else {
                        this.form = {
                            id: "",
                            name: "",
                            service_type: "",
                            billing_cycle: "yearly",
                            duration_days: "",
                            price: 0,
                            tax_rate: 0,
                            description: "",
                            is_active: true,
                        };
                    }
                    this.modals.form = true;
                },

                closeModal() {
                    this.modals.form = false;
                    this.editing = null;
                },

                saveService() {
                    this.isProcessing = true;
                    const url = this.editing ?
                        `{{ route('admin.services.index') }}/${this.editing.id}` :
                        `{{ route('admin.services.store') }}`;

                    const method = this.editing ? "PUT" : "POST";

                    fetch(url, {
                            method: method,
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.form),
                        })
                        .then(async (response) => {
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || "Validation failed");

                            BizAlert.toast(data.message, "success");
                            this.closeModal();
                            this.submitForm(); // Refresh the list without page reload
                        })
                        .catch((error) => {
                            BizAlert.toast(error.message, "error");
                        })
                        .finally(() => {
                            this.isProcessing = false;
                        });
                },

                deleteService(id) {
                    BizAlert.confirm(
                        "Delete Service?",
                        "If this service has been assigned to clients, it will be deactivated instead to preserve historical records.",
                        "Yes, remove it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ route('admin.services.index') }}/${id}`, {
                                    method: "DELETE",
                                    headers: {
                                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                            .content,
                                        Accept: "application/json",
                                    },
                                })
                                .then(async (response) => {
                                    const data = await response.json();
                                    if (!response.ok) throw new Error(data.message || "Failed to delete");

                                    BizAlert.toast(data.message, "success");
                                    this.submitForm(); // Refresh the list
                                })
                                .catch((error) => {
                                    BizAlert.toast(error.message, "error");
                                });
                        }
                    });
                },
            };
        }
    </script>
@endpush
