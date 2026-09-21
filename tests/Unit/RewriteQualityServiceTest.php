<?php

namespace Tests\Unit;

use App\Services\RewriteQualityService;
use PHPUnit\Framework\TestCase;

class RewriteQualityServiceTest extends TestCase
{
    private RewriteQualityService $quality;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quality = new RewriteQualityService();
    }

    /** A well-formed rewrite: own headline, own prose, sections, attribution. */
    private function goodArticle(): string
    {
        // ~800 words across two sections: inside the 600-1200 target band.
        $body = str_repeat('I spent a week wiring this into a production queue and the tradeoffs surprised me. ', 25);

        return "## Where this bites in production\n\n{$body}\n\n## My take\n\n{$body}\n\n"
            . '*Source: This post was inspired by "Some Vendor Ships A Thing" by A Writer at Example News. '
            . '[Read the original article](https://example.com/post)*';
    }

    public function test_a_well_formed_rewrite_passes(): void
    {
        $result = $this->quality->evaluate(
            'Why I Stopped Trusting My Own Queue Metrics',
            $this->goodArticle(),
            'Some Vendor Ships A Thing',
            'Completely different source wording about a vendor announcement. ' . str_repeat('unrelated source sentence here. ', 40),
            'A short excerpt.',
            'https://example.com/post'
        );

        $this->assertTrue($result['passed'], 'Expected pass, got: ' . json_encode($result));
        $this->assertSame(100, $result['score']);
    }

    public function test_reusing_the_source_headline_is_a_hard_failure(): void
    {
        $result = $this->quality->evaluate(
            'Some Vendor Ships A Thing',
            $this->goodArticle(),
            'Some Vendor Ships A Thing',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertFalse($result['passed']);
        $this->assertContains('title_matches_source', $result['hard_failures']);
    }

    public function test_copying_the_source_body_is_a_hard_failure(): void
    {
        $source = str_repeat('the vendor announced a new pricing tier for enterprise customers today. ', 40);

        $result = $this->quality->evaluate(
            'A Completely Different Headline About Pricing Tiers',
            "## Section\n\n" . $source . "\n\n## My take\n\n" . $source
                . "\n\n*Source: [Read the original article](https://example.com/post)*",
            'Vendor announces pricing',
            $source,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertFalse($result['passed']);
        $this->assertContains('body_copies_source', $result['hard_failures']);
    }

    public function test_short_article_and_missing_attribution_are_hard_failures(): void
    {
        $result = $this->quality->evaluate(
            'A Perfectly Reasonable Headline That Is Long Enough',
            "## Section\n\nToo little text here.",
            'Original title',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertContains('too_short', $result['hard_failures']);
        $this->assertContains('missing_attribution', $result['hard_failures']);
    }

    public function test_refusal_text_is_a_hard_failure(): void
    {
        $result = $this->quality->evaluate(
            'A Perfectly Reasonable Headline That Is Long Enough',
            "As an AI language model, I cannot help with that.\n\n" . $this->goodArticle(),
            'Original title',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertContains('placeholder_or_refusal', $result['hard_failures']);
    }

    public function test_non_english_article_is_a_hard_failure(): void
    {
        $result = $this->quality->evaluate(
            '분산형 SSH 공격, fail2ban recidive로 막아낸 이야기',
            str_repeat('분산형 SSH 공격을 fail2ban recidive 설정으로 막아낸 경험을 공유합니다. ', 80)
                . "\n\n*Source: [Read the original article](https://example.com/post)*",
            'Distributed SSH attacks',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertContains('not_english', $result['hard_failures']);
    }

    public function test_soft_flags_deduct_but_two_still_pass(): void
    {
        // One heading (few_headings) and a short headline (headline_length).
        $body = str_repeat('I rebuilt the retry logic and it held up under load. ', 90);
        $result = $this->quality->evaluate(
            'Short Headline',
            "## Only one section\n\n{$body}\n\n*Source: [Read the original article](https://example.com/post)*",
            'A totally different original title',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertSame(80, $result['score']);
        $this->assertTrue($result['passed']);
    }

    public function test_three_soft_flags_fall_below_the_pass_mark(): void
    {
        // few_headings + headline_length + no_excerpt.
        $body = str_repeat('The retry logic was rebuilt and it held up under load. ', 90);
        $result = $this->quality->evaluate(
            'Short Headline',
            "{$body}\n\n*Source: [Read the original article](https://example.com/post)*",
            'A totally different original title',
            null,
            '',
            'https://example.com/post'
        );

        $this->assertLessThan(RewriteQualityService::PASS_MARK, $result['score']);
        $this->assertFalse($result['passed']);
    }

    /** The bug that produced 45 bad posts: no "# " line, so the title fell back to the source's. */
    public function test_raw_response_without_h1_is_caught(): void
    {
        $raw = "## Where this bites\n\n" . str_repeat('I tried this in production and it broke. ', 90)
            . "\n\n*Source: [Read the original article](https://example.com/post)*";

        $result = $this->quality->evaluateRawResponse($raw, 'Original source headline', null, 'https://example.com/post');

        $this->assertFalse($result['passed']);
        $this->assertSame('missing_h1_headline', $result['hard_failures'][0]);
    }

    public function test_raw_response_with_h1_is_accepted(): void
    {
        $raw = "# Why I Stopped Trusting My Own Queue Metrics\n\n"
            . "## Where this bites\n\n" . str_repeat('I tried this in production and it broke. ', 90)
            . "\n\n## My take\n\n" . str_repeat('That changed how I scope retries now. ', 40)
            . "\n\n*Source: [Read the original article](https://example.com/post)*";

        $result = $this->quality->evaluateRawResponse($raw, 'Original source headline', null, 'https://example.com/post');

        $this->assertNotContains('missing_h1_headline', $result['hard_failures']);
        $this->assertTrue($result['passed'], json_encode($result));
    }

    /** A code snippet is what the prompt asks for, not scaffolding. */
    public function test_a_fenced_code_block_in_the_body_is_not_a_failure(): void
    {
        $body = str_repeat('I rewrote the retry helper and measured it again. ', 60);
        $content = "## The fix\n\n{$body}\n\n```php\n\$retries = 3;\n```\n\n## My take\n\n{$body}"
            . "\n\n*Source: [Read the original article](https://example.com/post)*";

        $result = $this->quality->evaluate(
            'Why I Rewrote My Retry Helper From Scratch',
            $content,
            'A totally different original title',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertNotContains('placeholder_or_refusal', $result['hard_failures']);
        $this->assertTrue($result['passed'], json_encode($result));
    }

    /** But a fence wrapping the entire answer is scaffolding. */
    public function test_a_fence_wrapping_the_whole_response_is_a_failure(): void
    {
        $result = $this->quality->evaluate(
            'Why I Rewrote My Retry Helper From Scratch',
            "```markdown\n## The fix\n\n" . str_repeat('I rewrote the retry helper again. ', 90)
                . "\n```\n\n*Source: [Read the original article](https://example.com/post)*",
            'A totally different original title',
            null,
            'excerpt',
            'https://example.com/post'
        );

        $this->assertContains('placeholder_or_refusal', $result['hard_failures']);
    }
}
