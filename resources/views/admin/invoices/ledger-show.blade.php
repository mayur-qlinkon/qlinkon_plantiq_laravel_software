@extends ('layouts.admin')

@section ('title', 'Ledger Details')

@push ('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
        @media print {
            @page {
                size: A4;
                margin: 12mm;
            }
            body {
                background: white !important;
                font-size: 10pt !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            /* 1. Hide sidebars/navs natively instead of using absolute positioning */
            aside,
            nav,
            header,
            footer,
            .sidebar,
            .admin-sidebar,
            .topbar {
                display: none !important;
            }

            /* 2. Reset wrappers to static block flow (CRITICAL FOR MULTI-PAGE PRINTING) */
            body,
            html,
            main,
            #app,
            .content-wrapper,
            .main-content,
            #print-area {
                position: static !important;
                display: block !important;
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            /* 3. Remove overflow bounds that cause right-side cutouts */
            .overflow-hidden,
            .overflow-x-auto,
            .overflow-y-auto {
                overflow: visible !important;
            }

            /* 4. Table Pagination & Layout Fixes */
            table {
                width: 100% !important;
                page-break-inside: auto;
                border-collapse: collapse;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }

            /* 5. Reduce Whitespace & Compress Layout for A4 */
            .no-print,
            .no-print * {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .pb-10 {
                padding-bottom: 0 !important;
            }
            .mb-6 {
                margin-bottom: 12px !important;
            }
            .p-5 {
                padding: 10px !important;
            }
            .gap-4 {
                gap: 8px !important;
            }

            /* 6. Force Summary Cards into 1 Neatly Spaced Row */
            .grid {
                display: flex !important;
                flex-wrap: nowrap !important;
            }
            .grid > div {
                flex: 1;
                margin-bottom: 0 !important;
            }
        }
    </style>
@endpush

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Customer Ledger</h1>
@endsection

@section ('content')
    <div id="print-area" class="pb-10" x-data="{ showFilters: false }">
        {{-- ── BREADCRUMB ── --}}
        <div class="no-print mb-5 flex items-center gap-2 text-xs text-gray-400">
            <a href="{{ route('admin.ledger.index') }}" class="transition-colors hover:text-[#108c2a]"
                >Customer Ledger</a
            >
            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
            <span class="font-semibold text-gray-600">{{ $client->name }}</span>
        </div>

        {{-- ── PAGE HEADER ── --}}
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div class="flex items-center gap-4">
                {{-- Avatar --}}
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#108c2a]/10 text-xl font-black text-[#108c2a]"
                >
                    {{ strtoupper(substr($client->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-800">{{ $client->name }}</h2>
                    @if ($client->company_name)
                        <p class="text-sm text-gray-500">{{ $client->company_name }}</p>
                    @endif
                    <div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-500">
                        @if ($client->phone)
                            <span class="flex items-center gap-1"
                                ><i data-lucide="phone" class="h-3 w-3"></i> {{ $client->phone }}</span
                            >
                        @endif
                        @if ($client->email)
                            <span class="flex items-center gap-1"
                                ><i data-lucide="mail" class="h-3 w-3"></i> {{ $client->email }}</span
                            >
                        @endif
                        @if ($client->gst_number)
                            <span class="flex items-center gap-1"
                                ><i data-lucide="hash" class="h-3 w-3"></i> GST: {{ $client->gst_number }}</span
                            >
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="no-print flex shrink-0 items-center gap-2">
                <button
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                >
                    <i data-lucide="filter" class="h-4 w-4 text-gray-500"></i> Filter
                    @if (array_filter($filters))
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    @endif
                </button>
                <button
                    onclick="window.print()"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                >
                    <i data-lucide="printer" class="h-4 w-4 text-gray-500"></i> Print
                </button>
                {{-- Export buttons (wired for future implementation) --}}
                <a
                    href="{{ route('admin.ledger.show', ['client' => $client->id, 'export' => 'pdf'] + $filters) }}"
                    target="_blank"
                    class="flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition-colors hover:bg-red-100"
                >
                    <i data-lucide="file-text" class="h-4 w-4"></i> PDF
                </a>
                <a
                    href="{{ route('admin.ledger.show', ['client' => $client->id, 'export' => 'excel'] + $filters) }}"
                    target="_blank"
                    class="flex items-center gap-2 rounded-lg bg-green-50 px-4 py-2.5 text-sm font-semibold text-green-600 shadow-sm transition-colors hover:bg-green-100"
                >
                    <i data-lucide="table" class="h-4 w-4"></i> Excel
                </a>
            </div>
        </div>

        {{-- ── FILTER PANEL ── --}}
        <div
            x-show="showFilters"
            x-cloak
            x-transition
            class="no-print mb-5 rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <form
                method="GET"
                action="{{ route('admin.ledger.show', $client->id) }}"
                class="flex flex-col flex-wrap items-end gap-3 sm:flex-row"
            >
                @if ($stores->count() > 1)
                    <div class="min-w-[160px] flex-1">
                        <label class="mb-1 block text-xs font-bold tracking-wider text-gray-500 uppercase">Store</label>
                        <select
                            name="store_id"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#108c2a]"
                        >
                            <option value="">All Stores</option>
                            @foreach ($stores as $store)
                                <option
                                    value="{{ $store->id }}"
                                    {{ ($filters['store_id'] ?? '') == $store->id ? 'selected' : '' }}
                                >
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="min-w-[160px] flex-1">
                    <label class="mb-1 block text-xs font-bold tracking-wider text-gray-500 uppercase">From Date</label>
                    <input
                        type="date"
                        name="from_date"
                        value="{{ $filters['from_date'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-[#108c2a]"
                    />
                </div>

                <div class="min-w-[160px] flex-1">
                    <label class="mb-1 block text-xs font-bold tracking-wider text-gray-500 uppercase">To Date</label>
                    <input
                        type="date"
                        name="to_date"
                        value="{{ $filters['to_date'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-[#108c2a]"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-[#108c2a] px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#0d7523]"
                    >
                        Apply
                    </button>
                    @if (array_filter($filters))
                        <a
                            href="{{ route('admin.ledger.show', $client->id) }}"
                            class="rounded-lg bg-red-50 px-4 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        >
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── SUMMARY CARDS ── --}}
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- Total Invoice --}}
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Total Invoiced</p>
                        <p class="text-2xl font-black text-gray-800">₹{{ number_format($summary['total_invoiced'], 2) }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <i data-lucide="file-text" class="h-5 w-5 text-blue-500"></i>
                    </div>
                </div>
            </div>

            {{-- Total Paid --}}
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Total Received</p>
                        <p class="text-2xl font-black text-green-600">₹{{ number_format($summary['total_paid'], 2) }}</p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50">
                        <i data-lucide="check-circle" class="h-5 w-5 text-green-500"></i>
                    </div>
                </div>
            </div>

            {{-- Outstanding --}}
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Outstanding</p>
                        <p
                            class="text-2xl font-black {{ $summary['outstanding'] > 0 ? 'text-orange-500' : 'text-green-600' }}"
                        >
                            ₹{{ number_format($summary['outstanding'], 2) }}
                        </p>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                        <i data-lucide="clock" class="h-5 w-5 text-orange-500"></i>
                    </div>
                </div>
            </div>

            {{-- Overdue --}}
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="mb-1.5 text-xs font-bold tracking-wider text-gray-400 uppercase">Overdue</p>
                        <p
                            class="text-2xl font-black {{ $summary['overdue'] > 0 ? 'text-red-600' : 'text-green-600' }}"
                        >
                            ₹{{ number_format($summary['overdue'], 2) }}
                        </p>
                    </div>
                    <div
                        class="w-10 h-10 rounded-xl {{ $summary['overdue'] > 0 ? 'bg-red-50' : 'bg-green-50' }} flex items-center justify-center shrink-0"
                    >
                        <i
                            data-lucide="alert-triangle"
                            class="w-5 h-5 {{ $summary['overdue'] > 0 ? 'text-red-500' : 'text-green-500' }}"
                        ></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── LEDGER TABLE ── --}}
        <div
            class="bg-transparent lg:overflow-hidden lg:rounded-xl lg:border lg:border-gray-100 lg:bg-white lg:shadow-sm"
        >
            {{-- Table Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-700">Transaction Ledger</h3>
                    <p class="mt-0.5 text-xs text-gray-400">
                        {{ $entries->count() }} entries
                        @if (!empty($filters['from_date']) || !empty($filters['to_date']))
                            for period
                            {{ !empty($filters['from_date']) ? \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') : '' }}
                            @if (!empty($filters['from_date']) && !empty($filters['to_date'])) — @endif
                            {{ !empty($filters['to_date']) ? \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') : '' }}
                        @endif
                    </p>
                </div>
                {{-- Legend --}}
                <div class="no-print flex items-center gap-4 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-blue-600">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span> Invoice (Debit)
                    </span>
                    <span class="flex items-center gap-1.5 text-green-600">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span> Payment (Credit)
                    </span>
                </div>
            </div>

            @if ($entries->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                    <i data-lucide="inbox" class="mb-4 h-14 w-14 text-gray-200"></i>
                    <p class="text-base font-semibold text-gray-500">No transactions found</p>
                    <p class="mt-1 text-sm">Try changing the date range or filters.</p>
                </div>
            @else
                <div class="overflow-x-auto lg:overflow-visible">
                    <table class="block w-full text-left text-sm lg:table">
                        <thead class="hidden lg:table-header-group">
                            <tr class="border-b border-gray-100">
                                <th class="w-28 px-5 py-3 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    Date
                                </th>
                                <th class="w-24 px-4 py-3 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    Type
                                </th>
                                <th class="px-4 py-3 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    Reference
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold tracking-wider text-gray-500 uppercase"
                                >
                                    Invoice Amt
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold tracking-wider text-gray-500 uppercase"
                                >
                                    Payment Amt
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold tracking-wider text-gray-500 uppercase"
                                >
                                    Balance
                                </th>
                                <th
                                    class="w-28 px-4 py-3 text-center text-xs font-bold tracking-wider text-gray-500 uppercase"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="block divide-y divide-gray-100 lg:table-row-group lg:divide-y-0">
                            @foreach ($entries as $entry)
                                @php
                                $isInvoice = $entry['type'] === 'invoice';
                                $balance   = $entry['running_balance'];

                                // Determine row highlight
                                $rowClass = '';
                                if ($isInvoice) {
                                    $rowClass = 'bg-blue-50/30 hover:bg-blue-50/60';
                                } else {
                                    $rowClass = 'bg-green-50/20 hover:bg-green-50/50';
                                }

                                // Status badge
                                $statusBadge = match(true) {
                                    !$isInvoice => ['label' => 'Received', 'class' => 'bg-green-100 text-green-700'],
                                    $entry['payment_status'] === 'paid'    => ['label' => 'Paid',    'class' => 'bg-green-100 text-green-700'],
                                    $entry['payment_status'] === 'partial' => ['label' => 'Partial', 'class' => 'bg-blue-100 text-blue-700'],
                                    $entry['due_date'] && \Carbon\Carbon::parse($entry['due_date'])->isPast()
                                                                           => ['label' => 'Overdue', 'class' => 'bg-red-100 text-red-700'],
                                    default                                => ['label' => 'Due',     'class' => 'bg-amber-100 text-amber-700'],
                                };
                            @endphp
                                <tr
                                    class="{{ $rowClass }} transition-colors flex flex-col lg:table-row border border-gray-200 lg:border-b lg:border-gray-50 lg:border-x-0 lg:border-t-0 mb-4 lg:mb-0 shadow-sm lg:shadow-none rounded-xl lg:rounded-none"
                                >
                                    {{-- Date --}}
                                    <td
                                        class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-black/5 px-4 py-2.5 lg:table-cell lg:rounded-none lg:border-none lg:bg-transparent lg:px-5 lg:py-3.5"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden">Date</span>
                                        <div class="text-right lg:text-left">
                                            <p class="text-xs font-semibold text-gray-800">
                                                {{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}
                                            </p>
                                            <p class="text-[10px] text-gray-500 lg:text-gray-400">
                                                {{ \Carbon\Carbon::parse($entry['date'])->format('D') }}
                                            </p>
                                        </div>
                                    </td>

                                    {{-- Type --}}
                                    <td
                                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 lg:table-cell lg:border-none lg:px-4 lg:py-3.5"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden">Type</span>
                                        @if ($isInvoice)
                                            <span
                                                class="inline-flex items-center gap-1 text-xs font-bold text-blue-600"
                                            >
                                                <i data-lucide="file-minus" class="h-3.5 w-3.5"></i> Invoice
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 text-xs font-bold text-green-600"
                                            >
                                                <i data-lucide="file-plus" class="h-3.5 w-3.5"></i> Payment
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Reference --}}
                                    <td
                                        class="flex items-start justify-between border-b border-gray-100 px-4 py-2.5 lg:table-cell lg:items-center lg:border-none lg:px-4 lg:py-3.5"
                                    >
                                        <span class="mt-0.5 text-xs font-bold text-gray-500 uppercase lg:hidden"
                                            >Reference</span
                                        >
                                        <div class="text-right lg:text-left">
                                            @if ($isInvoice)
                                                <a
                                                    href="{{ route('admin.invoices.show', $entry['invoice_id']) }}"
                                                    class="font-bold text-gray-800 transition-colors hover:text-[#108c2a]"
                                                >
                                                    {{ $entry['reference'] }}
                                                </a>
                                            @else
                                                <p class="font-semibold text-gray-700">{{ $entry['reference'] }}</p>
                                            @endif
                                            <p class="mt-0.5 text-[10px] text-gray-500 lg:text-gray-400">
                                                @if ($isInvoice && $entry['due_date'])
                                                    Due: {{ \Carbon\Carbon::parse($entry['due_date'])->format('d M Y') }}
                                                @elseif (!$isInvoice && $entry['payment_method'])
                                                    via {{ $entry['payment_method'] }}
                                                @endif
                                                @if ($entry['notes'])
                                                    · {{ Str::limit($entry['notes'], 40) }}
                                                @endif
                                            </p>
                                        </div>
                                    </td>

                                    {{-- Invoice Amount (Debit) --}}
                                    <td
                                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 lg:table-cell lg:border-none lg:px-4 lg:py-3.5 lg:text-right"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden"
                                            >Invoice Amt</span
                                        >
                                        @if ($isInvoice)
                                            <span class="font-bold text-gray-800"
                                                >₹{{ number_format($entry['invoice_amount'], 2) }}</span
                                            >
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Payment Amount (Credit) --}}
                                    <td
                                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 lg:table-cell lg:border-none lg:px-4 lg:py-3.5 lg:text-right"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden"
                                            >Payment Amt</span
                                        >
                                        @if (!$isInvoice)
                                            <span class="font-bold text-green-600"
                                                >₹{{ number_format($entry['payment_amount'], 2) }}</span
                                            >
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>

                                    {{-- Running Balance --}}
                                    <td
                                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5 lg:table-cell lg:border-none lg:px-4 lg:py-3.5 lg:text-right"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden">Balance</span>
                                        <div class="text-right">
                                            <span
                                                class="font-black {{ $balance > 0 ? 'text-red-600' : ($balance < 0 ? 'text-green-600' : 'text-gray-500') }}"
                                            >
                                                {{ $balance < 0 ? '(' : '' }}₹{{ number_format(abs($balance), 2) }}{{ $balance < 0 ? ')' : '' }}
                                            </span>
                                            @if ($balance < 0)
                                                <span
                                                    class="ml-1 text-[10px] font-medium text-green-500 lg:ml-0 lg:block"
                                                    >Cr</span
                                                >
                                            @elseif ($balance > 0)
                                                <span class="ml-1 text-[10px] font-medium text-red-400 lg:ml-0 lg:block"
                                                    >Dr</span
                                                >
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Status Badge --}}
                                    <td
                                        class="flex items-center justify-between px-4 py-3 lg:table-cell lg:px-4 lg:py-3.5 lg:text-center"
                                    >
                                        <span class="text-xs font-bold text-gray-500 uppercase lg:hidden">Status</span>
                                        <span
                                            class="inline-block text-xs font-bold px-2.5 py-1 rounded-full {{ $statusBadge['class'] }}"
                                        >
                                            {{ $statusBadge['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Closing Balance Row --}}
                            <tr
                                class="mt-6 flex flex-col rounded-xl bg-gray-800 text-white shadow-md lg:mt-0 lg:table-row lg:rounded-none lg:shadow-none"
                            >
                                <td
                                    colspan="3"
                                    class="border-b border-gray-700 px-4 py-3 text-center text-sm font-black tracking-widest uppercase lg:border-none lg:px-5 lg:py-4 lg:text-left"
                                >
                                    Closing Balance
                                </td>
                                <td
                                    class="flex items-center justify-between border-b border-gray-700 px-4 py-2.5 text-sm font-black lg:table-cell lg:border-none lg:px-4 lg:py-4 lg:text-right"
                                >
                                    <span class="text-xs font-medium text-gray-400 uppercase lg:hidden"
                                        >Total Invoiced</span
                                    >
                                    ₹{{ number_format($summary['total_invoiced'], 2) }}
                                </td>
                                <td
                                    class="flex items-center justify-between border-b border-gray-700 px-4 py-2.5 text-sm font-black text-green-400 lg:table-cell lg:border-none lg:px-4 lg:py-4 lg:text-right"
                                >
                                    <span class="text-xs font-medium text-gray-400 uppercase lg:hidden"
                                        >Total Paid</span
                                    >
                                    ₹{{ number_format($summary['total_paid'], 2) }}
                                </td>
                                <td
                                    class="px-4 lg:px-4 py-2.5 lg:py-4 flex justify-between lg:table-cell items-center lg:text-right border-b border-gray-700 lg:border-none font-black text-base {{ $summary['outstanding'] > 0 ? 'text-red-400' : 'text-green-400' }}"
                                >
                                    <span class="text-xs font-medium text-gray-400 uppercase lg:hidden"
                                        >Outstanding</span
                                    >
                                    ₹{{ number_format($summary['outstanding'], 2) }}
                                </td>
                                <td class="flex justify-center px-4 py-4 lg:table-cell lg:px-4 lg:py-4 lg:text-center">
                                    @if ($summary['outstanding'] <= 0)
                                        <span
                                            class="block w-full rounded-full bg-green-500 px-4 py-1.5 text-center text-xs font-bold text-white lg:inline-block lg:w-auto lg:px-2.5 lg:py-1"
                                            >Settled</span
                                        >
                                    @else
                                        <span
                                            class="block w-full rounded-full bg-red-500 px-4 py-1.5 text-center text-xs font-bold text-white lg:inline-block lg:w-auto lg:px-2.5 lg:py-1"
                                            >Pending</span
                                        >
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Print Footer --}}
        <div class="print-only mt-8 hidden border-t pt-4 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Powered by Qlinkon &bull; {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
@endsection
