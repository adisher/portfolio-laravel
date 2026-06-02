<?php

namespace App\Console\Commands\Demo;

use App\Models\DemoBooking;
use Illuminate\Console\Command;

class MarkDemoNoShows extends Command
{
    protected $signature   = 'demo:mark-noshows {--grace=30 : Minutes after scheduled_at to wait before marking no-show}';
    protected $description = 'Mark confirmed bookings as no_show if past their scheduled time';

    public function handle(): int
    {
        $grace = (int) $this->option('grace');

        $bookings = DemoBooking::confirmed()
            ->where('scheduled_at', '<', now()->subMinutes($grace))
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'no_show']);
            $this->info("Marked booking #{$booking->id} ({$booking->name}) as no_show.");
        }

        $this->info("Done. Marked {$bookings->count()} booking(s) as no_show.");

        return 0;
    }
}
