<?php

namespace Tests\Unit;

use App\Models\CollectedArticle;
use App\Services\SignificanceDetector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The fast path publishes straight to the live blog with nobody watching, so
 * the false-positive cases matter more than the positive one. Every
 * "not_significant" test below is a REAL article this wrongly published on
 * its first production run (10/10 were false positives): Git merge-conflict
 * tutorials, "Buy Me a Coffee" footers, a Mac pricing table, and an S3 image
 * URL containing "amazonaws.com".
 */
class SignificanceDetectorTest extends TestCase
{
    private SignificanceDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->detector = app(SignificanceDetector::class);

        // Source gating is exercised separately; these cases are about the
        // vocabulary and sentence rules.
        config(['blog_automation.significance.source_allowlist' => []]);
    }

    private function article(string $title, string $description): CollectedArticle
    {
        return new CollectedArticle([
            'title' => $title,
            'description' => $description,
            'url' => null,
        ]);
    }

    public function test_trigger_and_entity_in_same_sentence_is_significant(): void
    {
        $result = $this->detector->check($this->article(
            'SpaceX officially closes its Cursor acquisition',
            'The deal, worth $60 billion, brings the AI coding tool into SpaceX.'
        ));

        $this->assertTrue($result['significant']);
        $this->assertNotEmpty($result['matched_sentence']);
    }

    public function test_headline_using_buys_still_matches_via_the_money_pattern(): void
    {
        // Bare buy/bought/buying were removed from the vocabulary, so this
        // must still be caught by the dollar-amount pattern instead.
        $result = $this->detector->check($this->article(
            'SpaceX buys AI coding startup Cursor for $60 billion',
            'The acquisition follows the company IPO.'
        ));

        $this->assertTrue($result['significant']);
    }

    public function test_entity_mention_alone_without_trigger_language_is_not_significant(): void
    {
        $result = $this->detector->check($this->article(
            'Elon Musk shares thoughts on remote work culture',
            'In a recent interview, Musk discussed productivity tips for engineers.'
        ));

        $this->assertFalse($result['significant']);
    }

    public function test_trigger_language_alone_without_a_notable_entity_is_not_significant(): void
    {
        $result = $this->detector->check($this->article(
            'Local bakery chain acquired by regional investor group',
            'The $2 million acquisition adds five new locations.'
        ));

        $this->assertFalse($result['significant']);
    }

    /** Regression: merger?s? also matched the word "merge". */
    public function test_git_merge_tutorial_is_not_breaking_news(): void
    {
        $result = $this->detector->check($this->article(
            'Why WHERE x = NULL never works in SQL and what to use instead',
            'How to Resolve a Git Merge Conflict Without Panicking, from GitHub beginners tutorials.'
        ));

        $this->assertFalse($result['significant']);
    }

    /** Regression: bare "Buy" matched donation-link boilerplate. */
    public function test_buy_me_a_coffee_footer_is_not_breaking_news(): void
    {
        $result = $this->detector->check($this->article(
            'What data migration actually is',
            'If it was useful, Buy Me a Coffee. The full guide lives on my GitHub pages site.'
        ));

        $this->assertFalse($result['significant']);
    }

    /** Regression: "Buy a Mac" in a cost table. */
    public function test_hardware_pricing_table_is_not_breaking_news(): void
    {
        $result = $this->detector->check($this->article(
            'The actual cost of shipping an iOS app in 2026',
            'Buy a Mac mini at entry price, or rent a cloud Mac, or use GitHub Actions for free.'
        ));

        $this->assertFalse($result['significant']);
    }

    /** Regression: str_contains matched "amazon" inside "amazonaws.com". */
    public function test_entity_matching_is_whole_word_not_substring(): void
    {
        $result = $this->detector->check($this->article(
            'In the beginning',
            'I bought a copy of Sams Teach Yourself C in 21 Days, hosted at dev-to-uploads.s3.amazonaws.com for $30 million readers.'
        ));

        $this->assertFalse($result['significant']);
    }

    public function test_css_and_markup_residue_is_never_treated_as_a_sentence(): void
    {
        $result = $this->detector->check($this->article(
            'AI agents can find products',
            'body:has(.pageslug-aie) #topbar { display: none !important; } @media screen and (max-width: 768px) { meta acquisition $60 billion }'
        ));

        $this->assertFalse($result['significant']);
    }

    public function test_word_family_variants_all_match_not_just_the_exact_keyword(): void
    {
        foreach ([
            'OpenAI is acquiring a smaller AI startup for $500 million.',
            'OpenAI acquired a smaller AI startup for $500 million.',
            'OpenAI completed the acquisition of a smaller AI startup.',
        ] as $description) {
            $result = $this->detector->check($this->article('Industry update', $description));
            $this->assertTrue($result['significant'], "Failed for: {$description}");
        }
    }

    public function test_source_outside_the_allowlist_never_qualifies(): void
    {
        config(['blog_automation.significance.source_allowlist' => ['TechCrunch']]);

        // No rssSource relation loaded at all — unknown provenance.
        $result = $this->detector->check($this->article(
            'SpaceX officially closes its Cursor acquisition',
            'The deal, worth $60 billion, brings the AI coding tool into SpaceX.'
        ));

        $this->assertFalse($result['significant']);
    }

    public function test_disabled_via_config_never_fires(): void
    {
        config(['blog_automation.significance.enabled' => false]);

        $result = $this->detector->check($this->article(
            'SpaceX officially closes its Cursor acquisition',
            'The deal, worth $60 billion, brings the AI coding tool into SpaceX.'
        ));

        $this->assertFalse($result['significant']);
    }
}
