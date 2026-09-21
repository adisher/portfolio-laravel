<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\RewriteQualityService;
use Illuminate\Console\Command;

/**
 * Dry run of the rewrite quality gate over already-published posts.
 *
 * READ ONLY: it writes nothing. The point is to calibrate the thresholds
 * against real published content before the gate is allowed to block anything.
 * If it condemns a large share of the blog the rules are too strict; if it
 * lands near the failures we already know about (45 title passthroughs, 33
 * posts over 50% source overlap) they are about right.
 */
class ScoreRewriteQuality extends Command
{
    protected $signature = 'blog:score-rewrites
        {--limit=0 : Only score this many posts (0 = all)}
        {--failing-only : List only posts that would be blocked}
        {--show=15 : How many posts to list}
        {--ids= : Comma-separated post IDs to score instead of the whole blog}';

    protected $description = 'Dry-run the rewrite quality rules over published posts (writes nothing)';

    public function handle(RewriteQualityService $quality): int
    {
        $query = BlogPost::query()
            ->where('status', 'published')
            ->with('collectedArticle');

        if ($ids = $this->option('ids')) {
            $query->whereIn('id', array_filter(array_map('trim', explode(',', $ids))));
        }
        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $results = [];
        $noSource = 0;

        $query->chunk(100, function ($posts) use ($quality, &$results, &$noSource) {
            foreach ($posts as $post) {
                $article = $post->collectedArticle;
                if (! $article) {
                    $noSource++;
                }

                $sourceText = null;
                if ($article) {
                    $sourceText = (string) ($article->description ?? '');
                    if (is_array($article->content_data)) {
                        foreach ($article->content_data as $value) {
                            if (is_string($value)) {
                                $sourceText .= ' ' . $value;
                            }
                        }
                    }
                }

                $result = $quality->evaluate(
                    $post->title,
                    $post->content,
                    $article->title ?? null,
                    $sourceText,
                    $post->excerpt,
                    $post->original_url
                );

                $results[] = $result + ['id' => $post->id, 'title' => $post->title];
            }
        });

        if (empty($results)) {
            $this->warn('No published posts matched.');

            return self::SUCCESS;
        }

        $this->report($results, $noSource);

        return self::SUCCESS;
    }

    /** @param array<int,array<string,mixed>> $results */
    private function report(array $results, int $noSource): void
    {
        $total = count($results);
        $blocked = array_values(array_filter($results, fn ($r) => ! $r['passed']));
        $hardFailed = array_values(array_filter($results, fn ($r) => ! empty($r['hard_failures'])));

        $this->newLine();
        $this->info('DRY RUN - nothing was written.');
        $this->line('Posts scored: ' . $total . ($noSource ? "  ({$noSource} had no linked source article)" : ''));
        $this->newLine();

        $this->line('Would be BLOCKED: ' . count($blocked) . ' (' . $this->pct(count($blocked), $total) . ')');
        $this->line('  of those, hard failures: ' . count($hardFailed));
        $this->line('  of those, soft-flag score below ' . RewriteQualityService::PASS_MARK . ': '
            . (count($blocked) - count($hardFailed)));
        $this->newLine();

        $this->line('Hard failures by rule:');
        foreach ($this->tally($results, 'hard_failures') as $rule => $count) {
            $this->line('  ' . str_pad($rule, 26) . $count);
        }

        $this->newLine();
        $this->line('Soft flags by rule:');
        foreach ($this->tally($results, 'soft_flags') as $rule => $count) {
            $this->line('  ' . str_pad($rule, 26) . $count);
        }

        $this->newLine();
        $this->line('Score distribution:');
        foreach ([100, 90, 80, 70, 60, 50] as $band) {
            $n = count(array_filter($results, fn ($r) => $r['score'] === $band));
            $this->line('  ' . str_pad((string) $band, 5) . str_repeat('#', min(60, (int) ($n / 5))) . ' ' . $n);
        }
        $n = count(array_filter($results, fn ($r) => $r['score'] < 50));
        $this->line('  ' . str_pad('<50', 5) . str_repeat('#', min(60, (int) ($n / 5))) . ' ' . $n);

        $list = $this->option('failing-only') ? $blocked : $results;
        usort($list, fn ($a, $b) => [count($b['hard_failures']), -$b['score']] <=> [count($a['hard_failures']), -$a['score']]);

        $this->newLine();
        $this->line('Worst ' . min((int) $this->option('show'), count($list)) . ':');
        foreach (array_slice($list, 0, (int) $this->option('show')) as $r) {
            $reasons = implode(',', $r['hard_failures']) ?: implode(',', $r['soft_flags']);
            $this->line(sprintf(
                '  id=%-5d score=%-4d %-46s %s',
                $r['id'],
                $r['score'],
                mb_substr($r['title'], 0, 46),
                $reasons
            ));
            $this->line(sprintf(
                '        words=%s titleSim=%s%% overlap=%s headings=%s',
                $r['metrics']['word_count'],
                $r['metrics']['title_similarity'],
                $r['metrics']['source_overlap'] === null ? 'n/a' : $r['metrics']['source_overlap'] . '%',
                $r['metrics']['headings']
            ));
        }

        $this->newLine();
        $this->comment('Note: the missing-H1 rule cannot be checked here. The parser strips the "# " line,');
        $this->comment('so no stored post has one. That rule only applies live, on the raw model output.');
    }

    /**
     * @param array<int,array<string,mixed>> $results
     * @return array<string,int>
     */
    private function tally(array $results, string $key): array
    {
        $counts = [];
        foreach ($results as $r) {
            foreach ($r[$key] as $rule) {
                $counts[$rule] = ($counts[$rule] ?? 0) + 1;
            }
        }
        arsort($counts);

        return $counts ?: ['(none)' => 0];
    }

    private function pct(int $part, int $whole): string
    {
        return $whole === 0 ? '0%' : round(($part / $whole) * 100, 1) . '%';
    }
}
