<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class InterlinkAiMlPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:interlink-ai-ml {--apply : Actually write the content changes instead of just previewing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert contextual internal links between topically related AI & ML posts (one-time pass, idempotent — skips a pair once linked)';

    /**
     * Curated pairs (not auto-derived): each links FROM one post TO a
     * topically related sibling, one direction per row. Both directions
     * of a pair are listed separately so the anchor text can read naturally
     * in each post's own voice.
     *
     * Keyed by SLUG, not database ID — IDs differ between environments
     * (local dev DB vs. prod), slugs are the one thing guaranteed stable.
     */
    protected array $pairs = [
        ['introducing-gpt-4o-and-more-tools-to-chatgpt-free-users', 'our-approach-to-advertising-and-expanding-access-to-chatgpt', "I ran into the same free-tier tension while writing about [OpenAI's ad-supported free tier]({url}) — worth a read if you're weighing the same trade-off."],
        ['our-approach-to-advertising-and-expanding-access-to-chatgpt', 'introducing-gpt-4o-and-more-tools-to-chatgpt-free-users', "If you want the developer's-eye view of what actually changed when GPT-4o hit the free tier, I covered that in [Free GPT-4o Changes the Game for Developers]({url})."],
        ['gpt-51-a-smarter-more-conversational-chatgpt', 'introducing-chatgpt-go-now-available-worldwide', "The pricing side of this same shift is worth a look too — I broke it down in [The AI Price War Just Got Real]({url})."],
        ['introducing-chatgpt-go-now-available-worldwide', 'gpt-51-a-smarter-more-conversational-chatgpt', "Price isn't the only thing that changed recently — GPT-5.1's tone shift is its own story, covered in [The Tone Problem Nobody Talks About]({url})."],
        ['taisei-corporation-shapes-the-next-generation-of-talent-with-chatgpt', 'growing-impact-and-scale-with-chatgpt', "I picked this thread back up a few weeks later once the integration work actually started — see [Why I'm Taking Enterprise AI Integration Seriously Now]({url})."],
        ['growing-impact-and-scale-with-chatgpt', 'taisei-corporation-shapes-the-next-generation-of-talent-with-chatgpt', "This builds on an earlier piece where I was still working out whether enterprise AI was worth the seriousness — see [Why I'm Finally Taking Enterprise AI Seriously]({url})."],
        ['fighting-the-new-york-times-invasion-of-user-privacy', 'expanding-data-residency-access-to-business-customers-worldwide', "Data sovereignty is the other half of this problem — I wrote about it separately in [Data Sovereignty Isn't a Feature—It's Becoming Table Stakes]({url})."],
        ['expanding-data-residency-access-to-business-customers-worldwide', 'fighting-the-new-york-times-invasion-of-user-privacy', "This ties closely to a privacy-architecture problem I ran into elsewhere — see [When Your Users' Data Becomes Courtroom Evidence]({url})."],
    ];

    public function handle()
    {
        $apply = $this->option('apply');
        $linked = 0;
        $skipped = 0;

        foreach ($this->pairs as [$fromSlug, $toSlug, $template]) {
            $from = BlogPost::where('slug', $fromSlug)->first();
            $to = BlogPost::where('slug', $toSlug)->first();

            if (!$from || !$to) {
                $this->warn("SKIP {$fromSlug}->{$toSlug}: post not found on this environment");
                $skipped++;
                continue;
            }

            $url = route('blog.show', $to->slug);

            if (str_contains($from->content, $url)) {
                $this->line("SKIP {$fromSlug}->{$toSlug}: link already present");
                $skipped++;
                continue;
            }

            $sentence = str_replace(['{title}', '{url}'], [$to->title, $url], $template);

            $parts = explode("\n\n", $from->content, 3);
            if (count($parts) < 2) {
                $this->warn("SKIP {$fromSlug}->{$toSlug}: content too short to split");
                $skipped++;
                continue;
            }

            if (!$apply) {
                $this->info("WOULD LINK {$fromSlug} -> {$toSlug}");
                $linked++;
                continue;
            }

            $newContent = $parts[0] . "\n\n" . $sentence . "\n\n" . implode("\n\n", array_slice($parts, 1));
            $from->update(['content' => $newContent]);
            $this->info("LINKED {$fromSlug} -> {$toSlug}");
            $linked++;
        }

        $this->info("{$linked} link(s) " . ($apply ? 'written' : 'would be written') . ", {$skipped} skipped.");

        if (!$apply && $linked > 0) {
            $this->comment('Dry run — re-run with --apply to write these.');
        }

        return self::SUCCESS;
    }
}
