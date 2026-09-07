@extends ('layouts.admin')

@section('title', 'Projects - ' . config('app.name'))

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Projects</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    <div class="pb-10" x-data="projectIndex(@js($clients), @js($recentClientIds))">
        {{-- SEARCH & FILTER BAR --}}
        <div class="rounded-t-xl border border-b-0 border-gray-100 bg-white p-4 shadow-sm">
            <form id="project-filter-form" action="{{ route('admin.projects.index') }}" method="GET"
                class="flex w-full flex-wrap items-center gap-3" @submit.prevent="submitForm" @change="submitForm">
                {{-- Search Group --}}
                <div class="flex w-full max-w-md min-w-[250px] flex-1 flex-row items-center gap-2">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search Project Title or Client..." @input.debounce.400ms="submitForm"
                            class="w-full rounded-lg border border-gray-200 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>

                    <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        title="Clear Filters">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                    </button>
                </div>

                {{-- Client Filter --}}
                <div class="w-full shrink-0 lg:w-48">
                    <x-alpine-select name="client_id" model="filterClientId" items="clientOptions" placeholder="All Clients"
                        on-change="submitForm()" :allow-empty="true" />
                </div>

                {{-- Project Status Filter --}}
                <div class="w-full shrink-0 lg:w-40">
                    <x-alpine-select name="status" model="filterStatus" items="statusOptions" placeholder="All Statuses"
                        on-change="submitForm()" :allow-empty="true" />
                </div>

                {{-- Financial/Payment Status Filter --}}
                <div class="w-full shrink-0 lg:w-40">
                    <x-alpine-select name="payment_status" model="filterPaymentStatus" items="paymentStatusOptions"
                        placeholder="All Financials" on-change="submitForm()" :allow-empty="true" />
                </div>

                {{-- Create Project Button --}}
                @if (has_permission('projects.create'))
                    <div class="ml-auto flex w-full shrink-0 sm:w-auto">
                        <button type="button" @click="openModal()"
                            class="bg-brand-500 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors sm:w-auto">
                            <i data-lucide="plus" class="h-4 w-4"></i> New Project
                        </button>
                    </div>
                @endif
            </form>
        </div>

        {{-- DATA TABLE --}}
        <div id="projects-list-container"
            class="flex flex-col overflow-hidden rounded-b-xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase">
                        <tr>
                            <th class="w-12 px-6 py-4 text-center">#</th>
                            <th class="px-6 py-4">PROJECT DETAILS</th>
                            <th class="px-6 py-4">CLIENT</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-right">TOTAL CHARGED</th>
                            <th class="px-6 py-4 text-right">OUTSTANDING</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($projects as $project)
                            @php
                                // The model's accessors already read the withChargeTotals()
// aggregates (charges_total / charges_paid / charges_written_off)
// and only fall back to a query when that scope was not applied.
// The old charges_sum_* names never existed, so every row was
// silently taking the fallback path.
$totalCharged = $project->total_charged;
$balanceDue = max(0, $project->outstanding_amount);

// Only what the edit form needs. toJson() shipped the whole
// model, including the loaded client and owner relations,
// into an attribute on every single row.
$editPayload = [
    'id' => $project->id,
    'client_id' => $project->client_id,
    'title' => $project->title,
    'description' => $project->description,
    'status' => $project->status->value ?? $project->status,
    'start_date' => optional($project->start_date)->toDateString(),
    'expected_end_date' => optional($project->expected_end_date)->toDateString(),
];

