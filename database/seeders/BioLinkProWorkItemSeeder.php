<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;

/**
 * Canonical marketing manual for BioLink Pro. One seeder per work item so
 * running one never overwrites another. Idempotent: matched by name.
 *
 * Note: this is the WORK ITEM manual (feeds the AI article generator + Proof
 * of Work). The separate BioLinkProSeeder seeds the product's product_data and
 * product pages. They are different things; keep both.
 */
class BioLinkProWorkItemSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'type'  => 'product',
            'active' => true,
            'sort_order' => 2,

            'tagline' => 'A link-in-bio and mini portfolio page you actually own: one-time purchase, one-click deploy, no monthly subscription.',

            'target_audience' => 'Creators, freelancers, and professionals who want one polished link-in-bio page for their social profiles, projects, and intro video, but do not want to rent it from a subscription platform or wear its branding.',

            'how_it_helps' => "BioLink Pro is a self-hosted alternative to Linktree that you own outright. It gives you a polished bio-link page (profile, intro video, project showcase, social links, and a Buy Me a Coffee support link) managed through a visual admin panel with no code required. Deploy it to Vercel in one click on free hosting, pick from five themes, connect your own custom domain, and track page views and link clicks in a built-in analytics dashboard. Because you own the source code, there is no monthly fee, no platform logo on your page, and no risk of a subscription price hike or a shut-down service taking your page down with it.",

            'call_to_action' => "BioLink Pro is live, so you can try the full admin panel and see a real page before you spend a cent. Take it for a spin, and if it fits, it is a one-time purchase you deploy in about five minutes.",

            'tech_stack' => 'Next.js / React, Tailwind CSS, Vercel serverless hosting',

            'url' => 'https://portfolio-one-live.vercel.app/',

            'pain_points' => [
                'Link-in-bio platforms charge a recurring monthly fee for features that should be one-time',
                'Free tiers put their own branding on your page and lock the good themes behind a paywall',
                'You do not actually own your page: if the platform changes pricing or shuts down, your bio link goes with it',
                'Most bio-link tools will not let you use a real custom domain without upgrading',
                'Setting up a self-hosted alternative usually means wrestling with servers and code',
            ],

            'objections' => [
                'Isn\'t self-hosting hard? I\'m not a developer',
                'Linktree is free, so why would I pay for this?',
                'What if I want to change my links or switch themes later?',
                'Will it actually look professional, or like a generic template?',
                'What ongoing costs am I really signing up for?',
            ],

            'key_outcomes' => [
                'A live, branded bio-link page deployed in about five minutes',
                'One-time purchase with zero monthly fees (free Vercel hosting)',
                'Full ownership of the source code, your data, and your branding',
                'Built-in analytics: page views and link clicks tracked out of the box',
                'Five themes, drag-and-drop links, intro video, and custom-domain support, all managed without code',
            ],

            'proof_links' => [
                'https://portfolio-one-live.vercel.app/',
            ],

            'differentiators' => [
                'You own the code outright, unlike Linktree and other rented platforms',
                'One-time price instead of an open-ended monthly subscription',
                'One-click Vercel deploy on free hosting, so non-developers can go live fast',
                'Fully white-label: your brand, your domain, no platform logo',
                'A visual admin panel plus real analytics, not just a static page',
            ],

            'target_keywords' => [
                'self-hosted linktree alternative',
                'link in bio page open source',
                'linktree alternative no subscription',
                'own your link in bio',
                'one-click deploy bio link page',
            ],

            'article_angles' => [
                'Why I stopped renting my link-in-bio and built one I own',
                'Linktree alternatives compared: what "free" actually costs you',
                'Own your bio link: deploying a self-hosted link page to Vercel in five minutes',
                'What a link-in-bio page should include in 2026 (and what to skip)',
                'The case against subscriptions for the small tools you use every day',
            ],

            // Opening hooks: real, verified events to open an article with (add a
            // source in the text). Order by priority, best real event first. Curated
            // in the admin; leave empty to have the generator write an unnamed scene.
            'hooks' => [
                // e.g. 'In <year>, <platform> shut down its free tier and creators lost their bio pages overnight (source: <url>)',
            ],

            'stories' => "BioLink Pro came from a simple frustration: every link-in-bio tool wanted a monthly subscription to unlock the basics, a custom domain, decent themes, removing their logo, and none of them actually let you own your page. I wanted something a creator could deploy once, brand as their own, and never pay rent on again. So I built the thing I wished existed: a full bio-link page with a visual admin, real analytics, and a one-click deploy, that you own outright.",

            'notes' => 'Status: LIVE and testable (https://portfolio-one-live.vercel.app/), so the CTA is a genuine "try it before you buy." Tech stack confirmed. TODO before generating an article: replace or expand `stories` with the real specific origin (when it was built, first real user, any usage or sales numbers) for authenticity. Pricing is one-time: Personal $29, Commercial $79.',
        ];

        // Resolve by name/slug so IDs work across local and prod.
        $data['project_id'] = Project::where('title', 'BioLink Pro')->value('id');
        $data['blog_category_id'] = Category::where('slug', 'web-development')->value('id');

        WorkItem::updateOrCreate(['name' => 'BioLink Pro'], $data);
    }
}
