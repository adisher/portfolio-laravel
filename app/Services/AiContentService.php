<?php

namespace App\Services;

use App\Models\CollectedArticle;
use App\Models\AutoPublishSetting;
use App\Models\WorkItem;
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
        $this->model = config('blog_automation.ai.model', 'claude-haiku-4-5-20251001');
    }

    /**
     * Fetch and clean the full article text from the source URL for AI context.
     * Falls back to the RSS description if fetching fails.
     */
    protected function fetchSource(CollectedArticle $article): string
    {
        $url = trim($article->url ?? '');
        if (empty($url)) {
            return $article->description ?? '';
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Portfolio-Bot/1.0)'])
                ->get($url);

            if (!$response->successful()) {
                return $article->description ?? '';
            }

            // Strip scripts, styles, nav, footer, header, aside for text extraction
            $clean = preg_replace('/<(script|style|nav|footer|header|aside|form|iframe)[^>]*>.*?<\/\1>/si', '', $response->body());
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($clean)));

            if (strlen($text) > 6000) {
                $text = substr($text, 0, 6000) . '...';
            }

            return strlen($text) > 200 ? $text : ($article->description ?? '');

        } catch (Exception $e) {
            Log::info("Could not fetch source for article {$article->id}: " . $e->getMessage());
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

        // Fetch the full article text for AI context (images come from Pexels)
        $fullContent = $this->fetchSource($article);
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
    protected function callApi(string $prompt, int $maxTokens = 2500, ?string $model = null): ?array
    {
        if (!$this->apiKey) {
            Log::error('Anthropic API key not configured');
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(90)->post($this->apiUrl, [
            'model' => $model ?? $this->model,
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
     * Generate a first-person original marketing article from a work-item
     * manual and a chosen article angle. Returns [title, content, excerpt]
     * or null on failure. The caller creates the draft post.
     */
    public function generateFromWorkItem(WorkItem $workItem, string $angle, ?string $hook = null): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Original article generation skipped: AI disabled or budget exhausted');
            return null;
        }

        $prompt = $this->buildWorkItemPrompt($workItem, $angle, $hook);
        $model  = config('blog_automation.ai.original_model', $this->model);

        try {
            $response = $this->callApi($prompt, 3000, $model);
            if (!$response) {
                return null;
            }

            $this->budgetService->logUsage(
                'original_article',
                $response['input_tokens'],
                $response['output_tokens'],
                null,
                null,
                ['work_item_id' => $workItem->id, 'angle' => $angle, 'hook' => $hook],
                [],
                true
            );

            return $this->parseGeneratedArticle($response['content']);

        } catch (Exception $e) {
            Log::error('Work item article generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build the rich, voice-matched prompt for an original article.
     */
    protected function buildWorkItemPrompt(WorkItem $wi, string $angle, ?string $hook = null): string
    {
        $list = fn($arr) => empty($arr) ? '(none)' : "- " . implode("\n- ", $arr);

        $painPoints     = $list($wi->pain_points ?? []);
        $objections     = $list($wi->objections ?? []);
        $outcomes       = $list($wi->key_outcomes ?? []);
        $differentiators = $list($wi->differentiators ?? []);
        $keywords       = $list($wi->target_keywords ?? []);
        $primaryKeyword = $wi->target_keywords[0] ?? '';
        $stories        = trim((string) $wi->stories) !== '' ? $wi->stories : '(no personal stories provided; keep it grounded and specific without inventing biographical details)';
        $cta            = trim((string) $wi->call_to_action) !== '' ? $wi->call_to_action : 'Invite the reader to get in touch if this is their problem.';

        // Opening instruction: a real event if one was chosen, otherwise a concrete unnamed scene.
        $hook = trim((string) $hook);
        $opening = $hook !== ''
            ? "Open the article with this real event, which is true and verified. Lead with it as the reader's point of view, make its consequence concrete, then move into the problem it illustrates before you get anywhere near your solution:\n\"{$hook}\"\nYou may name what is stated in this hook, but do not add any further named companies, dates, numbers, or events around it that are not given here."
            : "Open with a specific, concrete scene: one person, one moment, one vivid consequence (for example a single dead link on an already-printed business card). Do NOT open with a generic \"there is a moment most people hit\" framing, and do NOT name any real company, product, event, date, or statistic, because none has been verified for you. Keep the scene true-to-life but unnamed.";

        return <<<PROMPT
You are Adil Sher, a full stack developer, writing an original article for your personal blog's "Proof of Work" section. These pieces market your skills through genuine, problem-led storytelling. This is not a summary or a news post. It is your own considered writing.

Write the article for this angle:
"{$angle}"

This article is about your work item: {$wi->name}.
What it is: {$wi->tagline}
Who it is for: {$wi->target_audience}
How it helps: {$wi->how_it_helps}

The real pain points it addresses:
{$painPoints}

Objections and hesitations a reader might have (address the relevant ones naturally, do not list them):
{$objections}

What makes it different / your engineering judgment:
{$differentiators}

Proof and outcomes you can reference:
{$outcomes}

Real stories and personal details to weave in where they fit (this is what keeps the piece authentic, not generic):
{$stories}

The soft call to action to land near the end:
{$cta}

Target search keyword to write around naturally: {$primaryKeyword}

HOW TO OPEN (this sets up the whole piece):
{$opening}
After the opening, put the reader inside the problem, deliver the genuine value, and only then present your work as the natural answer.

VOICE AND RULES (follow strictly):
- First person, opinionated, grounded in real experience. Sound like a thoughtful builder sharing genuine insight, not a marketer.
- Do not invent facts. Never state a named company, product, event, date, or statistic unless it appears above in the hook, the stories, or the proof. When in doubt, write a concrete but unnamed scene instead.
- Lead with the reader's pain or a real moment, deliver genuine value (teach how to think about the problem), and only then present your work as the natural answer. Roughly 80 percent value, 20 percent pitch.
- The pitch must be keen, not desperate: confident, specific about who it is for, and honest about the product's stage. Never beg.
- Be creative and unique. Do not follow a rigid template or use obvious section formulas. Let the piece breathe.
- Weave the real stories in naturally where they strengthen the point. Do not fabricate biographical facts beyond what is given.
- NEVER use em dashes anywhere. Use commas, colons, or periods instead.
- Where a real product screenshot would prove a point better than prose can (for example the live page, the admin panel, an analytics dashboard, a theme picker, drag-and-drop links), insert a placement marker on its own line in this exact format: [[screenshot: short description of what the image should show]]. Put it immediately after the paragraph it illustrates. Use 2 to 4 of these across the whole piece, one per idea, and never two in a row. These are placeholders a human will replace with a real screenshot, so do not use Markdown image syntax and do not invent image URLs. Only add a marker where an actual screenshot of this product would plausibly exist.
- 900 to 1100 words. Markdown formatting. Use ## for section headings. Put the article's headline as a single # line at the very top (create a compelling headline based on the angle, not a copy of it).
- Return only the article markdown, starting with the # headline. No preamble, no notes.
PROMPT;
    }

    /**
     * Parse a generated article into title, content, and excerpt.
     */
    protected function parseGeneratedArticle(string $response): array
    {
        $content = trim($response);
        $title = '';

        // Headline is the first # line
        if (preg_match('/^#\s+(.+)/m', $content, $m)) {
            $title = trim($m[1]);
            $content = trim(preg_replace('/^#\s+.+\n+/m', '', $content, 1));
        }

        // Excerpt: first substantial non-heading paragraph
        $excerpt = '';
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '#') && strlen($line) > 60) {
                $excerpt = \Illuminate\Support\Str::limit(strip_tags($line), 280);
                break;
            }
        }

        return [
            'title'   => $title,
            'content' => $content,
            'excerpt' => $excerpt,
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
        $content = trim($response);

        // Extract headline from first # line — Claude puts it at the top
        $title = $article->title;
        if (preg_match('/^#\s+(.+)/m', $content, $m)) {
            $title = trim($m[1]);
            // Strip the headline from body so it's not doubled in rendering
            $content = trim(preg_replace('/^#\s+.+\n+/m', '', $content, 1));
        }

        // Try to extract a TL;DR section if Claude included one
        $excerpt = '';
        if (preg_match('/##\s*TL;DR\s*\n(.+?)(?=\n##|\z)/si', $content, $matches)) {
            $excerpt = trim(strip_tags($matches[1]));
        }

        // Fallback: use the first non-heading paragraph as the excerpt
        if (empty($excerpt)) {
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if (!empty($line) && !str_starts_with($line, '#') && strlen($line) > 60) {
                    $excerpt = \Illuminate\Support\Str::limit(strip_tags($line), 280);
                    break;
                }
            }
        }

        // Final fallback: use the original RSS description
        if (empty($excerpt)) {
            $excerpt = \Illuminate\Support\Str::limit(strip_tags($article->description ?? ''), 280);
        }

        return [
            'title'           => $title,
            'content'         => $content,
            'tldr'            => $excerpt,
            'original_title'  => $article->title,
            'original_url'    => $article->url,
            'original_author' => $article->author,
            'original_source' => $article->rssSource?->name,
            'generated_at'    => now()->toIso8601String(),
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
