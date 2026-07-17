<?php

namespace Database\Seeders;

use App\Models\WorkItem;
use Illuminate\Database\Seeder;

/**
 * One-time loader for the researched BioLink Pro user voices. Removes the
 * unverifiable legacy voices (migrated from the old flat list) and loads the
 * verified, exact-URL voices found via research. Idempotent (keyed by source_url).
 *
 * These quotes were surfaced via web search; verify each on its source page and
 * attach a screenshot before publishing. Ongoing voices are curated in the admin
 * (Find Voices / manual add), not here.
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

        // Drop the old unverifiable r/musicmarketing pricing quotes (migrated from flat).
        $wi->voiceRecords()->where('meta->migrated_from_flat', true)->delete();

        $voices = [
            [
                'quote'       => 'After read.cv, Bento is shutting down too.',
                'attribution' => 'A designer on Peerlist',
                'source_url'  => 'https://peerlist.io/scroll/post/ACTHA9EG7KRJGMEPLIK6JQGJNGA6OD',
                'status'      => 'approved',
                'note'        => 'Platform-death fatigue: one creator-profile platform after another gets shut down.',
            ],
            [
                'quote'       => "Sad to hear that @_andychung's Posts.cv and Read.cv are shutting down, but what a get for @perplexity.ai!",
                'attribution' => '@chris on Threads',
                'source_url'  => 'https://www.threads.com/@chris/post/DE8HblwS8cI',
                'status'      => 'approved',
                'note'        => 'Wistful reaction to a platform people invested in winding down.',
            ],
            [
                'quote'       => "Users help grow a product with their network, their content, or their money, get comfortable with it, integrate it into their lives... and then it's yanked out from under them.",
                'attribution' => 'A commenter on Hacker News (read.cv acquisition thread)',
                'source_url'  => 'https://news.ycombinator.com/item?id=42742241',
                'status'      => 'candidate',
                'note'        => 'VERIFY: the 12-comment thread is real and useful, but this wording is from a search summary. Open the thread, find the actual comment, use its verbatim text, and screenshot it before approving.',
            ],
        ];

        foreach ($voices as $i => $v) {
            $wi->voiceRecords()->updateOrCreate(
                ['source_url' => $v['source_url']],
                [
                    'quote'       => $v['quote'],
                    'attribution' => $v['attribution'],
                    'status'      => $v['status'],
                    'sort_order'  => $i,
                    'meta'        => ['from_seeder' => true, 'note' => $v['note']],
                ]
            );
        }

        $this->command->info('BioLink Pro voices reloaded (' . count($voices) . ' voices; old unverifiable ones removed).');
    }
}
