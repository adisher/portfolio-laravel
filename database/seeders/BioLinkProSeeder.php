<?php

namespace Database\Seeders;

use App\Models\ProductPage;
use App\Models\Project;
use Illuminate\Database\Seeder;

class BioLinkProSeeder extends Seeder
{
    public function run(): void
    {
        $product = Project::where('slug', 'biolink-pro')->first();

        if (!$product) {
            $this->command->warn('BioLink Pro project not found. Skipping seeder.');
            return;
        }

        // Update product_data
        $product->update([
            'product_data' => [
                'features' => [
                    ['icon' => 'settings', 'title' => 'Visual Admin Panel', 'description' => 'Manage all your links, profile info, and settings through an intuitive admin dashboard. No coding required.'],
                    ['icon' => 'palette', 'title' => '5 Theme Presets', 'description' => 'Choose from 5 professionally designed themes. Each theme is fully customizable to match your brand.'],
                    ['icon' => 'chart', 'title' => 'Analytics Dashboard', 'description' => 'Track page views, link clicks, and visitor analytics. Understand your audience with real-time data.'],
                    ['icon' => 'rocket', 'title' => 'One-Click Vercel Deploy', 'description' => 'Deploy your BioLink page to Vercel in one click. No server setup, no configuration needed.'],
                    ['icon' => 'lightning', 'title' => 'Instant Updates', 'description' => 'Changes reflect instantly. Edit your links, bio, or theme and see updates live in seconds.'],
                    ['icon' => 'globe', 'title' => 'SEO Optimized', 'description' => 'Built-in SEO meta tags, Open Graph support, and structured data for maximum visibility.'],
                    ['icon' => 'link', 'title' => 'Drag-and-Drop Links', 'description' => 'Reorder your links effortlessly with drag-and-drop. Organize your page exactly how you want.'],
                    ['icon' => 'device', 'title' => 'Fully Responsive', 'description' => 'Looks perfect on every device — desktop, tablet, and mobile. Pixel-perfect responsive design.'],
                    ['icon' => 'shield', 'title' => 'White-Label', 'description' => 'No BioLink Pro branding. Your page, your brand. Use your custom domain for a professional look.'],
                    ['icon' => 'refresh', 'title' => 'Lifetime Updates', 'description' => 'Get free updates forever. New themes, features, and improvements — all included with your purchase.'],
                ],
                'how_it_works' => [
                    ['title' => 'Purchase', 'description' => 'Choose your plan and complete the purchase. You\'ll get instant access.'],
                    ['title' => 'Deploy to Vercel', 'description' => 'Click the deploy button to clone and deploy to your Vercel account in one click.'],
                    ['title' => 'Set Admin Password', 'description' => 'Visit your live site, go to /admin, and set your admin password on first visit.'],
                    ['title' => 'Customize', 'description' => 'Add your links, upload your photo, pick a theme, and customize everything from the admin panel.'],
                ],
                'pricing' => [
                    [
                        'name' => 'Personal',
                        'price' => '29',
                        'description' => 'Perfect for individuals and personal brands',
                        'highlighted' => false,
                        'cta_url' => '',
                        'features' => [
                            'Full source code access',
                            'All 5 theme presets',
                            'Admin dashboard',
                            'Analytics tracking',
                            'One-click Vercel deploy',
                            'Lifetime updates',
                            'Single site license',
                        ],
                    ],
                    [
                        'name' => 'Commercial',
                        'price' => '79',
                        'description' => 'For agencies and commercial use',
                        'highlighted' => true,
                        'cta_url' => '',
                        'features' => [
                            'Everything in Personal',
                            'Unlimited site license',
                            'Commercial use rights',
                            'Priority support',
                            'Custom theme guidance',
                            'White-label resale rights',
                            'Agency deployment guide',
                        ],
                    ],
                ],
                'faq' => [
                    ['question' => 'Do I need coding skills to use BioLink Pro?', 'answer' => 'No! BioLink Pro comes with a visual admin panel where you can manage everything — links, profile, theme, and settings — without touching any code.'],
                    ['question' => 'How do I get started after purchasing?', 'answer' => 'After purchase, you\'ll receive access to download the source code or deploy directly to Vercel with one click. Follow the setup guide to have your page live in under 5 minutes.'],
                    ['question' => 'Can I use my own custom domain?', 'answer' => 'Absolutely! After deploying to Vercel, you can connect your own custom domain through Vercel\'s dashboard. Full instructions are included in the deployment guide.'],
                    ['question' => 'Is there a monthly fee?', 'answer' => 'No monthly fees. BioLink Pro is a one-time purchase. Vercel\'s free tier handles hosting for most users, so your ongoing cost is $0.'],
                    ['question' => 'Can I customize the design?', 'answer' => 'Yes! Choose from 5 built-in themes, customize colors, update your profile photo, bio text, and social links. For advanced customization, you have full access to the source code.'],
                    ['question' => 'What kind of support do I get?', 'answer' => 'All purchases include access to the setup and deployment guides. Commercial license holders also get priority email support for any issues.'],
                ],
                'cta_url' => '',
                'cta_label' => 'Buy Now',
            ],
        ]);

        $this->command->info('BioLink Pro product_data seeded.');

        // Create product pages
        // Setup page
        ProductPage::updateOrCreate(
            ['project_id' => $product->id, 'slug' => 'setup'],
            [
                'title' => 'Get Started',
                'type' => 'setup',
                'is_published' => true,
                'sort_order' => 0,
                'content' => [
                    'heading' => 'Congratulations!',
                    'message' => 'Thank you for purchasing BioLink Pro. Choose how you\'d like to get started below.',
                    'options' => [
                        [
                            'icon' => 'download',
                            'title' => 'Download Source Code',
                            'description' => 'Download the full source code and deploy manually to your own server or hosting provider. Best for developers who want full control.',
                            'button_label' => 'Download Files',
                            'button_url' => 'https://github.com/yourusername/biolink-pro/releases',
                            'recommended' => false,
                        ],
                        [
                            'icon' => 'rocket',
                            'title' => 'Deploy to Vercel',
                            'description' => 'One-click deploy to Vercel with automatic GitHub repository setup. The fastest way to get your BioLink page live. Free hosting included.',
                            'button_label' => 'Start Deploy Guide',
                            'button_url' => '/products/biolink-pro/deploy',
                            'recommended' => true,
                        ],
                    ],
                ],
            ]
        );

        $this->command->info('BioLink Pro setup page seeded.');

        // Deploy page
        ProductPage::updateOrCreate(
            ['project_id' => $product->id, 'slug' => 'deploy'],
            [
                'title' => 'Deploy to Vercel',
                'type' => 'deploy',
                'is_published' => true,
                'sort_order' => 1,
                'content' => [
                    'heading' => 'Deploy to Vercel',
                    'steps' => [
                        [
                            'title' => 'Prerequisites',
                            'description' => 'Before you begin, make sure you have accounts on these platforms.',
                            'items' => "Create a free GitHub account at https://github.com\nCreate a free Vercel account at https://vercel.com\nSign into Vercel using your GitHub account",
                            'button_label' => 'Open GitHub',
                            'button_url' => 'https://github.com/signup',
                            'guidance' => 'If you already have these accounts, you can skip this step.',
                            'note' => '',
                        ],
                        [
                            'title' => 'Deploy to Vercel',
                            'description' => 'Click the button below to clone the BioLink Pro repository to your GitHub and deploy it to Vercel automatically.',
                            'items' => "Click the Deploy button below\nAuthorize Vercel to access your GitHub\nChoose a repository name\nClick Deploy",
                            'button_label' => 'Deploy to Vercel',
                            'button_url' => 'https://vercel.com/new/clone?repository-url=https://github.com/yourusername/biolink-pro',
                            'guidance' => 'The deployment process typically takes 1-2 minutes.',
                            'note' => 'Vercel will create a new repository in your GitHub account and deploy it automatically.',
                        ],
                        [
                            'title' => 'Visit Your Live Site',
                            'description' => 'Once deployment is complete, Vercel will provide you with a live URL. Click "Visit" to see your BioLink page.',
                            'items' => "Wait for deployment to complete\nClick the Visit button on Vercel\nYour BioLink page is now live!",
                            'button_label' => '',
                            'button_url' => '',
                            'guidance' => 'Your URL will look like: your-project.vercel.app',
                            'note' => '',
                        ],
                        [
                            'title' => 'Set Your Admin Password',
                            'description' => 'Navigate to your site\'s admin panel to set up your password on first visit.',
                            'items' => "Go to your-site.vercel.app/admin\nCreate your admin password\nLog in to the admin dashboard",
                            'button_label' => '',
                            'button_url' => '',
                            'guidance' => 'Save your password somewhere safe. You\'ll need it to manage your BioLink page.',
                            'note' => 'The first visit to /admin will prompt you to create a password. This only happens once.',
                        ],
                        [
                            'title' => 'Customize Your Page',
                            'description' => 'Use the admin panel to personalize your BioLink page with your content.',
                            'items' => "Upload your profile photo\nUpdate your bio and display name\nAdd your social links\nChoose a theme preset\nCustomize colors to match your brand",
                            'button_label' => '',
                            'button_url' => '',
                            'guidance' => 'All changes are saved instantly and reflected on your live page.',
                            'note' => '',
                        ],
                        [
                            'title' => 'Connect Custom Domain (Optional)',
                            'description' => 'Want to use your own domain? Connect it through your Vercel dashboard.',
                            'items' => "Go to your Vercel project settings\nClick on Domains\nAdd your custom domain\nUpdate DNS records as instructed",
                            'button_label' => 'Vercel Domain Docs',
                            'button_url' => 'https://vercel.com/docs/projects/domains',
                            'guidance' => 'DNS propagation can take up to 48 hours, but usually completes within minutes.',
                            'note' => 'You\'ll need to own a domain name. If you don\'t have one, you can purchase from Namecheap, GoDaddy, or Cloudflare.',
                        ],
                    ],
                    'support_heading' => 'Need Help?',
                    'support_message' => 'If you run into any issues during deployment, don\'t hesitate to reach out. We\'re here to help!',
                    'support_url' => '',
                ],
            ]
        );

        $this->command->info('BioLink Pro deploy page seeded.');
        $this->command->info('BioLink Pro seeding complete!');
    }
}
