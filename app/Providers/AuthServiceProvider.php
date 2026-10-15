<?php

namespace App\Providers;

use App\Models\AccountingEntry;
use App\Models\Building;
use App\Models\Company;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\AccountingEntryPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\MaintenanceRequestPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\RolePolicy;
use App\Policies\TenantPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Company::class => CompanyPolicy::class,
        Building::class => BuildingPolicy::class,
        Tenant::class => TenantPolicy::class,
        Payment::class => PaymentPolicy::class,
        MaintenanceRequest::class => MaintenanceRequestPolicy::class,
        Role::class => RolePolicy::class,
        User::class => UserPolicy::class,
        AccountingEntry::class => AccountingEntryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        foreach ([
            'dashboard.view',
            'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
            'settings.view', 'settings.edit',
            'buildings.view', 'buildings.create', 'buildings.edit', 'buildings.delete',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
            'rentals.view', 'rentals.create', 'rentals.edit', 'rentals.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
            'maintenance.view', 'maintenance.create', 'maintenance.edit', 'maintenance.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'accounting.view', 'accounting.create', 'accounting.edit', 'accounting.delete',
            'exports.view', 'exports.run',
            'notifications.view', 'notifications.edit',
        ] as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermission($permission));
        }
    }
}
