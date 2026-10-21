<?php

/*
|--------------------------------------------------------------------------
| Social content studio (docs/social-distribution-plan.md)
|--------------------------------------------------------------------------
*/

return [

    'reels' => [
        // Who gets the "new reel ready for review" and "render failed" emails.
        'alert_email' => env('SOCIAL_ALERT_EMAIL', 'adilsher973@gmail.com'),

        // Model that writes reel scripts and captions. Defaults to the model
        // used for flagship original articles; low volume, so cost is small.
        'ai_model' => env('SOCIAL_AI_MODEL', env('AI_ORIGINAL_MODEL', 'claude-sonnet-4-6')),

        // Target length. The voice reads ~2.5 words a second, so 25-45 s is
        // roughly 65-110 spoken words across all scenes.
        'min_seconds' => 25,
        'max_seconds' => 45,
    ],

];
