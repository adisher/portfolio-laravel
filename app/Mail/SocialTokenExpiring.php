<?php

namespace App\Mail;

use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Warns the admin that a social account's access token is about to expire (or
 * already has), so they can regenerate it before posting breaks.
 */
class SocialTokenExpiring extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SocialAccount $account)
    {
    }

    public function envelope(): Envelope
    {
        $expired = $this->account->token_expires_at && $this->account->token_expires_at->isPast();

        return new Envelope(
            subject: sprintf(
                '%s: your %s access token %s',
                config('app.name', 'Portfolio'),
                $this->account->name,
                $expired ? 'has expired' : 'expires soon'
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.social-token-expiring',
            with: [
                'account'   => $this->account,
                'expiresAt' => $this->account->token_expires_at,
                'manageUrl' => route('admin.social.index'),
            ],
        );
    }
}
