<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_only_sees_their_company_tenants(): void
    {
        [$companyA, $adminA] = $this->createCompanyAdmin('Alpha Housing', 'alpha-admin@example.com');
        [$companyB] = $this->createCompanyAdmin('Beta Housing', 'beta-admin@example.com');

        $this->createTenantForCompany($companyA, 'Alice Tenant', 'alice@example.com');
        $this->createTenantForCompany($companyB, 'Bob Tenant', 'bob@example.com');

        $response = $this->actingAs($adminA)->get(route('tenants.index'));

        $response->assertOk();
        $response->assertSee('Alice Tenant');
        $response->assertDontSee('Bob Tenant');
    }

    public function test_company_admin_cannot_access_other_company_tenant_record(): void
    {
        [$companyA, $adminA] = $this->createCompanyAdmin('Alpha Housing', 'alpha-admin@example.com');
        [$companyB] = $this->createCompanyAdmin('Beta Housing', 'beta-admin@example.com');

        $foreignTenant = $this->createTenantForCompany($companyB, 'Bob Tenant', 'bob@example.com');

        $response = $this->actingAs($adminA)->get(route('tenants.show', $foreignTenant));

        $response->assertNotFound();
    }

    private function createCompanyAdmin(string $companyName, string $email): array
    {
        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => $companyName,
            'slug' => str()->slug($companyName).'-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin = User::create([
            'company_id' => $company->id,
            'name' => $companyName.' Admin',
            'email' => $this->uniquifyEmail($email, $suffix),
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        return [$company, $admin];
    }

    private function createTenantForCompany(Company $company, string $fullName, string $email): Tenant
    {
        $suffix = Str::lower(Str::random(6));
        $building = Building::create([
            'company_id' => $company->id,
            'name' => $company->name.' Tower '.$suffix,
            'address_line1' => '1 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '100001',
            'total_units' => 1,
            'total_floors' => 1,
        ]);

        $unit = Unit::create([
            'company_id' => $company->id,
            'building_id' => $building->id,
            'unit_number' => '101',
            'floor' => 1,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'status' => 'OCCUPIED',
        ]);

        return Tenant::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'full_name' => $fullName,
            'email' => $this->uniquifyEmail($email, $suffix),
            'move_in_date' => now()->toDateString(),
            'active' => true,
        ]);
    }

    private function uniquifyEmail(string $email, string $suffix): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return "{$local}+{$suffix}@{$domain}";
    }
}
