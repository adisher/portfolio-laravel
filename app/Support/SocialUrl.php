<?php

namespace App\Support;

use App\Models\BlogPost;

/**
 * Builds the article URL we hand to social platforms, tagged with UTM params so
 * clicks are attributed to the right platform even when the referrer is stripped
 * (common on mobile apps). utm_source is the platform key, which TrafficSource
 * maps back to the platform name in analytics.
 */
class SocialUrl
{
    public static function for(BlogPost $post, string $platform, ?string $format = null): string
    {
        return route('blog.show', $post->slug) . '?' . http_build_query(array_filter([
            'utm_source'   => $platform,
            'utm_medium'   => 'social',
            'utm_campaign' => 'blog-share',
            // Which content format drove the visit (reel, carousel, ...), so
            // formats can be compared on real visits.
            'utm_content'  => $format,
        ]));
    }
}
