<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Project;
use App\Models\SportMatch;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Http\Request;
use Storage;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = Sitemap::create();

        // Add static pages
        $sitemap
            ->add(Url::create(route('home'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(1.0))
            
            ->add(Url::create(route('about'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.8))
            
            ->add(Url::create(route('portfolio.index'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.9))
            
            ->add(Url::create(route('blog.index'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.9))
            
            ->add(Url::create(route('contact'))
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.7));

        // Add published projects
        Project::published()
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($projects) use ($sitemap) {
                foreach ($projects as $project) {
                    $sitemap->add(
                        Url::create(route('portfolio.show', $project->slug))
                            ->setLastModificationDate($project->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.8)
                    );
                }
            });

        // Add published blog posts
        BlogPost::published()
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $sitemap->add(
                        Url::create(route('blog.show', $post->slug))
                            ->setLastModificationDate($post->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                            ->setPriority(0.8)
                    );
                }
            });

        // Add portfolio categories
        Category::active()
            ->whereHas('projects', function ($query) {
                $query->where('is_published', true);
            })
            ->chunk(100, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    $sitemap->add(
                        Url::create(route('portfolio.category', $category->slug))
                            ->setLastModificationDate($category->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.7)
                    );
                }
            });

        // Add blog categories
        Category::active()
            ->whereHas('blogPosts', function ($query) {
                $query->where('status', 'published');
            })
            ->chunk(100, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    $sitemap->add(
                        Url::create(route('blog.category', $category->slug))
                            ->setLastModificationDate($category->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.7)
                    );
                }
            });

        // Add sports pages — only when the sports section is enabled
        if (FeatureFlag::enabled('page.sports')) {
            $sitemap->add(
                Url::create(route('sports.index'))
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                    ->setPriority(0.9)
            );

            // Add match pages (recent + upcoming)
            SportMatch::whereIn('status', ['live', 'completed', 'scheduled'])
                ->where('scheduled_at', '>=', now()->subDays(30))
                ->chunk(100, function ($matches) use ($sitemap) {
                    foreach ($matches as $match) {
                        $sitemap->add(
                            Url::create(route('sports.match', $match->slug))
                                ->setLastModificationDate($match->updated_at)
                                ->setChangeFrequency(
                                    $match->status === 'live'
                                        ? Url::CHANGE_FREQUENCY_ALWAYS
                                        : Url::CHANGE_FREQUENCY_DAILY
                                )
                                ->setPriority($match->status === 'live' ? 0.9 : 0.7)
                        );
                    }
                });
        }

        return $sitemap->toResponse(request());
    }

    public function generate()
    {
        $sitemap = Sitemap::create();

        // Add static pages with detailed metadata
        $staticPages = [
            [
                'url' => route('home'),
                'priority' => 1.0,
                'frequency' => Url::CHANGE_FREQUENCY_WEEKLY,
                'images' => []
            ],
            [
                'url' => route('about'),
                'priority' => 0.8,
                'frequency' => Url::CHANGE_FREQUENCY_MONTHLY,
                'images' => []
            ],
            [
                'url' => route('portfolio.index'),
                'priority' => 0.9,
                'frequency' => Url::CHANGE_FREQUENCY_WEEKLY,
                'images' => []
            ],
            [
                'url' => route('blog.index'),
                'priority' => 0.9,
                'frequency' => Url::CHANGE_FREQUENCY_DAILY,
                'images' => []
            ],
            [
                'url' => route('contact'),
                'priority' => 0.7,
                'frequency' => Url::CHANGE_FREQUENCY_MONTHLY,
                'images' => []
            ],
        ];

        foreach ($staticPages as $page) {
            $url = Url::create($page['url'])
                ->setLastModificationDate(now())
                ->setChangeFrequency($page['frequency'])
                ->setPriority($page['priority']);

            $sitemap->add($url);
        }

        // Add projects with images
        Project::published()
            ->with(['category'])
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($projects) use ($sitemap) {
                foreach ($projects as $project) {
                    $url = Url::create(route('portfolio.show', $project->slug))
                        ->setLastModificationDate($project->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.8);

                    // Add project image
                    if ($project->featured_image) {
                        $url->addImage(Storage::url($project->featured_image), $project->title);
                    }

                    $sitemap->add($url);
                }
            });

        // Add blog posts with images
        BlogPost::published()
            ->with(['category', 'user'])
            ->orderBy('updated_at', 'desc')
            ->chunk(100, function ($posts) use ($sitemap) {
                foreach ($posts as $post) {
                    $url = Url::create(route('blog.show', $post->slug))
                        ->setLastModificationDate($post->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.8);

                    // Add post image
                    if ($post->featured_image) {
                        $url->addImage(Storage::url($post->featured_image), $post->title);
                    }

                    $sitemap->add($url);
                }
            });

        // Add categories
        Category::active()
            ->where(function ($query) {
                $query->whereHas('projects', function ($q) {
                    $q->where('is_published', true);
                })->orWhereHas('blogPosts', function ($q) {
                    $q->where('status', 'published');
                });
            })
            ->chunk(100, function ($categories) use ($sitemap) {
                foreach ($categories as $category) {
                    // Portfolio category
                    if ($category->projects()->published()->exists()) {
                        $sitemap->add(
                            Url::create(route('portfolio.category', $category->slug))
                                ->setLastModificationDate($category->updated_at)
                                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                                ->setPriority(0.7)
                        );
                    }

                    // Blog category
                    if ($category->blogPosts()->published()->exists()) {
                        $sitemap->add(
                            Url::create(route('blog.category', $category->slug))
                                ->setLastModificationDate($category->updated_at)
                                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                                ->setPriority(0.7)
                        );
                    }
                }
            });

        // Add sports pages — only when the sports section is enabled
        if (FeatureFlag::enabled('page.sports')) {
            $sitemap->add(
                Url::create(route('sports.index'))
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                    ->setPriority(0.9)
            );

            SportMatch::whereIn('status', ['live', 'completed', 'scheduled'])
                ->where('scheduled_at', '>=', now()->subDays(30))
                ->chunk(100, function ($matches) use ($sitemap) {
                    foreach ($matches as $match) {
                        $sitemap->add(
                            Url::create(route('sports.match', $match->slug))
                                ->setLastModificationDate($match->updated_at)
                                ->setChangeFrequency(
                                    $match->status === 'live'
                                        ? Url::CHANGE_FREQUENCY_ALWAYS
                                        : Url::CHANGE_FREQUENCY_DAILY
                                )
                                ->setPriority($match->status === 'live' ? 0.9 : 0.7)
                        );
                    }
                });
        }

        // Save sitemap to public directory
        $sitemap->writeToFile(public_path('sitemap.xml'));

        return response()->json([
            'message' => 'Sitemap generated successfully!',
            'url' => url('/sitemap.xml'),
            'count' => count($sitemap->getTags())
        ]);
    }
}