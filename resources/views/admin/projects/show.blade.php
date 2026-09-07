@extends ('layouts.admin')

@section('title', $project->title)

@section('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Project Workspace</h1>
@endsection

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .modal-scroll {
            max-height: calc(100vh - 6rem);
            overflow-y: auto;
        }
    </style>
@endpush

@section('content')
    <div class="space-y-5 pb-10" x-data="projectWorkspace(@js($project), @js($summary), @js($clientCredit), @js($catalog), @js($cycles), @js($payments))" x-cloak>
        {{-- WORKSPACE HEADER --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('admin.projects.index') }}"
                        class="mb-2 inline-flex items-center text-xs font-semibold text-gray-500 transition-colors hover:text-[#108c2a]">
                        <i data-lucide="arrow-left" class="mr-1 h-3.5 w-3.5"></i> Back to Projects
                    </a>
                    <h1 class="text-2xl font-black text-gray-900" x-text="project.title"></h1>
                    <div class="mt-1.5 flex items-center gap-2 text-sm text-gray-500">
                        <span>Client: <strong class="text-gray-700"
                                x-text="project.client?.name || 'Unknown Client'"></strong></span>
                        <span class="text-gray-300">|</span>
                        <span>Status:
                            <span
                                class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-extrabold text-gray-600 uppercase"
                                x-text="project.status.replace('_', ' ')"></span></span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (has_permission('project_client_services.create'))
                        <button @click="openModal('service')"
                            class="flex items-center rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-indigo-700">
                            <i data-lucide="plus" class="mr-1.5 h-4 w-4"></i> Assign Service
                        </button>
                    @endif
                    @if (has_permission('project_payments.create'))
                        <button @click="openPaymentModal()"
                            class="flex items-center rounded-lg bg-[#108c2a] px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-green-700">
                            <i data-lucide="wallet" class="mr-1.5 h-4 w-4"></i> Receive Payment
                        </button>
                    @endif
                    @if (has_permission('project_charges.create'))
                        <button @click="openModal('charge')"
                            class="flex items-center rounded-lg bg-gray-900 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-gray-800">
                            <i data-lucide="receipt-indian-rupee" class="mr-1.5 h-4 w-4"></i> Add Charge
                        </button>
                    @endif
                </div>
            </div>

            {{-- METRIC SUMMARY CARDS --}}
            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-5">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3.5">
                    <div class="mb-1 text-[10px] font-bold tracking-wider text-gray-500 uppercase">Total Charged</div>
                    <div class="text-lg font-black text-gray-900" x-text="formatMoney(summary.charged)"></div>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3.5">
                    <div class="mb-1 text-[10px] font-bold tracking-wider text-emerald-600 uppercase">
                        Paid / Allocated
                    </div>
                    <div class="text-lg font-black text-emerald-700" x-text="formatMoney(summary.paid)"></div>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-3.5">
                    <div class="mb-1 text-[10px] font-bold tracking-wider text-amber-600 uppercase">Written Off</div>
                    <div class="text-lg font-black text-amber-700" x-text="formatMoney(summary.written_off)"></div>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-3.5">
                    <div class="mb-1 text-[10px] font-bold tracking-wider text-red-600 uppercase">Outstanding Due</div>
                    <div class="text-lg font-black text-red-700" x-text="formatMoney(summary.outstanding)"></div>
                </div>
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-3.5">
                    <div class="mb-1 text-[10px] font-bold tracking-wider text-blue-600 uppercase">
                        Client Credit Avail.
                    </div>
                    <div class="text-lg font-black text-blue-700" x-text="formatMoney(clientCredit)"></div>
                </div>
            </div>
        </div>

        {{-- MAIN GRID --}}
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            {{-- CLIENT SERVICES — rendered second. Order is set with utilities
                 instead of moving the markup, so the two panels can be swapped
                 back without touching 90 lines of template. --}}
            <div class="order-2 flex flex-col rounded-xl border border-gray-100 bg-white shadow-sm">
                <div
                    class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50/50 px-5 py-4">
                    <div>
                        <h2 class="flex items-center text-sm font-bold text-gray-800">
                            <i data-lucide="boxes" class="mr-2 h-4 w-4 text-indigo-500"></i> Client Services
                        </h2>
                        <p class="mt-0.5 text-[10px] text-gray-500">Recurring or term-based entitlements.</p>
                    </div>
                </div>
                <div class="max-h-[600px] flex-1 space-y-4 overflow-y-auto p-5">
                    <template x-for="cs in project.client_services" :key="cs.id">
                        <div
                            class="rounded-xl border border-gray-100 p-4 transition-colors hover:border-indigo-100 hover:bg-indigo-50/30">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-bold text-gray-900"
                                        x-text="cs.name || (cs.service ? cs.service.name : 'Custom Service')"></div>
                                    {{-- The current billing period, not the sale
                                         date. cs.start_date / cs.end_date never
                                         existed on this table, so every card read
                                         "No start → Ongoing". --}}
                                    <div class="mt-1 flex items-center text-xs font-semibold text-gray-500">
                                        <i data-lucide="calendar" class="mr-1.5 h-3.5 w-3.5"></i>
                                        <span x-text="formatDate(cs.current_period_start) || 'No period'"></span>
                                        <span class="mx-1">&rarr;</span>
                                        <span x-text="formatDate(cs.current_period_end) || 'Ongoing'"></span>
                                    </div>
                                    <div class="mt-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                        x-text="cycleLabel(cs.billing_cycle)"></div>
                                </div>
                                <span :class="serviceStatusClass(cs.status)"
                                    class="rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase"
                                    x-text="cs.status"></span>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3">
                                {{-- The price is pre-tax while the charge it
                                     raises is not, so the tax has to be named
                                     here or the two numbers look unrelated. --}}
                                <div class="text-[11px] font-semibold text-gray-500">
                                    Period Price:
                                    <strong class="text-xs text-gray-900" x-text="formatMoney(cs.price)"></strong>
                                    <span x-show="Number(cs.tax_rate) > 0" x-cloak class="text-gray-400">
                                        + <span x-text="Number(cs.tax_rate)"></span>% GST =
                                        <strong class="text-gray-700"
                                            x-text="formatMoney(withTax(cs.price, cs.tax_rate))"></strong>
                                    </span>
                                </div>

                                @if (has_permission('project_client_services.renew'))
                                    <button x-show="isRenewable(cs)" @click="openRenewModal(cs)"
                                        class="flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 transition-colors hover:bg-indigo-100">
                                        <i data-lucide="rotate-cw" class="mr-1.5 h-3.5 w-3.5"></i> Renew Cycle
                                    </button>
                                @endif
                            </div>
                        </div>
                    </template>
                    <div x-show="!project.client_services || project.client_services.length === 0"
                        class="py-12 text-center">
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
                            <i data-lucide="package-open" class="h-6 w-6 text-gray-400"></i>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">No services assigned yet.</p>
                    </div>
                </div>
            </div>

            {{-- FINANCIAL CHARGES — rendered first (left column). --}}
            <div class="order-1 flex flex-col rounded-xl border border-gray-100 bg-white shadow-sm">
                <div
                    class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-gray-50/50 px-5 py-4">
                    <div>
                        <h2 class="flex items-center text-sm font-bold text-gray-800">
                            <i data-lucide="receipt" class="mr-2 h-4 w-4 text-red-500"></i> Financial Charges
                        </h2>
                        <p class="mt-0.5 text-[10px] text-gray-500">Money owed by the client for this project.</p>
                    </div>
                </div>
                <div class="max-h-[600px] flex-1 space-y-4 overflow-y-auto p-5">
                    <template x-for="ch in project.charges" :key="ch.id">
                        <div :class="cycleCardClass(ch)" class="rounded-xl border border-l-4 p-4 transition-colors">
                            {{-- Renewals of one service are identical apart from
                                 their date range, so the position in the sequence
                                 and its state are stated up front. Without this
                                 the only way to tell old from current is to read
                                 and compare every date. --}}
                            <div x-show="cycleMeta(ch).total > 1" x-cloak class="mb-2.5 flex items-center gap-2">
                                <span :class="cycleBadgeClass(ch)"
                                    class="rounded-md border px-2 py-0.5 text-[10px] font-extrabold tracking-wider uppercase"
                                    x-text="'Cycle ' + cycleMeta(ch).index + ' / ' + cycleMeta(ch).total"></span>
                                <span :class="cycleStateTextClass(ch)"
                                    class="text-[10px] font-extrabold tracking-wider uppercase"
                                    x-text="cycleStateLabel(ch)"></span>
                            </div>

                            <div class="flex items-start justify-between">
                                <div>
                                    <div :class="cycleMeta(ch).state === 'past' ? 'text-gray-500' : 'text-gray-900'"
                                        class="text-sm font-bold" x-text="ch.title"></div>
                                    <div class="mt-1 text-[11px] font-semibold text-gray-500" x-show="ch.period_start">
                                        <span x-text="formatDate(ch.period_start)"></span>
                                        <span x-show="ch.period_end">
                                            &rarr; <span x-text="formatDate(ch.period_end)"></span></span>
                                    </div>
                                    <div class="mt-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                        x-text="ch.type.replace('_', ' ')"></div>
                                </div>
                                {{-- Read the derived status rather than
                                     recomputing paid-vs-due here, or the screen
                                     can never show Partially Paid, Written Off
                                     or Cancelled. --}}
                                <span :class="chargeStatusClass(ch.status)"
                                    class="rounded-md border px-2 py-0.5 text-[10px] font-extrabold uppercase"
                                    x-text="ch.status.replace('_', ' ')"></span>
                            </div>

                            {{-- Without this line a ₹6,000 service silently
                                 becomes a ₹7,080 bill and the screen never says
                                 why. --}}
                            <div x-show="Number(ch.tax_amount) > 0 || Number(ch.discount_amount) > 0" x-cloak
                                class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-gray-500">
                                <span x-text="formatMoney(ch.subtotal)"></span>
                                <span x-show="Number(ch.discount_amount) > 0">
                                    − <span x-text="formatMoney(ch.discount_amount)"></span> discount
                                </span>
                                <span x-show="Number(ch.tax_amount) > 0">
                                    + <span x-text="formatMoney(ch.tax_amount)"></span> GST (<span
                                        x-text="Number(ch.tax_rate)"></span>%)
                                </span>
                                <span class="font-bold text-gray-700">
                                    = <span x-text="formatMoney(ch.total_amount)"></span>
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2 rounded-lg border border-gray-100 bg-gray-50 p-2.5">
                                <div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">Total Billed</div>
                                    <strong class="text-xs text-gray-800" x-text="formatMoney(ch.total_amount)"></strong>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">Paid</div>
                                    <strong class="text-xs text-[#108c2a]" x-text="formatMoney(ch.paid_amount)"></strong>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">Due</div>
                                    <strong class="text-xs text-red-600" x-text="formatMoney(chargeDue(ch))"></strong>
                                </div>
                            </div>

                            <div x-show="ch.written_off_amount > 0" x-cloak
                                class="mt-2 text-[10px] font-bold text-amber-600 uppercase">
                                Written off: <span x-text="formatMoney(ch.written_off_amount)"></span>
                            </div>

                            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2">
                                <button @click="toggleDetails('charge-' + ch.id)"
                                    class="text-[11px] font-bold text-gray-500 hover:text-gray-800"
                                    x-text="expanded['charge-' + ch.id] ? 'Hide details' : 'Details'"></button>

                                <div class="flex items-center gap-2">
                                    @if (has_permission('project_charges.update'))
                                        <button x-show="!isChargeLocked(ch)" @click="openEditChargeModal(ch)"
                                            class="rounded-lg bg-gray-100 px-3 py-1 text-[11px] font-bold text-gray-600 transition-colors hover:bg-gray-200">
                                            Edit
                                        </button>
                                    @endif

                                    @if (has_permission('project_charges.cancel'))
                                        <button x-show="!isChargeLocked(ch)" @click="openCancelChargeModal(ch)"
                                            class="rounded-lg bg-red-50 px-3 py-1 text-[11px] font-bold text-red-600 transition-colors hover:bg-red-100">
                                            Cancel
                                        </button>
                                    @endif

                                    @if (has_permission('project_payments.allocate'))
                                        <button x-show="chargeDue(ch) > 0 && availablePayments.length > 0"
                                            @click="openAllocateModal(ch)"
                                            class="rounded-lg bg-indigo-50 px-3 py-1 text-[11px] font-bold text-indigo-700 transition-colors hover:bg-indigo-100">
                                            Allocate Payment
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div x-show="expanded['charge-' + ch.id]" x-cloak class="mt-2 space-y-1">
                                <div x-show="ch.allocations.length === 0" class="text-[11px] text-gray-500">
                                    Nothing applied to this charge yet.
                                </div>
                                <template x-for="a in ch.allocations" :key="a.id">
                                    <div class="flex items-center justify-between border-b border-gray-100 py-1.5 text-[11px] last:border-0"
                                        :class="a.is_reversed ? 'text-gray-400 line-through' : 'text-gray-700'">
                                        <span class="truncate pr-3 font-semibold">
                                            <span
                                                x-text="
                                                    a.kind === 'write_off'
                                                        ? 'Written off'
                                                        : a.payment?.reference || a.payment?.payment_number || 'Payment'
                                                "></span>
                                        </span>
                                        <span class="shrink-0 font-bold" x-text="formatMoney(a.amount)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="!project.charges || project.charges.length === 0" class="py-12 text-center">
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
                            <i data-lucide="receipt" class="h-6 w-6 text-gray-400"></i>
                        </div>
                        <p class="text-sm font-semibold text-gray-500">No charges raised yet.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- CLIENT PAYMENTS --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-5 py-4">
                <div>
                    <h2 class="flex items-center text-sm font-bold text-gray-800">
                        <i data-lucide="wallet" class="mr-2 h-4 w-4 text-[#108c2a]"></i> Client Payments
                    </h2>
                    <p class="mt-0.5 text-[10px] text-gray-500">All payments from <strong
                            x-text="project.client?.name || 'Unknown Client'"></strong>, across every project — one payment
                        can settle charges on more than one. Open a row to see what it settled.</p>
                </div>
                @if (has_permission('project_payments.create'))
                    <button @click="openPaymentModal()" class="text-[11px] font-bold text-[#108c2a] hover:underline">
                        + Receive Payment
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Transaction</th>
                            <th class="px-5 py-3 text-right">Received</th>
                            <th class="px-5 py-3 text-right">Allocated</th>
                            <th class="px-5 py-3 text-right">Available</th>
                            <th class="px-5 py-3 text-right">Details</th>
                        </tr>
                    </thead>

                    {{-- One tbody per payment. x-for renders a single root
                         element, and each payment needs two rows: the summary
                         and its expandable allocation list. --}}
                    <template x-for="p in payments" :key="p.id">
                        <tbody class="divide-y divide-gray-100">
                            <tr class="transition-colors hover:bg-gray-50/50">
                                <td class="px-5 py-3 text-xs font-semibold text-gray-600"
                                    x-text="formatDate(p.payment_date)"></td>
                                <td class="px-5 py-3">
                                    <div class="font-mono text-[11px] font-bold text-gray-800"
                                        x-text="p.reference || p.payment_number"></div>
                                    <div class="text-[10px] text-gray-400" x-text="p.method || ''"></div>
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-black text-[#108c2a]"
                                    x-text="formatMoney(p.amount)"></td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-indigo-600"
                                    x-text="formatMoney(p.allocated)"></td>
                                <td class="px-5 py-3 text-right text-xs font-bold"
                                    :class="p.available > 0 ? 'text-blue-600' : 'text-gray-300'"
                                    x-text="formatMoney(p.available)"></td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if (has_permission('project_payments.allocate'))
                                            {{-- Allocation reads naturally from either side: from a
                                                 charge ("what pays this off") or from a payment
                                                 ("what does this money settle"). --}}
                                            <button x-show="p.available > 0 && outstandingCharges.length > 0"
                                                @click="openAllocateFromPayment(p)"
                                                class="rounded bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700 transition-colors hover:bg-indigo-100">
                                                Allocate
                                            </button>
                                        @endif
                                        <button @click="toggleDetails(p.id)"
                                            class="text-[11px] font-bold text-gray-500 hover:text-gray-800"
                                            x-text="expanded[p.id] ? 'Hide' : 'Details'"></button>
                                    </div>
                                </td>
                            </tr>

                            <tr x-show="expanded[p.id]" x-cloak>
                                <td colspan="6" class="bg-gray-50/70 px-5 py-3">
                                    <div x-show="p.allocations.length === 0" class="text-[11px] text-gray-500">
                                        Not applied to any charge yet — the full amount is sitting as client credit.
                                    </div>
                                    <template x-for="a in p.allocations" :key="a.id">
                                        <div class="flex items-center justify-between border-b border-gray-100 py-1.5 text-[11px] last:border-0"
                                            :class="a.is_reversed ? 'text-gray-400 line-through' : 'text-gray-700'">
                                            <span class="truncate pr-3 font-semibold"
                                                x-text="a.charge || 'Charge #' + a.charge_id"></span>
                                            <span class="flex shrink-0 items-center gap-2">
                                                <span
                                                    class="rounded bg-gray-200 px-1.5 py-0.5 text-[9px] font-bold uppercase"
                                                    x-text="a.source"></span>
                                                <span class="font-bold" x-text="formatMoney(a.amount)"></span>
                                            </span>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    <tbody x-show="payments.length === 0">
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm font-medium text-gray-400">
                                No payments received from this client yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODALS --}}

        {{-- 1. ASSIGN SERVICE MODAL --}}
        <div x-show="modals.service"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Assign Service</h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Pick from the catalog. Price and cycle are copied as a
                            snapshot, so later catalog changes never rewrite what was sold.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitService" class="space-y-4 p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Service *</label>
                        <x-alpine-select model="forms.service.service_id" items="catalog" item-key="id"
                            item-label="item.name + ' (' + cycleLabel(item.billing_cycle) + ' · ₹' + Number(item.price).toLocaleString('en-IN') + ')'"
                            placeholder="One-off service, not in the catalog" empty-text="The catalog is empty."
                            on-change="applyCatalogDefaults()" />
                        <p x-show="catalog.length === 0" class="mt-1 text-[10px] text-amber-600">The catalog is empty. Add
                            services under Services to reuse them across clients.</p>
                    </div>

                    {{-- Only for one-off services. A catalog pick carries its own name. --}}
                    <div x-show="!forms.service.service_id" x-cloak>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Service Name *</label>
                        <input type="text" x-model="forms.service.name" :required="!forms.service.service_id"
                            placeholder="e.g. Premium Hosting"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Billing Cycle *</label>
                            <x-alpine-select model="forms.service.billing_cycle" items="cycles" item-key="value"
                                item-label="item.label" placeholder="Select cycle" :required="true" :allow-empty="false" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Price (₹) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="0"
                                x-model.number="forms.service.price" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">GST Rate (%)</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="0" data-int-max="100"
                                x-model.number="forms.service.tax_rate"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        {{-- A custom cycle has no period length of its own, so
                             without this it could never be renewed. --}}
                        <div x-show="forms.service.billing_cycle === 'custom'" x-cloak>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Duration (days) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="1"
                                x-model.number="forms.service.duration_days"
                                :required="forms.service.billing_cycle === 'custom'"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                        <span class="text-gray-500">First charge will be</span>
                        <strong class="ml-1 text-sm text-gray-900"
                            x-text="formatMoney(withTax(forms.service.price, forms.service.tax_rate))"></strong>
                        <span x-show="Number(forms.service.tax_rate) > 0" x-cloak class="ml-1 text-gray-400">
                            (<span x-text="formatMoney(forms.service.price)"></span> +
                            <span x-text="Number(forms.service.tax_rate)"></span>% GST)
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Start Date</label>
                            <input type="date" x-model="forms.service.started_at"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div class="flex flex-col justify-end gap-2 pb-1">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" x-model="forms.service.raise_charge"
                                    class="h-4 w-4 rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                                <span class="text-xs font-bold text-gray-700">Raise Charge Immediately</span>
                            </label>
                            <label x-show="forms.service.billing_cycle !== 'one_time'" x-cloak
                                class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" x-model="forms.service.auto_renew"
                                    class="h-4 w-4 rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                                <span class="text-xs font-bold text-gray-700">Auto-renew each cycle</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Assign Service
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 2. RENEW SERVICE MODAL --}}
        <div x-show="modals.renew"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div @click.outside="closeModal()"
                class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Renew Service Cycle</h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Generates a new period and charge. Old history is
                            untouched.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitRenew" class="space-y-4 p-6">
                    <div class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs text-indigo-800">
                        Renewing:
                        <strong
                            x-text="
                                selectedService?.name || (selectedService?.service ? selectedService.service.name : '')
                            "></strong>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">New Period Start Date</label>
                            <input type="date" x-model="forms.renew.period_start"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Renewal Price (₹) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="0"
                                x-model.number="forms.renew.price" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Confirm Renewal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 3. ADD CHARGE MODAL --}}
        <div x-show="modals.charge"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div @click.outside="closeModal()"
                class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <h3 class="font-bold text-gray-900">Add Project Charge</h3>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitCharge" class="space-y-4 p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Charge Title *</label>
                        <input type="text" x-model="forms.charge.title" required
                            placeholder="e.g. Extra Server Storage"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Amount (₹) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="1"
                                x-model.number="forms.charge.subtotal" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">GST Rate (%)</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="0" data-int-max="100"
                                x-model.number="forms.charge.tax_rate" placeholder="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        {{-- The user types a pre-tax amount but the client is
                             billed the total, so show the total before saving. --}}
                        <div class="col-span-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                            <span class="text-gray-500">Client will be billed</span>
                            <strong class="ml-1 text-sm text-gray-900"
                                x-text="formatMoney(withTax(forms.charge.subtotal, forms.charge.tax_rate))"></strong>
                            <span x-show="Number(forms.charge.tax_rate) > 0" x-cloak class="ml-1 text-gray-400">
                                (<span x-text="formatMoney(forms.charge.subtotal)"></span> +
                                <span x-text="Number(forms.charge.tax_rate)"></span>% GST)
                            </span>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Charge Date</label>
                            <input type="date" x-model="forms.charge.charge_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Due Date</label>
                            <input type="date" x-model="forms.charge.due_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-gray-800">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i> Save
                            Charge
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 3b. EDIT CHARGE MODAL --}}
        <div x-show="modals.editCharge"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div @click.outside="closeModal()"
                class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Edit Charge</h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Only possible before any money has been applied to
                            this charge.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitEditCharge" class="space-y-4 p-6">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Charge Title *</label>
                        <input type="text" x-model="forms.editCharge.title" required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Amount (₹) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="1"
                                x-model.number="forms.editCharge.subtotal" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">GST Rate (%)</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="0" data-int-max="100"
                                x-model.number="forms.editCharge.tax_rate" placeholder="0"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div class="col-span-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                            <span class="text-gray-500">Client will be billed</span>
                            <strong class="ml-1 text-sm text-gray-900"
                                x-text="formatMoney(withTax(forms.editCharge.subtotal, forms.editCharge.tax_rate))"></strong>
                            <span x-show="Number(forms.editCharge.tax_rate) > 0" x-cloak class="ml-1 text-gray-400">
                                (<span x-text="formatMoney(forms.editCharge.subtotal)"></span> +
                                <span x-text="Number(forms.editCharge.tax_rate)"></span>% GST)
                            </span>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Charge Date</label>
                            <input type="date" x-model="forms.editCharge.charge_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Due Date</label>
                            <input type="date" x-model="forms.editCharge.due_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-gray-800">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 3c. CANCEL CHARGE MODAL --}}
        <div x-show="modals.cancelCharge"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div @click.outside="closeModal()"
                class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-red-100 bg-red-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-red-800">Cancel Charge</h3>
                        <p class="mt-0.5 text-[11px] text-red-600">This charge should never have existed. Any money
                            applied is returned to the client as credit.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-red-400 hover:text-red-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitCancelCharge" class="space-y-4 p-6">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-xs">
                        <span class="text-gray-500">Cancelling:</span>
                        <strong class="ml-1 text-gray-900" x-text="selectedCharge?.title"></strong>
                        <strong class="ml-1 text-gray-900"
                            x-text="'· ' + formatMoney(selectedCharge?.total_amount)"></strong>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Reason *</label>
                        <textarea x-model="forms.cancelCharge.reason" required rows="2"
                            placeholder="e.g. Raised by mistake, duplicate entry…"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Keep Charge
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Yes, Cancel Charge
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 4. RECEIVE PAYMENT MODAL --}}
        <div x-show="modals.payment"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div @click.outside="closeModal()"
                class="modal-scroll w-full max-w-lg animate-[slideUp_0.3s_ease-out] rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Record Client Payment</h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Records physical money received. Allocations are
                            handled automatically via FIFO.</p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form @submit.prevent="submitPayment" class="space-y-4 p-6">
                    {{-- What the client owes and already has on account, read
                         from the context endpoint when the modal opens. --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-red-100 bg-red-50 p-3">
                            <div class="text-[10px] font-bold tracking-wider text-red-600 uppercase">Outstanding</div>
                            <div class="text-sm font-black text-red-700" x-text="formatMoney(context.outstanding)"></div>
                        </div>
                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                            <div class="text-[10px] font-bold tracking-wider text-blue-600 uppercase">
                                Existing Credit
                            </div>
                            <div class="text-sm font-black text-blue-700" x-text="formatMoney(context.credit)"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Amount Received (₹) *</label>
                            <input type="text" inputmode="numeric" data-int data-int-min="1"
                                x-model.number="forms.payment.amount" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                            <p x-show="forms.payment.amount > context.outstanding" x-cloak
                                class="mt-1 text-[10px] font-semibold text-blue-600">
                                <span x-text="formatMoney(forms.payment.amount - context.outstanding)"></span>
                                will stay on the client's account as credit.
                            </p>
                        </div>

                        <div class="col-span-2">
                            <x-payment-method-select name="project_payment_method_id" label="Payment Method"
                                x-model="forms.payment.payment_method_id" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Payment Date</label>
                            <input type="date" x-model="forms.payment.payment_date"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Reference / Txn ID</label>
                            <input type="text" x-model="forms.payment.reference" placeholder="Cheque no, UTR, UPI ref"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        </div>

                        <div class="col-span-2">
                            <label class="mb-1.5 block text-xs font-bold text-gray-700">Notes</label>
                            <textarea x-model="forms.payment.notes" rows="2"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"></textarea>
                        </div>
                    </div>

                    {{-- Which bills the money will settle, in the order FIFO
                         will take them. Shown before saving so allocation is
                         visible rather than magic. --}}
                    <div x-show="forms.payment.allocate && context.charges.length > 0" x-cloak>
                        <div class="mb-1.5 text-[10px] font-bold tracking-wider text-gray-500 uppercase">
                            Will be applied to
                        </div>
                        <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-gray-100 bg-gray-50 p-2">
                            <template x-for="row in fifoPreview" :key="row.id">
                                <div class="flex items-center justify-between px-1 py-1 text-[11px]">
                                    <span class="truncate pr-2 font-semibold text-gray-700" x-text="row.title"></span>
                                    <span class="shrink-0 font-bold text-[#108c2a]" x-text="formatMoney(row.take)"></span>
                                </div>
                            </template>
                            <div x-show="fifoPreview.length === 0" class="px-1 py-1 text-[11px] text-gray-500">
                                Nothing outstanding — the full amount becomes credit.
                            </div>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <input type="checkbox" :checked="!forms.payment.allocate"
                            @change="forms.payment.allocate = !$event.target.checked"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#108c2a] focus:ring-[#108c2a]" />
                        <span>
                            <span class="block text-xs font-bold text-gray-700">Hold the full amount as credit</span>
                            <span class="block text-[10px] text-gray-500">
                                Record the money without settling any bill. Use this for a pure advance.
                            </span>
                        </span>
                    </label>
                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-[#108c2a] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 5. ALLOCATE EXISTING CREDIT MODAL --}}
        <div x-show="modals.allocate"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" x-cloak>
            <div class="modal-scroll w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div>
                        <h3 class="font-bold text-gray-900">Allocate Payment</h3>
                        <p class="mt-0.5 text-[11px] text-gray-500">Apply money the client has already paid to this charge.
                        </p>
                    </div>
                    <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form @submit.prevent="submitAllocation" class="space-y-4 p-6">
                    {{-- Both sides are selectable, because the same modal opens
                         from a charge ("what pays this off") and from a payment
                         ("what does this money settle"). The one it was opened
                         from arrives pre-filled. --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Apply To *</label>
                        <x-alpine-select model="forms.allocate.charge_id" items="outstandingCharges" item-key="id"
                            item-label="item.title + ' · ' + formatMoney(chargeDue(item)) + ' due'"
                            placeholder="Select a charge…" empty-text="No outstanding charges." :required="true"
                            :allow-empty="false" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Take From *</label>
                        <x-alpine-select model="forms.allocate.payment_id" items="availablePayments" item-key="id"
                            item-label="(item.reference || item.payment_number) + ' · ' + formatDate(item.payment_date) + ' · ' + formatMoney(item.available) + ' available'"
                            placeholder="Select a payment…" empty-text="No unallocated payments." :required="true"
                            :allow-empty="false" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-gray-700">Amount (₹) *</label>
                        <input type="text" inputmode="numeric" data-int data-int-min="1" :data-int-max="allocateMax"
                            x-model.number="forms.allocate.amount" required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]" />
                        <p class="mt-1 text-[10px] text-gray-500">Maximum <span
                                x-text="formatMoney(
                                    allocateMax,
                                )"></span>
                            — the lower of what is due and what the payment still has.</p>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeModal()"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">
                            <i data-lucide="loader-2" x-show="isProcessing" class="mr-2 h-4 w-4 animate-spin"></i>
                            Allocate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function projectWorkspace(project = {}, summary = {}, clientCredit = 0, catalog = [], cycles = [], payments = []) {
            return {
                project: project,
                summary: summary,
                clientCredit: clientCredit,
                catalog: catalog,
                cycles: cycles,
                payments: payments,

                modals: {
                    service: false,
                    renew: false,
                    charge: false,
                    editCharge: false,
                    cancelCharge: false,
                    payment: false,
                    allocate: false
                },
                isProcessing: false,
                paymentIdempotencyKey: null,
                selectedService: null,
                selectedCharge: null,
                expanded: {},

                // Filled from the context endpoint when the payment modal opens.
                context: {
                    outstanding: 0,
                    credit: 0,
                    charges: []
                },

                forms: {
                    service: {
                        service_id: "",
                        name: "",
                        billing_cycle: "yearly",
                        duration_days: "",
                        price: "",
                        tax_rate: "",
                        started_at: "{{ date('Y-m-d') }}",
                        auto_renew: false,
                        raise_charge: true,
                    },
                    renew: {
                        period_start: "",
                        price: ""
                    },
                    charge: {
                        title: "",
                        subtotal: "",
                        tax_rate: "",
                        // ChargeType only has one_time, renewal and adjustment.
                        // Renewals are raised by the renewal service, so a charge
                        // added by hand here is always one_time.
                        type: "one_time",
                        charge_date: "{{ date('Y-m-d') }}",
                        due_date: "",
                    },
                    editCharge: {
                        title: "",
                        subtotal: "",
                        tax_rate: "",
                        charge_date: "",
                        due_date: "",
                    },
                    cancelCharge: {
                        reason: "",
                    },
                    payment: {
                        amount: "",
                        payment_method_id: "",
                        payment_date: "{{ date('Y-m-d') }}",
                        reference: "",
                        notes: "",
                        allocate: true,
                    },
                    allocate: {
                        charge_id: "",
                        payment_id: "",
                        amount: ""
                    },
                },

                /** Payments with money still unapplied — the only ones worth allocating from. */
                get availablePayments() {
                    return this.payments.filter((p) => p.available > 0.005);
                },

                /** Charges on this project that still owe money. */
                get outstandingCharges() {
                    return (this.project.charges || []).filter((ch) => ch.status !== "cancelled" && this.chargeDue(ch) >
                        0.005);
                },

                /** The charge currently chosen in the allocate modal. */
                get allocateCharge() {
                    return (this.project.charges || []).find((ch) => String(ch.id) === String(this.forms.allocate
                        .charge_id));
                },

                /**
                 * Never more than the charge still owes, and never more than the
                 * payment still has. The server enforces both, but a cap here
                 * stops the user discovering the rule by hitting an error.
                 */
                get allocateMax() {
                    const payment = this.payments.find((p) => String(p.id) === String(this.forms.allocate.payment_id));
                    const charge = this.allocateCharge;

                    if (!payment && !charge) return 0;
                    if (!payment) return this.chargeDue(charge);
                    if (!charge) return payment.available;

                    return Math.min(payment.available, this.chargeDue(charge));
                },

                /** Walks the FIFO order the server will use, so the split is visible up front. */
                get fifoPreview() {
                    let remaining = Number(this.forms.payment.amount || 0);
                    const rows = [];

                    for (const charge of this.context.charges) {
                        if (remaining <= 0.005) break;

                        const take = Math.min(remaining, Number(charge.balance || 0));
                        if (take <= 0.005) continue;

                        rows.push({
                            id: charge.id,
                            title: charge.title,
                            take: take
                        });
                        remaining = Math.round((remaining - take) * 100) / 100;
                    }

                    return rows;
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof lucide !== "undefined") lucide.createIcons();
                    });
                },

                /**
                 * A 500 returns an HTML error page, and res.json() then throws a
                 * parser error that hides what actually went wrong. Every request
                 * on this screen goes through here so a server crash surfaces as
                 * a readable message instead of silence.
                 */
                async readResponse(res) {
                    const text = await res.text();

                    let data = null;

                    try {
                        data = JSON.parse(text);
                    } catch (_) {
                        console.error("Server did not return JSON:", text.slice(0, 2000));
                        throw new Error("Server error. Check the Laravel log for details.");
                    }

                    if (!res.ok) {
                        // Laravel validation errors arrive under errors, not message.
                        if (data.errors) throw new Error(Object.values(data.errors)[0][0]);
                        throw new Error(data.message || "Request failed.");
                    }

                    return data;
                },

                /**
                 * crypto.randomUUID needs a secure context, so plain http (a
                 * local office machine) falls back to a v4-shaped string. It
                 * only has to be unique per company, not cryptographically
                 * strong — the server validates the format either way.
                 */
                newIdempotencyKey() {
                    if (window.crypto && typeof window.crypto.randomUUID === "function") {
                        return window.crypto.randomUUID();
                    }

                    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
                        const r = (Math.random() * 16) | 0;
                        const v = c === "x" ? r : (r & 0x3) | 0x8;
                        return v.toString(16);
                    });
                },

                formatMoney(val) {
                    return (
                        "₹" + Number(val || 0).toLocaleString("en-IN", {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    );
                },

                formatDate(dateString) {
                    if (!dateString) return "";
                    const date = new Date(dateString);
                    return date.toLocaleDateString("en-IN", {
                        day: "2-digit",
                        month: "short",
                        year: "numeric"
                    });
                },

                cycleLabel(value) {
                    const cycle = this.cycles.find((c) => c.value === value);
                    return cycle ? cycle.label : String(value || "").replace("_", " ");
                },

                serviceStatusClass(status) {
                    if (status === "active") return "bg-green-50 text-green-700 border-green-200";
                    if (status === "expired") return "bg-orange-50 text-orange-700 border-orange-200";
                    return "bg-red-50 text-red-700 border-red-200";
                },

                /**
                 * Mirrors the server rule: a one-time service never renews, and a
                 * cancelled one never renews. Expired does — a client returning
                 * after a lapse is renewed forward, not recreated.
                 */
                isRenewable(cs) {
                    return cs.billing_cycle !== "one_time" && cs.status !== "cancelled";
                },

                /** Copies the catalog entry into the form as the starting point. */
                applyCatalogDefaults() {
                    const item = this.catalog.find((c) => String(c.id) === String(this.forms.service.service_id));

                    if (!item) return;

                    this.forms.service.name = "";
                    this.forms.service.billing_cycle = item.billing_cycle;
                    this.forms.service.duration_days = item.duration_days || "";
                    this.forms.service.price = Number(item.price);
                    this.forms.service.tax_rate = Number(item.tax_rate);
                },

                withTax(price, rate) {
                    const base = Number(price || 0);
                    return base + (base * Number(rate || 0)) / 100;
                },

                chargeDue(ch) {
                    const total = Number(ch.total_amount || 0);
                    const paid = Number(ch.paid_amount || 0);
                    const written = Number(ch.written_off_amount || 0);
                    return Math.max(0, total - paid - written);
                },

                /**
                 * Mirrors the server's ProjectCharge::isLocked() — once money has
                 * touched a charge (paid, written off) or it's cancelled, edit/cancel
                 * are hidden here rather than letting the user discover the rule by
                 * hitting a 422 from the server.
                 */
                isChargeLocked(ch) {
                    return Number(ch.paid_amount || 0) > 0 ||
                        Number(ch.written_off_amount || 0) > 0 ||
                        ch.status === "cancelled";
                },

                /**
                 * Position of a charge within its service's renewal sequence.
                 *
                 * Charges arrive newest-first, which is right for reading but
                 * useless for numbering, so siblings are re-sorted ascending by
                 * period. Ad-hoc charges carry no client_service_id and are
                 * treated as standalone — they get no ribbon.
                 *
                 * Computed on demand rather than cached: the list is a few dozen
                 * rows at most, and caching would go stale after every renew.
                 */
                cycleMeta(ch) {
                    if (!ch.client_service_id) {
                        return {
                            index: 1,
                            total: 1,
                            state: "standalone"
                        };
                    }

                    const siblings = (this.project.charges || [])
                        .filter((c) => c.client_service_id === ch.client_service_id)
                        .sort((a, b) => new Date(a.period_start || a.charge_date) - new Date(b.period_start || b
                            .charge_date));

                    const position = siblings.findIndex((c) => c.id === ch.id);
                    const total = siblings.length;

                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const startsLater = ch.period_start && new Date(ch.period_start) > today;

                    // The newest period is the live one unless it has not begun
                    // yet — a renewal raised in advance is upcoming, not current.
                    let state = "past";

                    if (startsLater) {
                        state = "upcoming";
                    } else if (position === total - 1) {
                        state = "current";
                    }

                    return {
                        index: position + 1,
                        total,
                        state
                    };
                },

                cycleStateLabel(ch) {
                    const state = this.cycleMeta(ch).state;
                    if (state === "current") return "Current period";
                    if (state === "upcoming") return "Upcoming";
                    if (state === "past") return "Previous period";
                    return "";
                },

                /** The left border carries the state so it stays readable while scrolling. */
                cycleCardClass(ch) {
                    const state = this.cycleMeta(ch).state;
                    if (state === "current")
                        return "border-gray-100 border-l-indigo-500 bg-indigo-50/20 hover:bg-indigo-50/40";
                    if (state === "upcoming") return "border-gray-100 border-l-sky-400 bg-sky-50/20 hover:bg-sky-50/40";
                    if (state === "past") return "border-gray-100 border-l-gray-300 bg-gray-50/40 hover:bg-gray-50/70";
                    return "border-gray-100 border-l-gray-200 hover:border-red-100 hover:bg-red-50/30";
                },

                cycleBadgeClass(ch) {
                    const state = this.cycleMeta(ch).state;
                    if (state === "current") return "border-indigo-200 bg-indigo-50 text-indigo-700";
                    if (state === "upcoming") return "border-sky-200 bg-sky-50 text-sky-700";
                    return "border-gray-200 bg-gray-100 text-gray-500";
                },

                cycleStateTextClass(ch) {
                    const state = this.cycleMeta(ch).state;
                    if (state === "current") return "text-indigo-600";
                    if (state === "upcoming") return "text-sky-600";
                    return "text-gray-400";
                },

                openModal(name) {
                    this.modals[name] = true;
                },

                closeModal() {
                    for (let k in this.modals) this.modals[k] = false;
                    this.selectedService = null;
                    this.forms.allocate = {
                        charge_id: "",
                        payment_id: "",
                        amount: ""
                    };
                },

                toggleDetails(key) {
                    this.expanded[key] = !this.expanded[key];
                },

                /**
                 * Loads what the client owes before the user types anything, so
                 * the amount can be checked against reality on the same screen.
                 */
                openPaymentModal() {
                    this.forms.payment.amount = "";
                    this.forms.payment.allocate = true;
                    this.openModal("payment");

                    fetch(`{{ route('admin.project_payments.context') }}?client_id=${this.project.client_id}`, {
                            headers: {
                                Accept: "application/json"
                            },
                        })
                        .then((res) => res.json())
                        .then((data) => {
                            if (!data.success) return;
                            this.context = {
                                outstanding: data.outstanding,
                                credit: data.credit,
                                charges: data.charges,
                            };
                        })
                        .catch(() => {});
                },

                /** Started from a charge: the charge is fixed, pick the money. */
                openAllocateModal(charge) {
                    this.forms.allocate = {
                        charge_id: charge.id,
                        payment_id: "",
                        amount: ""
                    };
                    this.openModal("allocate");
                },

                /** Started from a payment: the money is fixed, pick the charge. */
                openAllocateFromPayment(payment) {
                    this.forms.allocate = {
                        charge_id: "",
                        payment_id: payment.id,
                        amount: ""
                    };
                    this.openModal("allocate");
                },

                chargeStatusClass(status) {
                    if (status === "paid") return "bg-green-50 text-green-700 border-green-200";
                    if (status === "partially_paid") return "bg-amber-50 text-amber-700 border-amber-200";
                    if (status === "written_off") return "bg-slate-100 text-slate-600 border-slate-200";
                    if (status === "cancelled") return "bg-slate-100 text-slate-500 border-slate-200";
                    return "bg-red-50 text-red-600 border-red-200";
                },

                openRenewModal(cs) {
                    this.selectedService = cs;
                    this.forms.renew.price = cs.price;
                    this.openModal("renew");
                },

                openEditChargeModal(ch) {
                    this.selectedCharge = ch;
                    this.forms.editCharge = {
                        title: ch.title,
                        subtotal: Number(ch.subtotal),
                        tax_rate: Number(ch.tax_rate) || "",
                        charge_date: ch.charge_date ? ch.charge_date.slice(0, 10) : "",
                        due_date: ch.due_date ? ch.due_date.slice(0, 10) : "",
                    };
                    this.openModal("editCharge");
                },

                openCancelChargeModal(ch) {
                    this.selectedCharge = ch;
                    this.forms.cancelCharge = {
                        reason: "",
                    };
                    this.openModal("cancelCharge");
                },

                // --- AJAX Actions ---
                // Since this view relies heavily on summary and credit calculation via backend,
                // we reload the page softly after a successful action to refresh aggregates flawlessly.

                submitService() {
                    this.isProcessing = true;

                    const form = this.forms.service;

                    const payload = {
                        client_id: this.project.client_id,
                        project_id: this.project.id,
                        service_id: form.service_id || null,
                        // The server takes the name from the catalog entry when
                        // service_id is set, so only a one-off needs to send one.
                        name: form.service_id ? null : form.name,
                        billing_cycle: form.billing_cycle,
                        duration_days: form.billing_cycle === "custom" ? form.duration_days : null,
                        price: form.price,
                        tax_rate: form.tax_rate === "" ? null : form.tax_rate,
                        started_at: form.started_at,
                        auto_renew: form.auto_renew,
                        raise_charge: form.raise_charge,
                    };

                    fetch(`{{ route('admin.project_client_services.store') }}`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(payload),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitRenew() {
                    this.isProcessing = true;
                    // Built from the renew route itself rather than string-joining
                    // onto the index route — index is being removed, and this
                    // survives a change to the URL prefix either way.
                    fetch(`{{ route('admin.project_client_services.renew', ['clientService' => '__ID__']) }}`.replace(
                            '__ID__', this.selectedService.id), {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.forms.renew),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitCharge() {
                    this.isProcessing = true;
                    const payload = {
                        client_id: this.project.client_id,
                        project_id: this.project.id,
                        ...this.forms.charge,
                    };

                    fetch(`{{ route('admin.project_charges.store') }}`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(payload),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitEditCharge() {
                    this.isProcessing = true;

                    fetch(`{{ route('admin.project_charges.update', ['charge' => '__ID__']) }}`.replace('__ID__',
                            this.selectedCharge.id), {
                            method: "PUT",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.forms.editCharge),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitCancelCharge() {
                    this.isProcessing = true;

                    fetch(`{{ route('admin.project_charges.cancel', ['charge' => '__ID__']) }}`.replace('__ID__',
                            this.selectedCharge.id), {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(this.forms.cancelCharge),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);
                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitPayment() {
                    if (!this.forms.payment.payment_method_id) {
                        BizAlert.toast("Please select a payment method.", "error");
                        return;
                    }

                    this.isProcessing = true;

                    // One key per intended payment. Generated on the first
                    // attempt and deliberately KEPT through failures, so a
                    // retry after a timeout — where the server may already have
                    // committed — resolves to that same payment instead of
                    // booking the client's money twice. Cleared only on success.
                    if (!this.paymentIdempotencyKey) {
                        this.paymentIdempotencyKey = this.newIdempotencyKey();
                    }

                    const payload = {
                        client_id: this.project.client_id,
                        idempotency_key: this.paymentIdempotencyKey,
                        ...this.forms.payment,
                    };

                    fetch(`{{ route('admin.project_payments.store') }}`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify(payload),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);

                            // Report the split back. FIFO deciding silently is
                            // what makes people distrust an allocation system.
                            const applied = (data.applied || [])
                                .map((a) => `${a.title}: ${this.formatMoney(a.amount)}`)
                                .join("\n");

                            // This payment is recorded. The next one is a
                            // genuinely new transaction and must not dedup
                            // against it.
                            this.paymentIdempotencyKey = null;

                            BizAlert.toast(data.message, "success");
                            if (applied) console.info("Applied to:\n" + applied);

                            setTimeout(() => window.location.reload(), 1200);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },

                submitAllocation() {
                    this.isProcessing = true;

                    // The endpoint takes a charge_id => amount map, which is how
                    // one payment can be split across several charges at once.
                    const allocations = {};
                    allocations[this.forms.allocate.charge_id] = this.forms.allocate.amount;

                    fetch(`{{ route('admin.project_payments.store') }}/${this.forms.allocate.payment_id}/allocate`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                Accept: "application/json",
                            },
                            body: JSON.stringify({
                                allocations
                            }),
                        })
                        .then(async (res) => {
                            const data = await this.readResponse(res);

                            BizAlert.toast(data.message, "success");
                            setTimeout(() => window.location.reload(), 1000);
                        })
                        .catch((err) => {
                            BizAlert.toast(err.message, "error");
                            this.isProcessing = false;
                        });
                },
            };
        }
    </script>
@endpush
