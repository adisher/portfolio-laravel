<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Message</title>
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
            background: linear-gradient(135deg, #10B981, #3B82F6);
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

        .message-summary {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #10B981;
            margin: 20px 0;
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
        <h1>✅ Message Received!</h1>
        <p>Thank you for reaching out, {{ $contact->name }}!</p>
    </div>

    <div class="content">
        <p>Hi {{ $contact->name }},</p>

        <p>Thank you for contacting me through my portfolio website. I've received your message and will get back to you
            as soon as possible, typically within 24 hours.</p>

        <div class="message-summary">
            <h3>Your Message Summary:</h3>
            <p><strong>Subject:</strong> {{ $contact->subject ?: 'General Inquiry' }}</p>
            <p><strong>Submitted:</strong> {{ $contact->created_at->format('M j, Y \a\t g:i A') }}</p>
            <p><strong>Message:</strong></p>
            <div style="background: #f9fafb; padding: 15px; border-radius: 4px; margin-top: 10px;">
                {!! nl2br(e(Str::limit($contact->message, 200))) !!}
                @if(strlen($contact->message) > 200)
                <em>... (truncated)</em>
                @endif
            </div>
        </div>

        <p>In the meantime, feel free to:</p>
        <ul>
            <li>Explore my <a href="{{ route('portfolio.index') }}">portfolio projects</a></li>
            <li>Read my latest <a href="{{ route('blog.index') }}">blog articles</a></li>
            <li>Connect with me on social media</li>
        </ul>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ route('home') }}" class="btn">
                Visit My Portfolio
            </a>
        </div>

        <p>Best regards,<br>
            <strong>Your Name</strong><br>
            Full Stack Developer
        </p>
    </div>

    <div class="footer">
        <p>This is an automated response. Please do not reply to this email.</p>
        <p>If you need immediate assistance, please call or email me directly.</p>
    </div>
</body>

</html>