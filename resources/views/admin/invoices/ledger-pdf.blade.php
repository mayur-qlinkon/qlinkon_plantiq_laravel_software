<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Ledger — {{ $client->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── Layout ── */
        .page-header  { padding: 20px 24px 14px; border-bottom: 2px solid #108c2a; }
        .page-content { padding: 16px 24px 20px; }
        .page-footer  { padding: 10px 24px; border-top: 1px solid #e5e7eb; margin-top: 16px; }

        /* ── Company + Title Row ── */
        .header-grid { width: 100%; }
        .header-grid td { vertical-align: top; }
        .company-name  { font-size: 17px; font-weight: bold; color: #108c2a; }
        .company-meta  { font-size: 10px; color: #555; line-height: 1.6; margin-top: 3px; }
        .ledger-title  { font-size: 20px; font-weight: bold; color: #1a1a1a; text-align: right; text-transform: uppercase; letter-spacing: 1px; }
        .ledger-sub    { font-size: 10px; color: #777; text-align: right; margin-top: 3px; }

        /* ── Customer Info Box ── */
        .customer-box {
            background: #f8fdf9;
            border: 1px solid #d1fae5;
            border-left: 4px solid #108c2a;
            padding: 10px 14px;
            margin-bottom: 14px;
            border-radius: 4px;
        }
        .customer-box .label { font-size: 9px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .customer-box .value { font-size: 12px; font-weight: bold; color: #111; margin-top: 2px; }
        .customer-box .meta  { font-size: 10px; color: #555; margin-top: 2px; }
        .info-grid { width: 100%; }
        .info-grid td { vertical-align: top; padding-right: 12px; }

        /* ── Summary Cards ── */
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 14px; }
        .summary-card  { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; text-align: center; }
        .summary-card .card-label { font-size: 9px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-card .card-value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .card-blue   { border-top: 3px solid #3b82f6; }
        .card-green  { border-top: 3px solid #108c2a; }
        .card-orange { border-top: 3px solid #f97316; }
        .card-red    { border-top: 3px solid #ef4444; }
        .text-blue   { color: #2563eb; }
        .text-green  { color: #108c2a; }
        .text-orange { color: #ea580c; }
        .text-red    { color: #dc2626; }

        /* ── Ledger Table ── */
        .ledger-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .ledger-table thead tr { background: #108c2a; color: #fff; }
        .ledger-table thead th { padding: 8px 7px; text-align: left; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; }
        .ledger-table thead th.text-right { text-align: right; }
        .ledger-table thead th.text-center { text-align: center; }

        .ledger-table tbody tr { border-bottom: 1px solid #f0f0f0; }
        .ledger-table tbody tr.row-invoice { background: #eff6ff; }
        .ledger-table tbody tr.row-payment { background: #f0fdf4; }
        .ledger-table tbody tr.row-closing { background: #1a1a1a; color: #fff; }

        .ledger-table tbody td { padding: 6px 7px; vertical-align: top; }
        .ledger-table tbody td.text-right  { text-align: right; }
        .ledger-table tbody td.text-center { text-align: center; }

        .type-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .badge-invoice { background: #dbeafe; color: #1d4ed8; }
        .badge-payment { background: #dcfce7; color: #166534; }

        .status-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .status-paid     { background: #dcfce7; color: #166534; }
        .status-partial  { background: #dbeafe; color: #1d4ed8; }
        .status-overdue  { background: #fee2e2; color: #991b1b; }
        .status-due      { background: #fef3c7; color: #92400e; }
        .status-received { background: #dcfce7; color: #166534; }

        .ref-main { font-weight: bold; color: #111; }
        .ref-sub  { font-size: 9.5px; color: #777; margin-top: 1px; }

        .balance-dr { color: #dc2626; font-weight: bold; }
        .balance-cr { color: #108c2a; font-weight: bold; }
        .balance-nil { color: #6b7280; }

        .closing-label { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #fff; }
        .closing-value { font-size: 13px; font-weight: bold; text-align: right; color: #fff; }
        .closing-outstanding { color: #fca5a5; }
        .closing-settled     { color: #86efac; }

        /* ── Footer ── */
        .footer-text { font-size: 9.5px; color: #9ca3af; text-align: center; }
        .page-number { font-size: 9.5px; color: #9ca3af; text-align: right; }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

@php
    $company = auth()->user()->company;
    $store   = auth()->user()->company;
@endphp

{{-- ══════════════════════════════
     PAGE HEADER
══════════════════════════════ --}}
<div class="page-header">
    <table class="header-grid">
        <tr>
            <td style="width:55%">
                <div class="company-name">{{ $company->name ?? get_setting('site_name', 'Company') }}</div>
                <div class="company-meta">
                    @if($company->phone ?? get_setting('phone'))
                        Ph: {{ $company->phone ?? get_setting('phone') }}&nbsp;&nbsp;
                    @endif
                    @if($company->email ?? get_setting('email'))
                        Email: {{ $company->email ?? get_setting('email') }}
                    @endif
                    @if(get_setting('gst_number'))
                        <br>GSTIN: {{ get_setting('gst_number') }}
                    @endif
                </div>
            </td>
            <td style="width:45%">
                <div class="ledger-title">Account Ledger</div>
                <div class="ledger-sub">
                    Generated: {{ now()->format('d M Y, h:i A') }}<br>
                    @if(!empty($filters['from_date']) || !empty($filters['to_date']))
                        Period:
                        {{ !empty($filters['from_date']) ? \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') : 'Beginning' }}
                        &nbsp;to&nbsp;
                        {{ !empty($filters['to_date']) ? \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') : 'Today' }}
                    @else
                        All Transactions
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════════════════════════
     PAGE CONTENT
══════════════════════════════ --}}
<div class="page-content">

    {{-- Customer Info --}}
    <div class="customer-box">
        <table class="info-grid">
            <tr>
                <td style="width:50%">
                    <div class="label">Customer</div>
                    <div class="value">{{ $client->name }}</div>
                    <div class="meta">
                        @if($client->company_name) {{ $client->company_name }}<br> @endif
                        @if($client->phone) Ph: {{ $client->phone }}&nbsp; @endif
                        @if($client->email) &bull; {{ $client->email }} @endif
                    </div>
                </td>
                <td style="width:50%">
                    <div class="label">GSTIN / Registration</div>
                    <div class="value">{{ $client->gst_number ?: 'N/A' }}</div>
                    <div class="meta">
                        @if($client->city) {{ $client->city }}@if($client->zip_code), {{ $client->zip_code }}@endif @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Summary Cards --}}
    <div class="section-title">Summary</div>
    <table class="summary-table">
        <tr>
            <td style="width:25%">
                <div class="summary-card card-blue">
                    <div class="card-label">Total Invoiced</div>
                    <div class="card-value text-blue">&#8377;{{ number_format($summary['total_invoiced'], 2) }}</div>
                </div>
            </td>
            <td style="width:25%">
                <div class="summary-card card-green">
                    <div class="card-label">Total Received</div>
                    <div class="card-value text-green">&#8377;{{ number_format($summary['total_paid'], 2) }}</div>
                </div>
            </td>
            <td style="width:25%">
                <div class="summary-card card-orange">
                    <div class="card-label">Outstanding</div>
                    <div class="card-value {{ $summary['outstanding'] > 0 ? 'text-orange' : 'text-green' }}">
                        &#8377;{{ number_format($summary['outstanding'], 2) }}
                    </div>
                </div>
            </td>
            <td style="width:25%">
                <div class="summary-card card-red">
                    <div class="card-label">Overdue</div>
                    <div class="card-value {{ $summary['overdue'] > 0 ? 'text-red' : 'text-green' }}">
                        &#8377;{{ number_format($summary['overdue'], 2) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Ledger Table --}}
    <div class="section-title" style="margin-top:14px">Transaction Ledger</div>

    @if($entries->isEmpty())
        <p style="color:#9ca3af; font-size:11px; text-align:center; padding:20px 0;">
            No transactions found for the selected period.
        </p>
    @else
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width:62px">Date</th>
                    <th style="width:60px">Type</th>
                    <th>Reference / Details</th>
                    <th class="text-right" style="width:90px">Invoice (Dr)</th>
                    <th class="text-right" style="width:90px">Payment (Cr)</th>
                    <th class="text-right" style="width:90px">Balance</th>
                    <th class="text-center" style="width:58px">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    @php
                        $isInvoice = $entry['type'] === 'invoice';
                        $balance   = $entry['running_balance'];

                        $statusBadge = match(true) {
                            !$isInvoice => ['label' => 'Received', 'class' => 'status-received'],
                            $entry['payment_status'] === 'paid'    => ['label' => 'Paid',    'class' => 'status-paid'],
                            $entry['payment_status'] === 'partial' => ['label' => 'Partial', 'class' => 'status-partial'],
                            $entry['due_date'] && \Carbon\Carbon::parse($entry['due_date'])->isPast()
                                                                   => ['label' => 'Overdue', 'class' => 'status-overdue'],
                            default                                => ['label' => 'Due',     'class' => 'status-due'],
                        };
                    @endphp
                    <tr class="{{ $isInvoice ? 'row-invoice' : 'row-payment' }}">
                        <td>
                            <div style="font-weight:bold; white-space:nowrap">
                                {{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}
                            </div>
                            <div style="font-size:9px; color:#9ca3af">
                                {{ \Carbon\Carbon::parse($entry['date'])->format('D') }}
                            </div>
                        </td>
                        <td>
                            <span class="type-badge {{ $isInvoice ? 'badge-invoice' : 'badge-payment' }}">
                                {{ $isInvoice ? 'Invoice' : 'Payment' }}
                            </span>
                        </td>
                        <td>
                            <div class="ref-main">{{ $entry['reference'] }}</div>
                            <div class="ref-sub">
                                @if($isInvoice && $entry['due_date'])
                                    Due: {{ \Carbon\Carbon::parse($entry['due_date'])->format('d M Y') }}
                                @elseif(!$isInvoice && !empty($entry['payment_method']))
                                    via {{ $entry['payment_method'] }}
                                    @if(!empty($entry['invoice_ref'])) &bull; Against: {{ $entry['invoice_ref'] }} @endif
                                @endif
                                @if($entry['notes'])
                                    &bull; {{ \Illuminate\Support\Str::limit($entry['notes'], 45) }}
                                @endif
                            </div>
                        </td>
                        <td class="text-right">
                            @if($isInvoice)
                                <strong>&#8377;{{ number_format($entry['invoice_amount'], 2) }}</strong>
                            @else
                                <span style="color:#d1d5db">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(!$isInvoice)
                                <strong style="color:#108c2a">&#8377;{{ number_format($entry['payment_amount'], 2) }}</strong>
                            @else
                                <span style="color:#d1d5db">&mdash;</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="{{ $balance > 0 ? 'balance-dr' : ($balance < 0 ? 'balance-cr' : 'balance-nil') }}">
                                &#8377;{{ number_format(abs($balance), 2) }}
                            </span>
                            <div style="font-size:8.5px; color:{{ $balance > 0 ? '#ef4444' : ($balance < 0 ? '#108c2a' : '#9ca3af') }}">
                                {{ $balance > 0 ? 'Dr' : ($balance < 0 ? 'Cr' : '—') }}
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="status-badge {{ $statusBadge['class'] }}">
                                {{ $statusBadge['label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach

                {{-- Closing Balance --}}
                <tr class="row-closing">
                    <td colspan="3">
                        <span class="closing-label">Closing Balance</span>
                    </td>
                    <td class="text-right closing-value">
                        &#8377;{{ number_format($summary['total_invoiced'], 2) }}
                    </td>
                    <td class="text-right closing-value" style="color:#86efac">
                        &#8377;{{ number_format($summary['total_paid'], 2) }}
                    </td>
                    <td class="text-right closing-value {{ $summary['outstanding'] > 0 ? 'closing-outstanding' : 'closing-settled' }}">
                        &#8377;{{ number_format($summary['outstanding'], 2) }}
                    </td>
                    <td class="text-center">
                        @if($summary['outstanding'] <= 0)
                            <span class="status-badge" style="background:#166534; color:#fff">Settled</span>
                        @else
                            <span class="status-badge" style="background:#991b1b; color:#fff">Pending</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

</div>

{{-- ══════════════════════════════
     PAGE FOOTER
══════════════════════════════ --}}
<div class="page-footer">
    <table style="width:100%">
        <tr>
            <td class="footer-text" style="text-align:left">
                This is a computer-generated statement and does not require a signature.
            </td>
            <td class="footer-text" style="text-align:right">
                Powered by Qlinkon BIZNESS
            </td>
        </tr>
    </table>
</div>

</body>
</html>