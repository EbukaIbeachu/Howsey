<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictAccountantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->isAccountant()) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route ? $route->getName() : null;

        if ($name === 'logout') {
            return $next($request);
        }

        if ($name === 'dashboard') {
            return redirect()->route('accounting.index');
        }

        if ($name === 'coins.show' || $name === 'coins.update') {
            return $next($request);
        }

        if (is_string($name) && str_starts_with($name, 'accounting.')) {
            return $next($request);
        }

        $path = ltrim((string) $request->path(), '/');
        if ($path === 'dashboard') {
            return redirect()->route('accounting.index');
        }
        if ($path === 'coins') {
            return $next($request);
        }
        if ($path === 'accounting' || str_starts_with($path, 'accounting/')) {
            return $next($request);
        }

        return redirect()->route('accounting.index');
    }
}
