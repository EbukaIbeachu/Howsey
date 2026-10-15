<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $company = $this->resolveCompany($request);

        if (! $company || ! $company->hasExpiredSubscription()) {
            return $next($request);
        }

        if ($user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('companies.subscription.show', $company->slug)
            ->with('error', 'This workspace subscription has expired. Renew the annual plan to restore access for every user in the workspace.');
    }

    private function resolveCompany(Request $request): ?Company
    {
        $user = $request->user();

        if ($user && $user->company_id) {
            return $user->company()->with('settings')->first();
        }

        $currentCompany = app(CurrentCompany::class)->get();
        if ($currentCompany) {
            return $currentCompany;
        }

        $slug = $request->route('company');
        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        return Company::query()
            ->where('slug', $slug)
            ->with('settings')
            ->first();
    }
}
