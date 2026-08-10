<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Console\Command;

class AutoTagPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:auto-tag
                           {--apply : Actually write tag assignments instead of just previewing}
                           {--min=3 : Minimum number of articles a term must match before it becomes a tag}
                           {--max-tags=20 : Cap on how many distinct tags get created/used, to avoid fragmenting into tiny tags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-tag published posts by scanning content for a curated topic vocabulary, keeping only terms with enough article coverage to be a useful tag';

    /**
     * canonical tag name => list of patterns (regex-escaped internally) that
     * all count as that tag. Curated by hand rather than lifted wholesale
     * from category keywords, because those lists mix in generic/financial
     * words (billion, ceo, market) that make bad tags.
     */
    protected array $vocabulary = [
        // AI / LLM
        'OpenAI' => ['openai'],
        'ChatGPT' => ['chatgpt', 'gpt-4o', 'gpt-4', 'gpt-5', 'gpt-5\.1', 'gpt4o'],
        'Claude' => ['claude', 'anthropic'],
        'Gemini' => ['gemini'],
        'LLM' => ['llm', 'large language model'],
        'Machine Learning' => ['machine learning', '\bml\b'],
        // Bare "ai" is deliberately excluded — on an AI-heavy blog it matches
        // ~40% of all posts and stops being a useful discriminator. Require
        // the full phrase instead.
        'Artificial Intelligence' => ['artificial intelligence'],
        'Prompt Engineering' => ['prompt engineering', 'prompt design'],
        'RAG' => ['\brag\b', 'retrieval.augmented generation'],
        'AI Agents' => ['ai agent', 'autonomous agent'],
        'Whisper API' => ['whisper api', 'whisper model'],

        // DevOps / Cloud
        'Kubernetes' => ['kubernetes', '\bk8s\b'],
        'Docker' => ['\bdocker\b', 'containeri[sz]ation'],
        'AWS' => ['\baws\b', 'amazon web services'],
        'Azure' => ['\bazure\b'],
        'Google Cloud' => ['google cloud', '\bgcp\b'],
        'Terraform' => ['terraform'],
        'CI/CD' => ['ci/cd', 'continuous integration', 'continuous deployment'],
        'Microservices' => ['microservice'],
        'Serverless' => ['serverless'],

        // Web development
        'React' => ['\breact\b', 'react\.js'],
        'Next.js' => ['next\.js', '\bnextjs\b'],
        'TypeScript' => ['typescript'],
        'JavaScript' => ['javascript'],
        'Laravel' => ['laravel'],
        'PHP' => ['\bphp\b'],
        'Node.js' => ['node\.js', '\bnodejs\b'],
        // Bare "api" is deliberately excluded — same over-matching problem as
        // bare "ai" (37% of all posts on prod). Require an API-specific phrase.
        'API Design' => ['rest api', 'api design', 'graphql api', 'api endpoint'],
        'Tailwind CSS' => ['tailwind'],

        // Programming
        'Python' => ['python'],
        'Rust' => ['\brust\b'],
        'Golang' => ['golang', 'go language'],
        'Algorithms' => ['algorithm'],
        'Design Patterns' => ['design pattern'],
        'Git' => ['\bgit\b', 'version control'],
        'Testing' => ['unit test', 'integration test', '\btdd\b'],

        // Career
        'Career Growth' => ['career', 'promotion', 'tech lead'],
        'Remote Work' => ['remote work', 'work.from.home'],
        'Productivity' => ['productivity', 'burnout'],
        'Leadership' => ['leadership', 'engineering manager'],

        // Security / Privacy
        'Data Privacy' => ['\bprivacy\b', 'data sovereignty'],
        'Cybersecurity' => ['cybersecurity', 'security breach', 'vulnerability'],
        'Compliance' => ['compliance', 'regulation', 'gdpr'],

        // Business / Industry
        'Startups' => ['startup', '\bfounder\b'],
        'Enterprise Software' => ['enterprise'],
        'Product Launches' => ['product launch', 'announces', 'unveil'],

        // Design
        'UX Design' => ['user experience', '\bux\b'],
        'UI Design' => ['user interface', '\bui\b'],
        'Accessibility' => ['accessibility', '\ba11y\b', '\bwcag\b'],
        'Design Systems' => ['design system'],
    ];

    public function handle()
    {
        $apply = $this->option('apply');
        $min = (int) $this->option('min');
        $maxTags = (int) $this->option('max-tags');

        $posts = BlogPost::published()->get(['id', 'slug', 'title', 'excerpt', 'content']);

        if ($posts->isEmpty()) {
            $this->warn('No published posts found.');
            return self::SUCCESS;
        }

        // canonical => [post_id => true]
        $matches = [];

        foreach ($posts as $post) {
            $haystack = $post->title . ' ' . $post->excerpt . ' ' . $post->content;

            foreach ($this->vocabulary as $canonical => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match('#' . $pattern . '#i', $haystack)) {
                        $matches[$canonical][$post->id] = true;
                        break; // one pattern hit is enough for this post/tag
                    }
                }
            }
        }

        // Coverage counts, sorted highest first, filtered by --min, capped by --max-tags.
        $coverage = collect($matches)
            ->map(fn ($postIds) => count($postIds))
            ->filter(fn ($count) => $count >= $min)
            ->sortDesc()
            ->take($maxTags);

        if ($coverage->isEmpty()) {
            $this->warn("No vocabulary term reached the minimum of {$min} article(s). Nothing to do.");
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($coverage as $canonical => $count) {
            // Spread the sample across the matched set (not just the first 3
            // by post ID) so broad tags don't all show the same handful of
            // early posts — makes the preview table actually useful to eyeball.
            $ids = array_keys($matches[$canonical]);
            $sampleIds = collect($ids)->count() <= 3
                ? $ids
                : [$ids[0], $ids[intdiv(count($ids), 2)], $ids[count($ids) - 1]];

            $sampleSlugs = collect($sampleIds)
                ->map(fn ($id) => $posts->firstWhere('id', $id)?->slug)
                ->filter()
                ->implode(', ');
            $rows[] = [$canonical, $count, $sampleSlugs];
        }

        $this->table(['Tag', 'Article count', 'Sample slugs'], $rows);
        $this->info($coverage->count() . ' tag(s) selected out of ' . count($this->vocabulary) . " vocabulary terms (min={$min}, max-tags={$maxTags}).");

        $droppedForMin = collect($matches)->filter(fn ($postIds) => count($postIds) > 0 && count($postIds) < $min)->count();
        if ($droppedForMin > 0) {
            $this->comment("{$droppedForMin} term(s) matched at least one article but were dropped for being below the minimum of {$min} — raise coverage or lower --min to include them.");
        }

        if (!$apply) {
            $this->comment('Dry run — no changes made. Re-run with --apply to write these tag assignments.');
            return self::SUCCESS;
        }

        $attached = 0;
        foreach ($coverage as $canonical => $count) {
            $tag = Tag::firstOrCreate(['name' => $canonical]);
            $postIds = array_keys($matches[$canonical]);

            foreach ($postIds as $postId) {
                $post = $posts->firstWhere('id', $postId);
                // syncWithoutDetaching: adds this tag if missing, never removes
                // existing tags (manual or from an earlier run) on the post.
                $result = $post->tags()->syncWithoutDetaching([$tag->id]);
                if (!empty($result['attached'])) {
                    $attached++;
                }
            }
        }

        $this->info("Done. {$attached} new post-tag assignment(s) written across " . $coverage->count() . ' tag(s).');

        return self::SUCCESS;
    }
}
