<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SocialDraft;
use App\Models\SocialDraftTarget;
use App\Services\Social\ReelDraftService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Social Studio: reel drafts built from blog posts, reviewed and approved per
 * platform before anything is published.
 */
class SocialReelController extends Controller
{
    public function __construct(private ReelDraftService $reels) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $drafts = SocialDraft::with(['blogPost', 'targets'])
            ->where('kind', SocialDraft::KIND_REEL)
            ->when($status === 'review', fn ($q) => $q->needsReview())
            ->when($status && $status !== 'review', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Best-performing published articles that have no reel yet.
        $candidates = BlogPost::published()
            ->whereDoesntHave('socialDrafts', fn ($q) => $q->where('kind', SocialDraft::KIND_REEL))
            ->orderByDesc('views')
            ->take(8)
            ->get(['id', 'title', 'slug', 'views', 'published_at', 'source_type']);

        $needsReview = SocialDraft::where('kind', SocialDraft::KIND_REEL)->needsReview()->count();

        return view('admin.social.reels.index', compact('drafts', 'candidates', 'status', 'needsReview'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['blog_post_id' => 'required|exists:blog_posts,id']);

        $post  = BlogPost::findOrFail($validated['blog_post_id']);
        $draft = $this->reels->createForPost($post);

        if (! $draft) {
            return back()->with('error', 'Could not write a reel script. The AI service is off, over budget, or returned an error (see the log).');
        }

        return redirect()->route('admin.social.reels.show', $draft)
            ->with('success', 'Reel script written. Review the scenes and captions below.');
    }

    public function show(SocialDraft $draft)
    {
        $draft->load(['blogPost.category', 'targets']);

        return view('admin.social.reels.show', compact('draft'));
    }

    public function update(Request $request, SocialDraft $draft)
    {
        $validated = $request->validate([
            'scenes'                      => 'required|array|min:1|max:10',
            'scenes.*.type'               => 'required|in:hook,context,point,end',
            'scenes.*.on_screen_text'     => 'nullable|string|max:120',
            'scenes.*.voice_line'         => 'nullable|string|max:500',
            'scenes.*.duration_seconds'   => 'required|numeric|min:1|max:15',
            'scenes.*.visual'             => 'required|in:featured_image,portrait,screenshot,text,code',
            'scenes.*.visual_note'        => 'nullable|string|max:800',
            'instagram_caption'           => 'nullable|string|max:2200',
            'instagram_hashtags'          => 'nullable|string|max:500',
            'youtube_title'               => 'required|string|max:100',
            'youtube_description'         => 'nullable|string|max:5000',
            'youtube_tags'                => 'nullable|string|max:500',
        ]);

        $split = fn (?string $list) => preg_split('/[\s,]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY);

        $this->reels->saveEdits($draft, array_values($validated['scenes']), [
            'instagram' => [
                'caption'  => $validated['instagram_caption'] ?? '',
                'hashtags' => array_map(fn ($t) => ltrim($t, '#'), $split($validated['instagram_hashtags'] ?? '')),
            ],
            'youtube' => [
                'title'       => $validated['youtube_title'],
                'description' => $validated['youtube_description'] ?? '',
                'tags'        => array_map('trim', explode(',', (string) ($validated['youtube_tags'] ?? ''))),
            ],
        ]);

        return back()->with('success', 'Reel saved.');
    }

    public function regenerate(SocialDraft $draft)
    {
        return $this->reels->regenerate($draft)
            ? back()->with('success', 'Fresh script and captions written. Any approvals were reset.')
            : back()->with('error', 'Could not write a new script. The AI service is off, over budget, or returned an error.');
    }

    public function approveTarget(Request $request, SocialDraft $draft, SocialDraftTarget $target)
    {
        abort_unless($target->social_draft_id === $draft->id, 404);

        if (! $draft->isRendered()) {
            return back()->with('error', 'Render the reel and watch it before approving.');
        }

        $validated = $request->validate(['scheduled_for' => 'nullable|date|after_or_equal:today']);

        $this->reels->approveTarget(
            $target,
            ! empty($validated['scheduled_for']) ? Carbon::parse($validated['scheduled_for']) : null
        );

        return back()->with('success', "{$target->label()} approved.");
    }

    public function rejectTarget(SocialDraft $draft, SocialDraftTarget $target)
    {
        abort_unless($target->social_draft_id === $draft->id, 404);

        $this->reels->rejectTarget($target);

        return back()->with('success', "{$target->label()} rejected. It will not be posted.");
    }

    public function destroy(SocialDraft $draft)
    {
        $draft->delete();

        return redirect()->route('admin.social.reels.index')->with('success', 'Reel deleted.');
    }
}
