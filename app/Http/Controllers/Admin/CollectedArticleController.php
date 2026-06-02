<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\CollectedArticle;
use App\Models\Tag;
use Illuminate\Http\Request;

class CollectedArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = CollectedArticle::with(['rssSource']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('rss_source_id', $request->source);
        }

        if ($request->filled('min_score')) {
            $query->where('relevance_score', '>=', $request->min_score);
        }

        $articles = $query->orderBy('relevance_score', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        $sources = \App\Models\RssSource::all(['id', 'name']);

        return view('admin.collected-articles.index', compact('articles', 'sources'));
    }

    public function show(CollectedArticle $collectedArticle)
    {
        return view('admin.collected-articles.show', compact('collectedArticle'));
    }

    public function approve(CollectedArticle $collectedArticle)
    {
        $collectedArticle->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Article approved');
    }

    public function reject(CollectedArticle $collectedArticle)
    {
        $collectedArticle->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Article rejected');
    }

    public function createBlogPost(CollectedArticle $collectedArticle)
    {
        if ($collectedArticle->blog_post_id) {
            return redirect()->route('admin.blog-posts.edit', $collectedArticle->blog_post_id)
                ->with('info', 'Blog post already exists for this article');
        }

        $categories = Category::active()->get();
        $tags = Tag::all();
        
        // Generate initial content
        $initialContent = $this->generateInitialContent($collectedArticle);

        return view('admin.collected-articles.create-blog-post', 
            compact('collectedArticle', 'categories', 'tags', 'initialContent'));
    }

    public function storeBlogPost(Request $request, CollectedArticle $collectedArticle)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'excerpt'          => 'required|string|max:500',
            'content'          => 'required|string',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string',
            'status'           => 'required|in:draft,published,archived',
            'published_at'     => 'nullable|date',
            'category_id'      => 'required|exists:categories,id',
            'tags'             => 'nullable|array',
            'curator_notes'    => 'nullable|string',
        ]);

        $validated['user_id']               = auth()->id();
        $validated['source_type']           = 'curated';
        $validated['original_url']          = $collectedArticle->url;
        $validated['original_author']       = $collectedArticle->author;
        $validated['original_publication']  = $collectedArticle->rssSource->name;
        $validated['original_published_at'] = $collectedArticle->published_at;

        // Handle meta keywords
        if ($validated['meta_keywords']) {
            $keywords                   = array_map('trim', explode(',', $validated['meta_keywords']));
            $validated['meta_keywords'] = array_filter($keywords);
        }

        // Calculate reading time
        $wordCount                 = str_word_count(strip_tags($validated['content']));
        $validated['reading_time'] = ceil($wordCount / 200);

        if ($validated['status'] === 'published' && ! $validated['published_at']) {
            $validated['published_at'] = now();
        }

        $blogPost = BlogPost::create($validated);

        if ($request->has('tags')) {
            $blogPost->tags()->sync($request->tags);
        }

        // Update collected article
        $collectedArticle->update([
            'blog_post_id'  => $blogPost->id,
            'status'        => 'published',
            'curator_notes' => $request->curator_notes,
        ]);

        return redirect()->route('admin.blog-posts.index')
            ->with('success', 'Blog post created from curated article');
    }

    private function generateInitialContent($collectedArticle)
    {
        $content = "I recently came across this insightful article and wanted to share my thoughts on it.\n\n";
        $content .= "## My Take\n\n";
        $content .= "[Add your commentary, insights, and perspective here]\n\n";
        $content .= "## Key Takeaways\n\n";
        $content .= "- [Your key point 1]\n";
        $content .= "- [Your key point 2]\n";
        $content .= "- [Your key point 3]\n\n";
        $content .= "## Why This Matters\n\n";
        $content .= "[Explain why this topic is relevant to your audience]\n\n";
        $content .= "---\n\n";
        $content .= "**Source Attribution:**\n";
        $content .= "Originally published by " . ($collectedArticle->author ?: 'the team') . " at " . $collectedArticle->rssSource->name . ".\n";
        $content .= "[Read the original article](" . $collectedArticle->url . ")";
        
        return $content;
    }
}
