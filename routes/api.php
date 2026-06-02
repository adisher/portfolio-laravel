<?php

use App\Http\Controllers\Api\SportsApiController;
use App\Http\Controllers\Frontend\DemoBookingController;
use Illuminate\Support\Facades\Route;

// Demo booking API (public, no auth required)
Route::prefix('demo')->group(function () {
    Route::get('/slots', [DemoBookingController::class, 'getSlots']);
    Route::post('/book', [DemoBookingController::class, 'store']);
});

Route::prefix('sports')->group(function () {
    Route::get('/live', [SportsApiController::class, 'live']);
    Route::get('/match/{id}/score', [SportsApiController::class, 'matchScore']);
    Route::get('/match/{id}/detail', [SportsApiController::class, 'matchDetail']);
    Route::get('/match/{id}/events', [SportsApiController::class, 'matchEvents']);
});
