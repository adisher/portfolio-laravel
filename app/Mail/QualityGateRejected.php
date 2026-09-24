<?php

namespace App\Mail;

use App\Models\CollectedArticle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when the rewrite quality gate blocks a draft at publish time.
 *
 * The gate parks the article and publishes nothing, which is otherwise
 * invisible: the daily run happens on cron with nobody watching, and a day
 * with no post looks exactly like a day with no candidates. These alerts are
 * the difference between "the gate is working" and "the blog silently
 * stopped", which is precisely the failure mode that went unnoticed for weeks.
 */
class QualityGateRejected extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{score:int,hard_failures:array<string>,soft_flags:array<string>,metrics:array<string,mixed>} $verdict
     */
    public function __construct(
        public CollectedArticle $article,
        public array $verdict,
        public ?string $draftTitle = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $reason = $this->verdict['hard_failures'][0] ?? 'below pass mark';

        return new Envelope(
            subject: sprintf(
                '%s: draft rejected (%s) — %s',
                config('app.name', 'Portfolio'),
                str_replace('_', ' ', $reason),
                \Illuminate\Support\Str::limit($this->article->title, 60)
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quality-gate-rejected',
            with: [
                'article' => $this->article,
                'verdict' => $this->verdict,
                'draftTitle' => $this->draftTitle,
                'hardFailures' => $this->verdict['hard_failures'] ?? [],
                'softFlags' => $this->verdict['soft_flags'] ?? [],
                'metrics' => $this->verdict['metrics'] ?? [],
            ],
        );
    }
}