$statusColors = [
    'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
    'active' => 'bg-green-50 text-green-700 border-green-200',
    'on_hold' => 'bg-amber-50 text-amber-700 border-amber-200',
    'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
    'cancelled' => 'bg-red-50 text-red-600 border-red-200',
];
$statusValue = $project->status->value ?? $project->status;
$color = $statusColors[$statusValue] ?? $statusColors['draft'];
                            @endphp

                            <tr class="group transition-colors hover:bg-gray-50/50">
                                <td class="px-6 py-4 text-center text-xs font-bold text-gray-400">
                                    {{ $projects->firstItem() + $loop->index }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.projects.show', $project->id) }}"
                                            class="text-[13px] font-extrabold text-[#108c2a] hover:underline">
                                            {{ $project->title }}
                                        </a>
                                        <span class="mt-0.5 text-[11px] font-medium text-gray-500">
                                            {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M, Y') : 'No start date' }}
                                            @if ($project->expected_end_date)
                                                &rarr;
                                                {{ \Carbon\Carbon::parse($project->expected_end_date)->format('d M, Y') }}
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[13px] font-bold text-gray-800">{{ $project->client->name ?? 'Unknown Client' }}</span>
                                        @if ($project->client && $project->client->phone)
                                            <span class="mt-0.5 text-[11px] font-medium text-gray-500"><i
                                                    data-lucide="phone" class="mr-0.5 inline h-3 w-3"></i>
                                                {{ $project->client->phone }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $color }}">
                                        {{ str_replace('_', ' ', $statusValue) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span
                                        class="font-extrabold text-gray-800">₹{{ number_format($totalCharged, 2) }}</span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    @if ($balanceDue > 0)
                                        <span class="font-bold text-red-600">₹{{ number_format($balanceDue, 2) }}</span>
                                    @else
                                        <span class="text-gray-300">0.00</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        {{-- Workspace Link --}}
                                        @if (has_permission('projects.view'))
                                            <a href="{{ route('admin.projects.show', $project->id) }}"
                                                class="flex h-8 items-center justify-center rounded border border-gray-200 bg-white px-3 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50">
                                                Workspace <i data-lucide="arrow-right" class="ml-1 h-3.5 w-3.5"></i>
                                            </a>
                                        @endif

                                        {{-- Edit --}}
                                        @if (has_permission('projects.update'))
                                            <button type="button" @click="openModal(@js($editPayload))"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-blue-200 text-blue-500 transition-colors hover:bg-blue-50"
                                                title="Edit Project">
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        {{-- Delete --}}
                                        @if (has_permission('projects.delete'))
                                            <button type="button" @click="deleteProject({{ $project->id }})"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                                title="Delete Project">
                                                <i data-lucide="trash" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="folder-kanban" class="mb-3 h-10 w-10 opacity-20"></i>
                                        <p class="text-sm font-medium">No projects found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($projects->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">{{ $projects->links() }}</div>
            @endif
        </div>

        {{-- ADD / EDIT MODAL --}}
        <div x-show="modals.form" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="
                if (isClientModalOpen) { isClientModalOpen = false }
                else if (modals.form) { closeModal() }
            ">
            {{--
                Closes only via the header X, the Cancel button, or Escape.
                Backdrop clicks used to close it, which discarded a filled form
                on a stray click. Escape unwinds one layer at a time: the Quick
                Client modal first when it is open, this one otherwise.
            --}}
            <div class="w-full max-w-2xl animate-[slideUp_0.3s_ease-out] overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900"
                            x-text="editing ? 'Edit Project Details' : 'Create New Project'"></h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Projects are operational containers for client services
                            and charges.</p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 transition-colors hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="saveProject" class="max-h-[75vh] space-y-4 overflow-y-auto p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Project Title *</label>
                            <input type="text" x-model="form.title" required placeholder="e.g. Website Redesign & SEO"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Client *</label>

                            <div class="relative" @click.outside="clientDropdownOpen = false">
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <input type="text" x-model="clientSearchTerm"
                                            @focus="clientDropdownOpen = true"
                                            @input="clientDropdownOpen = true; form.client_id = ''"
                                            placeholder="Search client by name or phone..." autocomplete="off"
                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 pr-9 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                                        <i data-lucide="chevron-down"
                                            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                    </div>

                                    {{-- Quick Add Client — same component the invoice screen uses. --}}
                                    <button type="button" @click="clientDropdownOpen = false; isClientModalOpen = true"
                                        class="flex shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-3 text-blue-600 transition-colors hover:bg-blue-100"
                                        title="Quick Add Client">
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                    </button>
                                </div>

                                {{-- Actual value sent to the server. --}}
                                <input type="hidden" x-model="form.client_id" />

                                <ul x-show="clientDropdownOpen" x-cloak x-transition
                                    class="absolute top-full left-0 z-[60] mt-1 max-h-60 w-[calc(100%-2.75rem)] overflow-y-auto overscroll-contain rounded-lg border border-gray-200 bg-white shadow-2xl">
                                    {{-- Suggestions before the user types anything. --}}
                                    <li x-show="clientSearchTerm.trim() === '' && recentClients.length > 0"
                                        class="border-b border-gray-100 bg-gray-50 px-4 py-1.5 text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                                        Recent Clients
                                    </li>

                                    <li x-show="filteredClients.length === 0"
                                        class="px-4 py-4 text-center text-sm font-medium text-gray-500">
                                        No matching clients found.
                                    </li>

                                    <template x-for="client in filteredClients" :key="client.id">
                                        <li @click="selectClient(client)"
                                            class="cursor-pointer border-b border-gray-100 px-4 py-2.5 transition-colors last:border-0 hover:bg-gray-50">
                                            <div class="text-[13px] font-bold text-gray-800" x-text="client.name"></div>
                                            <div
                                                class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                                <span x-show="client.phone" x-text="client.phone"></span>
                                                <span x-show="client.gst_number"
                                                    class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold text-gray-600"
                                                    x-text="'GST: ' + client.gst_number"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Status</label>
                            <x-alpine-select name="form_status" model="form.status" items="statusOptions"
                                :allow-empty="false" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Start Date</label>
                            <input type="date" x-model="form.start_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Expected End Date</label>
                            <input type="date" x-model="form.expected_end_date" :min="form.start_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Description & Notes</label>
                            <textarea x-model="form.description" rows="2"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"></textarea>
                        </div>
                    </div>

                    {{-- INITIAL CHARGE SECTION (Only shown when creating a new project) --}}
                    <div x-show="!editing" x-cloak class="mt-6 rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                        <div class="mb-3 flex items-start gap-2">
                            <i data-lucide="info" class="mt-0.5 h-4 w-4 text-blue-500"></i>
                            <div>
                                <h4 class="text-xs font-bold text-blue-800">Initial Project Charge (Optional)</h4>
                                <p class="mt-0.5 text-[10px] text-blue-600">Enter an amount to automatically raise the
                                    first financial charge for this project immediately.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Agreed Amount (₹)</label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0"
                                    x-model.number="form.amount" placeholder="0"
                                    class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Tax Rate (%)</label>
                                <input type="text" inputmode="numeric" data-int data-int-min="0" data-int-max="100"
                                    x-model.number="form.tax_rate" placeholder="0"
                                    class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold text-gray-700">Due Date</label>
                                <input type="date" x-model="form.due_date" :min="form.start_date"
                                    class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="bg-brand-500 hover:bg-brand-600 flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white shadow-sm disabled:opacity-50">
                            <i data-lucide="loader-2" x-show="isProcessing" class="h-4 w-4 animate-spin"></i>
                            <span x-text="editing ? 'Update Project' : 'Create Project'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Quick Client Modal reads isClientModalOpen / newClient / saveQuickClient() from this scope. --}}
        <x-quick-client-modal />
    </div>
@endsection

@push('scripts')
    <script>
        function projectIndex(allClients = [], recentClientIds = []) {
            return {
                hasActiveFilters: false,
                isProcessing: false,
                editing: null,

                // Searchable client dropdown state.
                clientsList: allClients,
                recentClientIds: recentClientIds.map(Number),
                clientSearchTerm: "",
                clientDropdownOpen: false,

                // Quick Add Client modal state.
                isClientModalOpen: false,
                newClient: {
                    name: "",
                    phone: "",
                    city: "",
                    state_id: "",
                    registration_type: "unregistered",
                },

                modals: {
                    form: false,
                },

                // Select Data Options
                clientOptions: allClients.map(c => ({
                    id: c.id,
                    name: c.name
                })),
                statusOptions: @js(
    collect($statuses)
        ->map(function ($label, $val) {
            $value = is_array($label) ? $label['value'] ?? $val : $val;
            $text = is_array($label) ? $label['label'] ?? $value : $label;
            return ['id' => $value, 'name' => $text];
        })
        ->values(),
),
                paymentStatusOptions: [{
                        id: 'outstanding',
                        name: 'Outstanding Due'
                    },
                    {
                        id: 'settled',
                        name: 'Fully Settled'
                    },
                    {
                        id: 'unbilled',
                        name: 'Unbilled'
                    }
                ],

                // Filter models
                filterClientId: "{{ request('client_id') }}",
                filterStatus: "{{ request('status') }}",
                filterPaymentStatus: "{{ request('payment_status') }}",

                form: {
                    id: "",
                    client_id: "",
                    title: "",
                    description: "",
                    status: "draft",
                    start_date: "{{ date('Y-m-d') }}",
                    expected_end_date: "",
                    // Opening charge fields
                    amount: "",
                    tax_rate: "",
                    due_date: "",
                },

                init() {
                    this.checkActiveFilters();
                },

                // Ordered by how recently the client appeared on a project.
                get recentClients() {
                    return this.recentClientIds
                        .map((id) => this.clientsList.find((c) => Number(c.id) === id))
                        .filter(Boolean);
                },

                // Empty search shows recent clients only; typing searches the full list.
                get filteredClients() {
                    const term = this.clientSearchTerm.trim().toLowerCase();

                    if (term === "") {
                        return this.recentClients.length > 0 ? this.recentClients : this.clientsList;
                    }

                    return this.clientsList.filter(
                        (c) =>
                        c.name.toLowerCase().includes(term) ||
                        (c.phone && String(c.phone).includes(term)),
                    );
                },

                selectClient(client) {
                    this.form.client_id = client.id;
                    this.clientSearchTerm = client.name;
                    this.clientDropdownOpen = false;
                },

                resetNewClient() {
                    this.newClient = {
                        name: "",
                        phone: "",
                        city: "",
                        state_id: "",
                        registration_type: "unregistered",
                    };
                },

                async saveQuickClient() {
                    if (!this.newClient.name || !this.newClient.phone) {
                        BizAlert.toast("Please fill all required fields.", "error");
                        return;
                    }

                    if (this.newClient.phone.length !== 10) {
                        BizAlert.toast("Phone number must be exactly 10 digits.", "error");
                        return;
                    }

                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

                    if (!csrfMeta) {
                        BizAlert.toast("Security error: CSRF token missing.", "error");
                        return;
                    }

                    try {
                        BizAlert.loading("Saving client...");

                        const response = await fetch("{{ route('admin.clients.store') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": csrfMeta.content,
                            },
                            body: JSON.stringify(this.newClient),
                        });

                        let data;

                        // A 500 returns an HTML error page, which would throw here.
                        try {
                            data = await response.json();
                        } catch (parseError) {
                            BizAlert.toast("Server error. Please try again.", "error");
                            return;
                        }

                        if (!response.ok) {
                            let message = data.message || "Failed to save the client.";

                            if (data.errors) {
                                message = Object.values(data.errors)[0][0];
                            }

                            BizAlert.toast(message, "error");
                            return;
                        }

                        BizAlert.toast("Client added successfully.", "success");

                        this.isClientModalOpen = false;
                        this.resetNewClient();

                        // Add to the list and to the top of recents, then select it —
                        // a client created from this modal is always the one being used.
                        this.clientsList.push(data.client);
                        this.recentClientIds = [Number(data.client.id), ...this.recentClientIds];
                        this.selectClient(data.client);
                    } catch (error) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    }
                },

                clientNameById(id) {
                    const client = this.clientsList.find((c) => Number(c.id) === Number(id));
                    return client ? client.name : "";
                },

                // --- AJAX Pagination & Filters ---
                requestSeq: 0,
                suspendAutoSubmit: false,

                handlePaginationClick(e) {
                    // "?page=" misses links where page is not the first parameter,
                    // which is every link once a filter is applied.
                    const pageLink = e.target.closest('a[href*="page="]');
                    if (!pageLink) return;

                    e.preventDefault();
                    // The layout's SPA link interceptor listens on document and does
                    // not check defaultPrevented, so without this the same click also
                    // triggers a full page navigation — two requests per click.
                    e.stopPropagation();

                    this.fetchResults(pageLink.href);
                },

                checkActiveFilters() {
                    const form = document.getElementById("project-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    if (this.suspendAutoSubmit) return;

                    const form = document.getElementById("project-filter-form");
                    if (!form) return;

                    const url = new URL(form.action);

                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("project-filter-form");
                    if (!form) return;

                    // Clearing used to dispatch a change event per field, and the
                    // form's @change fired submitForm() while the remaining fields
                    // still held their old values. Those stale requests raced the
                    // clean one and frequently won, so the filter came straight back.
                    this.suspendAutoSubmit = true;

                    this.filterClientId = "";
                    this.filterStatus = "";
                    this.filterPaymentStatus = "";

                    form.querySelectorAll("input[name], select[name]").forEach((el) => {
                        if (el.name !== "_token") el.value = "";
                    });

                    this.suspendAutoSubmit = false;

                    this.fetchResults(form.action);
                },

                fetchResults(url) {
                    const container = document.getElementById("projects-list-container");
                    if (!container) return;

                    // Debounced typing leaves several requests in flight. Only the
                    // newest may paint, so a slow early response can never overwrite
                    // a newer one — or push a stale URL into the address bar.
                    const seq = ++this.requestSeq;

                    container.style.opacity = "0.5";
                    container.style.pointerEvents = "none";

                    fetch(url, {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then((res) => {
                            if (!res.ok) throw new Error("Could not load projects.");
                            return res.text();
                        })
                        .then((html) => {
                            if (seq !== this.requestSeq) return;

                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const fresh = doc.getElementById("projects-list-container");

                            if (!fresh) return;

                            container.innerHTML = fresh.innerHTML;
                            window.history.pushState({}, "", url);

                            this.checkActiveFilters();

                            // The swapped-in rows carry @click bindings, so Alpine has
                            // to walk the new nodes or Edit and Delete stop responding.
                            if (window.Alpine?.initTree) window.Alpine.initTree(container);
                            if (window.initIcons) window.initIcons(container);
                        })
                        .catch(() => {
                            if (seq === this.requestSeq) BizAlert.toast("Could not load projects.", "error");
                        })
                        .finally(() => {
                            if (seq !== this.requestSeq) return;
                            container.style.opacity = "1";
                            container.style.pointerEvents = "auto";
                        });
                },

                // --- CRUD Modals & Logic ---
                openModal(project = null) {
                    this.editing = project;
                    this.clientDropdownOpen = false;
                    // Edit mode must show the existing client's name in the search box,
                    // otherwise it looks like no client is selected.
                    this.clientSearchTerm = project ? this.clientNameById(project.client_id) : "";
                    if (project) {
                        this.form = {
                            id: project.id,
                            client_id: project.client_id,
                            title: project.title,
                            description: project.description || "",
                            status: project.status || "draft",
                            start_date: project.start_date ? project.start_date.split("T")[0] : "",
                            expected_end_date: project.expected_end_date ? project.expected_end_date.split("T")[0] : "",
                            amount: "",
                            tax_rate: "",
                            due_date: "",
                        };
                    } else {
                        this.form = {
                            id: "",
                            client_id: "",
                            title: "",
                            description: "",
                            status: "draft",
                            start_date: "{{ date('Y-m-d') }}",
                            expected_end_date: "",
                            amount: "",
                            tax_rate: "",
                            due_date: "",
                        };
                    }
                    this.modals.form = true;
                },

                closeModal() {
                    this.modals.form = false;
                    this.clientSearchTerm = "";
                    this.clientDropdownOpen = false;
                    this.isClientModalOpen = false;
                    this.resetNewClient();
                    this.editing = null;
                },

                saveProject() {
                    this.isProcessing = true;
                    const url = this.editing ?
                        `{{ route('admin.projects.index') }}/${this.editing.id}` :
                        `{{ route('admin.projects.store') }}`;

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

                            // update() deliberately ignores status — delivery status
                            // has its own endpoint so it reads clearly in the audit
                            // log. Editing it here used to do nothing at all.
                            if (this.editing && this.form.status !== this.editing.status) {
                                await this.updateStatus(this.editing.id, this.form.status);
                            }

                            BizAlert.toast(data.message, "success");

                            // If it's a new project and backend provided a redirect URL (to workspace)
                            if (!this.editing && data.redirect) {
                                window.location.href = data.redirect;
                                return;
                            }

                            this.closeModal();
                            this.submitForm(); // Refresh the list
                        })
                        .catch((error) => {
                            BizAlert.toast(error.message, "error");
                        })
                        .finally(() => {
                            this.isProcessing = false;
                        });
                },

                updateStatus(id, status) {
                    return fetch(`{{ route('admin.projects.index') }}/${id}/status`, {
                        method: "PATCH",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                            Accept: "application/json",
                        },
                        body: JSON.stringify({
                            status
                        }),
                    });
                },

                deleteProject(id) {
                    BizAlert.confirm(
                        "Delete Project?",
                        "This will remove the project completely. If there are unpaid charges, the system will block deletion.",
                        "Yes, delete it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`{{ route('admin.projects.index') }}/${id}`, {
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
