<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Project;
use App\Models\Sport;
use App\Models\SportMatch;
use Illuminate\Http\Response;

class LlmsTxtController extends Controller
{
    /**
     * Generate the llms.txt file for AI crawlers.
     */
    public function index(): Response
    {
        $siteName = config('app.name', 'Portfolio');
        $siteUrl = config('app.url');

        $categories = Category::active()->get();
        $latestPosts = BlogPost::published()
            ->latest('published_at')
            ->take(20)
            ->get();

        $content = $this->generateLlmsTxt($siteName, $siteUrl, $categories, $latestPosts);

        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Generate the full llms.txt with more details.
     */
    public function full(): Response
    {
        $siteName = config('app.name', 'Portfolio');
        $siteUrl = config('app.url');

        $categories = Category::active()->get();
        $latestPosts = BlogPost::published()
            ->latest('published_at')
            ->take(50)
            ->get();
        $projects = Project::published()
            ->featured()
            ->take(10)
            ->get();

        $content = $this->generateFullLlmsTxt($siteName, $siteUrl, $categories, $latestPosts, $projects);

        return response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Generate the standard llms.txt content.
     */
    protected function generateLlmsTxt(string $siteName, string $siteUrl, $categories, $latestPosts): string
    {
        $content = "# {$siteName}\n\n";
        $content .= "> Personal brand platform covering AI trends, web development, programming, and technology news.\n\n";

        // About section
        $content .= "## About\n\n";
        $content .= "- [About Me]({$siteUrl}/about): Background, skills, and professional experience\n";
        $content .= "- [Portfolio]({$siteUrl}/portfolio): Featured projects and work\n";
        $content .= "- [Contact]({$siteUrl}/contact): Get in touch\n\n";

        // Blog categories
        $content .= "## Blog Categories\n\n";
        foreach ($categories as $category) {
            $categoryUrl = "{$siteUrl}/blog/category/{$category->slug}";
            $content .= "- [{$category->name}]({$categoryUrl}): {$category->description}\n";
        }
        $content .= "\n";

        // Latest articles
        $content .= "## Latest Articles\n\n";
        foreach ($latestPosts as $post) {
            $postUrl = "{$siteUrl}/blog/{$post->slug}";
            $date = $post->published_at->format('Y-m-d');
            $content .= "- [{$post->title}]({$postUrl}) ({$date})\n";
        }
        $content .= "\n";

        // Feeds
        $content .= "## Feeds\n\n";
        $content .= "- [RSS Feed]({$siteUrl}/blog/feed): Subscribe to latest articles\n";
        $content .= "- [Sitemap]({$siteUrl}/sitemap.xml): Full site structure\n\n";

        // Live Sports Scores
        $activeSports = Sport::active()->orderBy('sort_order')->get();
        if ($activeSports->isNotEmpty()) {
            $content .= "## Live Sports Scores\n\n";
            $content .= "- [Sports Hub]({$siteUrl}/sports): Live scores, upcoming matches, and results\n";
            foreach ($activeSports as $sport) {
                $content .= "- [{$sport->name} Scores]({$siteUrl}/sports/{$sport->slug}): Live {$sport->name} scores and fixtures\n";
            }
            $content .= "\n";
        }

        // Topics covered
        $content .= "## Topics Covered\n\n";
        $content .= "- Artificial Intelligence and Machine Learning\n";
        $content .= "- Web Development (Frontend & Backend)\n";
        $content .= "- Programming Languages and Best Practices\n";
        $content .= "- Technology News and Industry Trends\n";
        $content .= "- DevOps and Cloud Computing\n";
        $content .= "- UI/UX Design\n";
        $content .= "- Career Development for Tech Professionals\n";
        $content .= "- Live Sports Scores and Match Coverage\n";

        return $content;
    }

    /**
     * Generate the full llms.txt with extended details.
     */
    protected function generateFullLlmsTxt(string $siteName, string $siteUrl, $categories, $latestPosts, $projects): string
    {
        $content = "# {$siteName} - Full Content Guide\n\n";
        $content .= "> Comprehensive guide to all content on this personal brand platform.\n\n";

        // Detailed about section
        $content .= "## About This Site\n\n";
        $content .= "This is a personal brand platform focused on technology, web development, and AI. ";
        $content .= "The site features original analysis and curated content from leading tech publications.\n\n";

        $content .= "### Key Pages\n\n";
        $content .= "- [Homepage]({$siteUrl}/): Featured projects, skills, and latest articles\n";
        $content .= "- [About]({$siteUrl}/about): Professional background and expertise\n";
        $content .= "- [Portfolio]({$siteUrl}/portfolio): Project showcase\n";
        $content .= "- [Blog]({$siteUrl}/blog): Technical articles and analysis\n";
        $content .= "- [Contact]({$siteUrl}/contact): Contact form\n\n";

        // Categories with descriptions
        $content .= "## Content Categories\n\n";
        foreach ($categories as $category) {
            $categoryUrl = "{$siteUrl}/blog/category/{$category->slug}";
            $content .= "### [{$category->name}]({$categoryUrl})\n\n";
            $content .= "{$category->description}\n\n";
        }

        // Featured projects
        if ($projects->isNotEmpty()) {
            $content .= "## Featured Projects\n\n";
            foreach ($projects as $project) {
                $projectUrl = "{$siteUrl}/portfolio/{$project->slug}";
                $content .= "### [{$project->title}]({$projectUrl})\n\n";
                if ($project->short_description) {
                    $content .= "{$project->short_description}\n\n";
                }
            }
        }

        // Latest articles with excerpts
        $content .= "## Recent Articles\n\n";
        foreach ($latestPosts as $post) {
            $postUrl = "{$siteUrl}/blog/{$post->slug}";
            $date = $post->published_at->format('F j, Y');
            $category = $post->category?->name ?? 'General';

            $content .= "### [{$post->title}]({$postUrl})\n\n";
            $content .= "**Published:** {$date} | **Category:** {$category}\n\n";

            if ($post->excerpt) {
                $content .= "{$post->excerpt}\n\n";
            }
        }

        // Live Sports
        $activeSports = Sport::active()->orderBy('sort_order')->get();
        if ($activeSports->isNotEmpty()) {
            $content .= "## Live Sports Scores\n\n";
            $content .= "Real-time scores across multiple sports including cricket, football, basketball, and tennis.\n\n";
            $content .= "- [Sports Hub]({$siteUrl}/sports): All live scores and upcoming matches\n";
            foreach ($activeSports as $sport) {
                $content .= "- [{$sport->name}]({$siteUrl}/sports/{$sport->slug}): Live {$sport->name} scores, fixtures, and results\n";
            }

            // Recent live/completed matches
            $recentMatches = SportMatch::whereIn('status', ['live', 'completed'])
                ->with('sport')
                ->latest('updated_at')
                ->take(10)
                ->get();

            if ($recentMatches->isNotEmpty()) {
                $content .= "\n### Recent Matches\n\n";
                foreach ($recentMatches as $match) {
                    $matchUrl = "{$siteUrl}/sports/{$match->sport->slug}/{$match->slug}";
                    $status = $match->status === 'live' ? 'LIVE' : 'Completed';
                    $content .= "- [{$match->title}]({$matchUrl}) ({$status})\n";
                }
            }
            $content .= "\n";
        }

        // Feeds and data
        $content .= "## Data & Feeds\n\n";
        $content .= "- [RSS Feed]({$siteUrl}/blog/feed): Latest articles in RSS format\n";
        $content .= "- [Sitemap]({$siteUrl}/sitemap.xml): Complete site structure\n";
        $content .= "- [llms.txt]({$siteUrl}/llms.txt): Simplified content guide\n\n";

        // Contact info
        $content .= "## Contact\n\n";
        $content .= "For inquiries, please use the [contact form]({$siteUrl}/contact).\n";

        return $content;
    }
}
