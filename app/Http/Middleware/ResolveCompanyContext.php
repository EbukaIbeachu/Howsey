<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCompanyContext
{
    private const REMEMBERED_COMPANY_COOKIE = 'howsey_company_slug';
    private const REMEMBERED_COMPANY_SESSION = 'howsey_company_slug';
    private const SUPER_ADMIN_VIEW_COMPANY_SESSION = 'howsey_super_admin_view_company_id';

    public function handle(Request $request, Closure $next): Response
    {
        $company = null;
        $user = $request->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $superAdminCompanies = collect();

        if ($isSuperAdmin) {
            $superAdminCompanies = Company::query()
                ->where('is_demo', false)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            $selectedCompanyId = (int) ($request->session()->get(self::SUPER_ADMIN_VIEW_COMPANY_SESSION) ?: 0);
            if ($selectedCompanyId > 0 && ! $superAdminCompanies->pluck('id')->contains($selectedCompanyId)) {
                $selectedCompanyId = 0;
            }
            if ($selectedCompanyId <= 0 && $superAdminCompanies->isNotEmpty()) {
                $selectedCompanyId = (int) $superAdminCompanies->sortBy('id')->first()->id;
            }

            if ($selectedCompanyId > 0) {
                $company = Company::query()
                    ->whereKey($selectedCompanyId)
                    ->with(['settings', 'notificationSettings'])
                    ->first();
            }
        } elseif ($user && $user->company) {
            $company = $user->company()->with(['settings', 'notificationSettings'])->first();
        } elseif ($this->hasExplicitCompanyRoute($request) && ($slug = $request->route('company'))) {
            $company = Company::query()
                ->where('slug', $slug)
                ->with(['settings', 'notificationSettings'])
                ->first();
        } elseif (! $isSuperAdmin && ($querySlug = $request->query('company'))) {
            $company = Company::query()
                ->where('slug', $querySlug)
                ->with(['settings', 'notificationSettings'])
                ->first();
        } elseif (! $isSuperAdmin && ($host = $request->getHost())
            && ! $this->isPlatformHost($host)
            && ($subdomain = explode('.', $host)[0] ?? null)
            && ! in_array($subdomain, ['www', 'localhost', '127'], true)) {
            $company = $this->resolveCompany($subdomain);
        } elseif (! $isSuperAdmin && ($sessionSlug = $request->session()->get(self::REMEMBERED_COMPANY_SESSION))) {
            $company = $this->resolveCompany($sessionSlug);
        } elseif (! $isSuperAdmin && ($cookieSlug = $request->cookie(self::REMEMBERED_COMPANY_COOKIE))) {
            $company = $this->resolveCompany($cookieSlug);
        }

        if ($company && ! $isSuperAdmin) {
            $request->session()->put(self::REMEMBERED_COMPANY_SESSION, $company->slug);
            cookie()->queue(cookie(self::REMEMBERED_COMPANY_COOKIE, $company->slug, 60 * 24 * 30));
        } elseif ($isSuperAdmin) {
            $this->forgetRememberedCompanyContext($request);
        }

        app(CurrentCompany::class)->set($company);
        view()->share('currentCompany', $company);
        view()->share('currentCompanySettings', optional($company)->settings);
        view()->share('superAdminViewCompanies', $superAdminCompanies);
        view()->share('superAdminViewingCompany', $isSuperAdmin ? $company : null);
        view()->share('superAdminViewingCompanyId', $isSuperAdmin ? optional($company)->id : null);

        return $next($request);
    }

    private function resolveCompany(string $slug): ?Company
    {
        return Company::query()
            ->where('slug', $slug)
            ->with(['settings', 'notificationSettings'])
            ->first();
    }

    private function hasExplicitCompanyRoute(Request $request): bool
    {
        return $request->routeIs(
            'companies.portal',
            'companies.login',
            'companies.login.*',
            'companies.register',
            'companies.register.*'
        );
    }

    private function forgetRememberedCompanyContext(Request $request): void
    {
        $request->session()->forget(self::REMEMBERED_COMPANY_SESSION);
        cookie()->queue(cookie()->forget(self::REMEMBERED_COMPANY_COOKIE));
    }

    private function isPlatformHost(string $host): bool
    {
        $platformHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $platformHost) {
            return false;
        }

        $host = strtolower($host);
        $platformHost = strtolower($platformHost);

        return $host === $platformHost || $host === 'www.'.$platformHost;
    }
}
