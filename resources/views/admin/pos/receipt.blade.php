<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Receipt - {{ $invoice->invoice_number }}</title>
    <link rel="icon" type="image/png"
        href="{{ get_setting('favicon') ? asset('storage/' . get_setting('favicon')) : asset('assets/icons/favicon.png') }}" />
    <style>
        /* 🌟 STRICT 80mm THERMAL PRINTER CSS */
        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", "Courier New", Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
        }

        .ticket {
            width: 216px;
            /* 80mm at 72dpi = 226pt; minus 10px padding each side = 206px inner. 216px outer keeps content snug */
            max-width: 100%;
            margin: 0 auto;
            padding: 5px;
            box-sizing: border-box;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
            table-layout: fixed;
            /* columns respect declared widths, never expand */
            word-break: break-word;
            /* long product names wrap instead of pushing the table wider */
        }

        th,
        td {
            padding: 3px 1px;
            overflow: hidden;
        }

        th {
            text-align: left;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .items-table td {
            border-bottom: 1px dashed #eee;
        }

        .items-table .meta-row td {
            border-bottom: 1px dashed #000;
            padding-top: 0;
            padding-bottom: 6px;
            font-size: 10px;
            color: #444;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .totals td {
            padding: 3px 0;
            border: none;
        }

        /* 🌟 STRIP AWAY BROWSER MARGINS DURING ACTUAL PRINT */
        .no-print {
            display: block;
        }

        @media print {
            @page {
                margin: 0;
                size: 50mm auto;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .ticket {
                width: 100% !important;
                padding: 4px !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    {{-- Action Bar: only rendered on the public share page, never printed --}}
    @if (isset($isPublicView) && $isPublicView && !request()->routeIs('*pdf*') && !isset($isPdf))
        <div class="no-print"
            style="
                background: #1e293b;
                padding: 10px 16px;
                overflow: hidden; /* Clears floats safely */
                font-family: sans-serif;
            ">
            <div style="float: left; padding-top: 6px;">
                <span style="color: #94a3b8; font-size: 12px; font-weight: bold;">
                    Receipt #{{ $invoice->invoice_number }}
                </span>
            </div>
            <div style="float: right;">
                <a href="{{ route('pos.receipt.pdf', [$invoice->id, $shareToken]) }}"
                    style="
                        background: #008a62;
                        color: #fff;
                        padding: 6px 14px;
                        border-radius: 6px;
                        font-size: 12px;
                        font-weight: bold;
                        text-decoration: none;
                        display: inline-block;
                        margin-right: 8px;
                    ">
                    &darr; Download PDF
                </a>
                <button onclick="triggerPrint()"
                    style="
                        background: #334155;
                        color: #fff;
                        padding: 6px 14px;
                        border-radius: 6px;
                        font-size: 12px;
                        font-weight: bold;
                        border: none;
                        cursor: pointer;
                        display: inline-block;
                    ">
                    Print
                </button>
            </div>
        </div>
    @endif
    <div class="ticket">
        <div class="text-center">
            {{-- 🌟 COMPANY & BRANCH HEADER --}}
            <h2 class="font-bold" style="margin: 0; font-size: 18px">
                {{ $invoice->company->name ?? 'COMPANY NAME' }}
            </h2>
            <h3 class="font-bold" style="margin: 2px 0; font-size: 14px">
                {{ $invoice->store->name ?? 'Branch Name' }}
            </h3>

            <p style="margin: 4px 0">{{ $invoice->store->address ?? 'Store Address' }}</p>
            <p style="margin: 2px 0">Phone: {{ $invoice->store->phone ?? 'N/A' }}</p>

            @if (isset($invoice->store->gst_number))
                <p style="margin: 2px 0">GSTIN: {{ $invoice->store->gst_number }}</p>
            @endif
            <div class="divider"></div>
        </div>

        {{-- Meta Info --}}
        <div>
            <p style="margin: 2px 0">Receipt: <span class="font-bold">{{ $invoice->invoice_number }}</span></p>
            <p style="margin: 2px 0">Date: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y, h:i A') }}
            </p>
            <p style="margin: 2px 0">Cashier: {{ $invoice->creator->name ?? 'Admin' }}</p>
            <p style="margin: 2px 0">Customer: <span
                    class="font-bold">{{ $invoice->customer_name ?: $invoice->customer->name ?? 'Walk-in' }}</span></p>

            {{-- Show Customer GSTIN for B2B --}}
            @if (!empty($invoice->customer->gst_number) || !empty($invoice->customer_gstin))
                <p style="margin: 2px 0">Cust GST: {{ $invoice->customer->gst_number ?? $invoice->customer_gstin }}</p>
            @endif
        </div>

        {{-- Line Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%">Item</th>
                    <th style="width: 15%">Qty</th>
                    <th class="text-right" style="width: 35%">Total</th>
                </tr>
            </thead>
            @if (batch_enabled())
                @php
                    $receiptBatchMovements = $invoice->relationLoaded('stockMovements')
                        ? $invoice->stockMovements
                            ->where('direction', 'out')
                            ->whereNotNull('batch_number')
                            ->groupBy('product_sku_id')
                        : collect();
                @endphp
            @endif
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td style="padding-bottom: 0">{{ $item->product_name }}</td>
                        <td style="padding-bottom: 0">{{ (int) $item->quantity }}</td>
                        <td class="text-right" style="padding-bottom: 0">
                            {{ number_format($item->total_amount, 2) }}
                        </td>
                    </tr>
                    {{-- SMART SECOND ROW FOR HSN & TAX (Fits perfectly in 80mm) --}}
                    <tr class="meta-row">
                        <td colspan="3">
                            @php
                                $taxPct = (float) $item->tax_percent;
                                $taxType = $item->tax_type ?? 'exclusive';
                                // Use DB-stored values — already have proportional discount baked in
                                $taxableLine = (float) $item->taxable_value;
                                $taxAmtLine = (float) $item->tax_amount;
                            @endphp

                            @if ($item->hsn_code)
                                HSN:{{ $item->hsn_code }} |
                            @endif

                            @if ($taxPct > 0)
                                | {{ $taxType === 'inclusive' ? 'Incl.' : '+' }}{{ $taxPct }}%
                                GST:&#8377;{{ number_format($taxAmtLine, 2) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Financials & GST Breakup --}}
        @php
            $trueTaxableSubtotal = 0;
            $trueGstTotal = 0;

            foreach ($invoice->items as $item) {
                // Use DB-stored values (proportional discount already deducted per line)
                $trueTaxableSubtotal += (float) $item->taxable_value;
                $trueGstTotal += (float) $item->tax_amount;
            }

            // Split total GST equally for CGST/SGST (intra-state)
            $trueCgst = $trueGstTotal / 2;
            $trueSgst = $trueGstTotal / 2;
        @endphp

        <table class="totals">
            {{-- 1. Display Taxable Subtotal --}}
            <tr>
                <td>Taxable Value</td>
                <td class="text-right">&#8377;{{ number_format($trueTaxableSubtotal, 2) }}</td>
            </tr>

            {{-- 2. Display Discount (If Any) --}}
            @if ($invoice->discount_amount > 0)
                <tr>
                    <td class="font-bold">Discount</td>
                    <td class="text-right font-bold">-&#8377;{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif

            {{-- 3. GST: IGST for inter-state, CGST+SGST for intra-state (from stored DB values) --}}
            @if ($invoice->igst_amount > 0)
                <tr>
                    <td>IGST</td>
                    <td class="text-right">&#8377;{{ number_format($invoice->igst_amount, 2) }}</td>
                </tr>
            @elseif ($invoice->cgst_amount > 0)
                <tr>
                    <td>
                        CGST
                        ({{ number_format((($invoice->cgst_amount / max($invoice->taxable_amount, 0.01)) * 100) / 2, 1) }}%)
                    </td>
                    <td class="text-right">&#8377;{{ number_format($invoice->cgst_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>
                        SGST
                        ({{ number_format((($invoice->sgst_amount / max($invoice->taxable_amount, 0.01)) * 100) / 2, 1) }}%)
                    </td>
                    <td class="text-right">&#8377;{{ number_format($invoice->sgst_amount, 2) }}</td>
                </tr>
            @endif

            {{-- 4. Round Off --}}
            @if ($invoice->round_off != 0)
                <tr>
                    <td>Round Off</td>
                    <td class="text-right">&#8377;{{ number_format($invoice->round_off, 2) }}</td>
                </tr>
            @endif

            {{-- 5. Grand Total (Sum of inclusive prices) --}}
            <tr class="font-bold" style="font-size: 15px">
                <td style="border-top: 1px dashed #000; padding-top: 5px">GRAND TOTAL</td>
                <td class="text-right" style="border-top: 1px dashed #000; padding-top: 5px">
                    &#8377;{{ number_format($invoice->grand_total, 2) }}
                </td>
            </tr>
        </table>

        {{-- Smart Divider Logic --}}
        @php
            $upiId = $invoice->store->upi_id ?? null;
            $showQr = false;

            // 🔥 CALCULATE ACTUAL PAYMENT STATUS
            $amountReceived = $payment->amount_received ?? 0;
            $grandTotal = $invoice->grand_total ?? 0;

            $isFullyPaid = $amountReceived >= $grandTotal;

            if (!empty($upiId)) {
                // ✅ SHOW QR IF NOT FULLY PAID
                if (!$isFullyPaid) {
                    $showQr = true;
                }

                // ✅ ALSO SHOW QR IF PAYMENT METHOD IS UPI (even if full paid)
                elseif (isset($payment) && isset($payment->paymentMethod)) {
                    $methodStr = strtolower(
                        ($payment->paymentMethod->name ?? '') . ' ' . ($payment->paymentMethod->slug ?? ''),
                    );

                    if (
                        str_contains($methodStr, 'upi') ||
                        str_contains($methodStr, 'qr') ||
                        str_contains($methodStr, 'scan') ||
                        str_contains($methodStr, 'gpay') ||
                        str_contains($methodStr, 'phonepe')
                    ) {
                        $showQr = true;
                    }
                }
            }
        @endphp

        @if ($payment || $showQr)
            <div class="divider"></div>
        @endif

        {{-- Payment Ledger --}}
        @if ($payment)
            <table class="totals" style="margin-top: 5px">
                <tr>
                    <td>
                        Paid via
                        ({{ $payment->paymentMethod->name ?? ($payment->paymentMethod->title ?? ($payment->paymentMethod->label ?? 'Cash')) }})
                    </td>
                    <td class="text-right font-bold">&#8377;{{ number_format($payment->amount_received, 2) }}</td>
                </tr>
                @if ($payment->change_returned > 0)
                    <tr>
                        <td>Change Returned</td>
                        <td class="text-right">&#8377;{{ number_format($payment->change_returned, 2) }}</td>
                    </tr>
                @endif
            </table>

            @if ($showQr)
                <div class="divider"></div>
            @endif
        @endif

        {{-- 🌟 DYNAMIC UPI QR CODE --}}
        @if ($showQr)
            @php
                $payeeName = rawurlencode($invoice->store->name ?? 'Store');
                $amount = number_format($invoice->grand_total, 2, '.', '');
                $upiString = "upi://pay?pa={$upiId}&pn={$payeeName}&am={$amount}&cu=INR";
                $qrApiUrl =
                    'https://api.qrserver.com/v1/create-qr-code/?size=120x120&margin=0&data=' . urlencode($upiString);
            @endphp

            <div class="text-center" style="margin: 10px 0">
                <p class="font-bold" style="font-size: 11px; margin-bottom: 5px">Scan to Pay via UPI</p>
                <img src="{{ $qrApiUrl }}" alt="UPI QR Code"
                    style="width: 110px; height: 110px; margin: 0 auto; display: block" />
                <p style="font-size: 10px; margin-top: 5px; font-family: monospace">UPI ID: {{ $upiId }}</p>
            </div>

            <div class="divider"></div>
        @endif

        {{-- Footer --}}
        <div class="text-center">
            @if ($invoice->discount_amount > 0)
                <p class="font-bold" style="margin: 10px 0 10px 0; border: 1px dashed #000; padding: 4px">You Saved
                    &#8377;{{ number_format($invoice->discount_amount, 2) }}!</p>
            @endif
            <p class="font-bold" style="margin: 5px 0">Thank you for your visit!</p>
            <p style="margin: 0; font-size: 10px">Powered by Qlinkon</p>
        </div>
    </div>

    <script>
        async function triggerPrint() {
            // Check if running inside the Flutter App
            if (window.AndroidPrinter && typeof window.AndroidPrinter.printReceipt === "function") {
                try {
                    // Fetch the JSON data from your Laravel controller
                    const response = await fetch("{{ route('admin.pos.receipt.json', $invoice->id) }}");
                    if (!response.ok) throw new Error("Could not load receipt data");

                    // Get it as a raw string and send it to the Flutter Native Bridge
                    const jsonString = await response.text();
                    window.AndroidPrinter.printReceipt(jsonString);
                } catch (err) {
                    console.error("Native print failed, falling back to browser print:", err);
                    window.print(); // Fallback if API fails
                }
            } else {
                // Not inside the Flutter app (running in standard browser)
                window.print();
            }
        }
    </script>
</body>

</html>
