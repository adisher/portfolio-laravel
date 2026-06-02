<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Demo Booking</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #FF6B35; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .detail-box { background: #f8fafc; border-left: 4px solid #FF6B35; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
  .detail-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
  .detail-row:last-child { margin-bottom: 0; }
  .detail-label { color: #9ca3af; min-width: 120px; font-weight: 500; }
  .detail-value { color: #111827; font-weight: 600; }
  .btn { display: inline-block; padding: 12px 28px; background: #FF6B35; color: #ffffff; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>📅 New Demo Booked</h1>
    <p>Someone just scheduled a demo.</p>
  </div>

  <div class="body">
    <p class="text">A new demo booking has been confirmed. Here are the details:</p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Name</span>
        <span class="detail-value">{{ $booking->name }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Email</span>
        <span class="detail-value">{{ $booking->email }}</span>
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
      <div class="detail-row">
        <span class="detail-label">Product</span>
        <span class="detail-value">{{ $booking->project?->title ?? '—' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Date & Time</span>
        <span class="detail-value">{{ $booking->scheduledAtFormatted() }}</span>
      </div>
      @if($booking->message)
      <div class="detail-row">
        <span class="detail-label">Message</span>
        <span class="detail-value">{{ $booking->message }}</span>
      </div>
      @endif
    </div>

    <a href="{{ route('admin.demo-bookings.show', $booking) }}" class="btn">View in Admin Panel</a>
  </div>

  <div class="footer">
    <p>Admin notification — {{ config('app.name') }}</p>
  </div>
</div>
</body>
</html>
