<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Breaking-news auto-publish</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #41EAD4; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .detail-box { background: #ecfdf9; border-left: 4px solid #41EAD4; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
  .detail-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
  .detail-row:last-child { margin-bottom: 0; }
  .detail-label { color: #9ca3af; min-width: 100px; font-weight: 500; flex-shrink: 0; }
  .detail-value { color: #111827; font-weight: 600; }
  .sentence-box { background: #f8fafc; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; color: #374151; font-style: italic; border: 1px solid #e5e7eb; }
  .btn { display: inline-block; padding: 12px 24px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; margin: 4px 8px; }
  .btn-secondary { background: #e5e7eb; color: #111827; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Breaking news auto-published</h1>
    <p>Skipped the normal queue — published immediately</p>
  </div>
  <div class="body">
    <p class="text">
      This article matched the significance detector (a notable entity and trigger language in the same
      sentence) and was auto-approved and published right away instead of waiting for the next daily batch.
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Title</span>
        <span class="detail-value">{{ $post->title }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Category</span>
        <span class="detail-value">{{ $post->category->name ?? 'Uncategorized' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Entity</span>
        <span class="detail-value">{{ $matchedEntity }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Trigger</span>
        <span class="detail-value">{{ $matchedTrigger }}</span>
      </div>
    </div>

    <p class="text" style="margin-bottom: 8px;">Matched sentence:</p>
    <div class="sentence-box">&ldquo;{{ $matchedSentence }}&rdquo;</div>

    <p class="text">
      Worth a quick look — if this doesn't actually belong, edit or unpublish it from the admin. If it's a bad
      match (a false positive), let me know what tripped it so the vocabulary can be tightened.
    </p>

    <p style="text-align:center; margin: 28px 0 8px;">
      <a href="{{ $postUrl }}" class="btn">View live post</a>
      <a href="{{ $editUrl }}" class="btn btn-secondary">Edit in admin</a>
    </p>
  </div>
  <div class="footer">
    You are receiving this because you administer {{ config('app.name', 'this site') }}.
  </div>
</div>
</body>
</html>
