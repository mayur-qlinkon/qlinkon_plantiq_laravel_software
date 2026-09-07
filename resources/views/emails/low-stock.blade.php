<x-mail.layout>
    <p style="margin:0 0 14px; font-size:14px; color:#111827;">Hi {{ $companyName }},</p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#374151;">
        @if ($lowStockCount === 1)
            <strong>1 product</strong> has dropped to its stock alert level.
        @else
            <strong>{{ $lowStockCount }} products</strong> have dropped to their stock alert level.
        @endif
    </p>

    <table style="width:100%; border-collapse:collapse; font-size:13px; background:#ffffff; border:1px solid #e5e7eb;">
        <tr style="background-color:#f9fafb;">
            <th style="padding:8px 10px; text-align:left; font-size:11px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Product</th>
            <th style="padding:8px 10px; text-align:right; font-size:11px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">In stock</th>
            <th style="padding:8px 10px; text-align:right; font-size:11px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #e5e7eb;">Alert at</th>
        </tr>
        @foreach ($rows as $row)
            <tr>
                <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6; color:#111827;">
                    {{ $row['name'] }}
                    <span style="color:#9ca3af;">({{ $row['sku'] }})</span>
                </td>
                <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6; text-align:right; font-weight:700; color:#b45309;">
                    {{ $row['current'] }}
                </td>
                <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6; text-align:right; color:#6b7280;">
                    {{ $row['alert'] }}
                </td>
            </tr>
        @endforeach
    </table>

    @if ($hiddenCount > 0)
        <p style="margin:10px 0 0; font-size:12px; color:#6b7280;">
            and {{ $hiddenCount }} more — open the report to see the full list.
        </p>
    @endif

    <p style="margin:20px 0 0;">
        <a href="{{ $viewUrl }}"
            style="display:inline-block; padding:10px 20px; background-color:#108c2a; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:8px;">
            View low stock report
        </a>
    </p>
</x-mail.layout>