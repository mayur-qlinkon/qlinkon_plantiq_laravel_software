<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Debit Note - {{ $purchaseReturn->return_number }}</title>
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
            padding-right: 20px;
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
        $companyInfo = $purchaseReturn->store ?? auth()->user()->company;

        // Helper function for clean currency formatting
        $formatAmt = function ($amount) {
            return number_format((float) $amount, 2, '.', ',');
        };
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h1 class="title">Debit Note / Return</h1>
                <div class="document-info">
                    <strong>Return Number:</strong> {{ $purchaseReturn->return_number }}<br>
                    <strong>Return Date:</strong> {{ $purchaseReturn->return_date->format('d M Y') }}<br>
                    <strong>Orig. PO Number:</strong> {{ $purchaseReturn->purchase->purchase_number ?? 'N/A' }}<br>
                    <strong>Status:</strong> <span
                        style="text-transform: uppercase;">{{ str_replace('_', ' ', $purchaseReturn->status) }}</span>
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
                <div class="section-title">Returning To Supplier</div>
                <div class="info-block">
                    <strong>{{ $purchaseReturn->supplier->name ?? 'N/A' }}</strong><br>
                    {{ $purchaseReturn->supplier->address }}<br>
                    @if ($purchaseReturn->supplier->phone)
                        Phone: {{ $purchaseReturn->supplier->phone }}<br>
                    @endif
                    @if ($purchaseReturn->supplier->email)
                        Email: {{ $purchaseReturn->supplier->email }}
                    @endif
                </div>
            </td>
            <td style="width: 48%; padding-left: 2%;">
                <div class="section-title">Dispatching Branch</div>
                <div class="info-block">
                    <strong>{{ $purchaseReturn->warehouse->name ?? 'N/A' }}</strong><br>
                    From Store: {{ $purchaseReturn->store->name ?? 'Default' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Product Description</th>
                <th>Reason</th>
                <th class="text-center">Rtn Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseReturn->items as $item)
                <tr class="page-break">
                    <td>
                        <strong>{{ $item->product->name ?? 'Unknown' }}</strong><br>
                        <span style="color:#555; font-size:10px;">SKU: {{ $item->productSku->sku ?? 'N/A' }}</span>
                    </td>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $item->return_reason) }}</td>
                    <td class="text-center font-bold">(-) {{ (float) $item->quantity }}</td>
                    <td class="text-right">{{ $formatAmt($item->unit_cost) }}</td>
                    <td class="text-right">{{ $formatAmt($item->tax_amount) }}</td>
                    <td class="text-right"><strong>Rs. {{ $formatAmt($item->total_price) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-wrapper page-break">
        <div class="totals-left">
            @if ($purchaseReturn->supplier_credit_note_number)
                <p style="margin-top: 0;"><strong>Supplier Credit Note Ref:</strong><br>
                    {{ $purchaseReturn->supplier_credit_note_number }}</p>
            @endif
            @if ($purchaseReturn->reason)
                <p><strong>Primary Reason for Return:</strong><br> {{ $purchaseReturn->reason }}</p>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td>Taxable Value Return</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchaseReturn->taxable_amount) }}</td>
                </tr>
                <tr>
                    <td>Tax Reversal</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchaseReturn->tax_amount) }}</td>
                </tr>
                <tr class="grand-total-row">
                    <td>TOTAL REFUND EXPECTED</td>
                    <td class="text-right">Rs. {{ $formatAmt($purchaseReturn->total_amount) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if ($purchaseReturn->notes)
        <div class="footer-notes page-break">
            <strong>Additional Notes:</strong><br>
            {!! nl2br(e($purchaseReturn->notes)) !!}
        </div>
    @endif

</body>

</html>
