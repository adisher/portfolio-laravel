<?php

namespace App\Console\Commands;

use App\Mail\SocialTokenExpiring;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the admin once when a social account's access token is within the
 * warning window (or already expired), so it can be regenerated before posting
 * breaks. Sends a single reminder per token: token_reminded_at is stamped after
 * sending and cleared automatically when a new token is saved.
 *
 * Non-expiring tokens (Facebook, token_expires_at null) are never flagged.
 */
class SocialTokenReminders extends Command
{
    protected $signature = 'social:token-reminders
        {--days=7 : Warn when a token expires within this many days}
        {--dry-run : List accounts that would be emailed without sending}';

    protected $description = 'Email a reminder before a social access token expires';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $accounts = SocialAccount::query()
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addDays($days))
            ->whereNull('token_reminded_at')
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No tokens are near expiry. Nothing to send.');
            return self::SUCCESS;
        }

        $recipients = $this->recipients();
        if (empty($recipients)) {
            $this->error('No recipient email could be resolved (no users and no mail.from.address).');
            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $when = $account->token_expires_at->diffForHumans();

            if ($dryRun) {
                $this->line("• [dry-run] {$account->name} ({$account->platform}) expires {$when}");
                continue;
            }

            Mail::to($recipients)->send(new SocialTokenExpiring($account));
            $account->forceFill(['token_reminded_at' => now()])->save();

            $this->info("✓ Reminder sent for {$account->name} ({$account->platform}), expires {$when}");
        }

        return self::SUCCESS;
    }

    /** Admin users' emails, falling back to the configured from-address. */
    private function recipients(): array
    {
        $emails = User::query()->whereNotNull('email')->pluck('email')->all();

        if (empty($emails) && $from = config('mail.from.address')) {
            $emails = [$from];
        }

        return $emails;
    }
}
