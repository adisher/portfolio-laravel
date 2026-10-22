<?php

namespace App\Services\Social;

use App\Mail\ReelReadyForReview;
use App\Mail\ReelRenderFailed;
use App\Models\BlogPost;
use App\Models\SocialDraft;
use App\Models\SocialDraftTarget;
use App\Services\AiContentService;
use App\Support\SocialUrl;
use App\Support\Text;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Lifecycle of a reel draft: AI script -> owner edits -> render -> review
 * (email alert) -> per-platform approval. Publishing lives in the platform
 * drivers; this service never posts anything.
 */
class ReelDraftService
{
    public function __construct(private AiContentService $ai) {}

    /**
     * Write a new reel script for a post and create its draft with one pending
     * target per reel platform. Returns null when the AI call fails.
     */
    public function createForPost(BlogPost $post): ?SocialDraft
    {
        $generated = $this->generate($post);
        if (! $generated) {
            return null;
        }

        $draft = SocialDraft::create([
            'blog_post_id' => $post->id,
            'kind'         => SocialDraft::KIND_REEL,
            'status'       => SocialDraft::STATUS_DRAFT,
            'script'       => [
                'scenes'         => $this->normaliseScenes($generated['scenes'] ?? []),
                'pronunciations' => $this->normalisePronunciations($generated['pronunciations'] ?? []),
            ],
            'captions'     => $this->normaliseCaptions($generated, $post),
        ]);

        foreach (SocialDraft::REEL_PLATFORMS as $platform) {
            $draft->targets()->create(['platform' => $platform, 'status' => SocialDraftTarget::STATUS_PENDING]);
        }

        return $draft->load('targets');
    }

    /** Replace the script and captions with a fresh AI version. Clears any render. */
    public function regenerate(SocialDraft $draft): bool
    {
        $generated = $this->generate($draft->blogPost);
        if (! $generated) {
            return false;
        }

        $draft->update([
            'status'   => SocialDraft::STATUS_DRAFT,
            'script'   => [
                'scenes'         => $this->normaliseScenes($generated['scenes'] ?? []),
                'pronunciations' => $this->normalisePronunciations($generated['pronunciations'] ?? []),
            ],
            'captions' => $this->normaliseCaptions($generated, $draft->blogPost),
            'error'    => null,
        ]);
        $this->resetTargets($draft);

        return true;
    }

    /**
     * Save the owner's edits. A changed script means the existing video no
     * longer matches it, so the draft drops back to "draft" (needs a render).
     */
    public function saveEdits(SocialDraft $draft, array $scenes, array $captions, ?string $pronunciationText = null): void
    {
        $scenes = $this->normaliseScenes($scenes);
        $pronunciations = $pronunciationText === null
            ? $draft->pronunciations()
            : $this->parsePronunciationText($pronunciationText);
        // Timing is derived, so compare only what the owner can edit. A new
        // pronunciation changes the voice too, so it also needs a render.
        $comparable = fn (array $list) => array_map(fn ($s) => array_diff_key($s, ['duration_seconds' => 1]), $list);
        $scriptChanged = $comparable($scenes) !== $comparable($draft->scenes())
            || $pronunciations !== $draft->pronunciations();

        $draft->update([
            'script'   => ['scenes' => $scenes, 'pronunciations' => $pronunciations],
            'captions' => $this->normaliseCaptions($captions, $draft->blogPost, appendLinks: false),
            'status'   => $scriptChanged && $draft->status !== SocialDraft::STATUS_RENDERING
                ? SocialDraft::STATUS_DRAFT
                : $draft->status,
        ]);

        if ($scriptChanged) {
            $this->resetTargets($draft);
        }
    }

    public function markRenderRequested(SocialDraft $draft): void
    {
        $draft->update([
            'status'              => SocialDraft::STATUS_RENDERING,
            'render_requested_at' => now(),
            'error'               => null,
        ]);

        // A new video needs a fresh review before anything is posted.
        $this->resetTargets($draft);
    }

