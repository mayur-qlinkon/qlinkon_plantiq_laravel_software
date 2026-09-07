<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Order - {{ $purchase->purchase_number }}</title>
    <style>
        /* Standard Professional B&W Invoice Styling */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .header-table {
            margin-bottom: 30px;
        }

        .header-table td {
            vertical-align: top;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-info {
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.6;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .info-block {
            font-size: 12px;
            line-height: 1.5;
        }

        /* Strict Accounting Table */
        .items-table {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            margin-top: 15px;
        }

        .items-table th {
            padding: 10px 6px;
            font-size: 11px;
            text-transform: uppercase;
            text-align: left;
            border-bottom: 1px solid #000;
        }

        .items-table td {
            padding: 10px 6px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Totals Block */
        .totals-wrapper {
            width: 100%;
            display: table;
            margin-top: 10px;
        }

        .totals-left {
            width: 50%;
            display: table-cell;
            vertical-align: bottom;
        }

        .totals-right {
            width: 50%;
            display: table-cell;
        }

        .totals-table {
            width: 100%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000 !important;
            padding: 10px 8px;
        }

        .footer-notes {
            margin-top: 30px;
            font-size: 11px;
            line-height: 1.5;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }

        .page-break {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>

    @php
        $companyInfo = $purchase->store ?? auth()->user()->company;

        // Helper function for clean currency formatting
        $formatAmt = function ($amount) {
            return number_format((float) $amount, 2, '.', ',');
        };
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h1 class="title">Purchase Order</h1>
                <div class="document-info">
                    <strong>PO Number:</strong> {{ $purchase->purchase_number }}<br>
                    <strong>Order Date:</strong> {{ $purchase->purchase_date->format('d M Y') }}<br>
                    <strong>Status:</strong> <span
                        style="text-transform: uppercase;">{{ str_replace('_', ' ', $purchase->status) }}</span>
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <h2 style="margin:0; font-size: 16px; font-weight: bold; text-transform: uppercase;">
                    {{ $companyInfo->name ?? 'Company Name' }}</h2>
                <div style="margin-top: 5px; line-height: 1.5;">
                    {{ $companyInfo->address }}<br>
                    Ph: {{ $companyInfo->phone }}<br>
                    Email: {{ $companyInfo->email }}
                </div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="section-title">Supplier Details</div>
                <div class="info-block">
                    <strong>{{ $purchase->supplier->name ?? 'N/A' }}</strong><br>
                    {{ $purchase->supplier->address }}<br>
                    @if ($purchase->supplier->phone)
                        Phone: {{ $purchase->supplier->phone }}<br>
                    @endif
                    @if ($purchase->supplier->email)
                        Email: {{ $purchase->supplier->email }}
                    @endif
                </div>
            </td>
            <td style="width: 48%; padding-left: 2%;">
                <div class="section-title">Shipping Destination</div>
                <div class="info-block">
                    <strong>{{ $purchase->warehouse->name ?? 'N/A' }}</strong><br>
                    Delivery Branch: {{ $purchase->store->name ?? 'Default' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Product Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Disc.</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->items as $item)
                @php
                    $qty = (float) $item->quantity;
                    $cost = (float) $item->unit_cost;

                    // Core line math using explicit database amounts
                    $lineGross = $qty * $cost;
                    $lineDiscAmount = (float) $item->discount_amount;
                    $lineTax = (float) $item->tax_amount;

                    // Fallback to strict math if item->total isn't perfectly mapped
                    $lineTotal =
                    $item->total ?? $lineGross - $lineDiscAmount + ($item->tax_type === 'exclusive' ? $lineTax : 0);
                @endphp
                <tr class="page-break">
                    <td>
                        <strong>{{ $item->product->name ?? 'Unknown' }}</strong><br>
                        <span style="color:#555; font-size:10px;">SKU: {{ $item->productSku->sku ?? 'N/A' }}</span>
                    </td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">{{ $formatAmt($cost) }}</td>

                    <td class="text-right">
                        @if ($item->discount_amount > 0)
                            @if ($item->discount_type === 'percentage')
                                {{ $formatAmt($item->discount_value) }}%<br>
                                <span style="font-size: 10px; color: #555;">(-{{ $formatAmt($lineDiscAmount) }})</span>
                            @else
                                Rs. {{ $formatAmt($lineDiscAmount) }}
                            @endif
                        @else
                            -
                        @endif
                    </td>

                    <td class="text-right">{{ $formatAmt($lineTax) }}</td>
                    <td class="text-right"><strong>Rs. {{ $formatAmt($lineTotal) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-wrapper page-break">
        <div class="totals-left">
            @if ($purchase->notes)
                <p><strong>Note:</strong><br> {{ $purchase->notes }}</p>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td>Subtotal (Taxable)</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchase->taxable_amount) }}</td>
                </tr>
                <tr>
                    <td>Total Tax</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchase->tax_amount) }}</td>
                </tr>

                @if ($purchase->discount_amount > 0)
                    <tr>
                        <td>
                            Global Discount
                            @if ($purchase->discount_type === 'percentage' && (float) $purchase->discount_value > 0)
                                <span style="font-size: 10px; color: #555;">({{ (float) $purchase->discount_value }}%)</span>
                            @endif
                        </td>
                        <td class="text-right">(-) Rs. {{ $formatAmt($purchase->discount_amount) }}</td>
                    </tr>
                @endif

                @if ($purchase->shipping_cost > 0)
                    <tr>
                        <td>Shipping Charges</td>
                        <td class="text-right">Rs. {{ $formatAmt($purchase->shipping_cost) }}</td>
                    </tr>
                @endif

                @if ($purchase->other_charges > 0)
                    <tr>
                        <td>Other Charges</td>
                        <td class="text-right">Rs. {{ $formatAmt($purchase->other_charges) }}</td>
                    </tr>
                @endif

                @if ($purchase->round_off != 0)
                    <tr>
                        <td>Round Off</td>
                        <td class="text-right">Rs. {{ $formatAmt($purchase->round_off) }}</td>
                    </tr>
                @endif

                <tr class="grand-total-row">
                    <td>GRAND TOTAL</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchase->total_amount) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if ($purchase->terms_and_conditions)
        <div class="footer-notes page-break">
            <strong>Terms & Conditions:</strong><br>
            {!! nl2br(e($purchase->terms_and_conditions)) !!}
        </div>
    @endif

</body>

</html>
