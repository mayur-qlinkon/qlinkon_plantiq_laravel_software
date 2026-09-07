<x-mail.layout :brandName="$storeName">
    <p style="margin:0 0 14px; font-size:14px; color:#111827;">Hi {{ $customerName }},</p>

    <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#374151;">
        Your appointment with {{ $storeName }} has been cancelled. The details are below
        for your reference.
    </p>

    <div style="margin:0 0 18px; padding:16px; background-color:#f9fafb; border-radius:8px;">
        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:13px; color:#6b7280;">
            <tr>
                <td style="padding:6px 0;">Reference</td>
                <td style="padding:6px 0; font-weight:700; text-align:right; text-decoration:line-through;">
                    {{ $appointmentNo }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;">Service</td>
                <td style="padding:6px 0; text-align:right; text-decoration:line-through;">{{ $serviceName }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;">Date</td>
                <td style="padding:6px 0; text-align:right; text-decoration:line-through;">{{ $appointmentDate }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;">Time slot</td>
                <td style="padding:6px 0; text-align:right; text-decoration:line-through;">{{ $slotLabel }}</td>
            </tr>
        </table>
    </div>

    @if ($adminNotes)
        <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Reason</p>
        <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#374151;">{{ $adminNotes }}</p>
    @endif

    <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
        You are welcome to book again at any time. If this cancellation was unexpected,
        please get in touch with us.
    </p>
</x-mail.layout>
