<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Draft rejected by the quality gate</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  .header { background: #0D1B2A; padding: 36px 40px; text-align: center; }
  .header h1 { margin: 0; color: #FF6B35; font-size: 22px; font-weight: 700; }
  .header p { margin: 6px 0 0; color: rgba(224,225,221,.6); font-size: 14px; }
  .body { padding: 40px; }
  .text { font-size: 15px; color: #4b5563; line-height: 1.7; margin-bottom: 20px; }
  .detail-box { background: #fff5f1; border-left: 4px solid #FF6B35; border-radius: 0 8px 8px 0; padding: 20px 24px; margin: 24px 0; }
  .detail-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
  .detail-row:last-child { margin-bottom: 0; }
  .detail-label { color: #9ca3af; min-width: 110px; font-weight: 500; flex-shrink: 0; }
  .detail-value { color: #111827; font-weight: 600; }
  .reason { display: inline-block; background: #FF6B35; color: #ffffff; font-size: 13px; font-weight: 700; padding: 4px 10px; border-radius: 6px; margin: 0 6px 6px 0; }
  .reason-soft { background: #e5e7eb; color: #374151; }
  .sentence-box { background: #f8fafc; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; color: #374151; border: 1px solid #e5e7eb; }
  .btn { display: inline-block; padding: 12px 24px; background: #41EAD4; color: #0D1B2A; font-weight: 700; font-size: 15px; text-decoration: none; border-radius: 8px; margin: 4px 8px; }
  .footer { background: #f8fafc; padding: 24px 40px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Draft rejected, nothing published</h1>
    <p>The rewrite failed the quality gate and the article was parked</p>
  </div>
  <div class="body">
    <p class="text">
      An article cleared the source checks and was rewritten, but the finished draft did not pass the quality
      gate, so it was <strong>not published</strong>. The article is parked: it stays available as source
      material and will not be retried.
    </p>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">Source article</span>
        <span class="detail-value">{{ $article->title }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Draft headline</span>
        <span class="detail-value">{{ $draftTitle ?: 'none produced' }}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Quality score</span>
        <span class="detail-value">{{ $verdict['score'] ?? 0 }} / 100</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Source</span>
        <span class="detail-value">{{ $article->rssSource?->name ?? 'unknown' }}</span>
      </div>
    </div>

    @if (! empty($hardFailures))
      <p class="text" style="margin-bottom: 8px;">Hard failures:</p>
      <p style="margin: 0 0 20px;">
        @foreach ($hardFailures as $failure)
          <span class="reason">{{ str_replace('_', ' ', $failure) }}</span>
        @endforeach
      </p>
    @endif

    @if (! empty($softFlags))
      <p class="text" style="margin-bottom: 8px;">Soft flags (10 points each):</p>
      <p style="margin: 0 0 20px;">
        @foreach ($softFlags as $flag)
          <span class="reason reason-soft">{{ str_replace('_', ' ', $flag) }}</span>
        @endforeach
      </p>
    @endif

    @if (! empty($metrics))
      <div class="sentence-box">
        Words: {{ $metrics['word_count'] ?? 'n/a' }} &middot;
        Title similarity to source: {{ $metrics['title_similarity'] ?? 'n/a' }}% &middot;
        Source overlap: {{ $metrics['source_overlap'] === null ? 'n/a' : ($metrics['source_overlap'] ?? 'n/a') . '%' }} &middot;
        Sections: {{ $metrics['headings'] ?? 0 }}
      </div>
    @endif

    <p class="text">
      One rejection is normal and means the gate is doing its job. Several in a row from the same source, or the
      same failure repeating, usually points at the prompt or the thresholds rather than the article.
    </p>

    <p style="text-align:center; margin: 28px 0 8px;">
      <a href="{{ $article->url }}" class="btn">View the original article</a>
    </p>
  </div>
  <div class="footer">
    You are receiving this because you administer {{ config('app.name', 'this site') }}.
  </div>
</div>
</body>
</html>
