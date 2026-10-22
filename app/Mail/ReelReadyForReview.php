<?php

namespace App\Mail;

use App\Models\SocialDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sent every time a reel finishes rendering. Nothing posts without approval,
 * so this email is what turns a finished render into a reviewed post.
 */
class ReelReadyForReview extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SocialDraft $draft)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New reel ready for review: ' . Str::limit($this->draft->blogPost->title, 70),
        );
    }

    public function content(): Content
    {
        $hook = collect($this->draft->scenes())->firstWhere('type', 'hook');

        return new Content(
            view: 'emails.reel-ready-for-review',
            with: [
                'draft'     => $this->draft,
                'post'      => $this->draft->blogPost,
                'hook'      => $hook['on_screen_text'] ?? null,
                'coverUrl'  => $this->draft->coverUrl() ? url($this->draft->coverUrl()) : null,
                'reviewUrl' => route('admin.social.reels.show', $this->draft),
                'seconds'   => $this->draft->duration_seconds ?: $this->draft->plannedSeconds(),
                'platforms' => $this->draft->targets->map->label()->implode(', '),
            ],
        );
    }
}
