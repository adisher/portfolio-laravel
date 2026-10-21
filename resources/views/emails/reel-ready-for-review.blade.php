<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New reel ready for review</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #41EAD4; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .cover { text-align: center; margin: 0 0 24px; }
  .cover img { width: 220px; max-width: 60%; border-radius: 12px; border: 1px solid #e5e7eb; }
  .hook { background: #0D1B2A; color: #ffffff; font-size: 18px; font-weight: 700; padding: 18px 22px; border-radius: 8px; margin: 0 0 24px; text-align: center; }
  .detail-box { background: #f0fdfa; border-left: 4px solid #41EAD4; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
  .detail-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
  .detail-row:last-child { margin-bottom: 0; }
  .detail-label { color: #9ca3af; min-width: 110px; font-weight: 500; flex-shrink: 0; }
  .detail-value { color: #111827; font-weight: 600; }
  .btn-wrap { text-align: center; margin: 28px 0 8px; }
  .btn { display: inline-block; padding: 14px 28px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>New reel ready for review</h1>
    <p>Nothing is posted until you approve it</p>
  </div>
  <div class="body">
    @if($coverUrl)
      <div class="cover"><img src="{{ $coverUrl }}" alt="Reel cover"></div>
    @endif

    @if($hook)
      <div class="hook">{{ $hook }}</div>
    @endif

    <p class="text">A reel for <strong>{{ $post->title }}</strong> has finished rendering. Watch it, adjust the captions if needed, then approve it for each platform.</p>

    <div class="detail-box">
      <div class="detail-row"><span class="detail-label">Length</span><span class="detail-value">{{ $seconds }} seconds</span></div>
      <div class="detail-row"><span class="detail-label">Scenes</span><span class="detail-value">{{ count($draft->scenes()) }}</span></div>
      <div class="detail-row"><span class="detail-label">Platforms</span><span class="detail-value">{{ $platforms }}</span></div>
    </div>

    <div class="btn-wrap"><a href="{{ $reviewUrl }}" class="btn">Watch and review</a></div>
  </div>
  <div class="footer">Sent by your site's Social Studio.</div>
</div>
</body>
</html>
