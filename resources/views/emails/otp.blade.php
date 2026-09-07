<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Password Reset OTP</title>
</head>
<body style="margin:0;padding:40px 20px;background:#f3f4f6;font-family:Poppins,Arial,sans-serif">
  <div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07)">

    <div style="background:linear-gradient(135deg,#134e4a,#0f766e);padding:32px 36px">
      <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px">{{ $appName }}</p>
      <p style="margin:6px 0 0;font-size:13px;color:#99f6e4;font-weight:400">Password Reset Request</p>
    </div>

    <div style="padding:36px">
      <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6">
        You requested a password reset. Use the OTP below to continue.
        It will expire in <strong style="color:#0f766e">{{ $expiryMinutes }} minutes</strong>.
      </p>

      <div style="background:#f0fdfa;border:1.5px solid #5eead4;border-radius:12px;padding:24px;text-align:center;margin:24px 0">
        <p style="margin:0 0 6px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:2px;color:#0f766e">Your OTP</p>
        <p style="margin:0;font-size:40px;font-weight:800;letter-spacing:14px;color:#134e4a;font-family:monospace">{{ $otp }}</p>
      </div>

      <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.6">
        Enter this code on the password reset page. Do <strong>not</strong> share it with anyone.
      </p>
      <p style="margin:0;font-size:13px;color:#9ca3af;line-height:1.6">
        If you didn't request this, you can safely ignore this email — your password will remain unchanged.
      </p>
    </div>

    <div style="padding:20px 36px;border-top:1px solid #f3f4f6;background:#fafafa">
      <p style="margin:0;font-size:11px;color:#9ca3af">
        &copy; {{ $appName }}. This is an automated message, please do not reply.
      </p>
    </div>

  </div>
</body>
</html>