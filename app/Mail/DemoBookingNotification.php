<?php

namespace App\Mail;

use App\Models\DemoBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoBookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Demo Booked — ' . $this->booking->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-booking-notification',
        );
    }
}
