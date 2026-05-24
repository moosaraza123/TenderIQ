<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Failed</title>
</head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 24px;">
  <h2 style="color: #ef4444;">Payment failed — action required</h2>
  <p>Hi {{ $user->name }},</p>
  <p>We were unable to process your latest payment. Your subscription may be paused if payment is not updated.</p>
  <p style="margin: 24px 0;">
    <a href="{{ url('/subscription/portal') }}" style="background:#ef4444;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
      Update Payment Method
    </a>
  </p>
  <p style="color: #888; font-size: 13px;">— The TenderIQ Team</p>
</body>
</html>
