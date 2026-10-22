<?php

namespace Tests\Feature;

use App\Services\Cinema\LocalCinemaFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The /cinema page renders the Local Cinema JSON Feed. What matters: items
 * show up, unsafe feed URLs never reach the page, and a broken remote falls
 * back to the last good copy instead of an empty page or a 500.
 */
class CinemaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function feed(array $items): array
    {
        return ['version' => 'https://jsonfeed.org/version/1.1', 'title' => 'Local Cinema', 'items' => $items];
    }

    public function test_it_lists_movies_from_the_feed(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::response($this->feed([
            [
                'id' => 'https://localcinema.xyz/watch/saint-the-sea-1773172',
                'url' => 'https://localcinema.xyz/watch/saint-the-sea-1773172',
                'title' => 'Saint &amp; the Sea (2026)',
                'content_text' => 'As a storm approaches their coastal village...',
                'content_html' => '<p><img src="https://image.tmdb.org/t/p/w780/poster.jpg" alt="" /></p>',
                'image' => null,
                'tags' => ['Movie'],
            ],
            [
                'url' => 'https://localcinema.xyz/watch/halchal-1102932',
                'title' => 'Halchal (1985)',
                'content_text' => 'Watch Halchal (1985) online on Local Cinema.',
                'image' => 'https://m.media-amazon.com/images/M/halchal.jpg',
            ],
        ]))]);

        $response = $this->get(route('cinema.index'));

        $response->assertOk()
            ->assertSee('Saint &amp; the Sea', false)
            ->assertSee('2026')
            ->assertSee('https://image.tmdb.org/t/p/w780/poster.jpg', false)
            ->assertSee('https://localcinema.xyz/watch/halchal-1102932', false)
            ->assertDontSee('Watch Halchal (1985) online on Local Cinema.');

        Http::assertSent(fn ($request) => $request['type'] === 'movies' && $request['limit'] == 60);
    }

    public function test_items_without_a_poster_are_skipped_and_list_is_capped(): void
    {
        $items = [['url' => 'https://localcinema.xyz/watch/no-poster-1', 'title' => 'No Poster (2027)', 'image' => null]];
        foreach (range(1, 15) as $i) {
            $items[] = ['url' => "https://localcinema.xyz/watch/film-{$i}", 'title' => "Film {$i} (2026)", 'image' => "https://image.tmdb.org/t/p/w500/{$i}.jpg"];
        }
        Http::fake(['localcinema.xyz/*' => Http::response($this->feed($items))]);

        $shown = app(LocalCinemaFeedService::class)->items('movies');

        $this->assertCount(12, $shown);
        $this->assertSame('Film 1', $shown[0]['title']);
        $this->assertNotContains('No Poster', array_column($shown, 'title'));
    }

    public function test_page_is_kept_out_of_search_engines(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::response($this->feed([]))]);

        $this->get(route('cinema.index'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<meta name="googlebot" content="noindex, nofollow">', false)
            ->assertDontSee('"ItemList"', false);
    }

    public function test_tv_tab_requests_tv_and_unknown_types_fall_back_to_movies(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::response($this->feed([]))]);

        $this->get(route('cinema.index', ['type' => 'tv']))->assertOk();
        $this->get(route('cinema.index', ['type' => 'nope']))->assertOk();

        Http::assertSent(fn ($request) => $request['type'] === 'tv');
        Http::assertNotSent(fn ($request) => $request['type'] === 'nope');
    }

    public function test_items_with_unsafe_urls_are_dropped(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::response($this->feed([
            ['url' => 'javascript:alert(1)', 'title' => 'Evil (2020)'],
            ['url' => 'https://localcinema.xyz/watch/ok-1', 'title' => 'Fine (2020)', 'image' => 'https://image.tmdb.org/t/p/w500/fine.jpg'],
            ['url' => 'https://localcinema.xyz/watch/bad-img', 'title' => 'BadImage (2020)', 'image' => 'javascript:alert(2)'],
        ]))]);

        $this->get(route('cinema.index'))
            ->assertOk()
            ->assertDontSee('Evil')
            ->assertDontSee('BadImage')
            ->assertDontSee('javascript:', false)
            ->assertSee('Fine');
    }

    public function test_remote_failure_serves_last_good_copy(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::sequence()
            ->push($this->feed([['url' => 'https://localcinema.xyz/watch/raja-1', 'title' => 'Raja (1990)', 'image' => 'https://image.tmdb.org/t/p/w500/raja.jpg']]))
            ->push('Server Error', 500),
        ]);

        $service = app(LocalCinemaFeedService::class);
        $this->assertCount(1, $service->items('movies'));

        $service->clearCache(); // force a refetch, which now fails
        $this->get(route('cinema.index'))->assertOk()->assertSee('Raja');
    }

    public function test_remote_failure_with_no_cache_shows_empty_state(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::response('nope', 503)]);

        $this->get(route('cinema.index'))
            ->assertOk()
            ->assertSee('temporarily unavailable');
    }
}
