<?php

namespace App\Jobs;

use App\Events\ScoreUpdated;
use App\Services\Sports\CricbuzzApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchLiveScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'sports';
    public $tries = 3;
    public $backoff = 10;

    public function handle(CricbuzzApiService $api): void
    {
        $updatedMatches = $api->syncLiveScores();

        foreach ($updatedMatches as $match) {
            try {
                broadcast(new ScoreUpdated($match))->toOthers();
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast score update', [
                    'match_id' => $match->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
