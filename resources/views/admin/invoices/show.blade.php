@extends ('layouts.admin')

@section ('title', 'Invoice: ' . $invoice->invoice_number)

@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Invoice Details</h1>
@endsection

@push ('styles')
    <style>
        /* ============================================================
        🖨️  PRINT OPTIMIZATION — A4 PORTRAIT
        ============================================================ */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }

            /* ── 1. Reset html/body ──────────────────────────────── */
            html,
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: white !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* ── 2. Hide admin chrome ────────────────────────────── */
            #main-sidebar,
            #sidebar-overlay,
            #nav-progress,
            #page-cover,
            header {
                display: none !important;
            }

            /* ── 3. Break the overflow-hidden/h-screen chains ───── */
            /*    These are the containers that physically clip the  */
            /*    content and stop pagination working               */
            body > div,
            body > div > div {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
                flex: none !important;
            }

            /* ── 4. Reset the <main> scroll container ───────────── */
            #page-content {
                filter: grayscale(100%) !important;
                display: block !important;
                height: auto !important;
                overflow: visible !important;
                padding: 0 !important;
                flex: none !important;
            }

            /* ── 5. Hide the Qlinkon branding footer ─────────────── */
            #page-content > footer {
                display: none !important;
            }

            /* ── 6. #print-area stays in NORMAL FLOW ────────────── */
            /*    Static position = browser paginates correctly     */
            #print-area {
                display: block !important;
                position: static !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            /* ── 7. Visibility toggles ───────────────────────────── */
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            /* ── 8. Payment history: respect toggle state ────────── */
            .print-hidden {
                display: none !important;
            }

            /* ── 9. Page break controls ──────────────────────────── */
            .page-break-before {
                page-break-before: always;
                break-before: page;
            }

            .page-break-after {
                page-break-after: always;
                break-after: page;
            }

            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* ── 10. Table + typography ──────────────────────────── */
            .print-table-full {
                width: 100% !important;
            }

            .invoice-header-title {
                font-size: 20pt !important;
            }
        }

        /* ============================================================
                                                                       🖥️  SCREEN — Utility helpers
                                                                       ============================================================ */
        .print-only {
            display: none;
        }

        /* Status pill colours */
        .status-paid {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }

        .status-partial {
            background: #fef9c3;
            color: #854d0e;
            border-color: #fde047;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #64748b;
            border-color: #cbd5e1;
        }

        /* Payment timeline card */
        .payment-card {
            transition:
                box-shadow 0.15s,
                transform 0.15s;
        }

        .payment-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        /* Balance indicator gradient */
        .balance-bar {
            height: 4px;
            border-radius: 9999px;
        }

        /* Smooth toggle */
        #payment-history-body {
            transition:
                max-height 0.35s ease,
                opacity 0.25s ease;
            overflow: hidden;
        }
    </style>
@endpush

