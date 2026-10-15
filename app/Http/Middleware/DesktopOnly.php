<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DesktopOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = strtolower($request->header('User-Agent', ''));

        $isMobile = str_contains($userAgent, 'mobile') ||
                    str_contains($userAgent, 'android') ||
                    str_contains($userAgent, 'iphone') ||
                    str_contains($userAgent, 'ipad');

        if ($isMobile) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This action is only allowed on desktop devices.'], 403);
            }

            return redirect()->back()->with('error', 'This action is only allowed on desktop devices.');
        }

        return $next($request);
    }
}
