<?php

namespace App\Mail;

use App\Models\DemoBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DemoBooking $booking,
        public int $hoursAhead // 24 or 1
    ) {
    }

    public function envelope(): Envelope
    {
        $when = $this->hoursAhead === 1 ? 'in 1 hour' : 'tomorrow';
        return new Envelope(
            subject: "Reminder: Your Demo is {$when} — " . ($this->booking->project?->title ?? 'Portfolio'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-reminder',
        );
    }
}
