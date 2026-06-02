<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'My Portfolio',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Name',
                'description' => 'The name of your portfolio website',
                'sort_order' => 1,
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Full Stack Developer',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Tagline',
                'description' => 'Short description that appears in title tags',
                'sort_order' => 2,
            ],
            [
                'key' => 'site_description',
                'value' => 'Professional portfolio showcasing web development projects and technical expertise',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Site Description',
                'description' => 'Default meta description for SEO',
                'sort_order' => 3,
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'file',
                'group' => 'general',
                'label' => 'Site Logo',
                'description' => 'Upload your site logo',
                'sort_order' => 4,
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'file',
                'group' => 'general',
                'label' => 'Favicon',
                'description' => 'Upload favicon (16x16 or 32x32 PNG)',
                'sort_order' => 5,
            ],

            // Contact Settings
            [
                'key' => 'contact_email',
                'value' => 'hello@portfolio.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Contact Email',
                'description' => 'Main contact email address',
                'sort_order' => 1,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+1 (555) 123-4567',
                'type' => 'string',
                'group' => 'contact',
                'label' => 'Phone Number',
                'description' => 'Contact phone number',
                'sort_order' => 2,
            ],
            [
                'key' => 'contact_address',
                'value' => 'Available for remote work worldwide',
                'type' => 'text',
                'group' => 'contact',
                'label' => 'Address/Location',
                'description' => 'Your location or availability',
                'sort_order' => 3,
            ],

            // Social Media
            [
                'key' => 'social_twitter',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'Twitter URL',
                'description' => 'Your Twitter profile URL',
                'sort_order' => 1,
            ],
            [
                'key' => 'social_linkedin',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'LinkedIn URL',
                'description' => 'Your LinkedIn profile URL',
                'sort_order' => 2,
            ],
            [
                'key' => 'social_github',
                'value' => null,
                'type' => 'url',
                'group' => 'social',
                'label' => 'GitHub URL',
                'description' => 'Your GitHub profile URL',
                'sort_order' => 3,
            ],

            // SEO Settings
            [
                'key' => 'seo_google_analytics',
                'value' => null,
                'type' => 'string',
                'group' => 'seo',
                'label' => 'Google Analytics ID',
                'description' => 'Google Analytics tracking ID (GA4)',
                'sort_order' => 1,
            ],
            [
                'key' => 'seo_google_tag_manager',
                'value' => null,
                'type' => 'string',
                'group' => 'seo',
                'label' => 'Google Tag Manager ID',
                'description' => 'Google Tag Manager container ID',
                'sort_order' => 2,
            ],
            [
                'key' => 'seo_meta_keywords',
                'value' => 'portfolio, web developer, full stack developer',
                'type' => 'text',
                'group' => 'seo',
                'label' => 'Default Keywords',
                'description' => 'Default meta keywords (comma separated)',
                'sort_order' => 3,
            ],

            // Features
            [
                'key' => 'feature_blog_enabled',
                'value' => true,
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Enable Blog',
                'description' => 'Show/hide blog section',
                'sort_order' => 1,
            ],
            [
                'key' => 'feature_contact_form_enabled',
                'value' => true,
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Enable Contact Form',
                'description' => 'Enable contact form submissions',
                'sort_order' => 2,
            ],
            [
                'key' => 'feature_rss_enabled',
                'value' => true,
                'type' => 'boolean',
                'group' => 'features',
                'label' => 'Enable RSS Feeds',
                'description' => 'Enable RSS feed generation',
                'sort_order' => 3,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}