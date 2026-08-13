<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Services\Social\SocialPublisher;
use Illuminate\Console\Command;

/**
 * Auto-posts eligible articles to every account that has auto-posting enabled.
 *
 * Eligibility per account:
 *   - article is published
 *   - article has >= account.min_human_views human views (blog_posts.views)
 *   - article has NOT already been posted to that account
 *
 * Newest eligible article first. NOTE: this command is intentionally NOT wired
 * into the scheduler (routes/console.php). It runs on demand until auto-posting
 * is switched on; each account's `auto_post_enabled` toggle gates participation.
 */
class SocialPublish extends Command
{
    protected $signature = 'social:publish
        {--limit=1 : Max articles to post per account per run}
        {--account= : Only run for this social account ID}
        {--dry-run : Show what would be posted without publishing}';

    protected $description = 'Post eligible articles to auto-enabled social accounts';

    public function handle(SocialPublisher $publisher): int
    {
        $limit  = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $accounts = SocialAccount::autoPosting()
            ->when($this->option('account'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No accounts have auto-posting enabled. Nothing to do.');
            return self::SUCCESS;
        }

        $posted = 0;

        foreach ($accounts as $account) {
            // Some platforms (LinkedIn) forbid automated posting under their API
            // terms. Never auto-post to those, even if the account has the toggle on.
            $driver = $publisher->driverFor($account);
            if (! $driver || ! $driver->allowsAutomatedPosting()) {
                $this->line("• {$account->name}: automated posting not permitted for {$account->platform} (manual only). Skipped.");
                continue;
            }

            $articles = $this->eligibleArticles($account, $limit);

            if ($articles->isEmpty()) {
                $this->line("• {$account->name}: no eligible articles.");
                continue;
            }

            foreach ($articles as $article) {
                if ($dryRun) {
                    $this->line("• [dry-run] {$account->name} ← \"{$article->title}\" ({$article->views} views)");
                    continue;
                }

                $log = $publisher->publish($account, $article, 'auto');

                if ($log->status === 'posted') {
                    $posted++;
                    $this->info("✓ {$account->name} ← \"{$article->title}\"");
                } else {
                    $this->error("✗ {$account->name} ← \"{$article->title}\": {$log->error}");
                }
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : "Done. {$posted} article(s) posted.");

        return self::SUCCESS;
    }

    /** Published articles above the account's view gate, not yet posted there. */
    private function eligibleArticles(SocialAccount $account, int $limit)
    {
        return BlogPost::published()
            ->where('views', '>=', $account->min_human_views)
            ->whereDoesntHave('socialPosts', function ($q) use ($account) {
                $q->where('social_account_id', $account->id)->where('status', 'posted');
            })
            ->with(['category', 'tags'])
            ->latest('published_at')
            ->take($limit)
            ->get();
    }
}
