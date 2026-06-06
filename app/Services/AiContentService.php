<?php

namespace App\Services;

use App\Models\CollectedArticle;
use App\Models\AutoPublishSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AiContentService
{
    protected AiBudgetService $budgetService;
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(AiBudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
        $this->apiKey = config('blog_automation.ai.api_key', env('ANTHROPIC_API_KEY'));
        $this->model = config('blog_automation.ai.model', 'claude-3-5-haiku-20241022');
    }

    /**
     * Fetch full article text from the original URL.
     * Falls back to the RSS description if fetching fails.
     */
    protected function fetchFullContent(CollectedArticle $article): string
    {
        if (empty($article->url)) {
            return $article->description ?? '';
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Portfolio-Bot/1.0)'])
                ->get($article->url);

            if (!$response->successful()) {
                return $article->description ?? '';
            }

            $html = $response->body();

            // Strip scripts, styles, nav, footer, header, aside
            $html = preg_replace('/<(script|style|nav|footer|header|aside|form|iframe)[^>]*>.*?<\/\1>/si', '', $html);

            // Strip all remaining HTML tags
            $text = strip_tags($html);

            // Collapse whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            // Limit to ~6000 chars to stay within token budget
            if (strlen($text) > 6000) {
                $text = substr($text, 0, 6000) . '...';
            }

            return strlen($text) > 200 ? $text : ($article->description ?? '');

        } catch (Exception $e) {
            Log::info("Could not fetch full content for article {$article->id}: " . $e->getMessage());
            return $article->description ?? '';
        }
    }

    /**
     * Transform a collected article into blog post content.
     */
    public function transformArticle(CollectedArticle $article): ?array
    {
        // Check budget
        if (!$this->budgetService->canMakeApiCall()) {
            Log::warning('AI content transformation skipped: budget exhausted or paused');
            return null;
        }

        $settings = AutoPublishSetting::getInstance();

        // Fetch the full article content from the original URL
        $fullContent = $this->fetchFullContent($article);
        $prompt = $this->buildTransformationPrompt($article, $settings, $fullContent);

        try {
            $response = $this->callApi($prompt);

            if (!$response) {
                return null;
            }

            // Parse the response
            $content = $this->parseTransformationResponse($response['content'], $article);

            // Log usage
            $this->budgetService->logUsage(
                'content_transform',
                $response['input_tokens'],
                $response['output_tokens'],
                $article->id,
                null,
                ['prompt_length' => strlen($prompt)],
                ['content_length' => strlen($content['content'] ?? '')],
                true
            );

            // Mark article as AI enhanced
            $article->update([
                'ai_enhanced' => true,
                'ai_generated_content' => $content,
            ]);

            return $content;

        } catch (Exception $e) {
            Log::error('AI content transformation failed: ' . $e->getMessage());

            $this->budgetService->logUsage(
                'content_transform',
                0,
                0,
                $article->id,
                null,
                [],
                [],
                false,
                $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Generate SEO metadata for an article.
     */
    public function generateSeoMetadata(CollectedArticle $article): ?array
    {
        if (!$this->budgetService->canMakeApiCall()) {
            return null;
        }

        $prompt = $this->buildSeoPrompt($article);

        try {
            $response = $this->callApi($prompt, 500);

            if (!$response) {
                return null;
            }

            $seoData = $this->parseSeoResponse($response['content']);

            $this->budgetService->logUsage(
                'seo_generate',
                $response['input_tokens'],
                $response['output_tokens'],
                $article->id
            );

            $article->update(['seo_data' => $seoData]);

            return $seoData;

        } catch (Exception $e) {
            Log::error('SEO generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Call the Claude API.
     */
    protected function callApi(string $prompt, int $maxTokens = 1500): ?array
    {
        if (!$this->apiKey) {
            Log::error('Anthropic API key not configured');
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post($this->apiUrl, [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if (!$response->successful()) {
            Log::error('Claude API error: ' . $response->body());
            return null;
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'input_tokens' => $data['usage']['input_tokens'] ?? 0,
            'output_tokens' => $data['usage']['output_tokens'] ?? 0,
        ];
    }

    /**
     * Build the transformation prompt using full article content.
     */
    protected function buildTransformationPrompt(CollectedArticle $article, AutoPublishSetting $settings, string $fullContent = ''): string
    {
        $sourceContent = !empty($fullContent) ? $fullContent : ($article->description ?? '');
        $categoryContext = $article->assignedCategory
            ? "Category: {$article->assignedCategory->name}"
            : '';

        $sourceLine = trim(implode(' at ', array_filter([
            $article->author,
            $article->rssSource?->name,
        ])));

        return <<<PROMPT
You are Adil Sher, a full stack developer based in Islamabad who writes a personal tech blog. You write in a first-person, opinionated developer voice. Your style is direct, analytical, and grounded in real production experience. You share genuine takes, not surface-level summaries.

Your task: read the original article content below and write a full blog post about its topic from your personal perspective as a working developer.

**Original Article**
Title: {$article->title}
Source: {$article->rssSource?->name}
Author: {$article->author}
URL: {$article->url}
{$categoryContext}

**Full Article Content:**
{$sourceContent}

---

**Write the blog post following this exact structure:**

1. A creative, opinionated headline that is NOT the original article title. Frame it from your perspective (e.g. "Why I Changed My Mind About X", "The CSS Feature I've Been Waiting For", "A Question That Changed How I Think About Y").

2. An opening hook (2-3 paragraphs). Start with a personal anecdote, a question, or an observation from your own work that connects to this topic. Make the reader feel you genuinely encountered this.

3. The core topic explained through your lens. Use section headings (## Heading). Break down what the original article is about but explain it through your own understanding and experience. What does this mean for someone actually building things in production?

4. Your own analysis section (## My Take / ## What This Means in Practice / similar). What do you agree with? What would you do differently? What questions does this raise for you?

5. If the topic involves code, include a practical code snippet with explanation.

6. A closing section with your next step or a question for the reader.

7. End with this source line exactly:
*Source: This post was inspired by "{$article->title}" by {$sourceLine}. [Read the original article]({$article->url})*

**Rules:**
- Write in first person throughout (I, my, we)
- Total length: 600-900 words
- Use ## headings for sections, no H1
- Short paragraphs (2-4 sentences max)
- Do not reproduce large sections of the original verbatim
- Sound like a developer who read the article and has genuine thoughts about it
- Return ONLY the markdown content starting with the headline. No preamble.
PROMPT;
    }

    /**
     * Build the SEO metadata prompt.
     */
    protected function buildSeoPrompt(CollectedArticle $article): string
    {
        return <<<PROMPT
Generate SEO metadata for this tech blog post:

Title: {$article->title}
Description: {$article->description}
Category: {$article->assignedCategory?->name}

Return a JSON object with:
- "meta_title": SEO-optimized title (max 60 chars)
- "meta_description": Compelling description (max 155 chars)
- "keywords": Array of 5-7 relevant keywords
- "suggested_title": A more engaging title variation

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Parse transformation response.
     */
    protected function parseTransformationResponse(string $response, CollectedArticle $article): array
    {
        // Clean up the response
        $content = trim($response);

        // Generate a suggested title if not present
        $title = $article->title;

        // Extract TL;DR if present
        $tldr = '';
        if (preg_match('/## TL;DR\s*\n(.+?)(?=\n##|\z)/s', $content, $matches)) {
            $tldr = trim($matches[1]);
        }

        return [
            'title' => $title,
            'content' => $content,
            'tldr' => $tldr,
            'original_title' => $article->title,
            'original_url' => $article->url,
            'original_author' => $article->author,
            'original_source' => $article->rssSource?->name,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Parse SEO response.
     */
    protected function parseSeoResponse(string $response): array
    {
        try {
            // Try to extract JSON from the response
            $response = trim($response);

            // Remove markdown code blocks if present
            if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $response, $matches)) {
                $response = $matches[1];
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'meta_title' => null,
                    'meta_description' => null,
                    'keywords' => [],
                    'suggested_title' => null,
                ];
            }

            return [
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'keywords' => $data['keywords'] ?? [],
                'suggested_title' => $data['suggested_title'] ?? null,
            ];

        } catch (Exception $e) {
            Log::warning('Failed to parse SEO response: ' . $e->getMessage());
            return [
                'meta_title' => null,
                'meta_description' => null,
                'keywords' => [],
                'suggested_title' => null,
            ];
        }
    }

    /**
     * Check if AI enhancement is enabled.
     */
    public function isEnabled(): bool
    {
        return config('blog_automation.ai.enabled', true)
            && !empty($this->apiKey)
            && $this->budgetService->canMakeApiCall();
    }
}
