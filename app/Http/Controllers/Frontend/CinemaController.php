<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Cinema\LocalCinemaFeedService;
use Illuminate\Http\Request;

class CinemaController extends Controller
{
    public function __construct(
        protected LocalCinemaFeedService $feed
    ) {}

    /**
     * Latest movies / TV shows from the Local Cinema feed.
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'movies');
        if (! array_key_exists($type, LocalCinemaFeedService::TYPES)) {
            $type = 'movies';
        }

        $items = $this->feed->items($type);
        $types = LocalCinemaFeedService::TYPES;

        return view('frontend.cinema.index', compact('items', 'type', 'types'));
    }
}
