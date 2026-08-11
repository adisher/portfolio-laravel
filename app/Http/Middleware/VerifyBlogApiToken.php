<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the read-only blog JSON API with a single static bearer token.
 *
 * Clients (n8n) send the token either as an Authorization: Bearer <token>
 * header or an X-Api-Token header. The comparison is timing-safe. If no token
 * is configured the API stays closed (fails shut), so a missing env var can
 * never accidentally expose the endpoints publicly.
 */
class VerifyBlogApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('blog_api.token');

        if (empty($expected)) {
            return response()->json([
                'message' => 'Blog API is not configured.',
            ], 503);
        }

        $provided = $request->bearerToken() ?: $request->header('X-Api-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Invalid or missing API token.',
            ], 401);
        }

        return $next($request);
    }
}
