<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Return #{{ $challanReturn->return_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-gray { color: #666; }
        .uppercase { text-transform: uppercase; }
        
        .border-bottom { border-bottom: 1px solid #ddd; }
        .border-top { border-top: 1px solid #ddd; }
        .bg-light { background-color: #f9f9f9; }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111;
        }

        .items-table th, .items-table td.table-head {
            background-color: #f3f4f6;
            padding: 10px;
            border-bottom: 2px solid #333;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-table td {
            padding: 6px 10px;
        }

        .badge {
            font-size: 10px;
            font-weight: bold;
            padding: 3px 6px;
            border: 1px solid #ccc;
            display: inline-block;
            border-radius: 3px;
            text-transform: uppercase;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    @php
        // 🌟 Resolve Core Relationships
        $company = $challanReturn->company ?? auth()->user()->company;
        $challan = $challanReturn->challan;
        $store = $challan->store;

        // 🌟 Party Details (Inherited from the original Challan snapshot)
        $partyName = $challan->party_name ?? 'Unknown Party';
        $partyPhone = $challan->party_phone ?? 'N/A';
        $partyAddress = $challan->party_address ?? 'N/A';
        $partyGSTIN = $challan->party_gst ?? null;
        $partyState = $challan->toState->name ?? $challan->party_state ?? 'N/A';
    @endphp

    {{-- HEADER --}}
    <table style="margin-bottom: 30px;">
        <tr>
            <td style="width: 50%;">
                <div class="header-title">GOODS RETURN</div>
                <div class="font-bold text-gray mt-4" style="font-size: 14px;"># {{ $challanReturn->return_number }}</div>
                <div class="badge">
                    CONDITION: {{ $challanReturn->condition_label }}
                </div>
            </td>
            <td style="width: 50%;" class="text-right">
                {{-- 1. Legal Entity (Company) --}}
                <h2 style="margin:0; font-size: 18px;" class="uppercase">{{ $company->name ?? 'Company Name' }}</h2>
                <div class="text-gray" style="line-height: 1.4; margin-top: 6px;">
                    @if (isset($company->gst_number) || isset($company->gstin))
                        GSTIN: <strong style="color: #333;">{{ $company->gst_number ?? $company->gstin }}</strong><br>
                    @endif
                    @if ($company->email)
                        Email: {{ $company->email }}<br>
                    @endif
                    @if ($company->phone)
                        Phone: {{ $company->phone }}
                    @endif
                </div>

                {{-- 2. Operational Branch (Store) --}}
                @if ($store)
                    <div style="margin-top: 12px;">
                        <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 2px;">
                            Returned To Branch
                        </div>
                        <div class="text-gray" style="line-height: 1.4;">
                            <span class="font-bold" style="color: #333;">{{ $store->name }}</span><br>
                            @if ($store->address)
                                {{ $store->address }}<br>
                            @endif
                            {{ $store->city ?? '' }}@if ($store->city && $store->zip_code), @endif{{ $store->zip_code ?? '' }}<br>
                            {{ $store->state->name ?? ($store->state_id ?? '') }}
                        </div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- META INFO (Logistics & Party) --}}
    <table class="border-top border-bottom" style="margin-bottom: 30px; padding: 15px 0;">
        <tr>
            <td style="width: 50%;">
                <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                    Returned From (Party)
                </div>
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 3px;">{{ $partyName }}</div>
                <div class="text-gray" style="line-height: 1.4;">
                    @if ($partyAddress !== 'N/A')
                        {{ $partyAddress }}<br>
                    @endif
                    @if ($partyState !== 'N/A')
                        State: {{ $partyState }}<br>
                    @endif
                    @if ($partyPhone !== 'N/A')
                        Phone: {{ $partyPhone }}<br>
                    @endif
                    @if ($partyGSTIN)
                        GSTIN: <strong style="color: #333;">{{ $partyGSTIN }}</strong>
                    @endif
                </div>
            </td>
            <td style="width: 50%; line-height: 1.8;">
                <table>
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 55%;">Return Date:</td>
                        <td class="font-bold text-right">{{ $challanReturn->return_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray font-bold text-right">Original Challan:</td>
                        <td class="font-bold text-right">{{ $challan->challan_number }}</td>
                    </tr>
                    @if ($challanReturn->vehicle_number)
                        <tr>
                            <td class="text-gray font-bold text-right">Return Vehicle No:</td>
                            <td class="font-bold text-right uppercase">{{ $challanReturn->vehicle_number }}</td>
                        </tr>
                    @endif
                    @if ($challanReturn->received_by)
                        <tr>
                            <td class="text-gray font-bold text-right">Received By:</td>
                            <td class="font-bold text-right">{{ $challanReturn->received_by }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEMS TABLE --}}
    <table class="items-table" style="margin-bottom: 30px;">
        <thead>
            <tr>
                <td class="table-head text-left">Product Details</td>
                <td class="table-head text-center">Original Qty</td>
                <td class="table-head text-center">Returned Qty</td>
                <td class="table-head text-center" style="color: #333;">Damaged Qty</td>
                <td class="table-head text-left">Damage Notes</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($challanReturn->items as $item)
                <tr>
                    <td>
                        <div class="font-bold">{{ $item->challanItem->product_name ?? 'Unknown Product' }}</div>
                        <div style="font-size: 10px; color: #777;">SKU: {{ $item->challanItem->sku_code ?? 'N/A' }}</div>
                    </td>
                    <td class="text-center text-gray">{{ (float) ($item->challanItem->qty_sent ?? 0) }}</td>
                    <td class="text-center font-bold">{{ (float) $item->qty_returned }}</td>
                    <td class="text-center font-bold" style="{{ $item->qty_damaged > 0 ? 'color: #333;' : 'color: #999;' }}">
                        {{ (float) $item->qty_damaged }}
                    </td>
                    <td class="text-left text-gray" style="font-size: 11px;">
                        {{ $item->damage_note ?: '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY & TOTALS --}}
    <table>
        <tr>
            {{-- Left Side: Notes --}}
            <td style="width: 50%; padding-right: 20px;">
                @if ($challanReturn->notes)
                    <div style="margin-bottom: 15px;">
                        <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 3px;">
                            Return Notes
                        </div>
                        <div class="text-gray" style="line-height: 1.4;">{{ $challanReturn->notes }}</div>
                    </div>
                @endif
            </td>

            {{-- Right Side: Totals --}}
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 60%;">Total Quantity Returned</td>
                        <td class="font-bold text-right">{{ (float) $challanReturn->total_qty_returned }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-right border-bottom" style="padding-bottom: 10px; color: #333;">Total Damaged</td>
                        <td class="font-bold text-right border-bottom" style="padding-bottom: 10px; color: #333;">{{ (float) $challanReturn->total_qty_damaged }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-right" style="font-size: 14px; padding: 12px 10px; color: #333;">Clean Stock Recovered</td>
                        <td class="font-bold text-right" style="font-size: 14px; padding: 12px 10px; color: #333;">{{ (float) $challanReturn->total_qty_clean }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- SIGNATURE BLOCK (Absolute separation using a full-width table) --}}
    <table style="width: 100%; margin-top: 80px; page-break-inside: avoid;">
        <tr>
            <td style="width: 50%; text-align: left;">
                <div style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; width: 180px; font-size: 10px; font-weight: bold; text-transform: uppercase; text-align: center;">
                    Party Signature
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; width: 180px; font-size: 10px; font-weight: bold; text-transform: uppercase; text-align: center;">
                    Receiver (Store Auth)
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 40px; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; text-align: center;">
        This is a computer-generated document. No signature is required.
    </div>

</body>
</html>