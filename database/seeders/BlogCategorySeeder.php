<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'AI & Machine Learning',
                'slug' => 'ai-machine-learning',
                'description' => 'Latest trends in artificial intelligence, machine learning, deep learning, LLMs, and AI tools transforming the tech industry.',
                'color' => '#8B5CF6', // Purple
                'is_active' => true,
                'keywords' => ['ai', 'artificial intelligence', 'machine learning', 'deep learning', 'neural network', 'llm', 'gpt', 'chatgpt', 'claude', 'openai', 'anthropic', 'gemini', 'transformer', 'nlp', 'natural language', 'computer vision', 'reinforcement learning', 'ml', 'model', 'training', 'inference', 'prompt', 'embedding', 'rag', 'agent', 'automation', 'copilot'],
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Frontend and backend development tutorials, frameworks, best practices, and the latest web technologies.',
                'color' => '#3B82F6', // Blue
                'is_active' => true,
                'keywords' => ['web', 'frontend', 'backend', 'javascript', 'typescript', 'react', 'vue', 'angular', 'svelte', 'next.js', 'nuxt', 'node', 'express', 'laravel', 'php', 'html', 'css', 'tailwind', 'sass', 'webpack', 'vite', 'api', 'rest', 'graphql', 'responsive', 'accessibility', 'pwa', 'spa', 'ssr', 'jamstack', 'browser', 'dom', 'http'],
            ],
            [
                'name' => 'Tech News',
                'slug' => 'tech-news',
                'description' => 'Breaking news, product launches, acquisitions, and industry updates from the technology world.',
                'color' => '#EF4444', // Red
                'is_active' => true,
                'keywords' => ['news', 'announcement', 'launch', 'release', 'acquisition', 'merger', 'funding', 'startup', 'ipo', 'valuation', 'billion', 'million', 'google', 'apple', 'microsoft', 'amazon', 'meta', 'facebook', 'twitter', 'tesla', 'nvidia', 'intel', 'amd', 'company', 'ceo', 'industry', 'market', 'stock', 'investment', 'venture', 'regulation', 'antitrust', 'privacy', 'security breach'],
            ],
            [
                'name' => 'Programming',
                'slug' => 'programming',
                'description' => 'Programming languages, algorithms, coding tutorials, software architecture, and development best practices.',
                'color' => '#10B981', // Green
                'is_active' => true,
                'keywords' => ['programming', 'code', 'coding', 'algorithm', 'data structure', 'python', 'java', 'rust', 'go', 'golang', 'kotlin', 'swift', 'c++', 'c#', 'ruby', 'perl', 'function', 'class', 'object', 'oop', 'functional', 'design pattern', 'solid', 'clean code', 'refactor', 'debug', 'testing', 'tdd', 'unit test', 'git', 'version control', 'open source', 'library', 'package', 'dependency'],
            ],
            [
                'name' => 'Design & UX',
                'slug' => 'design-ux',
                'description' => 'User interface design, user experience principles, design systems, accessibility, and visual design trends.',
                'color' => '#F59E0B', // Amber
                'is_active' => true,
                'keywords' => ['design', 'ux', 'ui', 'user experience', 'user interface', 'figma', 'sketch', 'adobe', 'prototype', 'wireframe', 'mockup', 'usability', 'accessibility', 'a11y', 'wcag', 'color', 'typography', 'layout', 'grid', 'responsive', 'mobile', 'icon', 'illustration', 'animation', 'interaction', 'user research', 'persona', 'journey map', 'design system', 'component'],
            ],
            [
                'name' => 'DevOps & Cloud',
                'slug' => 'devops-cloud',
                'description' => 'Cloud computing, CI/CD pipelines, containerization, infrastructure as code, and DevOps practices.',
                'color' => '#06B6D4', // Cyan
                'is_active' => true,
                'keywords' => ['devops', 'cloud', 'aws', 'azure', 'gcp', 'google cloud', 'docker', 'kubernetes', 'k8s', 'container', 'microservice', 'serverless', 'lambda', 'terraform', 'ansible', 'jenkins', 'github actions', 'ci/cd', 'pipeline', 'deployment', 'infrastructure', 'monitoring', 'logging', 'observability', 'prometheus', 'grafana', 'helm', 'pod', 'cluster', 'scaling', 'load balancer', 'cdn', 'redis', 'kafka'],
            ],
            [
                'name' => 'Career & Growth',
                'slug' => 'career-growth',
                'description' => 'Professional development, job market insights, productivity tips, and career advice for tech professionals.',
                'color' => '#EC4899', // Pink
                'is_active' => true,
                'keywords' => ['career', 'job', 'interview', 'resume', 'salary', 'hiring', 'remote', 'freelance', 'consulting', 'productivity', 'burnout', 'work-life balance', 'leadership', 'management', 'team', 'mentor', 'learning', 'skill', 'certification', 'bootcamp', 'portfolio', 'networking', 'conference', 'community', 'growth', 'promotion', 'senior', 'junior', 'developer advocate', 'tech lead'],
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('Blog categories seeded successfully!');
    }
}
