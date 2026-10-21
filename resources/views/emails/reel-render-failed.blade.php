<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reel render failed</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #FF6B35; font-size: 22px; font-weight: 700; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .error { background: #fff5f1; border-left: 4px solid #FF6B35; border-radius: 0 8px 8px 0; padding: 16px 20px; font-family: monospace; font-size: 13px; color: #7c2d12; white-space: pre-wrap; word-break: break-word; }
  .btn-wrap { text-align: center; margin: 28px 0 8px; }
  .btn { display: inline-block; padding: 14px 28px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header"><h1>Reel render failed</h1></div>
  <div class="body">
    <p class="text">The reel for <strong>{{ $post->title }}</strong> could not be rendered. Nothing was posted. You can fix the script and render it again from the review page.</p>
    <div class="error">{{ $draft->error }}</div>
    <div class="btn-wrap"><a href="{{ $reviewUrl }}" class="btn">Open the reel</a></div>
  </div>
</div>
</body>
</html>
