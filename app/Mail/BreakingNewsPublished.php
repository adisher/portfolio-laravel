<?php

namespace App\Mail;

use App\Models\BlogPost;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent the moment SignificanceDetector auto-publishes an article outside the
 * normal daily batch, so the fast-path stays visible instead of a post just
 * quietly appearing with no record of why it skipped the queue.
 */
class BreakingNewsPublished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BlogPost $post,
        public string $matchedEntity,
        public string $matchedTrigger,
        public string $matchedSentence,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '%s: breaking-news auto-publish — %s',
                config('app.name', 'Portfolio'),
                $this->post->title
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.breaking-news-published',
            with: [
                'post' => $this->post,
                'matchedEntity' => $this->matchedEntity,
                'matchedTrigger' => $this->matchedTrigger,
                'matchedSentence' => $this->matchedSentence,
                'postUrl' => route('blog.show', $this->post->slug),
                'editUrl' => route('admin.blog-posts.edit', $this->post),
            ],
        );
    }
}
