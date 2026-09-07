@php
    $currentStatus = $plantBatch->statusEnum();

    // Maps the enum to the CSS badge class below (badge-active / badge-closed / badge-cancelled)
    $statusClass = strtolower($currentStatus->value);

    // Quantity reconciliation: initial - lost - harvested should land on current_quantity.
    // If it does not, the batch has manual adjustments and we flag the variance instead of hiding it.
    $expectedQuantity = (int) $plantBatch->initial_quantity - $totalLost - $totalHarvested;
    $variance = (int) $plantBatch->current_quantity - $expectedQuantity;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Plant Batch {{ $plantBatch->batch_code }}</title>
    <style>
        /* Base Reset & Typography */
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

        /* Top Decorative Bar */
        .top-bar {
            height: 6px;
            background-color: #059669;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .page-container {
            padding: 30px 40px 60px 40px;
        }

        /* ── Header ───────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-eyebrow {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .doc-title {
            font-size: 26px;
            font-weight: 800;
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
            padding: 3px 0;
            font-size: 11px;
            color: #6b7280;
        }

        .meta-table td strong {
            color: #111827;
            margin-right: 8px;
        }

        /* ── Badges ───────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-radius: 4px;
            margin-top: 5px;
        }

        .badge-active {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-closed {
            background-color: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .badge-cancelled {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge-sm {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 3px;
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        /* ── Sections & Cards ─────────────────────────────── */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #ffffff;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background-color: #f9fafb;
            padding: 10px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 13px;
            color: #374151;
        }

        .card-body {
            padding: 16px;
        }

        /* ── Data Grids ───────────────────────────────────── */
        .data-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .data-grid td {
            vertical-align: top;
            padding-right: 16px;
            padding-bottom: 14px;
        }

        .data-grid td:last-child {
            padding-right: 0;
        }

        .data-grid tr:last-child td {
            padding-bottom: 0;
        }

        .data-label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .data-value {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .data-sub {
            display: block;
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
        }

        .code-pill {
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            font-weight: 700;
            color: #1f2937;
            background-color: #f3f4f6;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        /* ── Quantity Summary Strip ───────────────────────── */
        .stat-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 20px;
        }

        .stat-table td {
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            background-color: #f9fafb;
        }

        .stat-value {
            display: block;
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
        }

        .stat-label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-top: 4px;
        }

        .stat-loss .stat-value {
            color: #b91c1c;
        }

        .stat-harvest .stat-value {
            color: #047857;
        }

        .stat-current {
            background-color: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
        }

        .stat-current .stat-value {
            color: #064e3b;
        }

        /* ── Ledger Tables ────────────────────────────────── */
        .ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .ledger th {
            background-color: #f9fafb;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .ledger td {
            padding: 8px 12px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
            color: #374151;
        }

        .ledger tr:last-child td {
            border-bottom: none;
        }

        .ledger .num {
            text-align: right;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
        }

        .empty-row {
            padding: 16px 12px !important;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
        }

        .truncate-note {
            padding: 8px 12px;
            font-size: 9px;
            color: #9ca3af;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
            text-align: center;
        }

        /* ── Callout Boxes ────────────────────────────────── */
        .callout {
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 12px;
            font-size: 11.5px;
            border-left: 4px solid;
        }

        .callout-notes {
            background-color: #f9fafb;
            border-left-color: #9ca3af;
            color: #4b5563;
            font-style: italic;
        }

        .callout-warning {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            font-size: 11px;
        }

        .callout-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            text-align: center;
            padding: 20px;
            margin-top: 24px;
            border-radius: 6px;
        }

        .callout-danger .danger-title {
            color: #991b1b;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .callout-danger .danger-text {
            color: #b91c1c;
            font-size: 11.5px;
        }

        /* ── Footer ───────────────────────────────────────── */
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
    <!-- Top Green Accent Bar -->
    <div class="top-bar"></div>

    <!-- Fixed Footer -->
    <footer>
        PLANT BATCH REPORT &nbsp;|&nbsp; REF: {{ $plantBatch->batch_code }} &nbsp;|&nbsp; PAGE <span
            class="page-number"></span>
    </footer>

    <div class="page-container">

        <!-- Header Section -->
        <table class="header-table">
            <tr>
                <td style="width: 58%;">
                    <div class="brand-eyebrow">Production / Plant Batches</div>
                    <div class="doc-title">Plant Batch Report</div>
                    <div class="badge badge-{{ $statusClass }}">{{ $currentStatus->label() }}</div>
                </td>
                <td style="width: 42%;">
                    <table class="meta-table">
                        <tr>
                            <td><strong>Batch Code:</strong></td>
                            <td>{{ $plantBatch->batch_code }}</td>
                        </tr>
                        <tr>
                            <td><strong>Started On:</strong></td>
                            <td>{{ $plantBatch->batch_start_datetime?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @if ($plantBatch->batch_end_datetime)
                            <tr>
                                <td><strong>Ended On:</strong></td>
                                <td>{{ $plantBatch->batch_end_datetime->format('d M Y') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td><strong>Generated On:</strong></td>
                            <td>{{ $generatedAt }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Quantity Reconciliation Strip -->
        <table class="stat-table">
            <tr>
                <td>
                    <span class="stat-value">{{ number_format($plantBatch->initial_quantity) }}</span>
                    <span class="stat-label">Initial Qty</span>
                </td>
                <td class="stat-loss">
                    <span class="stat-value">{{ number_format($totalLost) }}</span>
                    <span class="stat-label">Total Lost</span>
                </td>
                <td class="stat-harvest">
                    <span class="stat-value">{{ number_format($totalHarvested) }}</span>
                    <span class="stat-label">Total Harvested</span>
                </td>
                <td class="stat-current">
                    <span class="stat-value">{{ number_format($plantBatch->current_quantity) }}</span>
                    <span class="stat-label">Current Qty</span>
                </td>
            </tr>
        </table>

        @if ($variance !== 0)
            <div class="callout callout-warning">
                <strong>Quantity variance detected:</strong>
                Initial minus losses and harvests works out to {{ number_format($expectedQuantity) }}, but the batch
                currently holds {{ number_format($plantBatch->current_quantity) }}
                ({{ $variance > 0 ? '+' : '' }}{{ number_format($variance) }}). This normally means a manual
                adjustment was recorded against the batch.
            </div>
        @endif

        <!-- Batch Details -->
        <div class="card">
            <div class="card-header">Batch Details</div>
            <div class="card-body">
                <div style="font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 14px;">
                    {{ $plantBatch->product->name ?? 'Unknown Product' }}
                </div>

                <table class="data-grid">
                    <tr>
                        <td style="width: 33%;">
                            <span class="data-label">SKU Identifier</span>
                            <span class="code-pill">{{ $plantBatch->sku->sku ?? 'N/A' }}</span>
                        </td>
                        <td style="width: 33%;">
                            <span class="data-label">Source Type</span>
                            <span class="data-value">{{ $plantBatch->source_type_label }}</span>
                        </td>
                        <td style="width: 34%;">
                            <span class="data-label">Batch Age</span>
                            <span class="data-value">{{ $plantBatch->age ?? '—' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="data-label">Created By</span>
                            <span class="data-value">{{ $plantBatch->createdBy->name ?? 'System' }}</span>
                        </td>
                        <td>
                            <span class="data-label">Started</span>
                            <span
                                class="data-value">{{ $plantBatch->batch_start_datetime?->format('d M Y, h:i A') ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="data-label">Ended</span>
                            <span
                                class="data-value">{{ $plantBatch->batch_end_datetime?->format('d M Y, h:i A') ?? 'Not ended' }}</span>
                        </td>
                    </tr>
                </table>

                @if ($plantBatch->notes)
                    <div class="callout callout-notes">
                        <strong
                            style="display: block; font-size: 10px; margin-bottom: 4px; font-style: normal; text-transform: uppercase;">Batch
                            Notes</strong>
                        "{{ $plantBatch->notes }}"
                    </div>
                @endif
            </div>
        </div>

        <!-- Source Document -->
        @if ($plantBatch->source_type === 'production_plan' && $plantBatch->productionPlanItem)
            <div class="card">
                <div class="card-header">Source Document — Production Plan</div>
                <div class="card-body">
                    <table class="data-grid">
                        <tr>
                            <td style="width: 50%;">
                                <span class="data-label">Plan Reference</span>
                                <span
                                    class="code-pill">{{ $plantBatch->productionPlanItem->plan->plan_code ?? '#' . $plantBatch->productionPlanItem->production_plan_id }}</span>
                            </td>
                            <td style="width: 50%;">
                                <span class="data-label">Planned By</span>
                                <span
                                    class="data-value">{{ $plantBatch->productionPlanItem->plan->createdBy->name ?? '—' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @elseif ($plantBatch->source_type === 'purchase' && $plantBatch->purchaseItem)
            <div class="card">
                <div class="card-header">Source Document — Purchase</div>
                <div class="card-body">
                    <table class="data-grid">
                        <tr>
                            <td style="width: 50%;">
                                <span class="data-label">Purchase Reference</span>
                                <span
                                    class="code-pill">{{ $plantBatch->purchaseItem->purchase->purchase_no ?? '#' . $plantBatch->purchaseItem->purchase_id }}</span>
                            </td>
                            <td style="width: 50%;">
                                <span class="data-label">Supplier</span>
                                <span
                                    class="data-value">{{ $plantBatch->purchaseItem->purchase->supplier->name ?? '—' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        <!-- Current Placement -->
        <div class="card">
            <div class="card-header">Current Placement</div>
            <div class="card-body">
                @if ($currentPlacement)
                    <table class="data-grid">
                        <tr>
                            <td style="width: 33%;">
                                <span class="data-label">Site</span>
                                <span
                                    class="data-value">{{ $currentPlacement->growingSpace->site->name ?? '—' }}</span>
                            </td>
                            <td style="width: 33%;">
                                <span class="data-label">Zone</span>
                                <span
                                    class="data-value">{{ $currentPlacement->growingSpace->zone->name ?? '—' }}</span>
                            </td>
                            <td style="width: 34%;">
                                <span class="data-label">Growing Space</span>
                                <span class="data-value">{{ $currentPlacement->growingSpace->name ?? '—' }}</span>
                                <span class="data-sub">Placed on
                                    {{ $currentPlacement->placed_at?->format('d M Y, h:i A') ?? '—' }}</span>
                            </td>
                        </tr>
                    </table>
                @else
                    <div style="color: #9ca3af; font-style: italic; font-size: 11.5px;">
                        This batch is not currently placed in any growing space.
                    </div>
                @endif
            </div>
        </div>

        <!-- Placement History -->
        <div class="card">
            <div class="card-header">Placement &amp; Movement History</div>
            <table class="ledger">
                <thead>
                    <tr>
                        <th style="width: 30%;">Growing Space</th>
                        <th style="width: 22%;">Placed At</th>
                        <th style="width: 22%;">Ended At</th>
                        <th style="width: 26%;">Placed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($placements as $placement)
                        <tr>
                            <td>
                                <strong>{{ $placement->growingSpace->name ?? '—' }}</strong>
                                <span class="data-sub">
                                    {{ $placement->growingSpace->site->name ?? '—' }} &rsaquo;
                                    {{ $placement->growingSpace->zone->name ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $placement->placed_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>
                                @if ($placement->ended_at)
                                    {{ $placement->ended_at->format('d M Y, h:i A') }}
                                @else
                                    <span class="badge-sm">Ongoing</span>
                                @endif
                            </td>
                            <td>{{ $placement->placedBy->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-row">No placement history recorded for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($placements->count() === 25)
                <div class="truncate-note">Showing the 25 most recent movements only.</div>
            @endif
        </div>

        <!-- Loss Ledger -->
        <div class="card">
            <div class="card-header">Loss Ledger &nbsp;—&nbsp; {{ number_format($totalLost) }} plants lost in total
            </div>
            <table class="ledger">
                <thead>
                    <tr>
                        <th style="width: 18%;">Date</th>
                        <th style="width: 14%;" class="num">Qty Lost</th>
                        <th style="width: 24%;">Reason</th>
                        <th style="width: 22%;">Recorded By</th>
                        <th style="width: 22%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($losses as $loss)
                        <tr>
                            <td>{{ $loss->loss_date?->format('d M Y') ?? '—' }}</td>
                            <td class="num" style="color: #b91c1c;">{{ number_format($loss->quantity_lost) }}</td>
                            <td>{{ $loss->reason ?? '—' }}</td>
                            <td>{{ $loss->recordedBy->name ?? 'System' }}</td>
                            <td>{{ $loss->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-row">No losses recorded for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($losses->count() === 25)
                <div class="truncate-note">Showing the 25 most recent loss entries only.</div>
            @endif
        </div>

        <!-- Harvest Ledger -->
        <div class="card">
            <div class="card-header">Harvest Ledger &nbsp;—&nbsp; {{ number_format($totalHarvested) }} plants
                harvested in total</div>
            <table class="ledger">
                <thead>
                    <tr>
                        <th style="width: 18%;">Date</th>
                        <th style="width: 14%;" class="num">Reported</th>
                        <th style="width: 14%;" class="num">Received</th>
                        <th style="width: 18%;">Status</th>
                        <th style="width: 18%;">Harvested By</th>
                        <th style="width: 18%;">Warehouse</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($harvests as $harvest)
                        <tr>
                            <td>{{ $harvest->harvested_on?->format('d M Y') ?? '—' }}</td>
                            <td class="num">{{ number_format($harvest->quantity_harvested) }}</td>
                            <td class="num" style="color: #047857;">
                                {{ $harvest->received_quantity !== null ? number_format($harvest->received_quantity) : '—' }}
                            </td>
                            <td>
                                <span class="badge-sm">
                                    {{ $harvest->status instanceof \App\Enums\Production\HarvestStatus ? $harvest->status->label() : ucfirst((string) $harvest->status) }}
                                </span>
                            </td>
                            <td>{{ $harvest->harvestedBy->name ?? 'System' }}</td>
                            <td>{{ $harvest->warehouse->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">No harvests recorded for this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($harvests->count() === 25)
                <div class="truncate-note">Showing the 25 most recent harvest lots only.</div>
            @endif
        </div>

        <!-- Terminal State Callout -->
        @if ($currentStatus === \App\Enums\Production\BatchStatus::Cancelled)
            <div class="callout-danger">
                <div class="danger-title">This Plant Batch Was Cancelled</div>
                <div class="danger-text">The batch is no longer active and cannot be placed, harvested or moved.</div>
            </div>
        @endif

    </div>
</body>

</html>
