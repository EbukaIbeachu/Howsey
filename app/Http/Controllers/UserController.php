<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\AutoEmailGenerator;
use App\Support\RoleRequestNotifier;
use App\Support\CurrentCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (! $user->hasPermission('users.view')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $query = User::with('role');
        $viewCompanyId = app(CurrentCompany::class)->id();

        if (auth()->user()->isSuperAdmin() && $viewCompanyId) {
            $query->where('company_id', $viewCompanyId);
        } elseif (! auth()->user()->isSuperAdmin()) {
            $query->where('company_id', auth()->user()->company_id);
            $query->whereHas('role', function ($q) {
                $q->where('name', '!=', 'super_admin');
            });
        }

        if (auth()->user()->isManager()) {
            $query->whereHas('role', function ($q) {
                $q->where('name', 'tenant');
            })->whereHas('tenant.unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }

        $users = $query->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('users.create'), 403);

        $roles = Role::query()
            ->whereIn('name', ['tenant', 'manager', 'accountant', 'admin'])
            ->get()
            ->sortBy(function ($role) {
                return array_search($role->name, ['tenant', 'manager', 'accountant', 'admin'], true);
            })
            ->values();

        if (auth()->user()->isManager()) {
            $roles = $roles->where('name', 'tenant');
        }

        $availableTenants = $this->availableTenantCandidates();

        return view('users.create', compact('roles', 'availableTenants'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('users.create'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'tenant_id' => 'nullable|integer',
        ]);

        $role = Role::find($validated['role_id']);

        if (auth()->user()->isManager()) {
            if (! $role || $role->name !== 'tenant') {
                return back()->withInput()->withErrors(['role_id' => 'Managers can only create tenant users.']);
            }
        }

        $targetRole = strtolower((string) optional($role)->name);
        $linkedTenant = null;
        $resolvedCompanyId = auth()->user()->isSuperAdmin()
            ? app(CurrentCompany::class)->id()
            : auth()->user()->company_id;

        if ($targetRole === 'tenant') {
            $linkedTenant = $this->resolveTenantForAccountCreation($request->integer('tenant_id'));
            if (! $linkedTenant) {
                return back()->withInput()->withErrors([
                    'tenant_id' => 'Select a tenant who is already registered to a building/unit before creating a tenant account.',
                ]);
            }

            if (! $linkedTenant->active) {
                return back()->withInput()->withErrors([
                    'tenant_id' => 'Inactive or former tenants cannot be given an active login account.',
                ]);
            }

            $validated['name'] = $linkedTenant->full_name;
            $validated['email'] = $linkedTenant->email;
            $validated['phone'] = $linkedTenant->phone ?: ($validated['phone'] ?? null);
            $resolvedCompanyId = $linkedTenant->company_id;
        } elseif (in_array($targetRole, ['admin', 'manager', 'accountant'], true)) {
            if (! $resolvedCompanyId) {
                return back()->withInput()->withErrors([
                    'role_id' => 'Select the company workspace before creating admin, manager, or accountant accounts.',
                ]);
            }

            $resolvedCompany = \App\Models\Company::query()->find($resolvedCompanyId);
            $validated['email'] = AutoEmailGenerator::generate(
                $validated['name'],
                $targetRole,
                AutoEmailGenerator::domainForCompany($resolvedCompany)
            );
        } elseif (blank($validated['email'])) {
            return back()->withInput()->withErrors(['email' => 'Account ID is required for this role.']);
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $targetRole === 'tenant'
            ? (bool) optional($linkedTenant)->active
            : true;
        $validated['company_id'] = $resolvedCompanyId;

        $user = User::create($validated);
        app(AuditLogService::class)->log('user_created', $user, null, $user->toArray());

        if ($linkedTenant) {
            $linkedTenant->update(['user_id' => $user->id]);

            $linkedTenant->loadMissing(['unit.building.manager']);
            $manager = optional(optional($linkedTenant->unit)->building)->manager;

            if ($manager) {
                RoleRequestNotifier::notifyUser($manager, [
                    'title' => 'Tenant account created',
                    'message' => $user->name.' now has a tenant account ready for review.',
                    'url' => route('users.index'),
                    'request_type' => 'tenant_account_created',
                    'created_by' => optional(auth()->user())->name,
                    'subject_id' => $user->id,
                ], auth()->id());
            } else {
                RoleRequestNotifier::notifyAdmins([
                    'title' => 'Tenant account created without assigned manager',
                    'message' => $user->name.' now has a tenant account, but the building manager is not assigned.',
                    'url' => route('users.index'),
                    'request_type' => 'tenant_account_created',
                    'created_by' => optional(auth()->user())->name,
                    'subject_id' => $user->id,
                ], auth()->id());
            }
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.view'), 403);
        $this->abortIfProtectedSuperAdmin($user);
        $this->abortIfOutsideCompany($user);

        if (auth()->user()->isManager()) {
            if (! $user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);
        $this->abortIfProtectedSuperAdmin($user);
        $this->abortIfOutsideCompany($user);

        if (auth()->user()->isManager()) {
            if (! $user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $roles = Role::all();
        if (auth()->user()->isManager()) {
            $roles = $roles->where('name', 'tenant');
        }

        return view('users.edit', [
            'user' => $user,
            'roles' => $roles,
            'linkedTenant' => $this->findTenantForUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);
        $this->abortIfProtectedSuperAdmin($user);
        $this->abortIfOutsideCompany($user);

        if (auth()->user()->isManager()) {
            if (! $user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $role = Role::find($validated['role_id']);
        if (auth()->user()->isManager()) {
            if (! $role || $role->name !== 'tenant') {
                return back()->withInput()->withErrors(['role_id' => 'Managers can only assign tenant role.']);
            }
        }

        $targetRole = strtolower((string) optional($role)->name);
        if (! $request->has('is_active')) {
            $validated['is_active'] = false;
        }

        if ($targetRole === 'tenant') {
            $linkedTenant = $this->findTenantForUser($user);
            if (! $linkedTenant || ! $linkedTenant->unit_id || ! optional($linkedTenant->unit)->building) {
                return back()->withInput()->withErrors([
                    'role_id' => 'Tenant users must remain linked to a tenant who is already assigned to a building/unit.',
                ]);
            }

            if (auth()->user()->isManager() && (int) optional(optional($linkedTenant->unit)->building)->manager_id !== (int) auth()->id()) {
                return back()->withInput()->withErrors([
                    'role_id' => 'Managers can only manage tenant users in their assigned buildings.',
                ]);
            }

            if (! $linkedTenant->user_id) {
                $linkedTenant->update(['user_id' => $user->id]);
            }

            $validated['name'] = $linkedTenant->full_name;
            $validated['email'] = $linkedTenant->email;
            $validated['phone'] = $linkedTenant->phone ?: ($validated['phone'] ?? null);
            $validated['is_active'] = (bool) $linkedTenant->active && (bool) ($validated['is_active'] ?? false);
        }

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $oldValues = $user->toArray();
        $user->update($validated);
        app(AuditLogService::class)->log('user_updated', $user, $oldValues, $user->toArray());

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.delete'), 403);
        $this->abortIfProtectedSuperAdmin($user);
        $this->abortIfOutsideCompany($user);

        if (auth()->user()->isManager()) {
            if (! $user->isTenant()) {
                abort(403, 'Unauthorized action.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // Check dependencies (e.g., if user is a tenant with active rent)
        if ($user->tenant && $user->tenant->rents()->active()->exists()) {
            return back()->with('error', 'Cannot delete user with active rental agreements.');
        }

        app(AuditLogService::class)->log('user_deleted', $user, $user->toArray(), null);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);
        $this->abortIfProtectedSuperAdmin($user);
        $this->abortIfOutsideCompany($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate yourself.');
        }

        if (auth()->user()->isManager()) {
            if (! $user->isTenant()) {
                return back()->with('error', 'Managers can only approve/deactivate tenants.');
            }
            $managerId = optional(optional(optional($user->tenant)->unit)->building)->manager_id;
            if ($managerId !== auth()->id()) {
                return back()->with('error', 'Unauthorized action.');
            }
        }

        $oldValues = $user->toArray();
        $user->update(['is_active' => ! $user->is_active]);
        app(AuditLogService::class)->log('user_status_toggled', $user, $oldValues, $user->toArray());

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User has been $status.");
    }

    private function availableTenantCandidates()
    {
        $query = Tenant::with(['unit.building'])
            ->whereNotNull('unit_id');

        if (auth()->user()->isManager()) {
            $query->whereHas('unit.building', function ($builder) {
                $builder->where('manager_id', auth()->id());
            });
        }

        $tenants = $query->get();
        $accountIdsInUse = User::query()
            ->whereIn('email', $tenants->pluck('email')->filter()->unique()->values())
            ->pluck('email')
            ->flip();

        return $tenants
            ->filter(function (Tenant $tenant) use ($accountIdsInUse) {
                return blank($tenant->user_id)
                    && filled($tenant->email)
                    && $tenant->unit
                    && $tenant->unit->building
                    && ! $accountIdsInUse->has($tenant->email);
            })
            ->sortBy(function (Tenant $tenant) {
                return sprintf(
                    '%s|%s|%s',
                    strtolower((string) optional($tenant->unit->building)->name),
                    strtolower((string) optional($tenant->unit)->unit_number),
                    strtolower((string) $tenant->full_name)
                );
            })
            ->values();
    }

    private function resolveTenantForAccountCreation(?int $tenantId): ?Tenant
    {
        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::with(['unit.building'])->find($tenantId);
        if (! $tenant || ! $tenant->unit_id || ! $tenant->unit || ! $tenant->unit->building) {
            return null;
        }

        if (auth()->user()->isManager() && (int) $tenant->unit->building->manager_id !== (int) auth()->id()) {
            return null;
        }

        if (filled($tenant->user_id)) {
            return null;
        }

        if (blank($tenant->email) || User::where('email', $tenant->email)->exists()) {
            return null;
        }

        return $tenant;
    }

    private function findTenantForUser(User $user): ?Tenant
    {
        $tenant = $user->tenant()->with(['unit.building'])->first();
        if ($tenant) {
            return $tenant;
        }

        return Tenant::with(['unit.building'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($tenantQuery) use ($user) {
                        $tenantQuery->whereNull('user_id')
                            ->where('email', $user->email);
                    });
            })
            ->first();
    }

    private function abortIfProtectedSuperAdmin(User $user): void
    {
        if (! auth()->user()->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function abortIfOutsideCompany(User $user): void
    {
        if (! auth()->user()->isSuperAdmin() && (int) $user->company_id !== (int) auth()->user()->company_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
