<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        /* DOMPDF highly compatible CSS */
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .header-table {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .info-table {
            margin-bottom: 25px;
        }

        .info-table td {
            vertical-align: top;
        }

        .meta-label {
            color: #555;
            font-weight: bold;
        }

        .items-table {
            margin-bottom: 25px;
        }

        .items-table th {
            background-color: #000;
            color: #fff;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .totals-table {
            width: 300px;
            float: right;
            border-top: 1px solid #000;
            margin-bottom: 30px;
        }

        .totals-table td {
            padding: 6px 0;
        }

        .grand-total {
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            clear: both;
            margin-top: 30px;
        }

        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #555;
        }

        /* Utility */
        .page-break {
            page-break-after: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @php
        $formatAmt = function ($amount) {
            return number_format((float) $amount, 2, '.', ',');
        };
        $customerName = $quotation->customer ? $quotation->customer->name : $quotation->customer_name ?? 'Guest';
        $customerPhone = $quotation->customer ? $quotation->customer->phone : $quotation->customer_phone ?? 'N/A';
        $customerGST = $quotation->customer ? $quotation->customer->gst_number : $quotation->customer_gstin ?? null;
        $quoteType = !empty($customerGST) ? 'B2B' : 'B2C';
    @endphp

    <table class="header-table">
        <tr>
            <td class="text-left" style="width: 50%; vertical-align: top;">
                <div class="document-title">QUOTATION</div>
                <div class="font-bold"># {{ $quotation->quotation_number }}</div>
                <div style="margin-top: 5px;">
                    <span class="meta-label">Date:</span>
                    {{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}<br>
                    @if ($quotation->valid_until)
                        <span class="meta-label">Valid Until:</span>
                        {{ \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') }}
                    @endif
                </div>
            </td>
            <td class="text-right" style="width: 50%; vertical-align: top;">
                <div class="company-title">{{ $company->name }}</div>
                <div>
                    @if ($company->gst_number || $company->gstin)
                        GSTIN: {{ $company->gst_number ?? $company->gstin }}<br>
                    @endif
                    Email: {{ $company->email }}<br>
                    Phone: {{ $company->phone }}
                </div>
                @if ($quotation->store)
                    <div style="margin-top: 8px;">
                        <span class="font-bold">Branch:</span><br>
                        {{ $quotation->store->name }}<br>
                        {{ $quotation->store->city ?? '' }} {{ $quotation->store->zip_code ?? '' }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="text-left" style="width: 50%;">
                <div class="meta-label text-uppercase" style="font-size: 10px; margin-bottom: 3px;">Quotation To</div>
                <div class="font-bold" style="font-size: 14px;">{{ $customerName }}</div>
                @if ($customerGST)
                    <div><span class="font-bold">GSTIN:</span> {{ $customerGST }}</div>
                @endif
                @if ($quotation->billing_address)
                    <div>{{ $quotation->billing_address }}</div>
                @endif
                @if ($customerPhone !== 'N/A')
                    <div>Phone: {{ $customerPhone }}</div>
                @endif
            </td>
            <td class="text-right" style="width: 50%;">
                <div><span class="meta-label">Place of Supply:</span> <span
                        class="font-bold">{{ $quotation->supply_state }}</span></div>
                <div><span class="meta-label">Quotation Type:</span> <span class="font-bold">{{ $quoteType }}</span>
                </div>
                <div><span class="meta-label">Status:</span> <span
                        class="font-bold text-uppercase">{{ $quotation->status }}</span></div>
                @if ($quotation->reference_number)
                    <div><span class="meta-label">Ref/PO:</span> <span
                            class="font-bold">{{ $quotation->reference_number }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left">Description</th>
                <th class="text-center">HSN</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-center">Disc</th>
                <th class="text-center">GST</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td class="text-left">
                        <div class="font-bold">{{ $item->product_name }}</div>
                        <div style="font-size: 9px; color: #666;">SKU: {{ $item->sku_code ?? 'N/A' }}</div>
                    </td>
                    <td class="text-center">{{ $item->hsn_code ?? '-' }}</td>
                    <td class="text-center font-bold">{{ (float) $item->quantity }}</td>
                    <td class="text-right">{{ $formatAmt($item->unit_price) }}</td>
                    <td class="text-center">
                        @if ($item->discount_amount > 0)
                            @if ($item->discount_type === 'percentage')
                                <div class="font-bold">{{ (float) $item->discount_value }}%</div>
                                <div style="font-size: 9px; color: #666; margin-top: 2px;">(-{{ $formatAmt($item->discount_amount) }})</div>
                            @else
                                <div class="font-bold">{{ $formatAmt($item->discount_amount) }}</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ (float) $item->tax_percent }}%</td>
                    <td class="text-right font-bold">{{ $formatAmt($item->total_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="avoid-break">
        <table class="totals-table">
            <tr>
                <td class="text-left">Subtotal</td>
                <td class="text-right font-bold">{{ $formatAmt($quotation->subtotal) }}</td>
            </tr>
            @if ($quotation->igst_amount > 0)
                <tr>
                    <td class="text-left">IGST</td>
                    <td class="text-right font-bold">{{ $formatAmt($quotation->igst_amount) }}</td>
                </tr>
            @else
                @if ($quotation->cgst_amount > 0 || $quotation->sgst_amount > 0)
                    <tr>
                        <td class="text-left">CGST</td>
                        <td class="text-right font-bold">{{ $formatAmt($quotation->cgst_amount) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">SGST</td>
                        <td class="text-right font-bold">{{ $formatAmt($quotation->sgst_amount) }}</td>
                    </tr>
                @endif
            @endif

            @if ($quotation->shipping_charge > 0)
                <tr>
                    <td class="text-left">Shipping / Other</td>
                    <td class="text-right font-bold">{{ $formatAmt($quotation->shipping_charge) }}</td>
                </tr>
            @endif

            @if ($quotation->discount_amount > 0)
                <tr>
                    <td class="text-left">
                        Discount
                        @if($quotation->discount_type === 'percentage')
                            <span style="font-size: 10px; color: #555;">({{ (float) $quotation->discount_value }}%)</span>
                        @endif
                    </td>
                    <td class="text-right font-bold">(-) {{ $formatAmt($quotation->discount_amount) }}</td>
                </tr>
            @endif  

            <tr>
                <td class="text-left grand-total text-uppercase">Grand Total</td>
                <td class="text-right grand-total">{{ $formatAmt($quotation->grand_total) }}</td>
            </tr>
        </table>

        <div class="footer">
            @if ($quotation->notes || $quotation->terms_conditions)
                <div style="width: 60%; float: left;">
                    @if ($quotation->notes)
                        <div style="margin-bottom: 15px;">
                            <div class="font-bold text-uppercase" style="font-size: 11px; margin-bottom: 3px;">Notes:
                            </div>
                            <div>{{ $quotation->notes }}</div>
                        </div>
                    @endif

                    @if ($quotation->terms_conditions)
                        <div>
                            <div class="font-bold text-uppercase" style="font-size: 11px; margin-bottom: 3px;">Terms &
                                Conditions:</div>
                            <div style="font-size: 11px;">{!! nl2br(e($quotation->terms_conditions)) !!}</div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="signature-box">
                Authorized Signatory
            </div>
        </div>
    </div>

</body>

</html>
