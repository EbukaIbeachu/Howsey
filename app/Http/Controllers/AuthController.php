<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CurrentCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const REMEMBERED_COMPANY_COOKIE = 'howsey_company_slug';
    private const REMEMBERED_COMPANY_SESSION = 'howsey_company_slug';

    public function showLogin(Request $request, ?string $company = null)
    {
        $loginCompany = $company
            ? Company::where('slug', $company)->with('settings')->first()
            : null;

        $rootCompanyAdminAccess = ! $company
            && ($request->query('entry') === 'company-admin'
                || (bool) $request->session()->getOldInput('root_company_admin_access'));

        $newWorkspace = $request->session()->get('new_workspace');
        $prefillAccountId = is_array($newWorkspace)
            && $loginCompany
            && ($newWorkspace['slug'] ?? null) === $loginCompany->slug
                ? (string) ($newWorkspace['account_id'] ?? '')
                : '';

        return view('auth.login', compact('loginCompany', 'rootCompanyAdminAccess', 'prefillAccountId'));
    }

    public function login(Request $request)
    {
        $rootCompanyAdminAccess = $request->boolean('root_company_admin_access');

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'workspace' => [$rootCompanyAdminAccess ? 'required' : 'nullable', 'string', 'max:255'],
        ]);
        $throttleKey = Str::transliterate(Str::lower($validated['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in '
                    .ceil(RateLimiter::availableIn($throttleKey) / 60).' minute(s).',
            ])->onlyInput('email');
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        $requestedCompany = $request->route('company');
        $company = $requestedCompany
            ? Company::where('slug', $requestedCompany)->first()
            : ($rootCompanyAdminAccess ? $this->resolveWorkspaceCompany($validated['workspace'] ?? null) : app(CurrentCompany::class)->get());

        if ($rootCompanyAdminAccess && ! $company) {
            return back()->withErrors([
                'workspace' => 'Enter a valid company workspace link or slug.',
            ])->withInput($request->except('password'));
        }

        if ($company && $company->hasExpiredSubscription()) {
            return back()
                ->withErrors([
                    $rootCompanyAdminAccess ? 'workspace' : 'email' => 'This workspace subscription has expired. Renew the annual plan before any user can sign in again.',
                ])
                ->with('expiredCompanySlug', $company->slug)
                ->withInput($request->except('password'));
        }

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($throttleKey);

            if (! Auth::user()->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Your account is inactive or pending approval.',
                ])->onlyInput('email');
            }

            if ($rootCompanyAdminAccess && ! Auth::user()->isAdmin()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Only company admin accounts can sign in from the main HOWSEY homepage. Other roles should use their company link.',
                ])->withInput($request->except('password'));
            }

            if (! Auth::user()->isSuperAdmin() && $company && (int) Auth::user()->company_id !== (int) $company->id) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'This account does not belong to the selected company workspace.',
                ])->withInput($request->except('password'));
            }

            $request->session()->regenerate();

            if ($company && ! Auth::user()->isSuperAdmin()) {
                $request->session()->put(self::REMEMBERED_COMPANY_SESSION, $company->slug);
                cookie()->queue(cookie(self::REMEMBERED_COMPANY_COOKIE, $company->slug, 60 * 24 * 30));
            }

            if ($company && $company->isPlatformInactive() && ! Auth::user()->isSuperAdmin()) {
                Auth::logout();

                return back()->withErrors([
                    $rootCompanyAdminAccess ? 'workspace' : 'email' => $company->platformLockMessage(),
                ])->withInput($request->except('password'));
            }

            if ($company && $company->isAdminInactive() && ! Auth::user()->isSuperAdmin()) {
                if (! Auth::user()->isAdmin()) {
                    Auth::logout();

                    return back()->withErrors([
                        'email' => $company->adminLockMessage(),
                    ])->withInput($request->except('password'));
                }

                return redirect()
                    ->route('company-settings.edit')
                    ->with('warning', 'This workspace is currently deactivated. Reactivate it from Company Settings to restore access for staff and tenants.');
            }

            $destination = Auth::user()->isAccountant()
                ? route('accounting.index')
                : route('dashboard');

            return redirect()->intended($destination);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'The provided account ID and password do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function resolveWorkspaceCompany(?string $workspace): ?Company
    {
        $slug = $this->extractWorkspaceSlug($workspace);

        if (! $slug) {
            return null;
        }

        return Company::where('slug', $slug)->first();
    }

    private function extractWorkspaceSlug(?string $workspace): ?string
    {
        $workspace = trim((string) $workspace);

        if ($workspace === '') {
            return null;
        }

        if (filter_var($workspace, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($workspace, PHP_URL_PATH), '/');
            $segments = array_values(array_filter(explode('/', $path)));

            if (($segments[0] ?? null) === 'c' && isset($segments[1])) {
                return $segments[1];
            }

            return $segments[count($segments) - 1] ?? null;
        }

        $workspace = trim($workspace, '/');

        if (str_starts_with($workspace, 'c/')) {
            $segments = explode('/', $workspace);

            return $segments[1] ?? null;
        }

        return $workspace;
    }
}
