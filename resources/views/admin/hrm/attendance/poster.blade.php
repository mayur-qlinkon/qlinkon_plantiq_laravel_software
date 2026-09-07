<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance QR Poster — {{ $store->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }

        .poster {
            width: 420px;
            background: #fff;
            border: 3px solid #166534;
            border-radius: 20px;
            overflow: hidden;
            text-align: center;
        }

        .poster-header {
            background: #166534;
            padding: 28px 24px 22px;
            color: #fff;
        }

        .poster-header .label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 6px;
        }

        .poster-header .store-name {
            font-size: 28px;
            font-weight: 900;
            line-height: 1.15;
        }

        .poster-body {
            padding: 28px 32px 24px;
        }

        .instruction {
            font-size: 15px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .qr-wrapper {
            background: #fff;
            border: 2.5px solid #d1fae5;
            border-radius: 16px;
            padding: 16px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .qr-wrapper svg {
            width: 260px;
            height: 260px;
            display: block;
        }

        .steps {
            background: #f0fdf4;
            border-radius: 12px;
            padding: 16px 18px;
            text-align: left;
            margin-bottom: 20px;
        }

        .steps p {
            font-size: 12px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .steps ol {
            padding-left: 18px;
        }

        .steps ol li {
            font-size: 13px;
            color: #374151;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .poster-footer {
            background: #f9fafb;
            border-top: 1.5px solid #e5e7eb;
            padding: 14px;
            font-size: 11px;
            color: #9ca3af;
        }

        @media print {
            body { padding: 0; background: #fff; min-height: auto; display: block; }
            .poster { margin: 0 auto; border: 3px solid #166534; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div>
    <!-- Print Button (hidden on print) -->
    <div class="no-print" style="text-align:center; margin-bottom:20px;">
        <button onclick="window.print()"
            style="background:#166534; color:#fff; border:none; padding:12px 28px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; margin-right:10px;">
            🖨️ Print Poster
        </button>
        <button onclick="window.close()"
            style="background:#f3f4f6; color:#374151; border:none; padding:12px 20px; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer;">
            Close
        </button>
    </div>

    <div class="poster">

        <!-- Header -->
        <div class="poster-header">
            {{-- <p class="label">Office QR Code</p> --}}
            <p class="store-name">{{ $store->name }}</p>
        </div>

        <!-- Body -->
        <div class="poster-body">
            <p class="instruction">Scan QR & Mark Your Attendance</p>

            <!-- QR Code -->
            <div class="qr-wrapper">
                {!! $qrSvg !!}
            </div>

            <!-- Steps -->
            <div class="steps">
                <p>How to scan</p>
                <ol>
                    <li>Open your phone camera</li>
                    <li>Point it at this QR code</li>
                    <li>Tap the link that appears</li>
                    <li>Allow location access &amp; tap Mark Attendance</li>
                </ol>
            </div>
        </div>

        <!-- Footer -->
        <div class="poster-footer">
            Please be physically present at the office to mark attendance
        </div>
    </div>
</div>

<script>
    // Auto-open print dialog when loaded
    window.addEventListener('load', function() {
        setTimeout(() => window.print(), 600);
    });
</script>

</body>
</html>
