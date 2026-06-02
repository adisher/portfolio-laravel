<?php

namespace App\Mail;

use App\Models\AiBudgetSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiBudgetWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public AiBudgetSetting $settings;
    public int $percent;

    public function __construct(AiBudgetSetting $settings, int $percent)
    {
        $this->settings = $settings;
        $this->percent = $percent;
    }

    public function envelope(): Envelope
    {
        $subject = $this->percent >= 80
            ? '⚠️ Critical: AI Budget at ' . $this->percent . '%'
            : '📊 AI Budget Warning: ' . $this->percent . '% Used';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai-budget-warning',
            with: [
                'settings' => $this->settings,
                'percent' => $this->percent,
                'remaining' => $this->settings->remaining_budget,
                'used' => $this->settings->current_month_usage_usd,
                'total' => $this->settings->total_budget,
            ],
        );
    }
}
