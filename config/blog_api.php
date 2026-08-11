<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blog Read API
    |--------------------------------------------------------------------------
    |
    | Static bearer token used to authenticate external automation clients
    | (currently n8n) that read published articles from the JSON API under
    | the /api/blog prefix. Set BLOG_API_TOKEN in .env. Because production
    | runs config:cache, this MUST be read here and never via bare env() in
    | app code, or it resolves to null once the config is cached.
    |
    */

    'token' => env('BLOG_API_TOKEN'),

    // Default number of articles returned per page by the list endpoint.
    'per_page' => (int) env('BLOG_API_PER_PAGE', 15),

    // Hard cap so a client cannot request an unbounded page size.
    'max_per_page' => (int) env('BLOG_API_MAX_PER_PAGE', 50),

];