    /** Called by the render upload endpoint. Sends the review email. */
    public function markRendered(SocialDraft $draft, string $videoPath, ?string $coverPath, ?int $durationSeconds): void
    {
        $draft->update([
            'status'           => SocialDraft::STATUS_READY,
            'video_path'       => $videoPath,
            'cover_path'       => $coverPath,
            'duration_seconds' => $durationSeconds,
            'rendered_at'      => now(),
            'error'            => null,
        ]);

        $this->alert(new ReelReadyForReview($draft->fresh(['blogPost', 'targets'])));
    }

    public function markRenderFailed(SocialDraft $draft, string $error, bool $notify = true): void
    {
        $draft->update([
            'status' => SocialDraft::STATUS_RENDER_FAILED,
            'error'  => Str::limit($error, 2000),
        ]);

        if ($notify) {
            $this->alert(new ReelRenderFailed($draft->fresh('blogPost')));
        }
    }

    /**
     * Ask the AI for a script; if the voiceover runs long, ask once more with
     * the exact word count so it trims. A long script means a reel well over
     * the target length, which is the most common miss.
     */
    private function generate(BlogPost $post): ?array
    {
        $generated = $this->ai->generateReelScript($post);
        if (! $generated) {
            return null;
        }

        $problems = $this->scriptProblems($generated, $post);

        if ($problems) {
            $retry = $this->ai->generateReelScript($post, implode(' ', $problems));
            if ($retry) {
                $generated = $retry;
            }
        }

        return $generated;
    }

    /**
     * What is wrong with an AI script, phrased as instructions for the retry:
     * too long, or sending viewers anywhere but the owner's own article.
     *
     * @return string[]
     */
    public function scriptProblems(array $generated, BlogPost $post): array
    {
        $scenes   = collect($generated['scenes'] ?? []);
        $problems = [];

        $max   = (int) config('social.reels.max_words', 105);
        $words = SocialDraft::countWords($scenes->pluck('voice_line')->implode(' '));
        if ($words > $max) {
            $problems[] = "Your previous script had {$words} spoken words, which runs too long. Rewrite it with at most {$max} spoken words in total. Cut points rather than cramming them.";
        }

        if ($this->promotesSource($scenes->all(), $post)) {
            $problems[] = 'Your previous script told viewers to go to the original source. Never send viewers to the source or any other website; credit it in passing at most, and end by pointing to the full article on my site (link in bio).';
        }

        return $problems;
    }

