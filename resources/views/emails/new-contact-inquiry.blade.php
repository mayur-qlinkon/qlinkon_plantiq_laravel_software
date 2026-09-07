<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>New Contact Inquiry</title>
  <style>
    @media only screen and (max-width: 480px) {
      .email-outer { padding: 20px 10px !important; }
      .email-card { border-radius: 12px !important; }
      .email-header { padding: 24px 20px !important; }
      .email-body { padding: 24px 20px !important; }
      .email-footer { padding: 16px 20px !important; }
      .detail-table, .detail-table tbody, .detail-table tr {
        display: block !important;
        width: 100% !important;
      }
      .detail-label {
        display: block !important;
        width: 100% !important;
        padding: 10px 0 2px !important;
      }
      .detail-value {
        display: block !important;
        width: 100% !important;
        padding: 0 0 8px !important;
        border-bottom: 1px solid #f3f4f6;
      }
      .message-box { padding: 16px 18px !important; }
    }
  </style>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Poppins,Arial,sans-serif">
  <div class="email-outer" style="padding:40px 20px;">
  <div class="email-card" style="max-width:520px;width:100%;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07);box-sizing:border-box">

    <div class="email-header" style="background:linear-gradient(135deg,#134e4a,#0f766e);padding:32px 36px">
      <p style="margin:0;font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px">Qlinkon Platform</p>
      <p style="margin:6px 0 0;font-size:13px;color:#99f6e4;font-weight:400">New Contact Inquiry Submitted</p>
    </div>

    <div class="email-body" style="padding:36px;box-sizing:border-box">
      <p style="margin:0 0 20px;font-size:15px;color:#374151;line-height:1.6">
        A tenant has submitted a new inquiry from the Help Center.
      </p>

      <table class="detail-table" style="width:100%;border-collapse:collapse;margin-bottom:20px;table-layout:fixed">
        <tr>
          <td class="detail-label" style="padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;width:120px;vertical-align:top">Name</td>
          <td class="detail-value" style="padding:8px 0;font-size:14px;color:#111827;word-break:break-word">{{ $name ?? '—' }}</td>
        </tr>
        <tr>
          <td class="detail-label" style="padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;vertical-align:top">Email</td>
          <td class="detail-value" style="padding:8px 0;font-size:14px;color:#111827;word-break:break-word">{{ $email ?? '—' }}</td>
        </tr>
        <tr>
          <td class="detail-label" style="padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;vertical-align:top">Phone</td>
          <td class="detail-value" style="padding:8px 0;font-size:14px;color:#111827;word-break:break-word">{{ $phone ?? '—' }}</td>
        </tr>
        <tr>
          <td class="detail-label" style="padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;vertical-align:top">Company</td>
          <td class="detail-value" style="padding:8px 0;font-size:14px;color:#111827;word-break:break-word">{{ $companyName ?? '—' }}</td>
        </tr>
        <tr>
          <td class="detail-label" style="padding:8px 0;font-size:12px;font-weight:600;color:#6b7280;vertical-align:top">Submitted</td>
          <td class="detail-value" style="padding:8px 0;font-size:14px;color:#111827;word-break:break-word">{{ $submittedAt ?? '—' }}</td>
        </tr>
      </table>

      <div class="message-box" style="background:#f0fdfa;border:1.5px solid #5eead4;border-radius:12px;padding:20px 24px;box-sizing:border-box">
        <p style="margin:0 0 8px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:#0f766e">Message</p>
        <p style="margin:0;font-size:14px;color:#134e4a;line-height:1.6;white-space:pre-line;word-break:break-word">{{ $inquiryMessage }}</p>
      </div>
    </div>

    <div class="email-footer" style="padding:20px 36px;border-top:1px solid #f3f4f6;background:#fafafa;box-sizing:border-box">
      <p style="margin:0;font-size:11px;color:#9ca3af">
        &copy; {{ date('Y') }} Qlinkon Platform. Sent automatically from the Help Center contact form.
      </p>
    </div>

  </div>
  </div>
</body>
</html>