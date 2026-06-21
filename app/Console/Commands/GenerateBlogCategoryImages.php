<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateBlogCategoryImages extends Command
{
    protected $signature = 'blog:generate-category-images {--force : Regenerate images that already exist}';
    protected $description = 'Generate branded SVG placeholder images for each blog category';

    /**
     * Accent color per category slug (falls back to teal).
     */
    protected array $accents = [
        'ai-machine-learning' => '#41EAD4',
        'web-development'      => '#FF6B35',
        'tech-news'           => '#41EAD4',
        'programming'         => '#FF6B35',
        'design-ux'           => '#41EAD4',
        'devops-cloud'        => '#FF6B35',
        'career-growth'       => '#41EAD4',
    ];

    public function handle(): int
    {
        $categories = Category::forBlog()->get();

        if ($categories->isEmpty()) {
            $this->warn('No blog categories found.');
            return Command::SUCCESS;
        }

        $force = $this->option('force');
        $created = 0;
        $skipped = 0;

        foreach ($categories as $cat) {
            $path = 'blog-defaults/' . $cat->slug . '.svg';

            if (!$force && Storage::disk('public')->exists($path)) {
                $skipped++;
                continue;
            }

            $accent = $this->accents[$cat->slug] ?? '#41EAD4';
            Storage::disk('public')->put($path, $this->buildSvg($cat->name, $accent));
            $this->info('Generated: ' . $path);
            $created++;
        }

        $this->info("Done. Created: {$created}, skipped (already exist): {$skipped}.");
        return Command::SUCCESS;
    }

    /**
     * Build a 1200x630 branded SVG card with the category name.
     */
    protected function buildSvg(string $name, string $accent): string
    {
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0D1B2A"/>
      <stop offset="100%" stop-color="#1B3A4B"/>
    </linearGradient>
    <radialGradient id="glow" cx="80%" cy="25%" r="60%">
      <stop offset="0%" stop-color="{$accent}" stop-opacity="0.30"/>
      <stop offset="100%" stop-color="{$accent}" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <rect width="1200" height="630" fill="url(#glow)"/>
  <rect x="90" y="250" width="80" height="6" rx="3" fill="{$accent}"/>
  <text x="90" y="350" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="72" font-weight="700" fill="#E0E1DD">{$name}</text>
  <text x="90" y="410" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="30" font-weight="400" fill="{$accent}">adilsher.pro &#183; Blog</text>
</svg>
SVG;
    }
}
