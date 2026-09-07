<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Labels</title>
    <style>
        /* Exact paper size definition */
        @page {
            size: 50mm 25mm;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        /* Each label takes up exactly one page */
        body {
            margin: 0;
            padding: 0;
            /* DejaVu Sans is bundled with Dompdf and perfectly supports the ₹ character */
            font-family: "DejaVu Sans", sans-serif;
        }

        /* Each label takes up exactly one page */
        .page {
            /* 50mm width - 3mm total padding = 47mm */
            width: 47mm;
            /* 25mm height - 3mm total padding = 22mm */
            height: 22mm;
            padding: 1.5mm;
            margin: 0;
            overflow: hidden;
        }

        /* ----- BARCODE LAYOUT (CENTERED) ----- */
        .layout-barcode {
            text-align: center;
        }
        .layout-barcode .store {
            font-size: 5pt;
            font-weight: normal;
            color: #444;
            text-transform: uppercase;
            text-overflow: ellipsis;
        }
        .layout-barcode .product {
            /* font-size handled dynamically by PHP */
            font-weight: normal;
            margin-top: 1px;
            line-height: 1.3; /* Increased line-height gives natural room for bottom-hanging letters (like p, g, y) */
            white-space: nowrap;
            overflow: hidden;
        }
        .layout-barcode .barcode-container {
            margin-top: 0px; /* Reset this so we don't break the page height */
        }
        .layout-barcode img {
            height: 7mm; /* Shrunk by 1mm to compensate for the text height, keeping everything safely on one page */
            max-width: 45mm;
        }
        .layout-barcode .sku {
            font-family: monospace;
            font-size: 5pt;
            margin-top: 1px;
        }
        .layout-barcode .price {
            font-size: 8pt;
            font-weight: normal;
            margin-top: 2px;
        }

        /* ----- QR LAYOUT (SIDE-BY-SIDE) ----- */
        .layout-qr {
            width: 100%;
        }
        /* Tables are the safest way to do side-by-side in Dompdf */
        .qr-table {
            width: 100%;
            border-collapse: collapse;
            /* 🌟 MATHEMATICAL CENTER: (22mm container - 15mm content) / 2 = 3.5mm */
            margin-top: 3.5mm;
        }
        .qr-table td {
            vertical-align: middle;
        }
        .qr-left {
            width: 17mm; /* Increased width to accommodate padding */
            padding-left: 2mm; /* Safety buffer to prevent printer cutoff */
            text-align: left;
        }
        .qr-left img {
            width: 15mm;
            height: 15mm;
            display: block; /* 🌟 ROOT FIX: Removes the invisible font-baseline gap under the image that pushes content upward */
        }
        .qr-right {
            padding-left: 2mm;
            text-align: left;
            line-height: 1.1;
        }
        .qr-right .store {
            /* font-size handled dynamically by PHP */
            font-weight: normal;
            color: #444;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
        }
        .qr-right .product {
            /* font-size handled dynamically by PHP */
            font-weight: normal;
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
        }
        .qr-right .variant {
            /* font-size handled dynamically by PHP */
            color: #555;
            white-space: nowrap;
            overflow: hidden;
        }
        .qr-right .price {
            font-size: 9pt;
            font-weight: normal;
            margin-top: 2mm;
        }
    </style>
</head>
<body>
    @foreach ($processedLabels as $label)
        @php
            // 🌟 SMART TEXT ENGINE: Calculate lengths to adjust font size and natively inject dots
            $rawName = $label['name'];
            $rawAttr = $label['attributes'] ?? '';
            $rawStore = $storeName;

            // --- Barcode Formatting (Wider space) ---
            $bcFullName = $rawName . ($rawAttr ? ' - ' . $rawAttr : '');
            $bcFontSize = strlen($bcFullName) > 28 ? '6.5pt' : '7.5pt'; // Shrink font if text is long
            $bcText = \Illuminate\Support\Str::limit($bcFullName, 38, '...'); // Hard cut with dots at 38 chars
            $bcStore = \Illuminate\Support\Str::limit($rawStore, 35, '...');

            // --- QR Formatting (Narrower space) ---
            $qrFontSize = strlen($rawName) > 16 ? '6pt' : '7pt'; // Shrink font if text is long
            $qrText = \Illuminate\Support\Str::limit($rawName, 21, '...'); // Hard cut with dots at 21 chars
            $qrStore = \Illuminate\Support\Str::limit($rawStore, 20, '...');
            
            $varFontSize = strlen($rawAttr) > 18 ? '4.5pt' : '5pt'; // Shrink variant font if long
            $varText = \Illuminate\Support\Str::limit($rawAttr, 24, '...');
        @endphp

        @if ($type === 'barcode')
            {{-- Only apply the page break if it is NOT the very last label --}}
            <div class="page layout-barcode" @if (!$loop->last) style="page-break-after: always" @endif>
                <div class="store" style="font-size: 5pt;">{{ $bcStore }}</div>
                <div class="product" style="font-size: {{ $bcFontSize }};">
                    {{ $bcText }}
                </div>

                <div class="barcode-container">
                    <img src="{{ $label['image'] }}" />
                    <div class="sku">{{ $label['label_value'] }}</div>
                </div>

                @if ($label['price'])
                    <div class="price">{{ $label['price'] }}</div>
                @endif
            </div>
        @else
            <div class="page layout-qr" @if (!$loop->last) style="page-break-after: always" @endif>
                <table class="qr-table">
                    <tr>
                        <td class="qr-left">
                            <img src="{{ $label['image'] }}" />
                        </td>
                        <td class="qr-right">
                            <div class="store" style="font-size: 5pt;">{{ $qrStore }}</div>
                            <div class="product" style="font-size: {{ $qrFontSize }};">{{ $qrText }}</div>
                            
                            @if ($rawAttr)
                                <div class="variant" style="font-size: {{ $varFontSize }};">{{ $varText }}</div>
                            @endif

                            @if ($label['price'])
                                <div class="price">{{ $label['price'] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endforeach
</body>
</html>
