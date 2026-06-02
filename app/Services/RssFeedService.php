<?php
namespace App\Services;

use App\Models\CollectedArticle;
use App\Models\RssSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class RssFeedService
{
    protected $relevantKeywords = [
        'web development', 'frontend', 'backend', 'javascript', 'php',
        'laravel', 'react', 'vue', 'css', 'html', 'portfolio',
        'programming', 'coding', 'developer', 'design', 'ui', 'ux',
    ];

    public function fetchAllSources()
    {
        $sources        = RssSource::active()->get();
        $totalCollected = 0;

        foreach ($sources as $source) {
            if ($source->needsFetch()) {
                $collected = $this->fetchSource($source);
                $totalCollected += $collected;
            }
        }

        return $totalCollected;
    }

    public function fetchSource(RssSource $source)
    {
        try {
            $response = Http::timeout(30)->get($source->url);

            if (! $response->successful()) {
                Log::warning("Failed to fetch RSS from {$source->name}: " . $response->status());
                return 0;
            }

            $xml      = new SimpleXMLElement($response->body());
            $articles = $this->parseRssXml($xml, $source);

            $source->update(['last_fetched_at' => now()]);

            return count($articles);

        } catch (\Exception $e) {
            Log::error("Error fetching RSS from {$source->name}: " . $e->getMessage());
            return 0;
        }
    }

    protected function parseRssXml(SimpleXMLElement $xml, RssSource $source)
    {
        $articles = [];
        $items    = $xml->channel->item ?? $xml->entry ?? [];

        foreach ($items as $item) {
            $title       = (string) ($item->title ?? '');
            $url         = (string) ($item->link ?? $item->id ?? '');
            $description = (string) ($item->description ?? $item->summary ?? '');
            $author      = (string) ($item->author ?? $item->creator ?? '');

            // Parse publication date
            $pubDate     = (string) ($item->pubDate ?? $item->published ?? $item->updated ?? '');
            $publishedAt = $this->parseDate($pubDate);

            if (empty($title) || empty($url)) {
                continue;
            }

            // Check if already exists
            if (CollectedArticle::where('url', $url)->exists()) {
                continue;
            }

            // Calculate relevance score
            $relevanceScore = $this->calculateRelevanceScore($title, $description);

            $article = CollectedArticle::create([
                'rss_source_id'   => $source->id,
                'title'           => $title,
                'description'     => $description,
                'url'             => $url,
                'author'          => $author,
                'published_at'    => $publishedAt,
                'content_data'    => [
                    'raw_item'    => $item->asXML(),
                    'source_name' => $source->name,
                ],
                'relevance_score' => $relevanceScore,
                'status'          => $relevanceScore >= 70 ? 'approved' : 'pending',
            ]);

            $articles[] = $article;
        }

        return $articles;
    }

    protected function parseDate($dateString)
    {
        if (empty($dateString)) {
            return now();
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }

    protected function calculateRelevanceScore($title, $description)
    {
        $content  = strtolower($title . ' ' . $description);
        $score    = 0;
        $maxScore = 100;

        // Keyword matching (60% of score)
        $keywordMatches = 0;
        foreach ($this->relevantKeywords as $keyword) {
            if (strpos($content, strtolower($keyword)) !== false) {
                $keywordMatches++;
            }
        }
        $score += ($keywordMatches / count($this->relevantKeywords)) * 60;

        // Content length bonus (20% of score)
        $contentLength = strlen($content);
        if ($contentLength > 100) {
            $score += min(20, ($contentLength / 500) * 20);
        }

                      // Recency bonus (20% of score)
        $score += 20; // Base recency score since we're fetching recent content

        return min($maxScore, round($score, 2));
    }
}
