<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Support\Text;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * One-off backfill: remove em dashes from existing content across the models
 * that hold author-facing copy. Dry-run by default; pass --apply to write.
 *
 * Future content is handled at save time (see BlogPost::booted); this just
 * cleans what is already stored.
 */
class StripEmDashes extends Command
{
    protected $signature = 'content:strip-em-dashes {--apply : Persist changes (otherwise dry-run)}';

    protected $description = 'Strip em dashes from existing stored content';

    /** model class => text fields to clean. */
    private const TARGETS = [
        BlogPost::class          => ['title', 'excerpt', 'content', 'meta_title', 'meta_description'],
        \App\Models\Category::class     => ['name', 'description', 'meta_title', 'meta_description'],
        \App\Models\Testimonial::class  => ['quote'],
        \App\Models\Project::class      => ['title', 'excerpt', 'description', 'meta_title', 'meta_description'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $total = 0;

        foreach (self::TARGETS as $modelClass => $fields) {
            $fields = array_values(array_filter($fields, fn ($f) => $this->hasColumn($modelClass, $f)));
            if (empty($fields)) {
                continue;
            }

            $changed = 0;

            $modelClass::query()->chunkById(200, function ($rows) use ($fields, $apply, &$changed, &$total) {
                foreach ($rows as $row) {
                    $dirty = false;

                    foreach ($fields as $field) {
                        $original = $row->{$field};
                        if (empty($original) || ! preg_match('/[\x{2014}\x{2015}]/u', $original)) {
                            continue;
                        }
                        $cleaned = Text::stripEmDashes($original);
                        if ($cleaned !== $original) {
                            $row->{$field} = $cleaned;
                            $dirty = true;
                        }
                    }

                    if ($dirty) {
                        $changed++;
                        $total++;
                        if ($apply) {
                            // saveQuietly: don't re-fire model hooks or bump timestamps.
                            $row->saveQuietly();
                        }
                    }
                }
            });

            $this->line(sprintf('%-28s %d row(s) %s', class_basename($modelClass), $changed, $apply ? 'cleaned' : 'would change'));
        }

        $this->newLine();
        $this->info($apply
            ? "Done. {$total} row(s) updated."
            : "Dry run: {$total} row(s) would change. Re-run with --apply to persist.");

        return self::SUCCESS;
    }

    private function hasColumn(string $modelClass, string $field): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        return \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), $field);
    }
}
