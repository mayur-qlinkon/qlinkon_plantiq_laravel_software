@extends ('layouts.admin')

@section ('title', 'Sales Invoices - ' . config('app.name'))

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Invoices</h1>
@endsection

@push ('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section ('content')
    <div class="pb-10" x-data="invoiceIndex()">
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

        {{-- SEARCH & FILTER BAR --}}
        <div class="rounded-t-xl border border-b-0 border-gray-100 bg-white p-4 shadow-sm">
            <form
                id="invoice-filter-form"
                action="{{ route('admin.invoices.index') }}"
                method="GET"
                class="flex w-full flex-wrap items-center gap-3"
                @submit.prevent="submitForm"
                @change="submitForm"
            >
                {{-- 1. Search Group (Input + Search + Clear) --}}
                <div class="flex w-full max-w-md min-w-[250px] flex-1 flex-row items-center gap-2">
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>
                        </div>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search Invoice Number, Customer..."
                            @input.debounce.400ms="submitForm"
                            class="w-full rounded-lg border border-gray-200 py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 transition-all outline-none focus:border-[#108c2a] focus:ring-1 focus:ring-[#108c2a]"
                        />
                    </div>

                    <button
                        type="button"
                        @click="clearFilters"
                        x-show="hasActiveFilters"
                        x-cloak
                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2.5 text-sm font-bold text-red-500 transition-colors hover:bg-red-100"
                        title="Clear Filters"
                    >
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear
                    </button>
                </div>

                {{-- Who raised the invoice. Useful for counter shifts and for
                     settling "who billed this" questions without opening rows. --}}
                @if ($creators->isNotEmpty())
                    <div class="w-full shrink-0 lg:w-auto">
                        <x-custom-select
                            name="created_by"
                            placeholder="All Users"
                            :options="$creators->pluck('name', 'id')->toArray()"
                            selected="{{ request('created_by') }}"
                        />
                    </div>
                @endif

                <div class="w-full shrink-0 lg:w-auto">
                    <x-custom-select
                        name="payment_status"
                        placeholder="All Payments"
                        :options="[
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]"
                        selected="{{ request('payment_status') }}"
                    />
                </div>

                <div class="w-full shrink-0 lg:w-auto">
                    <x-custom-select
                        name="source"
                        placeholder="All Sources"
                        :options="[
                        'pos' => 'POS Sale',
                        'direct' => 'Direct Invoice',
                        'online' => 'Online Order',
                    ]"
                        selected="{{ request('source') }}"
                    />
                </div>

                <div class="w-full shrink-0 lg:w-auto">
                    <x-custom-select
                        name="status"
                        placeholder="All Statuses"
                        :options="[
                        'draft' => 'Draft',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]"
                        selected="{{ request('status') }}"
                    />
                </div>

                <div class="flex w-full shrink-0 items-center justify-between gap-1.5 sm:w-auto">
                    <input
                        type="date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        max="{{ request('end_date') ?: now()->format('Y-m-d') }}"
                        title="From date"
                        class="w-[130px] rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-sm outline-none focus:border-[#108c2a]"
                    />
                    <span class="shrink-0 text-xs text-gray-400">to</span>
                    <input
                        type="date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        min="{{ request('start_date') }}"
                        max="{{ now()->format('Y-m-d') }}"
                        title="To date"
                        class="w-[130px] rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-sm outline-none focus:border-[#108c2a]"
                    />
                </div>

                {{-- 3. Create Invoice Button (Pushed to the right) --}}
                @if (has_permission('invoices.create'))
                    <div class="ml-auto flex w-full shrink-0 sm:w-auto">
                        <a
                            href="{{ route('admin.invoices.create') }}"
                            class="bg-brand-500 hover:bg-brand-600 flex w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap text-white shadow-sm transition-colors sm:w-auto"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i> Create Invoice
                        </a>
                    </div>
                @endif
            </form>

            @if (!is_null($filteredCount))
                <div class="px-1 pt-3 text-xs text-gray-600 sm:text-sm">
                    <span class="font-bold text-gray-800">{{ number_format($filteredCount) }}</span>
                    invoice{{ $filteredCount === 1 ? '' : 's' }} found
                    @if (request('start_date') && request('end_date'))
                        between
                        <span
                            class="font-semibold"
                            >{{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}</span
                        >
                        and
                        <span
                            class="font-semibold"
                            >{{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</span
                        >
                    @elseif (request('start_date'))
                        from
                        <span
                            class="font-semibold"
                            >{{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}</span
                        >
                    @elseif (request('end_date'))
                        up to
                        <span
                            class="font-semibold"
                            >{{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}</span
                        >
                    @endif
                </div>
            @endif
        </div>

        {{-- DATA TABLE --}}
        <div
            id="invoices-list-container"
            class="flex flex-col overflow-hidden rounded-b-xl border border-gray-100 bg-white shadow-sm"
            @click="handlePaginationClick($event)"
        >
            {{-- 🖥️ DESKTOP VIEW (TABLE) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead
                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase"
                    >
                        <tr>
                            <th class="w-12 px-6 py-4 text-center">#</th>
                            <th class="px-6 py-4">INV DETAILS</th>
                            <th class="px-6 py-4">CUSTOMER</th>
                            {{-- UI Fix: Hide on mobile, show on iPad+ --}}
                            <th class="hidden px-6 py-4 md:table-cell">SOURCE</th>
                            <th class="px-6 py-4 text-center">STATUS</th>
                            <th class="px-6 py-4 text-center">PAYMENT</th>
                            <th class="px-6 py-4 text-right">PAID</th>
                            <th class="px-6 py-4 text-right">PENDING</th>
                            <th class="px-6 py-4 text-right">KASAR (₹)</th>
                            <th class="px-6 py-4 text-right">TOTAL AMOUNT</th>
                            <th class="px-6 py-4 text-right">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($invoices as $invoice)
                            {{-- 🌟 Calculate Paid/Pending/Kasar for this row --}}
                            @php
                                $paidAmt = $invoice->payments->where('status', 'completed')->sum('amount');
                                $rowReturned = $invoice->returns->where('status', 'confirmed')->sum('grand_total');
                                $rowKasar = $invoice->writeOffs->where('status', 'confirmed')->sum('amount');
                                $balanceDue = max(0, $invoice->grand_total - $paidAmt - $rowReturned - $rowKasar);
                                
                                // WhatsApp payment reminder
                                $customerPhone = $invoice->client?->phone ?? null;
                                $customerName = $invoice->customer_name ?: ($invoice->client->name ?? 'Customer');
                                $waText = urlencode(
                                    "🔔 *Payment Reminder*\n\n" .
                                    "Hi {$customerName}, this is a friendly reminder regarding *Invoice #{$invoice->invoice_number}*.\n\n" .
                                    "💰 Total Amount: ₹" . number_format($invoice->grand_total, 2) . "\n" .
                                    "✅ Paid: ₹" . number_format($paidAmt, 2) . "\n" .
                                    "🔴 Balance Due: ₹" . number_format($balanceDue, 2) . "\n" .
                                    ($invoice->due_date ? "📅 Due Date: " . $invoice->due_date->format('d M, Y') . "\n" : "") .
                                    "\nKindly clear the pending amount at your earliest convenience. Thank you! 🙏"
                                );
                            @endphp

                            <tr class="group transition-colors hover:bg-gray-50/50">
                                {{-- Serial Number Column --}}
                                <td class="px-6 py-4 text-center text-xs font-bold text-gray-400">
                                    #{{ $invoices->firstItem() + $loop->index }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a
                                            href="{{ route('admin.invoices.show', $invoice->id) }}"
                                            class="text-[13px] font-extrabold text-[#108c2a] hover:underline"
                                        >
                                            {{ $invoice->invoice_number }}
                                        </a>
                                        <span class="mt-0.5 text-[11px] font-medium text-gray-500">
                                            {{ $invoice->invoice_date->format('d M, Y') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-bold text-gray-800">
                                            {{ $invoice->customer_name ?: $invoice->client->name ?? 'Walk-in Customer' }}
                                        </span>
                                        <span
                                            class="mt-0.5 text-[11px] font-bold tracking-tighter text-gray-400 uppercase"
                                        >
                                            {{ $invoice->supply_state }}
                                        </span>
                                    </div>
                                </td>

                                <td class="hidden px-6 py-4 md:table-cell">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest {{ $invoice->source === 'pos' ? 'text-orange-500' : 'text-blue-500' }}"
                                    >
                                        {{ $invoice->source }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                                            'confirmed' => 'bg-green-50 text-green-700 border-green-200',
                                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                                        ];
                                        $color = $statusColors[$invoice->status] ?? $statusColors['draft'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $color }}"
                                    >
                                        {{ $invoice->status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @php
                                        $payColors = [
                                            'unpaid' => 'bg-red-50 text-red-600',
                                            'partial' => 'bg-blue-50 text-blue-600',
                                            'paid' => 'bg-green-50 text-green-700',
                                        ];
                                        $pColor = $payColors[$invoice->payment_status] ?? $payColors['unpaid'];
                                    @endphp
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $pColor }}"
                                    >
                                        {{ $invoice->payment_status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold text-green-700">₹{{ number_format($paidAmt, 2) }}</span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    @if ($balanceDue > 0)
                                        <span class="font-semibold text-red-600"
                                            >₹{{ number_format($balanceDue, 2) }}</span
                                        >
                                    @else
                                        <span class="text-gray-300">0.00</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    @if ($rowKasar > 0)
                                        <span
                                            class="rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700"
                                        >
                                            ₹{{ number_format($rowKasar, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">0.00</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span class="font-extrabold text-gray-800"
                                        >₹{{ number_format($invoice->grand_total, 2) }}</span
                                    >
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 transition-opacity">
                                        {{-- 1. View Button --}}
                                        @if (has_permission('invoices.view'))
                                            <a
                                                href="{{ route('admin.invoices.show', $invoice->id) }}"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                                                title="View Invoice"
                                            >
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </a>
                                        @endif

                                        @if ($invoice->status === 'confirmed' && has_permission('invoice_returns.create'))
                                            <a
                                                href="{{ route('admin.invoice-returns.create', $invoice->id) }}"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-gray-200 text-gray-600 text-red-600 transition-colors hover:bg-red-50"
                                                title="Create Return"
                                            >
                                                <i data-lucide="undo-2" class="h-4 w-4"></i>
                                            </a>
                                        @endif

                                        {{-- 🌟 Quick Pay Button (Only show if there is a balance and not cancelled) --}}
                                        @if ($invoice->status !== 'cancelled' && $balanceDue > 0 && has_permission('invoices.add_payment'))
                                            <button
                                                type="button"
                                                onclick="window.dispatchEvent(new CustomEvent('open-quick-payment', { detail: { dueAmount: {{ (float) $balanceDue }}, action: '{{ route('admin.invoices.pay', $invoice->id) }}' } }))"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-green-200 text-green-600 transition-colors hover:bg-green-50"
                                                title="Record Payment"
                                            >
                                                <i data-lucide="wallet" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if ($invoice->status !== 'cancelled' && $balanceDue > 0 && $customerPhone)
                                            <a
                                                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customerPhone) }}?text={{ $waText }}"
                                                target="_blank"
                                                class="flex h-8 w-8 items-center justify-center rounded border border-green-200 text-[#25D366] transition-colors hover:bg-[#25D366] hover:text-white"
                                                title="Send Payment Reminder on WhatsApp"
                                            >
                                                <i class="fa-brands fa-whatsapp text-lg"></i>
                                            </a>
                                        @endif

                                        @if ($invoice->status !== 'cancelled')
                                            {{-- 🌟 2. NEW: Edit Button --}}
                                            @if ($invoice->status !== 'confirmed' && has_permission('invoices.update'))
                                                <a
                                                    href="{{ route('admin.invoices.edit', $invoice->id) }}"
                                                    class="flex h-8 w-8 items-center justify-center rounded border border-blue-200 text-blue-500 transition-colors hover:bg-blue-50"
                                                    title="Edit Invoice"
                                                >
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </a>
                                            @endif

                                            {{-- 3. Cancel Button --}}
                                            <form
                                                action="{{ route('admin.invoices.destroy', $invoice->id) }}"
                                                method="POST"
                                                @submit.prevent="confirmCancel($event.target)"
                                                class="inline-block"
                                            >
                                                @csrf
                                                @method ('DELETE')
                                                <button
                                                    type="submit"
                                                    class="flex h-8 w-8 items-center justify-center rounded border border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                                    title="Cancel Invoice"
                                                >
                                                    <i data-lucide="x-circle" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i data-lucide="shopping-cart" class="mb-3 h-10 w-10 opacity-20"></i>
                                        <p class="text-sm font-medium">No sales invoices found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 📱 MOBILE VIEW (CARDS) --}}
            <div class="space-y-3 bg-gray-50/60 p-3 md:hidden">
                @forelse ($invoices as $invoice)
                    @php
                        $paidAmt = $invoice->payments->where('status', 'completed')->sum('amount');
                        $rowReturned = $invoice->returns->where('status', 'confirmed')->sum('grand_total');
                        $rowKasar = $invoice->writeOffs->where('status', 'confirmed')->sum('amount');
                        $balanceDue = max(0, $invoice->grand_total - $paidAmt - $rowReturned - $rowKasar);

                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'confirmed' => 'bg-green-50 text-green-700 border-green-200',
                            'cancelled' => 'bg-red-50 text-red-600 border-red-200',
                        ];
                        $color = $statusColors[$invoice->status] ?? $statusColors['draft'];

                        $payColors = [
                            'unpaid' => 'bg-red-50 text-red-600',
                            'partial' => 'bg-blue-50 text-blue-600',
                            'paid' => 'bg-green-50 text-green-700',
                        ];
                        $pColor = $payColors[$invoice->payment_status] ?? $payColors['unpaid'];
                        $customerPhone = $invoice->client?->phone ?? null;
                        $customerName = $invoice->customer_name ?: ($invoice->client->name ?? 'Customer');
                        $waText = urlencode(
                            "🔔 *Payment Reminder*\n\n" .
                            "Hi {$customerName}, this is a friendly reminder regarding *Invoice #{$invoice->invoice_number}*.\n\n" .
                            "💰 Total Amount: ₹" . number_format($invoice->grand_total, 2) . "\n" .
                            "✅ Paid: ₹" . number_format($paidAmt, 2) . "\n" .
                            "🔴 Balance Due: ₹" . number_format($balanceDue, 2) . "\n" .
                            ($invoice->due_date ? "📅 Due Date: " . $invoice->due_date->format('d M, Y') . "\n" : "") .
                            "\nKindly clear the pending amount at your earliest convenience. Thank you! 🙏"
                        );
                    @endphp
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md"
                    >
                        {{-- Header: Customer & Total --}}
                        <div class="flex items-start justify-between gap-2">
                            {{-- Customer Name Container with min-w-0 & flex-1 to strictly enforce truncate --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <span class="shrink-0 text-xs font-semibold text-gray-400"
                                        >#{{ $invoices->firstItem() + $loop->index }}.</span
                                    >
                                    <p
                                        class="truncate text-[14px] font-bold text-gray-800"
                                        title="{{ $invoice->customer_name ?: $invoice->client->name ?? 'Walk-in Customer' }}"
                                    >
                                        {{ $invoice->customer_name ?: $invoice->client->name ?? 'Walk-in Customer' }}
                                    </p>
                                </div>
                                <p class="mt-0.5 text-[11px] font-bold tracking-tighter text-gray-400 uppercase">
                                    {{ $invoice->supply_state }}
                                </p>
                            </div>

                            {{-- Price stay stable on the right side --}}
                            <div class="shrink-0 pl-1 text-right">
                                <span class="text-[16px] font-black text-[#108c2a]"
                                    >₹{{ number_format($invoice->grand_total, 2) }}</span
                                >
                            </div>
                        </div>

                        {{-- Invoice Details & Badges --}}
                        <div class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2.5">
                            <div class="flex items-center justify-between">
                                <a
                                    href="{{ route('admin.invoices.show', $invoice->id) }}"
                                    class="text-[13px] font-extrabold text-[#108c2a] hover:underline"
                                >
                                    {{ $invoice->invoice_number }}
                                </a>
                                <span class="text-[11px] font-medium text-gray-500">
                                    {{ $invoice->invoice_date->format('d M, Y') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 border-t border-gray-100/50 pt-1">
                                <span
                                    class="text-[9px] font-black uppercase tracking-widest {{ $invoice->source === 'pos' ? 'text-orange-500' : 'text-blue-500' }}"
                                >
                                    {{ $invoice->source }}
                                </span>
                                <span class="text-gray-300">|</span>
                                <span
                                    class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider border {{ $color }}"
                                >
                                    {{ $invoice->status }}
                                </span>
                                <span
                                    class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wider {{ $pColor }}"
                                >
                                    {{ $invoice->payment_status }}
                                </span>
                                @if ($rowKasar > 0)
                                    <span class="text-gray-300">|</span>
                                    <span
                                        class="rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[9px] font-extrabold tracking-wider text-amber-700 uppercase"
                                    >
                                        Kasar ₹{{ number_format($rowKasar, 2) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between border-t border-gray-100/50 pt-1 text-[11px]">
                                <span class="text-gray-500"
                                    >Paid:
                                    <span class="font-bold text-green-700"
                                        >₹{{ number_format($paidAmt, 2) }}</span
                                    ></span
                                >
                                <span class="text-gray-500"
                                    >Pending:
                                    <span class="font-bold {{ $balanceDue > 0 ? 'text-red-600' : 'text-gray-400' }}"
                                        >₹{{ number_format($balanceDue, 2) }}</span
                                    ></span
                                >
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-1">
                            @if (has_permission('invoices.view'))
                                <a
                                    href="{{ route('admin.invoices.show', $invoice->id) }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50"
                                    title="View Invoice"
                                >
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>
                            @endif

                            @if ($invoice->status === 'confirmed' && has_permission('invoice_returns.create'))
                                <a
                                    href="{{ route('admin.invoice-returns.create', $invoice->id) }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-red-600 transition-colors hover:bg-red-50"
                                    title="Create Return"
                                >
                                    <i data-lucide="undo-2" class="h-4 w-4"></i>
                                </a>
                            @endif

                            @if ($invoice->status !== 'cancelled' && $balanceDue > 0 && has_permission('invoices.add_payment'))
                                <button
                                    type="button"
                                    onclick="window.dispatchEvent(new CustomEvent('open-quick-payment', { detail: { dueAmount: {{ (float) $balanceDue }}, action: '{{ route('admin.invoices.pay', $invoice->id) }}' } }))"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-green-200 text-green-600 transition-colors hover:bg-green-50"
                                    title="Record Payment"
                                >
                                    <i data-lucide="wallet" class="h-4 w-4"></i>
                                </button>
                            @endif

                            @if ($invoice->status !== 'cancelled' && $balanceDue > 0 && $customerPhone)
                                <a
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customerPhone) }}?text={{ $waText }}"
                                    target="_blank"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-green-200 text-[#25D366] transition-colors hover:bg-[#25D366] hover:text-white"
                                    title="Send Payment Reminder on WhatsApp"
                                >
                                    <i class="fa-brands fa-whatsapp text-lg"></i>
                                </a>
                            @endif

                            @if ($invoice->status !== 'cancelled')
                                @if ($invoice->status !== 'confirmed' && has_permission('invoices.update'))
                                    <a
                                        href="{{ route('admin.invoices.edit', $invoice->id) }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 text-blue-500 transition-colors hover:bg-blue-50"
                                        title="Edit Invoice"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>
                                @endif

                                <form
                                    action="{{ route('admin.invoices.destroy', $invoice->id) }}"
                                    method="POST"
                                    @submit.prevent="confirmCancel($event.target)"
                                    class="inline-block"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50"
                                        title="Cancel Invoice"
                                    >
                                        <i data-lucide="x-circle" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 text-center text-sm text-gray-400">
                        <div class="flex flex-col items-center justify-center">
                            <i data-lucide="shopping-cart" class="mb-3 h-10 w-10 opacity-20"></i>
                            <p class="font-medium">No sales invoices found.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($invoices->hasPages())
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4">{{ $invoices->links() }}</div>
            @endif
        </div>

        {{-- STAT CARDS — summary of the current filtered result set --}}
        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-4">
                <div class="mb-1 flex items-center gap-1.5 text-xs font-bold tracking-wider text-emerald-700 uppercase">
                    <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i> Total Paid
                </div>
                <div class="text-xl font-black text-gray-900">₹{{ number_format($invoiceStats['total_paid'], 2) }}</div>
            </div>

            <div class="rounded-xl border border-red-200 bg-gradient-to-br from-red-50 to-red-100 p-4">
                <div class="mb-1 flex items-center gap-1.5 text-xs font-bold tracking-wider text-red-700 uppercase">
                    <i data-lucide="clock" class="h-3.5 w-3.5"></i> Total Pending
                </div>
                <div class="text-xl font-black text-gray-900">
                    ₹{{ number_format($invoiceStats['total_pending'], 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-4">
                <div class="mb-1 flex items-center gap-1.5 text-xs font-bold tracking-wider text-amber-700 uppercase">
                    <i data-lucide="scissors" class="h-3.5 w-3.5"></i> Total Kasar
                </div>
                <div class="text-xl font-black text-gray-900">
                    ₹{{ number_format($invoiceStats['total_kasar'], 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-violet-200 bg-gradient-to-br from-violet-50 to-violet-100 p-4">
                <div class="mb-1 flex items-center gap-1.5 text-xs font-bold tracking-wider text-violet-700 uppercase">
                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i> Total Amount
                </div>
                <div class="text-xl font-black text-gray-900">
                    ₹{{ number_format($invoiceStats['total_amount'], 2) }}
                </div>
            </div>
        </div>

        {{-- Single shared quick-payment modal — opened dynamically per row via window event --}}
        <x-modals.quick-payment
            action="#"
            :due-amount="0"
            :total-amount="0"
            :paid-amount="0"
            :payment-methods="$paymentMethods"
            title="Record Customer Payment"
            subtitle="Payment will be recorded against this invoice."
        />
    </div>
@endsection

@push ('scripts')
    <script>
        function invoiceIndex() {
            return {
                confirmCancel(form) {
                    BizAlert.confirm(
                        "Cancel Invoice?",
                        "This action will void the invoice and reverse any associated stock movements.",
                        "Yes, Cancel it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Cancelling...");
                            form.submit();
                        }
                    });
                },

                // --- SPA-Safe AJAX Search & Filter Logic ---
                hasActiveFilters: false,

                init() {
                    this.checkActiveFilters();
                    // Expose safely for external components if needed
                    window.submitInvoiceForm = () => this.submitForm();
                },

                handlePaginationClick(e) {
                    const pageLink = e.target.closest('a[href*="?page="]');
                    if (pageLink) {
                        e.preventDefault();
                        this.fetchResults(pageLink.href);
                    }
                },

                checkActiveFilters() {
                    const form = document.getElementById("invoice-filter-form");
                    if (!form) return;
                    const formData = new FormData(form);
                    this.hasActiveFilters = [...formData.entries()].some(([, v]) => v && String(v).trim() !== "");
                },

                submitForm() {
                    const form = document.getElementById("invoice-filter-form");
                    if (!form) return;
                    const url = new URL(form.action);
                    new FormData(form).forEach((v, k) => {
                        if (v) url.searchParams.set(k, v);
                    });

                    this.fetchResults(url.toString());
                },

                clearFilters() {
                    const form = document.getElementById("invoice-filter-form");
                    if (form) {
                        form.querySelectorAll(
                            'input[type="text"], input[type="search"], input[type="date"], select',
                        ).forEach((el) => {
                            el.value = "";
                            el.dispatchEvent(
                                new Event("change", {
                                    bubbles: true,
                                }),
                            );
                        });

                        // Reset external Alpine customer dropdowns if present
                        document.querySelectorAll("[x-data]").forEach((el) => {
                            if (el._x_dataStack) {
                                const d = el._x_dataStack[0];
                                if (d && "selected" in d && "customers" in d) {
                                    d.selected = null;
                                    d.search = "";
                                }
                            }
                        });

                        this.fetchResults(form.action);
                    }
                },

                fetchResults(url) {
                    const targetContainer = document.getElementById("invoices-list-container");
                    if (!targetContainer) return;

                    targetContainer.style.opacity = "0.5";
                    targetContainer.style.pointerEvents = "none";

                    fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    })
                        .then((res) => res.text())
                        .then((html) => {
                            const doc = new DOMParser().parseFromString(html, "text/html");
                            const newContainer = doc.getElementById("invoices-list-container");

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
            };
        }
    </script>
@endpush
