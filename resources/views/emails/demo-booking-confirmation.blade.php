<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demo Confirmed</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #41EAD4; font-size: 22px; font-weight: 700; letter-spacing: -0.3px; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .greeting { font-size: 17px; color: #0D1B2A; font-weight: 600; margin-bottom: 12px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .detail-box { background: #f8fafc; border-left: 4px solid #41EAD4; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
  .detail-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
  .detail-row:last-child { margin-bottom: 0; }
  .detail-label { color: #9ca3af; min-width: 110px; font-weight: 500; }
  .detail-value { color: #111827; font-weight: 600; }
  .btn { display: inline-block; padding: 12px 28px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; margin-top: 8px; }
  .cancel-link { display: block; margin-top: 20px; font-size: 13px; color: #9ca3af; text-align: center; }
  .cancel-link a { color: #6b7280; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
  .footer a { color: #6b7280; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>✓ Demo Confirmed</h1>
    <p>Your booking is all set.</p>
  </div>

  <div class="body">
    <p class="greeting">Hi {{ $booking->name }},</p>
    <p class="text">
      Your demo for <strong>{{ $booking->project?->title ?? 'our product' }}</strong> is confirmed.
      Here are the details:
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Date & Time</span>
        <span class="detail-value">{{ $booking->scheduledAtFormatted() }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Duration</span>
        <span class="detail-value">{{ $booking->duration_minutes }} minutes</span>
      </div>
      @if($booking->company)
      <div class="detail-row">
        <span class="detail-label">Company</span>
        <span class="detail-value">{{ $booking->company }}</span>
      </div>
      @endif
      @if($booking->plan_interest)
      <div class="detail-row">
        <span class="detail-label">Plan Interest</span>
        <span class="detail-value">{{ $booking->plan_interest }}</span>
      </div>
      @endif
    </div>

    <p class="text">
      You'll receive a reminder 24 hours before and again 1 hour before the demo.
      A calendar invite (.ics) is attached to this email — add it to your calendar to be prepared.
    </p>

    <p class="text" style="margin-bottom: 8px;">Need to cancel or reschedule?</p>
    <a href="{{ $booking->getCancelUrl() }}" class="btn">Cancel Booking</a>

    <p class="text" style="margin-top: 28px; font-size: 14px;">
      Looking forward to connecting with you.
    </p>
  </div>

  <div class="footer">
    <p>You're receiving this because you booked a demo on <a href="{{ url('/') }}">{{ config('app.name') }}</a>.</p>
    <p style="margin-top:8px;"><a href="{{ $booking->getCancelUrl() }}">Cancel this booking</a></p>
  </div>
</div>
</body>
</html>
