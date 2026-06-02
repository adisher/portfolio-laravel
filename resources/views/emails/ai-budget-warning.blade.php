<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Budget Warning</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: {{ $percent >= 80 ? '#FEE2E2' : '#FEF3C7' }};
            border-left: 4px solid {{ $percent >= 80 ? '#EF4444' : '#F59E0B' }};
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: {{ $percent >= 80 ? '#DC2626' : '#D97706' }};
            font-size: 24px;
        }
        .stats {
            background: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .stat-row:last-child {
            border-bottom: none;
        }
        .stat-label {
            color: #6B7280;
        }
        .stat-value {
            font-weight: 600;
            color: #111827;
        }
        .progress-bar {
            background: #E5E7EB;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            background: {{ $percent >= 80 ? '#EF4444' : '#F59E0B' }};
            height: 100%;
            width: {{ $percent }}%;
            border-radius: 10px;
        }
        .action-button {
            display: inline-block;
            background: #3B82F6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            color: #6B7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $percent >= 80 ? '⚠️ Critical Warning' : '📊 Budget Warning' }}</h1>
        <p>Your AI budget has reached <strong>{{ $percent }}%</strong> of the monthly limit.</p>
    </div>

    <div class="stats">
        <div class="stat-row">
            <span class="stat-label">Budget Used</span>
            <span class="stat-value">${{ number_format($used, 4) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Total Budget</span>
            <span class="stat-value">${{ number_format($total, 2) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Remaining</span>
            <span class="stat-value">${{ number_format($remaining, 4) }}</span>
        </div>
    </div>

    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>

    <p>
        @if($percent >= 80)
            <strong>Action Required:</strong> Your AI processing will automatically pause when the budget is exhausted.
            Consider adding additional budget to continue uninterrupted content generation.
        @else
            This is an informational notice. Your AI processing will continue normally until the budget is exhausted.
        @endif
    </p>

    <a href="{{ url('/admin/dashboard') }}" class="action-button">
        View Dashboard
    </a>

    <div class="footer">
        <p>This is an automated message from your blog automation system.</p>
        <p>You're receiving this because you're subscribed to AI budget alerts.</p>
    </div>
</body>
</html>