    /** Does any scene tell viewers to go and read the original source? */
    private function promotesSource(array $scenes, BlogPost $post): bool
    {
        $names = array_filter([
            $post->original_url ? preg_replace('/^www\./', '', (string) parse_url($post->original_url, PHP_URL_HOST)) : null,
            $post->original_author,
        ]);
        if (! $names) {
            return false;
        }

        $nudge = '/\b(read|reading|check|visit|head|go to|over at|find|see|full (story|article|breakdown|post))\b/i';

        foreach ($scenes as $scene) {
            $text = strtolower(($scene['voice_line'] ?? '') . ' ' . ($scene['on_screen_text'] ?? ''));
            foreach ($names as $name) {
                if (str_contains($text, strtolower($name)) && preg_match($nudge, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Approve one platform for publishing at a time (now if null). */
    public function approveTarget(SocialDraftTarget $target, ?\DateTimeInterface $when = null): void
    {
        $target->update([
            'status'        => SocialDraftTarget::STATUS_APPROVED,
            'approved_at'   => now(),
            'scheduled_for' => $when ?? now(),
            'error'         => null,
        ]);
    }

    public function rejectTarget(SocialDraftTarget $target): void
    {
        $target->update(['status' => SocialDraftTarget::STATUS_REJECTED, 'scheduled_for' => null]);
    }

    /**
     * This reel's pronunciations as [word, how to say it] pairs, from the AI's
     * [{word, say}] list or from pairs. Duplicates keep the last entry.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function normalisePronunciations(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            [$word, $say] = isset($item['word']) ? [$item['word'], $item['say'] ?? ''] : [($item[0] ?? ''), ($item[1] ?? '')];
            $word = trim((string) $word);
            $say  = trim(Text::stripEmDashes((string) $say));
            if ($word !== '' && $say !== '' && mb_strlen($word) <= 80 && mb_strlen($say) <= 120) {
                $map[mb_strtolower($word)] = [$word, $say];
            }
        }

        return array_values($map);
    }

    /** Parse "word = how to say it" lines (":" also works). */
    public function parsePronunciationText(string $text): array
    {
        $pairs = [];
        foreach (preg_split('/\R/', $text) as $line) {
            if (preg_match('/^\s*([^=]+?)\s*=\s*(.+?)\s*$/u', $line, $m)) {
                $pairs[] = [$m[1], $m[2]];
            }
        }

        return $this->normalisePronunciations($pairs);
    }

    /** Clean, clamp and house-style the scene list (AI output or form input). */
    public function normaliseScenes(array $scenes): array
    {
        $types   = ['hook', 'context', 'point', 'end'];
        $visuals = ['featured_image', 'portrait', 'screenshot', 'text', 'code'];

        return collect($scenes)
            ->filter(fn ($s) => is_array($s) && trim((string) ($s['on_screen_text'] ?? '')) . trim((string) ($s['voice_line'] ?? '')) !== '')
            ->map(function ($s) use ($types, $visuals) {
                $scene = [
                    'type'           => in_array($s['type'] ?? null, $types, true) ? $s['type'] : 'point',
                    'on_screen_text' => $this->clean($s['on_screen_text'] ?? '', 80),
                    'voice_line'     => $this->clean($s['voice_line'] ?? '', 400),
                    'visual'         => in_array($s['visual'] ?? null, $visuals, true) ? $s['visual'] : 'text',
                    'visual_note'    => $this->clean($s['visual_note'] ?? '', 600, keepNewlines: true),
                ];
                // Timing follows the spoken words, never a guessed number.
                $scene['duration_seconds'] = SocialDraft::estimateSeconds($scene);

                return $scene;
            })
            ->values()
            ->all();
    }

    /**
     * Captions for each reel platform. $appendLinks adds the tracked article
     * link to the YouTube description (only on first generation; edits keep
     * whatever the owner wrote).
     */
    public function normaliseCaptions(array $data, BlogPost $post, bool $appendLinks = true): array
    {
        $hashtags = collect($data['instagram']['hashtags'] ?? [])
            ->map(fn ($t) => preg_replace('/[^A-Za-z0-9_]/', '', (string) $t))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();

        $title = $this->clean($data['youtube']['title'] ?? $post->title, 100);
        if (! str_contains(strtolower($title), '#shorts')) {
            $title = Str::limit($title, 91, '') . ' #Shorts';
        }

        $description = $this->clean($data['youtube']['description'] ?? '', 4500, keepNewlines: true);
        if ($appendLinks) {
            $description = trim($description . "\n\nFull article: " . SocialUrl::for($post, 'youtube', SocialDraft::KIND_REEL));
        }

        return [
            'instagram' => [
                'caption'  => $this->clean($data['instagram']['caption'] ?? '', 2000, keepNewlines: true),
                'hashtags' => $hashtags,
            ],
            'youtube' => [
                'title'       => $title,
                'description' => $description,
                'tags'        => collect($data['youtube']['tags'] ?? [])
                    ->map(fn ($t) => $this->clean($t, 30))
                    ->filter()
                    ->unique()
                    ->take(15)
                    ->values()
                    ->all(),
            ],
        ];
    }

    /** Back to pending after the content changed, unless already posted. */
    private function resetTargets(SocialDraft $draft): void
    {
        $draft->targets()
            ->whereNotIn('status', [SocialDraftTarget::STATUS_POSTED])
            ->update(['status' => SocialDraftTarget::STATUS_PENDING, 'approved_at' => null, 'scheduled_for' => null]);
    }

    private function clean($text, int $limit, bool $keepNewlines = false): string
    {
        $text = (string) $text;
        // House style: no em or en dashes anywhere in social copy.
        $text = Text::stripEmDashes($text);
        $text = preg_replace('/\s*\x{2013}\s*/u', ' to ', $text);
        $text = $keepNewlines
            ? trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', $text))))
            : trim(preg_replace('/\s+/', ' ', $text));

        return Str::limit($text, $limit, '');
    }

    private function alert($mailable): void
    {
        $to = config('social.reels.alert_email');
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            // An email failure must never break the render pipeline.
            Log::error('Reel alert email failed: ' . $e->getMessage());
        }
    }
}
