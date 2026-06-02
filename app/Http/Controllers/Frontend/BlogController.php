<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::published()
            ->with(['category', 'tags', 'user'])
            ->latest('published_at')
            ->paginate(10);

        $categories = Category::active()
            ->withCount('blogPosts')
            ->get();

        $popularTags = Tag::withCount('blogPosts')
            ->orderBy('blog_posts_count', 'desc')
            ->take(10)
            ->get();

        return view('frontend.blog', compact('posts', 'categories', 'popularTags'));
    }

    public function show($slug)
    {
        $post = BlogPost::published()
            ->with(['category', 'tags', 'user'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $post->increment('views');

        $relatedPosts = BlogPost::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->take(3)
            ->get();

        return view('frontend.blog-detail', compact('post', 'relatedPosts'));
    }

    public function category($slug)
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();
        
        $posts = BlogPost::published()
            ->where('category_id', $category->id)
            ->with(['category', 'tags', 'user'])
            ->latest('published_at')
            ->paginate(10);

        return view('frontend.blog-category', compact('category', 'posts'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->get('q');
        $startTime = microtime(true);

        // Search in title, excerpt, and content
        $posts = BlogPost::published()
            ->with(['category', 'tags', 'user'])
            ->where(function($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('excerpt', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%')
                  ->orWhere('meta_keywords', 'like', '%' . $query . '%');
            })
            ->latest('published_at')
            ->paginate(10);

        // Calculate search time
        $searchTime = microtime(true) - $startTime;

        // Add highlighting to search results
        $posts->getCollection()->transform(function ($post) use ($query) {
            $post->highlighted_title = $this->highlightSearchTerm($post->title, $query);
            $post->highlighted_excerpt = $this->highlightSearchTerm($post->excerpt, $query);
            
            // Find content snippet that contains search term
            $post->highlighted_content = $this->getContentSnippet($post->content, $query);
            
            return $post;
        });

        // Get sidebar data
        $categories = Category::active()
            ->withCount('blogPosts')
            ->orderBy('blog_posts_count', 'desc')
            ->get();

        $popularTags = Tag::withCount('blogPosts')
            ->orderBy('blog_posts_count', 'desc')
            ->take(10)
            ->get();

        $recentPosts = BlogPost::published()
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('frontend.blog-search', compact(
            'posts', 
            'query', 
            'searchTime', 
            'categories', 
            'popularTags', 
            'recentPosts'
        ));
    }

    private function highlightSearchTerm($text, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $text;
        }

        $highlighted = preg_replace(
            '/(' . preg_quote($searchTerm, '/') . ')/i',
            '<mark class="bg-yellow-200 dark:bg-yellow-600 px-1 rounded">$1</mark>',
            $text
        );

        return $highlighted ?: $text;
    }

    private function getContentSnippet($content, $searchTerm, $snippetLength = 200)
    {
        if (empty($searchTerm)) {
            return null;
        }

        $position = stripos($content, $searchTerm);
        if ($position === false) {
            return null;
        }

        $start = max(0, $position - 100);
        $snippet = substr($content, $start, $snippetLength);
        
        // Clean up the snippet
        $snippet = strip_tags($snippet);
        
        // Add ellipsis if we're not at the beginning/end
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if (strlen($content) > $start + $snippetLength) {
            $snippet = $snippet . '...';
        }

        return $this->highlightSearchTerm($snippet, $searchTerm);
    }
}