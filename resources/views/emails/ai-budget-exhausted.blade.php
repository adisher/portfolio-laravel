<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Budget Exhausted</title>
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
            background: #FEE2E2;
            border-left: 4px solid #DC2626;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #DC2626;
            font-size: 24px;
        }
        .alert-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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
        .action-button {
            display: inline-block;
            background: #10B981;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            margin-right: 10px;
        }
        .secondary-button {
            display: inline-block;
            background: #6B7280;
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
        .status-badge {
            display: inline-block;
            background: #DC2626;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛑 AI Budget Exhausted</h1>
        <span class="status-badge">Processing Paused</span>
    </div>

    <div class="alert-box">
        <p><strong>Your AI content generation has been automatically paused.</strong></p>
        <p>The monthly budget of ${{ number_format($total, 2) }} has been fully consumed. No further AI API calls will be made until you add more budget.</p>
    </div>

    <div class="stats">
        <div class="stat-row">
            <span class="stat-label">Total Spent This Month</span>
            <span class="stat-value">${{ number_format($used, 4) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Monthly Budget</span>
            <span class="stat-value">${{ number_format($total, 2) }}</span>
        </div>
        <div class="stat-row">
            <span class="stat-label">Status</span>
            <span class="stat-value" style="color: #DC2626;">Paused</span>
        </div>
    </div>

    <h3>What happens now?</h3>
    <ul>
        <li>RSS feeds will continue to be fetched</li>
        <li>Articles will be collected and scored</li>
        <li>AI content enhancement is <strong>paused</strong></li>
        <li>Auto-publishing is <strong>paused</strong></li>
    </ul>

    <h3>To resume AI processing:</h3>
    <ol>
        <li>Go to your admin dashboard</li>
        <li>Click "Add $1.00" to add additional budget</li>
        <li>AI processing will resume automatically</li>
    </ol>

    <a href="{{ url('/admin/dashboard') }}" class="action-button">
        Add Budget Now
    </a>
    <a href="{{ url('/admin/dashboard') }}" class="secondary-button">
        View Dashboard
    </a>

    <div class="footer">
        <p>This is an automated message from your blog automation system.</p>
        <p>The budget will automatically reset on the 1st of next month.</p>
    </div>
</body>
</html>
