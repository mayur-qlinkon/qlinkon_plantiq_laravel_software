<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Credit Note #{{ $invoiceReturn->credit_note_number }}</title>
    <style>
        /* Base dompdf compatible styles */
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

        td, th {
            vertical-align: top;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-gray { color: #666; }
        .uppercase { text-transform: uppercase; }
        .border-bottom { border-bottom: 1px solid #ddd; }
        .border-top { border-top: 1px solid #ddd; }
        .bg-light { background-color: #f9f9f9; }
        
        /* Spacing */
        .p-2 { padding: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }

        /* Specific elements */
        .header-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1f2937; /* Dark gray to match invoices */
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            margin-top: 5px;
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

        // Resolve Entity/Branch Details
        $company = $invoiceReturn->company ?? auth()->user()->company;
        $store   = $invoiceReturn->store;

        // Fallbacks for billing info
        $billingGstin = $store->gst_number ?? get_setting('gst_number');
        
        // Handle Signature loading exactly like Invoice
        $signaturePath = null;
        if (!empty($store->signature)) {
            $signaturePath = storage_path('app/public/' . $store->signature);
        }
        
        $billingSignatureUrl = null;
        if ($signaturePath && file_exists($signaturePath)) {
            $type = pathinfo($signaturePath, PATHINFO_EXTENSION);
            $data = file_get_contents($signaturePath);
            $billingSignatureUrl = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // Customer Details
        $customerName = $invoiceReturn->customer ? $invoiceReturn->customer->name : $invoiceReturn->customer_name ?? 'Guest Customer';
        $customerPhone = $invoiceReturn->customer ? $invoiceReturn->customer->phone : 'N/A';
        $customerAddress = $invoiceReturn->customer ? $invoiceReturn->customer->address : 'N/A';
    @endphp

    {{-- HEADER --}}
    <table style="margin-bottom: 30px;">
        <tr>
            <td style="width: 50%;">
                <div class="header-title">CREDIT NOTE</div>
                <div class="font-bold text-gray mt-4"># {{ $invoiceReturn->credit_note_number }}</div>
                
                @if($invoiceReturn->status === 'draft')
                    <div class="status-badge" style="color: #ea580c; border-color: #ffedd5; background-color: #fff7ed;">DRAFT</div>
                @else
                    <div class="status-badge" style="color: #16a34a; border-color: #dcfce7; background-color: #f0fdf4;">CONFIRMED</div>
                @endif
            </td>
            <td style="width: 50%;" class="text-right">
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

                @if ($store)
                    <div style="margin-top: 12px;">
                        <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 2px;">
                            Branch / Store
                        </div>
                        <div class="text-gray" style="line-height: 1.4;">
                            <span class="font-bold" style="color: #333;">{{ $store->name }}</span><br>
                            @if ($store->address)
                                {{ $store->address }}<br>
                            @endif
                            {{ $store->city ?? '' }}@if ($store->city && $store->zip_code), @endif{{ $store->zip_code ?? '' }}<br>
                            {{ $store->state->name ?? '' }}
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
                <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                    Issued To
                </div>
                <div style="font-size: 14px; font-weight: bold; margin-bottom: 3px;">{{ $customerName }}</div>
                <div class="text-gray" style="line-height: 1.4;">
                    @if ($customerAddress !== 'N/A')
                        {{ $customerAddress }}<br>
                    @endif
                    @if ($customerPhone !== 'N/A')
                        Phone: {{ $customerPhone }}<br>
                    @endif
                    @if ($invoiceReturn->customer && $invoiceReturn->customer->gst_number)
                        GSTIN: <strong>{{ $invoiceReturn->customer->gst_number }}</strong>
                    @endif
                </div>
            </td>
            <td style="width: 50%; line-height: 1.8;">
                <table>
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 60%;">Date:</td>
                        <td class="font-bold text-right">{{ \Carbon\Carbon::parse($invoiceReturn->return_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray font-bold text-right">Against Invoice:</td>
                        <td class="font-bold text-right">
                            {{ $invoiceReturn->invoice ? $invoiceReturn->invoice->invoice_number : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-gray font-bold text-right">Return Reason:</td>
                        <td class="font-bold text-right uppercase" style="font-size: 10px;">
                            {{ str_replace('_', ' ', $invoiceReturn->return_reason) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-gray font-bold text-right">Place of Supply:</td>
                        <td class="font-bold text-right">{{ $invoiceReturn->supply_state }}</td>
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
                <th class="text-center">Return Qty</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoiceReturn->items as $item)
                @php
                    // Display variants properly if available
                    $variantStr = $item->sku?->skuValues?->map(fn($v) => $v->attributeValue?->value)->filter()->implode(' - ');
                    $displayName = $variantStr ? ($item->product_name . ' - ' . $variantStr) : $item->product_name;
                @endphp
                <tr>
                    <td>
                        <div class="font-bold">{{ $displayName }}</div>
                        <div style="font-size: 10px; color: #777;">SKU: {{ $item->sku->sku_code ?? ($item->sku->sku ?? 'N/A') }}</div>

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
                    <td class="text-center font-bold" style="color: #ea580c;">{{ (float) $item->quantity }}</td>
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
            {{-- Left Side: Notes --}}
            <td style="width: 50%; padding-right: 20px;">
                @if ($invoiceReturn->notes)
                    <div class="mb-2 text-gray"><strong style="color:#333;">Note:</strong> {{ $invoiceReturn->notes }}</div>
                @endif
                
                <div style="margin-top: 20px;">
                    <div style="font-size: 10px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                        Return Type
                    </div>
                    <div class="font-bold" style="font-size: 12px;">
                        @if($invoiceReturn->return_type === 'refund')
                            Refund to Customer
                        @elseif($invoiceReturn->return_type === 'credit')
                            Store Credit
                        @else
                            Replacement
                        @endif
                    </div>
                </div>
            </td>

            {{-- Right Side: Totals --}}
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="text-gray font-bold text-right" style="width: 60%;">Taxable Amount</td>
                        <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->taxable_amount) }}</td>
                    </tr>

                    @if ($invoiceReturn->igst_amount > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">IGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->igst_amount) }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="text-gray font-bold text-right">CGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->cgst_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray font-bold text-right">SGST</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->sgst_amount) }}</td>
                        </tr>
                    @endif

                    @if ($invoiceReturn->discount_amount > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">
                                Invoice Discount
                                @if ($invoiceReturn->discount_type === 'percentage' && (float) $invoiceReturn->discount_value > 0)
                                    ({{ (float) $invoiceReturn->discount_value }}%)
                                @endif
                            </td>
                            <td class="font-bold text-right" style="color: red;">
                                (-) ₹{{ $formatAmt($invoiceReturn->discount_amount) }}
                            </td>
                        </tr>
                    @endif

                    @if ($invoiceReturn->shipping_charge > 0)
                        <tr>
                            <td class="text-gray font-bold text-right">Shipping / Return Fee</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->shipping_charge) }}</td>
                        </tr>
                    @endif

                    @if ($invoiceReturn->round_off != 0)
                        <tr>
                            <td class="text-gray font-bold text-right">Round Off</td>
                            <td class="font-bold text-right">₹ {{ $formatAmt($invoiceReturn->round_off) }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="font-bold text-right border-top border-bottom"
                            style="font-size: 14px; padding: 10px;">Credit Total</td>
                        <td class="font-bold text-right border-top border-bottom"
                            style="font-size: 14px; padding: 10px; color: #16a34a;">₹ {{ $formatAmt($invoiceReturn->grand_total) }}</td>
                    </tr>
                </table>

                <div class="text-right" style="margin-top: 50px;">
                    @if ($billingSignatureUrl)
                        <img src="{{ $billingSignatureUrl }}" alt="Authorized Signature"
                            style="max-height: 80px; display: block; margin-left: auto; margin-bottom: 4px;">
                    @endif
                    <div style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; width: 150px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                        Authorized Signatory
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if ($invoiceReturn->terms_conditions)
        <div style="margin-top: 40px; font-size: 10px; color: #666; border-top: 1px solid #eee; padding-top: 10px;">
            <div><strong>Terms & Conditions:</strong><br>{!! nl2br(e($invoiceReturn->terms_conditions)) !!}</div>
        </div>
    @endif

</body>
</html>