@section ('content')
    @php
        /* ── Helpers ──────────────────────────────────────────── */
        $fmt = fn($v) => '₹' . number_format((float) $v, 2, '.', ',');
        $spellOut = function ($amount) {
            if (!class_exists('NumberFormatter')) {
                return '';
            }
            $fmt = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            $rupees = floor((float) $amount);
            $paise = round(((float) $amount - $rupees) * 100);
            $str = ucwords($fmt->format($rupees));
            if ($paise > 0) {
                $str .= ' And ' . ucwords($fmt->format($paise)) . ' Paise';
            }
            return $str;
        };
        $amountInWords = $spellOut($invoice->grand_total);

        /* ── Invoice-page i18n (JSON dictionaries) ────────────── */
        $loadLang = fn($locale) => file_exists(resource_path("lang/{$locale}.json"))
            ? json_decode(file_get_contents(resource_path("lang/{$locale}.json")), true) ?? []
            : [];
        $invoiceI18n = [
            'en' => $loadLang('en'),
            'hi' => $loadLang('hi'),
            'gu' => $loadLang('gu'),
        ];

        /* ── Company & Store ──────────────────────────────────── */
        $company = $invoice->company ?? auth()->user()->company;
        $store = $invoice->store;

        /* Billing details belong to the store that issued this document.
           There is no company-level fallback — those settings were retired
           when billing moved onto Stores → Edit. */
        $billingGstin = $store?->gst_number;
        $billingUpiId = $store?->upi_id;
        $billingBankName = $store?->bank_name;
        $billingAccName = $store?->account_name;
        $billingAccNo = $store?->account_number;
        $billingIfsc = $store?->ifsc_code;

        // signature_url returns null when unset. Building the asset() path
        // unconditionally would yield "/storage/", which is truthy and renders
        // as a broken image.
        $billingSignatureUrl = $store?->signature_url;

        $billingFooterNote = $invoice->invoice_footer_note ?: $store?->invoice_footer_note;
        $billingTerms = $invoice->terms_conditions ?: $store?->invoice_terms;

        /* ── Indian State Code Map ────────────────────────────── */
        $stateCodes = [
            'Andhra Pradesh' => '37',
            'Arunachal Pradesh' => '12',
            'Assam' => '18',
            'Bihar' => '10',
            'Chhattisgarh' => '22',
            'Goa' => '30',
            'Gujarat' => '24',
            'Haryana' => '06',
            'Himachal Pradesh' => '02',
            'Jharkhand' => '20',
            'Karnataka' => '29',
            'Kerala' => '32',
            'Madhya Pradesh' => '23',
            'Maharashtra' => '27',
            'Manipur' => '14',
            'Meghalaya' => '17',
            'Mizoram' => '15',
            'Nagaland' => '13',
            'Odisha' => '21',
            'Punjab' => '03',
            'Rajasthan' => '08',
            'Sikkim' => '11',
            'Tamil Nadu' => '33',
            'Telangana' => '36',
            'Tripura' => '16',
            'Uttar Pradesh' => '09',
            'Uttarakhand' => '05',
            'West Bengal' => '19',
            'Andaman and Nicobar Islands' => '35',
            'Chandigarh' => '04',
            'Dadra and Nagar Haveli and Daman and Diu' => '26',
            'Delhi' => '07',
            'Jammu and Kashmir' => '01',
            'Ladakh' => '38',
            'Lakshadweep' => '31',
            'Puducherry' => '34',
        ];
        $stateCode = $stateCodes[$invoice->supply_state] ?? 'N/A';

        /* ── Customer ─────────────────────────────────────────── */
        $customerName = $invoice->client?->name ?? ($invoice->customer_name ?? 'Guest Customer');
        $customerPhone = $invoice->client?->phone ?? null;
        $customerAddress = $invoice->client?->address ?? null;
        $customerGSTIN = $invoice->client?->gst_number ?? ($invoice->customer_gstin ?? null);
        $invoiceType = !empty($customerGSTIN) ? 'B2B' : 'B2C';

        /* ── Tax Rates ────────────────────────────────────────── */
        $uniqueRates = $invoice->items->pluck('tax_percent')->unique();
        $isMixed = $uniqueRates->count() > 1;
        $baseRate = $uniqueRates->count() === 1 ? $uniqueRates->first() : 0;
        $igstRate = $isMixed ? 'Mixed' : (float) $baseRate . '%';
        $cgstRate = $isMixed ? 'Mixed' : (float) ($baseRate / 2) . '%';
        $sgstRate = $isMixed ? 'Mixed' : (float) ($baseRate / 2) . '%';
        /* ── Payments ─────────────────────────────────────────── */
        $completedPayments = $invoice->payments->where('status', 'completed');
        $paidAmt = $completedPayments->sum('amount');
        $totalReceived = $completedPayments->sum('amount_received');
        $totalChange = $completedPayments->sum('change_returned');
        $paymentCount = $completedPayments->count();

        /* First payment — the one taken at invoice creation. Sorted by id
           rather than date, since several payments can share a date and only
           insertion order tells them apart. paymentMethod is already eager
           loaded in the controller, so this costs no extra query. */
        $firstPayment = $completedPayments->sortBy('id')->first();
        $firstMethodLabel = $firstPayment?->paymentMethod?->label;
        $firstMethodSlug = $firstPayment?->paymentMethod?->slug ?? '';

        /* Same icon mapping the payment-method picker uses, so a method reads
           the same on the form and on the invoice. */
        $methodIcon = match ($firstMethodSlug) {
            'cash' => 'banknote',
            'upi' => 'qr-code',
            'card', 'credit_card' => 'credit-card',
            'bank_transfer' => 'landmark',
            'cheque' => 'scroll-text',
            default => 'wallet',
        };

        /* ── Returns & Write-offs (Kasar) ────────────────────────── */
        $confirmedReturns = $invoice->returns->where('status', 'confirmed');
        $totalReturned = $confirmedReturns->sum('grand_total');
        $confirmedWriteOffs = $invoice->writeOffs->where('status', 'confirmed');
        $writtenOffAmt = $confirmedWriteOffs->sum('amount');

        /* Balance Due = Grand Total - Completed Payments - Confirmed Returns - Confirmed Write-offs */
        $balanceDue = max(0, $invoice->grand_total - $paidAmt - $totalReturned - $writtenOffAmt);

        /* Payment status helper */
        $pStatusClass = match ($invoice->payment_status) {
            'paid' => 'status-paid',
            'partial' => 'status-partial',
            default => 'status-unpaid',
        };
        if ($invoice->status === 'cancelled') {
            $pStatusClass = 'status-cancelled';
        }

        /* Settlement percentage for bar — includes cash payments + returns + write-offs, not cash alone */
        $settledAmt = $invoice->grand_total - $balanceDue;
        $paidPct = $invoice->grand_total > 0 ? min(100, round(($settledAmt / $invoice->grand_total) * 100)) : 0;

        /* ── Batch (if enabled) ───────────────────────────────── */
        $batchMovements = collect();
        if (function_exists('batch_enabled') && batch_enabled()) {
            $batchMovements = $invoice->stockMovements
                ->where('direction', 'out')
                ->whereNotNull('batch_number')
                ->groupBy('product_sku_id');
        }

        /* ── WA message ───────────────────────────────────────── */
        $waText = urlencode(
            "Hello {$customerName},\nYour Invoice {$invoice->invoice_number} for " .
                number_format($invoice->grand_total, 2) .
                " is ready.\nBalance due: " .
                number_format($balanceDue, 2) .
                ".\nThank you!",
        );
    @endphp

    <div class="space-y-4 pb-10">
        {{-- ══════════════════════════════════════════════════════
         A. ACTION BAR  (screen only)
    ══════════════════════════════════════════════════════ --}}
        <div class="no-print flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div>
                <x-admin.breadcrumb
                    :items="[
                    ['label' => 'Invoices', 'url' => route('admin.invoices.index')],
                    ['label' => 'Invoice Details'],
                ]"
                />

                {{-- Audit trail. Deliberately outside #print-area so it never
                     reaches the customer's copy — this is internal information
                     about who raised the bill, not part of the document. --}}
                @if ($invoice->creator || $invoice->created_at)
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        @if ($invoice->creator)
                            <a
                                href="{{ route('admin.invoices.index', ['created_by' => $invoice->creator->id]) }}"
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-500 transition-colors hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700"
                                title="See all invoices created by {{ $invoice->creator->name }}"
                            >
                                <i data-lucide="user-round" class="h-3 w-3 text-gray-400"></i>
                                Created by
                                <span class="text-gray-800">{{ $invoice->creator->name }}</span>
                            </a>
                        @endif

                        @if ($invoice->created_at)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-500"
                            >
                                <i data-lucide="clock" class="h-3 w-3 text-gray-400"></i>
                                {{ $invoice->created_at->format('d M Y, h:i A') }}
                            </span>
                        @endif

                        @if ($invoice->source)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-bold text-gray-500"
                            >
                                <i data-lucide="tag" class="h-3 w-3 text-gray-400"></i>
                                {{ ucfirst(str_replace('_', ' ', $invoice->source)) }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-start gap-2 sm:justify-end">
                {{-- Language Switcher --}}
                <select
                    id="invoice-lang"
                    onchange="setInvoiceLanguage(this.value)"
                    class="rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs font-medium text-gray-700 shadow-sm"
                >
                    <option value="en">English</option>
                    <option value="hi">Hindi</option>
                    <option value="gu">Gujarati</option>
                </select>

                {{-- Back --}}
                {{-- <a
                    href="{{ route('admin.invoices.index') }}"
                    class="btn-outline flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Back
                </a> --}}

                {{-- Edit --}}
                @if ($invoice->status !== 'cancelled' && $invoice->status !== 'confirmed' && has_permission('invoices.update'))
                    <a
                        href="{{ route('admin.invoices.edit', $invoice->id) }}"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                    >
                        <i data-lucide="pencil" class="h-4 w-4"></i> Edit
                    </a>
                @endif

                {{-- Mark as Kasar --}}
                @if ($invoice->status !== 'cancelled' && $balanceDue > 0)
                    <button
                        type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-write-off', { detail: { dueAmount: {{ (float) $balanceDue }}, action: '{{ route('admin.invoices.write-off.store', $invoice->id) }}' } }))"
                        class="flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 shadow-sm transition-colors hover:bg-amber-100"
                    >
                        <i data-lucide="scissors" class="h-4 w-4"></i> Mark as Kasar
                    </button>
                @endif

                {{-- Print --}}
                <button
                    onclick="window.print()"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm transition-colors hover:bg-gray-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i> Print
                </button>

                {{-- PDF --}}
                @if (has_permission('invoices.download_pdf'))
                    {{-- <a
                        href="{{ route('admin.invoices.pdf', $invoice->id) }}"
                        target="_blank"
                        class="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                    >
                        <i data-lucide="download" class="h-4 w-4"></i> PDF
                    </a> --}}
                @endif

                {{-- WhatsApp --}}
                @if ($customerPhone)
                    <a
                        href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customerPhone) }}?text={{ $waText }}"
                        target="_blank"
                        class="flex items-center gap-1.5 rounded-lg bg-[#25D366] px-3 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#1da851]"
                    >
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.669.149-.198.297-.768.966-.941 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.058-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.173.198-.297.297-.495.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.371-.273.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.148.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.626.711.226 1.358.194 1.87.118.571-.085 1.758-.718 2.007-1.411.248-.694.248-1.289.173-1.411-.074-.124-.272-.198-.57-.347z"
                            />
                            <path
                                d="M12.004 2C6.486 2 2 6.484 2 12c0 1.991.585 3.847 1.589 5.407L2 22l4.75-1.557A9.956 9.956 0 0012.004 22C17.522 22 22 17.516 22 12S17.522 2 12.004 2z"
                            />
                        </svg>
                        WhatsApp
                    </a>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
         B1. SOURCE ORDER BANNER  (screen only)
    ══════════════════════════════════════════════════════ --}}
        @if ($invoice->sourceOrder)
            <div class="no-print rounded-xl border border-violet-200 bg-violet-50 p-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 rounded-lg bg-violet-600 p-2 text-white">
                            <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-violet-800">Generated from Order</h4>
                            <p class="text-xs text-violet-600">
                                This invoice was created from storefront order
                                <span class="font-black">{{ $invoice->sourceOrder->order_number }}</span>
                                · {{ $invoice->sourceOrder->created_at->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <a
                        href="{{ route('admin.orders.show', $invoice->sourceOrder->id) }}"
                        class="flex flex-shrink-0 items-center gap-1.5 rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-xs font-bold text-violet-700 shadow-sm transition-colors hover:bg-violet-50"
                    >
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        View Order {{ $invoice->sourceOrder->order_number }}
                    </a>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
         B. CREDIT NOTE ALERT  (screen only)
    ══════════════════════════════════════════════════════ --}}
        @if ($invoice->returns->count() > 0)
            <div class="no-print rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 rounded-lg bg-red-600 p-2 text-white">
                            <i data-lucide="undo-2" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-red-800">Linked Credit Notes</h4>
                            <p class="text-xs text-red-600">Items from this invoice have been returned.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($invoice->returns as $ret)
                            <a
                                href="{{ route('admin.invoice-returns.show', $ret->id) }}"
                                class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 shadow-sm transition-colors hover:bg-red-50"
                            >
                                VIEW {{ $ret->credit_note_number }} ({{ $fmt($ret->grand_total) }})
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
         B2. KASAR (WRITE-OFF) ALERT  (screen only)
    ══════════════════════════════════════════════════════ --}}
        @if ($confirmedWriteOffs->count() > 0)
            <div class="no-print rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 rounded-lg bg-amber-600 p-2 text-white">
                            <i data-lucide="scissors" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-amber-800">Kasar (Written Off)</h4>
                            <p class="text-xs text-amber-600">{{ $fmt($writtenOffAmt) }} of this invoice's balance has been written off.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($confirmedWriteOffs as $wo)
                            <span
                                class="rounded-lg border border-amber-200 bg-white px-3 py-1.5 text-xs font-bold text-amber-700 shadow-sm"
                            >
                                {{ $wo->write_off_number }} ({{ $fmt($wo->amount) }})
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
         C. FINANCIAL SUMMARY BANNER  (screen only)
    ══════════════════════════════════════════════════════ --}}
        <div class="no-print grid grid-cols-2 gap-3 lg:grid-cols-4">
            {{-- Grand Total --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase">Grand Total</div>
                <div class="text-xl font-black text-gray-900">{{ $fmt($invoice->grand_total) }}</div>
            </div>

            {{-- Total Received --}}
            <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase">Received</div>
                <div class="text-xl font-black text-green-600">{{ $fmt($paidAmt) }}</div>

                {{-- How the first payment came in. Answers "was this cash or UPI?"
                     without opening the payments list below. --}}
                @if ($firstMethodLabel)
                    <div class="mt-2 flex items-center gap-1.5 border-t border-gray-100 pt-2">
                        <i data-lucide="{{ $methodIcon }}" class="h-3.5 w-3.5 shrink-0 text-gray-400"></i>
                        <span class="truncate text-[11px] font-bold text-gray-600">{{ $firstMethodLabel }}</span>
                        @if ($paymentCount > 1)
                            <span class="ml-auto shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold text-gray-500">
                                +{{ $paymentCount - 1 }} more
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Balance Due --}}
            <div
                class="bg-white border {{ $balanceDue > 0 ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }} rounded-xl p-4 shadow-sm"
            >
                <div
                    class="text-xs font-bold {{ $balanceDue > 0 ? 'text-red-400' : 'text-green-500' }} uppercase tracking-wider mb-1"
                >
                    Balance Due
                </div>
                <div class="text-xl font-black {{ $balanceDue > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $fmt($balanceDue) }}
                </div>
            </div>

            {{-- Status + Payments Count --}}
            <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase">Status</div>
                <div class="flex flex-wrap items-center justify-between gap-1.5">
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border uppercase {{ $pStatusClass }}"
                    >
                        {{ $invoice->payment_status }}
                    </span>
                    @if ($invoice->settlement_status === 'closed' && $invoice->payment_status !== 'paid')
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 uppercase"
                        >
                            <i data-lucide="scissors" class="h-3 w-3"></i> Closed (Kasar)
                        </span>
                    @endif
                    @if ($paymentCount > 0)
                        <span class="text-xs font-medium text-gray-500"
                            >{{ $paymentCount }} payment{{ $paymentCount > 1 ? 's' : '' }}</span
                        >
                    @endif
                </div>
                {{-- Progress bar --}}
                <div class="balance-bar mt-3 bg-gray-100">
                    <div class="balance-bar bg-green-500 transition-all" style="width: {{ $paidPct }}%"></div>
                </div>
                <div class="mt-1 text-right text-[10px] text-gray-400">{{ $paidPct }}% settled</div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
         D. INVOICE DOCUMENT  (screen + print)
    ══════════════════════════════════════════════════════ --}}
        <div
            id="print-area"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white text-gray-800 shadow-sm print:rounded-none print:border-none print:shadow-none"
        >
            {{-- ─── SECTION 1: HEADER ──────────────────────────── --}}
            <div
                class="flex flex-col justify-between gap-6 border-b-2 border-gray-800 p-6 sm:p-8 md:flex-row print:flex-row"
            >
                {{-- Left: Invoice branding --}}
                <div class="flex-1">
                    <h1
                        class="invoice-header-title mb-0.5 text-2xl font-black tracking-widest text-gray-900 uppercase sm:text-3xl"
                        data-i18n="Tax Invoice"
                    >
                        {{ __('Tax Invoice') }}
                    </h1>
                    <div class="mb-3 text-sm font-bold text-gray-500"># {{ $invoice->invoice_number }}</div>

                    {{-- Status badge (screen) --}}
                    <div class="no-print flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border uppercase {{ $pStatusClass }}"
                        >
                            @if ($invoice->payment_status === 'paid')
                                <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                            @elseif ($invoice->payment_status === 'partial')
                                <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                            @else
                                <i data-lucide="alert-circle" class="h-3.5 w-3.5"></i>
                            @endif
                            {{ $invoice->payment_status }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                        >
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>

                    {{-- CANCELLED stamp --}}
                    @if ($invoice->status === 'cancelled')
                        <div
                            class="mt-3 inline-block -rotate-6 border-2 border-red-600 px-4 py-1 text-base font-black text-red-600 uppercase select-none"
                            data-i18n="CANCELLED"
                        >
                            {{ __('CANCELLED') }}
                        </div>
                    @endif
                </div>

                {{-- Right: Company info --}}
                <div
                    class="flex flex-col items-start gap-1 text-left text-sm md:items-end md:text-right print:items-end print:text-right"
                >
                    <h2 class="text-lg leading-tight font-black text-gray-900 uppercase sm:text-xl">
                        {{ $company->name }}
                    </h2>
                    @if ($billingGstin)
                        <div class="text-xs text-gray-500">
                            GSTIN: <span class="font-bold text-gray-800 uppercase">{{ $billingGstin }}</span>
                        </div>
                    @endif
                    <div class="text-xs text-gray-500">{{ $company->email }}</div>
                    <div class="text-xs text-gray-500">{{ $company->phone }}</div>

                    @if ($store)
                        <div class="mt-3 border-t border-gray-100 pt-3 text-left md:text-right print:text-right">
                            <div class="text-[12px] font-black tracking-widest text-gray-600 uppercase">
                                <span data-i18n="Branch">{{ __('Branch') }}</span>:
                                <span class="text-gray-700">{{ $store->name }}</span>
                            </div>
                            <div class="mt-0.5 text-[12px] leading-snug text-gray-600">
                                @if ($store->address)
                                    {{ $store->address }},
                                @endif
                                {{ $store->city }}{{ $store->city && $store->zip_code ? ', ' : '' }}{{ $store->zip_code }}<br />
                                {{ $store->state->name ?? '' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ─── SECTION 2: CUSTOMER & INVOICE META ─────────── --}}
            <div
                class="avoid-break grid grid-cols-1 gap-6 border-b border-gray-200 p-6 sm:p-8 md:grid-cols-2 print:grid-cols-2"
            >
                {{-- Billed To --}}
                <div>
                    <h3
                        class="mb-2 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                        data-i18n="Billed To"
                    >
                        {{ __('Billed To') }}
                    </h3>
                    <div class="text-base leading-tight font-bold text-gray-900">{{ $customerName }}</div>
                    @if ($customerGSTIN)
                        <div class="mt-0.5 text-xs font-bold text-gray-700 uppercase">GSTIN: {{ $customerGSTIN }}</div>
                    @endif
                    @if ($customerAddress)
                        <div class="mt-1 text-sm leading-snug text-gray-500">{{ $customerAddress }}</div>
                    @endif
                    @if ($customerPhone)
                        <div class="mt-1 text-sm text-gray-500">
                            <i data-lucide="phone" class="no-print mr-1 inline h-3 w-3"></i>{{ $customerPhone }}
                        </div>
                    @endif
                </div>

                {{-- Invoice Details --}}
                <div
                    class="rounded-lg border border-gray-100 bg-gray-50 p-4 md:rounded-none md:border-none md:bg-transparent md:p-0 print:bg-transparent"
                >
                    <h3
                        class="mb-2 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                        data-i18n="Invoice Details"
                    >
                        {{ __('Invoice Details') }}
                    </h3>
                    <dl class="space-y-1">
                        <div class="flex justify-between text-[13px]">
                            <dt class="font-medium text-gray-500" data-i18n="Invoice Date">Invoice Date</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                            </dd>
                        </div>
                        @if ($invoice->due_date)
                            <div class="flex justify-between text-[13px]">
                                <dt class="font-medium text-gray-500" data-i18n="Due Date">Due Date</dt>
                                <dd
                                    class="font-semibold {{ now()->greaterThan($invoice->due_date) && $balanceDue > 0 ? 'text-red-600' : 'text-gray-900' }}"
                                >
                                    {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between text-[13px]">
                            <dt class="font-medium text-gray-500" data-i18n="Place of Supply">Place of Supply</dt>
                            <dd class="font-bold text-gray-900">{{ $invoice->supply_state }} ({{ $stateCode }})</dd>
                        </div>
                        <div class="flex justify-between text-[13px]">
                            <dt class="font-medium text-gray-500" data-i18n="Invoice Type">Invoice Type</dt>
                            <dd class="font-bold text-gray-900 uppercase">{{ $invoiceType }}</dd>
                        </div>
                        <div class="flex justify-between text-[13px]">
                            <dt class="font-medium text-gray-500" data-i18n="Reverse Charge">Reverse Charge</dt>
                            <dd class="font-medium text-gray-900" data-i18n="No">No</dd>
                        </div>
                        <div class="mt-1 flex justify-between border-t border-gray-200 pt-2 text-[13px]">
                            <dt class="font-bold text-gray-500" data-i18n="Payment Status">Payment Status</dt>
                            <dd
                                class="font-black uppercase {{ $invoice->payment_status === 'paid' ? 'text-green-600' : ($invoice->payment_status === 'partial' ? 'text-amber-600' : 'text-red-600') }}"
                            >
                                {{ $invoice->payment_status }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- ─── SECTION 3: LINE ITEMS TABLE ────────────────── --}}
            <div class="px-6 py-5 sm:px-8">
                <div class="overflow-x-auto">
                    <table class="print-table-full w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b-2 border-gray-800 text-[11px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                <th class="pr-3 pb-3" data-i18n="Description">Description</th>
                                <th
                                    class="hidden px-2 pb-3 text-center sm:table-cell print:table-cell"
                                    data-i18n="HSN/SAC"
                                >
                                    HSN/SAC
                                </th>
                                <th class="px-2 pb-3 text-center" data-i18n="Qty">Qty</th>
                                <th class="px-2 pb-3 text-right" data-i18n="Rate">Rate</th>
                                <th
                                    class="hidden px-2 pb-3 text-right sm:table-cell print:table-cell"
                                    data-i18n="Disc."
                                >
                                    Disc.
                                </th>
                                <th class="px-2 pb-3 text-right" data-i18n="Tax">Tax</th>
                                <th class="pb-3 pl-2 text-right" data-i18n="Taxable Amt">Taxable Amt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($invoice->items as $item)
                                <tr class="avoid-break">
                                    <td class="py-3 pr-3">
                                        <div class="text-sm font-bold text-gray-900">{{ $item->product_name }}</div>
                                        <div class="mt-0.5 font-mono text-[11px] text-gray-400">
                                            <span data-i18n="SKU:">SKU:</span>
                                            {{ $item->sku->sku_code ?? ($item->sku->sku ?? 'N/A') }}
                                        </div>
                                        @if (!$batchMovements->isEmpty() && isset($batchMovements[$item->product_sku_id]))
                                            @foreach ($batchMovements[$item->product_sku_id] as $bm)
                                                <span
                                                    class="mt-1 inline-block rounded border border-blue-200 bg-blue-50 px-1.5 py-0.5 font-mono text-[10px] text-blue-700"
                                                >
                                                    <span data-i18n="Batch:">Batch:</span> {{ $bm->batch_number }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td
                                        class="hidden px-2 py-3 text-center text-[13px] text-gray-500 sm:table-cell print:table-cell"
                                    >
                                        {{ $item->hsn_code ?? '—' }}
                                    </td>
                                    <td class="px-2 py-3 text-center text-[13px] font-semibold text-gray-800">
                                        {{ (float) $item->quantity }}
                                    </td>
                                    <td class="px-2 py-3 text-right text-[13px] text-gray-600">
                                        {{ $fmt($item->unit_price) }}
                                    </td>
                                    <td
                                        class="hidden px-2 py-3 text-right text-[13px] text-gray-600 sm:table-cell print:table-cell"
                                    >
                                        @if ($item->discount_amount > 0)
                                            @if ($item->discount_type === 'percentage' && (float) $item->discount_value > 0)
                                                {{ (float) $item->discount_value }}%
                                                <div class="text-[10px] text-gray-400">
                                                    (-₹{{ number_format($item->discount_amount, 2) }})
                                                </div>
                                            @else
                                                {{ $fmt($item->discount_amount) }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-right text-[13px] text-gray-600">
                                        {{ $fmt($item->tax_amount) }}
                                        <div class="text-[10px] text-gray-400">({{ (float) $item->tax_percent }}%)</div>
                                    </td>
                                    <td class="py-3 pl-2 text-right text-[13px] font-bold text-gray-900">
                                        {{ $fmt($item->taxable_value) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─── SECTION 4: BANKING & TOTALS (Stays on Page 1) ──────────────────── --}}
            <div class="avoid-break border-t border-gray-100 px-6 py-4 sm:px-8">
                <div class="flex flex-col justify-between gap-6 md:flex-row print:flex-row">
                    {{-- Left: Bank Details & UPI --}}
                    <div class="flex-1 space-y-3">
                        @if ($billingBankName || $billingAccNo)
                            <div>
                                <h4
                                    class="mb-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                    data-i18n="Bank Details"
                                >
                                    Bank Details
                                </h4>
                                <div class="space-y-0.5 text-[12px] text-gray-700">
                                    @if ($billingBankName)
                                        <div>
                                            <span class="font-semibold" data-i18n="Bank:">Bank:</span>
                                            {{ $billingBankName }}
                                        </div>
                                    @endif
                                    @if ($billingAccName)
                                        <div>
                                            <span class="font-semibold" data-i18n="A/C Name:">A/C Name:</span>
                                            {{ $billingAccName }}
                                        </div>
                                    @endif
                                    @if ($billingAccNo)
                                        <div>
                                            <span class="font-semibold" data-i18n="A/C No:">A/C No:</span>
                                            {{ $billingAccNo }}
                                        </div>
                                    @endif
                                    @if ($billingIfsc)
                                        <div>
                                            <span class="font-semibold" data-i18n="IFSC:">IFSC:</span>
                                            {{ $billingIfsc }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- UPI QR (Slightly smaller for compact printing) --}}
                        @if ($billingUpiId)
                            <div>
                                <h4
                                    class="mb-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                    data-i18n="Pay via UPI"
                                >
                                    Pay via UPI
                                </h4>
                                @php
                                    $upiAmount = $balanceDue > 0 ? $balanceDue : $invoice->grand_total;
                                    $upiString =
                                        'upi://pay?pa=' .
                                        $billingUpiId .
                                        '&pn=' .
                                        urlencode($billingAccName ?: $company->name) .
                                        '&am=' .
                                        $upiAmount .
                                        '&cu=INR';
                                @endphp
                                <div class="inline-block rounded-lg border border-gray-200 bg-white p-1.5 shadow-sm">
                                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(70)->generate($upiString) !!}
                                </div>
                                <div class="mt-0.5 text-[10px] text-gray-400">{{ $billingUpiId }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Right: Totals Table --}}
                    <div class="w-full flex-shrink-0 md:w-72 print:w-72">
                        <table class="w-full text-[12px] sm:text-[13px]">
                            <tbody>
                                <tr>
                                    <td class="py-1 font-medium text-gray-500" data-i18n="Subtotal">Subtotal</td>
                                    <td class="py-1 text-right font-semibold text-gray-800">
                                        {{ $fmt($invoice->subtotal) }}
                                    </td>
                                </tr>

                                @if ($invoice->discount_amount > 0)
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500">
                                            <span data-i18n="Discount">Discount</span>
                                            @if ($invoice->discount_type === 'percentage')
                                                <span class="text-[10px] text-gray-400"
                                                    >({{ (float) $invoice->discount_value }}%)</span
                                                >
                                            @endif
                                        </td>
                                        <td class="py-1 text-right font-semibold text-red-600">
                                            (−) {{ $fmt($invoice->discount_amount) }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="border-t border-gray-100">
                                    <td class="py-1 font-bold text-gray-700" data-i18n="Taxable Amount">
                                        Taxable Amount
                                    </td>
                                    <td class="py-1 text-right font-bold text-gray-900">
                                        {{ $fmt($invoice->taxable_amount) }}
                                    </td>
                                </tr>
                                @if (isset($invoice->shipping_charge) && $invoice->shipping_charge > 0)
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500">
                                            <span data-i18n="Shipping">Shipping</span>
                                            @if (($invoice->shipping_tax_rate ?? 0) > 0)
                                                <span class="text-[10px] text-gray-400"
                                                    >(+{{ (float) $invoice->shipping_tax_rate }}% GST)</span
                                                >
                                            @else
                                                <span class="text-[10px] text-gray-400" data-i18n="(Exempt)"
                                                    >(Exempt)</span
                                                >
                                            @endif
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-800">
                                            {{ $fmt($invoice->shipping_charge) }}
                                        </td>
                                    </tr>
                                    @if (($invoice->shipping_tax_amount ?? 0) > 0)
                                        <tr>
                                            <td class="py-1 font-medium text-gray-500">
                                                @if ($invoice->igst_amount > 0)
                                                    IGST on Freight
                                                @else
                                                    GST on Freight
                                                @endif
                                                <span class="text-[10px] text-gray-400"
                                                    >({{ (float) $invoice->shipping_tax_rate }}%)</span
                                                >
                                            </td>
                                            <td class="py-1 text-right font-semibold text-gray-800">
                                                {{ $fmt($invoice->shipping_tax_amount) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endif
                                @if ($invoice->igst_amount > 0)
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500">
                                            IGST <span class="text-[10px] text-gray-400">({{ $igstRate }})</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-800">
                                            {{ $fmt($invoice->igst_amount) }}
                                        </td>
                                    </tr>
                                @elseif ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500">
                                            CGST <span class="text-[10px] text-gray-400">({{ $cgstRate }})</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-800">
                                            {{ $fmt($invoice->cgst_amount) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500">
                                            SGST <span class="text-[10px] text-gray-400">({{ $sgstRate }})</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-800">
                                            {{ $fmt($invoice->sgst_amount) }}
                                        </td>
                                    </tr>
                                @endif
                                @if (isset($invoice->round_off) && $invoice->round_off != 0)
                                    <tr>
                                        <td class="py-1 font-medium text-gray-500" data-i18n="Round Off">Round Off</td>
                                        <td class="py-1 text-right font-semibold text-gray-800">
                                            {{ $fmt($invoice->round_off) }}
                                        </td>
                                    </tr>
                                @endif
                                {{-- Grand Total --}}
                                <tr class="border-t-2 border-gray-900">
                                    <td
                                        class="py-1.5 text-[14px] font-black text-gray-900 uppercase"
                                        data-i18n="Grand Total"
                                    >
                                        Grand Total
                                    </td>
                                    <td class="py-1.5 text-right text-[15px] font-black text-gray-900">
                                        {{ $fmt($invoice->grand_total) }}
                                    </td>
                                </tr>
                                @if ($amountInWords)
                                    <tr class="border-t border-gray-100">
                                        <td colspan="2" class="pt-2 pb-1 text-left">
                                            <div
                                                class="text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                                data-i18n="Amount in Words"
                                            >
                                                Amount in Words
                                            </div>
                                            <div class="mt-0.5 text-xs font-semibold text-gray-700 italic">
                                                {{ $amountInWords }}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                {{-- Payment Ledger --}}
                                @if ($totalReceived > 0 || $writtenOffAmt > 0)
                                    @if ($totalReceived > 0)
                                        <tr class="border-t border-gray-100 text-gray-500">
                                            <td class="pt-1.5 pb-0.5 font-medium" data-i18n="Amt. Received">
                                                Amt. Received
                                            </td>
                                            <td class="pt-1.5 pb-0.5 text-right font-semibold text-green-700">
                                                {{ $fmt($totalReceived) }}
                                            </td>
                                        </tr>
                                        @if ($totalChange > 0)
                                            <tr class="text-gray-500">
                                                <td class="py-0.5 font-medium" data-i18n="Change Returned">
                                                    Change Returned
                                                </td>
                                                <td class="py-0.5 text-right font-semibold">
                                                    {{ $fmt($totalChange) }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endif
                                    @if ($writtenOffAmt > 0)
                                        <tr class="border-t border-gray-100 text-gray-500">
                                            <td
                                                class="py-1.5 text-[10px] font-black text-amber-700 uppercase"
                                                data-i18n="Kasar / Adjusted"
                                            >
                                                Kasar / Adjusted
                                            </td>
                                            <td class="py-1.5 text-right text-[12px] font-black text-amber-700">
                                                (-) {{ $fmt($writtenOffAmt) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($balanceDue > 0)
                                        <tr class="border-t border-gray-100 text-gray-500">
                                            <td
                                                class="py-1.5 text-[10px] font-black text-red-700 uppercase"
                                                data-i18n="Balance Due"
                                            >
                                                Balance Due
                                            </td>
                                            <td class="py-1.5 text-right text-[12px] font-black text-red-700">
                                                {{ $fmt($balanceDue) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ─── SECTION 5: NOTES, TERMS & SIGNATURE (Can break to next page) ─────────────── --}}
            <div class="border-t border-gray-200 px-6 py-5 sm:px-8">
                <div class="flex flex-col items-end justify-between gap-6 md:flex-row print:flex-row">
                    {{-- Left: Notes and Terms --}}
                    <div class="flex-1 space-y-3 text-xs text-gray-500">
                        @if ($invoice->notes)
                            <div>
                                <h4
                                    class="mb-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                    data-i18n="Note"
                                >
                                    Note
                                </h4>
                                <p class="leading-relaxed text-gray-700">{{ $invoice->notes }}</p>
                            </div>
                        @endif
                        {{-- @if ($billingFooterNote)
                        <div class="leading-relaxed">{!! nl2br(e($billingFooterNote)) !!}</div>
                    @endif --}}
                        {{-- @if ($billingTerms)
                        <div>
                            <strong class="text-gray-600 uppercase tracking-wider block mb-1">Terms & Conditions:</strong>
                            <div class="leading-relaxed">{!! nl2br(e($billingTerms)) !!}</div>
                        </div>
                    @endif --}}
                    </div>

                    {{-- Right: Authorized Signature --}}
                    <div class="avoid-break w-full flex-shrink-0 text-right md:w-64 print:w-64">
                        @if ($billingSignatureUrl)
                            <img
                                src="{{ $billingSignatureUrl }}"
                                alt="Authorized Signature"
                                class="mb-2 ml-auto max-h-16 object-contain opacity-90"
                            />
                        @else
                            <div class="h-12"></div>
                            {{-- Spacer if no signature image --}}
                        @endif
                        <div
                            class="mt-2 inline-block min-w-[160px] border-t border-gray-400 pt-1.5 text-center text-[11px] font-bold tracking-wider text-gray-700 uppercase"
                            data-i18n="Authorized Signatory"
                        >
                            Authorized Signatory
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── SECTION 5: FOOTER NOTE + TERMS ─────────────── --}}
            @if ($billingFooterNote || $billingTerms)
                <div
                    class="avoid-break space-y-3 border-t border-gray-200 bg-gray-50 px-6 py-5 text-xs text-gray-500 sm:px-8"
                >
                    @if ($billingFooterNote)
                        <div class="leading-relaxed">{!! nl2br(e($billingFooterNote)) !!}</div>
                    @endif
                    @if ($billingTerms)
                        <div>
                            <strong class="tracking-wider text-gray-600 uppercase" data-i18n="Terms & Conditions:"
                                >Terms & Conditions:</strong
                            >
                            <div class="mt-1 leading-relaxed">{!! nl2br(e($billingTerms)) !!}</div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ─── SECTION 6: PAYMENT HISTORY ─────────────────── --}}
            @if ($invoice->payments->count() > 0 || $confirmedWriteOffs->count() > 0)
                <div id="payment-history-section" class="page-break-before border-t-2 border-dashed border-gray-300">
                    {{-- Section header + toggle (screen only) --}}
                    <div
                        class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 sm:px-8"
                    >
                        <div class="flex items-center gap-3">
                            <div class="no-print rounded-lg bg-indigo-600 p-1.5 text-white">
                                <i data-lucide="credit-card" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black tracking-wide text-gray-800 uppercase">
                                    Payment History
                                </h3>
                                @php $historyEntryCount = $paymentCount + $confirmedWriteOffs->count(); @endphp
                                <p class="text-[11px] text-gray-400">{{ $historyEntryCount }} transaction{{ $historyEntryCount > 1 ? 's' : '' }} recorded</p>
                            </div>
                        </div>
                        {{-- Toggle button (screen only) --}}
                        <button
                            id="toggle-payment-history"
                            onclick="togglePaymentHistory()"
                            class="no-print flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 transition-colors hover:bg-indigo-100 hover:text-indigo-800"
                        >
                            <i data-lucide="eye-off" id="toggle-icon" class="h-3.5 w-3.5"></i>
                            <span id="toggle-label">Hide</span>
                        </button>
                    </div>

                    {{-- Payment cards container --}}
                    <div id="payment-history-body" class="space-y-3 px-6 py-5 sm:px-8">
                        {{-- Print-only summary row --}}
                        <div
                            class="print-only mb-4 flex flex-row items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-[12px]"
                        >
                            <div class="flex-1 border-r border-gray-200 text-center last:border-r-0">
                                <span class="block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                    >Total Billed</span
                                >
                                <span class="text-sm font-black text-gray-900">{{ $fmt($invoice->grand_total) }}</span>
                            </div>
                            <div class="flex-1 border-r border-gray-200 text-center last:border-r-0">
                                <span class="block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                    >Total Received</span
                                >
                                <span class="text-sm font-black text-green-700">{{ $fmt($paidAmt) }}</span>
                            </div>
                            @if ($writtenOffAmt > 0)
                                <div class="flex-1 border-r border-gray-200 text-center last:border-r-0">
                                    <span class="block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                        >Kasar / Adjusted</span
                                    >
                                    <span class="text-sm font-black text-amber-700">{{ $fmt($writtenOffAmt) }}</span>
                                </div>
                            @endif
                            <div class="flex-1 text-center">
                                <span class="block text-[10px] font-bold tracking-wider text-gray-500 uppercase"
                                    >Balance</span
                                >
                                <span
                                    class="font-black text-sm {{ $balanceDue > 0 ? 'text-red-700' : 'text-green-700' }}"
                                    >{{ $fmt($balanceDue) }}</span
                                >
                            </div>
                        </div>

                        @foreach ($invoice->payments->sortByDesc('payment_date') as $payment)
                            @php
                                $isCompleted = $payment->status === 'completed';
                                $isCancelled = $payment->status === 'cancelled';
                                $methodLabel = $payment->paymentMethod?->label ?? 'N/A';
                                $methodSlug = $payment->paymentMethod?->slug ?? '';
                                $creatorName = $payment->creator?->name ?? 'System';

                                $methodIcon = match (true) {
                                    str_contains($methodSlug, 'cash') => 'banknote',
                                    str_contains($methodSlug, 'upi') => 'scan-qr-code',
                                    str_contains($methodSlug, 'card') => 'credit-card',
                                    str_contains($methodSlug, 'bank') ||
                                        str_contains($methodSlug, 'neft') ||
                                        str_contains($methodSlug, 'rtgs')
                                        => 'landmark',
                                    str_contains($methodSlug, 'cheque') => 'file-text',
                                    default => 'wallet',
                                };
                                $borderColor = $isCompleted
                                    ? 'border-l-green-500'
                                    : ($isCancelled
                                        ? 'border-l-gray-300'
                                        : 'border-l-amber-400');
                            @endphp

                            <div
                                class="payment-card border border-gray-200 border-l-4 {{ $borderColor }} rounded-lg bg-white p-4 avoid-break"
                            >
                                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    {{-- Left: Icon + Main Info --}}
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="no-print flex-shrink-0 w-9 h-9 rounded-full {{ $isCompleted ? 'bg-green-100 text-green-700' : ($isCancelled ? 'bg-gray-100 text-gray-400' : 'bg-amber-100 text-amber-700') }} flex items-center justify-center mt-0.5"
                                        >
                                            <i data-lucide="{{ $methodIcon }}" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-gray-900"
                                                    ># {{ $payment->payment_number }}</span
                                                >
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border
                                        {{ $isCompleted ? 'bg-green-50 text-green-700 border-green-200' : ($isCancelled ? 'bg-gray-100 text-gray-500 border-gray-300' : 'bg-amber-50 text-amber-700 border-amber-200') }}"
                                                >
                                                    {{ $payment->status }}
                                                </span>
                                                <span class="font-mono text-[11px] text-gray-400">
                                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-[12px] text-gray-500">
                                                <span>
                                                    <span class="font-semibold text-gray-600">Method:</span>
                                                    {{ $methodLabel }}
                                                </span>
                                                @if ($payment->reference)
                                                    <span>
                                                        <span class="font-semibold text-gray-600">Ref:</span>
                                                        <span
                                                            class="font-mono text-gray-700"
                                                            >{{ $payment->reference }}</span
                                                        >
                                                    </span>
                                                @endif
                                                <span>
                                                    <span class="font-semibold text-gray-600">By:</span>
                                                    {{ $creatorName }}
                                                </span>
                                            </div>
                                            @if ($payment->notes)
                                                <p class="mt-1.5 text-[12px] leading-snug text-gray-400 italic">
                                                    {{ $payment->notes }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Right: Amounts --}}
                                    <div class="flex-shrink-0 pl-12 text-left sm:pl-0 sm:text-right">
                                        <div class="text-base font-black text-gray-900">
                                            {{ $fmt($payment->amount) }}
                                        </div>
                                        @if ($payment->amount_received > 0 && $payment->amount_received != $payment->amount)
                                            <div class="mt-0.5 text-[11px] text-gray-400">
                                                Received:
                                                <span
                                                    class="font-semibold text-gray-600"
                                                    >{{ $fmt($payment->amount_received) }}</span
                                                >
                                            </div>
                                        @endif
                                        @if ($payment->change_returned > 0)
                                            <div class="text-[11px] text-gray-400">
                                                Change:
                                                <span
                                                    class="font-semibold text-gray-600"
                                                    >{{ $fmt($payment->change_returned) }}</span
                                                >
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Kasar (Write-off) entries — shown alongside payments so the full settlement history is visible --}}
                        @foreach ($confirmedWriteOffs->sortByDesc('write_off_date') as $wo)
                            <div
                                class="payment-card avoid-break rounded-lg border border-l-4 border-amber-200 border-l-amber-500 bg-amber-50/40 p-4"
                            >
                                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    {{-- Left: Icon + Main Info --}}
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="no-print mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                                        >
                                            <i data-lucide="scissors" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-black text-gray-900"
                                                    ># {{ $wo->write_off_number }}</span
                                                >
                                                <span
                                                    class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 uppercase"
                                                >
                                                    Kasar
                                                </span>
                                                <span class="font-mono text-[11px] text-gray-400">
                                                    {{ \Carbon\Carbon::parse($wo->write_off_date)->format('d M Y') }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-[12px] text-gray-500">
                                                <span>
                                                    <span class="font-semibold text-gray-600">Reason:</span>
                                                    {{ ucwords(str_replace('_', ' ', $wo->reason)) }}
                                                </span>
                                                <span>
                                                    <span class="font-semibold text-gray-600">By:</span>
                                                    {{ $wo->creator?->name ?? 'System' }}
                                                </span>
                                            </div>
                                            @if ($wo->notes)
                                                <p class="mt-1.5 text-[12px] leading-snug text-gray-400 italic">
                                                    {{ $wo->notes }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Right: Amount --}}
                                    <div class="flex-shrink-0 pl-12 text-left sm:pl-0 sm:text-right">
                                        <div class="text-base font-black text-amber-700">
                                            (-) {{ $fmt($wo->amount) }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-gray-400">Non-cash adjustment</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Screen-only summary footer --}}
                        <div
                            class="no-print mt-4 flex flex-wrap justify-end gap-4 border-t border-gray-100 pt-4 text-[13px]"
                        >
                            <div class="text-gray-500">
                                Grand Total:
                                <span class="font-black text-gray-900">{{ $fmt($invoice->grand_total) }}</span>
                            </div>
                            <div class="text-gray-500">
                                Total Received: <span class="font-black text-green-700">{{ $fmt($paidAmt) }}</span>
                            </div>
                            @if ($writtenOffAmt > 0)
                                <div class="text-gray-500">
                                    Kasar / Adjusted:
                                    <span class="font-black text-amber-600">{{ $fmt($writtenOffAmt) }}</span>
                                </div>
                            @endif
                            @if ($balanceDue > 0)
                                <div class="text-gray-500">
                                    Balance Due: <span class="font-black text-red-600">{{ $fmt($balanceDue) }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1 font-bold text-green-700">
                                    <i data-lucide="check-circle-2" class="h-4 w-4"></i> Fully Settled
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
        {{-- /print-area --}}

        <x-modals.write-off
            :action="route('admin.invoices.write-off.store', $invoice->id)"
            :due-amount="$balanceDue"
            :total-amount="$invoice->grand_total"
            :paid-amount="$paidAmt"
            currency="₹"
        />
    </div>
    {{-- /pb-10 --}}
@endsection

@push ('scripts')
    <script>
        /**
         * Toggle payment history section visibility (screen only).
         * In print, the section is always shown via CSS.
         */
        function togglePaymentHistory() {
            const body = document.getElementById("payment-history-body");
            const section = document.getElementById("payment-history-section"); // whole section
            const icon = document.getElementById("toggle-icon");
            const label = document.getElementById("toggle-label");
            const isHidden = body.style.display === "none";

            if (isHidden) {
                body.style.display = "";
                section.classList.remove("print-hidden"); // ← show in print
                icon.setAttribute("data-lucide", "eye-off");
                label.textContent = "Hide";
            } else {
                body.style.display = "none";
                section.classList.add("print-hidden"); // ← hide in print
                icon.setAttribute("data-lucide", "eye");
                label.textContent = "Show";
            }

            if (typeof lucide !== "undefined") lucide.createIcons();
        }

        /**
         * Invoice label translation — client-side only (JSON dicts from server).
         */
        window.__invoiceI18n = @json ($invoiceI18n);

        function applyInvoiceLanguage(lang) {
            const dict = window.__invoiceI18n[lang] || {};
            document.querySelectorAll("[data-i18n]").forEach(function (el) {
                const key = el.getAttribute("data-i18n");
                el.textContent = dict[key] || key; // fallback: original English text
            });
            const select = document.getElementById("invoice-lang");
            if (select) select.value = lang;
        }

        function setInvoiceLanguage(lang) {
            localStorage.setItem("invoice_lang", lang);
            applyInvoiceLanguage(lang);
        }

        document.addEventListener("DOMContentLoaded", function () {
            const saved = localStorage.getItem("invoice_lang") || "en";
            applyInvoiceLanguage(saved);
        });
    </script>
@endpush
