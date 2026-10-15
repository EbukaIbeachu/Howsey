<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RbacPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_company_management_permission_cannot_open_companies_index(): void
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Gamma Housing',
            'slug' => 'gamma-housing-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $role = Role::create([
            'name' => 'limited_admin',
            'slug' => 'limited-admin',
            'description' => 'Limited Admin',
            'is_system' => false,
        ]);

        $dashboardPermission = Permission::where('name', 'dashboard.view')->firstOrFail();
        $role->permissions()->sync([$dashboardPermission->id]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Limited User',
            'email' => 'limited+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('companies.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_open_companies_index(): void
    {
        $suffix = Str::lower(Str::random(6));
        $role = Role::where('name', 'super_admin')->firstOrFail();

        $user = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('companies.index'));

        $response->assertOk();
    }

    public function test_super_admin_uses_default_howsey_branding_even_with_remembered_company_context(): void
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Tenant Brand '.$suffix,
            'slug' => 'tenant-brand-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'primary_color' => '#ef4444',
            'logo_path' => 'company-logos/test-logo.png',
            'email_header' => $company->name,
            'receipt_header' => 'Tenant Brand Receipt',
        ]);

        $role = Role::where('name', 'super_admin')->firstOrFail();

        $user = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-brand+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession(['howsey_company_slug' => $company->slug])
            ->withCookie('howsey_company_slug', $company->slug)
            ->actingAs($user)
            ->get(route('companies.create'));

        $response->assertOk();
        $response->assertSee('HOWSEY', false);
        $response->assertSee('/pwa-icon.png', false);
        $response->assertDontSee('/pwa-icon.png?company='.$company->slug, false);
        $response->assertSee('#7c3aed', false);
        $response->assertDontSee('#ef4444', false);
    }

    public function test_super_admin_company_switcher_filters_users_page_to_selected_company(): void
    {
        $suffix = Str::lower(Str::random(6));
        $companyA = Company::create([
            'name' => 'Alpha Context '.$suffix,
            'slug' => 'alpha-context-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $companyB = Company::create([
            'name' => 'Beta Context '.$suffix,
            'slug' => 'beta-context-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        $alphaUser = User::create([
            'company_id' => $companyA->id,
            'name' => 'Alpha Viewer',
            'email' => 'alpha-viewer+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $betaUser = User::create([
            'company_id' => $companyB->id,
            'name' => 'Beta Viewer',
            'email' => 'beta-viewer+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-context+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.view-company'), ['company_id' => $companyA->id])
            ->assertSessionHas('howsey_super_admin_view_company_id', $companyA->id);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Viewing Company: '.$companyA->name);
        $response->assertSee($alphaUser->name);
        $response->assertDontSee($betaUser->name);
    }

    public function test_super_admin_gets_platform_dashboard_instead_of_company_operations_dashboard(): void
    {
        $suffix = Str::lower(Str::random(6));
        Company::create([
            'name' => 'Platform Company '.$suffix,
            'slug' => 'platform-company-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-dashboard+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('HOWSEY Platform Dashboard');
        $response->assertSee('Manage Companies');
        $response->assertDontSee('This dashboard is for HOWSEY itself, not one company’s operations.');
        $response->assertDontSee('Demo Companies');
        $response->assertDontSee('Recent Payments');
        $response->assertDontSee('Overdue Rents');
    }

    public function test_super_admin_platform_dashboard_hides_demo_companies(): void
    {
        $suffix = Str::lower(Str::random(6));
        Company::create([
            'name' => 'Live Company '.$suffix,
            'slug' => 'live-company-'.$suffix,
            'status' => 'active',
            'is_demo' => false,
            'onboarded_at' => now(),
        ]);

        Company::create([
            'name' => 'Demo Company '.$suffix,
            'slug' => 'demo-company-'.$suffix,
            'status' => 'active',
            'is_demo' => true,
            'onboarded_at' => now(),
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-demo-hide+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Live Company '.$suffix);
        $response->assertDontSee('Demo Company '.$suffix);
        $response->assertDontSee('Demo Companies');
        $response->assertDontSee('Demo</span>', false);
    }

    public function test_main_howsey_home_hides_visible_login_link_while_company_portal_keeps_it(): void
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Portal Company '.$suffix,
            'slug' => 'portal-company-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'primary_color' => '#0f766e',
            'email_header' => $company->name,
            'receipt_header' => 'Portal Receipt',
        ]);

        $mainResponse = $this->get(url('/'));

        $mainResponse->assertOk();
        $mainResponse->assertDontSee('Log in');
        $mainResponse->assertDontSee(route('login'), false);
        $mainResponse->assertSee('Create Company');

        $portalResponse = $this->get(route('companies.portal', $company->slug));

        $portalResponse->assertOk();
        $portalResponse->assertSee('Log in');
        $portalResponse->assertSee(route('companies.login', $company->slug), false);
    }

    public function test_main_howsey_home_hides_pwa_install_hooks_but_company_portal_keeps_them(): void
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Pwa Portal '.$suffix,
            'slug' => 'pwa-portal-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        CompanySetting::create([
            'company_id' => $company->id,
            'primary_color' => '#2563eb',
            'email_header' => $company->name,
            'receipt_header' => 'PWA Portal Receipt',
        ]);

        $mainResponse = $this->get(url('/'));

        $mainResponse->assertOk();
        $mainResponse->assertDontSee('manifest.webmanifest', false);
        $mainResponse->assertDontSee('portal-pwa-install-button', false);
        $mainResponse->assertDontSee('service-worker.js', false);

        $portalResponse = $this->get(route('companies.portal', $company->slug));

        $portalResponse->assertOk();
        $portalResponse->assertSee('manifest.webmanifest', false);
        $portalResponse->assertSee('portal-pwa-install-button', false);
        $portalResponse->assertSee('service-worker.js', false);
    }

    public function test_super_admin_sees_delete_action_on_company_edit_page(): void
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Edit Company '.$suffix,
            'slug' => 'edit-company-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-edit-delete+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('companies.edit', $company));

        $response->assertOk();
        $response->assertSee('Delete Company');
        $response->assertSee(route('companies.destroy', $company), false);
    }

    public function test_super_admin_can_delete_company_and_related_records(): void
    {
        $suffix = Str::lower(Str::random(6));
        $companyToDelete = Company::create([
            'name' => 'Delete Me '.$suffix,
            'slug' => 'delete-me-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $fallbackCompany = Company::create([
            'name' => 'Fallback '.$suffix,
            'slug' => 'fallback-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        CompanySetting::create([
            'company_id' => $companyToDelete->id,
            'primary_color' => '#22c55e',
            'email_header' => 'Delete Me',
            'receipt_header' => 'Delete Me Receipt',
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        $companyUser = User::create([
            'company_id' => $companyToDelete->id,
            'name' => 'Delete User',
            'email' => 'delete-user+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $buildingId = DB::table('buildings')->insertGetId([
            'company_id' => $companyToDelete->id,
            'name' => 'Delete Building',
            'address_line1' => '123 Delete Street',
            'city' => 'Lagos',
            'state' => 'LA',
            'postal_code' => '100001',
            'total_units' => 1,
            'total_floors' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unitId = DB::table('units')->insertGetId([
            'company_id' => $companyToDelete->id,
            'building_id' => $buildingId,
            'unit_number' => 'A1',
            'status' => 'AVAILABLE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenantId = DB::table('tenants')->insertGetId([
            'company_id' => $companyToDelete->id,
            'unit_id' => $unitId,
            'full_name' => 'Delete Tenant',
            'move_in_date' => now()->toDateString(),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rentId = DB::table('rents')->insertGetId([
            'company_id' => $companyToDelete->id,
            'tenant_id' => $tenantId,
            'unit_id' => $unitId,
            'annual_amount' => 120000,
            'rent_duration' => 'annually',
            'due_day' => 1,
            'start_date' => now()->toDateString(),
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentId = DB::table('payments')->insertGetId([
            'company_id' => $companyToDelete->id,
            'rent_id' => $rentId,
            'payment_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'amount' => 120000,
            'status' => 'COMPLETED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner-delete+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $expectedFallbackCompanyId = Company::query()
            ->whereKeyNot($companyToDelete->id)
            ->orderBy('id')
            ->value('id');

        $response = $this
            ->withSession(['howsey_super_admin_view_company_id' => $companyToDelete->id])
            ->actingAs($superAdmin)
            ->delete(route('companies.destroy', $companyToDelete));

        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHas('success', 'Company "'.$companyToDelete->name.'" deleted successfully.');
        $response->assertSessionHas('howsey_super_admin_view_company_id', $expectedFallbackCompanyId);

        $this->assertDatabaseMissing('companies', ['id' => $companyToDelete->id]);
        $this->assertDatabaseMissing('company_settings', ['company_id' => $companyToDelete->id]);
        $this->assertDatabaseMissing('users', ['id' => $companyUser->id]);
        $this->assertDatabaseMissing('buildings', ['id' => $buildingId]);
        $this->assertDatabaseMissing('units', ['id' => $unitId]);
        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
        $this->assertDatabaseMissing('rents', ['id' => $rentId]);
        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
        $this->assertDatabaseHas('companies', ['id' => $fallbackCompany->id]);
    }
}
