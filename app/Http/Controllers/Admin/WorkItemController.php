<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Project;
use App\Models\WorkItem;
use App\Models\WorkItemVoice;
use Illuminate\Http\Request;

class WorkItemController extends Controller
{
    public function index()
    {
        $workItems = WorkItem::with('project')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.work-items.index', compact('workItems'));
    }

    public function create()
    {
        $projects = Project::orderBy('title')->get(['id', 'title']);
        return view('admin.work-items.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        WorkItem::create($data);

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item created.');
    }

    public function show(WorkItem $workItem)
    {
        $workItem->load(['project', 'voiceRecords.media']);
        $mediaOptions = Media::images()->latest()->take(200)->get(['id', 'file_name']);
        return view('admin.work-items.show', compact('workItem', 'mediaOptions'));
    }

    public function edit(WorkItem $workItem)
    {
        $projects = Project::orderBy('title')->get(['id', 'title']);
        return view('admin.work-items.edit', compact('workItem', 'projects'));
    }

    public function update(Request $request, WorkItem $workItem)
    {
        $data = $this->validateData($request);
        $workItem->update($data);

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item updated.');
    }

    public function destroy(WorkItem $workItem)
    {
        $workItem->delete();

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item deleted.');
    }

    /**
     * Generate a draft original article from a work item + chosen angle.
     */
    public function generateArticle(Request $request, WorkItem $workItem)
    {
        $angle = $request->input('angle');

        if (!in_array($angle, $workItem->article_angles ?? [], true)) {
            return back()->with('error', 'Please choose one of this work item\'s article angles.');
        }

        // Optional opening hook. Empty means "let the AI write a concrete unnamed scene."
        // Only a hook curated on this work item is allowed through (no free-text events).
        $hook = $request->input('hook');
        if ($hook !== null && $hook !== '' && !in_array($hook, $workItem->hooks ?? [], true)) {
            return back()->with('error', 'Please choose one of this work item\'s hooks, or none.');
        }

        // Selected approved voices (with their attached screenshots) to weave in.
        $voiceIds = array_filter((array) $request->input('voice_ids', []));
        $voices = $workItem->approvedVoices()->with('media')->whereIn('id', $voiceIds)->get();

        $result = app(\App\Services\AiContentService::class)->generateFromWorkItem($workItem, $angle, $hook ?: null, $voices);

        if (!$result || empty($result['content'])) {
            return back()->with('error', 'Article generation failed. Check the AI budget/key configuration and try again.');
        }

        $wordCount = str_word_count(strip_tags($result['content']));

        $post = \App\Models\BlogPost::create([
            'title'            => $result['title'] ?: ($workItem->name . ' article'),
            'excerpt'          => $result['excerpt'] ?: \Illuminate\Support\Str::limit(strip_tags($workItem->tagline ?? ''), 200),
            'content'          => $result['content'],
            'category_id'      => $workItem->blog_category_id,
            'user_id'          => auth()->id(),
            'status'           => 'draft',
            'source_type'      => 'original',
            'meta_title'       => \Illuminate\Support\Str::limit($result['title'] ?: $workItem->name, 60, ''),
            'meta_description' => \Illuminate\Support\Str::limit(strip_tags($workItem->tagline ?: $result['excerpt']), 155),
            'meta_keywords'    => !empty($workItem->target_keywords) ? $workItem->target_keywords : null,
            'reading_time'     => max(1, (int) ceil($wordCount / 200)),
        ]);

        return redirect()->route('admin.blog-posts.edit', $post)
            ->with('success', 'Draft generated. Review it, personalize it, and publish when ready.');
    }

    /**
     * Discover organic user-voice candidates via Claude's web search tool.
     */
    public function findVoices(WorkItem $workItem)
    {
        $result = app(\App\Services\AiContentService::class)->findVoices($workItem);

        if ($result === null) {
            return back()->with('error', 'Voice search failed. Check the AI budget/key and try again.');
        }

        $created = 0;
        foreach ($result['candidates'] as $c) {
            $exists = WorkItemVoice::where('work_item_id', $workItem->id)
                ->where('quote', $c['quote'])->exists();
            if ($exists) {
                continue;
            }
            $workItem->voiceRecords()->create([
                'quote'       => $c['quote'],
                'attribution' => $c['attribution'],
                'source_url'  => $c['source_url'],
                'status'      => 'candidate',
                'meta'        => ['note' => $c['note'] ?? null, 'from_search' => true],
            ]);
            $created++;
        }

        $msg = $created > 0
            ? "Found {$created} candidate voice(s) from {$result['searches']} search(es). Review, attach screenshots, and approve the ones you want."
            : "No new candidates found ({$result['searches']} search(es)). Try tuning the pain points or keywords.";

        return back()->with('success', $msg);
    }

    /**
     * Manually add an approved voice.
     */
    public function storeVoice(Request $request, WorkItem $workItem)
    {
        $data = $request->validate([
            'quote'       => 'required|string',
            'attribution' => 'nullable|string|max:255',
            'source_url'  => 'nullable|url|max:1000',
        ]);

        $workItem->voiceRecords()->create([
            'quote'       => $data['quote'],
            'attribution' => $data['attribution'] ?? null,
            'source_url'  => $data['source_url'] ?? null,
            'status'      => 'approved',
        ]);

        return back()->with('success', 'Voice added.');
    }

    /**
     * Update a voice: approve/unapprove, attach a screenshot, or edit fields.
     */
    public function updateVoice(Request $request, WorkItemVoice $voice)
    {
        $data = $request->validate([
            'status'      => 'nullable|in:candidate,approved',
            'media_id'    => 'nullable|exists:media,id',
            'quote'       => 'nullable|string',
            'attribution' => 'nullable|string|max:255',
            'source_url'  => 'nullable|url|max:1000',
        ]);

        $voice->fill(array_filter($data, fn($v) => $v !== null && $v !== ''));

        // Allow explicitly clearing the attached screenshot.
        if ($request->has('media_id') && $request->input('media_id') === '') {
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

    /**
     * Validate and normalize the form data, cleaning the list fields.
     */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:product,service,project,skill',
            'project_id'      => 'nullable|exists:projects,id',
            'blog_category_id' => 'nullable|exists:categories,id',
            'stories'         => 'nullable|string',
            'active'          => 'nullable|boolean',
            'sort_order'      => 'nullable|integer',
            'tagline'         => 'nullable|string|max:500',
            'target_audience' => 'nullable|string',
            'how_it_helps'    => 'nullable|string',
            'call_to_action'  => 'nullable|string',
            'tech_stack'      => 'nullable|string|max:500',
            'url'             => 'nullable|url|max:500',
            'notes'           => 'nullable|string',
            'pain_points'     => 'nullable|array',
            'objections'      => 'nullable|array',
            'key_outcomes'    => 'nullable|array',
            'proof_links'     => 'nullable|array',
            'differentiators' => 'nullable|array',
            'target_keywords' => 'nullable|array',
            'article_angles'  => 'nullable|array',
            'hooks'           => 'nullable|array',
            'screenshots'     => 'nullable|array',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['sort_order'] = (int) ($request->input('sort_order', 0));

        foreach (['pain_points', 'objections', 'key_outcomes', 'proof_links', 'differentiators', 'target_keywords', 'article_angles', 'hooks', 'screenshots'] as $field) {
            $validated[$field] = $this->cleanList($request->input($field, []));
        }

        return $validated;
    }

    /**
     * Drop empty entries and re-index a list of strings.
     */
    protected function cleanList($items): array
    {
        if (!is_array($items)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $items), fn($v) => $v !== ''));
    }
}
