<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tenders Alert</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: #14b8a6; padding: 32px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.85); margin: 8px 0 0; font-size: 14px; }
        .body { padding: 32px; }
        .tender-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .tender-number { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .tender-title { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 8px; }
        .tender-meta { font-size: 13px; color: #64748b; }
        .tender-meta span { margin-right: 16px; }
        .cta-btn { display: inline-block; background: #14b8a6; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; margin-top: 12px; }
        .footer { padding: 24px 32px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8; }
        .footer a { color: #14b8a6; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>TenderIQ</h1>
        <p>{{ $tenders->count() }} new {{ Str::plural('tender', $tenders->count()) }} match your alert</p>
    </div>
    <div class="body">
        <p style="margin-top:0;color:#334155;">Hi {{ $user->name }}, here are the latest tenders that match your saved alert:</p>

        @foreach ($tenders as $tender)
        <div class="tender-card">
            <div class="tender-number">{{ $tender->tender_number }}</div>
            <div class="tender-title">{{ $tender->title }}</div>
            <div class="tender-meta">
                <span>🏢 {{ $tender->organization_name }}</span>
                <span>📅 Closes {{ $tender->closing_at?->format('d M Y') }}</span>
                @if ($tender->city) <span>📍 {{ $tender->city }}</span> @endif
            </div>
            <a href="{{ url('/tenders/' . $tender->tender_number) }}" class="cta-btn">View Tender →</a>
        </div>
        @endforeach
    </div>
    <div class="footer">
        <p>You received this because you set up a tender alert on TenderIQ.</p>
        <p><a href="{{ $unsubscribeUrl }}">Unsubscribe from alerts</a> · <a href="{{ url('/alerts') }}">Manage alerts</a></p>
    </div>
</div>
</body>
</html>
