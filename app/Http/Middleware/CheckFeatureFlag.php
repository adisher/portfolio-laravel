<?php
namespace App\Http\Middleware;

use App\Models\FeatureFlag;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureFlag
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (FeatureFlag::enabled($key)) {
            return $next($request);
        }

        // Authenticated admins can still preview the page
        if (auth()->check()) {
            $request->attributes->set('_flag_hidden', true);
            return $next($request);
        }

        // Public visitors: show coming-soon or redirect
        $meta = FeatureFlag::meta($key);

        if (! empty($meta['redirect'])) {
            return redirect()->route($meta['redirect']);
        }

        return response()->view('errors.coming-soon', ['flagKey' => $key], 200);
    }
}
