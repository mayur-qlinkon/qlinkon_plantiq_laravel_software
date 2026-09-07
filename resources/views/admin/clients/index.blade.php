@extends ('layouts.admin')

@section('title', 'Clients Management')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        body.modal-open {
            overflow: hidden;
        }
    </style>
@endpush

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Clients</h1>
@endsection

@section('content')
    <div class="pb-10" x-data="clientManager(@js($clients->items()))">
        @if (session('success'))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-green-100 bg-green-50 px-5 py-4 text-sm font-bold text-green-700 shadow-sm">
                <i data-lucide="check-circle" class="h-5 w-5"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div
                class="mb-6 rounded-xl border border-red-100 bg-[#fee2e2] px-5 py-4 text-sm font-bold text-[#ef4444] shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i> Please fix the following errors:
                </div>
                <ul class="list-inside list-disc space-y-1 pl-7 text-xs font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── TOOLBAR (Search, Filters & Actions) ── --}}
        <div class="mb-0 rounded-t-xl border border-b-0 border-gray-100 bg-white p-4 shadow-sm">
            <form id="client-filter-form" method="GET" action="{{ route('admin.clients.index') }}"
                @submit.prevent="submitForm" @change="submitForm"
                class="flex flex-col items-start justify-between gap-4 xl:flex-row xl:items-center">
                {{-- Left Side: Search + Filters --}}
                <div class="flex w-full flex-wrap items-center gap-3 xl:max-w-5xl xl:flex-1">
                    {{-- Search Group (Input + Clear) --}}
                    <div class="flex w-full max-w-md min-w-[250px] flex-1 flex-row items-center gap-2">
                        <div class="relative flex-1">
                            {{-- Stable inline container locking search icon geometry --}}
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <i data-lucide="search" class="text-brand-500 h-4 w-4"></i>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}"
                                @input.debounce.400ms="submitForm" placeholder="Search Name, Phone, Email, City or GSTIN..."
                                class="w-full rounded-lg border border-gray-200 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <button type="button" @click="clearFilters" x-show="hasActiveFilters" x-cloak
                            class="flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-red-50 px-3 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                            title="Clear Filters">
                            <i data-lucide="x" class="h-4 w-4"></i> Clear
                        </button>
                    </div>

                    {{-- Pagination Limit Selection --}}
                    <div class="w-full shrink-0 sm:w-[120px]">
                        <x-custom-select name="per_page" placeholder="50 / page" :options="['50' => '50 / page', '100' => '100 / page', '200' => '200 / page']"
                            selected="{{ request('per_page', 50) }}" />
                    </div>

                    {{-- Status Custom Select --}}
                    <div class="w-full shrink-0 sm:w-[150px]">
                        <x-custom-select name="status" placeholder="All Status" :options="['active' => 'Active', 'inactive' => 'Inactive']"
                            selected="{{ request('status') }}" />
                    </div>

                    {{-- GST Registration Type Custom Select --}}
                    <div class="w-full shrink-0 sm:w-[170px]">
                        <x-custom-select name="registration_type" placeholder="All GST Types" :options="[
                            'registered' => 'Regular',
                            'composition' => 'Composition',
                            'unregistered' => 'Unregistered',
                            'sez' => 'SEZ',
                            'overseas' => 'Overseas',
                        ]"
                            selected="{{ request('registration_type') }}" />
                    </div>
                </div>

                {{-- Right Side: Actions --}}
                <div
                    class="mt-2 flex w-full flex-row flex-wrap items-center justify-start gap-2 xl:mt-0 xl:w-auto xl:justify-end">
                    @if (has_permission('clients.export'))
                        <button type="button" @click="exportCSV()"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold whitespace-nowrap text-gray-600 shadow-sm transition-colors hover:bg-gray-50 md:px-4">
                            <i data-lucide="file-spreadsheet" class="h-4 w-4 text-[#108c2a]"></i> CSV
                        </button>
                        <button type="button" @click="exportPDF()"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold whitespace-nowrap text-gray-600 shadow-sm transition-colors hover:bg-gray-50 md:px-4">
                            <i data-lucide="file-text" class="h-4 w-4 text-red-500"></i> PDF
                        </button>
                    @endif

                    @if (has_permission('clients.create'))
                        <x-import-modal type="clients" />
                    @endif

                    @if (has_permission('clients.create'))
                        <button type="button" @click="openCreate()"
                            class="flex items-center gap-1.5 rounded-lg bg-[#108c2a] px-4 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors hover:bg-green-700 md:px-5">
                            <i data-lucide="plus" class="h-4 w-4"></i> Add Client
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- The ids actually rendered on this page. Selection reads from here
             rather than from a JS copy, so it can never disagree with the rows
             on screen after an AJAX filter or page change. --}}
        <div id="clients-list-container" data-page-ids="{{ $clients->pluck('id')->join(',') }}"
            class="overflow-hidden rounded-b-xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)">
            {{-- Appears only with a selection, so the toolbar does not compete
                 with the filters when nothing is picked. --}}
            @if (has_permission('clients.delete'))
                <div x-show="selectedIds.length > 0" x-cloak
                    class="bg-brand-50/60 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                    <span class="text-[13px] font-bold text-gray-700">
                        <span x-text="selectedIds.length"></span> selected
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="clearSelection()"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Clear
                        </button>
                        <button type="button" @click="bulkDelete()" :disabled="bulkDeleting"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-1.5 text-xs font-bold text-white transition-colors hover:bg-red-700 disabled:opacity-60">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            <span x-text="bulkDeleting ? 'Deleting...' : 'Delete selected'"></span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/80">
                            @if (has_permission('clients.delete'))
                                <th class="w-10 px-4 py-4 text-center">
                                    <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="allSelected"
                                        :indeterminate.camel="someSelected"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                </th>
                            @endif
                            <th
                                class="w-12 px-4 py-4 text-center text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                #
                            </th>
                            <th class="px-6 py-4 text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                Client Details
                            </th>
                            <th class="px-6 py-4 text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                Contact
                            </th>
                            <th class="px-6 py-4 text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                Location
                            </th>
                            <th
                                class="px-6 py-4 text-center text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                Status
                            </th>
                            <th
                                class="px-6 py-4 text-right text-[11px] font-extrabold tracking-wider text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($clients as $client)
                            <tr class="group transition-colors hover:bg-gray-50/50"
                                :class="selectedIds.includes({{ $client->id }}) ? 'bg-brand-50/40' : ''">
                                @if (has_permission('clients.delete'))
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" value="{{ $client->id }}" x-model.number="selectedIds"
                                            class="h-4 w-4 cursor-pointer rounded border-gray-300 text-red-600 focus:ring-red-500" />
                                    </td>
                                @endif
                                <td class="px-4 py-4 text-center text-sm font-bold text-gray-400">
                                    {{ ($clients->currentPage() - 1) * $clients->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="bg-brand-50 border-brand-100 text-brand-600 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm font-bold">
                                            {{ strtoupper(substr($client->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-800">{{ $client->name }}</div>
                                            <div class="mt-0.5 text-[12px] text-gray-400">
                                                {{ $client->email ?? 'No email added' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                                            <i data-lucide="phone" class="h-3.5 w-3.5 text-gray-400"></i>
                                            {{ $client->phone ?? 'N/A' }}
                                        </div>
                                        @if ($client->company_name)
                                            <div class="flex items-center gap-2 text-[12px] text-gray-500">
                                                <i data-lucide="building-2" class="h-3.5 w-3.5 text-gray-300"></i>
                                                {{ $client->company_name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        @if ($client->city)
                                            <span class="text-[14px] font-medium text-gray-500">
                                                {{ $client->city }}
                                            </span>
                                        @else
                                            <span class="text-xs font-medium text-gray-400 italic">-</span>
                                        @endif
                                        @if ($client->state)
                                            <span
                                                class="text-[11px] font-medium text-gray-500">{{ $client->state->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($client->is_active)
                                        <span
                                            class="rounded-md bg-[#dcfce7] px-3 py-1 text-[10px] font-bold tracking-wider text-[#16a34a] uppercase">Active</span>
                                    @else
                                        <span
                                            class="rounded-md bg-gray-200 px-3 py-1 text-[10px] font-bold tracking-wider text-gray-500 uppercase">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        @if (has_permission('clients.update'))
                                            <button type="button" @click="openEdit({{ $client->id }})"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100 hover:text-blue-700"
                                                title="Edit Client">
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if (has_permission('clients.delete'))
                                            <button type="button"
                                                @click="openDelete({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-colors hover:bg-red-100 hover:text-red-600"
                                                title="Delete Client">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="users" class="mb-3 h-12 w-12 text-gray-300"></i>
                                        <p class="text-sm font-medium">No clients found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="divide-y divide-gray-50 border-t border-gray-50 bg-white md:hidden">
                @forelse ($clients as $client)
                    <div class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50">
                        {{-- Header: Avatar, Name & Status --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-6 w-auto min-w-[24px] shrink-0 items-center justify-center rounded bg-gray-100 px-1.5 text-[10px] font-bold text-gray-500">
                                    #{{ ($clients->currentPage() - 1) * $clients->perPage() + $loop->iteration }}
                                </div>
                                <div
                                    class="bg-brand-50 border-brand-100 text-brand-600 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm font-bold">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-[14px] font-bold text-gray-900">{{ $client->name }}</p>
                                    <p class="mt-0.5 truncate text-[11px] text-gray-500">
                                        {{ $client->email ?? 'No email added' }}</p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                @if ($client->is_active)
                                    <span
                                        class="rounded bg-[#dcfce7] px-2 py-0.5 text-[9px] font-extrabold tracking-wider text-[#16a34a] uppercase">Active</span>
                                @else
                                    <span
                                        class="rounded bg-gray-200 px-2 py-0.5 text-[9px] font-extrabold tracking-wider text-gray-500 uppercase">Inactive</span>
                                @endif
                            </div>
                        </div>

                        {{-- Details: Phone & City --}}
                        <div
                            class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-1.5 text-sm font-medium text-gray-600">
                                    <i data-lucide="phone" class="h-3.5 w-3.5 text-gray-400"></i>
                                    {{ $client->phone ?? 'N/A' }}
                                </div>
                                @if ($client->company_name)
                                    <div class="flex items-center gap-1.5 text-[11px] text-gray-500">
                                        <i data-lucide="building-2" class="h-3 w-3 text-gray-400"></i>
                                        {{ $client->company_name }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                @if ($client->city)
                                    <span
                                        class="inline-flex items-center rounded-md border border-blue-100 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-600">
                                        {{ $client->city }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-medium text-gray-400 italic">-</span>
                                @endif
                                @if ($client->state)
                                    <span class="text-[10px] font-medium text-gray-500">{{ $client->state->name }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-1 flex items-center justify-end gap-2 border-t border-gray-50 pt-1">
                            @if (has_permission('clients.update'))
                                <button type="button" @click="openEdit({{ $client->id }})"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100 hover:text-blue-700"
                                    title="Edit Client">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                            @endif

                            @if (has_permission('clients.delete'))
                                <button type="button"
                                    @click="openDelete({{ $client->id }}, '{{ addslashes($client->name) }}')"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition-colors hover:bg-red-100 hover:text-red-600"
                                    title="Delete Client">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-sm text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="users" class="mb-3 h-12 w-12 text-gray-300 opacity-50"></i>
                            <p class="font-medium text-gray-500">No clients found matching your criteria.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($clients->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">{{ $clients->links() }}</div>
            @endif
        </div>

        {{-- Fixed: Shifted scroll handling to outer layout container to support long fields naturally across viewports --}}
        <div x-cloak x-show="showCreateModal"
            class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto p-4 sm:p-6 md:items-center">
            <div class="absolute fixed inset-0 bg-black/40 backdrop-blur-sm" x-show="showCreateModal"
                x-transition.opacity></div>
            <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" x-show="showCreateModal"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                <div
                    class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                    <h3 class="text-[16px] font-bold tracking-tight text-gray-800">Add New Client</h3>
                    <button type="button" @click="closeAll()"
                        class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form action="{{ route('admin.clients.store') }}" method="POST"
                    @submit.prevent="saveClient($event, false)">
                    @csrf
                    {{-- Fixed: Removed max-h restriction so container boundaries expand to fit the child inputs naturally --}}
                    <div class="grid grid-cols-1 gap-4 overflow-visible p-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Full Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. Rahul Sharma"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Phone Number <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="phone" required minlength="10" maxlength="10"
                                placeholder="e.g. 9876543210"
                                @input="$event.target.value = $event.target.value.replace(/[^0-9]/g, '')"
                                :class="{ 'border-red-500 focus:border-red-500': validationErrors.phone }"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                            <p class="mt-1 text-[10px] font-medium text-gray-400" x-show="!validationErrors.phone">10
                                digits only. Must be unique.</p>
                            <p class="mt-1 text-[11px] font-bold text-red-500" x-show="validationErrors.phone"
                                x-text="validationErrors.phone?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Email Address</label>
                            <input type="email" name="email" placeholder="e.g. client@example.com"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Company Name</label>
                            <input type="text" name="company_name" placeholder="e.g. Tech Corp"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">GST Number</label>
                            <input type="text" name="gst_number" placeholder="e.g. 22AAAAA0000A1Z5"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Registration Type</label>
                            <x-custom-select name="registration_type" placeholder="Select Registration Type"
                                :options="[
                                    'registered' => 'Regular',
                                    'composition' => 'Composition',
                                    'unregistered' => 'Unregistered',
                                    'sez' => 'SEZ',
                                    'overseas' => 'Overseas',
                                ]" selected="registered" />
                        </div>

                        <div class="pt-2 sm:col-span-2">
                            <button type="button" @click="showExtras = !showExtras"
                                class="flex items-center gap-2 text-sm font-bold text-[#108c2a] transition-colors hover:text-green-700">
                                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform duration-200"
                                    :class="showExtras ? 'rotate-180' : ''"></i>
                                <span
                                    x-text="showExtras ? 'Hide Additional Details' : 'Add Address & Notes (Optional)'"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2" x-show="showExtras" x-collapse
                            x-cloak>
                            <div class="border-t border-gray-100 pt-4 sm:col-span-2">
                                <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Address Details</label>
                                {{-- Textarea, not an input: the column is TEXT with
                                     no length cap, and a single-line field hid
                                     everything past the first few words while the
                                     user was still typing the address. --}}
                                <textarea name="address" rows="2" placeholder="Street Address"
                                    class="focus:border-brand-500 mb-3 w-full resize-y rounded-md border border-gray-300 px-3.5 py-2.5 text-sm leading-relaxed transition-all outline-none"></textarea>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" name="city" placeholder="City"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />

                                    <div class="w-full shrink-0">
                                        <x-custom-select name="state_id" placeholder="Select State" :options="collect($states)->pluck('name', 'id')->toArray()"
                                            selected="" />
                                    </div>
                                    <input type="text" name="zip_code" placeholder="Zip Code"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                                    <input type="text" name="country" value="India" placeholder="Country"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Notes</label>
                                <textarea name="notes" rows="2" placeholder="Any client specifics..."
                                    class="focus:border-brand-500 w-full resize-none rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none"></textarea>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                class="relative inline-flex w-fit cursor-pointer items-center rounded-xl border border-gray-100 bg-gray-50 p-3 pr-5">
                                <input type="checkbox" name="is_active" value="1" x-model="clientForm.is_active"
                                    class="peer sr-only" />
                                <div
                                    class="peer relative h-6 w-11 rounded-full bg-gray-300 peer-checked:bg-[#108c2a] peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                                </div>
                                <span class="ms-3 text-sm font-bold text-gray-700">Active Client Account</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                        <button type="button" @click="closeAll()"
                            class="rounded-md border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-brand-500 hover:bg-brand-600 rounded-md px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors">
                            Save Client
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Fixed: Shifted scroll handling to outer layout container to support long fields naturally across viewports --}}
        <div x-cloak x-show="showEditModal"
            class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto p-4 sm:p-6 md:items-center">
            <div class="absolute fixed inset-0 bg-black/40 backdrop-blur-sm" x-show="showEditModal" x-transition.opacity>
            </div>
            <div class="relative my-auto w-full max-w-2xl rounded-xl bg-white shadow-2xl" x-show="showEditModal"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0">
                <div
                    class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                    <h3 class="text-[16px] font-bold tracking-tight text-gray-800">Edit Client</h3>
                    <button type="button" @click="closeAll()"
                        class="text-gray-400 transition-colors hover:text-red-500">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form :action="`/admin/clients/${clientForm.id}`" method="POST"
                    @submit.prevent="saveClient($event, true)">
                    @csrf
                    @method ('PUT')
                    {{-- Fixed: Removed max-h restriction so container boundaries expand to fit the child inputs naturally --}}
                    <div class="grid grid-cols-1 gap-4 overflow-visible p-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Full Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="clientForm.name" required
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Phone Number <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="phone" x-model="clientForm.phone" required minlength="10"
                                maxlength="10"
                                @input="
                                    $event.target.value = $event.target.value.replace(/[^0-9]/g, '');
                                    clientForm.phone = $event.target.value;
                                "
                                :class="{ 'border-red-500 focus:border-red-500': validationErrors.phone }"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                            <p class="mt-1 text-[11px] font-bold text-red-500" x-show="validationErrors.phone"
                                x-text="validationErrors.phone?.[0]"></p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Email Address</label>
                            <input type="email" name="email" x-model="clientForm.email"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Company Name</label>
                            <input type="text" name="company_name" x-model="clientForm.company_name"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">GST Number</label>
                            <input type="text" name="gst_number" x-model="clientForm.gst_number"
                                class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Registration Type</label>
                            <x-custom-select name="registration_type" id="edit-client-registration-type"
                                placeholder="Select Registration Type" :options="[
                                    'registered' => 'Regular',
                                    'composition' => 'Composition',
                                    'unregistered' => 'Unregistered',
                                    'sez' => 'SEZ',
                                    'overseas' => 'Overseas',
                                ]" selected="" />
                        </div>
                        <div class="pt-2 sm:col-span-2">
                            <button type="button" @click="showExtras = !showExtras"
                                class="flex items-center gap-2 text-sm font-bold text-[#108c2a] transition-colors hover:text-green-700">
                                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform duration-200"
                                    :class="showExtras ? 'rotate-180' : ''"></i>
                                <span
                                    x-text="showExtras ? 'Hide Additional Details' : 'Add Address & Notes (Optional)'"></span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2" x-show="showExtras" x-collapse
                            x-cloak>
                            <div class="border-t border-gray-100 pt-4 sm:col-span-2">
                                <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Address Details</label>
                                {{-- Same change as the create modal — both must
                                     match, or an address entered in one looks
                                     truncated in the other. --}}
                                <textarea name="address" x-model="clientForm.address" rows="2" placeholder="Street Address"
                                    class="focus:border-brand-500 mb-3 w-full resize-y rounded-md border border-gray-300 px-3.5 py-2.5 text-sm leading-relaxed transition-all outline-none"></textarea>

                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" name="city" x-model="clientForm.city" placeholder="City"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />

                                    <div class="w-full shrink-0">
                                        <x-custom-select name="state_id" id="edit-client-state-id"
                                            placeholder="Select State" :options="collect($states)->pluck('name', 'id')->toArray()" selected="" />
                                    </div>
                                    <input type="text" name="zip_code" x-model="clientForm.zip_code"
                                        placeholder="Zip Code"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                                    <input type="text" name="country" x-model="clientForm.country"
                                        placeholder="Country"
                                        class="focus:border-brand-500 w-full rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none" />
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-[12px] font-bold text-gray-700">Notes</label>
                                <textarea name="notes" x-model="clientForm.notes" rows="2"
                                    class="focus:border-brand-500 w-full resize-none rounded-md border border-gray-300 px-3.5 py-2.5 text-sm transition-all outline-none"></textarea>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                class="relative inline-flex w-fit cursor-pointer items-center rounded-xl border border-gray-100 bg-gray-50 p-3 pr-5">
                                <input type="checkbox" name="is_active" value="1" x-model="clientForm.is_active"
                                    class="peer sr-only" />
                                <div
                                    class="peer relative h-6 w-11 rounded-full bg-gray-300 peer-checked:bg-[#108c2a] peer-focus:outline-none after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white">
                                </div>
                                <span class="ms-3 text-sm font-bold text-gray-700">Active Client Account</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                        <button type="button" @click="closeAll()"
                            class="rounded-md border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-brand-500 hover:bg-brand-600 rounded-md px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors">
                            Update Client
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" x-show="showDeleteModal" x-transition.opacity>
            </div>
            <div class="relative w-full max-w-sm overflow-hidden rounded-xl bg-white text-center shadow-2xl"
                x-show="showDeleteModal" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                <div class="p-6 pt-8">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50">
                        <i data-lucide="alert-triangle" class="h-8 w-8 text-red-500"></i>
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-800">Delete Client?</h3>
                    <p class="text-sm text-gray-500">Are you sure you want to delete <strong class="text-gray-800"
                            x-text="deleteForm.name"></strong>? This action cannot be undone.</p>
                </div>

                <div class="flex justify-center gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4">
                    <button type="button" @click="closeAll()"
                        class="rounded-md border border-gray-200 bg-white px-6 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50">
                        Cancel
                    </button>
                    <form :action="`/admin/clients/${deleteForm.id}`" method="POST"
                        @submit="BizAlert.loading('Deleting...')">
                        @csrf
                        @method ('DELETE')
                        <button type="submit"
                            class="rounded-md bg-red-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-red-600">
                            Yes, Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function clientManager(allClientsData) {
            return {
                allClients: allClientsData,
                showCreateModal: false,
                showEditModal: false,
                showDeleteModal: false,
                validationErrors: {},
                showExtras: false,

                // Bulk selection. Holds ids from the current page only —
                // pagination replaces the rows, so a stale id could otherwise
                // delete something the user can no longer see.
                selectedIds: [],
                bulkDeleting: false,

                // Bumped whenever the list is swapped by AJAX, purely to make
                // the getters below re-evaluate — Alpine cannot track the DOM
                // attribute they read.
                pageVersion: 0,

                get pageIds() {
                    this.pageVersion;

                    const raw = document.getElementById("clients-list-container")?.dataset.pageIds ?? "";

                    return raw === "" ? [] : raw.split(",").map(Number);
                },

                get allSelected() {
                    // every(), not a length comparison: a leftover id from a
                    // previous page would otherwise make the counts match and
                    // tick the header while a visible row sits unchecked.
                    return this.pageIds.length > 0 && this.pageIds.every((id) => this.selectedIds.includes(id));
                },

                get someSelected() {
                    return this.selectedIds.length > 0 && !this.allSelected;
                },

                toggleAll(checked) {
                    this.selectedIds = checked ? [...this.pageIds] : [];
                },

                clearSelection() {
                    this.selectedIds = [];
                },

                async bulkDelete() {
                    if (this.selectedIds.length === 0) return;

                    const count = this.selectedIds.length;

                    const result = await BizAlert.confirm(
                        "Delete " + count + " client(s)?",
                        "This cannot be undone. Clients linked to invoices or quotations may be skipped.",
                        "Yes, delete",
                    );

                    if (!result.isConfirmed) return;

                    this.bulkDeleting = true;

                    try {
                        const res = await fetch("{{ route('admin.clients.bulk-destroy') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({
                                ids: this.selectedIds
                            }),
                        });

                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            BizAlert.toast(data.message || "Could not delete the selected clients.", "error");
                            return;
                        }

                        BizAlert.toast(data.message, "success");
                        this.clearSelection();
                        setTimeout(() => window.location.reload(), 700);
                    } catch (e) {
                        BizAlert.toast("Network error. Please try again.", "error");
                    } finally {
                        this.bulkDeleting = false;
                    }
                },

                // SPA-Safe Search & Filter Logic
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    window.submitClientForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("client-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([k, v]) => {
                        if (k === "per_page") return false;
                        return v && String(v).trim() !== "";
                    });
                },

                submitForm() {
                    const form = document.getElementById("client-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    if (typeof BizAlert !== 'undefined') {
                        BizAlert.loading("Clearing filters...");
                    }
                    window.location.href = "{{ route('admin.clients.index') }}";
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("clients-list-container");
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
                            const newContainer = doc.getElementById("clients-list-container");

                            if (newContainer) {
                                targetContainer.innerHTML = newContainer.innerHTML;

                                // Carry the new page's ids across, then drop any
                                // selection that is no longer on screen — bulk
                                // delete must never act on invisible rows.
                                targetContainer.dataset.pageIds = newContainer.dataset.pageIds ?? "";
                                this.pageVersion++;
                                this.selectedIds = this.selectedIds.filter((id) => this.pageIds.includes(id));
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

                /// Fully expanded client state mapped to DB schema
                clientForm: {
                    id: "",
                    name: "",
                    phone: "",
                    email: "",
                    company_name: "",
                    gst_number: "",
                    registration_type: "registered",
                    address: "",
                    city: "",
                    state_id: "",
                    zip_code: "",
                    country: "",
                    notes: "",
                    is_active: true,
                },
                deleteForm: {
                    id: "",
                    name: "",
                },

                openCreate() {
                    document.body.classList.add("modal-open");
                    this.closeAll();
                    this.showExtras = false;
                    // Clear out data just in case an edit was cancelled previously
                    this.clientForm = {
                        id: "",
                        name: "",
                        phone: "",
                        email: "",
                        company_name: "",
                        gst_number: "",
                        registration_type: "registered",
                        address: "",
                        city: "",
                        state_id: "",
                        zip_code: "",
                        country: "India",
                        notes: "",
                        is_active: true,
                    };
                    this.showCreateModal = true;
                },

                openEdit(id) {
                    this.closeAll();
                    let client = this.allClients.find((c) => c.id === id);
                    if (!client) return;
                    this.showExtras = !!(client.address || client.city || client.state_id || client.zip_code || client
                        .notes);
                    this.clientForm = {
                        id: client.id,
                        name: client.name,
                        phone: client.phone || "",
                        email: client.email || "",
                        company_name: client.company_name || "",
                        gst_number: client.gst_number || "",
                        registration_type: client.registration_type || "registered",
                        address: client.address || "",
                        city: client.city || "",
                        state_id: client.state_id || "",
                        zip_code: client.zip_code || "",
                        country: client.country || "India",
                        notes: client.notes || "",
                        is_active: client.is_active === true || client.is_active === 1,
                    };
                    this.showEditModal = true;

                    // Sync the active record parameters to the newly opened custom components cleanly
                    this.$nextTick(() => {
                        const regTypeSelect = document.getElementById("edit-client-registration-type");
                        if (regTypeSelect) {
                            regTypeSelect.value = client.registration_type || "registered";
                            regTypeSelect.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                        }
                        const stateSelect = document.getElementById("edit-client-state-id");
                        if (stateSelect) {
                            stateSelect.value = client.state_id || "";
                            stateSelect.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                        }
                    });
                },

                openDelete(id, name) {
                    this.closeAll();
                    this.deleteForm = {
                        id: id,
                        name: name,
                    };
                    this.showDeleteModal = true;
                },

                closeAll() {
                    document.body.classList.remove("modal-open");
                    this.showCreateModal = false;
                    this.showEditModal = false;
                    this.showDeleteModal = false;
                    this.validationErrors = {};
                },
                async saveClient(event, isEdit = false) {
                    BizAlert.loading(isEdit ? "Updating..." : "Saving...");
                    this.validationErrors = {};
                    const formElement = event.target;
                    const formData = new FormData(formElement);
                    const url = isEdit ? `/admin/clients/${this.clientForm.id}` : "{{ route('admin.clients.store') }}";
                    try {
                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: formData,
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            if (res.status === 422) {
                                this.validationErrors = data.errors || {};
                                if (typeof swal !== "undefined") swal.close();
                            } else {
                                if (typeof swal !== "undefined") swal.close();
                                BizAlert.toast(data.message || "Something went wrong", "error");
                            }
                        } else {
                            window.location.reload();
                        }
                    } catch (err) {
                        if (typeof swal !== "undefined") swal.close();
                        BizAlert.toast("Network error", "error");
                    }
                },
                // --- EXPORT ALL DATA TO CSV (BACKEND) ---
                exportCSV() {
                    BizAlert.loading("Generating CSV...");

                    const form = document.getElementById("client-filter-form");
                    const url = new URL("{{ route('admin.clients.download-csv') }}", window.location.origin);

                    if (form) {
                        new FormData(form).forEach((v, k) => {
                            if (v && String(v).trim() !== "") {
                                url.searchParams.set(k, v);
                            }
                        });
                    }

                    window.location.href = url.toString();

                    setTimeout(() => {
                        swal.close();
                    }, 2000);
                },

                // --- EXPORT SPECIFIC DATA TO PDF (BACKEND) ---
                exportPDF() {
                    BizAlert.loading("Generating PDF...");

                    const form = document.getElementById("client-filter-form");
                    const url = new URL("{{ route('admin.clients.download-pdf') }}", window.location.origin);

                    // Attach current search/filter parameters to the PDF request
                    if (form) {
                        new FormData(form).forEach((v, k) => {
                            if (v && String(v).trim() !== "") {
                                url.searchParams.set(k, v);
                            }
                        });
                    }

                    // Navigate to the download route
                    window.location.href = url.toString();

                    // Close the loading alert after a short delay (gives the browser time to start the download)
                    setTimeout(() => {
                        swal.close(); // Assuming BizAlert uses SweetAlert under the hood
                    }, 2000);
                },
            };
        }
    </script>
@endpush
