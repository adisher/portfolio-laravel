<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! session('last_login_tracked')) {
            Auth::user()->updateLastLogin();
            session(['last_login_tracked' => true]);
        }

        return $next($request);
    }
}
