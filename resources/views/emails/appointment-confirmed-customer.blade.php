<x-mail.layout :brandName="$storeName">
    <p style="margin:0 0 14px; font-size:14px; color:#111827;">Hi {{ $customerName }},</p>

    <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#374151;">
        Good news — your appointment with {{ $storeName }} is confirmed. Here are the details.
    </p>

    <div style="margin:0 0 18px; padding:16px; background-color:#f0fdf4; border-radius:8px; border:1px solid #bbf7d0;">
        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:13px; color:#374151;">
            <tr>
                <td style="padding:6px 0; color:#6b7280;">Reference</td>
                <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $appointmentNo }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#6b7280;">Service</td>
                <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $serviceName }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#6b7280;">Date</td>
                <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $appointmentDate }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#6b7280;">Time slot</td>
                <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $slotLabel }}</td>
            </tr>
        </table>
    </div>

    @if ($address)
        <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Address</p>
        <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#374151;">{{ $address }}</p>
    @endif

    @if ($adminNotes)
        <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">A note from
            us</p>
        <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#374151;">{{ $adminNotes }}</p>
    @endif

    <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
        If you need to reschedule or cancel, please get in touch and quote reference {{ $appointmentNo }}.
    </p>
</x-mail.layout>
