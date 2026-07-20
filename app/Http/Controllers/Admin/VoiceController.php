<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\WorkItem;
use App\Models\WorkItemVoice;
use App\Services\AiContentService;
use App\Services\BraveSearchService;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    /**
     * Hub: every work item with its voice stats.
     */
    public function index()
    {
        $workItems = WorkItem::withCount([
            'voiceRecords',
            'voiceRecords as approved_count'   => fn ($q) => $q->where('status', 'approved'),
            'voiceRecords as candidate_count'  => fn ($q) => $q->where('status', 'candidate'),
            'voiceRecords as screenshot_count' => fn ($q) => $q->whereNotNull('media_id'),
        ])->orderBy('sort_order')->orderBy('name')->get();

        $braveConfigured = app(BraveSearchService::class)->isConfigured();

        return view('admin.voices.index', compact('workItems', 'braveConfigured'));
    }

    /**
     * Workspace: find + review + approve voices for one work item.
     */
    public function show(WorkItem $workItem)
    {
        $workItem->load('voiceRecords.media');
        $runs = $workItem->voiceSearchRuns()->latest()->limit(10)->get();
        $braveConfigured = app(BraveSearchService::class)->isConfigured();

        return view('admin.voices.show', compact('workItem', 'runs', 'braveConfigured'));
    }

    /**
     * Run a search (Brave or Claude), store candidates + a run log. AJAX-friendly.
     */
    public function find(Request $request, WorkItem $workItem)
    {
        // Claude is the default engine; Brave is an optional paid alternative.
        $engine = $request->input('engine') === 'brave' ? 'brave' : 'claude';

        $result = $engine === 'claude'
            ? app(AiContentService::class)->findVoices($workItem)
            : app(BraveSearchService::class)->findVoices($workItem);

        // Create candidate records, skipping ones we already have (by quote or url).
        $created = 0;
        foreach ($result['candidates'] as $c) {
            $dupe = $workItem->voiceRecords()
                ->where(function ($q) use ($c) {
                    $q->where('quote', $c['quote']);
                    if (!empty($c['source_url'])) {
                        $q->orWhere('source_url', $c['source_url']);
                    }
                })->exists();
            if ($dupe) {
                continue;
            }
            $workItem->voiceRecords()->create([
                'quote'       => $c['quote'],
                'attribution' => $c['attribution'] ?? null,
                'source_url'  => $c['source_url'] ?? null,
                'status'      => 'candidate',
                'meta'        => [
                    'note'       => $c['note'] ?? null,
                    'confidence' => $c['confidence'] ?? null,
                    'engine'     => $engine,
                ],
            ]);
            $created++;
        }

        $status = !($result['ok'] ?? false)
            ? 'failed'
            : (empty($result['candidates']) ? 'empty' : 'success');

        $run = $workItem->voiceSearchRuns()->create([
            'engine'           => $engine,
            'queries'          => $result['queries'] ?? [],
            'candidates_found' => $created,
            'status'           => $status,
            'cost_usd'         => $result['cost'] ?? 0,
            'raw'              => is_string($result['raw'] ?? null)
                ? $result['raw']
                : json_encode($result['raw'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'note'             => $result['note'] ?? null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            $workItem->load('voiceRecords.media');
            return response()->json([
                'ok'      => $result['ok'] ?? false,
                'status'  => $status,
                'created' => $created,
                'note'    => $result['note'] ?? null,
                'listsHtml' => view('admin.voices._lists', compact('workItem'))->render(),
                'runHtml'   => view('admin.voices._run', ['run' => $run])->render(),
            ]);
        }

        return back()->with('success', "Search complete ({$engine}): {$created} new candidate(s).");
    }

    public function storeVoice(Request $request, WorkItem $workItem)
    {
        $data = $request->validate([
            'quote'       => 'required|string',
            'attribution' => 'nullable|string|max:255',
            'source_url'  => 'nullable|url|max:1000',
            'screenshot'  => 'nullable|image|max:8192',
        ]);

        // Screenshot can be pasted straight into the add form, so it's one save.
        $mediaId = null;
        if ($request->hasFile('screenshot')) {
            $mediaId = Media::uploadFile($request->file('screenshot'), '/voices')->id;
        }

        $workItem->voiceRecords()->create([
            'quote'       => $data['quote'],
            'attribution' => $data['attribution'] ?? null,
            'source_url'  => $data['source_url'] ?? null,
            'media_id'    => $mediaId,
            'status'      => 'approved',
            'meta'        => ['added_manually' => true],
        ]);

        return back()->with('success', 'Voice added.' . ($mediaId ? ' Screenshot attached.' : ''));
    }

    public function updateVoice(Request $request, WorkItemVoice $voice)
    {
        $data = $request->validate([
            'status'            => 'nullable|in:candidate,approved',
            'quote'             => 'nullable|string',
            'attribution'       => 'nullable|string|max:255',
            'source_url'        => 'nullable|url|max:1000',
            'screenshot'        => 'nullable|image|max:8192',
            'remove_screenshot' => 'nullable|boolean',
        ]);

        $voice->fill(array_filter([
            'status'      => $data['status'] ?? null,
            'quote'       => $data['quote'] ?? null,
            'attribution' => $data['attribution'] ?? null,
            'source_url'  => $data['source_url'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($request->hasFile('screenshot')) {
            $media = Media::uploadFile($request->file('screenshot'), '/voices');
            $voice->media_id = $media->id;
        } elseif ($request->boolean('remove_screenshot')) {
            $voice->media_id = null;
        }

        $voice->save();

        return back()->with('success', 'Voice updated.');
    }

    public function destroyVoice(WorkItemVoice $voice)
    {
        $voice->delete();
        return back()->with('success', 'Voice removed.');
    }
}
