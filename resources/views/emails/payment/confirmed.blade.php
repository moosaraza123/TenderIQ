<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment Confirmed</title>
</head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 24px;">
  <h2 style="color: #14b8a6;">Your TenderIQ subscription is active</h2>
  <p>Hi {{ $user->name }},</p>
  <p>Thank you for subscribing. Your payment of <strong>${{ number_format($amount, 2) }}</strong> has been received and your account is now active.</p>
  <p>You can start browsing tenders right away:</p>
  <p style="margin: 24px 0;">
    <a href="{{ url('/tenders') }}" style="background:#14b8a6;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
      Browse Tenders
    </a>
  </p>
  <p style="color: #888; font-size: 13px;">
    Manage your subscription anytime at
    <a href="{{ url('/subscription/portal') }}">{{ url('/subscription/portal') }}</a>
  </p>
  <p style="color: #888; font-size: 13px;">— The TenderIQ Team</p>
</body>
</html>
