<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Company;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitWorkflowOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_units_form_can_auto_create_tenant_lease_and_financial_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $suffix = Str::lower(Str::random(6));
        $company = Company::create([
            'name' => 'Workflow Company '.$suffix,
            'slug' => 'workflow-company-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Workflow Admin',
            'email' => 'workflow-admin+'.$suffix.'@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $building = Building::create([
            'company_id' => $company->id,
            'name' => 'Workflow Towers',
            'address_line1' => '1 Workflow Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '100001',
            'total_units' => 10,
            'total_floors' => 5,
        ]);

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'building_id' => $building->id,
            'unit_number' => 'A-101',
            'floor' => '1',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'status' => 'AVAILABLE',
            'unit_workflow_enabled' => '1',
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '08030000000',
            'tenant_move_in_date' => '2026-08-13',
            'lease_annual_amount' => 450000,
            'lease_rent_duration' => 'monthly',
            'lease_start_date' => '2026-08-13',
            'lease_end_date' => '2027-08-12',
            'create_financial_entry' => '1',
            'financial_amount' => 450000,
            'financial_payment_date' => '2026-08-13',
            'financial_payment_method' => 'BANK_TRANSFER',
            'financial_status' => 'PENDING',
        ]);

        $response->assertRedirect(route('buildings.show', ['building' => $building->id, 'tab' => 'payments']));

        $this->assertDatabaseHas('units', [
            'company_id' => $company->id,
            'building_id' => $building->id,
            'unit_number' => 'A-101',
            'status' => 'OCCUPIED',
        ]);

        $tenant = Tenant::where('company_id', $company->id)
            ->where('full_name', 'Jane Doe')
            ->first();

        $this->assertNotNull($tenant);
        $this->assertSame('j.doe@howsey.site', $tenant->email);

        $this->assertDatabaseHas('rents', [
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'annual_amount' => 450000,
            'rent_duration' => 'monthly',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('payments', [
            'company_id' => $company->id,
            'amount' => 450000,
            'payment_method' => 'BANK_TRANSFER',
            'status' => 'PENDING',
        ]);
    }
}
