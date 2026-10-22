<?php

namespace App\Console\Commands;

use App\Models\SocialDraft;
use App\Services\Social\GithubRenderClient;
use App\Services\Social\ReelDraftService;
use Illuminate\Console\Command;

/**
 * Marks reels that have been "rendering" too long as failed, which also
 * emails the owner. Without it a render job that died before it could report
 * back would leave the reel stuck on "Rendering" forever.
 */
class SocialReelsTimeout extends Command
{
    protected $signature = 'social:reels-timeout';

    protected $description = 'Fail reel renders that never reported back';

    public function handle(ReelDraftService $reels, GithubRenderClient $github): int
    {
        $minutes = (int) config('social.reels.render_timeout_minutes', 45);

        $stuck = SocialDraft::where('status', SocialDraft::STATUS_RENDERING)
            ->where('render_requested_at', '<', now()->subMinutes($minutes))
            ->get();

        foreach ($stuck as $draft) {
            $reels->markRenderFailed(
                $draft,
                "The render did not report back within {$minutes} minutes. Check the run on GitHub: {$github->runsUrl()}"
            );
            $this->line("Timed out reel #{$draft->id}");
        }

        return self::SUCCESS;
    }
}
