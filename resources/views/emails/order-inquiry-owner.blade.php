<x-mail.layout :brandName="$storeName">
    <p style="margin:0 0 16px; font-size:15px; font-weight:800; color:#111827;">New inquiry received</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; font-size:13px; color:#374151;">
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Customer</td>
            <td style="padding:6px 0; font-weight:700; text-align:right;">{{ $customerName }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Phone</td>
            <td style="padding:6px 0; text-align:right;">{{ $customerPhone ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Email</td>
            <td style="padding:6px 0; text-align:right;">{{ $customerEmail ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Product</td>
            <td style="padding:6px 0; text-align:right;">{{ $productName ?: '—' }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Reference</td>
            <td style="padding:6px 0; text-align:right;">{{ $orderNumber }}</td>
        </tr>
    </table>

    @if ($message)
        {{-- Escaped by design: this text comes from a public storefront form,
             so raw HTML here would let anyone inject links into the owner's inbox. --}}
        <div style="margin:16px 0 0; padding:14px; background-color:#f9fafb; border-radius:8px;">
            <p style="margin:0 0 6px; font-size:11px; font-weight:700; color:#9ca3af; text-transform:uppercase;">Message
            </p>
            <p style="margin:0; font-size:13px; line-height:1.6; color:#374151; white-space:pre-wrap;">
                {{ $message }}</p>
        </div>
    @endif
</x-mail.layout>
