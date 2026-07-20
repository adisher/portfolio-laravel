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
                true,
                model: $this->model
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
                $e->getMessage(),
                model: $this->model
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
                $article->id,
                model: $this->model
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
    public function generateFromWorkItem(WorkItem $workItem, string $angle, ?string $hook = null, $voices = null): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Original article generation skipped: AI disabled or budget exhausted');
            return null;
        }

        $prompt = $this->buildWorkItemPrompt($workItem, $angle, $hook, $voices);
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
                ['work_item_id' => $workItem->id, 'angle' => $angle, 'hook' => $hook, 'voices' => $voices ? count($voices) : 0],
                [],
                true,
                model: $model
            );

            return $this->parseGeneratedArticle($response['content']);

        } catch (Exception $e) {
            Log::error('Work item article generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find organic user-voice candidates for a work item using Claude's web
     * search tool. Returns ['candidates' => [...], 'searches' => int] or null.
     * The caller reviews candidates and creates records; nothing is auto-approved.
     */
    public function findVoices(WorkItem $workItem, int $want = 8): array
    {
        $fail = fn($note) => ['candidates' => [], 'queries' => [], 'raw' => '', 'cost' => 0.0, 'note' => $note, 'ok' => false];

        if (!$this->isEnabled()) {
            return $fail('Claude search skipped: AI is disabled or the monthly budget is exhausted.');
        }
        if (!$this->apiKey) {
            return $fail('Claude search skipped: Anthropic API key (CLAUDE_API_KEY) is not configured.');
        }

        $model    = config('blog_automation.ai.model', $this->model);
        $domains  = \App\Support\VoiceFilter::domainsFor($workItem);
        $prompt   = $this->buildFindVoicesPrompt($workItem, $want, $domains);
        $messages = [['role' => 'user', 'content' => $prompt]];
        $inputTokens = 0; $outputTokens = 0; $searches = 0; $text = ''; $queries = [];
        $useAllowedDomains = true;

        try {
            // Server-tool turns can pause; resume a few times until end_turn.
            for ($round = 0; $round < 5; $round++) {
                $tool = [
                    'type' => 'web_search_20250305',
                    'name' => 'web_search',
                    'max_uses' => 6,
                ];
                // Restrict the search to community platforms at the API level, so a
                // competitor blog or SEO listicle simply cannot come back.
                if ($useAllowedDomains && !empty($domains)) {
                    $tool['allowed_domains'] = array_values($domains);
                }

                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->timeout(120)->post($this->apiUrl, [
                    'model' => $model,
                    'max_tokens' => 3000,
                    'tools' => [$tool],
                    'messages' => $messages,
                ]);

                if (!$response->successful()) {
                    $body = $response->body();
                    // If this tool version rejects allowed_domains, retry once without it
                    // (the prompt still names the allowed platforms).
                    if ($useAllowedDomains && str_contains($body, 'allowed_domains')) {
                        Log::warning('web_search rejected allowed_domains; retrying without it.');
                        $useAllowedDomains = false;
                        continue;
                    }
                    Log::error('Find voices API error: ' . $body);
                    return $fail('Claude API error (HTTP ' . $response->status() . '). Check the API key and budget.');
                }

                $data = $response->json();
                $inputTokens  += $data['usage']['input_tokens'] ?? 0;
                $outputTokens += $data['usage']['output_tokens'] ?? 0;
                $searches     += $data['usage']['server_tool_use']['web_search_requests'] ?? 0;

                foreach ($data['content'] ?? [] as $block) {
                    $type = $block['type'] ?? '';
                    if ($type === 'text') {
                        $text .= $block['text'];
                    } elseif ($type === 'server_tool_use' && ($block['name'] ?? '') === 'web_search') {
                        if (!empty($block['input']['query'])) {
                            $queries[] = $block['input']['query'];
                        }
                    }
                }

                if (($data['stop_reason'] ?? '') === 'pause_turn') {
                    $messages[] = ['role' => 'assistant', 'content' => $data['content']];
                    continue;
                }
                break;
            }

            $fee = (float) config('blog_automation.ai.web_search_cost_per_search', 0.01);
            $log = $this->budgetService->logUsage(
                'find_voices', $inputTokens, $outputTokens, null, null,
                ['work_item_id' => $workItem->id, 'searches' => $searches],
                [], true, null,
                model: $model, extraCostUsd: $searches * $fee
            );

            $candidates = $this->parseVoicesJson($text);

            // Enforce the allowlist even if the model wandered off it.
            $candidates = array_values(array_filter(
                $candidates,
                fn ($c) => \App\Support\VoiceFilter::hostAllowed($c['source_url'] ?? null, $domains)
            ));

            return [
                'candidates' => $candidates,
                'queries'    => $queries ?: ["(model ran {$searches} searches)"],
                'raw'        => $text,
                'cost'       => (float) $log->cost_usd,
                'note'       => empty($candidates)
                    ? 'Claude searched but returned no candidates. Try the Brave engine, or broaden this manual\'s keywords/pain points.'
                    : null,
                'ok'         => true,
            ];

        } catch (Exception $e) {
            Log::error('Find voices failed: ' . $e->getMessage());
            return $fail('Claude search failed: ' . $e->getMessage());
        }
    }

    /**
     * Prompt Claude to search the web for organic user voices and return JSON.
     */
    protected function buildFindVoicesPrompt(WorkItem $wi, int $want, array $domains = []): string
    {
        $list = fn($arr) => empty($arr) ? '(none)' : "- " . implode("\n- ", $arr);
        $pains = $list($wi->pain_points ?? []);
        $platforms = implode(', ', $domains) ?: 'reddit.com, news.ycombinator.com';

        return <<<PROMPT
You are collecting REAL USER COMMENTS to use as social proof in an article about "{$wi->name}".

Background (context only — do NOT search these commercial phrases, they surface vendor listicles):
What it is: {$wi->tagline}
Who it is for: {$wi->target_audience}

The frustrations to hunt for, in users' own words:
{$pains}

SEARCH ONLY THESE PLATFORMS: {$platforms}

Search the way a frustrated user writes, not the way a marketer does.
Good queries: "linktree got so expensive", "why am I paying monthly for a link page", "anyone else annoyed by".
Bad queries: "best link in bio alternative" — that is exactly what vendor listicles are optimised for.

WHAT COUNTS AS A VOICE:
- A comment, post or reply written by an INDIVIDUAL describing their own experience or frustration, at a specific URL on one of the platforms above.

WHAT NEVER COUNTS (do not return these under any circumstances):
- Company blogs, vendor pages, product marketing, or "best/top/alternatives/vs/review" listicles.
- Anything written by a business promoting a product, especially a competitor of this one.
- An article author's own generic commentary. We want users, not authors.

RULES:
- Be generous: a human verifies every candidate, so return anything plausibly a real user comment rather than filtering hard. Better {$want} to prune than 0.
- Never invent a quote or a URL. If you cannot get exact wording, put the gist in "quote", set a lower "confidence", and say so in "note".
- "confidence": "high" (quoting real on-page user text), "medium" (paraphrased from a snippet), "low" (loosely related).

Return ONLY a JSON array (no prose, no markdown fences):
[{"quote": "the user's words", "attribution": "who said it, e.g. a user in r/somesubreddit", "source_url": "https://...", "note": "one short line of context", "confidence": "high|medium|low"}]
Return [] only if these platforms genuinely have nothing on the topic.
PROMPT;
    }

    /**
     * Extract the candidate array from the model's (possibly fenced) text.
     */
    protected function parseVoicesJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/```(?:json)?/i', '', $text);
        if (preg_match('/\[.*\]/s', $text, $m)) {
            $text = $m[0];
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $quote = trim((string) ($row['quote'] ?? ''));
            if ($quote === '') {
                continue;
            }
            $out[] = [
                'quote'       => $quote,
                'attribution' => trim((string) ($row['attribution'] ?? '')) ?: null,
                'source_url'  => trim((string) ($row['source_url'] ?? '')) ?: null,
                'note'        => trim((string) ($row['note'] ?? '')) ?: null,
                'confidence'  => trim((string) ($row['confidence'] ?? '')) ?: null,
            ];
        }
        return $out;
    }

    /**
     * Build the rich, voice-matched prompt for an original article.
     */
    protected function buildWorkItemPrompt(WorkItem $wi, string $angle, ?string $hook = null, $voices = null): string
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

        // Selected user voices (records). For each, tell the model to embed the
        // attached screenshot if there is one, otherwise leave a [[social:]] marker.
        $voicesBlock = '(none selected; do not invent any)';
        if ($voices && count($voices)) {
            $lines = [];
            foreach (collect($voices)->values() as $i => $v) {
                $n = $i + 1;
                $attr = $v->attribution ? " — {$v->attribution}" : '';
                $src  = $v->source_url ? " (source: {$v->source_url})" : '';
                $line = "Voice {$n}: \"" . trim($v->quote) . "\"{$attr}{$src}.";
                if ($v->media && $v->media->url) {
                    $line .= " Screenshot available: right after this quote's blockquote, embed it on its own line exactly as: ![" . ($v->attribution ?: 'user comment') . "]({$v->media->url})";
                } else {
                    $line .= " No screenshot: after the blockquote, add a [[social: short description of the post]] marker on its own line.";
                }
                $lines[] = $line;
            }
            $voicesBlock = implode("\n", $lines);
        }

        // Screenshot library: constrain markers to a fixed set of slugs when the
        // product defines one, otherwise fall back to free-form descriptions.
        $shots = array_values(array_filter(array_map('trim', $wi->screenshots ?? []), fn($s) => $s !== ''));
        $screenshotRule = empty($shots)
            ? "- Where a real product screenshot would prove a point better than prose can, insert a marker on its own line as [[screenshot: short description of what the image should show]], immediately after the paragraph it illustrates. Use 2 to 4 across the piece, one per idea, and never two in a row. A human adds the real image, so do not use Markdown image syntax or invent image URLs."
            : "- Product screenshots: this product has a fixed screenshot library. Where one proves a point better than prose, insert a marker on its own line as [[screenshot: slug]] using ONLY a slug from this list (the exact text before the dash), placed immediately after the paragraph it illustrates:\n" . ("  - " . implode("\n  - ", $shots)) . "\nUse 2 to 4 markers total, one per idea, never two in a row, and never invent a slug that is not in this list.";

        // Opening instruction: a real event if one was chosen, otherwise a concrete unnamed scene.
        $hook = trim((string) $hook);
        $opening = $hook !== ''
            ? "Open the article with this real event, which is true and verified. Lead with it as the reader's point of view, make its consequence concrete, then move into the problem it illustrates before you get anywhere near your solution:\n\"{$hook}\"\nName only what this hook states; do not invent extra companies, dates, or numbers around it beyond the material you are given elsewhere in this prompt."
            : "Open with a specific, concrete scene: one person, one moment, one vivid consequence (for example a single dead link on an already-printed business card). Do NOT open with a generic \"there is a moment most people hit\" framing. Keep the opening SCENE itself unnamed and invent no real names in it. You may still cite the verified events in the supporting pattern once you are past the opening.";

        // Supporting pattern: the other real events (not the lead hook), so the piece
        // can show this is a pattern, not a one-off. Empty when there are 0-1 hooks.
        $allHooks   = array_values(array_filter(array_map('trim', $wi->hooks ?? []), fn($h) => $h !== ''));
        $supporting = array_slice(array_values(array_filter($allHooks, fn($h) => $h !== $hook)), 0, 5);
        $supportingBlock = empty($supporting) ? '' : "\n\nSUPPORTING PATTERN (use to show this is not an isolated case):\n" . ("- " . implode("\n- ", $supporting)) . "\nAfter the opening and the problem, briefly reference two or three of these other real events to establish a pattern rather than a one-off. Cite each one you name with its compact [(Source)](url) link. Keep it to a sentence or two of prose, never a bulleted list, and keep the lead event dominant. Do not add a separate screenshot for each of these; at most you may add a single [[social: ...]] marker for one collective visual (for example several shutdown headlines together) if it genuinely strengthens the pattern.";

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

Real user sentiment to weave in as social proof (hand-picked, real people). Quote each one verbatim, attribute it, cite it, and follow its per-voice screenshot instruction. Do NOT invent, reword, or re-attribute any of these, and do not add voices that are not listed here:
{$voicesBlock}

The soft call to action to land near the end:
{$cta}

Target search keyword to write around naturally: {$primaryKeyword}

HOW TO OPEN (this sets up the whole piece):
{$opening}
After the opening, put the reader inside the problem, deliver the genuine value, and only then present your work as the natural answer.{$supportingBlock}

VOICE AND RULES (follow strictly):
- First person, opinionated, grounded in real experience. Sound like a thoughtful builder sharing genuine insight, not a marketer.
- Stay calm and analytical even when citing platform failures. You are the reasonable builder pointing at a pattern, never an outraged rage-channel and never clickbait. No manufactured suspense, no "what happened next will shock you."
- Do not invent facts. Never state a named company, product, event, date, statistic, or quote unless it appears above in the hook, the supporting pattern, the stories, the proof, or the user sentiment. When in doubt, write a concrete but unnamed scene instead.
- Lead with the reader's pain or a real moment, deliver genuine value (teach how to think about the problem), and only then present your work as the natural answer. Roughly 80 percent value, 20 percent pitch.
- The pitch must be keen, not desperate: confident, specific about who it is for, and honest about the product's stage. Never beg.
- Be creative and unique. Do not follow a rigid template or use obvious section formulas. Let the piece breathe.
- Weave the real stories in naturally where they strengthen the point. Do not fabricate biographical facts beyond what is given.
- Social proof: present each of the user voices provided above as a Markdown blockquote (a line starting with >) holding the verbatim quote, its attribution, and a compact [(Source)](url) citation, placed where it strengthens a pain point. Never invent, reword, or re-attribute a quote, and never add a voice that was not provided.
- Citations: when you state a fact or quote that came from the hook, the proof, or the user sentiment, cite it by placing a compact reference link right after it, formatted exactly as [(Source)](url) where Source is a short name (the publication or subreddit), never the raw URL spelled out. Only cite sources actually provided above. Do not add a bibliography or a sources list at the end.
- Rhythm: a few times, at genuine turning points, you may isolate one short, punchy sentence on its own line and bold it for emphasis. Use this sparingly (two or three times at most), never as decoration.
- NEVER use em dashes anywhere. Use commas, colons, or periods instead.
{$screenshotRule}
- After each voice's blockquote, follow that voice's screenshot instruction: if a screenshot image was given, embed that exact image markdown on its own line; if not, add a [[social: short description]] marker on its own line for a human to fill. Only ever use the image URL you were given for that voice, never invent one.
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

        // Excerpt: first substantial paragraph, skipping headings, markers and quotes,
        // cleaned of markdown so it reads well as a meta description.
        $excerpt = '';
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '[[') || str_starts_with($line, '>')) {
                continue;
            }
            $plain = preg_replace('/\[\[[^\]]*\]\]/', '', $line);            // [[screenshot: ...]] / [[social: ...]]
            $plain = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $plain);  // [text](url) -> text
            $plain = trim(strip_tags(str_replace(['**', '__', '`', '*'], '', $plain)));
            if (strlen($plain) > 60) {
                $excerpt = \Illuminate\Support\Str::limit($plain, 280);
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
