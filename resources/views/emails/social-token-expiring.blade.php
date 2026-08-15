<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Social token expiring</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #FF6B35; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .detail-box { background: #fff7ed; border-left: 4px solid #FF6B35; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
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
    @php $expired = $expiresAt && $expiresAt->isPast(); @endphp
    <h1>{{ $expired ? 'Access token expired' : 'Access token expiring soon' }}</h1>
    <p>{{ $account->name }} ({{ ucfirst($account->platform) }})</p>
  </div>
  <div class="body">
    <p class="text">
      @if($expired)
        The access token for <strong>{{ $account->name }}</strong> has expired, so posting to this account will
        fail until you reconnect it.
      @else
        The access token for <strong>{{ $account->name }}</strong> is about to expire. Reconnect it before then so
        posting keeps working.
      @endif
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Account</span>
        <span class="detail-value">{{ $account->name }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Platform</span>
        <span class="detail-value">{{ ucfirst($account->platform) }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">{{ $expired ? 'Expired' : 'Expires' }}</span>
        <span class="detail-value">
          {{ $expiresAt ? $expiresAt->format('M j, Y') : 'Unknown' }}
          @if($expiresAt)({{ $expiresAt->diffForHumans() }})@endif
        </span>
      </div>
    </div>

    <p class="text">
      Generate a fresh token, then open the account in your admin and paste it into the token field. The reminder
      resets automatically once a new token is saved.
    </p>

    <p style="text-align:center; margin: 28px 0 8px;">
      <a href="{{ $manageUrl }}" class="btn">Manage social accounts</a>
    </p>
  </div>
  <div class="footer">
    You are receiving this because you administer {{ config('app.name', 'this site') }}.
  </div>
</div>
</body>
</html>
