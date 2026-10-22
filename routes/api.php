<?php

use App\Http\Controllers\Api\BlogApiController;
use App\Http\Controllers\Api\SportsApiController;
use App\Http\Controllers\Frontend\DemoBookingController;
use Illuminate\Support\Facades\Route;

// Demo booking API (public, no auth required)
Route::prefix('demo')->group(function () {
    Route::get('/slots', [DemoBookingController::class, 'getSlots']);
    Route::post('/book', [DemoBookingController::class, 'store']);
});

// Read-only blog API for external automation (n8n). Token-guarded; the
// 'latest' route is declared before '{slug}' so it is not swallowed as a slug.
Route::prefix('blog')->middleware('blog.api.token')->group(function () {
    Route::get('/articles', [BlogApiController::class, 'index']);
    Route::get('/articles/latest', [BlogApiController::class, 'latest']);
    // Claims + marks the next unposted article. This is the daily n8n endpoint.
    Route::post('/articles/next', [BlogApiController::class, 'next']);
    Route::get('/articles/{slug}', [BlogApiController::class, 'show']);
    Route::post('/articles/{slug}/mark-posted', [BlogApiController::class, 'markPosted']);
    Route::post('/articles/{slug}/unmark', [BlogApiController::class, 'unmark']);
});

Route::prefix('sports')->group(function () {
    Route::get('/live', [SportsApiController::class, 'live']);
    Route::get('/match/{id}/score', [SportsApiController::class, 'matchScore']);
    Route::get('/match/{id}/detail', [SportsApiController::class, 'matchDetail']);
    Route::get('/match/{id}/events', [SportsApiController::class, 'matchEvents']);
});

// Reel render pipeline: called by the GitHub Actions render job
// (.github/workflows/render-reel.yml). Token-guarded; 'voice-sample' is
// declared before '{draft}' so it is not swallowed as an id.
Route::prefix('social/render')->middleware('social.render.token')->name('api.social.render.')
    ->controller(\App\Http\Controllers\Api\ReelRenderController::class)->group(function () {
        Route::get('/voice-sample', 'voiceSample')->name('voice-sample');
        Route::get('/{draft}', 'show')->whereNumber('draft')->name('show');
        Route::post('/{draft}/chunk', 'chunk')->whereNumber('draft')->name('chunk');
        Route::post('/{draft}/complete', 'complete')->whereNumber('draft')->name('complete');
        Route::post('/{draft}/failed', 'failed')->whereNumber('draft')->name('failed');
    });
