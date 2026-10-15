<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyWorkspaceIsAccessible
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $company = $this->resolveCompany($request);

        if (! $company) {
            return $next($request);
        }

        if ($company->isPlatformInactive()) {
            if ($user) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('companies.login', $company->slug)
                ->with('error', $company->platformLockMessage());
        }

        if (! $company->isAdminInactive()) {
            return $next($request);
        }

        if ($user && $user->isAdmin() && (int) $user->company_id === (int) $company->id) {
            if ($request->routeIs('company-settings.*')) {
                return $next($request);
            }

            return redirect()
                ->route('company-settings.edit')
                ->with('warning', 'This workspace is currently deactivated. Reactivate it from Company Settings to restore access for staff and tenants.');
        }

        if ($user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('companies.login', $company->slug)
            ->with('error', $company->adminLockMessage());
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
