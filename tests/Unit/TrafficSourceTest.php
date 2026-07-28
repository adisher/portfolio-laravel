<?php

namespace Tests\Unit;

use App\Support\TrafficSource;
use Tests\TestCase;

/**
 * Traffic-source classification — the logic behind "sources cited". The AI
 * cases and the search/AI disambiguation (gemini.google.com is AI, not Search)
 * are the ones that matter most. Extends the framework TestCase because the
 * self-referral branch reads config('app.url').
 */
class TrafficSourceTest extends TestCase
{
    private function assertSource(string $expectedSource, ?string $expectedDetail, ?string $referrer, ?string $utm = null): void
    {
        [$source, $detail] = TrafficSource::classify($referrer, $utm);
        $this->assertSame($expectedSource, $source, "referrer={$referrer}");
        if ($expectedDetail !== null) {
            $this->assertSame($expectedDetail, $detail, "detail for referrer={$referrer}");
        }
    }

    public function test_ai_assistants_are_identified_by_name(): void
    {
        $this->assertSource(TrafficSource::AI, 'ChatGPT', 'https://chatgpt.com/');
        $this->assertSource(TrafficSource::AI, 'ChatGPT', 'https://chat.openai.com/c/abc');
        $this->assertSource(TrafficSource::AI, 'Claude', 'https://claude.ai/chat/xyz');
        $this->assertSource(TrafficSource::AI, 'Perplexity', 'https://www.perplexity.ai/search?q=x');
        $this->assertSource(TrafficSource::AI, 'Gemini', 'https://gemini.google.com/app');
        $this->assertSource(TrafficSource::AI, 'Copilot', 'https://copilot.microsoft.com/');
    }

    public function test_gemini_is_ai_not_search_despite_google_domain(): void
    {
        // gemini.google.com contains "google." — must resolve to AI, not Search.
        $this->assertSource(TrafficSource::AI, 'Gemini', 'https://gemini.google.com/');
    }

    public function test_search_engines(): void
    {
        $this->assertSource(TrafficSource::SEARCH, 'Google', 'https://www.google.com/search?q=x');
        $this->assertSource(TrafficSource::SEARCH, 'Bing', 'https://www.bing.com/search?q=x');
        $this->assertSource(TrafficSource::SEARCH, 'DuckDuckGo', 'https://duckduckgo.com/?q=x');
    }

    public function test_social_networks(): void
    {
        $this->assertSource(TrafficSource::SOCIAL, 'X', 'https://x.com/user/status/1');
        $this->assertSource(TrafficSource::SOCIAL, 'X', 'https://t.co/abc');
        $this->assertSource(TrafficSource::SOCIAL, 'LinkedIn', 'https://www.linkedin.com/feed/');
        $this->assertSource(TrafficSource::SOCIAL, 'Hacker News', 'https://news.ycombinator.com/item?id=1');
    }

    public function test_no_referrer_is_direct(): void
    {
        $this->assertSource(TrafficSource::DIRECT, null, null);
        $this->assertSource(TrafficSource::DIRECT, null, '');
    }

    public function test_unknown_domain_is_referral_with_host_detail(): void
    {
        $this->assertSource(TrafficSource::REFERRAL, 'someblog.dev', 'https://someblog.dev/post/1');
        // www is stripped from the detail host.
        $this->assertSource(TrafficSource::REFERRAL, 'example.com', 'https://www.example.com/x');
    }

    public function test_utm_source_overrides_when_present(): void
    {
        $this->assertSource(TrafficSource::AI, 'ChatGPT', null, 'chatgpt');
        $this->assertSource(TrafficSource::AI, 'Claude', 'https://unknown.com/', 'claude');
    }

    public function test_labels(): void
    {
        $this->assertSame('AI Assistant', TrafficSource::label(TrafficSource::AI));
        $this->assertSame('Direct', TrafficSource::label(TrafficSource::DIRECT));
    }
}
