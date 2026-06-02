<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - {{ $order->project->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f1f5f9;
        }

        .email-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #0D1B2A, #1B3A4B);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
        }

        .header p {
            margin: 0;
            opacity: 0.7;
            font-size: 15px;
        }

        .checkmark {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: {{ $order->project->color_primary ?? '#41EAD4' }};
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .content {
            padding: 30px;
        }

        .order-details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 18px;
        }

        .order-label {
            color: #64748b;
            font-size: 14px;
        }

        .order-value {
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: {{ $order->project->color_primary ?? '#41EAD4' }};
            color: #0D1B2A !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            margin: 10px 0;
        }

        .cta-section {
            text-align: center;
            padding: 20px 0;
            margin: 20px 0;
            border-top: 1px solid #e2e8f0;
        }

        .access-link {
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 16px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #475569;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            padding: 20px 30px;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="checkmark">
                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#0D1B2A" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1>Payment Confirmed!</h1>
            <p>Thank you for purchasing {{ $order->project->title }}</p>
        </div>

        <div class="content">
            <p>Hi{{ $order->customer_name ? ' ' . $order->customer_name : '' }},</p>

            <p>Your payment has been successfully processed. Here are your order details:</p>

            <div class="order-details">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px;">Product</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-weight: 600; font-size: 14px;">{{ $order->project->title }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px;">Plan</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-weight: 600; font-size: 14px;">{{ $order->tier_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 14px;">Date</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #0f172a; font-weight: 600; font-size: 14px;">{{ $order->paid_at?->format('M d, Y') ?? now()->format('M d, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0 8px; color: #64748b; font-size: 14px;">Total</td>
                        <td style="padding: 12px 0 8px; text-align: right; color: #0f172a; font-weight: 800; font-size: 20px;">{{ $order->currency }} {{ number_format($order->amount, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="cta-section">
                <p style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">Ready to get started?</p>
                <p style="color: #64748b; font-size: 14px; margin-top: 0;">Click the button below to access your product setup page.</p>

                <a href="{{ $accessUrl }}" class="btn">
                    Get Started &rarr;
                </a>

                <p style="color: #94a3b8; font-size: 12px; margin-top: 16px;">
                    Or copy this link to your browser:
                </p>
                <div class="access-link">{{ $accessUrl }}</div>

                <p style="color: #ef4444; font-size: 12px; margin-top: 8px;">
                    <strong>Important:</strong> Save this email! The link above is your unique access to the product.
                </p>
            </div>

            <p style="color: #64748b; font-size: 14px;">
                If you have any questions or need help with setup, please don't hesitate to
                <a href="{{ route('contact') }}" style="color: {{ $order->project->color_primary ?? '#41EAD4' }};">contact us</a>.
            </p>
        </div>

        <div class="footer">
            <p>Order ID: {{ Str::limit($order->order_token, 16) }}</p>
            <p>This is an automated confirmation email. Please save it for your records.</p>
        </div>
    </div>
</body>

</html>
