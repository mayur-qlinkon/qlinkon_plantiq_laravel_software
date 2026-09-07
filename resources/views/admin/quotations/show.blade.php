@extends ('layouts.admin')

@section ('title', 'Quotation: ' . $quotation->quotation_number)
@section ('header-title')
    <h1 class="text-sm font-bold tracking-widest text-gray-500 uppercase">Quotation Details</h1>
@endsection
@push ('styles')
    <style>
        /* 🖨️ A4 PRINT OPTIMIZATION */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: white !important;
            }

            /* Hide everything outside the print area */
            body * {
                visibility: hidden;
            }

            /* Make print area and its children visible */
            #print-area,
            #print-area * {
                visibility: visible;
            }

            /* Reset print area positioning */
            #print-area {
                filter: grayscale(100%) !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none !important;
                box-shadow: none !important;
                box-sizing: border-box !important;
            }

            /* Utility classes for print */
            .no-print {
                display: none !important;
            }

            .page-break-avoid {
                page-break-inside: avoid;
            }

            /* Force specific elements to behave on paper */
            .print-grid-2 {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 1.5rem !important;
            }

            .print-flex-row {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
            }

            .print-text-right {
                text-align: right !important;
            }

            .print-w-half {
                width: 50% !important;
            }
        }
    </style>
@endpush

