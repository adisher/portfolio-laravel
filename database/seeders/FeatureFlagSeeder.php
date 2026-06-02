<?php
namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            // ── PAGES ──────────────────────────────────────────────────────
            ['key' => 'page.blog',        'group' => 'page', 'label' => 'Blog',         'description' => 'The /blog route and all blog sub-pages.',              'sort_order' => 10],
            ['key' => 'page.portfolio',   'group' => 'page', 'label' => 'Portfolio',    'description' => 'The /portfolio route and individual project pages.',    'sort_order' => 20],
            ['key' => 'page.contact',     'group' => 'page', 'label' => 'Contact',      'description' => 'The /contact page.',                                   'sort_order' => 30],
            ['key' => 'page.sports',      'group' => 'page', 'label' => 'Sports',       'description' => 'The /sports live-scores page.',                        'sort_order' => 40],
            ['key' => 'page.about',       'group' => 'page', 'label' => 'About',        'description' => 'The /about page.',                                     'sort_order' => 50],
            ['key' => 'page.products',    'group' => 'page', 'label' => 'Products',     'description' => 'All /products/{slug} product detail pages.',           'sort_order' => 60],

            // ── HOMEPAGE SECTIONS ──────────────────────────────────────
            ['key' => 'section.home.hero',              'group' => 'section', 'label' => 'Homepage: Hero',               'description' => 'Main hero banner on the homepage.',                   'sort_order' => 110],
            ['key' => 'section.home.stats',             'group' => 'section', 'label' => 'Homepage: Stats Banner',       'description' => 'Skills/projects/clients stats strip.',                'sort_order' => 120],
            ['key' => 'section.home.featured_projects', 'group' => 'section', 'label' => 'Homepage: Featured Projects',  'description' => 'Featured projects grid section.',                     'sort_order' => 130],
            ['key' => 'section.home.testimonials',      'group' => 'section', 'label' => 'Homepage: Testimonials',       'description' => 'Globe testimonials section.',                         'sort_order' => 140],
            ['key' => 'section.home.live_scores',       'group' => 'section', 'label' => 'Homepage: Live Scores',        'description' => 'Cricket live-scores widget on the homepage.',         'sort_order' => 150],
            ['key' => 'section.home.blog_posts',        'group' => 'section', 'label' => 'Homepage: Latest Blog Posts',  'description' => 'Latest posts strip on the homepage.',                 'sort_order' => 160],

            // ── PRODUCT PAGE SECTIONS ──────────────────────────────────
            ['key' => 'section.product.gallery',        'group' => 'section', 'label' => 'Product: Screenshot Gallery',  'description' => 'Screenshot carousel on product detail pages.',        'sort_order' => 210],
            ['key' => 'section.product.features',       'group' => 'section', 'label' => 'Product: Features Grid',       'description' => 'Feature cards section on product pages.',             'sort_order' => 220],
            ['key' => 'section.product.how_it_works',   'group' => 'section', 'label' => 'Product: How It Works',        'description' => 'Step-by-step process section on product pages.',     'sort_order' => 230],
            ['key' => 'section.product.pricing',        'group' => 'section', 'label' => 'Product: Pricing',             'description' => 'Pricing tiers section on product pages.',             'sort_order' => 240],
            ['key' => 'section.product.metrics',        'group' => 'section', 'label' => 'Product: Key Metrics',         'description' => 'Metrics/stats section on product pages.',             'sort_order' => 250],
            ['key' => 'section.product.faq',            'group' => 'section', 'label' => 'Product: FAQ',                 'description' => 'Frequently asked questions on product pages.',        'sort_order' => 260],
            ['key' => 'section.product.cta_banner',     'group' => 'section', 'label' => 'Product: CTA Banner',          'description' => 'Bottom call-to-action banner on product pages.',      'sort_order' => 270],
            ['key' => 'section.product.more_products',  'group' => 'section', 'label' => 'Product: More Products',       'description' => 'Related products carousel at the bottom.',            'sort_order' => 280],

            // ── FEATURES ──────────────────────────────────────────────
            ['key' => 'feature.demo_booking',   'group' => 'feature', 'label' => 'Demo Booking System',   'description' => 'Hides demo scheduling buttons/modal and blocks the API slot endpoint.',  'sort_order' => 310],
            ['key' => 'feature.contact_form',   'group' => 'feature', 'label' => 'Contact Form',          'description' => 'Disables contact form submissions (shows a message instead).',          'sort_order' => 320],
            ['key' => 'feature.live_scores',    'group' => 'feature', 'label' => 'Live Scores API',       'description' => 'Disables live cricket score polling and display.',                      'sort_order' => 330],

            // ── NAV ───────────────────────────────────────────────────
            ['key' => 'nav.blog',        'group' => 'nav', 'label' => 'Nav: Blog',       'description' => 'Blog link in desktop and mobile navigation.',      'sort_order' => 410],
            ['key' => 'nav.portfolio',   'group' => 'nav', 'label' => 'Nav: Portfolio',  'description' => 'Portfolio link in desktop and mobile navigation.', 'sort_order' => 420],
            ['key' => 'nav.sports',      'group' => 'nav', 'label' => 'Nav: Sports',     'description' => 'Sports link in desktop and mobile navigation.',    'sort_order' => 430],
            ['key' => 'nav.contact',     'group' => 'nav', 'label' => 'Nav: Contact',    'description' => 'Contact link in desktop and mobile navigation.',   'sort_order' => 440],

            // ── BANNER ────────────────────────────────────────────────
            ['key' => 'banner.global', 'group' => 'banner', 'label' => 'Announcement Banner',
             'description' => 'Global banner shown above the navbar on all frontend pages.',
             'is_enabled' => false,
             'metadata' => ['message' => '', 'link' => '', 'link_text' => '', 'color' => 'teal'],
             'sort_order' => 510],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::updateOrCreate(
                ['key' => $flag['key']],
                array_merge(['is_enabled' => true], $flag)
            );
        }
    }
}
