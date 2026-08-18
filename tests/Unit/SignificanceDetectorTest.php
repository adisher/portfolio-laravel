<?php

namespace Tests\Unit;

use App\Models\CollectedArticle;
use App\Services\SignificanceDetector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Catches stories the normal per-category keyword scorer misses because it
 * only judges keyword-density on a short RSS snippet (e.g. the SpaceX/Cursor
 * acquisition, which scored ~40/100 despite being major industry news). The
 * cases that matter: a real trigger+entity co-occurrence fires, an entity
 * mentioned with no trigger language nearby does NOT fire (guards against
 * "Elon Musk" alone flooding every mention as breaking news).
 */
class SignificanceDetectorTest extends TestCase
{
    private SignificanceDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->detector = app(SignificanceDetector::class);
    }

    public function test_trigger_and_entity_in_same_sentence_is_significant(): void
    {
        $article = new CollectedArticle([
            'title' => 'SpaceX officially closes its Cursor acquisition',
            'description' => 'The deal, worth $60 billion, brings the AI coding tool into SpaceX.',
            'url' => null,
        ]);

        $result = $this->detector->check($article);

        $this->assertTrue($result['significant']);
        $this->assertNotEmpty($result['matched_sentence']);
    }

    public function test_entity_mention_alone_without_trigger_language_is_not_significant(): void
    {
        $article = new CollectedArticle([
            'title' => 'Elon Musk shares thoughts on remote work culture',
            'description' => 'In a recent interview, Musk discussed productivity tips for engineers.',
            'url' => null,
        ]);

        $result = $this->detector->check($article);

        $this->assertFalse($result['significant']);
    }

    public function test_trigger_language_alone_without_a_notable_entity_is_not_significant(): void
    {
        $article = new CollectedArticle([
            'title' => 'Local bakery chain acquired by regional investor group',
            'description' => 'The $2 million acquisition adds five new locations.',
            'url' => null,
        ]);

        $result = $this->detector->check($article);

        $this->assertFalse($result['significant']);
    }

    public function test_word_family_variants_all_match_not_just_the_exact_keyword(): void
    {
        $variants = [
            'OpenAI is acquiring a smaller AI startup for $500 million.',
            'OpenAI acquired a smaller AI startup for $500 million.',
            'OpenAI is buying a smaller AI startup for $500 million.',
        ];

        foreach ($variants as $description) {
            $article = new CollectedArticle([
                'title' => 'Industry update',
                'description' => $description,
                'url' => null,
            ]);

            $result = $this->detector->check($article);
            $this->assertTrue($result['significant'], "Failed for: {$description}");
        }
    }

    public function test_disabled_via_config_never_fires(): void
    {
        config(['blog_automation.significance.enabled' => false]);

        $article = new CollectedArticle([
            'title' => 'SpaceX officially closes its Cursor acquisition',
            'description' => 'The deal, worth $60 billion, brings the AI coding tool into SpaceX.',
            'url' => null,
        ]);

        $result = $this->detector->check($article);

        $this->assertFalse($result['significant']);
    }
}
