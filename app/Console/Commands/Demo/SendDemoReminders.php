<?php

namespace App\Console\Commands\Demo;

use App\Mail\DemoReminderEmail;
use App\Models\DemoBooking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDemoReminders extends Command
{
    protected $signature   = 'demo:send-reminders {--hours=24 : Hours ahead to check (24 or 1)}';
    protected $description = 'Send demo reminder emails to upcoming bookings';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        if (!in_array($hours, [24, 1])) {
            $this->error('--hours must be 24 or 1.');
            return 1;
        }

        $column = $hours === 24 ? 'reminder_24h_sent_at' : 'reminder_1h_sent_at';

        // Find confirmed bookings in the target window that haven't been reminded yet
        $windowStart = now()->addHours($hours - ($hours === 1 ? 0.25 : 1));
        $windowEnd   = now()->addHours($hours + ($hours === 1 ? 0.25 : 1));

        $bookings = DemoBooking::confirmed()
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->whereNull($column)
            ->get();

        foreach ($bookings as $booking) {
            try {
                Mail::to($booking->email)->send(new DemoReminderEmail($booking, $hours));
                $booking->update([$column => now()]);
                $this->info("Sent {$hours}h reminder to {$booking->email} for booking #{$booking->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$booking->email}: " . $e->getMessage());
            }
        }

        $this->info("Done. Processed {$bookings->count()} reminder(s).");

        return 0;
    }
}
