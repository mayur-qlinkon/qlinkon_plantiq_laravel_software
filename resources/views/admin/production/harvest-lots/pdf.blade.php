@php
    $currentStatus =
        $harvest->status instanceof \App\Enums\Production\HarvestStatus
            ? $harvest->status
            : \App\Enums\Production\HarvestStatus::tryFrom($harvest->status);

    if (!$currentStatus) {
        $currentStatus = \App\Enums\Production\HarvestStatus::Pending;
    }

    $statusClass = strtolower($currentStatus->name ?? 'pending');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Harvest Lot #H-{{ $harvest->id }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
            font-size: 12px;
            color: #374151;
            line-height: 1.5;
            background-color: #ffffff;
        }

        .top-bar {
            height: 6px;
            background-color: #059669;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .page-container {
            padding: 30px 40px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-eyebrow {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 28px;
            font-weight: bold;
            color: #111827;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 11px;
            color: #6b7280;
        }

        .meta-table td strong {
            color: #111827;
            margin-right: 8px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px 5px 12px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-top: 5px;
            line-height: 1;
            text-align: center;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-received {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #ffffff;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            background-color: #f9fafb;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: bold;
            font-size: 13px;
            color: #374151;
        }

        .card-body {
            padding: 16px;
        }

        .data-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .data-grid td {
            vertical-align: top;
            padding-right: 16px;
            padding-bottom: 16px;
            width: 50%;
        }

        .data-grid td:last-child {
            padding-right: 0;
        }

        .data-label {
            display: block;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .data-value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .data-sub {
            display: block;
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
        }

        .code-pill {
            display: inline-block;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
            background-color: #f3f4f6;
            padding: 5px 8px 4px 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            line-height: 1;
        }

        .callout {
            border-radius: 6px;
            margin-top: 12px;
            font-size: 11.5px;
            border-left: 4px solid;
            overflow: hidden;
        }

        .callout-notes {
            padding: 12px 16px;
            background-color: #f9fafb;
            border-left-color: #9ca3af;
            color: #4b5563;
            font-style: italic;
        }

        .callout-success {
            background-color: #ecfdf5;
            border-left-color: #10b981;
            border: 1px solid #a7f3d0;
            border-left: 4px solid #10b981;
        }

        .outcome-table {
            width: 100%;
            border-collapse: collapse;
        }

        .outcome-table td {
            vertical-align: top;
            padding: 16px;
        }

        .outcome-table td:first-child {
            padding-left: 20px;
        }

        .callout-danger {
            background-color: #fef2f2;
            border-left-color: #ef4444;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            text-align: center;
            padding: 24px;
        }

        .callout-danger .danger-title {
            color: #991b1b;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .callout-danger .danger-text {
            color: #b91c1c;
            font-size: 12px;
        }

        footer {
            position: fixed;
            bottom: 0px;
            left: 40px;
            right: 40px;
            height: 40px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            letter-spacing: 0.5px;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="top-bar"></div>

    <footer>
        HARVEST LOT REPORT &nbsp;|&nbsp; REF: #H-{{ $harvest->id }} &nbsp;|&nbsp; PAGE <span class="page-number"></span>
    </footer>

    <div class="page-container">

        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="brand-eyebrow">Production / Harvest Receiving</div>
                    <div class="doc-title">Harvest Lot Report</div>
                    <div class="badge badge-{{ $statusClass }}">{{ $currentStatus->label() }}</div>
                </td>
                <td style="width: 40%;">
                    <table class="meta-table">
                        <tr>
                            <td><strong>Lot Reference:</strong></td>
                            <td>#H-{{ $harvest->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>Harvested On:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($harvest->harvested_on)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Generated On:</strong></td>
                            <td>{{ $generatedAt }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="card">
            <div class="card-header">Harvest Details</div>
            <div class="card-body">
                <table class="data-grid">
                    <tr>
                        <td>
                            <span class="data-label">Reported By</span>
                            <span class="data-value">{{ $harvest->harvestedBy->name ?? 'System' }}</span>
                            <span class="data-sub">Field Worker</span>
                        </td>
                        <td>
                            <span class="data-label">Reported Quantity</span>
                            <span class="data-value">{{ number_format($harvest->quantity_harvested) }} Plants</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Product &amp; Source Batch</div>
            <div class="card-body">
                <div style="font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 16px;">
                    {{ $harvest->batch->product->name ?? 'Unknown Product' }}
                </div>

                <table class="data-grid">
                    <tr>
                        <td>
                            <span class="data-label">SKU Identifier</span>
                            <span class="code-pill">{{ $harvest->batch->sku->sku ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="data-label">Source Batch Code</span>
                            <span class="code-pill">{{ $harvest->batch->batch_code }}</span>
                        </td>
                    </tr>
                </table>

                @if ($harvest->notes)
                    <div class="callout callout-notes">
                        <strong
                            style="display: block; font-size: 10px; margin-bottom: 4px; font-style: normal; text-transform: uppercase;">Worker
                            Notes</strong>
                        "{{ $harvest->notes }}"
                    </div>
                @endif
            </div>
        </div>

        @if ($currentStatus === \App\Enums\Production\HarvestStatus::Received)
            <div class="section-title" style="margin-top: 32px; color: #065f46;">Stock Receiving Confirmation</div>
            <div class="callout callout-success">
                <table class="outcome-table">
                    <tr>
                        <td style="width: 33%;">
                            <span class="data-label" style="color: #047857;">Final Received Qty</span>
                            <span class="data-value"
                                style="color: #064e3b;">{{ number_format($harvest->received_quantity) }} Plants</span>
                        </td>
                        <td style="width: 33%;">
                            <span class="data-label" style="color: #047857;">Destination Warehouse</span>
                            <span class="data-value"
                                style="color: #064e3b;">{{ $harvest->warehouse->name ?? 'Unknown' }}</span>
                        </td>
                        <td style="width: 33%;">
                            <span class="data-label" style="color: #047857;">Received By &amp; Date</span>
                            <span class="data-value"
                                style="color: #064e3b; font-size: 12px;">{{ $harvest->receivedBy->name ?? 'Admin' }}</span>
                            <span class="data-sub"
                                style="color: #059669;">{{ \Carbon\Carbon::parse($harvest->received_at)->format('d M Y, h:i A') }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        @if ($currentStatus === \App\Enums\Production\HarvestStatus::Cancelled)
            <div class="callout callout-danger" style="margin-top: 32px;">
                <div class="danger-title">This Harvest Lot Was Cancelled</div>
                <div class="danger-text">The reported quantity was returned to the source plant batch and is no longer
                    valid.</div>
            </div>
        @endif

    </div>
</body>

</html>
