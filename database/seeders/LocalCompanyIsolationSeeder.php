<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalCompanyIsolationSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $this->seedCompanyWorkspace(
            adminRole: $adminRole,
            companyData: [
                'name' => 'Company A',
                'slug' => 'company-a',
                'status' => 'active',
                'is_demo' => true,
            ],
            settingsData: [
                'primary_color' => '#2563eb',
                'email_header' => 'Company A',
                'receipt_header' => 'Official Company A Payment Receipt',
                'address' => '1 Company A Close, Lagos',
                'support_email' => 'support@company-a.howsey.local',
                'support_phone' => '08000000001',
            ],
            adminData: [
                'name' => 'Company A Admin',
                'email' => 'admin@company-a.howsey.local',
                'phone' => '08010000001',
                'password' => 'ChangeMe123!',
            ],
            buildingData: [
                'name' => 'Company A Towers',
                'address_line1' => '10 Alpha Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'postal_code' => '100001',
            ],
            unitData: [
                'unit_number' => 'A-101',
                'floor' => 1,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'status' => 'OCCUPIED',
            ],
            tenantData: [
                'full_name' => 'Alice Company A Tenant',
                'email' => 'alice@company-a.howsey.local',
                'phone' => '08020000001',
                'room_number' => 'A-101',
            ],
            rentData: [
                'annual_amount' => 1200000,
                'rent_duration' => 'annually',
                'due_day' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'ACTIVE',
            ],
            paymentData: [
                'payment_date' => '2026-01-03',
                'due_date' => '2026-01-01',
                'amount' => 1200000,
                'payment_method' => 'bank_transfer',
                'status' => 'COMPLETED',
                'reference' => 'COMPANY-A-PAY-001',
                'notes' => 'Local isolation verification payment',
            ],
        );

        $this->seedCompanyWorkspace(
            adminRole: $adminRole,
            companyData: [
                'name' => 'Company B',
                'slug' => 'company-b',
                'status' => 'active',
                'is_demo' => true,
            ],
            settingsData: [
                'primary_color' => '#059669',
                'email_header' => 'Company B',
                'receipt_header' => 'Official Company B Payment Receipt',
                'address' => '2 Company B Avenue, Abuja',
                'support_email' => 'support@company-b.howsey.local',
                'support_phone' => '08000000002',
            ],
            adminData: [
                'name' => 'Company B Admin',
                'email' => 'admin@company-b.howsey.local',
                'phone' => '08010000002',
                'password' => 'ChangeMe123!',
            ],
            buildingData: [
                'name' => 'Company B Residences',
                'address_line1' => '20 Beta Crescent',
                'city' => 'Abuja',
                'state' => 'FCT',
                'postal_code' => '900001',
            ],
            unitData: [
                'unit_number' => 'B-201',
                'floor' => 2,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'status' => 'OCCUPIED',
            ],
            tenantData: [
                'full_name' => 'Ben Company B Tenant',
                'email' => 'ben@company-b.howsey.local',
                'phone' => '08020000002',
                'room_number' => 'B-201',
            ],
            rentData: [
                'annual_amount' => 1800000,
                'rent_duration' => 'annually',
                'due_day' => 1,
                'start_date' => '2026-02-01',
                'end_date' => '2027-01-31',
                'status' => 'ACTIVE',
            ],
            paymentData: [
                'payment_date' => '2026-02-05',
                'due_date' => '2026-02-01',
                'amount' => 1800000,
                'payment_method' => 'cash',
                'status' => 'COMPLETED',
                'reference' => 'COMPANY-B-PAY-001',
                'notes' => 'Local isolation verification payment',
            ],
        );
    }

    private function seedCompanyWorkspace(
        Role $adminRole,
        array $companyData,
        array $settingsData,
        array $adminData,
        array $buildingData,
        array $unitData,
        array $tenantData,
        array $rentData,
        array $paymentData
    ): void {
        DB::transaction(function () use (
            $adminRole,
            $companyData,
            $settingsData,
            $adminData,
            $buildingData,
            $unitData,
            $tenantData,
            $rentData,
            $paymentData
        ) {
            $company = Company::query()->updateOrCreate(
                ['slug' => $companyData['slug']],
                $companyData + ['onboarded_at' => now()]
            );

            DB::table('company_settings')->updateOrInsert(
                ['company_id' => $company->id],
                $settingsData + ['created_at' => now(), 'updated_at' => now()]
            );

            DB::table('notification_settings')->updateOrInsert(
                ['company_id' => $company->id],
                [
                    'email_enabled' => true,
                    'send_overdue_rent_reminders' => true,
                    'send_lease_expiry_reminders' => true,
                    'lease_expiry_days_notice' => 30,
                    'from_name' => $company->name,
                    'from_email' => $settingsData['support_email'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $admin = User::query()->updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'company_id' => $company->id,
                    'name' => $adminData['name'],
                    'phone' => $adminData['phone'],
                    'password' => Hash::make($adminData['password']),
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                ]
            );

            $building = Building::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $buildingData['name']],
                $buildingData + [
                    'company_id' => $company->id,
                    'total_units' => 1,
                    'total_floors' => 1,
                ]
            );

            $unit = Unit::query()->updateOrCreate(
                ['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => $unitData['unit_number']],
                $unitData + [
                    'company_id' => $company->id,
                    'building_id' => $building->id,
                ]
            );

            $tenant = Tenant::query()->updateOrCreate(
                ['company_id' => $company->id, 'email' => $tenantData['email']],
                $tenantData + [
                    'company_id' => $company->id,
                    'unit_id' => $unit->id,
                    'move_in_date' => $rentData['start_date'],
                    'move_out_date' => null,
                    'active' => true,
                ]
            );

            $rent = Rent::query()->updateOrCreate(
                ['company_id' => $company->id, 'tenant_id' => $tenant->id, 'unit_id' => $unit->id],
                $rentData + [
                    'company_id' => $company->id,
                    'tenant_id' => $tenant->id,
                    'unit_id' => $unit->id,
                ]
            );

            Payment::query()->updateOrCreate(
                ['company_id' => $company->id, 'reference' => $paymentData['reference']],
                $paymentData + [
                    'company_id' => $company->id,
                    'rent_id' => $rent->id,
                ]
            );
        });
    }
}
