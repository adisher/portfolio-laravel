<?php

use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoAvailabilityController as AdminDemoAvailabilityController;
use App\Http\Controllers\Admin\DemoBookingController as AdminDemoBookingController;
use App\Http\Controllers\Admin\ProductPageController as AdminProductPageController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SolutionController as AdminSolutionController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\VisibilityController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\DemoBookingController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\ProjectController;
use App\Http\Controllers\Frontend\SafepayWebhookController;
use Illuminate\Support\Facades\Route;

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::feeds();

// Sitemap routes
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/admin/sitemap/generate', [App\Http\Controllers\SitemapController::class, 'generate'])
    ->middleware('auth')
    ->name('admin.sitemap.generate');

// Dynamic robots.txt with AI crawler support
Route::get('/robots.txt', function () {
    $sitemapUrl = url('/sitemap.xml');
    $llmsTxtUrl = url('/llms.txt');

    $content = <<<ROBOTS
# robots.txt for Portfolio Website
# Updated for AI Search Engine Indexing

User-agent: *
Allow: /

# AI Crawlers - ALLOW ALL for AI search indexing
User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: OAI-SearchBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: Claude-Web
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: Applebot-Extended
Allow: /

User-agent: cohere-ai
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: Bytespider
Allow: /

# Traditional Search Engines
User-agent: Googlebot
Allow: /

User-agent: Bingbot
Allow: /

# Disallow admin and auth pages
Disallow: /admin/
Disallow: /login
Disallow: /register
Disallow: /password/
Disallow: /sanctum/

# Allow RSS feeds
Allow: /blog/feed
Allow: /blog/category/*/feed

# Allow sports pages
Allow: /sports/

# Sitemaps and AI content guides
Sitemap: {$sitemapUrl}

# LLMs.txt for AI crawlers
# See: https://llmstxt.org/
# LLMs-Txt: {$llmsTxtUrl}

# Crawl delay
Crawl-delay: 1
ROBOTS;

    return response($content, 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

// LLMs.txt for AI search indexing
Route::get('/llms.txt', [App\Http\Controllers\LlmsTxtController::class, 'index'])->name('llms.txt');
Route::get('/llms-full.txt', [App\Http\Controllers\LlmsTxtController::class, 'full'])->name('llms-full.txt');

Route::get('/about', function () {
    $about = [
        'hero_bio'      => setting('about_hero_bio', ''),
        'resume_url'    => setting('about_resume_url', ''),
        'personal_bio'  => setting('about_personal_bio', ''),
        'interests'     => array_filter(array_map('trim', explode('|', setting('about_interests', '')))),
        'stats'         => setting('about_stats', []),
        'skills'        => setting('about_skills', []),
        'experience'    => setting('about_experience', []),
        'values'        => setting('about_values', []),
    ];
    return view('frontend.about', compact('about'));
})->middleware('feature:page.about')->name('about');

// Product Routes (own products — separate from portfolio case studies)
Route::get('/products/{slug}', [ProductController::class, 'show'])->middleware('feature:page.products')->name('products.show');
Route::get('/products/{productSlug}/{pageSlug}', [ProductController::class, 'page'])->middleware('feature:page.products')->name('products.page');

// Checkout Routes (Safepay Express Checkout — guest checkout, no auth required)
Route::post('/checkout/{productSlug}/{tierIndex}', [CheckoutController::class, 'initiate'])->name('checkout.initiate');
Route::get('/checkout/processing/{orderToken}', [CheckoutController::class, 'processing'])->name('checkout.processing');
Route::get('/checkout/status/{orderToken}', [CheckoutController::class, 'status'])->name('checkout.status');
Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
Route::get('/checkout/success/{orderToken}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

// Safepay Webhook (CSRF excluded in bootstrap/app.php)
Route::post('/webhook/safepay', [SafepayWebhookController::class, 'handle'])->name('webhook.safepay');

Route::prefix('portfolio')->name('portfolio.')->middleware('feature:page.portfolio')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [ProjectController::class, 'category'])->name('category');
    Route::get('/{slug}', [ProjectController::class, 'show'])->name('show');
});

Route::prefix('blog')->name('blog.')->middleware('feature:page.blog')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/search', [BlogController::class, 'search'])->name('search');
    Route::get('/proof-of-work', [BlogController::class, 'proofOfWork'])->name('proof-of-work');
    Route::get('/category/{slug}', [BlogController::class, 'category'])->name('category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
});

// Sports Frontend Routes — T20 Cricket World Cup 2026
Route::prefix('sports')->name('sports.')->middleware('feature:page.sports')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\SportsController::class, 'index'])->name('index');
    Route::get('/match/{matchSlug}', [\App\Http\Controllers\Frontend\SportsController::class, 'match'])->name('match');
    // Legacy redirect: /sports/{sport}/{match} → /sports/match/{match}
    Route::get('/{sportSlug}/{matchSlug}', fn($s, $m) => redirect()->route('sports.match', $m, 301));
});

// Frontend Contact Routes
Route::get('/contact', [ContactController::class, 'index'])->middleware('feature:page.contact')->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('feature:page.contact')->name('contact.store');

// Demo Booking — cancel via email token (no auth required)
Route::get('/demo/cancel/{token}', [DemoBookingController::class, 'cancel'])->name('demo.cancel');

// Redirect authenticated users from /dashboard to admin dashboard
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/admin/dashboard');
    })->name('dashboard');
});

// Admin Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('projects', AdminProjectController::class);
    Route::post('projects/{project}/images', [AdminProjectController::class, 'uploadImages'])->name('projects.images.upload');
    Route::delete('projects/{project}/images/{image}', [AdminProjectController::class, 'deleteImage'])->name('projects.images.delete');
    Route::get('projects/{project}/product-content', [AdminProjectController::class, 'productContent'])->name('projects.product-content');
    Route::put('projects/{project}/product-content', [AdminProjectController::class, 'updateProductContent'])->name('projects.update-product-content');
    Route::resource('projects.product-pages', AdminProductPageController::class)->except(['show']);
    Route::resource('testimonials', AdminTestimonialController::class)->except(['show']);
    Route::resource('solutions', AdminSolutionController::class)->except(['show']);
    Route::resource('blog-posts', BlogPostController::class);

    // Orders routes
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show']);

    // Settings routes
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');

    // About page routes
    Route::get('/about', [\App\Http\Controllers\Admin\AboutController::class, 'edit'])->name('about.edit');
    Route::put('/about', [\App\Http\Controllers\Admin\AboutController::class, 'update'])->name('about.update');

    // Work Items (marketing manuals)
    Route::resource('work-items', \App\Http\Controllers\Admin\WorkItemController::class);

    // Admin Contact Routes
    Route::controller(AdminContactController::class)->group(function () {
        Route::get('contacts', 'index')->name('contacts.index');
        Route::get('contacts/{contact}', 'show')->name('contacts.show');
        Route::patch('contacts/{contact}/mark-read', 'markAsRead')->name('contacts.mark-read');
        Route::patch('contacts/{contact}/notes', 'updateNotes')->name('contacts.update-notes');
        Route::delete('contacts/{contact}', 'destroy')->name('contacts.destroy');
    });

    // Media routes
    Route::prefix('media')->name('media.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MediaController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\MediaController::class, 'store'])->name('store');
        Route::get('/picker', [\App\Http\Controllers\Admin\MediaController::class, 'picker'])->name('picker');
        Route::get('/{media}', [\App\Http\Controllers\Admin\MediaController::class, 'show'])->name('show');
        Route::put('/{media}', [\App\Http\Controllers\Admin\MediaController::class, 'update'])->name('update');
        Route::delete('/{media}', [\App\Http\Controllers\Admin\MediaController::class, 'destroy'])->name('destroy');
        Route::post('/folder', [\App\Http\Controllers\Admin\MediaController::class, 'createFolder'])->name('folder.create');
    });
    // Profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('update');
        Route::put('/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('password');
        Route::put('/notifications', [\App\Http\Controllers\Admin\ProfileController::class, 'updateNotifications'])->name('notifications');
        Route::delete('/remove-picture', [\App\Http\Controllers\Admin\ProfileController::class, 'removeProfilePicture'])->name('remove-picture');
    });

    // Demo Bookings & Availability
    Route::prefix('demo-bookings')->name('demo-bookings.')->group(function () {
        Route::get('/', [AdminDemoBookingController::class, 'index'])->name('index');
        Route::get('/{demoBooking}', [AdminDemoBookingController::class, 'show'])->name('show');
        Route::patch('/{demoBooking}/status', [AdminDemoBookingController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{demoBooking}/notes', [AdminDemoBookingController::class, 'updateNotes'])->name('update-notes');
        Route::delete('/{demoBooking}', [AdminDemoBookingController::class, 'destroy'])->name('destroy');
    });
    Route::get('demo-availability', [AdminDemoAvailabilityController::class, 'edit'])->name('demo-availability.edit');
    Route::put('demo-availability', [AdminDemoAvailabilityController::class, 'update'])->name('demo-availability.update');
    Route::post('demo-availability/blocked-dates', [AdminDemoAvailabilityController::class, 'addBlockedDate'])->name('demo-availability.blocked-dates.store');
    Route::delete('demo-availability/blocked-dates/{blockedDate}', [AdminDemoAvailabilityController::class, 'removeBlockedDate'])->name('demo-availability.blocked-dates.destroy');

    // Analytics routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
        Route::get('/search', [\App\Http\Controllers\Admin\AnalyticsController::class, 'search'])->name('search');
        Route::get('/realtime', [\App\Http\Controllers\Admin\AnalyticsController::class, 'realtime'])->name('realtime');
        Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
    });

    // RSS Sources routes
    Route::resource('rss-sources', \App\Http\Controllers\Admin\RssSourceController::class);
    Route::post('rss-sources/{rssSource}/fetch', [\App\Http\Controllers\Admin\RssSourceController::class, 'fetch'])
        ->name('rss-sources.fetch');
    Route::post('rss-sources/fetch-all', [\App\Http\Controllers\Admin\RssSourceController::class, 'fetchAll'])
        ->name('rss-sources.fetch-all');

    // Sports Management routes
    Route::prefix('sports')->name('sports.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\SportManagementController::class, 'dashboard'])->name('dashboard');
        Route::post('/sync/{type}', [\App\Http\Controllers\Admin\SportManagementController::class, 'sync'])->name('sync');
        Route::get('/sync-logs', [\App\Http\Controllers\Admin\SportManagementController::class, 'syncLogs'])->name('sync-logs');
        Route::resource('matches', \App\Http\Controllers\Admin\MatchController::class);
        Route::resource('teams', \App\Http\Controllers\Admin\TeamController::class);
        Route::resource('tournaments', \App\Http\Controllers\Admin\TournamentController::class);
    });

    // Visibility
    Route::get('visibility', [VisibilityController::class, 'index'])->name('visibility.index');
    Route::patch('visibility/{flag}', [VisibilityController::class, 'toggle'])->name('visibility.toggle');
    Route::patch('visibility/{flag}/meta', [VisibilityController::class, 'updateMeta'])->name('visibility.meta');

    // Collected Articles routes
    Route::prefix('collected-articles')->name('collected-articles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'index'])->name('index');
        Route::get('{collectedArticle}', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'show'])->name('show');
        Route::post('{collectedArticle}/approve', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'approve'])->name('approve');
        Route::post('{collectedArticle}/reject', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'reject'])->name('reject');
        Route::get('{collectedArticle}/create-blog-post', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'createBlogPost'])->name('create-blog-post');
        Route::post('{collectedArticle}/store-blog-post', [\App\Http\Controllers\Admin\CollectedArticleController::class, 'storeBlogPost'])->name('store-blog-post');
    });


});

require __DIR__ . '/auth.php';
