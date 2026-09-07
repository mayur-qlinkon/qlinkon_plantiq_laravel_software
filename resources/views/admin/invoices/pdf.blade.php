<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
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

        .text-gray {
            color: #666;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .border-bottom {
            border-bottom: 1px solid #ddd;
        }

        .border-top {
            border-top: 1px solid #ddd;
        }

        .bg-light {
            background-color: #f9f9f9;
        }

        .p-2 {
            padding: 8px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        /* Specific elements */
        .header-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .items-table th {
            background-color: #f3f4f6;
            padding: 10px;
            border-bottom: 2px solid #333;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-table td {
            padding: 6px 10px;
        }
    </style>
</head>

<body>

    @php
        $formatAmt = function ($amount) {
            return number_format((float) $amount, 2, '.', ',');
        };

        // Legal Entity & Operational Branch Details
        $company = $invoice->company ?? auth()->user()->company;
        $store   = $invoice->store;

        // Billing priority: invoice snapshot → store accessor (store accessors already fall back to get_setting)
        $billingGstin        = $invoice->gst_number     ?? $store->gst_number     ?? get_setting('gst_number');
        $billingUpiId        = $invoice->upi_id         ?? $store->upi_id;
        $billingBankName     = $invoice->bank_name      ?? $store->bank_name;
        $billingAccName      = $invoice->account_name   ?? $store->account_name;
        $billingAccNo        = $invoice->account_number ?? $store->account_number;
        $billingIfsc         = $invoice->ifsc_code      ?? $store->ifsc_code;
        $signaturePath = null;

        if ($invoice->signature) {
            $signaturePath = storage_path('app/public/' . $invoice->signature);
        } elseif (!empty($store->signature)) {
            $signaturePath = storage_path('app/public/' . $store->signature);
        }

        $billingSignatureUrl = null;

        if ($signaturePath && file_exists($signaturePath)) {
            $type = pathinfo($signaturePath, PATHINFO_EXTENSION);
            $data = file_get_contents($signaturePath);
            $billingSignatureUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        $billingFooterNote   = $invoice->invoice_footer_note ?? $store->invoice_footer_note;
        $billingTerms        = $invoice->terms_conditions    ?? $store->invoice_terms;

        $customerName = $invoice->client ? $invoice->client->name : $invoice->customer_name ?? 'Guest Customer';
        $customerPhone = $invoice->client ? $invoice->client->phone : 'N/A';
        $customerAddress = $invoice->client ? $invoice->client->address : 'N/A';
        $paidAmt = $invoice->payments->where('status', 'completed')->sum('amount');
        $totalReceived = $invoice->payments->where('status', 'completed')->sum('amount_received');
        $totalChange = $invoice->payments->where('status', 'completed')->sum('change_returned');
        $balanceDue = $invoice->grand_total - $paidAmt;
    @endphp

    {{-- HEADER --}}
    <table style="margin-bottom: 30px;">
        <tr>
            <td style="width: 50%;">
                <div class="header-title">TAX INVOICE</div>
                <div class="font-bold text-gray mt-4"># {{ $invoice->invoice_number }}</div>
            </td>
            <td style="width: 50%;" class="text-right">
                {{-- 🌟 1. Legal Entity (Company) --}}
                <h2 style="margin:0; font-size: 18px;" class="uppercase">{{ $company->name ?? 'Company Name' }}</h2>
                <div class="text-gray" style="line-height: 1.4; margin-top: 6px;">
                    @if ($billingGstin)
                        GSTIN: <strong style="color: #333;">{{ $billingGstin }}</strong><br>
                    @endif
                    @if ($company->email)
                        Email: {{ $company->email }}<br>
                    @endif
                    @if ($company->phone)
                        Phone: {{ $company->phone }}
                    @endif
                </div>

                {{-- 🌟 2. Operational Branch (Store) --}}
                @if ($store)
                    <div style="margin-top: 12px;">
                        <div
                            style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 2px;">
                            Branch / Store</div>
                        <div class="text-gray" style="line-height: 1.4;">
                            <span class="font-bold" style="color: #333;">{{ $store->name }}</span><br>
                            @if ($store->address)
                                {{ $store->address }}<br>
                            @endif
                            {{ $store->city ?? '' }}@if ($store->city && $store->zip_code)
                                ,
                            @endif{{ $store->zip_code ?? '' }}<br>
                            {{ $store->state->name ?? ($store->state_id ?? '') }}
                        </div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- META INFO --}}
    <table class="border-top border-bottom" style="margin-bottom: 30px; padding: 15px 0;">
        <tr>
            <td style="width: 50%;">
                <div
                    style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                    Billed To</div>
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 3px;">{{ $customerName }}</div>
                <div class="text-gray" style="line-height: 1.4;">
                    @if ($customerAddress !== 'N/A')
                        {{ $customerAddress }}<br>
                    @endif
                    @if ($customerPhone !== 'N/A')
                        Phone: {{ $customerPhone }}<br>
                    @endif
                    @if ($invoice->client && $invoice->client->gst_number)
                        GSTIN: <strong>{{ $invoice->client->gst_number }}</strong>
                    @endif
                </div>
            </td>
            <td style="width: 50%; line-height: 1.8;">
                <table>
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 60%;">Invoice Date:</td>
                        <td class="font-bold text-right">
                            {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
                    </tr>
                    @if ($invoice->due_date)
                        <tr>
                            <td class="text-gray font-bold text-right">Due Date:</td>
                            <td class="font-bold text-right">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-gray font-bold text-right">Place of Supply:</td>
                        <td class="font-bold text-right">{{ $invoice->supply_state }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ITEMS TABLE --}}
    <table class="items-table" style="margin-bottom: 30px;">
        <thead>
            <tr>
                <th class="text-left">Product Details</th>
                <th class="text-center">HSN/SAC</th>
                <th class="text-right">Price</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>
                        <div class="font-bold">{{ $item->product_name }}</div>
                        <div style="font-size: 10px; color: #777;">SKU:
    {{ $item->sku->sku_code ?? ($item->sku->sku ?? 'N/A') }}</div>

