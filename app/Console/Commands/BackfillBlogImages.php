<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\CollectedArticle;
use App\Services\PexelsImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillBlogImages extends Command
{
    protected $signature = 'blog:backfill-images
        {--missing-only : Only process posts without a raster image}';

    protected $description = 'Source a Pexels image (16:9) for curated blog posts, with category-default fallback';

    public function handle(PexelsImageService $pexels): int
    {
        $query = BlogPost::where('source_type', 'curated');

        if ($this->option('missing-only')) {
            $query->where(function ($q) {
                $q->whereNull('featured_image')
                  ->orWhere('featured_image', '')
                  ->orWhere('featured_image', 'like', '%.svg');
            });
        }

        $posts = $query->get();
        $this->info('Posts to process: ' . $posts->count());

        $pexelsCount = 0;
        $fallbackCount = 0;
        $noneCount = 0;

        foreach ($posts as $post) {
            $article = CollectedArticle::where('blog_post_id', $post->id)->first();

            $image = null;
            if ($article) {
                $image = $pexels->fetchForArticle($article);
            }

            if (!$image) {
                $slug = $article?->assignedCategory?->slug ?? $post->category?->slug;
                if ($slug) {
                    $candidate = 'blog-defaults/' . $slug . '.svg';
                    if (Storage::disk('public')->exists($candidate)) {
                        $image = $candidate;
                    }
                }
            }

            if ($image) {
                $post->update(['featured_image' => $image]);
                if (str_ends_with($image, '.svg')) {
                    $fallbackCount++;
                    $this->line("  [fallback] {$post->id} " . \Illuminate\Support\Str::limit(trim($post->title), 45));
                } else {
                    $pexelsCount++;
                    $this->info("  [pexels]   {$post->id} " . \Illuminate\Support\Str::limit(trim($post->title), 45));
                }
            } else {
                $noneCount++;
                $this->warn("  [none]     {$post->id} " . \Illuminate\Support\Str::limit(trim($post->title), 45));
            }

            usleep(300000); // 0.3s gentle pacing for the Pexels API
        }

        $this->newLine();
        $this->info("Done. Pexels: {$pexelsCount}, fallback: {$fallbackCount}, none: {$noneCount}");
        return Command::SUCCESS;
    }
}
