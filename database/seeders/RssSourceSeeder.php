<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\RssSource;
use Illuminate\Database\Seeder;

class RssSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $categories = Category::pluck('id', 'slug')->toArray();

        $sources = [
            // AI & Machine Learning
            [
                'name' => 'OpenAI Blog',
                'url' => 'https://openai.com/blog/rss.xml',
                'category' => 'ai',
                'target_category_id' => $categories['ai-machine-learning'] ?? null,
                'priority' => 10,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'Google AI Blog',
                'url' => 'https://blog.google/technology/ai/rss/',
                'category' => 'ai',
                'target_category_id' => $categories['ai-machine-learning'] ?? null,
                'priority' => 10,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'MIT Technology Review - AI',
                'url' => 'https://www.technologyreview.com/topic/artificial-intelligence/feed',
                'category' => 'ai',
                'target_category_id' => $categories['ai-machine-learning'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Towards Data Science',
                'url' => 'https://towardsdatascience.com/feed',
                'category' => 'ai',
                'target_category_id' => $categories['ai-machine-learning'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'The Batch - DeepLearning.AI',
                'url' => 'https://www.deeplearning.ai/the-batch/feed/',
                'category' => 'ai',
                'target_category_id' => $categories['ai-machine-learning'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],

            // Web Development
            [
                'name' => 'CSS-Tricks',
                'url' => 'https://css-tricks.com/feed/',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Smashing Magazine',
                'url' => 'https://www.smashingmagazine.com/feed/',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Dev.to',
                'url' => 'https://dev.to/feed',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 75,
            ],
            [
                'name' => 'Web.dev (Google)',
                'url' => 'https://web.dev/feed.xml',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
            [
                'name' => 'JavaScript Weekly',
                'url' => 'https://javascriptweekly.com/rss/',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'MDN Blog',
                'url' => 'https://developer.mozilla.org/en-US/blog/rss.xml',
                'category' => 'web',
                'target_category_id' => $categories['web-development'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],

            // Tech News
            [
                'name' => 'TechCrunch',
                'url' => 'https://techcrunch.com/feed/',
                'category' => 'tech',
                'target_category_id' => $categories['tech-news'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 15,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 75,
            ],
            [
                'name' => 'Ars Technica',
                'url' => 'https://feeds.arstechnica.com/arstechnica/index',
                'category' => 'tech',
                'target_category_id' => $categories['tech-news'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'The Verge',
                'url' => 'https://www.theverge.com/rss/index.xml',
                'category' => 'tech',
                'target_category_id' => $categories['tech-news'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 75,
            ],
            [
                'name' => 'Wired',
                'url' => 'https://www.wired.com/feed/rss',
                'category' => 'tech',
                'target_category_id' => $categories['tech-news'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 75,
            ],
            [
                'name' => 'Hacker News - Front Page',
                'url' => 'https://hnrss.org/frontpage',
                'category' => 'tech',
                'target_category_id' => $categories['tech-news'] ?? null,
                'priority' => 7,
                'fetch_frequency' => 30,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 80,
            ],

            // Programming
            [
                'name' => 'Laravel News',
                'url' => 'https://laravel-news.com/feed',
                'category' => 'dev',
                'target_category_id' => $categories['programming'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
            [
                'name' => 'Real Python',
                'url' => 'https://realpython.com/atom.xml',
                'category' => 'dev',
                'target_category_id' => $categories['programming'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Martin Fowler',
                'url' => 'https://martinfowler.com/feed.atom',
                'category' => 'dev',
                'target_category_id' => $categories['programming'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 240,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
            [
                'name' => 'InfoQ',
                'url' => 'https://feed.infoq.com/',
                'category' => 'dev',
                'target_category_id' => $categories['programming'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'The Pragmatic Engineer',
                'url' => 'https://newsletter.pragmaticengineer.com/feed',
                'category' => 'dev',
                'target_category_id' => $categories['programming'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],

            // DevOps & Cloud
            [
                'name' => 'AWS Blog',
                'url' => 'https://aws.amazon.com/blogs/aws/feed/',
                'category' => 'devops',
                'target_category_id' => $categories['devops-cloud'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Docker Blog',
                'url' => 'https://www.docker.com/blog/feed/',
                'category' => 'devops',
                'target_category_id' => $categories['devops-cloud'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],
            [
                'name' => 'Kubernetes Blog',
                'url' => 'https://kubernetes.io/feed.xml',
                'category' => 'devops',
                'target_category_id' => $categories['devops-cloud'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 65,
            ],

            // Design & UX
            [
                'name' => 'A List Apart',
                'url' => 'https://alistapart.com/main/feed/',
                'category' => 'design',
                'target_category_id' => $categories['design-ux'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
            [
                'name' => 'UX Collective',
                'url' => 'https://uxdesign.cc/feed',
                'category' => 'design',
                'target_category_id' => $categories['design-ux'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 60,
                'active' => true,
                'auto_publish' => false,
                'min_quality_score' => 70,
            ],
            [
                'name' => 'Nielsen Norman Group',
                'url' => 'https://www.nngroup.com/feed/rss/',
                'category' => 'design',
                'target_category_id' => $categories['design-ux'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 120,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],

            // Career & Growth
            [
                'name' => 'Coding Horror',
                'url' => 'https://blog.codinghorror.com/rss/',
                'category' => 'career',
                'target_category_id' => $categories['career-growth'] ?? null,
                'priority' => 9,
                'fetch_frequency' => 240,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
            [
                'name' => 'Joel on Software',
                'url' => 'https://www.joelonsoftware.com/feed/',
                'category' => 'career',
                'target_category_id' => $categories['career-growth'] ?? null,
                'priority' => 8,
                'fetch_frequency' => 240,
                'active' => true,
                'auto_publish' => true,
                'min_quality_score' => 60,
            ],
        ];

        foreach ($sources as $sourceData) {
            RssSource::updateOrCreate(
                ['url' => $sourceData['url']],
                $sourceData
            );
        }

        $this->command->info('RSS sources seeded successfully! Total: ' . count($sources) . ' sources');
    }
}