@section ('content')
    @php
        // Clean currency formatter
        $formatAmt = function ($amount) {
            return number_format((float) $amount, 2, '.', ',');
        };

        // Company Details
        $company = $quotation->company ?? auth()->user()->company;
        $store = $quotation->store;

        // Signature belongs to the store that issued this quotation. There is
        // no company-level fallback — that setting was retired when billing
        // moved onto Stores → Edit. signature_url returns null when unset, so
        // the block below simply renders the line without an image.
        $billingSignatureUrl = $store?->signature_url;

        // Indian State Code Mapping
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

        // Customer Details
        $customerName = $quotation->customer
            ? $quotation->customer->name
            : $quotation->customer_name ?? 'Guest/Prospect';
        $customerPhone = $quotation->customer ? $quotation->customer->phone : $quotation->customer_phone ?? 'N/A';
        $customerAddress = $quotation->customer ? $quotation->customer->address : 'N/A';
        $customerGSTIN = $quotation->customer ? $quotation->customer->gst_number : $quotation->customer_gstin ?? null;

        // Determine Type (If GST exists, it is B2B)
        $quoteType = !empty($customerGSTIN) ? 'B2B' : 'B2C';
        $stateCode = $stateCodes[$quotation->supply_state] ?? 'N/A';

        // Expiry Status Check
        $isExpired =
            $quotation->valid_until && \Carbon\Carbon::now()->startOfDay()->greaterThan($quotation->valid_until);
    @endphp

    <div class="pb-10" x-data="quotationShow()">
        {{-- ACTION BAR (Hidden on Print) --}}
        <div class="no-print mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div class="w-full sm:w-auto">
                <x-admin.breadcrumb
                    :items="[
                    ['label' => 'Quotations', 'url' => route('admin.quotations.index')],
                    ['label' => 'Quotation Details'],
                ]"
                />
            </div>

            {{-- UI Fix: Buttons wrap and fill width on mobile --}}
            <div class="flex w-full flex-wrap items-center gap-2 md:w-auto">
                <a
                    href="{{ route('admin.quotations.index') }}"
                    class="flex flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 sm:flex-none"
                >
                    <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i> Back
                </a>

                @if ($quotation->status !== 'converted')
                    <a
                        href="{{ route('admin.quotations.edit', $quotation->id) }}"
                        class="flex flex-1 items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 sm:flex-none"
                    >
                        <i data-lucide="pencil" class="mr-2 h-4 w-4"></i> Edit
                    </a>
                @endif

                <button
                    onclick="window.print()"
                    class="mt-2 flex w-full flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-800 shadow-sm transition-colors hover:bg-gray-50 sm:mt-0 sm:w-auto sm:flex-none"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i> Print
                </button>

                @if (has_permission('quotations.download_pdf'))
                    <a
                        href="{{ route('admin.quotations.pdf', $quotation->id) }}"
                        target="_blank"
                        class="mt-2 flex w-full flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-blue-700 sm:mt-0 sm:w-auto sm:flex-none"
                    >
                        <i data-lucide="download" class="h-4 w-4"></i> PDF
                    </a>
                @endif

                @php
                    $waText = urlencode(
                        "Hello {$customerName},\nHere is your Quotation {$quotation->quotation_number} for Rs. " .
                            $formatAmt($quotation->grand_total) .
                            ".\nPlease let us know if you have any questions!",
                    );
                @endphp
                <a
                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customerPhone) }}?text={{ $waText }}"
                    target="_blank"
                    class="mt-2 flex w-full flex-1 items-center justify-center gap-2 rounded-lg bg-[#25D366] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#1da851] sm:mt-0 sm:w-auto sm:flex-none"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.669.149-.198.297-.768.966-.941 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.058-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.173.198-.297.297-.495.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.371-.273.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.148.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.626.711.226 1.358.194 1.87.118.571-.085 1.758-.718 2.007-1.411.248-.694.248-1.289.173-1.411-.074-.124-.272-.198-.57-.347z" />
                        <path d="M12.004 2C6.486 2 2 6.484 2 12c0 1.991.585 3.847 1.589 5.407L2 22l4.75-1.557A9.956 9.956 0 0012.004 22C17.522 22 22 17.516 22 12S17.522 2 12.004 2z" />
                    </svg>
                    WhatsApp
                </a>

                @if ($quotation->status !== 'converted')
                    <form
                        action="{{ route('admin.quotations.convert', $quotation->id) }}"
                        method="POST"
                        @submit.prevent="confirmConvert($event.target)"
                        class="mt-2 w-full flex-1 sm:mt-0 sm:w-auto sm:flex-none"
                    >
                        @csrf
                        <button
                            type="submit"
                            x-ref="convertBtn"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-black disabled:opacity-60"
                        >
                            <i data-lucide="file-check-2" class="h-4 w-4"></i> Convert to Invoice
                        </button>
                    </form>
                @else
                    @if ($quotation->invoice)
                        <a
                            href="{{ route('admin.invoices.show', $quotation->invoice->id) }}"
                            class="mt-2 flex flex-1 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-indigo-700 sm:mt-0 sm:flex-none"
                        >
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                            View Invoice &nbsp;<span
                                class="font-mono opacity-90"
                                >{{ $quotation->invoice->invoice_number }}</span
                            >
                        </a>
                    @endif
                @endif
            </div>
        </div>

        {{-- 📄 THE QUOTATION UI (Fluid on web, Strict on Print) --}}
        <div
            id="print-area"
            class="w-full rounded-xl border border-gray-200 bg-white font-sans text-[#212538] shadow-sm print:rounded-none print:border-none print:shadow-none"
        >
            <div class="p-6 md:p-10 print:px-8 print:py-4">
                {{-- Header Row --}}
                {{-- UI Fix: Replaced invalid custom print classes with standard Tailwind print:* classes --}}
                <div
                    class="mb-8 flex flex-col items-start justify-between gap-6 border-b-2 border-gray-900 pb-6 md:flex-row print:flex-row"
                >
                    <div class="w-full md:w-auto">
                        <h1 class="mb-1 text-3xl font-black tracking-widest text-gray-900 uppercase">QUOTATION</h1>
                        <div class="mb-2 text-[15px] font-bold text-gray-800"># {{ $quotation->quotation_number }}</div>

                        <div class="text-[13px] leading-relaxed text-gray-700">
                            <span class="font-bold text-gray-500">Date:</span>
                            {{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}<br />
                            @if ($quotation->valid_until)
                                <span class="font-bold text-gray-500">Valid Until:</span>
                                <span
                                    class="{{ $isExpired && $quotation->status !== 'converted' ? 'text-red-600 font-bold' : '' }}"
                                >
                                    {{ \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') }}
                                </span>
                            @endif
                        </div>

                        @if ($quotation->status === 'converted')
                            <div class="no-print mt-3 flex flex-wrap items-center gap-2">
                                <div
                                    class="inline-block -rotate-2 transform border-2 border-indigo-600 px-2 py-0.5 text-xs font-black text-indigo-600 uppercase"
                                >
                                    CONVERTED
                                </div>
                                @if ($quotation->invoice)
                                    <a
                                        href="{{ route('admin.invoices.show', $quotation->invoice->id) }}"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 transition-colors hover:bg-indigo-100"
                                    >
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        {{ $quotation->invoice->invoice_number }}
                                    </a>
                                @endif
                            </div>
                        @elseif ($isExpired)
                            <div
                                class="no-print mt-3 inline-block -rotate-2 transform border-2 border-red-600 px-2 py-0.5 text-xs font-black text-red-600 uppercase"
                            >
                                EXPIRED
                            </div>
                        @endif
                    </div>

                    {{-- UI Fix: flex-col items-start md:items-end ensures perfect alignment on mobile and desktop --}}
                    <div
                        class="flex w-full flex-col items-start text-left text-sm text-gray-800 md:w-auto md:items-end md:text-right print:items-end print:text-right"
                    >
                        <div class="text-xl font-black text-gray-900 uppercase">{{ $company->name }}</div>
                        <div class="mt-1 text-[13px] leading-relaxed text-gray-600">
                            @if ($company->gst_number || $company->gstin)
                                GSTIN:
                                <span
                                    class="font-bold text-gray-900 uppercase"
                                    >{{ $company->gst_number ?? $company->gstin }}</span
                                ><br />
                            @endif
                            Email: {{ $company->email }}<br />
                            Phone: {{ $company->phone }}
                        </div>

                        @if ($store)
                            <div class="mt-4 text-left text-[13px] leading-relaxed md:text-right print:text-right">
                                <div class="font-bold text-gray-900">Branch:</div>
                                <div class="text-gray-600">
                                    {{ $store->name }}{{ $store->city ? ' - ' . $store->city : '' }}<br />
                                    {{ $store->state->name ?? $store->state_id }}{{ $store->zip_code ? ' - ' . $store->zip_code : '' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Billed To & Meta Data --}}
                {{-- UI Fix: Stack on mobile (grid-cols-1), separate on md/print --}}
                <div class="mb-10 grid grid-cols-1 gap-8 text-sm text-gray-800 md:grid-cols-2 print:grid-cols-2">
                    <div>
                        <div class="mb-2 text-xs font-black tracking-widest text-gray-400 uppercase">Quotation To</div>
                        <div class="mb-1 text-base font-bold text-gray-900">{{ $customerName }}</div>
                        <div class="text-[13px] leading-relaxed text-gray-600">
                            @if ($customerGSTIN)
                                <span class="font-bold text-gray-900 uppercase">GSTIN: {{ $customerGSTIN }}</span
                                ><br />
                            @endif
                            @if ($customerAddress !== 'N/A')
                                {{ $customerAddress }}<br
                                 />
                            @endif
                            @if ($customerPhone !== 'N/A')
                                Phone: {{ $customerPhone }}<br
                                 />
                            @endif
                            @if ($quotation->customer_email)
                                Email: {{ $quotation->customer_email }}
                            @endif
                        </div>
                    </div>

                    {{-- UI Fix: Added bg-gray-50 on mobile for clear separation --}}
                    <div
                        class="space-y-1.5 rounded-lg bg-gray-50 p-4 text-[13px] md:rounded-none md:bg-transparent md:p-0 print:bg-transparent"
                    >
                        <div class="grid grid-cols-2">
                            <span class="font-bold text-gray-500">Place of Supply:</span>
                            <span class="text-right font-bold md:text-left print:text-right"
                                >{{ $quotation->supply_state }} ({{ $stateCode }})</span
                            >
                        </div>
                        <div class="grid grid-cols-2">
                            <span class="font-bold text-gray-500">Quotation Type:</span>
                            <span class="text-right font-bold md:text-left print:text-right">{{ $quoteType }}</span>
                        </div>
                        <div class="grid grid-cols-2">
                            <span class="font-bold text-gray-500">Status:</span>
                            <span
                                class="font-bold uppercase text-right md:text-left print:text-right {{ $quotation->status === 'converted' ? 'text-indigo-600' : ($quotation->status === 'rejected' ? 'text-red-500' : 'text-[#108c2a]') }}"
                                >{{ $quotation->status }}</span
                            >
                        </div>
                        @if ($quotation->reference_number)
                            <div class="grid grid-cols-2">
                                <span class="font-bold text-gray-500">Reference / PO:</span>
                                <span
                                    class="text-right font-bold md:text-left print:text-right"
                                    >{{ $quotation->reference_number }}</span
                                >
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Products Table --}}
                <div class="mb-10 overflow-x-auto print:overflow-visible">
                    <table class="w-full min-w-[700px] border-collapse text-sm print:min-w-0">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="rounded-tl-sm px-4 py-3 text-left font-bold">Description</th>
                                <th class="px-4 py-3 text-center font-bold">HSN</th>
                                <th class="px-4 py-3 text-center font-bold">Qty</th>
                                <th class="px-4 py-3 text-right font-bold">Rate</th>
                                <th class="px-4 py-3 text-center font-bold">Disc</th>
                                <th class="px-4 py-3 text-center font-bold">GST</th>
                                <th class="rounded-tr-sm px-4 py-3 text-right font-bold">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="border-b border-gray-200">
                            @foreach ($quotation->items as $item)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-bold text-gray-900">{{ $item->product_name }}</div>
                                        <div class="mt-0.5 font-mono text-[11px] text-gray-500">
                                            SKU: {{ $item->sku_code ?? ($item->sku->sku ?? 'N/A') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center align-top text-gray-600">
                                        {{ $item->hsn_code ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-center align-top font-bold text-gray-800">
                                        {{ (float) $item->quantity }}
                                    </td>
                                    <td class="px-4 py-4 text-right align-top text-gray-600">
                                        ₹{{ $formatAmt($item->unit_price) }}
                                    </td>
                                    <td class="px-4 py-4 text-center align-top text-gray-600">
                                        @if ($item->discount_amount > 0)
                                            @if ($item->discount_type === 'percentage')
                                                <div class="font-bold text-gray-800">
                                                    {{ (float) $item->discount_value }}%
                                                </div>
                                                <div class="mt-0.5 text-[11px] text-gray-500">
                                                    (-₹{{ $formatAmt($item->discount_amount) }})
                                                </div>
                                            @else
                                                <div class="font-bold text-gray-800">
                                                    ₹{{ $formatAmt($item->discount_amount) }}
                                                </div>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center align-top text-gray-600">
                                        {{ (float) $item->tax_percent }}%
                                    </td>
                                    <td class="px-4 py-4 text-right align-top font-bold text-gray-900">
                                        ₹{{ $formatAmt($item->total_amount) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals Area --}}
                <div class="mb-12 flex flex-col justify-end md:flex-row print:flex-row">
                    <div class="w-full md:ml-auto md:w-80 print:ml-auto print:w-[300px]">
                        <table class="w-full text-sm text-gray-800">
                            <tbody>
                                <tr>
                                    <td class="py-2 font-medium text-gray-600">Subtotal</td>
                                    <td class="py-2 text-right font-bold">₹{{ $formatAmt($quotation->subtotal) }}</td>
                                </tr>

                                @if ($quotation->igst_amount > 0)
                                    <tr>
                                        <td class="py-2 font-medium text-gray-600">IGST</td>
                                        <td class="py-2 text-right font-bold">
                                            ₹{{ $formatAmt($quotation->igst_amount) }}
                                        </td>
                                    </tr>
                                @else
                                    @if ($quotation->cgst_amount > 0 || $quotation->sgst_amount > 0)
                                        <tr>
                                            <td class="py-2 font-medium text-gray-600">CGST</td>
                                            <td class="py-2 text-right font-bold">
                                                ₹{{ $formatAmt($quotation->cgst_amount) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 font-medium text-gray-600">SGST</td>
                                            <td class="py-2 text-right font-bold">
                                                ₹{{ $formatAmt($quotation->sgst_amount) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endif

                                @if ($quotation->shipping_charge > 0)
                                    <tr>
                                        <td class="py-2 font-medium text-gray-600">Shipping / Other</td>
                                        <td class="py-2 text-right font-bold">
                                            ₹{{ $formatAmt($quotation->shipping_charge) }}
                                        </td>
                                    </tr>
                                @endif

                                @if ($quotation->discount_amount > 0)
                                    <tr>
                                        <td class="py-2 font-medium text-gray-600">
                                            Discount
                                            @if ($quotation->discount_type === 'percentage')
                                                <span class="ml-1 text-xs text-gray-500"
                                                    >({{ (float) $quotation->discount_value }}%)</span
                                                >
                                            @endif
                                        </td>
                                        <td class="py-2 text-right font-bold text-red-600">
                                            (-) ₹{{ $formatAmt($quotation->discount_amount) }}
                                        </td>
                                    </tr>
                                @endif

                                <tr class="border-t-2 border-gray-900">
                                    <td class="py-3 text-base font-black text-gray-900 uppercase">Grand Total</td>
                                    <td class="py-3 text-right text-[17px] font-black text-[#108c2a]">
                                        ₹{{ $formatAmt($quotation->grand_total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer (Notes, Terms & Signature) --}}
                {{-- UI Fix: items-start for mobile prevents notes from aligning weirdly, md:items-end for desktop/print --}}
                <div
                    class="page-break-avoid flex flex-col items-start justify-between gap-8 md:flex-row md:items-end print:flex-row print:items-end"
                >
                    <div class="order-2 w-full text-sm text-gray-800 md:order-1 md:w-2/3 print:order-1 print:w-1/2">
                        @if ($quotation->notes)
                            <div class="mb-5 rounded-lg bg-gray-50 p-4 md:bg-transparent md:p-0 print:bg-transparent">
                                <h4 class="mb-1 text-[11px] font-black tracking-widest text-gray-900 uppercase">
                                    Notes:
                                </h4>
                                <p class="leading-relaxed text-gray-600">{{ $quotation->notes }}</p>
                            </div>
                        @endif

                        @if ($quotation->terms_conditions)
                            <div class="rounded-lg bg-gray-50 p-4 md:bg-transparent md:p-0 print:bg-transparent">
                                <h4 class="mb-1 text-[11px] font-black tracking-widest text-gray-900 uppercase">
                                    Terms & Conditions:
                                </h4>
                                <div class="text-[13px] leading-relaxed text-gray-600">
                                    {!! nl2br(e($quotation->terms_conditions)) !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Signature Block --}}
                    <div
                        class="order-1 flex w-full justify-start pt-8 md:order-2 md:w-1/3 md:justify-end md:pt-10 print:order-2 print:w-1/2 print:justify-end"
                    >
                        <div class="w-full min-w-[200px] text-center sm:w-auto">
                            @if ($billingSignatureUrl)
                                <img
                                    src="{{ $billingSignatureUrl }}"
                                    alt="Authorized Signature"
                                    class="mx-auto mb-2 max-h-16 object-contain opacity-90"
                                />
                            @else
                                {{-- Spacer keeps the signature line at the same height either way --}}
                                <div class="h-12"></div>
                            @endif
                            <div
                                class="border-t border-gray-400 pt-2 text-[13px] font-bold tracking-widest text-gray-500 uppercase"
                            >
                                Authorized Signatory
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Declared as a global rather than through Alpine.data(). Scripts pushed
        // from a view run after Alpine has already booted and scanned the DOM,
        // so an alpine:init listener registers too late and x-data resolves to
        // an empty scope. Alpine evaluates x-data expressions at runtime, which
        // means a plain global is available by the time it is needed.
        window.quotationShow = function () {
            return {
                confirmConvert(form) {
                    BizAlert.confirm(
                        "Convert to Invoice?",
                        "This will generate a Draft Invoice with the exact details of this quotation. The quotation will be locked.",
                        "Yes, Convert it",
                    ).then((result) => {
                        if (result.isConfirmed) {
                            BizAlert.loading("Converting to Invoice...");
                            // Conversion creates an invoice, so a second submit
                            // would create a duplicate.
                            if (this.$refs.convertBtn) this.$refs.convertBtn.disabled = true;
                            form.submit();
                        }
                    });
                },
            };
        };
    </script>
@endsection
