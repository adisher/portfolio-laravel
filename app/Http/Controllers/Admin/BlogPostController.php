<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['category', 'user']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            });
        }
        
        $posts = $query->latest()->paginate(15);
        
        return view('admin.blog-posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::active()->forBlog()->get();
        $tags       = Tag::all();

        return view('admin.blog-posts.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string', // Changed from array to string
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'source_type' => 'nullable|in:original,curated',
            'original_url' => 'nullable|url',
            'original_author' => 'nullable|string|max:255',
            'original_publication' => 'nullable|string|max:255',
            'original_published_at' => 'nullable|date',
            'curator_notes' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();
        
        // Handle meta keywords
        if ($validated['meta_keywords']) {
            $keywords = array_map('trim', explode(',', $validated['meta_keywords']));
            $validated['meta_keywords'] = array_filter($keywords);
        }
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }
        
        // Calculate reading time
        $wordCount = str_word_count(strip_tags($validated['content']));
        $validated['reading_time'] = ceil($wordCount / 200);
        
        if ($validated['status'] === 'published' && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        $post = BlogPost::create($validated);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        $this->ensureFeaturedImage($post);

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post created successfully');
    }

    /**
     * When a post is published without a featured image, source a relevant
     * one from Pexels (same as the curated pipeline). Silent on failure.
     */
    protected function ensureFeaturedImage(BlogPost $post): void
    {
        if ($post->status !== 'published' || !empty($post->featured_image)) {
            return;
        }

        $image = app(\App\Services\PexelsImageService::class)->fetchForBlogPost($post);
        if ($image) {
            $post->update(['featured_image' => $image]);
        }
    }

    public function show(BlogPost $blogPost)
    {
        return view('admin.blog-posts.show', compact('blogPost'));
    }

    public function edit(BlogPost $blogPost)
    {
        $categories = Category::active()->forBlog()->get();
        $tags       = Tag::all();

        return view('admin.blog-posts.edit', compact('blogPost', 'categories', 'tags'));
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string', // Changed from array to string
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'source_type' => 'nullable|in:original,curated',
            'original_url' => 'nullable|url',
            'original_author' => 'nullable|string|max:255',
            'original_publication' => 'nullable|string|max:255',
            'original_published_at' => 'nullable|date',
            'curator_notes' => 'nullable|string',
        ]);

        // Handle meta keywords
        if ($validated['meta_keywords']) {
            $keywords = array_map('trim', explode(',', $validated['meta_keywords']));
            $validated['meta_keywords'] = array_filter($keywords);
        }

        if ($request->hasFile('featured_image')) {
            if ($blogPost->featured_image) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }
        
        // Calculate reading time
        $wordCount = str_word_count(strip_tags($validated['content']));
        $validated['reading_time'] = ceil($wordCount / 200);
        
        if ($validated['status'] === 'published' && !$blogPost->published_at && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        $blogPost->update($validated);

        if ($request->has('tags')) {
            $blogPost->tags()->sync($request->tags);
        }

        $this->ensureFeaturedImage($blogPost->refresh());

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post updated successfully');
    }

    public function destroy(BlogPost $blogPost)
    {
        if ($blogPost->featured_image) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('success', 'Blog post deleted successfully');
    }
}