@if ($item->discount_amount > 0)
    <div style="font-size: 10px; margin-top: 2px; color: #c2410c;">
        Disc:
        @if ($item->discount_type === 'percentage' && (float) $item->discount_value > 0)
            {{ (float) $item->discount_value }}%
            <span style="color:#999;">(-₹{{ $formatAmt($item->discount_amount) }})</span>
        @else
            ₹{{ $formatAmt($item->discount_amount) }}
        @endif
    </div>
@endif
                    </td>
                    <td class="text-center text-gray">{{ $item->hsn_code ?? '-' }}</td>
                    <td class="text-right text-gray">{{ $formatAmt($item->unit_price) }}</td>
                    <td class="text-center font-bold">{{ (float) $item->quantity }}</td>
                    <td class="text-right text-gray">
                        {{ $formatAmt($item->tax_amount) }}<br>
                        <span style="font-size: 9px;">({{ (float) $item->tax_percent }}%)</span>
                    </td>
                    <td class="text-right font-bold">{{ $formatAmt($item->total_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY BLOCK --}}
    <table>
        <tr>
            {{-- Left Side: Notes & Bank --}}
            <td style="width: 50%; padding-right: 20px;">
                @if ($invoice->notes)
                    <div class="mb-2 text-gray"><strong style="color:#333;">Note:</strong> {{ $invoice->notes }}</div>
                @endif

                @if ($billingBankName || $billingAccNo)
                    <div style="margin-top: 20px;">
                        <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                            Bank Details</div>
                        <div class="text-gray" style="line-height: 1.5; font-size: 11px;">
                            @if ($billingBankName)<strong>Bank:</strong> {{ $billingBankName }}<br>@endif
                            @if ($billingAccName)<strong>A/C Name:</strong> {{ $billingAccName }}<br>@endif
                            @if ($billingAccNo)<strong>A/C No:</strong> {{ $billingAccNo }}<br>@endif
                            @if ($billingIfsc)<strong>IFSC:</strong> {{ $billingIfsc }}@endif
                        </div>
                    </div>
                @endif

                {{-- @if ($balanceDue > 0 && $billingUpiId)
                    <div style="margin-top: 15px;">
                        <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                            Pay via UPI
                        </div>
                        @php
                            $upiString = 'upi://pay?pa=' . $billingUpiId . '&pn=' . urlencode($billingAccName ?: $company->name) . '&am=' . $balanceDue . '&cu=INR';

                            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                                ->size(160)
                                ->margin(0)
                                ->generate($upiString);
                        @endphp

                        <div style="width: 80px; height: 80px;">
                            {!! $qrSvg !!}
                        </div>
                    </div>
                @endif --}}
            </td>

            {{-- Right Side: Totals --}}
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 60%;">Taxable Amount</td>
                        <td class="font-bold text-right">₹ {{ $formatAmt($invoice->taxable_amount) }}</td>
                    </tr>

                    @if ($invoice->igst_amount > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">IGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoice->igst_amount) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="text-gray font-bold text-right">CGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoice->cgst_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray font-bold text-right">SGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoice->sgst_amount) }}</td>
                        </tr>
                    @endif

                    @if ($invoice->discount_amount > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">
                                Discount
                                @if ($invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0)
                                    ({{ (float) $invoice->discount_value }}%)
                                @endif
                            </td>
                            <td class="font-bold text-right" style="color: red;">
                                @if ($invoice->discount_type === 'percentage' && (float) $invoice->discount_value > 0)
                                    (-₹{{ $formatAmt($invoice->discount_amount) }})
                                @else
                                    (-) ₹{{ $formatAmt($invoice->discount_amount) }}
                                @endif
                            </td>
                        </tr>
                    @endif

                    @if ($invoice->shipping_charge > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">
                                Freight / Shipping
                                @if(($invoice->shipping_tax_rate ?? 0) > 0)
                                    <span style="font-size:10px; color:#888;">(+ {{ (float)$invoice->shipping_tax_rate }}% GST)</span>
                                @else
                                    <span style="font-size:10px; color:#888;">(Exempt)</span>
                                @endif
                            </td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoice->shipping_charge) }}</td>
                        </tr>
                        @if(($invoice->shipping_tax_amount ?? 0) > 0)
                            <tr>
                                @if($invoice->igst_amount > 0)
                                    <td class="text-gray font-bold text-right">IGST on Freight ({{ (float)$invoice->shipping_tax_rate }}%)</td>
                                @else
                                    <td class="text-gray font-bold text-right">CGST+SGST on Freight ({{ (float)$invoice->shipping_tax_rate }}%)</td>
                                @endif
                                <td class="font-bold text-right">₹ {{ $formatAmt($invoice->shipping_tax_amount) }}</td>
                            </tr>
                        @endif
                    @endif

                    @if ($invoice->round_off != 0)
                        <tr>
                            <td class="text-gray font-bold text-right">Round Off</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoice->round_off) }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="font-bold text-right border-top border-bottom"
                            style="font-size: 14px; padding: 10px;">Grand Total</td>
                        <td class="font-bold text-right border-top border-bottom"
                            style="font-size: 14px; padding: 10px;">₹ {{ $formatAmt($invoice->grand_total) }}</td>
                    </tr>

                    {{-- POS Payment Tracking --}}
                    @if ($totalReceived > 0)
                        <tr class="bg-light">
                            <td class="font-bold text-right">Amount Received</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($totalReceived) }}</td>
                        </tr>
                        @if ($totalChange > 0)
                            <tr class="bg-light">
                                <td class="font-bold text-right">Change Returned</td>
                                <td class="font-bold text-right">₹ {{ $formatAmt($totalChange) }}</td>
                            </tr>
                        @endif
                        <tr class="bg-light">
                            <td class="font-bold text-right border-bottom">Paid Against Bill</td>
                            <td class="font-bold text-right border-bottom">₹ {{ $formatAmt($paidAmt) }}</td>
                        </tr>
                        @if ($balanceDue > 0)
                            <tr>
                                <td class="font-bold text-right">Balance Due</td>
                                <td class="font-bold text-right">₹
                                    {{ $formatAmt($balanceDue) }}</td>
                            </tr>
                        @endif
                    @endif
                </table>

                <div class="text-right" style="margin-top: 50px;">
                    @if ($billingSignatureUrl)
                        <img src="{{ $billingSignatureUrl }}" alt="Authorized Signature"
                            style="max-height: 80px; display: block; margin-left: auto; margin-bottom: 4px;">
                    @endif
                    <div
                        style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; width: 150px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                        Authorized Signatory
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if ($billingFooterNote || $billingTerms)
        <div style="margin-top: 40px; font-size: 10px; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
            @if ($billingFooterNote)
                <div style="margin-bottom: 8px; line-height: 1.5;">{!! nl2br(e($billingFooterNote)) !!}</div>
            @endif
            @if ($billingTerms)
                <div><strong>Terms & Conditions:</strong><br>{!! nl2br(e($billingTerms)) !!}</div>
            @endif
        </div>
    @endif

</body>

</html>