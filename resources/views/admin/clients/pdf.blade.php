<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Clients Report</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            padding: 0;
            vertical-align: top;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-text {
            text-align: right;
            font-size: 10px;
            color: #718096;
        }
        .filter-badge {
            background-color: #edf2f7;
            color: #2d3748;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            display: inline-block;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .content-table th {
            background-color: #333;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            border: 1px solid #000;
            font-size: 10px;
            text-transform: uppercase;
        }
        .content-table td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }
        .content-table tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .badge-inactive {
            background-color: #fed7d7;
            color: #742a2a;
        }
        .text-muted {
            color: #a0aec0;
            font-style: italic;
        }

        /* DomPDF Footer Setup */
        footer {
            position: fixed;
            bottom: -30px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 9px;
            color: #a0aec0;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <footer>Client Directory Report — Page <span class="page-number"></span></footer>

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td>
                <div class="title">Client Directory</div>
                <div style="margin-top: 5px">
                    @if ($search)
                        <span class="filter-badge">Search: "{{ $search }}"</span>
                    @endif
                    @if ($status)
                        <span class="filter-badge">Status: {{ ucfirst($status) }}</span>
                    @endif
                    @if ($registrationType)
                        <span class="filter-badge">Type: {{ ucfirst($registrationType) }}</span>
                    @endif
                </div>
            </td>
            <td class="meta-text">
                <strong>Generated On:</strong> {{ $generatedAt }}<br />
                <strong>Total Records:</strong> {{ $clients->count() }}
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center">#</th>
                <th style="width: 25%">Client / Company</th>
                <th style="width: 20%">Contact Info</th>
                <th style="width: 15%">Location</th>
                <th style="width: 15%">Reg. Type</th>
                <th style="width: 20%">GSTIN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td style="text-align: center; color: #718096; font-weight: bold">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $client->name }}</strong>
                        @if ($client->company_name)
                            <br
                            /><span style="color: #4a5568; font-size: 10px">{{ $client->company_name }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $client->email ?? 'N/A' }}<br />
                        <span style="color: #4a5568">{{ $client->phone ?? 'N/A' }}</span>
                    </td>
                    <td>
                        {{ $client->city ?? 'N/A' }}
                        @if ($client->state)
                            <br
                            /><span style="color: #718096">{{ $client->state->name }}</span>
                        @endif
                    </td>
                    <td>
                        <span style="text-transform: uppercase; font-size: 10px">
                            {{ $client->registration_type }}
                        </span>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 10px; letter-spacing: 0.5px">
                            {{ $client->gst_number ?? '—' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px" class="text-muted">
                        No clients found matching the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
