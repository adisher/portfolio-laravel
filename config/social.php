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

        // Target length. Scene timing is worked out from the spoken words at
        // this pace (the render later replaces it with the real audio length),
        // so 25-45 s is roughly 60-105 spoken words across all scenes.
        'min_seconds'      => 25,
        'max_seconds'      => 45,
        'words_per_second' => 2.5,
        'min_words'        => 60,
        'max_words'        => 105,
    ],

];
