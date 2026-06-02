<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Demo Reminder</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #41EAD4; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .greeting { font-size: 17px; color: #0D1B2A; font-weight: 600; margin-bottom: 12px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .highlight-box { background: #0D1B2A; border-radius: 10px; padding: 24px; margin: 24px 0; text-align: center; }
  .highlight-time { font-size: 28px; font-weight: 800; color: #41EAD4; margin: 0; }
  .highlight-label { font-size: 13px; color: rgba(224,225,221,.6); margin: 6px 0 0; }
  .cancel-link { display: block; margin-top: 24px; font-size: 13px; color: #9ca3af; text-align: center; }
  .cancel-link a { color: #6b7280; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>⏰ Demo Reminder</h1>
    <p>Your demo is {{ $hoursAhead === 1 ? 'coming up in 1 hour' : 'tomorrow' }}.</p>
  </div>

  <div class="body">
    <p class="greeting">Hi {{ $booking->name }},</p>
    <p class="text">
      Just a reminder — your demo for
      <strong>{{ $booking->project?->title ?? 'our product' }}</strong>
      is {{ $hoursAhead === 1 ? 'in about 1 hour' : 'scheduled for tomorrow' }}.
    </p>

    <div class="highlight-box">
      <p class="highlight-time">{{ $booking->scheduledAtFormatted('g:i A') }}</p>
      <p class="highlight-label">{{ $booking->scheduledAtFormatted('l, d F Y T') }}</p>
    </div>

    <p class="text">
      Duration: <strong>{{ $booking->duration_minutes }} minutes</strong>
      @if($booking->plan_interest)
        &nbsp;·&nbsp; Plan: <strong>{{ $booking->plan_interest }}</strong>
      @endif
    </p>

    <p class="text">See you soon!</p>

    <p class="cancel-link">
      Need to cancel? <a href="{{ $booking->getCancelUrl() }}">Click here</a>
    </p>
  </div>

  <div class="footer">
    <p>{{ config('app.name') }} · <a href="{{ $booking->getCancelUrl() }}">Cancel booking</a></p>
  </div>
</div>
</body>
</html>
