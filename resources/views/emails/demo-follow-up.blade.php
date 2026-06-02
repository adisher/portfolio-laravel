<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Thanks for your time</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #41EAD4; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .greeting { font-size: 17px; color: #0D1B2A; font-weight: 600; margin-bottom: 12px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .btn { display: inline-block; padding: 12px 28px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
  .footer a { color: #6b7280; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Thanks for the Demo</h1>
    <p>Great connecting with you.</p>
  </div>

  <div class="body">
    <p class="greeting">Hi {{ $booking->name }},</p>
    <p class="text">
      Thanks for taking the time to see <strong>{{ $booking->project?->title ?? 'our product' }}</strong> in action.
      It was great connecting with you.
    </p>

    <p class="text">
      If you have any questions, want to revisit anything we discussed, or are ready to move forward — just reply to this email.
      I'm happy to help.
    </p>

    @if($booking->project)
    <p class="text" style="margin-bottom: 12px;">Want to explore further?</p>
    <a href="{{ route('products.show', $booking->project->slug) }}" class="btn">
      View {{ $booking->project->title }}
    </a>
    @endif

    <p class="text" style="margin-top: 28px; font-size: 14px;">
      Looking forward to hopefully working together.
    </p>
  </div>

  <div class="footer">
    <p><a href="{{ url('/') }}">{{ config('app.name') }}</a> · <a href="{{ route('contact') }}">Get in touch</a></p>
  </div>
</div>
</body>
</html>
