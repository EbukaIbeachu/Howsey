<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AutoEmailGenerator;
use App\Support\CurrentCompany;
use App\Support\RoleRequestNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register', ['registrationCompany' => app(CurrentCompany::class)->get()]);
    }

    public function showCompanyRegistrationForm(string $company)
    {
        return view('auth.register', ['registrationCompany' => $this->resolveCompany($company)]);
    }

    public function register(Request $request)
    {
        return $this->handleStaffRegistration($request, app(CurrentCompany::class)->get());
    }

    public function registerForCompany(Request $request, string $company)
    {
        return $this->handleStaffRegistration($request, $this->resolveCompany($company));
    }

    public function showTenantRegistrationForm()
    {
        return view('auth.register-tenant', ['registrationCompany' => app(CurrentCompany::class)->get()]);
    }

    public function showCompanyTenantRegistrationForm(string $company)
    {
        return view('auth.register-tenant', ['registrationCompany' => $this->resolveCompany($company)]);
    }

    public function registerTenant(Request $request)
    {
        return $this->handleTenantRegistration($request, app(CurrentCompany::class)->get());
    }

    public function registerTenantForCompany(Request $request, string $company)
    {
        return $this->handleTenantRegistration($request, $this->resolveCompany($company));
    }

    private function handleStaffRegistration(Request $request, ?Company $registrationCompany = null)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:manager,accountant'],
        ]);

        $roleName = $request->role;
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return back()->withErrors(['role' => ucfirst($roleName).' role does not exist.']);
        }

        $generatedAccountId = AutoEmailGenerator::generate(
            $request->name,
            $roleName,
            $this->registrationDomain($registrationCompany)
        );

        $user = User::create($this->buildUserPayload([
            'name' => $request->name,
            'email' => $generatedAccountId,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'company_id' => $registrationCompany?->id,
            'phone' => $request->phone,
            'is_active' => false,
        ]));

        $payload = [
            'title' => ucfirst($roleName).' registration pending approval',
            'message' => $request->name.' created a '.$roleName.' profile with account ID '.$generatedAccountId.'.',
            'url' => route('users.index'),
            'request_type' => 'staff_registration',
            'created_by' => $request->name,
            'subject_id' => $user->id,
        ];

        if ($registrationCompany) {
            $this->notifyCompanyAdmins($registrationCompany, $payload);
        } else {
            RoleRequestNotifier::notifyAdmins($payload);
        }

        return redirect($this->loginRedirect($registrationCompany))
            ->with('success', ucfirst($roleName).' registration successful. Please wait for admin approval.');
    }

    private function handleTenantRegistration(Request $request, ?Company $registrationCompany = null)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::where('name', 'tenant')->first();
        if (! $role) {
            return back()->withErrors(['role' => 'Tenant role configuration error.']);
        }

        $generatedAccountId = AutoEmailGenerator::generate(
            $request->name,
            'tenant',
            $this->registrationDomain($registrationCompany)
        );

        $existingTenant = Tenant::with('unit.building')
            ->where('email', $generatedAccountId)
            ->when($registrationCompany, function ($query) use ($registrationCompany) {
                $query->where('company_id', $registrationCompany->id);
            })
            ->first();

        if (! $existingTenant || ! $existingTenant->unit_id || ! optional($existingTenant->unit)->building) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'A tenant account can only be created after the tenant has been registered to a building/unit. Contact management first.']);
        }

        if (! $existingTenant->active) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This tenant record is inactive, so a login account cannot be created.']);
        }

        if ($existingTenant->user_id) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'A login account already exists for this tenant record.']);
        }

        $user = User::create($this->buildUserPayload([
            'name' => $request->name,
            'email' => $generatedAccountId,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'company_id' => $existingTenant->company_id,
            'phone' => $request->phone,
            'is_active' => false,
        ]));

        $existingTenant->update(['user_id' => $user->id]);

        $existingTenant->loadMissing(['unit.building.manager']);
        $manager = optional(optional($existingTenant->unit)->building)->manager;

        if ($manager) {
            RoleRequestNotifier::notifyUser($manager, [
                'title' => 'Tenant account pending approval',
                'message' => $user->name.' created a tenant account for review.',
                'url' => route('users.index'),
                'request_type' => 'tenant_registration',
                'created_by' => $user->name,
                'subject_id' => $user->id,
            ]);
        } else {
            $payload = [
                'title' => 'Tenant account pending approval',
                'message' => $user->name.' created a tenant account, but no building manager is assigned yet.',
                'url' => route('users.index'),
                'request_type' => 'tenant_registration',
                'created_by' => $user->name,
                'subject_id' => $user->id,
            ];

            if ($registrationCompany) {
                $this->notifyCompanyAdmins($registrationCompany, $payload);
            } else {
                RoleRequestNotifier::notifyAdmins($payload);
            }
        }

        return redirect($this->loginRedirect($registrationCompany))
            ->with('success', 'Tenant registration successful. Please wait for management approval.');
    }

    public function showAdminRegistrationForm()
    {
        return view('auth.register-admin');
    }

    public function registerAdmin(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::where('name', 'admin')->first();
        if (! $role) {
            $role = Role::create(['name' => 'admin', 'description' => 'Administrator']);
        }

        // Check if this is the first user, if so, make active. Otherwise, require approval?
        // Or just make all admins from this page active as it's a special page.
        // I'll make them active to ensure at least one admin can get in.
        $generatedAccountId = AutoEmailGenerator::generate($request->name, 'admin', AutoEmailGenerator::DEFAULT_DOMAIN);

        $user = User::create($this->buildUserPayload([
            'name' => $request->name,
            'email' => $generatedAccountId,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'phone' => $request->phone,
            'is_active' => true,
        ]));

        return redirect()->route('login')->with('success', 'Admin registration successful. You can now login.');
    }

    private function buildUserPayload(array $attributes): array
    {
        if (! Schema::hasColumn('users', 'phone')) {
            unset($attributes['phone']);
        }

        return $attributes;
    }

    private function resolveCompany(string $company): Company
    {
        return Company::query()
            ->where('slug', $company)
            ->with('settings')
            ->firstOrFail();
    }

    private function registrationDomain(?Company $company = null): string
    {
        return AutoEmailGenerator::domainForCompany($company);
    }

    private function loginRedirect(?Company $company = null): string
    {
        return $company ? route('companies.login', $company->slug) : route('login');
    }

    private function notifyCompanyAdmins(Company $company, array $payload): void
    {
        $recipients = User::query()
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['admin']);
            })
            ->where('company_id', $company->id)
            ->get();

        RoleRequestNotifier::notifyUsers($recipients, $payload);
    }
}
