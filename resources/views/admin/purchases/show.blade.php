@extends ('layouts.admin')

@section ('title', 'Purchase Order: ' . $purchase->purchase_number)

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-slate-500 uppercase">Purchase Details</h1>
@endsection

@push ('styles')
    <style>
        /* ============================================================
           🖨️ PERFECT A4 PRINT OPTIMIZATION (Bulletproof)
           ============================================================ */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }

            /* 1. Reset html/body to allow edge-to-edge printing */
            html,
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 12px !important;
            }

            /* 2. Hide admin chrome */
            #main-sidebar,
            #sidebar-overlay,
            #nav-progress,
            #page-cover,
            header,
            footer {
                display: none !important;
            }

            /* 3. Break overflow-hidden chains from admin layout */
            body > div,
            body > div > div,
            #page-content {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
                flex: none !important;
            }

            /* 4. Document Area stays in normal flow to paginate correctly */
            #print-area {
                display: block !important;
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* 5. Handle horizontal scrolls on tables */
            .overflow-x-auto {
                overflow: visible !important;
            }

            table {
                width: 100% !important;
                table-layout: auto !important;
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
            }

            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
@endpush

@section ('content')
    @php
        /* ── Helpers ──────────────────────────────────────────── */
        $formatAmt = function ($amount) {
            $amount = (float) $amount;
            if ($amount == 0) return '0.00';
            return number_format($amount, 2, '.', ',');
        };

        /* ── Company & Supplier Info ──────────────────────────── */
        $companyInfo = $purchase->store ?? auth()->user()->company;
        $supplier = $purchase->supplier;

        /* ── Status Colors ────────────────────────────────────── */
        $statusColors = [
            'draft'              => 'bg-slate-100 text-slate-700 border-slate-200',
            'ordered'            => 'bg-blue-100 text-blue-700 border-blue-200',
            'partially_received' => 'bg-amber-100 text-amber-700 border-amber-200',
            'received'           => 'bg-green-100 text-green-700 border-green-200',
            'cancelled'          => 'bg-red-100 text-red-700 border-red-200',
        ];
        $payColors = [
            'unpaid'  => 'bg-red-100 text-red-700 border-red-200',
            'partial' => 'bg-amber-100 text-amber-700 border-amber-200',
            'paid'    => 'bg-green-100 text-green-700 border-green-200',
        ];

        $sColor = $statusColors[$purchase->status] ?? $statusColors['draft'];
        $pColor = $payColors[$purchase->payment_status] ?? $payColors['unpaid'];

        /* ── WhatsApp Message ─────────────────────────────────── */
        $waText = urlencode(
            "Purchase Order {$purchase->purchase_number} Details. Total: Rs. " . number_format($purchase->total_amount, 2)
        );
    @endphp

    <div class="space-y-4 pb-10">
        {{-- ══════════════════════════════════════════════════════
             1. ACTION BAR (Screen Only)
        ══════════════════════════════════════════════════════ --}}
        <div class="no-print mb-4 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div class="w-full sm:w-auto">
                <x-admin.breadcrumb
                    :items="[
                    ['label' => 'Purchases', 'url' => route('admin.purchases.index')],
                    ['label' => 'Purchase Details'],
                ]"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.purchases.index') }}"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Back
                </a>

                @if ($purchase->status !== 'received' && $purchase->status !== 'cancelled' && has_permission('purchases.edit'))
                    <a
                        href="{{ route('admin.purchases.edit', $purchase->id) }}"
                        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                    >
                        <i data-lucide="pencil" class="h-4 w-4"></i> Edit
                    </a>
                @endif

                @if ($purchase->status === 'received' && has_permission('purchase_returns.create'))
                    <a
                        href="{{ route('admin.purchase-returns.create', ['purchase_id' => $purchase->id]) }}"
                        class="flex items-center gap-1.5 rounded-lg border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-bold text-orange-700 shadow-sm transition-colors hover:bg-orange-100"
                    >
                        <i data-lucide="undo-2" class="h-4 w-4"></i> Return
                    </a>
                @endif

                @if ($purchase->payment_status !== 'paid' && $purchase->status === 'received' && has_permission('purchases.edit'))
                    <button
                        type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-quick-payment', { detail: {} }))"
                        class="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                    >
                        <i data-lucide="indian-rupee" class="h-4 w-4"></i> Record Payment
                    </button>
                @endif

                <button
                    onclick="window.print()"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-800 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i> Print
                </button>

                @if (has_permission('purchases.download_pdf'))
                    <a
                        href="{{ route('admin.purchases.pdf', $purchase->id) }}"
                        target="_blank"
                        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        title="Download PDF"
                    >
                        <i data-lucide="file-text" class="h-4 w-4"></i> PDF
                    </a>
                @endif

                <a
                    href="https://wa.me/?text={{ $waText }}"
                    target="_blank"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition-colors hover:border-[#1da851] hover:bg-[#e8fbf0] hover:text-[#1da851]"
                >
                    <i data-lucide="message-circle" class="h-4 w-4 text-[#25D366]"></i> Share
                </a>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             2. 📄 A4 DOCUMENT AREA
        ══════════════════════════════════════════════════════ --}}
        <div id="print-area" class="w-full rounded-sm bg-white p-6 text-slate-800 shadow-xl sm:p-12">
            {{-- ── Header ── --}}
            <div
                class="mb-8 flex flex-col items-start justify-between gap-6 border-b-2 border-slate-900 pb-6 sm:flex-row"
            >
                {{-- Left: Title & Meta --}}
                <div class="flex-1">
                    <h1 class="text-2xl leading-none font-black tracking-widest text-slate-900 uppercase sm:text-3xl">
                        Purchase Order
                    </h1>
                    <div class="mt-2 text-sm font-bold text-slate-500"># {{ $purchase->purchase_number }}</div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            class="px-2.5 py-1 rounded border text-[10px] font-bold uppercase tracking-wider {{ $sColor }}"
                        >
                            {{ ucfirst(str_replace('_', ' ', $purchase->status)) }}
                        </span>
                        <span
                            class="px-2.5 py-1 rounded border text-[10px] font-bold uppercase tracking-wider {{ $pColor }}"
                        >
                            {{ ucfirst($purchase->payment_status) }}
                        </span>
                    </div>
                </div>

                {{-- Right: Company Info --}}
                <div class="flex-shrink-0 text-left sm:text-right">
                    <h2 class="text-lg font-black text-slate-900 uppercase sm:text-xl">
                        {{ $companyInfo->name ?? 'N/A' }}
                    </h2>
                    @if ($companyInfo->email ?? false)
                        <div class="mt-1 text-sm text-slate-600">{{ $companyInfo->email }}</div>
                    @endif
                    @if ($companyInfo->phone ?? false)
                        <div class="text-sm text-slate-600">{{ $companyInfo->phone }}</div>
                    @endif

                    @if ($companyInfo->address ?? false)
                        <div class="mt-3 text-xs text-slate-500">
                            <div class="mb-0.5 font-bold tracking-wider text-slate-700 uppercase">Address</div>
                            <div class="leading-relaxed">{{ $companyInfo->address }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Supplier & Purchase Info ── --}}
            <div class="avoid-break mb-8 grid grid-cols-1 gap-8 sm:grid-cols-2">
                {{-- Supplier --}}
                <div>
                    <h3 class="mb-3 text-[10px] font-black tracking-widest text-slate-400 uppercase">Supplier Info</h3>
                    <div class="text-base font-bold text-slate-900">{{ $supplier->name ?? 'N/A' }}</div>

                    @if ($supplier->email)
                        <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
                            <i data-lucide="mail" class="no-print h-3.5 w-3.5 text-slate-400"></i>
                            {{ $supplier->email }}
                        </div>
                    @endif
                    @if ($supplier->phone)
                        <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
                            <i data-lucide="phone" class="no-print h-3.5 w-3.5 text-slate-400"></i>
                            {{ $supplier->phone }}
                        </div>
                    @endif
                    @if ($supplier->address)
                        <div class="mt-1.5 text-sm leading-snug text-slate-500">{{ $supplier->address }}</div>
                    @endif
                </div>

                {{-- Purchase Details Box --}}
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 sm:p-5">
                    <h3 class="mb-3 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Purchase Details
                    </h3>
                    <dl class="space-y-1.5 text-[13px]">
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Date</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $purchase->created_at ? $purchase->created_at->format('d M Y') : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Reference</dt>
                            <dd class="font-semibold text-slate-900">{{ $purchase->purchase_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Warehouse</dt>
                            <dd class="font-semibold text-slate-900">{{ $purchase->warehouse->name ?? 'N/A' }}</dd>
                        </div>
                        <div class="mt-2 flex justify-between border-t border-slate-200 pt-2">
                            <dt class="font-bold text-slate-500">Payment Status</dt>
                            <dd
                                class="font-black uppercase {{ $purchase->payment_status === 'paid' ? 'text-green-600' : ($purchase->payment_status === 'partial' ? 'text-amber-600' : 'text-red-600') }}"
                            >
                                {{ $purchase->payment_status }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- ── Items Table ── --}}
            <div class="mb-6 overflow-x-auto print:overflow-visible">
                <table class="w-full text-left text-sm print:text-[12px]">
                    <thead>
                        <tr
                            class="border-b-2 border-slate-800 text-[10px] font-black tracking-wider text-slate-500 uppercase"
                        >
                            <th class="pr-2 pb-3">Product Description</th>
                            @if (batch_enabled())
                                <th class="px-2 pb-3 text-center">Batch/Exp</th>
                            @endif
                            <th class="px-2 pb-3 text-center">Net Cost</th>
                            <th class="px-2 pb-3 text-center">Qty</th>
                            <th class="px-2 pb-3 text-center">Unit Cost</th>
                            <th class="px-2 pb-3 text-center">Disc.</th>
                            <th class="px-2 pb-3 text-center">Tax</th>
                            <th class="pb-3 pl-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 border-b border-slate-200">
                        @foreach ($purchase->items as $item)
                            <tr class="avoid-break group transition-colors hover:bg-slate-50/50">
                                <td class="py-3 pr-2 align-top">
                                    <div class="font-bold text-slate-900">
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="mt-0.5 font-mono text-[11px] text-slate-400">
                                        SKU: {{ $item->productSku->sku ?? 'N/A' }}
                                    </div>
                                </td>

                                @if (batch_enabled())
                                    <td class="px-2 py-3 text-center align-top text-[11px]">
                                        @if ($item->batch_number)
                                            <div class="font-mono font-semibold text-slate-700">
                                                {{ $item->batch_number }}
                                            </div>
                                        @else
                                            <div class="text-slate-400">—</div>
                                        @endif
                                        @if ($item->expiry_date)
                                            <div
                                                class="text-slate-500 mt-0.5 {{ $item->expiry_date->isPast() ? 'text-red-500 font-bold' : '' }}"
                                            >
                                                Exp: {{ $item->expiry_date->format('d/m/y') }}
                                            </div>
                                        @endif
                                    </td>
                                @endif

                                <td
                                    class="px-2 py-3 text-center align-top font-semibold whitespace-nowrap text-slate-700"
                                >
                                    ₹{{ $formatAmt($item->unit_cost) }}
                                </td>
                                <td class="px-2 py-3 text-center align-top font-bold text-slate-900">
                                    {{ (float) $item->quantity }}
                                </td>
                                <td class="px-2 py-3 text-center align-top whitespace-nowrap text-slate-600">
                                    ₹{{ $formatAmt($item->unit_cost) }}
                                </td>

                                <td class="px-2 py-3 text-center align-top leading-tight whitespace-nowrap">
                                    @if ($item->discount_amount > 0)
                                        @if ($item->discount_type === 'percentage' && (float) $item->discount_value > 0)
                                            <div class="font-medium text-slate-700">
                                                {{ (float) $item->discount_value }}%
                                            </div>
                                            <div class="mt-0.5 text-[10px] text-slate-400">
                                                (-₹{{ $formatAmt($item->discount_amount) }})
                                            </div>
                                        @else
                                            <div class="font-medium text-slate-700">
                                                ₹{{ $formatAmt($item->discount_amount) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                @php
                                    $baseAfterDisc = ($item->quantity * $item->unit_cost) - $item->discount_amount;
                                    $taxAmt = $item->tax_type === 'inclusive'
                                        ? $baseAfterDisc - $baseAfterDisc / (1 + $item->tax_percent / 100)
                                        : $baseAfterDisc * ($item->tax_percent / 100);
                                    
                                    $rowTotal = $item->total ?? $baseAfterDisc + ($item->tax_type === 'exclusive' ? $taxAmt : 0);
                                @endphp

                                <td class="px-2 py-3 text-center align-top whitespace-nowrap text-slate-600">
                                    ₹{{ $formatAmt($taxAmt) }}
                                </td>

                                <td class="py-3 pl-2 text-right align-top font-bold whitespace-nowrap text-slate-900">
                                    ₹{{ $formatAmt($rowTotal) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Totals & Notes ── --}}
            <div class="avoid-break flex flex-col items-end justify-between gap-6 pt-4 sm:flex-row">
                {{-- Left: Notes / Terms --}}
                <div class="order-2 w-full text-xs text-slate-500 sm:order-1 sm:w-1/2">
                    @if ($purchase->notes)
                        <div class="mb-4">
                            <h4 class="mb-1 text-[10px] font-bold tracking-wider text-slate-700 uppercase">Note:</h4>
                            <p class="leading-relaxed">{{ $purchase->notes }}</p>
                        </div>
                    @endif
                    @if ($purchase->terms_and_conditions)
                        <div>
                            <h4 class="mb-1 text-[10px] font-bold tracking-wider text-slate-700 uppercase">
                                Terms & Conditions:
                            </h4>
                            <p class="leading-relaxed whitespace-pre-line">{{ $purchase->terms_and_conditions }}</p>
                        </div>
                    @endif
                </div>

                {{-- Right: Calculation Box --}}
                <div class="order-1 w-full text-[13px] sm:order-2 sm:w-72">
                    <div class="flex justify-between py-1.5 text-slate-600">
                        <span class="font-medium">Order Tax</span>
                        <span class="font-bold text-slate-800">₹ {{ $formatAmt($purchase->tax_amount) }}</span>
                    </div>

                    @if ($purchase->discount_amount > 0)
                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span class="font-medium">
                                Discount
                                @if ($purchase->discount_type === 'percentage' && (float) $purchase->discount_value > 0)
                                    <span class="ml-1 text-[10px] font-normal text-slate-400"
                                        >({{ (float) $purchase->discount_value }}%)</span
                                    >
                                @endif
                            </span>
                            <span class="font-bold text-red-600"
                                >(-) ₹ {{ $formatAmt($purchase->discount_amount) }}</span
                            >
                        </div>
                    @endif

                    <div class="flex justify-between py-1.5 text-slate-600">
                        <span class="font-medium">Shipping</span>
                        <span class="font-bold text-slate-800">₹ {{ $formatAmt($purchase->shipping_cost) }}</span>
                    </div>

                    @if ($purchase->other_charges > 0)
                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span class="font-medium">Other Charges</span>
                            <span class="font-bold text-slate-800">₹ {{ $formatAmt($purchase->other_charges) }}</span>
                        </div>
                    @endif

                    @if ($purchase->round_off != 0)
                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span class="font-medium">Round Off</span>
                            <span class="font-bold text-slate-800">₹ {{ $formatAmt($purchase->round_off) }}</span>
                        </div>
                    @endif

                    <div class="mb-2 flex justify-between border-b border-slate-200 py-1.5 pb-3 text-slate-600">
                        <span class="font-medium">Paid Amount</span>
                        <span class="font-bold text-emerald-600"
                            >₹ {{ $formatAmt($purchase->total_amount - $purchase->balance_amount) }}</span
                        >
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <span class="text-[11px] font-black tracking-widest text-slate-900 uppercase">Grand Total</span>
                        <span class="text-lg leading-none font-black text-slate-900"
                            >₹ {{ $formatAmt($purchase->total_amount) }}</span
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             3. PAYMENT HISTORY (Screen Only)
        ══════════════════════════════════════════════════════ --}}
        @if ($purchase->payments->count() > 0)
            <div class="no-print mt-6 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                    <h3 class="flex items-center gap-2 text-[12px] font-black tracking-widest text-slate-600 uppercase">
                        <i data-lucide="history" class="h-4 w-4 text-slate-400"></i> Payment History
                    </h3>
                </div>
                <div class="divide-y divide-slate-50">
                    @foreach ($purchase->payments->where('status', 'completed') as $pay)
                        <div
                            class="flex flex-col justify-between gap-4 px-6 py-4 text-[13px] transition-colors hover:bg-slate-50/50 sm:flex-row sm:items-center"
                        >
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                >
                                    <i data-lucide="check" class="h-4 w-4 font-bold"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-black text-slate-800"
                                            >₹{{ number_format($pay->amount, 2) }}</span
                                        >
                                        <span
                                            class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[10px] text-slate-500"
                                            >{{ $pay->payment_number }}</span
                                        >
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-slate-500">
                                        Via
                                        <span
                                            class="font-medium text-slate-700"
                                            >{{ $pay->paymentMethod->label ?? '—' }}</span
                                        >
                                        @if ($pay->reference)
                                            <span class="mx-1">•</span>
                                            Ref:
                                            <span class="font-mono text-slate-600">{{ $pay->reference }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="ml-11 flex items-center gap-2 sm:ml-0 sm:block sm:text-right">
                                <div class="font-medium text-slate-700">
                                    {{ $pay->payment_date->format('d M Y, h:i A') }}
                                </div>
                                <div class="text-[11px] text-slate-400 sm:mt-0.5">
                                    By {{ $pay->creator->name ?? 'System' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Quick Payment Modal ── --}}
        @if ($purchase->payment_status !== 'paid')
            @php
                $completedPaid = $purchase->payments->where('status', 'completed')->sum('amount');
                $dueAmount = max(0, $purchase->total_amount - $completedPaid);
            @endphp
            <x-modals.quick-payment
                :action="route('admin.purchases.pay', $purchase->id)"
                :due-amount="$dueAmount"
                :total-amount="$purchase->total_amount"
                :paid-amount="$completedPaid"
                :payment-methods="$paymentMethods"
                title="Record Supplier Payment"
                subtitle="Payment will be recorded against this purchase order."
            />
        @endif
    </div>
@endsection
