<x-mail.layout :brandName="$storeName">
    <p style="margin:0 0 14px; font-size:14px; color:#111827;">Hi {{ $customerName }},</p>

    <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
        Thank you for your interest in <strong>{{ $productName }}</strong>.
        Our team at {{ $storeName }} will get back to you shortly.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0"
        style="width:100%; background-color:#f9fafb; border-radius:8px; padding:14px; margin:0 0 14px;">
        <tr>
            <td style="font-size:13px; color:#6b7280;">Reference number</td>
            <td style="font-size:13px; font-weight:700; color:#111827; text-align:right;">{{ $orderNumber }}</td>
        </tr>
    </table>

    <p style="margin:0; font-size:13px; color:#6b7280;">Received on {{ $inquiryDate }}</p>
</x-mail.layout>
