<x-mail.layout>
    <p style="margin:0 0 16px; font-size:15px; font-weight:800; color:#111827;">
        New appointment from {{ $customerName }}
    </p>

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
            <td style="padding:6px 0; text-align:right;">{{ $appointmentDate }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Time slot</td>
            <td style="padding:6px 0; text-align:right;">{{ $slotLabel }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Phone</td>
            <td style="padding:6px 0; text-align:right;">{{ $customerPhone }}</td>
        </tr>
        @if ($customerEmail)
            <tr>
                <td style="padding:6px 0; color:#6b7280;">Email</td>
                <td style="padding:6px 0; text-align:right;">{{ $customerEmail }}</td>
            </tr>
        @endif
    </table>

    @if ($address)
        <div style="margin:16px 0 0; padding:14px; background-color:#f9fafb; border-radius:8px;">
            <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Address
            </p>
            <p style="margin:0; font-size:13px; line-height:1.6; color:#374151;">{{ $address }}</p>
        </div>
    @endif

    @if ($notes)
        <div style="margin:16px 0 0; padding:14px; background-color:#f9fafb; border-radius:8px;">
            <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Notes
            </p>
            <p style="margin:0; font-size:13px; line-height:1.6; color:#374151;">{{ $notes }}</p>
        </div>
    @endif

    <p style="margin:20px 0 0;">
        <a href="{{ $actionUrl }}"
            style="display:inline-block; padding:10px 20px; background-color:#108c2a; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:8px;">
            View appointment
        </a>
    </p>
</x-mail.layout>
