<?php

namespace App\Mail;

use App\Models\SocialDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/** Sent when a reel render fails, so a stuck render never goes unnoticed. */
class ReelRenderFailed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SocialDraft $draft)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reel render failed: ' . Str::limit($this->draft->blogPost->title, 70),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reel-render-failed',
            with: [
                'draft'     => $this->draft,
                'post'      => $this->draft->blogPost,
                'reviewUrl' => route('admin.social.reels.show', $this->draft),
            ],
        );
    }
}
