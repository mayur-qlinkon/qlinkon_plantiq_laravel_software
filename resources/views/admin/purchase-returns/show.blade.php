@extends ('layouts.admin')

@section ('title', 'Purchase Return: ' . $purchaseReturn->return_number)

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-slate-500 uppercase">Purchase Return Details</h1>
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

            /* Alpine's x-teleport relocates modals to <body>, making them
               direct children that the `body > div` rule above force-shows.
               Anything Alpine has hidden inline must stay hidden in print. */
            [style*="display: none"] {
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
        $companyInfo = $purchaseReturn->store ?? auth()->user()->company;
        $supplier = $purchaseReturn->supplier;

        /* ── Status Colors ────────────────────────────────────── */
        $statusColors = [
            'draft'     => 'bg-slate-100 text-slate-700 border-slate-200',
            'returned'  => 'bg-green-100 text-green-700 border-green-200',
            'cancelled' => 'bg-red-100 text-red-700 border-red-200',
        ];
        $payColors = [
            'pending'  => 'bg-orange-100 text-orange-700 border-orange-200',
            'adjusted' => 'bg-blue-100 text-blue-700 border-blue-200',
            'refunded' => 'bg-green-100 text-green-700 border-green-200',
        ];

        $sColor = $statusColors[$purchaseReturn->status] ?? $statusColors['draft'];
        $pColor = $payColors[$purchaseReturn->payment_status] ?? $payColors['pending'];

        /* ── WhatsApp Message ─────────────────────────────────── */
        $waText = urlencode(
            "Debit Note / Purchase Return {$purchaseReturn->return_number} Details. Expected Refund: Rs. " . number_format($purchaseReturn->total_amount, 2)
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
                    ['label' => 'Purchase Returns', 'url' => route('admin.purchase-returns.index')],
                    ['label' => 'Return Details'],
                ]"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.purchase-returns.index') }}"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Back
                </a>

                @if ($purchaseReturn->status !== 'returned' && $purchaseReturn->status !== 'cancelled' && has_permission('purchase_returns.update'))
                    <a
                        href="{{ route('admin.purchase-returns.edit', $purchaseReturn->id) }}"
                        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                    >
                        <i data-lucide="pencil" class="h-4 w-4"></i> Edit
                    </a>
                @endif

                <button
                    onclick="window.print()"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-800 shadow-sm transition-colors hover:bg-slate-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i> Print
                </button>

                @if (has_permission('purchase_returns.download_pdf'))
                    <a
                        href="{{ route('admin.purchase-returns.pdf', $purchaseReturn->id) }}"
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
                        Debit Note
                    </h1>
                    <div class="mt-2 text-sm font-bold text-slate-500">
                        Return # {{ $purchaseReturn->return_number }}
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            class="px-2.5 py-1 rounded border text-[10px] font-bold uppercase tracking-wider {{ $sColor }}"
                        >
                            Status: {{ ucfirst($purchaseReturn->status) }}
                        </span>
                        <span
                            class="px-2.5 py-1 rounded border text-[10px] font-bold uppercase tracking-wider {{ $pColor }}"
                        >
                            Refund: {{ ucfirst($purchaseReturn->payment_status) }}
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

            {{-- ── Supplier & Return Info ── --}}
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

                {{-- Return Details Box --}}
                <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 sm:p-5">
                    <h3 class="mb-3 text-[10px] font-black tracking-widest text-slate-400 uppercase">Return Details</h3>
                    <dl class="space-y-1.5 text-[13px]">
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Date</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $purchaseReturn->return_date ? $purchaseReturn->return_date->format('d M Y') : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Orig. PO</dt>
                            <dd class="font-semibold">
                                <a
                                    href="{{ route('admin.purchases.show', $purchaseReturn->purchase_id) }}"
                                    class="no-print text-indigo-600 hover:underline"
                                >
                                    {{ $purchaseReturn->purchase->purchase_number }}
                                </a>
                                <span
                                    class="hidden text-slate-900 print:inline"
                                    >{{ $purchaseReturn->purchase->purchase_number }}</span
                                >
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="font-medium text-slate-500">Warehouse</dt>
                            <dd class="font-semibold text-slate-900">
                                {{ $purchaseReturn->warehouse->name ?? 'N/A' }}
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
                            <th class="px-2 pb-3">Reason</th>
                            <th class="px-2 pb-3 text-center">Unit Cost</th>
                            <th class="px-2 pb-3 text-center">Rtn Qty</th>
                            <th class="px-2 pb-3 text-center">Tax</th>
                            <th class="pb-3 pl-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 border-b border-slate-200">
                        @foreach ($purchaseReturn->items as $item)
                            <tr class="avoid-break group transition-colors hover:bg-slate-50/50">
                                <td class="py-3 pr-2 align-top">
                                    <div class="font-bold text-slate-900">
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="mt-0.5 font-mono text-[11px] text-slate-400">
                                        SKU: {{ $item->productSku->sku ?? 'N/A' }}
                                    </div>
                                </td>

                                <td class="px-2 py-3 align-top text-[11px] text-slate-600">
                                    <span
                                        class="inline-block rounded border border-slate-200 bg-slate-100 px-2 py-0.5 font-semibold tracking-wider uppercase"
                                    >
                                        {{ str_replace('_', ' ', $item->return_reason) }}
                                    </span>
                                </td>

                                <td
                                    class="px-2 py-3 text-center align-top font-semibold whitespace-nowrap text-slate-700"
                                >
                                    ₹{{ $formatAmt($item->unit_cost) }}
                                </td>
                                <td class="px-2 py-3 text-center align-top font-black text-red-600">
                                    -{{ (float) $item->quantity }}
                                </td>
                                <td class="px-2 py-3 text-center align-top whitespace-nowrap text-slate-600">
                                    ₹{{ $formatAmt($item->tax_amount) }}
                                </td>
                                <td class="py-3 pl-2 text-right align-top font-bold whitespace-nowrap text-slate-900">
                                    ₹{{ $formatAmt($item->total_price) }}
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
                    @if ($purchaseReturn->supplier_credit_note_number)
                        <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                            <h4 class="mb-1 text-[10px] font-bold tracking-wider text-blue-800 uppercase">
                                Supplier Credit Note Ref
                            </h4>
                            <p class="font-mono text-blue-900">{{ $purchaseReturn->supplier_credit_note_number }}</p>
                        </div>
                    @endif

                    @if ($purchaseReturn->reason)
                        <div class="mb-3">
                            <h4 class="mb-0.5 text-[10px] font-bold tracking-wider text-slate-700 uppercase">
                                Return Summary Reason:
                            </h4>
                            <p class="leading-relaxed text-slate-600">{{ $purchaseReturn->reason }}</p>
                        </div>
                    @endif

                    @if ($purchaseReturn->notes)
                        <div>
                            <h4 class="mb-0.5 text-[10px] font-bold tracking-wider text-slate-700 uppercase">
                                Additional Notes:
                            </h4>
                            <p class="leading-relaxed whitespace-pre-line">{{ $purchaseReturn->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Right: Calculation Box --}}
                <div class="order-1 w-full text-[13px] sm:order-2 sm:w-80">
                    <div class="flex justify-between py-1.5 text-slate-600">
                        <span class="font-medium">Taxable Value Return</span>
                        <span class="font-bold text-slate-800"
                            >₹ {{ $formatAmt($purchaseReturn->taxable_amount) }}</span
                        >
                    </div>

                    <div class="mb-2 flex justify-between border-b border-slate-200 py-1.5 pb-3 text-slate-600">
                        <span class="font-medium">Total Tax Reversal</span>
                        <span class="font-bold text-slate-800">₹ {{ $formatAmt($purchaseReturn->tax_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between py-2 text-emerald-600">
                        <span class="text-[11px] font-black tracking-widest uppercase">Total Refund Expected</span>
                        <span class="text-lg leading-none font-black"
                            >₹ {{ $formatAmt($purchaseReturn->total_amount) }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
