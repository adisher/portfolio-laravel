<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RssSource;
use App\Services\RssFeedService;
use Illuminate\Http\Request;

class RssSourceController extends Controller
{
    public function index()
    {
        $sources = RssSource::withCount('collectedArticles')
            ->orderBy('priority', 'desc')
            ->paginate(15);

        return view('admin.rss-sources.index', compact('sources'));
    }

    public function create()
    {
        return view('admin.rss-sources.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'url'             => 'required|url|unique:rss_sources,url',
            'category'        => 'required|string|max:100',
            'priority'        => 'required|integer|min:1|max:10',
            'fetch_frequency' => 'required|integer|min:15|max:1440',
            'active'          => 'boolean',
        ]);

        $validated['active'] = $request->has('active');

        RssSource::create($validated);

        return redirect()->route('admin.rss-sources.index')
            ->with('success', 'RSS source created successfully');
    }

    public function show(RssSource $rssSource)
    {
        $rssSource->load(['collectedArticles' => function ($query) {
            $query->latest()->take(20);
        }]);

        return view('admin.rss-sources.show', compact('rssSource'));
    }

    public function edit(RssSource $rssSource)
    {
        return view('admin.rss-sources.edit', compact('rssSource'));
    }

    public function update(Request $request, RssSource $rssSource)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'url'             => 'required|url|unique:rss_sources,url,' . $rssSource->id,
            'category'        => 'required|string|max:100',
            'priority'        => 'required|integer|min:1|max:10',
            'fetch_frequency' => 'required|integer|min:15|max:1440',
            'active'          => 'boolean',
        ]);

        $validated['active'] = $request->has('active');

        $rssSource->update($validated);

        return redirect()->route('admin.rss-sources.index')
            ->with('success', 'RSS source updated successfully');
    }

    public function destroy(RssSource $rssSource)
    {
        $rssSource->delete();

        return redirect()->route('admin.rss-sources.index')
            ->with('success', 'RSS source deleted successfully');
    }

    public function fetch(RssSource $rssSource, RssFeedService $feedService)
    {
        $collected = $feedService->fetchSource($rssSource);

        return redirect()->back()
            ->with('success', "Collected {$collected} new articles from {$rssSource->name}");
    }

    public function fetchAll(RssFeedService $feedService)
    {
        $collected = $feedService->fetchAllSources();

        return redirect()->back()
            ->with('success', "Collected {$collected} new articles from all sources");
    }
}
