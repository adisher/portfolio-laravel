<?php

namespace App\Mail;

use App\Models\AiBudgetSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiBudgetExhaustedMail extends Mailable
{
    use Queueable, SerializesModels;

    public AiBudgetSetting $settings;

    public function __construct(AiBudgetSetting $settings)
    {
        $this->settings = $settings;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🛑 AI Budget Exhausted - Processing Paused',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai-budget-exhausted',
            with: [
                'settings' => $this->settings,
                'used' => $this->settings->current_month_usage_usd,
                'total' => $this->settings->total_budget,
            ],
        );
    }
}
