<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude Safepay webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhook/safepay',
        ]);

        $middleware->alias([
            'track.login' => \App\Http\Middleware\TrackLastLogin::class,
            'track.pageviews' => \App\Http\Middleware\TrackPageViews::class,
            'feature' => \App\Http\Middleware\CheckFeatureFlag::class,
        ]);

        // Apply to web routes (excluding admin)
        $middleware->web([
            'track.pageviews',
        ]);
        // Apply to admin routes
        $middleware->group('admin', [
            'auth',
            'track.login',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
