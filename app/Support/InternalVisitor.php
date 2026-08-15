<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Decides whether a request is "internal" (the site owner / staff) and should
 * therefore be excluded from analytics and view counts.
 *
 * A visit is internal when ANY of these hold:
 *   - the user is authenticated (an admin is logged in), OR
 *   - the browser carries the internal_visitor cookie (set once logged in, or
 *     via the /exclude-me link, so it keeps excluding even after logout), OR
 *   - the request IP is in the analytics_excluded_ips setting (comma/space list).
 */
class InternalVisitor
{
    public const COOKIE = 'internal_visitor';

    public static function check(Request $request): bool
    {
        if (auth()->check()) {
            return true;
        }

        if ($request->cookie(self::COOKIE)) {
            return true;
        }

        $raw = setting('analytics_excluded_ips', '');
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        $ips = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $raw)));

        return in_array($request->ip(), $ips, true);
    }
}
