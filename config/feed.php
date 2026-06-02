<?php

return [
    'feeds' => [
        'blog'          => [
            /*
             * Here you can specify which class and method will return
             * the items that should appear in the feed. For example:
             * [App\Model::class, 'getAllFeedItems']
             */
            'items'       => [\App\Models\BlogPost::class, 'getFeedItems'],

            /*
             * The feed will be available on this url.
             */
            'url'         => '/blog/feed',

            'title'       => 'Portfolio Blog - Latest Articles',
            'description' => 'Stay updated with the latest web development articles, tutorials, and insights.',
            'language'    => 'en-US',

            /*
             * The image to display for the feed. For Atom feeds, this is displayed as
             * a banner/logo; for RSS and JSON feeds, it's displayed as an icon.
             * An empty value omits the image attribute from the feed.
             */
            'image'       => '',

            /*
             * The format of the feed. Acceptable values are 'rss', 'atom', or 'json'.
             */
            'format'      => 'rss',

            /*
             * The view that will render the feed.
             */
            'view'        => 'feeds.blog',

            /*
             * The mime type to be used in the <link> tag. Set to null to automatically
             * determine the correct value.
             */
            'type'        => 'application/rss+xml',

            /*
             * The content type for the feed response. Set to null to automatically
             * determine the correct value.
             */
            'contentType' => 'application/rss+xml',
        ],

        'blog-category' => [
            'items'       => [\App\Models\BlogPost::class, 'getFeedItemsByCategory'],
            'url'         => '/blog/category/{category}/feed',
            'title'       => 'Portfolio Blog - {category} Articles',
            'description' => 'Latest {category} articles and tutorials.',
            'language'    => 'en-US',
            'image'       => '',
            'format'      => 'rss',
            'view'        => 'feeds.blog-category',
            'type'        => 'application/rss+xml',
            'contentType' => 'application/rss+xml',
        ],
    ],
];
