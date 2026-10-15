<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminExportOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            return response('Access denied. Only administrators can export reports.', 403);
        }

        return $next($request);
    }
}
