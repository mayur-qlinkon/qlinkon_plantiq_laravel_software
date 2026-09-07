<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->order_number }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .bordered th, .bordered td {
            border: 1px solid #000;
            padding: 6px;
        }

        .no-border td {
            padding: 4px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }

        .header {
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
        }

        .small {
            font-size: 11px;
        }

        .total-box td {
            border: 1px solid #000;
            padding: 6px;
        }

    </style>
</head>
<body>

@php
    $format = fn($v) => '₹ ' . number_format($v, 2);
    $paid = $order->payments?->where('status','completed')->sum('amount') ?? 0;
    $balance = $order->total_amount - $paid;
@endphp

<!-- HEADER -->
<table class="no-border header">
    <tr>
        <td>
            <div class="title">{{ $company->name }}</div>
            <div class="small">
                {{ get_setting('address', '', $company->id) }} <br>
                Phone: {{ get_setting('phone', '', $company->id) }} <br>
                GSTIN: {{ get_setting('gstin', '', $company->id) }}
            </div>
        </td>

        <td class="text-right">
            <div class="title">Order</div>
            <div class="small">
                Order No: {{ $order->order_number }} <br>
                Date: {{ $order->created_at->format('d-m-Y') }}
            </div>
        </td>
    </tr>
</table>

<!-- CUSTOMER -->
<table class="bordered" style="margin-bottom:10px;">
    <tr>
        <td width="50%">
            <b>Bill To:</b><br>
            {{ $order->customer_name }}<br>
            {{ $order->customer_phone }}<br>
            {{ $order->delivery_address }}
        </td>
        <td width="50%">
            <b>Payment Status:</b> {{ strtoupper($order->payment_status) }}<br>
            <b>Payment Method:</b> {{ strtoupper($order->payment_method) }}
        </td>
    </tr>
</table>

<!-- ITEMS -->
<table class="bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th class="text-center">Qty</th>
            <th class="text-right">Rate</th>
            <th class="text-right">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $i => $item)
        <tr>
            <td class="text-center">{{ $i+1 }}</td>
            <td>
                {{ $item->product_name }}<br>
                <span class="small">{{ $item->sku_label }}</span>
            </td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-right">{{ $format($item->unit_price) }}</td>
            <td class="text-right">{{ $format($item->line_total) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- TOTAL -->
<table style="margin-top:10px;">
    <tr>
        <td width="60%"></td>
        <td width="40%">
            <table class="total-box">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">{{ $format($order->subtotal) }}</td>
                </tr>

                @if($order->discount_amount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right">- {{ $format($order->discount_amount) }}</td>
                </tr>
                @endif

                @if($order->cgst_amount > 0)
                <tr>
                    <td>CGST</td>
                    <td class="text-right">{{ $format($order->cgst_amount) }}</td>
                </tr>
                <tr>
                    <td>SGST</td>
                    <td class="text-right">{{ $format($order->sgst_amount) }}</td>
                </tr>
                @endif

                @if($order->igst_amount > 0)
                <tr>
                    <td>IGST</td>
                    <td class="text-right">{{ $format($order->igst_amount) }}</td>
                </tr>
                @endif

                <tr>
                    <td class="bold">Grand Total</td>
                    <td class="text-right bold">{{ $format($order->total_amount) }}</td>
                </tr>

                <tr>
                    <td>Paid</td>
                    <td class="text-right">{{ $format($paid) }}</td>
                </tr>

                <tr>
                    <td class="bold">Balance</td>
                    <td class="text-right bold">{{ $format($balance) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- FOOTER -->
<br><br>
<table>
    <tr>
        <td>
            <b>Terms & Conditions:</b><br>
            Goods once sold will not be taken back.<br>
            Subject to Ahmedabad jurisdiction.
        </td>
        <td class="text-right">
            <br><br><br>
            _______________________<br>
            Authorized Signatory
        </td>
    </tr>
</table>

</body>
</html>