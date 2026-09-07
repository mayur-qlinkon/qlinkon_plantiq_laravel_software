<x-mail.layout>
    <p style="margin:0 0 16px; font-size:15px; font-weight:800; color:#111827;">New contact inquiry</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:13px; color:#374151;">
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Name</td>
            <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $name }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Email</td>
            <td style="padding:6px 0; text-align:right;">{{ $email }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Phone</td>
            <td style="padding:6px 0; text-align:right;">{{ $phone }}</td>
        </tr>
    </table>

    <div style="margin:16px 0 0; padding:14px; background-color:#f9fafb; border-radius:8px;">
        <p style="margin:0; font-size:13px; line-height:1.6; color:#374151; white-space:pre-wrap;">{{ $message }}
        </p>
    </div>
</x-mail.layout>
