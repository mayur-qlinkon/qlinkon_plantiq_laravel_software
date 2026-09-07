@extends('layouts.admin')

@section('title', 'Return: ' . $challanReturn->return_number)

@push('styles')
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

            body * {
                visibility: hidden;
            }

            #print-area,
            #print-area * {
                visibility: visible;
            }

            #print-area {
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
    <h1 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Return Details</h1>
@endsection

@section('content')
    @php
        // Resolve Company and Store
        $company = $challanReturn->company ?? auth()->user()->company;
        $challan = $challanReturn->challan;
        $store = $challan->store;

        // Party Details (From original Challan)
        $partyName = $challan->party_name ?? 'Unknown Party';
        $partyPhone = $challan->party_phone ?? 'N/A';
        $partyAddress = $challan->party_address ?? 'N/A';
        $partyGSTIN = $challan->party_gst ?? null;
        $partyState = $challan->toState->name ?? $challan->party_state ?? 'N/A';

        // Badge Color Mapping for Condition
        $colorMap = [
            'green' => 'bg-green-50 text-green-700 border-green-200',
            'red'   => 'bg-red-50 text-red-700 border-red-200',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
            'gray'  => 'bg-gray-50 text-gray-700 border-gray-200',
        ];
        $conditionColorClass = $colorMap[$challanReturn->condition_color] ?? $colorMap['gray'];
    @endphp

    <div class="pb-10">
        {{-- ACTION BAR (Hidden on Print) --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
            <div class="w-full sm:w-auto">
                <x-admin.breadcrumb :items="[
                    ['label' => 'Challan Return', 'url' => route('admin.challan-returns.index')],
                    ['label' => 'Challan Return Details'],
                ]" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.challan-returns.index') }}"
                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 px-4 py-1.5 rounded text-sm transition-colors flex items-center shadow-sm font-medium">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1.5"></i> Back
                </a>

                @if(has_permission('challan_returns.update'))
                    <a href="{{ route('admin.challan-returns.edit', $challanReturn->id) }}"
                        class="bg-white border border-gray-200 hover:bg-blue-50 hover:text-blue-600 text-gray-600 px-4 py-1.5 rounded text-sm transition-colors flex items-center shadow-sm font-medium">
                        <i data-lucide="pencil" class="w-4 h-4 mr-1.5"></i> Edit Notes
                    </a>
                @endif

                <button onclick="window.print()"
                    class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 px-4 py-1.5 rounded text-sm transition-colors flex items-center gap-1.5 shadow-sm font-bold">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Receipt
                </button>

                @if(has_permission('challan_returns.download_pdf'))
                    <a href="{{ route('admin.challan-returns.pdf', $challanReturn->id) }}" target="_blank"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-sm transition-colors flex items-center gap-1.5 shadow-sm font-bold">
                        <i data-lucide="download" class="w-4 h-4"></i> Download PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- 📄 THE RETURN RECEIPT UI (Full Width) --}}
        <div id="print-area" class="w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden text-gray-800 print:grayscale print:shadow-none print:border-none">

            {{-- Header --}}
            <div class="p-8 border-b-2 border-gray-800 grid grid-cols-2 gap-6 items-start">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-widest text-gray-900 mb-1">Goods Return</h1>
                    <div class="text-sm text-gray-600 font-bold mb-3"># {{ $challanReturn->return_number }}</div>

                    {{-- Status Badges --}}
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-block border text-[11px] font-black uppercase px-2.5 py-1 rounded {{ $conditionColorClass }}">
                            {{ $challanReturn->condition_label }}
                        </span>
                        
                        <span class="inline-block border border-gray-200 bg-gray-50 text-gray-700 text-[11px] font-black uppercase px-2.5 py-1 rounded">
                            <i data-lucide="undo-2" class="w-3 h-3 inline pb-0.5"></i> Inward Return
                        </span>
                    </div>
                </div>

                <div class="text-right text-sm flex flex-col items-end">
                    {{-- Company Info --}}
                    <h2 class="text-xl font-black text-gray-900 uppercase leading-none">{{ $company->name }}</h2>
                    <div class="text-gray-600 text-[12px] mt-1">
                        @if ($company->gst_number || $company->gstin)
                            GSTIN: <span class="font-bold text-gray-900 uppercase">{{ $company->gst_number ?? $company->gstin }}</span><br>
                        @endif
                        Email: {{ $company->email }}<br>
                        Phone: {{ $company->phone }}
                    </div>

                    {{-- Store / Branch Info --}}
                    @if ($store)
                        <div class="text-right mt-4">
                            <h3 class="text-[10px] font-black text-gray-700 uppercase tracking-widest">Returned To Branch: <span class="font-bold text-black">{{ $store->name }}</span></h3>
                            <div class="text-gray-800 text-[13px] leading-tight">
                                @if ($store->address)
                                    {{ $store->address }}<br>
                                @endif
                                {{ $store->city }}{{ $store->city && $store->zip_code ? ', ' : '' }}{{ $store->zip_code }}<br>
                                {{ $store->state->name ?? $store->state_id }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Meta & Address Block --}}
            <div class="p-8 grid grid-cols-2 gap-8 border-b border-gray-200">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Returned From (Party)</h3>
                        <div class="text-sm text-gray-800">
                            <div class="font-bold text-base mb-0.5">{{ $partyName }}</div>
                            
                            @if ($partyGSTIN)
                                <div class="font-bold text-gray-900 uppercase">GSTIN: {{ $partyGSTIN }}</div>
                            @endif
                            @if ($partyAddress !== 'N/A')
                                <div class="text-gray-600 leading-tight mt-1">{{ $partyAddress }}</div>
                            @endif
                            @if ($partyPhone !== 'N/A')
                                <div class="text-gray-600 mt-1">Phone: {{ $partyPhone }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-1 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Return Date:</span>
                        <span class="text-right font-semibold">{{ $challanReturn->return_date->format('d M Y') }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Original Challan:</span>
                        <span class="text-right font-bold text-blue-600">
                            <a href="{{ route('admin.challans.show', $challan->id) }}" class="hover:underline no-print">{{ $challan->challan_number }}</a>
                            <span class="hidden print:inline">{{ $challan->challan_number }}</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 text-[13px]">
                        <span class="font-bold text-gray-500">Challan Type:</span>
                        <span class="text-right font-semibold text-gray-900">{{ $challan->type_label }}</span>
                    </div>
                    
                    @if($challanReturn->vehicle_number)
                        <div class="grid grid-cols-2 text-[13px] pt-2 mt-2 border-t border-gray-200">
                            <span class="font-bold text-gray-500">Return Vehicle No:</span>
                            <span class="text-right font-bold text-gray-900 uppercase">{{ $challanReturn->vehicle_number }}</span>
                        </div>
                    @endif
                    
                    @if($challanReturn->received_by)
                        <div class="grid grid-cols-2 text-[13px]">
                            <span class="font-bold text-gray-500">Received By:</span>
                            <span class="text-right font-semibold text-gray-900">{{ $challanReturn->received_by }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line Items Table --}}
            <div class="px-8 py-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead>
                            <tr class="border-b-2 border-gray-800 text-xs font-black text-gray-700 uppercase tracking-wider">
                                <th class="py-3 px-2">Description</th>
                                <th class="py-3 px-2 text-center">Original Qty</th>
                                <th class="py-3 px-2 text-center bg-gray-50">Returned Qty</th>
                                <th class="py-3 px-2 text-center text-red-600">Damaged Qty</th>
                                <th class="py-3 px-2 text-left">Damage Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($challanReturn->items as $item)
                                <tr>
                                    <td class="py-4 px-2">
                                        <div class="font-bold text-gray-900">{{ $item->challanItem->product_name ?? 'Unknown Product' }}</div>
                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $item->challanItem->sku_code ?? 'N/A' }}</div>
                                    </td>
                                    
                                    {{-- Original Sent Qty --}}
                                    <td class="py-4 px-2 text-center text-gray-500">{{ (float) ($item->challanItem->qty_sent ?? 0) }}</td>
                                    
                                    {{-- Returned Qty --}}
                                    <td class="py-4 px-2 text-center font-black text-gray-900 bg-gray-50/50">
                                        {{ (float) $item->qty_returned }}
                                    </td>

                                    {{-- Damaged Qty --}}
                                    <td class="py-4 px-2 text-center font-bold {{ $item->qty_damaged > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        {{ (float) $item->qty_damaged }}
                                    </td>

                                    {{-- Damage Notes --}}
                                    <td class="py-4 px-2 text-left text-gray-600 max-w-[250px] whitespace-normal text-xs">
                                        {{ $item->damage_note ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Summary & Totals --}}
            <div class="px-8 pb-8 page-break-avoid flex flex-col md:flex-row print:flex-row justify-between items-end gap-8 print:gap-4 border-t border-gray-100 pt-6">

                {{-- Left Side: Notes & Signature --}}
                <div class="w-full md:w-1/2 print:w-1/2 mb-6 md:mb-0 print:mb-0 space-y-6">
                    @if ($challanReturn->notes)
                        <div>
                            <h4 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Return Notes</h4>
                            <p class="text-[13px] text-gray-700 font-medium">{{ $challanReturn->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Right Side: Constrained Totals Table --}}
                <div class="w-full md:w-[350px] flex flex-col items-end">
                    <div class="w-full bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <table class="w-full text-[13px]">
                            <tbody>
                                <tr>
                                    <td class="py-1.5 text-gray-600 font-semibold">Total Quantity Returned</td>
                                    <td class="py-1.5 text-right text-gray-900 font-bold">{{ (float) $challanReturn->total_qty_returned }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-red-600 font-semibold">Total Damaged</td>
                                    <td class="py-1.5 text-right text-red-600 font-bold">{{ (float) $challanReturn->total_qty_damaged }}</td>
                                </tr>
                                
                                <tr class="border-t border-gray-200">
                                    <td class="pt-3 pb-1 text-[12px] font-black text-green-700 uppercase tracking-wider">Clean Stock Recovered</td>
                                    <td class="pt-3 pb-1 text-right text-[18px] font-black text-green-700">
                                        {{ (float) $challanReturn->total_qty_clean }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- 🌟 Full Width Signature Block (Absolute Left and Right separation) --}}
            <div class="px-8 pb-10 pt-16 w-full page-break-avoid flex justify-between items-end mt-auto">
                <div class="text-left">
                    <div class="border-t-2 border-gray-400 pt-2 inline-block min-w-[200px] text-[11px] font-bold text-gray-800 uppercase tracking-wider text-center">
                        Party Signature
                    </div>
                </div>
                <div class="text-right">
                    <div class="border-t-2 border-gray-400 pt-2 inline-block min-w-[200px] text-[11px] font-bold text-gray-800 uppercase tracking-wider text-center">
                        Receiver (Store Auth)
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection