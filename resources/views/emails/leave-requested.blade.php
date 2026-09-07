<x-mail.layout>
    <p style="margin:0 0 16px; font-size:15px; font-weight:800; color:#111827;">
        Leave request from {{ $employeeName }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:13px; color:#374151;">
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Type</td>
            <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $leaveType }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">From</td>
            <td style="padding:6px 0; text-align:right;">{{ $fromDate }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">To</td>
            <td style="padding:6px 0; text-align:right;">{{ $toDate }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Total days</td>
            <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $totalDays }}</td>
        </tr>
    </table>

    <div style="margin:16px 0; padding:14px; background-color:#f9fafb; border-radius:8px;">
        <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Reason</p>
        <p style="margin:0; font-size:13px; line-height:1.6; color:#374151;">{{ $reason }}</p>
    </div>

    <a href="{{ $actionUrl }}"
        style="display:inline-block; padding:10px 20px; background-color:#108c2a; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:8px;">
        Review request
    </a>
</x-mail.layout>
