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
                'url' => 'https://localcinema.xyz/watch/change-623866',
                'title' => 'Change (2014)',
                'content_text' => 'Watch Change (2014) online on Local Cinema.',
                'image' => null,
            ],
        ]))]);

        $response = $this->get(route('cinema.index'));

        $response->assertOk()
            ->assertSee('Saint &amp; the Sea', false)
            ->assertSee('2026')
            ->assertSee('https://image.tmdb.org/t/p/w780/poster.jpg', false)
            ->assertSee('https://localcinema.xyz/watch/change-623866', false)
            ->assertDontSee('Watch Change (2014) online on Local Cinema.');

        Http::assertSent(fn ($request) => $request['type'] === 'movies' && $request['limit'] == 12);
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
            ['url' => 'https://localcinema.xyz/watch/ok-1', 'title' => 'Fine (2020)', 'image' => 'javascript:alert(2)'],
        ]))]);

        $this->get(route('cinema.index'))
            ->assertOk()
            ->assertDontSee('Evil')
            ->assertDontSee('javascript:', false)
            ->assertSee('Fine');
    }

    public function test_remote_failure_serves_last_good_copy(): void
    {
        Http::fake(['localcinema.xyz/*' => Http::sequence()
            ->push($this->feed([['url' => 'https://localcinema.xyz/watch/raja-1', 'title' => 'Raja (1990)']]))
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
