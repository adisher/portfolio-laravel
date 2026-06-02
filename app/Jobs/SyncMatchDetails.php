<?php

namespace App\Jobs;

use App\Models\SportMatch;
use App\Services\Sports\CricbuzzApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMatchDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'sports';
    public $tries = 3;
    public $backoff = 30;

    public function __construct(
        public SportMatch $match,
    ) {}

    public function handle(CricbuzzApiService $api): void
    {
        $api->syncMatchDetails($this->match);
    }
}
