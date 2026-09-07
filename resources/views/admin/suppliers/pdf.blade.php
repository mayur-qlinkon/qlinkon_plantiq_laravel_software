<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers Directory Report</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif; 
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
        }
        .header-table td {
            padding: 0;
            vertical-align: bottom;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-text {
            text-align: right;
            font-size: 9px;
            color: #475569;
        }
        .filter-badge {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 2px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            display: inline-block;
            margin-top: 5px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .content-table th {
            background-color: #000000;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 5px;
            border: 1px solid #000000;
            font-size: 9px;
            text-transform: uppercase;
        }
        .content-table td {
            padding: 5px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }
        .content-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; font-size: 9.5px; }
        
        footer {
            position: fixed;
            bottom: -30px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>

    <footer>
        Supplier Directory Statement — Page <span class="page-number"></span>
    </footer>

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td>
                <div class="title">Suppliers Directory</div>
                <div>
                    @if($search)
                        <span class="filter-badge">Search: "{{ $search }}"</span>
                    @endif
                    @if($status)
                        <span class="filter-badge">Status: {{ ucfirst($status) }}</span>
                    @endif
                    @if($registrationType)
                        <span class="filter-badge">Type: {{ ucfirst($registrationType) }}</span>
                    @endif
                </div>
            </td>
            <td class="meta-text">
                <strong>Generated On:</strong> {{ $generatedAt }}<br>
                <strong>Total Vendors:</strong> {{ $suppliers->count() }}
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 20%;">Supplier Details</th>
                <th style="width: 15%;">Contact Info</th>
                <th style="width: 12%;">Location</th>
                <th style="width: 13%;">GSTIN / PAN</th>
                <th style="width: 12%;">Credit Config</th>
                <th style="width: 18%;">Bank Details</th>
                <th style="width: 10%; text-align: right;">Current Bal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>
                        <strong>{{ $supplier->name }}</strong>
                        @if(!$supplier->is_active)
                            <span style="font-size:8px; font-weight:bold; text-transform:uppercase; border:1px solid #000; padding:0 2px;">(Inactive)</span>
                        @endif
                    </td>
                    <td>
                        {{ $supplier->phone ?? '—' }}<br>
                        <span style="color:#475569; font-size:9px;">{{ $supplier->email ?? '—' }}</span>
                    </td>
                    <td>
                        {{ $supplier->city ?? '—' }}
                        @if($supplier->state)
                            <br><span style="color: #64748b;">{{ $supplier->state->name }} ({{ $supplier->state->code }})</span>
                        @endif
                    </td>
                    <td>
                        <span class="font-mono">{{ $supplier->gstin ?? '—' }}</span><br>
                        <span class="font-mono" style="color:#475569;">{{ $supplier->pan ?? '—' }}</span>
                    </td>
                    <td>
                        Days: {{ $supplier->credit_days ?? 0 }}<br>
                        Limit: {{ $supplier->credit_limit > 0 ? 'Rs.; '.number_format($supplier->credit_limit, 2) : 'No Limit' }}
                    </td>
                    <td style="font-size:9px; color:#334155;">
                        @if($supplier->bank_name)
                            <strong>{{ $supplier->bank_name }}</strong><br>
                            A/C: <span class="font-mono">{{ $supplier->account_number }}</span><br>
                            IFSC: <span class="font-mono">{{ $supplier->ifsc_code }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">
                        Rs. {{ number_format($supplier->current_balance, 2) }}
                        <br><span style="font-size:8px; font-weight:normal; color:#475569; text-transform:uppercase;">{{ $supplier->balance_type }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b; font-style: italic;">
                        No supplier directory records matched the applied filter criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>