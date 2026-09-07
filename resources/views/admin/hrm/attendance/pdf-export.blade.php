<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Attendance Report</title>
<style>
    /* ── Base Reset & Typography ── */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; padding: 20px; }
    table { width: 100%; border-collapse: collapse; }

    /* ── Header ── (Converted to Table for PDF stability) */
    .header-table { width: 100%; border-bottom: 2px solid #1e293b; margin-bottom: 15px; padding-bottom: 12px; }
    .header-left { text-align: left; vertical-align: top; }
    .header-right { text-align: right; vertical-align: bottom; font-size: 9px; color: #94a3b8; line-height: 1.6; }
    .company-name { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
    .report-title { font-size: 11px; color: #64748b; margin-bottom: 6px; }
    .period-badge { display: inline-block; background: #1e293b; color: #fff; font-size: 9px; font-weight: 700; padding: 4px 10px; border-radius: 4px; }

    /* ── Summary Cards ── (Converted to Table for horizontal alignment) */
    .summary-container { margin-bottom: 20px; }
    .summary-table { width: 100%; table-layout: fixed; }
    .summary-table td { vertical-align: top; }
    .summary-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 5px; text-align: center; background: #fff; }
    .summary-card .num { font-size: 18px; font-weight: 700; }
    .summary-card .lbl { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-top: 4px; }
    .sc-total   .num { color: #1e293b; }
    .sc-present .num { color: #16a34a; }
    .sc-late    .num { color: #d97706; }
    .sc-absent  .num { color: #dc2626; }
    .sc-halfday .num { color: #0284c7; }
    .sc-leave   .num { color: #7c3aed; }

    /* ── Data Table ── */
    .data-table { width: 100%; font-size: 9px; margin-bottom: 20px; }
    .data-table thead tr { background: #1e293b; color: #fff; }
    .data-table thead th { padding: 8px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }
    .data-table tbody tr { border-bottom: 1px solid #e2e8f0; }
    .data-table tbody tr:nth-child(even) { background: #f8fafc; }
    .data-table tbody td { padding: 8px; vertical-align: middle; }
    .td-num { color: #94a3b8; font-weight: 700; }
    .td-emp-name { font-weight: 700; color: #1e293b; }
    .td-emp-code { font-size: 8px; color: #94a3b8; }
    .td-dept { color: #64748b; }

    /* Status badges (Added subtle borders for printers that strip backgrounds) */
    .badge { display: inline-block; padding: 3px 6px; border-radius: 4px; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
    .badge-present  { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-late     { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-absent   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-half_day { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
    .badge-on_leave { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
    .badge-holiday  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-week_off { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .override-dot { display: inline-block; width: 6px; height: 6px; background: #f59e0b; border-radius: 50%; }

    /* ── Footer ── */
    .footer-table { width: 100%; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 8px; color: #94a3b8; }
    .footer-left { text-align: left; }
    .footer-right { text-align: right; }
</style>
</head>
<body>

    {{-- ── Header ── --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name">{{ $company->name ?? 'Company' }}</div>
                <div class="report-title">Attendance Report &mdash; HRM</div>
                <div class="period-badge">{{ $periodLabel }}</div>
            </td>
            <td class="header-right">
                Generated: {{ $generatedAt }}<br>
                Total Records: {{ $summary['total'] }}
            </td>
        </tr>
    </table>

    {{-- ── Summary Cards ── --}}
    <div class="summary-container">
        <table class="summary-table">
            <tr>
                <td style="width: 16.66%; padding-right: 4px;">
                    <div class="summary-card sc-total">
                        <div class="num">{{ $summary['total'] }}</div>
                        <div class="lbl">Total</div>
                    </div>
                </td>
                <td style="width: 16.66%; padding: 0 4px;">
                    <div class="summary-card sc-present">
                        <div class="num">{{ $summary['present'] }}</div>
                        <div class="lbl">Present</div>
                    </div>
                </td>
                <td style="width: 16.66%; padding: 0 4px;">
                    <div class="summary-card sc-late">
                        <div class="num">{{ $summary['late'] }}</div>
                        <div class="lbl">Late</div>
                    </div>
                </td>
                <td style="width: 16.66%; padding: 0 4px;">
                    <div class="summary-card sc-absent">
                        <div class="num">{{ $summary['absent'] }}</div>
                        <div class="lbl">Absent</div>
                    </div>
                </td>
                <td style="width: 16.66%; padding: 0 4px;">
                    <div class="summary-card sc-halfday">
                        <div class="num">{{ $summary['half_day'] }}</div>
                        <div class="lbl">Half Day</div>
                    </div>
                </td>
                <td style="width: 16.66%; padding-left: 4px;">
                    <div class="summary-card sc-leave">
                        <div class="num">{{ $summary['on_leave'] }}</div>
                        <div class="lbl">On Leave</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Data Table ── --}}
    @if($records->isEmpty())
        <p style="text-align:center; color:#94a3b8; padding: 30px 0;">No attendance records found for this period.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:28px">#</th>
                    <th style="width:65px">Emp. Code</th>
                    <th style="width:120px">Employee Name</th>
                    <th style="width:80px">Department</th>
                    <th style="width:65px">Date</th>
                    <th style="width:40px">Day</th>
                    <th style="width:55px">Check In</th>
                    <th style="width:55px">Check Out</th>
                    <th style="width:45px">Worked</th>
                    <th style="width:45px">Overtime</th>
                    <th style="width:60px">Status</th>
                    <th style="width:35px">Method</th>
                    <th style="width:35px; text-align:center;">Override</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $i => $att)
                    @php
                        $empName = $att->employee?->user?->name ?? 'Unknown';
                    @endphp
                    <tr>
                        <td class="td-num">{{ $i + 1 }}</td>
                        <td class="td-emp-code">{{ $att->employee?->employee_code ?? '---' }}</td>
                        <td class="td-emp-name">{{ $empName }}</td>
                        <td class="td-dept">{{ $att->employee?->department?->name ?? '---' }}</td>
                        <td>{{ $att->date->format('d M Y') }}</td>
                        <td style="color:#64748b">{{ $att->date->format('D') }}</td>
                        <td>{{ $att->check_in_time?->format('h:i A') ?? '---' }}</td>
                        <td>{{ $att->check_out_time?->format('h:i A') ?? '---' }}</td>
                        <td style="font-weight:700">{{ $att->worked_hours ? number_format($att->worked_hours, 1).'h' : '---' }}</td>
                        <td style="{{ $att->overtime_hours > 0 ? 'color:#2563eb;font-weight:700' : 'color:#94a3b8' }}">
                            {{ $att->overtime_hours ? number_format($att->overtime_hours, 1).'h' : '---' }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $att->status }}">
                                {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                            </span>
                        </td>
                        <td style="color:#64748b;text-transform:uppercase;font-size:8px">
                            {{ $att->check_in_method ?? '---' }}
                        </td>
                        <td style="text-align:center">
                            @if($att->is_overridden)
                                <span class="override-dot"></span>
                            @else
                                <span style="color:#cbd5e1">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Footer ── --}}
    <table class="footer-table">
        <tr>
            <td class="footer-left">{{ $company->name ?? 'Company' }} &mdash; Attendance Report</td>
            <td class="footer-right">{{ $periodLabel }} &mdash; Generated {{ $generatedAt }}</td>
        </tr>
    </table>

</body>
</html>