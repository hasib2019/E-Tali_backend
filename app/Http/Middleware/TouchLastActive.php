<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refreshes the authenticated user's `last_active_at` at most once every few
 * minutes (cache-throttled) so "who's online" stays cheap — one write, not one
 * per request.
 */
class TouchLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user) {
            $key = "last_active:{$user->id}";
            if (! Cache::has($key)) {
                Cache::put($key, true, now()->addMinutes(3));
                User::whereKey($user->id)->update(['last_active_at' => now()]);
            }
        }

        return $response;
    }
}
