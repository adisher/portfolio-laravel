<?php

namespace Database\Seeders;

use App\Models\WorkItem;
use Illuminate\Database\Seeder;

/**
 * Clears the previously seeded (unverified) BioLink Pro voices so the list starts
 * clean. Voices are now harvested by hand from the curated Research Sources on the
 * Voices page: open a source, find a real 1-2 star review, paste the quote and a
 * screenshot, approve.
 *
 * Only deletes rows this seeder created (meta.from_seeder) and legacy migrated
 * ones (meta.migrated_from_flat). Anything you added or screenshotted yourself is
 * left untouched.
 */
class BioLinkProVoicesSeeder extends Seeder
{
    public function run(): void
    {
        $wi = WorkItem::where('name', 'BioLink Pro')->first();
        if (!$wi) {
            $this->command->warn('BioLink Pro work item not found. Skipping.');
            return;
        }

        $removed = $wi->voiceRecords()
            ->where(function ($q) {
                $q->where('meta->from_seeder', true)
                  ->orWhere('meta->migrated_from_flat', true);
            })
            ->delete();

        $this->command->info("Cleared {$removed} auto-seeded voice(s). Hand-added voices were left alone.");
        $this->command->info('Harvest new voices from the Research Sources on /admin/voices.');
    }
}
