<?php

namespace App\Mail;

use App\Models\DemoBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoFollowUpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thanks for the demo — ' . ($this->booking->project?->title ?? 'Portfolio'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-follow-up',
        );
    }
}
