<?php

namespace Tests\Unit;

use App\Support\BotDetector;
use PHPUnit\Framework\TestCase;

/**
 * Bot classification. The regressions that matter: the AI crawlers the old
 * isRobot()-only path let through as "human" (Claude-Web, Google-Extended)
 * must now be caught, and a real browser UA must NOT be flagged (a
 * false-positive would delete a real reader from the analytics).
 */
class BotDetectorTest extends TestCase
{
    public function test_ai_crawlers_are_flagged_and_named(): void
    {
        foreach ([
            'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)' => 'ClaudeBot (Anthropic)',
            'Mozilla/5.0 (compatible; GPTBot/1.1; +https://openai.com/gptbot)'   => 'GPTBot (OpenAI)',
            'Mozilla/5.0 ChatGPT-User/1.0 +https://openai.com/bot'              => 'ChatGPT-User (OpenAI)',
            'Mozilla/5.0 (compatible; PerplexityBot/1.0)'                        => 'PerplexityBot',
        ] as $ua => $name) {
            [$isBot, $reason, $crawler] = BotDetector::fromUserAgent($ua);
            $this->assertTrue($isBot, $ua);
            $this->assertSame('ai_crawler', $reason, $ua);
            $this->assertSame($name, $crawler, $ua);
        }
    }

    /** These are the two that jenssegers isRobot() misses — the whole reason for this class. */
    public function test_catches_the_crawlers_isrobot_misses(): void
    {
        foreach (['Mozilla/5.0 (compatible; Claude-Web/1.0)', 'Mozilla/5.0 (compatible; Google-Extended/1.0)'] as $ua) {
            [$isBot, $reason] = BotDetector::fromUserAgent($ua);
            $this->assertTrue($isBot, "should flag: {$ua}");
            $this->assertSame('ai_crawler', $reason);
        }
    }

    public function test_search_and_seo_crawlers_get_their_own_reason(): void
    {
        $this->assertSame('search_crawler', BotDetector::fromUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1)')[1]);
        $this->assertSame('search_crawler', BotDetector::fromUserAgent('Mozilla/5.0 (compatible; bingbot/2.0)')[1]);
        $this->assertSame('seo_crawler', BotDetector::fromUserAgent('Mozilla/5.0 (compatible; AhrefsBot/7.0)')[1]);
    }

    public function test_real_browsers_are_not_flagged(): void
    {
        foreach ([
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ] as $ua) {
            [$isBot, $reason] = BotDetector::fromUserAgent($ua);
            $this->assertFalse($isBot, "should NOT flag real browser: {$ua}");
            $this->assertNull($reason);
        }
    }

    public function test_the_prod_scraper_ua_is_not_caught_by_ua_alone(): void
    {
        // The rotating-proxy scraper spoofed this exact clean Chrome UA. UA
        // detection cannot catch it — that's the behavioural pass's job. This
        // test documents the boundary so nobody "fixes" it by blocking the UA
        // (which would also block real Mac Chrome users).
        $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36';
        $this->assertFalse(BotDetector::isBot($ua));
    }

    public function test_empty_user_agent_is_left_to_behavioural_detection(): void
    {
        [$isBot, $reason] = BotDetector::fromUserAgent('');
        $this->assertFalse($isBot);
        $this->assertNull($reason);
    }
}
