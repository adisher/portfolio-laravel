<?php

namespace App\Console\Commands\Demo;

use App\Mail\DemoFollowUpEmail;
use App\Models\DemoBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDemoFollowUps extends Command
{
    protected $signature   = 'demo:send-followups {--after=2 : Hours after demo to send follow-up}';
    protected $description = 'Send follow-up emails after demos complete';

    public function handle(): int
    {
        $afterHours = (int) $this->option('after');

        // Bookings whose scheduled_at is between 2-26 hours ago,
        // status is completed, and follow-up not yet sent
        $from = now()->subHours($afterHours + 24);
        $to   = now()->subHours($afterHours);

        $bookings = DemoBooking::where('status', 'completed')
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNull('follow_up_sent_at')
            ->get();

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->email)->send(new DemoFollowUpEmail($booking));
                $booking->update(['follow_up_sent_at' => now()]);
                $this->info("Sent follow-up to {$booking->email} for booking #{$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed follow-up for #{$booking->id}: " . $e->getMessage());
            }
        }

        $this->info("Done. Processed {$bookings->count()} follow-up(s).");

        return 0;
    }
}
