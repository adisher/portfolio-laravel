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

            // Opening hooks: real, verified events to open an article with (source
            // included). Order by priority, best real event first. Curated in the
            // admin; leave empty to have the generator write an unnamed scene.
            'hooks' => [
                'In February 2026, Bento.me, a link-in-bio platform Linktree had bought two years earlier, was shut down: users were locked out, all their content was deleted, and every Bento bio-link now silently redirects to Linktree\'s own website (source: taplink.at/en/blog/bento-me-over.html).',
                'In December 2023 Linktree acquired Koji, a link-in-bio platform used by more than 150,000 creators that had raised nearly $40 million, and shut it down weeks later on January 31, 2024 (source: techcrunch.com, 2023-12-14).',
                'Shopify retired Linkpop, its own link-in-bio product, on July 7, 2025 with no reason given and no migration path, and existing pages simply stopped working (source: coywolf.com).',
                'Tap Bio told its users it would shut down on October 31, 2026, after which accounts are locked and every user page is deleted; it stopped accepting new signups as soon as it announced (source: taplink.at/en/blog/tap-bio-over.html).',
                'In 2025 Linktree, the market leader, raised its prices and added a 12 percent fee on creator sales, a reminder that on a platform you rent, the terms can change under you at any time (source: techcrunch.com, 2025-04-23).',
                'In December 2022, X (then Twitter) briefly banned link-in-bio services outright: for a few days, simply having a Linktree or lnk.bio link in your bio could get your account suspended, until creator backlash forced a reversal (source: tubefilter.com, 2022-12-19).',
                'On August 25, 2025, Google shut down its goo.gl URL shortener and let millions of previously shared short links start returning 404 errors, proof that even a link you handed out everywhere can simply stop resolving when it lives on someone else\'s service (source: developers.googleblog.com).',
            ],

            // User voices (social proof) are NOT seeded. They are curated in the
            // admin via the "Find Voices" web-search button, manual add, or the
            // one-time migration of any legacy flat voices into work_item_voices
            // records. Each is reviewed, screenshotted, and approved by hand.

            // Canonical screenshot library ("slug — description"). The generator may
            // only emit [[screenshot: slug]] markers from this list. Capture the
            // own-UI shots with scripts/screenshots; the two Vercel screens are
            // captured manually. See docs/screenshot-library.md.
            'screenshots' => [
                'live-page — the public bio page in the default theme',
                'live-themes — two or three of the five themes shown together',
                'admin-dashboard — the admin overview after login',
                'analytics — the page views and link clicks panel',
                'link-editor — the links list with drag-and-drop handles',
                'theme-picker — the five-theme selector',
                'profile-editor — the profile fields (photo, bio, social links)',
                'vercel-deploy — the one-click Vercel deploy screen (external, capture manually)',
                'custom-domain — the Vercel custom-domain settings (external, capture manually)',
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
