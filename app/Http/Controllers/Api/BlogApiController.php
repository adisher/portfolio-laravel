<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogArticleResource;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only JSON API over published blog articles, consumed by external
 * automation (currently n8n) to fan articles out to social platforms.
 *
 * All endpoints are guarded by the VerifyBlogApiToken middleware and only
 * ever expose posts that are published and past their publish time.
 */
class BlogApiController extends Controller
{
    /**
     * GET /api/blog/articles
     *
     * Paginated list of published articles, newest first. Supports:
     *   - since={ISO-8601}  only articles published after this timestamp
     *   - category={slug}   restrict to one blog category
     *   - per_page={n}      page size (capped by config)
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', config('blog_api.per_page'));
        $perPage = max(1, min($perPage, (int) config('blog_api.max_per_page')));

        $query = BlogPost::published()
            ->with(['category', 'user', 'tags'])
            ->latest('published_at');

        if ($since = $request->query('since')) {
            try {
                $query->where('published_at', '>', now()->parse($since));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Invalid "since" timestamp. Use ISO-8601, e.g. 2026-08-11T09:00:00Z.',
                ], 422);
            }
        }

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        // ?posted=0 → only the queue (unposted); ?posted=1 → only already posted.
        if ($request->has('posted')) {
            $request->boolean('posted') ? $query->posted() : $query->notPosted();
        }

        return BlogArticleResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/blog/articles/next
     *
     * Atomically claims the next article in the posting queue: the oldest
     * published article that has not been posted yet. Marks it posted in the
     * same transaction and returns it, so two runs in a row hand out two
     * different articles and nothing is ever posted twice.
     *
     * Optional body/query `via` records the destination (e.g. "n8n").
     * Returns 404 once the queue is drained.
     */
    public function next(Request $request)
    {
        $via = $request->input('via');

        $article = DB::transaction(function () use ($via) {
            $post = BlogPost::published()
                ->notPosted()
                ->oldest('published_at')
                ->lockForUpdate()
                ->first();

            if (! $post) {
                return null;
            }

            $post->forceFill([
                'posted_at'  => now(),
                'posted_via' => $via,
            ])->save();

            return $post;
        });

        if (! $article) {
            return response()->json(['message' => 'Posting queue is empty. All published articles have been posted.'], 404);
        }

        $article->load(['category', 'user', 'tags']);

        return new BlogArticleResource($article);
    }

    /**
     * POST /api/blog/articles/{slug}/mark-posted
     *
     * Idempotently marks one article as posted without consuming the queue.
     * Use this for the two-step pattern (read then confirm) or manual fixes.
     */
    public function markPosted(Request $request, string $slug)
    {
        $article = BlogPost::where('slug', $slug)->first();

        if (! $article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        if ($article->posted_at === null) {
            $article->forceFill([
                'posted_at'  => now(),
                'posted_via' => $request->input('via'),
            ])->save();
        }

        $article->load(['category', 'user', 'tags']);

        return new BlogArticleResource($article);
    }

    /**
     * POST /api/blog/articles/{slug}/unmark
     *
     * Puts an article back into the posting queue (clears posted tracking).
     * Handy while testing the n8n flow so the same article can be reused.
     */
    public function unmark(string $slug)
    {
        $article = BlogPost::where('slug', $slug)->first();

        if (! $article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        $article->forceFill([
            'posted_at'  => null,
            'posted_via' => null,
        ])->save();

        $article->load(['category', 'user', 'tags']);

        return new BlogArticleResource($article);
    }

    /**
     * GET /api/blog/articles/latest
     *
     * The single newest published article: the payload a daily n8n schedule
     * polls to decide what to post. 404 when nothing is published yet.
     */
    public function latest()
    {
        $article = BlogPost::published()
            ->with(['category', 'user', 'tags'])
            ->latest('published_at')
            ->first();

        if (! $article) {
            return response()->json(['message' => 'No published articles found.'], 404);
        }

        return new BlogArticleResource($article);
    }

    /**
     * GET /api/blog/articles/{slug}
     *
     * Full detail for one published article by slug.
     */
    public function show(string $slug)
    {
        $article = BlogPost::published()
            ->with(['category', 'user', 'tags'])
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            return response()->json(['message' => 'Article not found.'], 404);
        }

        return new BlogArticleResource($article);
    }
}
