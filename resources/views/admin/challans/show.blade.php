@extends('layouts.admin')

@section('title', 'Challan: ' . $challan->challan_number)

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

@section('header-title')
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Challan Details</h1>
@endsection

@section('content')
    @php
        // Resolve Company and Store
        $company = $challan->company ?? auth()->user()->company;
        $store = $challan->store;

        // Signature belongs to the store that issued this challan, same as
        // quotations. signature_url returns null when unset, so the block
        // below simply renders the line without an image.
        $billingSignatureUrl = $store?->signature_url;

        // Party Details
        $partyName = $challan->party_name ?? 'Unknown Party';
        $partyPhone = $challan->party_phone ?? 'N/A';
        $partyAddress = $challan->party_address ?? 'N/A';
        $partyGSTIN = $challan->party_gst ?? null;
        $partyState = $challan->toState->name ?? ($challan->party_state ?? 'N/A');

        // Badge Color Mapping (Used for screen only)
        $colorMap = [
            'gray' => 'text-gray-700',
            'blue' => 'text-blue-700',
            'indigo' => 'text-indigo-700',
            'cyan' => 'text-cyan-700',
            'amber' => 'text-amber-600',
            'teal' => 'text-teal-700',
            'green' => 'text-green-600',
            'lime' => 'text-lime-700',
            'slate' => 'text-slate-700',
            'red' => 'text-red-600',
        ];
        $statusColorClass = $colorMap[$challan->status_color] ?? $colorMap['gray'];
    @endphp

    <div class="pb-10">

        {{-- ACTION BAR --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <div class="w-full sm:w-auto">
                <x-admin.breadcrumb :items="[
                    ['label' => 'Challans', 'url' => route('admin.challans.index')],
                    ['label' => 'Challan Details'],
                ]" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (has_permission('challans.update'))
                    @if ($challan->status !== 'cancelled')
                        <a href="{{ route('admin.challans.edit', $challan->id) }}"
                            class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                            <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                        </a>
                    @endif
                @endif

                <button onclick="window.print()"
                    class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>

                @if (has_permission('challans.download_pdf'))
                    <a href="{{ route('admin.challans.pdf', $challan->id) }}" target="_blank"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="download" class="w-4 h-4"></i> PDF
                    </a>
                @endif

                @php
                    $linkedInvoices = $challan->invoices;
                    $canConvert =
                        $challan->direction === 'outward' &&
                        !in_array($challan->status, ['draft', 'cancelled', 'converted_to_invoice', 'fully_returned']) &&
                        $challan->items->sum('qty_pending') > 0;
                @endphp

                @foreach ($linkedInvoices as $linkedInvoice)
                    <a href="{{ route('admin.invoices.show', $linkedInvoice->id) }}"
                        class="bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2">
                        <i data-lucide="file-check" class="w-4 h-4"></i>
                        {{ $linkedInvoice->invoice_number }}
                        <span class="text-[11px] uppercase opacity-70">{{ $linkedInvoice->status }}</span>
                    </a>
                @endforeach
                @if ($canConvert)
                    <a href="{{ route('admin.invoices.create', ['challan_id' => $challan->id]) }}"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <i data-lucide="file-plus" class="w-4 h-4"></i> Invoice
                    </a>
                @endif

                @php
                    $waText = urlencode(
                        "Hello {$partyName},\nHere is your {$challan->type_label} Document #{$challan->challan_number} dated " .
                            $challan->challan_date->format('d M Y') .
                            ".\nThank you for your business!",
                    );
                    $waPhone = preg_replace('/[^0-9]/', '', $partyPhone);
                @endphp
                @if (strlen($waPhone) >= 10)
                    <a href="https://wa.me/{{ $waPhone }}?text={{ $waText }}" target="_blank"
                        class="bg-[#25D366] hover:bg-[#1da851] text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.669.149-.198.297-.768.966-.941 1.164-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.058-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.173.198-.297.297-.495.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.371-.273.297-1.04 1.016-1.04 2.479 0 1.463 1.064 2.876 1.213 3.074.148.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.626.711.226 1.358.194 1.87.118.571-.085 1.758-.718 2.007-1.411.248-.694.248-1.289.173-1.411-.074-.124-.272-.198-.57-.347z" />
                            <path
                                d="M12.004 2C6.486 2 2 6.484 2 12c0 1.991.585 3.847 1.589 5.407L2 22l4.75-1.557A9.956 9.956 0 0012.004 22C17.522 22 22 17.516 22 12S17.522 2 12.004 2z" />
                        </svg>
                        WhatsApp
                    </a>
                @endif
            </div>
        </div>

        {{-- Return Overdue Alert --}}
        @if ($challan->is_returnable && $challan->is_return_overdue)
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 no-print flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-red-600 p-2 rounded-lg text-white">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-800">Return Overdue</h4>
                        <p class="text-xs text-red-600 font-medium">This challan was due for return on
                            {{ $challan->return_due_date->format('d M, Y') }}.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- 📄 THE DOCUMENT --}}
        <div id="print-area"
            class="w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden text-gray-800 print:shadow-none print:border-none">
            <div class="p-8 md:p-12">

                {{-- 🌟 HEADER: Title on Left, Company on Right --}}
                <div
                    class="p-4 md:p-8 pb-4 flex flex-col md:flex-row print:flex-row justify-between items-start gap-6 md:gap-0">
                    <div>
                        <div
                            class="inline-block bg-gray-800 text-white px-3 py-1 text-[10px] font-black uppercase tracking-widest mb-3">
                            {{ $challan->type_label }}
                        </div>
                        <h1 class="text-3xl font-black uppercase tracking-tighter text-gray-900 leading-none">
                            # {{ $challan->challan_number }}
                        </h1>
                        <div class="text-[12px] text-gray-500 font-bold mt-2">
                            Date: {{ $challan->challan_date->format('d M Y') }}
                        </div>
                    </div>

                    <div
                        class="text-left md:text-right print:text-right flex flex-col items-start md:items-end print:items-end">
                        <h2 class="text-xl font-black text-gray-900 uppercase leading-none">{{ $company->name }}</h2>
                        <div class="text-gray-600 text-[12px] mt-1 font-medium">
                            @if ($company->gst_number)
                                GSTIN: <span
                                    class="font-bold text-gray-900 uppercase">{{ $company->gst_number }}</span><br>
                            @endif
                            Email: {{ $company->email }}<br>
                            Phone: {{ $company->phone }}
                        </div>

                        @if ($store)
                            <div class="mt-4 text-right">
                                <h3
                                    class="text-[10px] font-black text-gray-700 uppercase tracking-widest leading-none mb-1">
                                    Dispatched From: <span class="text-black">{{ $store->name }}</span>
                                </h3>
                                <div class="text-gray-800 text-[12px] leading-tight font-medium">
                                    {{ $store->address }}<br>
                                    {{ $store->city }}, {{ $store->state->name ?? '' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- The Heavy Separator Line --}}
                <div class="mx-8 border-t-2 border-gray-900"></div>

                {{-- 🌟 INFO BLOCK: Billed To on Left, Metadata on Right --}}
                {{-- UI Fix: 1 column on mobile, 2 columns on md+, responsive padding/gaps --}}
                <div class="p-4 md:p-8 grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-8 md:gap-12">
                    {{-- Customer Details --}}
                    <div>
                        <h3 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">
                            {{ $challan->direction === 'outward' ? 'Dispatched To / Billed To' : 'Received From' }}
                        </h3>
                        <div class="text-sm text-gray-800">
                            <div class="font-black text-base mb-0.5 uppercase">{{ $partyName }}</div>
                            @if ($partyGSTIN)
                                <div class="font-bold text-gray-900 uppercase">GSTIN: {{ $partyGSTIN }}</div>
                            @endif
                            @if ($partyAddress !== 'N/A')
                                <div class="text-gray-600 leading-snug font-medium mt-1">
                                    {!! nl2br(e($partyAddress)) !!}
                                </div>
                            @endif
                            @if ($partyState !== 'N/A')
                                <div class="text-gray-600 leading-snug font-medium mt-1">State: {{ $partyState }}</div>
                            @endif
                            @if ($partyPhone !== 'N/A')
                                <div class="text-gray-600 leading-snug font-medium">Phone: {{ $partyPhone }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- Document Data --}}
                    <div class="space-y-1">
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Direction:</span>
                            <span class="text-right font-bold text-gray-900 uppercase">{{ $challan->direction }}</span>
                        </div>
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Status:</span>
                            <span
                                class="text-right font-black uppercase {{ $statusColorClass }}">{{ $challan->status_label }}</span>
                        </div>

                        @if ($challan->transport_name)
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">Transporter:</span>
                                <span
                                    class="text-right font-bold text-gray-900 uppercase">{{ $challan->transport_name }}</span>
                            </div>
                        @endif

                        @if ($challan->vehicle_number)
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">Vehicle No:</span>
                                <span
                                    class="text-right font-bold text-gray-900 uppercase">{{ $challan->vehicle_number }}</span>
                            </div>
                        @endif

                        @if ($challan->eway_bill_number)
                            <div class="grid grid-cols-2 text-[13px]">
                                <span class="font-bold text-gray-500">E-Way Bill:</span>
                                <span class="text-right font-bold text-gray-900">{{ $challan->eway_bill_number }}</span>
                            </div>
                        @endif

                        @if ($challan->is_returnable && $challan->return_due_date)
                            <div class="grid grid-cols-2 text-[13px] pt-1 border-t border-gray-100">
                                <span class="font-bold text-gray-500">Return Due:</span>
                                <span
                                    class="text-right font-black {{ $challan->is_return_overdue ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $challan->return_due_date->format('d M Y') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 🌟 ITEMS TABLE --}}
                {{-- UI Fix: Less padding on mobile to maximize table width --}}
                <div class="overflow-x-auto print:overflow-visible mb-10 px-4 md:px-8">
                    <table class="w-full text-sm print:text-[11px] border-collapse">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold border-b">Description</th>
                                <th class="py-3 px-4 text-center font-bold border-b">HSN/SAC</th>
                                @if (batch_enabled())
                                    <th class="py-3 px-4 text-center font-bold border-b">Batch #</th>
                                    <th class="py-3 px-4 text-center font-bold border-b">Expiry</th>
                                @endif
                                <th class="py-3 px-4 text-right font-bold border-b">Qty Sent</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-orange-600">Returned</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-green-600">Invoiced</th>
                                <th class="py-3 px-4 text-right font-bold border-b text-blue-600">Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($challan->items as $item)
                                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50">
                                    <td class="py-4 px-4 text-gray-800">
                                        <div class="font-bold">{{ $item->product_name }}</div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU:
                                            {{ $item->sku_code ?? '-' }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-center text-gray-500">{{ $item->hsn_code ?? '-' }}</td>

                                    @if (batch_enabled())
                                        <td class="py-4 px-4 text-center font-mono text-[12px] text-gray-700">
                                            {{ $item->batch_number ?? '-' }}</td>
                                        <td class="py-4 px-4 text-center text-[12px]">
                                            @if ($item->expiry_date)
                                                <span
                                                    class="{{ $item->expiry_date->isPast() ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                                    {{ $item->expiry_date->format('d M Y') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="py-4 px-4 text-right font-bold text-gray-900">{{ (float) $item->qty_sent }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-semibold {{ $item->qty_returned > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                        {{ (float) $item->qty_returned }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-semibold {{ $item->qty_invoiced > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ (float) $item->qty_invoiced }}
                                    </td>
                                    <td
                                        class="py-4 px-4 text-right font-bold {{ $item->qty_pending > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                        {{ (float) $item->qty_pending }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- 🌟 FINAL FIXED SUMMARY --}}
                <div
                    class="px-4 md:px-8 pb-12 flex flex-col md:flex-row print:flex-row justify-between items-start gap-8 md:gap-10 page-break-avoid border-t border-gray-200 pt-6">

                    {{-- Left side: Notes --}}
                    <div class="w-full md:flex-1 space-y-4">
                        @if ($challan->purpose_note)
                            <div class="text-[12px] text-gray-600">
                                <strong class="text-gray-800 uppercase tracking-widest text-[10px]">Purpose of
                                    Challan:</strong>
                                <p class="mt-1 leading-relaxed">{{ $challan->purpose_note }}</p>
                            </div>
                        @endif

                        @if ($challan->internal_notes)
                            <div class="no-print bg-yellow-50 p-3 rounded-lg border border-yellow-200 mt-2">
                                <h4 class="text-[10px] font-black text-yellow-700 uppercase tracking-widest mb-1">Internal
                                    Notes (Hidden on Print)</h4>
                                <p class="text-[12px] text-yellow-800">{{ $challan->internal_notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Right side: Narrow Totals Block (Forced 300px width) --}}
                    <div class="w-full md:w-[300px] print:w-[300px] ml-auto print:ml-auto flex flex-col items-end">
                        <table class="w-full text-[13px] border-collapse">
                            {{-- Thick line for the total --}}
                            <tr class="border-t-2 border-gray-900">
                                <td class="py-3 text-[14px] font-black text-gray-900 uppercase">Total Quantity</td>
                                <td class="py-3 text-right text-[16px] font-black text-gray-900 whitespace-nowrap">
                                    {{ (float) $challan->total_qty }}
                                </td>
                            </tr>
                        </table>

                        {{-- Signatures section --}}
                        <div class="mt-20 w-full flex items-start justify-between gap-8">
                            <div class="flex-1 min-w-[120px] text-center flex flex-col">
                                <div class="h-12 mb-1"></div>
                                <div
                                    class="border-t border-gray-400 pt-1 text-[10px] font-bold text-gray-800 uppercase tracking-wider">
                                    Receiver Sign
                                </div>
                            </div>
                            <div class="flex-1 min-w-[120px] text-center flex flex-col">
                                <div class="h-12 mb-1 flex items-end justify-center">
                                    @if ($billingSignatureUrl)
                                        <img src="{{ $billingSignatureUrl }}" alt="Authorized Signature"
                                            class="max-h-12 object-contain opacity-90" />
                                    @endif
                                </div>
                                <div
                                    class="border-t border-gray-400 pt-1 text-[10px] font-bold text-gray-800 uppercase tracking-wider">
                                    Authorized Sign
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
