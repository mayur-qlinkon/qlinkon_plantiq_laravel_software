{{--
    Shared shell for every transactional email.

    Inline styles only, and tables for structure: Gmail strips <style> blocks
    and most clients still do not support flexbox or grid in email.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
</head>

<body
    style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">

                    {{-- Header --}}
                    <tr>
                        <td style="padding:20px 28px; border-bottom:1px solid #f3f4f6;">
                            <p style="margin:0; font-size:15px; font-weight:800; color:#111827;">
                                {{ $brandName ?? config('app.name') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 28px; background-color:#f9fafb; border-top:1px solid #f3f4f6;">
                            <p style="margin:0; font-size:11px; line-height:1.6; color:#9ca3af;">
                                This is an automated message — please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
