@extends('layouts.admin')

@section('title', 'Credit Note: ' . $invoiceReturn->credit_note_number)

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Sales Return Details</h1>
@endsection

@push('styles')
    <style>
        /* 🖨️ PRO-GRADE A4 PRINT OPTIMIZATION */
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

            body * {
                visibility: hidden;
            }

            #print-area,
            #print-area * {
                visibility: visible;
            }

            #print-area {
                filter: grayscale(100%) !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                border: none !important;
                box-shadow: none !important;
            }

            .no-print {
                display: none !important;
            }

            .page-break-avoid {
                page-break-inside: avoid;
            }
        }
    </style>
@endpush
@section('content')
    @php
        $formatAmt = fn($amount) => number_format((float) $amount, 2, '.', ',');
        
        // Company & Store Details
        $company = $invoiceReturn->company ?? auth()->user()->company;
        $store   = $invoiceReturn->store;

        // Billing priority (Fallback safety)
        $billingGstin = $company->gst_number ?? $store->gst_number ?? get_setting('gst_number');

        // Customer Details fallback
        $customerName = $invoiceReturn->customer ? $invoiceReturn->customer->name : $invoiceReturn->customer_name ?? 'Guest Customer';
        $customerAddress = $invoiceReturn->customer ? $invoiceReturn->customer->address : 'N/A';
        $customerPhone = $invoiceReturn->customer ? $invoiceReturn->customer->phone : 'N/A';
        $customerGSTIN = $invoiceReturn->customer ? $invoiceReturn->customer->gst_number : null;

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
        $stateCode = $stateCodes[$invoiceReturn->supply_state] ?? 'N/A';
    @endphp
    <div class="pb-10">

        {{-- ACTION BAR --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoice-returns.index') }}"
                    class="text-gray-500 hover:text-gray-800 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-500 uppercase tracking-widest">Credit Note Details</h1>
            </div>

            {{-- UI Fix: Allowed buttons to wrap and fill width on mobile --}}
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                @if ($invoiceReturn->status === 'draft')
                    @if(has_permission('invoice_returns.update'))
                    <a href="{{ route('admin.invoice-returns.edit', $invoiceReturn->id) }}"
                        class="flex-1 sm:flex-none justify-center bg-white border border-gray-200 text-gray-600 px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                    </a>
                    @endif                  

                    @if(has_permission('invoice_returns.confirm'))
                    <form action="{{ route('admin.invoice-returns.confirm', $invoiceReturn->id) }}" method="POST"
                        class="w-full sm:w-auto flex-1 sm:flex-none"
                        onsubmit="event.preventDefault(); BizAlert.confirm('Confirm Return?', 'This will lock the Credit Note and put stock back into the warehouse.', 'Yes, Confirm It').then((r) => r.isConfirmed && this.submit())">
                        @csrf
                        <button type="submit"
                            class="w-full bg-[#108c2a] hover:bg-[#0c6b1f] text-white px-6 py-2.5 rounded-lg text-sm font-bold transition-all shadow-md flex items-center justify-center gap-2">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Confirm
                        </button>
                    </form>
                    @endif
                @endif

                <a href="{{ route('admin.invoice-returns.download-pdf', $invoiceReturn->id) }}" target="_blank"
                    class="flex-1 sm:flex-none justify-center bg-white border border-gray-200 text-gray-600 px-4 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="download" class="w-4 h-4"></i> PDF
                </a>

                <button onclick="window.print()"
                    class="flex-1 sm:flex-none justify-center w-full sm:w-auto mt-2 sm:mt-0 bg-gray-900 text-white px-4 py-2.5 rounded-lg text-sm font-bold flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>
            </div>
        </div>

        {{-- THE DOCUMENT --}}
        <div id="print-area" class="w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden text-gray-800 print:shadow-none print:border-none">
            
            {{-- 🌟 HEADER: Title on Left, Company on Right --}}
            {{-- UI Fix: Stack on mobile (flex-col), side-by-side on desktop (md:flex-row) --}}
            <div class="px-5 sm:px-8 md:px-12 print:px-0 py-6 md:py-10 print:py-4 flex flex-col md:flex-row print:flex-row justify-between items-start gap-6">
                <div class="w-full md:w-auto">
                    <div class="inline-block bg-red-600 text-white px-3 py-1 text-[10px] font-black uppercase tracking-widest mb-3">
                        Credit Note
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tighter text-gray-900 leading-none">
                        # {{ $invoiceReturn->credit_note_number }}
                    </h1>
                    <div class="text-[12px] text-gray-500 font-bold mt-2">
                        Date: {{ $invoiceReturn->return_date->format('d M Y') }}
                    </div>
                </div>

                <div class="w-full md:w-auto text-left md:text-right print:text-right flex flex-col items-start md:items-end print:items-end">
                    <h2 class="text-lg sm:text-xl font-black text-gray-900 uppercase leading-none">{{ $company->name }}</h2>
                    <div class="text-gray-600 text-[12px] mt-1 font-medium">
                        @if ($billingGstin)
                            GSTIN: <span class="font-bold text-gray-900 uppercase">{{ $billingGstin }}</span><br>
                        @endif
                        Email: {{ $company->email }}<br>
                        Phone: {{ $company->phone }}
                    </div>

                    @if ($store)
                        <div class="mt-4 text-left md:text-right print:text-right">
                            <h3 class="text-[10px] font-black text-gray-700 uppercase tracking-widest leading-none mb-1">
                                Branch: <span class="text-black">{{ $store->name }}</span>
                            </h3>
                            <div class="text-gray-800 text-[12px] leading-tight font-medium">
                                @if ($store->address)
                                    {{ $store->address }}<br>
                                @endif
                                {{ $store->city }}{{ $store->city && $store->zip_code ? ', ' : '' }}{{ $store->zip_code ?? '' }}<br>
                                {{ $store->state->name ?? $store->state_id ?? '' }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- The Heavy Separator Line --}}
            <div class="mx-5 sm:mx-8 md:mx-12 print:mx-0 border-t-2 border-gray-900"></div>
            
            <div class="px-5 sm:px-8 md:px-12 print:px-0 py-6 print:py-4 grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-8 md:gap-12">
                {{-- Customer Details --}}
                <div>
                    <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Billed To</h3>
                    <div class="text-sm text-gray-800">
                        <div class="font-black text-base mb-0.5 uppercase">{{ $customerName }}</div>
                        @if ($customerGSTIN)
                            <div class="font-bold text-gray-900 uppercase">GSTIN: {{ $customerGSTIN }}</div>
                        @endif
                        @if ($customerAddress !== 'N/A')
                            <div class="text-gray-600 leading-snug font-medium mt-1">
                                {!! nl2br(e($customerAddress)) !!}
                            </div>
                        @endif
                        @if ($customerPhone !== 'N/A')
                            <div class="text-gray-600 font-medium mt-1">Phone: {{ $customerPhone }}</div>
                        @endif
                    </div>
                </div>

                {{-- Document Data --}}
                <div class="space-y-1 bg-gray-50 md:bg-transparent p-4 md:p-0 rounded-lg md:rounded-none border md:border-none border-gray-100 print:bg-transparent print:border-none">
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Ref Invoice:</span>
                        <span class="text-right font-bold text-blue-600 underline">
                            <a href="{{ route('admin.invoices.show', $invoiceReturn->invoice_id) }}">{{ $invoiceReturn->invoice->invoice_number }}</a>
                        </span>
                    </div>
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Place of Supply:</span>
                        <span class="text-right font-bold text-gray-900">{{ $invoiceReturn->supply_state }}
                            ({{ $stateCode }})</span>
                    </div>
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Return Type:</span>
                        <span class="text-right font-black text-red-600 uppercase">{{ $invoiceReturn->return_type }}</span>
                    </div>
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Return Reason:</span>
                        <span class="text-right font-bold text-gray-900 uppercase">{{ str_replace('_', ' ', $invoiceReturn->return_reason) }}</span>
                    </div>
                    <div class="grid grid-cols-2 text-[13px] pt-2 mt-1 border-t border-gray-200 md:border-gray-100">
                        <span class="font-bold text-gray-500">Warehouse:</span>
                        <span class="text-right font-bold text-gray-900">{{ $invoiceReturn->warehouse->name }}</span>
                    </div>
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Restocked:</span>
                        <span class="text-right font-black {{ $invoiceReturn->stock_updated ? 'text-green-600' : 'text-amber-500' }}">
                            {{ $invoiceReturn->stock_updated ? 'YES' : 'PENDING' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="overflow-x-auto print:overflow-visible mb-10 px-5 sm:px-8 md:px-12 print:px-0">
                <table class="w-full text-sm border-collapse min-w-[650px] print:min-w-0">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold border-b">Description</th>
                            <th class="py-3 px-4 text-center font-bold border-b">HSN</th>
                            <th class="py-3 px-4 text-center font-bold border-b no-print">Original</th>
                            <th class="py-3 px-4 text-center font-bold border-b no-print">Returned<br><span class="text-[10px] font-normal text-gray-500">(all returns)</span></th>
                            <th class="py-3 px-4 text-center font-bold border-b no-print">Remaining</th>
                            <th class="py-3 px-4 text-center font-bold border-b">This Return</th>
                            <th class="py-3 px-4 text-right font-bold border-b">Rate</th>
                            <th class="py-3 px-4 text-center font-bold border-b">GST</th>
                            <th class="py-3 px-4 text-right font-bold border-b">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoiceReturn->items as $item)
                            @php
                                $cap = $returnableMap[$item->invoice_item_id] ?? null;
                                $formatQty = function ($v) {
                                    $s = number_format((float) $v, 4, '.', '');
                                    return rtrim(rtrim($s, '0'), '.') ?: '0';
                                };
                            @endphp
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50">
                                <td class="py-4 px-4 font-bold text-gray-800">
                                    {{ $item->product_name }}
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU:
                                            {{ $item->sku->sku_code ?? ($item->sku->sku ?? 'N/A') }}</div>
                                </td>
                                <td class="py-4 px-4 text-center text-gray-500">{{ $item->hsn_code ?? '-' }}</td>
                                <td class="py-4 px-4 text-center text-gray-700 font-semibold no-print">
                                    {{ $cap ? $formatQty($cap['original']) : '-' }}
                                </td>
                                <td class="py-4 px-4 text-center font-semibold no-print {{ $cap && $cap['returned'] > 0 ? 'text-amber-700' : 'text-gray-400' }}">
                                    {{ $cap ? $formatQty($cap['returned']) : '-' }}
                                </td>
                                <td class="py-4 px-4 text-center font-semibold no-print {{ $cap && $cap['remaining'] > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                    {{ $cap ? $formatQty($cap['remaining']) : '-' }}
                                </td>
                                <td class="py-4 px-4 text-center font-bold text-red-600">
                                    {{ $formatQty($item->quantity) }}
                                </td>
                                <td class="py-4 px-4 text-right text-gray-600">
                                    ₹{{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-4 px-4 text-center text-gray-500">{{ (float) $item->tax_percent }}%
                                </td>
                                <td class="py-4 px-4 text-right font-bold text-gray-900">
                                    ₹{{ number_format($item->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 🌟 FINAL FIXED SUMMARY: Narrow Column for Screen & Print --}}
            <div class="px-5 sm:px-8 md:px-12 print:px-0 pb-12 print:pb-4 flex flex-col md:flex-row print:flex-row justify-between items-start gap-8 md:gap-10 page-break-avoid">

                {{-- Left side: Notes --}}
                <div class="w-full md:w-1/2 print:w-1/2 order-2 md:order-1 print:order-1">
                    @if ($invoiceReturn->notes)
                        <div class="text-[12px] text-gray-600 bg-gray-50 md:bg-transparent p-4 md:p-0 rounded-lg md:rounded-none print:bg-transparent print:p-0">
                            <strong class="text-gray-800 uppercase tracking-widest text-[10px]">Notes:</strong>
                            <p class="mt-1 leading-relaxed">{{ $invoiceReturn->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Right side: Totals & Signature --}}
                {{-- UI Fix: Added ml-auto and print:ml-auto to strictly lock it to the right side --}}
                <div class="w-full md:w-[300px] print:w-[300px] ml-auto print:ml-auto flex flex-col items-end order-1 md:order-2 print:order-2">
                    <table class="w-full text-[13px] border-collapse">
                        <tr>
                            <td class="py-1.5 text-gray-600 font-semibold text-left">Taxable Subtotal</td>
                            <td class="py-1.5 text-right font-bold text-gray-900 whitespace-nowrap">
                                ₹{{ $formatAmt($invoiceReturn->subtotal) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 text-gray-600 font-semibold text-left">GST Reversal</td>
                            <td class="py-1.5 text-right font-bold text-gray-900 whitespace-nowrap">
                                ₹{{ $formatAmt($invoiceReturn->tax_amount) }}</td>
                        </tr>
                        {{-- Thick line for the total --}}
                        <tr class="border-t-2 border-gray-900">
                            <td class="py-3 text-[14px] font-black text-gray-900 uppercase text-left">Total Refund</td>
                            <td class="py-3 text-right text-[16px] font-black text-red-600 whitespace-nowrap print:text-gray-900">
                                ₹{{ $formatAmt($invoiceReturn->grand_total) }}
                            </td>
                        </tr>
                    </table>

                    {{-- Signature section --}}
                    <div class="mt-16 md:mt-24 text-right w-full flex justify-end">
                        <div class="inline-block min-w-[200px] text-center">
                            <div class="border-t border-gray-400 pt-2 text-[11px] font-bold text-gray-800 uppercase tracking-wider">
                                Authorized Signatory
                            </div>
                            <div class="mt-1 text-[9px] text-gray-400 font-bold uppercase tracking-widest leading-none">
                                {{ config('app.name') }} ERP
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
