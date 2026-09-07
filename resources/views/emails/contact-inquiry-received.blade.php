<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>We've received your request</title>
</head>
<body style="margin: 0; padding: 40px 20px; background: #f3f4f6; font-family: Poppins, Arial, sans-serif">
    <div
        style="
            max-width: 480px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
        "
    >
        <div style="background: linear-gradient(135deg, #134e4a, #0f766e); padding: 32px 36px">
            <p
                style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px"
            >Qlinkon Support</p>
            <p
                style="margin: 6px 0 0; font-size: 13px; color: #99f6e4; font-weight: 400"
            >Help Center Request Received</p>
        </div>

        <div style="padding: 36px">
            <p
                style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6"
            >Hi {{ $name ?? 'there' }},</p>
            <p
                style="margin: 0 0 16px; font-size: 15px; color: #374151; line-height: 1.6"
            >Thank you for reaching out. We've received your request and our support team will get back to you as soon as possible — typically within 24 hours.</p>

            <div
                style="
                    background: #f0fdfa;
                    border: 1.5px solid #5eead4;
                    border-radius: 12px;
                    padding: 20px 24px;
                    margin: 24px 0;
                "
            >
                <p
                    style="
                        margin: 0 0 8px;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 1.5px;
                        color: #0f766e;
                    "
                >Your message</p>
                <p
                    style="margin: 0; font-size: 14px; color: #134e4a; line-height: 1.6; white-space: pre-line"
                >{{ $inquiryMessage }}</p>
            </div>

            <p
                style="margin: 0; font-size: 13px; color: #9ca3af; line-height: 1.6"
            >No action is needed from you right now. If you have more details to add, just reply to this email.</p>
        </div>

        <div style="padding: 20px 36px; border-top: 1px solid #f3f4f6; background: #fafafa">
            <p
                style="margin: 0; font-size: 11px; color: #9ca3af"
            >&copy; {{ date('Y') }} Qlinkon. This is an automated message, please do not reply directly unless you have additional details to share.</p>
        </div>
    </div>
</body>
</html>
