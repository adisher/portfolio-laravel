<?php

namespace App\Mail;

use App\Models\DemoBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoBookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemoBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Demo is Confirmed — ' . ($this->booking->project?->title ?? 'Portfolio'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo-booking-confirmation',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn() => $this->buildIcs(),
                'demo-invite.ics'
            )->withMime('text/calendar'),
        ];
    }

    private function buildIcs(): string
    {
        $booking   = $this->booking;
        $startUtc  = $booking->scheduled_at->format('Ymd\THis\Z');
        $endUtc    = $booking->scheduled_at->copy()->addMinutes($booking->duration_minutes)->format('Ymd\THis\Z');
        $stampUtc  = now()->format('Ymd\THis\Z');
        $uid       = 'demo-' . $booking->id . '@portfolio';
        $title     = $booking->project?->title ?? 'Product Demo';
        $summary   = "Demo: {$title}";
        $organizer = config('mail.from.address');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Portfolio//Demo Booking//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$stampUtc}",
            "DTSTART:{$startUtc}",
            "DTEND:{$endUtc}",
            "SUMMARY:{$summary}",
            "ORGANIZER:mailto:{$organizer}",
            "ATTENDEE;CN={$booking->name}:mailto:{$booking->email}",
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }
}
