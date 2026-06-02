<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background: #f8fafc;
            padding: 30px 20px;
            border-radius: 0 0 8px 8px;
        }

        .info-row {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #3B82F6;
        }

        .label {
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 5px;
        }

        .value {
            color: #1F2937;
        }

        .message-box {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
            margin: 15px 0;
        }

        .footer {
            text-align: center;
            color: #6B7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3B82F6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📧 New Contact Form Submission</h1>
        <p>You have received a new message through your portfolio contact form</p>
    </div>

    <div class="content">
        <div class="info-row">
            <div class="label">From:</div>
            <div class="value">{{ $contact->name }}</div>
        </div>

        <div class="info-row">
            <div class="label">Email:</div>
            <div class="value">
                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
            </div>
        </div>

        @if($contact->subject)
        <div class="info-row">
            <div class="label">Subject:</div>
            <div class="value">{{ $contact->subject }}</div>
        </div>
        @endif

        <div class="info-row">
            <div class="label">Submitted:</div>
            <div class="value">{{ $contact->created_at->format('M j, Y \a\t g:i A') }}</div>
        </div>

        <div class="label" style="margin-top: 20px; margin-bottom: 10px;">Message:</div>
        <div class="message-box">
            {!! nl2br(e($contact->message)) !!}
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="mailto:{{ $contact->email }}?subject=Re: {{ $contact->subject ?: 'Your message' }}" class="btn">
                Reply to {{ $contact->name }}
            </a>
            <a href="{{ route('admin.contacts.show', $contact) }}" class="btn" style="background: #10B981;">
                View in Admin Panel
            </a>
        </div>
    </div>

    <div class="footer">
        <p>This email was automatically generated from your portfolio contact form.</p>
        <p>
            <strong>Portfolio Admin Panel:</strong>
            <a href="{{ route('admin.dashboard') }}">{{ route('admin.dashboard') }}</a>
        </p>
    </div>
</body>

</html>