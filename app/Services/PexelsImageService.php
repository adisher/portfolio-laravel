<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\CollectedArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class PexelsImageService
{
    protected string $apiUrl = 'https://api.pexels.com/v1/search';

    /** Canonical output dimensions (16:9). */
    protected int $targetWidth = 1280;
    protected int $targetHeight = 720;

    /**
     * Per-category base search queries (fallback when no usable tags exist).
     */
    protected array $categoryQueries = [
        'ai-machine-learning' => 'artificial intelligence technology',
        'web-development'      => 'web development code screen',
        'devops-cloud'        => 'server data center cloud computing',
        'programming'         => 'programming code laptop',
        'design-ux'           => 'ux design interface',
        'tech-news'           => 'technology',
        'career-growth'       => 'office workspace laptop',
    ];

    /**
     * Find a relevant Pexels image for a collected article, normalize to 16:9,
     * store it on the public disk, and return the storage path (or null).
     */
    public function fetchForArticle(CollectedArticle $article): ?string
    {
        return $this->searchAndStore(
            $this->buildQuery($article),
            Str::slug(Str::limit($article->title ?? 'post', 50, '')) . '-' . $article->id
        );
    }

    /**
     * Find a relevant Pexels image for a blog post (e.g. an original article
     * published with no image). Same 16:9 normalization and storage.
     */
    public function fetchForBlogPost(BlogPost $post): ?string
    {
        return $this->searchAndStore(
            $this->buildQueryForPost($post),
            Str::slug(Str::limit($post->title ?? 'post', 50, '')) . '-' . $post->id
        );
    }

    /**
     * Core: search Pexels for a query, download a landscape result, normalize,
     * store, and return the path. Returns null on any failure.
     */
    protected function searchAndStore(string $query, string $filenameBase): ?string
    {
        $key = config('services.pexels.api_key');
        if (empty($key)) {
            Log::warning('Pexels API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $key])
                ->timeout(20)
                ->get($this->apiUrl, [
                    'query'       => $query,
                    'orientation' => 'landscape',
                    'per_page'    => 15,
                    'size'        => 'large',
                ]);

            if (!$response->successful()) {
                Log::info("Pexels search failed ({$response->status()}) for query: {$query}");
                return null;
            }

            $photos = $response->json('photos') ?? [];
            if (empty($photos)) {
                Log::info("Pexels returned no photos for query: {$query}");
                return null;
            }

            // Pick randomly among returned results to avoid repeats across posts
            $photo = $photos[array_rand($photos)];
            $imageUrl = $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? null;
            if (!$imageUrl) {
                return null;
            }

            return $this->downloadAndStore($imageUrl, $filenameBase);

        } catch (Exception $e) {
            Log::info("Pexels fetch error for {$filenameBase}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build a search query from a collected article: category base query,
     * refined with a significant title keyword.
     */
    protected function buildQuery(CollectedArticle $article): string
    {
        $slug = $article->assignedCategory?->slug;
        $base = $this->categoryQueries[$slug] ?? 'technology';
        $keyword = $this->significantKeyword($article->title ?? '');

        return trim($keyword !== '' ? ($base . ' ' . $keyword) : $base);
    }

    /**
     * Build a search query from a blog post: category base query, refined with
     * a significant title keyword (same style as curated posts).
     */
    protected function buildQueryForPost(BlogPost $post): string
    {
        $slug = $post->category?->slug;
        $base = $this->categoryQueries[$slug] ?? 'technology';
        $keyword = $this->significantKeyword($post->title ?? '');

        return trim($keyword !== '' ? ($base . ' ' . $keyword) : $base);
    }

    /**
     * Pull one meaningful keyword from the title to add specificity.
     */
    protected function significantKeyword(string $title): string
    {
        $stop = ['the','and','for','with','your','that','this','from','what','when','why','how','are','our','its','into','about','more','less','just'];
        $words = preg_split('/[^a-zA-Z]+/', strtolower($title), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $w) {
            if (strlen($w) >= 5 && !in_array($w, $stop, true)) {
                return $w;
            }
        }
        return '';
    }

    /**
     * Download the image and center-crop/resize it to canonical 16:9.
     */
    protected function downloadAndStore(string $imageUrl, string $filenameBase): ?string
    {
        try {
            $resp = Http::timeout(20)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($imageUrl);
            if (!$resp->successful()) {
                return null;
            }

            $src = @imagecreatefromstring($resp->body());
            if ($src === false) {
                return null;
            }

            $normalized = $this->cropToCanonical($src);
            imagedestroy($src);

            ob_start();
            imagejpeg($normalized, null, 82);
            $jpeg = ob_get_clean();
            imagedestroy($normalized);

            $path = 'blog/' . $filenameBase . '.jpg';
            Storage::disk('public')->put($path, $jpeg);

            return $path;

        } catch (Exception $e) {
            Log::info("Pexels download error for {$filenameBase}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Center-crop a GD image to the target aspect ratio, then resize to canonical dimensions.
     */
    protected function cropToCanonical(\GdImage $src): \GdImage
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        $targetRatio = $this->targetWidth / $this->targetHeight;
        $srcRatio = $sw / $sh;

        if ($srcRatio > $targetRatio) {
            // Source wider than target: crop width
            $cropH = $sh;
            $cropW = (int) round($sh * $targetRatio);
            $cropX = (int) round(($sw - $cropW) / 2);
            $cropY = 0;
        } else {
            // Source taller than target: crop height
            $cropW = $sw;
            $cropH = (int) round($sw / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($sh - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($this->targetWidth, $this->targetHeight);
        imagecopyresampled(
            $dst, $src,
            0, 0, $cropX, $cropY,
            $this->targetWidth, $this->targetHeight,
            $cropW, $cropH
        );

        return $dst;
    }
}
