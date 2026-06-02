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
        $this->model = config('blog_automation.ai.model', 'claude-3-haiku-20240307');
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
        $prompt = $this->buildTransformationPrompt($article, $settings);

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
     * Build the transformation prompt.
     */
    protected function buildTransformationPrompt(CollectedArticle $article, AutoPublishSetting $settings): string
    {
        $sections = [];

        if ($settings->include_tldr) {
            $sections[] = "## TL;DR\n[Write a 2-3 sentence summary of the key points]";
        }

        if ($settings->include_key_insights) {
            $sections[] = "## Key Insights\n- [Insight 1]\n- [Insight 2]\n- [Insight 3]";
        }

        $sections[] = "## Analysis\n[Write 2-3 paragraphs of original analysis and commentary on this topic. Add your perspective on why this matters to developers and tech professionals.]";

        if ($settings->include_faq_section) {
            $sections[] = "## FAQ\n**Q: [Relevant question 1]?**\nA: [Direct answer]\n\n**Q: [Relevant question 2]?**\nA: [Direct answer]";
        }

        $sectionsText = implode("\n\n", $sections);

        $categoryContext = $article->assignedCategory
            ? "This article is for the '{$article->assignedCategory->name}' category."
            : "";

        return <<<PROMPT
You are a tech blogger writing for a personal brand website focused on AI, web development, and technology.

Transform the following article summary into an engaging blog post. Write in a professional but approachable tone. Add original analysis and insights.

**Original Article:**
Title: {$article->title}
Source: {$article->rssSource?->name}
Author: {$article->author}
Description: {$article->description}

{$categoryContext}

**Write the blog post with these sections:**

{$sectionsText}

## Source Attribution
*This article discusses content originally published by {$article->author} at {$article->rssSource?->name}. [Read the original article]({$article->url})*

**Important guidelines:**
- Write original analysis, don't just summarize
- Use clear, scannable formatting with short paragraphs
- Include actionable insights for developers
- Keep the total content between 400-800 words
- Make the content AI-friendly with clear structure

Return ONLY the markdown content, no additional commentary.
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
