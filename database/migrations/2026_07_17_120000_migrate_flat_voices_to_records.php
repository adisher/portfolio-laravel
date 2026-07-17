<?php

use App\Models\WorkItem;
use App\Models\WorkItemVoice;
use Illuminate\Database\Migrations\Migration;

/**
 * One-time: copy each work item's legacy flat `voices` JSON strings into
 * work_item_voices records (status=approved). The flat column is left in place
 * but is no longer read by the generator.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (WorkItem::whereNotNull('voices')->get() as $wi) {
            $order = 0;
            foreach ((array) $wi->voices as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }

                // Extract a "(source: X)" tail into source_url.
                $sourceUrl = null;
                if (preg_match('/\(source:\s*([^)]+)\)\s*\.?$/i', $line, $m)) {
                    $sourceUrl = trim($m[1]);
                    $line = trim(preg_replace('/\(source:\s*[^)]+\)\s*\.?$/i', '', $line));
                }

                // Skip if this exact quote already exists (idempotent-ish).
                $exists = WorkItemVoice::where('work_item_id', $wi->id)
                    ->where('quote', $line)->exists();
                if ($exists) {
                    continue;
                }

                WorkItemVoice::create([
                    'work_item_id' => $wi->id,
                    'quote'        => $line,
                    'attribution'  => null,
                    'source_url'   => $sourceUrl,
                    'status'       => 'approved',
                    'sort_order'   => $order++,
                    'meta'         => ['migrated_from_flat' => true],
                ]);
            }
        }
    }

    public function down(): void
    {
        WorkItemVoice::whereJsonContains('meta->migrated_from_flat', true)->delete();
    }
};
