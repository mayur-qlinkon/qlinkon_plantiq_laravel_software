<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Salary Slip — {{ $salarySlip->slip_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
            background-color: #ffffff;
        }
        body {
            /* CRITICAL: DejaVu Sans is required by Dompdf to render the ₹ symbol */
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* ---- Header ---- */
        .header-table {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }
        .company-tagline {
            font-size: 11px;
            color: #666666;
        }
        .title-box {
            text-align: right;
        }
        .slip-title {
            font-size: 18px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .slip-period {
            font-size: 12px;
            color: #666666;
            margin-top: 4px;
        }
        
        /* ---- Employee Details ---- */
        .emp-details {
            border: 1px solid #d1d5db;
            margin-bottom: 20px;
        }
        .emp-details td {
            padding: 7px 10px;
            border: 1px solid #d1d5db;
        }
        .emp-label {
            background-color: #f9fafb;
            font-weight: bold;
            color: #4b5563;
            width: 20%;
            font-size: 10px;
            text-transform: uppercase;
        }
        .emp-value {
            width: 30%;
            color: #111827;
            font-size: 11px;
        }

        /* ---- Salary Components ---- */
        .salary-table {
            border: 1px solid #d1d5db;
            margin-bottom: 20px;
        }
        .salary-table th {
            background-color: #f3f4f6;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            border: 1px solid #d1d5db;
            text-align: left;
        }
        .salary-table th.amount-col {
            text-align: right;
            width: 30%;
        }
        .salary-table td {
            padding: 7px 10px;
            border-left: 1px solid #d1d5db;
            border-right: 1px solid #d1d5db;
            vertical-align: top;
        }
        .salary-table td.amount-col {
            text-align: right;
        }
        .amount-earn { color: #000; }
        .amount-ded { color: #000; }
        
        .totals-row td {
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            font-weight: bold;
            background-color: #f9fafb;
            padding: 8px 10px;
        }
        
        /* ---- Net Salary Box ---- */
        .net-salary-box {
            border: 1px solid #000;
            background-color: #000;
            color: #ffffff;
            margin-bottom: 20px;
        }
        .net-salary-box td {
            padding: 12px 15px;
        }
        .net-label {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .net-amount {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
        }

        /* ---- Footer Status ---- */
        .status-box {
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            margin-bottom: 30px;
        }
        .status-box td {
            padding: 10px 15px;
            font-size: 11px;
        }
        
        .footer {
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px dashed #d1d5db;
            padding-top: 10px;
        }
    </style>
</head>
<body>

@php
    use Illuminate\Support\Facades\Auth;
    use Carbon\Carbon;
    
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    
    // Dynamic Data Mapping
    $companyName = Auth::user()?->company?->name ?? 'Your Company Name';
    $empName     = $salarySlip->employee->user?->name ?? '—';
    $earnings    = $salarySlip->items->where('type', 'earning');
    $deductions  = $salarySlip->items->where('type', 'deduction');
    
    // Convert Net Salary to Words (e.g. "Eighty One Thousand Rupees Only")
    $netWords = '';
    if (extension_loaded('intl')) {
        $f = new \NumberFormatter("en_IN", \NumberFormatter::SPELLOUT);
        $netWords = ucwords($f->format($salarySlip->net_salary)) . ' Rupees Only';
    }
@endphp

<table class="header-table">
    <tr>
        <td style="vertical-align: top; width: 60%;">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-tagline">Payroll Department</div>
        </td>
        <td class="title-box" style="vertical-align: bottom;">
            <div class="slip-title">Payslip</div>
            <div class="slip-period">For the month of {{ $months[$salarySlip->month] ?? $salarySlip->month }} {{ $salarySlip->year }}</div>
            <div style="font-size: 10px; color: #000; margin-top: 2px;">Slip # {{ $salarySlip->slip_number }}</div>
        </td>
    </tr>
</table>

<table class="emp-details">
    <tr>
        <td class="emp-label">Employee Name</td>
        <td class="emp-value" style="font-weight:bold;">{{ $empName }}</td>
        <td class="emp-label">Bank Account</td>
        <td class="emp-value">
            @if($salarySlip->employee->bank_account_number)
                **** {{ substr($salarySlip->employee->bank_account_number, -4) }}
            @else
                —
            @endif
        </td>
    </tr>
    <tr>
        <td class="emp-label">Employee Code</td>
        <td class="emp-value">{{ $salarySlip->employee->employee_code ?? '—' }}</td>
        <td class="emp-label">PAN Number</td>
        <td class="emp-value" style="text-transform: uppercase;">{{ $salarySlip->employee->pan_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="emp-label">Designation</td>
        <td class="emp-value">{{ $salarySlip->employee->designation?->name ?? '—' }}</td>
        <td class="emp-label">UAN Number</td>
        <td class="emp-value">{{ $salarySlip->employee->uan_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="emp-label">Department</td>
        <td class="emp-value">{{ $salarySlip->employee->department?->name ?? '—' }}</td>
        <td class="emp-label">PF Number</td>
        <td class="emp-value">{{ $salarySlip->employee->pf_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="emp-label">Working Days</td>
        <td class="emp-value">{{ $salarySlip->working_days ?? '0' }}</td>
        <td class="emp-label">Paid Days</td>
        <td class="emp-value">{{ ($salarySlip->present_days ?? 0) + ($salarySlip->leave_days ?? 0) }}</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding-right: 5px;">
            <table class="salary-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Earnings</th>
                        <th class="amount-col">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($earnings as $item)
                    <tr>
                        <td>
                            {{ $item->component_name }}
                            @if($item->calculation_detail)
                                <br><span style="font-size: 9px; color: #666;">{{ $item->calculation_detail }}</span>
                            @endif
                        </td>
                        <td class="amount-col amount-earn">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="color:#9ca3af;text-align:center;padding:15px;">No earnings recorded</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td>Gross Earnings</td>
                        <td class="amount-col amount-earn">{{ number_format($salarySlip->gross_earnings, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>

        <td style="width: 50%; vertical-align: top; padding-left: 5px;">
            <table class="salary-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Deductions</th>
                        <th class="amount-col">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deductions as $item)
                    <tr>
                        <td>
                            {{ $item->component_name }}
                            @if($item->calculation_detail)
                                <br><span style="font-size: 9px; color: #666;">{{ $item->calculation_detail }}</span>
                            @endif
                        </td>
                        <td class="amount-col amount-ded">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="color:#9ca3af;text-align:center;padding:15px;">No deductions recorded</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td>Total Deductions</td>
                        <td class="amount-col amount-ded">{{ number_format($salarySlip->total_deductions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </td>
    </tr>
</table>

<table class="net-salary-box">
    <tr>
        <td style="width: 50%;">
            <div class="net-label">Net Pay</div>
            <div style="font-size: 11px; margin-top: 4px; font-style: italic;">
                {{ $netWords }}
            </div>
        </td>
        <td style="width: 50%; text-align: right;">
            <div class="net-amount">₹ {{ number_format($salarySlip->net_salary, 2) }}</div>
        </td>
    </tr>
</table>

<table class="status-box">
    <tr>
        <td style="width: 33%;">
            <strong style="color:#6b7280; text-transform:uppercase; font-size:9px;">Status</strong><br>
            @php $sc = \App\Models\Hrm\SalarySlip::STATUS_COLORS[$salarySlip->status] ?? ['text' => '#374151']; @endphp
            <span style="color:{{ $sc['text'] }}; font-weight:bold; font-size: 12px; text-transform:uppercase;">
                {{ \App\Models\Hrm\SalarySlip::STATUS_LABELS[$salarySlip->status] ?? $salarySlip->status }}
            </span>
        </td>
        <td style="width: 33%;">
            <strong style="color:#6b7280; text-transform:uppercase; font-size:9px;">Payment Method</strong><br>
            <strong style="font-size: 12px; text-transform:capitalize;">
                {{ $salarySlip->payment_label ?? '—' }}
            </strong>
        </td>
        <td style="width: 33%;">
            <strong style="color:#6b7280; text-transform:uppercase; font-size:9px;">Paid On</strong><br>
            <strong style="font-size: 12px;">
                {{ $salarySlip->payment_date ? Carbon::parse($salarySlip->payment_date)->format('d M Y') : '—' }}
            </strong>
        </td>
    </tr>
</table>

<div class="footer">
    This is a computer-generated salary slip and does not require a physical signature.<br>
    Generated on {{ now()->format('d M Y h:i A') }}
</div>

</body>
</html>