<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Runs after the response is sent to the browser, so geolocation and
     * DB writes never add latency to the page load.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldTrack($request, $response)) {
            $this->trackPageView($request);
        }
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        return $request->isMethod('GET') &&
               $response->getStatusCode() === 200 &&
               !$request->is('admin/*') &&
               !$request->is('api/*') &&
               !$request->ajax() &&
               !$this->isAssetRequest($request);
    }

    private function isAssetRequest(Request $request): bool
    {
        $path = $request->path();
        $extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
        
        foreach ($extensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }

        return false;
    }

    private function trackPageView(Request $request)
    {
        try {
            // Get or create visitor
            $visitor = $this->getOrCreateVisitor($request);
            
            // Create page view record
            $pageView = $this->createPageView($request, $visitor);
            
            // Update visitor stats
            $this->updateVisitorStats($visitor);
            
        } catch (\Exception $e) {
            // Log error but don't break the app
            \Log::error('Analytics tracking failed: ' . $e->getMessage());
        }
    }

    private function getOrCreateVisitor(Request $request): Visitor
    {
        $sessionId = session()->getId();
        
        $visitor = Visitor::where('session_id', $sessionId)->first();
        
        if (!$visitor) {
            $visitor = Visitor::createFromRequest($request);
        } else {
            // Update last activity
            $visitor->update([
                'last_activity_at' => now(),
            ]);
        }

        return $visitor;
    }

    private function createPageView(Request $request, Visitor $visitor): PageView
    {
        $url = $request->url();
        $pageInfo = $this->getPageInfo($request);
        
        return PageView::create([
            'visitor_id' => $visitor->id,
            'url' => $url,
            'page_title' => $pageInfo['title'],
            'page_type' => $pageInfo['type'],
            'content_type' => $pageInfo['content_type'],
            'content_id' => $pageInfo['content_id'],
            'method' => $request->method(),
        ]);
    }

    private function getPageInfo(Request $request): array
    {
        $path = $request->path();
        $routeName = $request->route()?->getName();
        
        $info = [
            'title' => null,
            'type' => 'page',
            'content_type' => null,
            'content_id' => null,
        ];

        // Determine page type and content
        if ($path === '/') {
            $info['type'] = 'home';
            $info['title'] = 'Home';
        } elseif (str_starts_with($path, 'about')) {
            $info['type'] = 'about';
            $info['title'] = 'About';
        } elseif (str_starts_with($path, 'contact')) {
            $info['type'] = 'contact';
            $info['title'] = 'Contact';
        } elseif (str_starts_with($path, 'portfolio')) {
            $info['type'] = 'portfolio';
            if ($routeName === 'portfolio.show' && $request->route('slug')) {
                $info['content_type'] = 'project';
                $project = \App\Models\Project::where('slug', $request->route('slug'))->first();
                if ($project) {
                    $info['content_id'] = $project->id;
                    $info['title'] = $project->title;
                }
            } else {
                $info['title'] = 'Portfolio';
            }
        } elseif (str_starts_with($path, 'blog')) {
            $info['type'] = 'blog';
            if ($routeName === 'blog.show' && $request->route('slug')) {
                $info['content_type'] = 'blog_post';
                $post = \App\Models\BlogPost::where('slug', $request->route('slug'))->first();
                if ($post) {
                    $info['content_id'] = $post->id;
                    $info['title'] = $post->title;
                }
            } else {
                $info['title'] = 'Blog';
            }
        }

        return $info;
    }

    private function updateVisitorStats(Visitor $visitor)
    {
        $visitor->increment('page_views');
        
        // Calculate session duration
        $sessionDuration = now()->diffInSeconds($visitor->first_visit_at);
        $visitor->update(['session_duration' => $sessionDuration]);
    }
}