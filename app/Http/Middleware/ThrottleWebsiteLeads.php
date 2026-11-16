<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleWebsiteLeads
{
    public function handle(Request $request, Closure $next): Response
    {
        $max = (int) config('phoenix.integrations.website.rate_limit_per_minute', 30);
        $key = 'website-leads:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
