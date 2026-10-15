<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ThreeYearOperationalDemoSeeder extends Seeder
{
    private array $columnCache = [];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $roles = $this->ensureRoles();
            $this->cleanupOperationalData($roles['tenant']);

            $actors = $this->ensureCoreUsers($roles);
            $tenantUsers = $this->createTenantUsers($roles['tenant']);
            $scenario = $this->seedBuildingsUnitsTenantsAndRents($actors, $tenantUsers);

            $this->seedPayments($scenario, $actors);
            $this->seedAnnouncements($scenario, $actors);
            $this->seedMaintenance($scenario, $actors);
            $this->seedAccounting($scenario, $actors);
            $this->seedLegacyExpenses($scenario, $actors);
            $this->seedPasswordRecovery($tenantUsers);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function ensureRoles(): array
    {
        return [
            'admin' => $this->firstOrCreateRole('admin', 'System administrator'),
            'manager' => $this->firstOrCreateRole('manager', 'Property manager'),
            'tenant' => $this->firstOrCreateRole('tenant', 'Tenant account'),
            'accountant' => $this->firstOrCreateRole('accountant', 'Accounting officer'),
        ];
    }

    private function cleanupOperationalData(int $tenantRoleId): void
    {
        foreach ([
            'announcement_dismissals',
            'password_recovery_requests',
            'sessions',
            'edit_requests',
            'audit_logs',
            'accounting_entries',
            'expenses',
            'legacy_expenses',
            'maintenance_requests',
            'payments',
            'rents',
            'tenants',
            'announcements',
            'units',
            'buildings',
        ] as $table) {
            $this->deleteTableIfExists($table);
        }

        if ($this->tableExists('users')) {
            DB::table('users')->where('role_id', $tenantRoleId)->delete();
        }
    }

    private function ensureCoreUsers(array $roles): array
    {
        $adminId = $this->firstUserIdByRole($roles['admin'])
            ?? $this->createUser([
                'name' => 'Super Admin',
                'email' => 'admin@howsey.local',
                'phone' => '08030000001',
                'password' => Hash::make('ChangeMe123!'),
                'role_id' => $roles['admin'],
                'is_active' => true,
                'coin_balance' => 5000.00,
            ]);

        $managerIds = DB::table('users')
            ->where('role_id', $roles['manager'])
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        if (count($managerIds) < 1) {
            $managerIds[] = $this->createUser([
                'name' => 'Daniel Manager',
                'email' => 'daniel.manager@howsey.online',
                'phone' => '08030000002',
                'password' => Hash::make('password'),
                'role_id' => $roles['manager'],
                'is_active' => true,
                'coin_balance' => 1200.00,
            ]);
        }

        if (count($managerIds) < 2) {
            $managerIds[] = $this->createUser([
                'name' => 'Sandra Manager',
                'email' => 'sandra.manager@howsey.online',
                'phone' => '08030000003',
                'password' => Hash::make('password'),
                'role_id' => $roles['manager'],
                'is_active' => true,
                'coin_balance' => 950.00,
            ]);
        }

        $accountantId = $this->firstUserIdByRole($roles['accountant'])
            ?? $this->createUser([
                'name' => 'Ada Accountant',
                'email' => 'ada.accountant@howsey.online',
                'phone' => '08030000004',
                'password' => Hash::make('password'),
                'role_id' => $roles['accountant'],
                'is_active' => true,
                'coin_balance' => 800.00,
            ]);

        return [
            'admin' => $adminId,
            'manager_1' => $managerIds[0],
            'manager_2' => $managerIds[1],
            'accountant' => $accountantId,
        ];
    }

    private function createTenantUsers(int $tenantRoleId): array
    {
        $tenantProfiles = [
            'ada_nwosu' => ['Ada Nwosu', 'ada.nwosu@tenant.howsey.online', '08040000010', 120.00],
            'tunde_bello' => ['Tunde Bello', 'tunde.bello@tenant.howsey.online', '08040000011', 90.00],
            'zainab_yusuf' => ['Zainab Yusuf', 'zainab.yusuf@tenant.howsey.online', '08040000012', 65.00],
            'miriam_okafor' => ['Miriam Okafor', 'miriam.okafor@tenant.howsey.online', '08040000013', 110.00],
            'chika_eze' => ['Chika Eze', 'chika.eze@tenant.howsey.online', '08040000014', 75.00],
            'samuel_danjuma' => ['Samuel Danjuma', 'samuel.danjuma@tenant.howsey.online', '08040000015', 82.00],
            'grace_ibekwe' => ['Grace Ibekwe', 'grace.ibekwe@tenant.howsey.online', '08040000016', 70.00],
            'david_lawal' => ['David Lawal', 'david.lawal@tenant.howsey.online', '08040000017', 95.00],
            'fatima_sani' => ['Fatima Sani', 'fatima.sani@tenant.howsey.online', '08040000018', 55.00],
            'kenneth_udo' => ['Kenneth Udo', 'kenneth.udo@tenant.howsey.online', '08040000019', 88.00],
            'esther_obi' => ['Esther Obi', 'esther.obi@tenant.howsey.online', '08040000020', 92.00],
            'helen_opara' => ['Helen Opara', 'helen.opara@tenant.howsey.online', '08040000021', 77.00],
        ];

        $users = [];

        foreach ($tenantProfiles as $key => [$name, $email, $phone, $coinBalance]) {
            $users[$key] = $this->createUser([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make('password'),
                'role_id' => $tenantRoleId,
                'is_active' => true,
                'coin_balance' => $coinBalance,
            ]);
        }

        return $users;
    }

    private function seedBuildingsUnitsTenantsAndRents(array $actors, array $tenantUsers): array
    {
        $scenario = [
            'buildings' => [],
            'units' => [],
            'tenants' => [],
            'rents' => [],
        ];

        $buildingDefinitions = [
            'maple' => ['Maple Court', '12 Admiralty Way', 'Lagos', 'Lagos', '101001', 5, 3, $actors['manager_1']],
            'cedar' => ['Cedar Heights', '24 GRA Extension', 'Port Harcourt', 'Rivers', '500001', 5, 4, $actors['manager_2']],
            'palm' => ['Palm Residency', '8 New Haven Road', 'Enugu', 'Enugu', '400001', 5, 2, $actors['manager_1']],
            'sunrise' => ['Sunrise Plaza', '31 Ring Road', 'Ibadan', 'Oyo', '200001', 5, 3, $actors['manager_2']],
        ];

        foreach ($buildingDefinitions as $key => [$name, $line1, $city, $state, $postalCode, $totalUnits, $totalFloors, $managerId]) {
            $scenario['buildings'][$key] = $this->insertRecord('buildings', [
                'name' => $name,
                'address_line1' => $line1,
                'address_line2' => null,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'total_units' => $totalUnits,
                'total_floors' => $totalFloors,
                'image_path' => null,
                'manager_id' => $managerId,
                'created_by' => $actors['admin'],
            ]);
        }

        $unitDefinitions = [
            'maple_101' => ['maple', '101', '1', 1, 1, 'OCCUPIED', $actors['manager_1']],
            'maple_102' => ['maple', '102', '1', 2, 2, 'OCCUPIED', $actors['manager_1']],
            'maple_103' => ['maple', '103', '2', 3, 3, 'OCCUPIED', $actors['manager_1']],
            'maple_104' => ['maple', '104', '2', 2, 2, 'AVAILABLE', $actors['manager_1']],
            'maple_105' => ['maple', '105', '3', 1, 1, 'MAINTENANCE', $actors['manager_1']],

            'cedar_201' => ['cedar', '201', '1', 1, 1, 'OCCUPIED', $actors['manager_2']],
            'cedar_202' => ['cedar', '202', '2', 2, 2, 'OCCUPIED', $actors['manager_2']],
            'cedar_203' => ['cedar', '203', '3', 3, 3, 'OCCUPIED', $actors['manager_2']],
            'cedar_204' => ['cedar', '204', '3', 2, 2, 'AVAILABLE', $actors['manager_2']],
            'cedar_205' => ['cedar', '205', '4', 1, 1, 'AVAILABLE', $actors['manager_2']],

            'palm_301' => ['palm', '301', '1', 1, 1, 'OCCUPIED', $actors['manager_1']],
            'palm_302' => ['palm', '302', '1', 2, 2, 'OCCUPIED', $actors['manager_1']],
            'palm_303' => ['palm', '303', '2', 3, 2, 'OCCUPIED', $actors['manager_1']],
            'palm_304' => ['palm', '304', '2', 2, 2, 'AVAILABLE', $actors['manager_1']],
            'palm_305' => ['palm', '305', '2', 1, 1, 'MAINTENANCE', $actors['manager_1']],

            'sunrise_401' => ['sunrise', '401', '1', 1, 1, 'OCCUPIED', $actors['manager_2']],
            'sunrise_402' => ['sunrise', '402', '2', 2, 2, 'OCCUPIED', $actors['manager_2']],
            'sunrise_403' => ['sunrise', '403', '3', 3, 3, 'OCCUPIED', $actors['manager_2']],
            'sunrise_404' => ['sunrise', '404', '3', 2, 2, 'AVAILABLE', $actors['manager_2']],
            'sunrise_405' => ['sunrise', '405', '3', 1, 1, 'AVAILABLE', $actors['manager_2']],
        ];

        foreach ($unitDefinitions as $key => [$buildingKey, $unitNumber, $floor, $bedrooms, $bathrooms, $status, $createdBy]) {
            $scenario['units'][$key] = $this->insertRecord('units', [
                'building_id' => $scenario['buildings'][$buildingKey],
                'unit_number' => $unitNumber,
                'floor' => $floor,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'status' => $status,
                'created_by' => $createdBy,
            ]);
        }

        $tenantDefinitions = [
            'ada_current' => ['ada_nwosu', 'maple_101', 'Ada Nwosu', '08040000010', 'ada.nwosu@tenant.howsey.online', '101', '2025-02-01', null, true, $actors['manager_1']],
            'tunde_current' => ['tunde_bello', 'maple_102', 'Tunde Bello', '08040000011', 'tunde.bello@tenant.howsey.online', '102', '2024-03-15', null, true, $actors['manager_1']],
            'zainab_current' => ['zainab_yusuf', 'maple_103', 'Zainab Yusuf', '08040000012', 'zainab.yusuf@tenant.howsey.online', '103', '2026-01-10', null, true, $actors['manager_1']],
            'miriam_current' => ['miriam_okafor', 'cedar_201', 'Miriam Okafor', '08040000013', 'miriam.okafor@tenant.howsey.online', '201', '2024-07-01', null, true, $actors['manager_2']],
            'chika_current' => ['chika_eze', 'cedar_202', 'Chika Eze', '08040000014', 'chika.eze@tenant.howsey.online', '202', '2025-09-01', null, true, $actors['manager_2']],
            'samuel_current' => ['samuel_danjuma', 'cedar_203', 'Samuel Danjuma', '08040000015', 'samuel.danjuma@tenant.howsey.online', '203', '2026-02-12', null, true, $actors['manager_2']],
            'grace_current' => ['grace_ibekwe', 'palm_301', 'Grace Ibekwe', '08040000016', 'grace.ibekwe@tenant.howsey.online', '301', '2024-01-05', null, true, $actors['manager_1']],
            'david_current' => ['david_lawal', 'palm_302', 'David Lawal', '08040000017', 'david.lawal@tenant.howsey.online', '302', '2025-04-20', null, true, $actors['manager_1']],
            'fatima_current' => ['fatima_sani', 'palm_303', 'Fatima Sani', '08040000018', 'fatima.sani@tenant.howsey.online', '303', '2026-06-01', null, true, $actors['manager_1']],
            'kenneth_current' => ['kenneth_udo', 'sunrise_401', 'Kenneth Udo', '08040000019', 'kenneth.udo@tenant.howsey.online', '401', '2024-02-11', null, true, $actors['manager_2']],
            'esther_current' => ['esther_obi', 'sunrise_402', 'Esther Obi', '08040000020', 'esther.obi@tenant.howsey.online', '402', '2025-01-15', null, true, $actors['manager_2']],
            'helen_current' => ['helen_opara', 'sunrise_403', 'Helen Opara', '08040000021', 'helen.opara@tenant.howsey.online', '403', '2026-03-03', null, true, $actors['manager_2']],
            'john_old' => [null, 'maple_101', 'John Peters', '08050000013', 'john.peters@old.howsey.online', '101', '2024-01-01', '2025-01-15', false, $actors['manager_1']],
            'ngozi_old' => [null, 'cedar_201', 'Ngozi Afolabi', '08050000014', 'ngozi.afolabi@old.howsey.online', '201', '2024-01-10', '2024-06-25', false, $actors['manager_2']],
            'ibrahim_old' => [null, 'palm_301', 'Ibrahim Musa', '08050000015', 'ibrahim.musa@old.howsey.online', '301', '2024-01-01', '2024-12-20', false, $actors['manager_1']],
            'rose_old' => [null, 'sunrise_401', 'Rose Kalu', '08050000016', 'rose.kalu@old.howsey.online', '401', '2024-01-01', '2024-12-31', false, $actors['manager_2']],
        ];

        foreach ($tenantDefinitions as $key => [$userKey, $unitKey, $fullName, $phone, $email, $roomNumber, $moveInDate, $moveOutDate, $active, $createdBy]) {
            $scenario['tenants'][$key] = $this->insertRecord('tenants', [
                'user_id' => $userKey ? $tenantUsers[$userKey] : null,
                'unit_id' => $scenario['units'][$unitKey],
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'profile_image' => null,
                'room_number' => $roomNumber,
                'move_in_date' => $moveInDate,
                'move_out_date' => $moveOutDate,
                'active' => $active,
                'created_by' => $createdBy,
            ]);
        }

        $rentDefinitions = [
            'maple_101_old' => ['john_old', 'maple_101', 1050000.00, 1, '2024-01-01', '2025-01-15', 'EXPIRED', $actors['manager_1']],
            'maple_101_current' => ['ada_current', 'maple_101', 1250000.00, 1, '2025-02-01', null, 'ACTIVE', $actors['manager_1']],
            'maple_102_old' => ['tunde_current', 'maple_102', 1350000.00, 15, '2024-03-15', '2025-03-14', 'EXPIRED', $actors['manager_1']],
            'maple_102_current' => ['tunde_current', 'maple_102', 1550000.00, 15, '2025-03-15', null, 'ACTIVE', $actors['manager_1']],
            'maple_103_current' => ['zainab_current', 'maple_103', 1900000.00, 10, '2026-01-10', null, 'ACTIVE', $actors['manager_1']],

            'cedar_201_old' => ['ngozi_old', 'cedar_201', 1100000.00, 10, '2024-01-10', '2024-06-25', 'EXPIRED', $actors['manager_2']],
            'cedar_201_current' => ['miriam_current', 'cedar_201', 1400000.00, 1, '2024-07-01', null, 'ACTIVE', $actors['manager_2']],
            'cedar_202_current' => ['chika_current', 'cedar_202', 1300000.00, 1, '2025-09-01', '2026-08-31', 'ACTIVE', $actors['manager_2']],
            'cedar_203_current' => ['samuel_current', 'cedar_203', 1850000.00, 12, '2026-02-12', null, 'ACTIVE', $actors['manager_2']],

            'palm_301_old' => ['ibrahim_old', 'palm_301', 950000.00, 5, '2024-01-01', '2024-12-20', 'EXPIRED', $actors['manager_1']],
            'palm_301_current' => ['grace_current', 'palm_301', 1200000.00, 5, '2024-01-05', null, 'ACTIVE', $actors['manager_1']],
            'palm_302_current' => ['david_current', 'palm_302', 1250000.00, 20, '2025-04-20', '2026-04-19', 'ACTIVE', $actors['manager_1']],
            'palm_303_current' => ['fatima_current', 'palm_303', 1750000.00, 1, '2026-06-01', null, 'ACTIVE', $actors['manager_1']],

            'sunrise_401_old' => ['rose_old', 'sunrise_401', 1000000.00, 11, '2024-01-01', '2024-12-31', 'EXPIRED', $actors['manager_2']],
            'sunrise_401_current' => ['kenneth_current', 'sunrise_401', 1350000.00, 11, '2024-02-11', null, 'ACTIVE', $actors['manager_2']],
            'sunrise_402_old' => ['esther_current', 'sunrise_402', 1400000.00, 15, '2025-01-15', '2026-01-14', 'EXPIRED', $actors['manager_2']],
            'sunrise_402_current' => ['esther_current', 'sunrise_402', 1650000.00, 15, '2026-01-15', null, 'ACTIVE', $actors['manager_2']],
            'sunrise_403_current' => ['helen_current', 'sunrise_403', 2050000.00, 3, '2026-03-03', null, 'ACTIVE', $actors['manager_2']],
        ];

        foreach ($rentDefinitions as $key => [$tenantKey, $unitKey, $annualAmount, $dueDay, $startDate, $endDate, $status, $createdBy]) {
            $scenario['rents'][$key] = $this->insertRecord('rents', [
                'tenant_id' => $scenario['tenants'][$tenantKey],
                'unit_id' => $scenario['units'][$unitKey],
                'annual_amount' => $annualAmount,
                'due_day' => $dueDay,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'signed_agreement_path' => null,
                'created_by' => $createdBy,
            ]);
        }

        return $scenario;
    }

    private function seedPayments(array $scenario, array $actors): void
    {
        $payments = [
            ['maple_101_old', '2024-01-05', '2024-01-01', 1050000.00, 'BANK_TRANSFER', 'COMPLETED', 'R1-2024', 'Old agreement paid', $actors['manager_1']],
            ['maple_101_current', '2025-02-05', '2025-02-01', 1250000.00, 'BANK_TRANSFER', 'COMPLETED', 'R2-2025', 'Paid in full', $actors['manager_1']],
            ['maple_101_current', '2026-02-10', '2026-02-01', 900000.00, 'CARD', 'COMPLETED', 'R2-2026', 'Partial payment leaves outstanding', $actors['manager_1']],
            ['maple_102_old', '2024-03-20', '2024-03-15', 1350000.00, 'BANK_TRANSFER', 'COMPLETED', 'R3-2024', 'Old rent paid', $actors['manager_1']],
            ['maple_102_current', '2025-03-20', '2025-03-15', 1550000.00, 'BANK_TRANSFER', 'COMPLETED', 'R4-2025', 'Renewed agreement payment', $actors['manager_1']],
            ['maple_102_current', '2026-03-25', '2026-03-15', 1200000.00, 'CASH', 'COMPLETED', 'R4-2026', 'Part payment', $actors['manager_1']],
            ['maple_103_current', '2026-01-15', '2026-01-10', 500000.00, 'BANK_TRANSFER', 'COMPLETED', 'R5-2026', 'New move-in partial payment', $actors['manager_1']],

            ['cedar_201_old', '2024-01-15', '2024-01-10', 1100000.00, 'BANK_TRANSFER', 'COMPLETED', 'R6-2024', 'Former tenant paid before exit', $actors['manager_2']],
            ['cedar_201_current', '2024-07-05', '2024-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R7-2024', 'Year 2024 paid', $actors['manager_2']],
            ['cedar_201_current', '2025-07-07', '2025-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R7-2025', 'Year 2025 paid', $actors['manager_2']],
            ['cedar_201_current', '2026-07-08', '2026-07-01', 700000.00, 'CARD', 'COMPLETED', 'R7-2026', 'Partial 2026 payment', $actors['manager_2']],
            ['cedar_202_current', '2025-09-05', '2025-09-01', 1300000.00, 'BANK_TRANSFER', 'COMPLETED', 'R8-2025', 'Paid in first cycle', $actors['manager_2']],
            ['cedar_202_current', '2026-02-01', '2026-09-01', 250000.00, 'BANK_TRANSFER', 'PENDING', 'R8-2026-P', 'Advance payment pending confirmation', $actors['manager_2']],
            ['cedar_203_current', '2026-02-20', '2026-02-12', 1000000.00, 'CASH', 'COMPLETED', 'R9-2026', 'Partial payment', $actors['manager_2']],

            ['palm_301_old', '2024-01-06', '2024-01-05', 950000.00, 'BANK_TRANSFER', 'COMPLETED', 'R10-2024', 'Old tenant paid', $actors['manager_1']],
            ['palm_301_current', '2024-01-10', '2024-01-05', 1200000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2024', 'Paid', $actors['manager_1']],
            ['palm_301_current', '2025-01-08', '2025-01-05', 1200000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2025', 'Paid', $actors['manager_1']],
            ['palm_301_current', '2026-01-09', '2026-01-05', 800000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2026', 'Partial payment', $actors['manager_1']],
            ['palm_302_current', '2025-04-25', '2025-04-20', 1250000.00, 'BANK_TRANSFER', 'COMPLETED', 'R12-2025', 'Paid', $actors['manager_1']],
            ['palm_302_current', '2026-04-28', '2026-04-20', 700000.00, 'CARD', 'COMPLETED', 'R12-2026', 'Part payment', $actors['manager_1']],
            ['palm_303_current', '2026-06-05', '2026-06-01', 500000.00, 'CASH', 'COMPLETED', 'R13-2026', 'Partial first payment', $actors['manager_1']],

            ['sunrise_401_old', '2024-01-05', '2024-01-01', 1000000.00, 'BANK_TRANSFER', 'COMPLETED', 'R14-2024', 'Former tenant paid', $actors['manager_2']],
            ['sunrise_401_current', '2024-02-15', '2024-02-11', 1350000.00, 'BANK_TRANSFER', 'COMPLETED', 'R15-2024', 'Paid', $actors['manager_2']],
            ['sunrise_401_current', '2025-02-15', '2025-02-11', 1000000.00, 'CARD', 'COMPLETED', 'R15-2025', 'Partial payment', $actors['manager_2']],
            ['sunrise_401_current', '2026-03-01', '2026-02-11', 300000.00, 'BANK_TRANSFER', 'COMPLETED', 'R15-2026', 'Small top-up', $actors['manager_2']],
            ['sunrise_402_old', '2025-01-20', '2025-01-15', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R16-2025', 'Old agreement settled', $actors['manager_2']],
            ['sunrise_402_current', '2026-01-20', '2026-01-15', 900000.00, 'BANK_TRANSFER', 'COMPLETED', 'R17-2026', 'Increased rent partially paid', $actors['manager_2']],
            ['sunrise_403_current', '2026-03-08', '2026-03-03', 2050000.00, 'BANK_TRANSFER', 'COMPLETED', 'R18-2026', 'Paid in full', $actors['manager_2']],
        ];

        foreach ($payments as [$rentKey, $paymentDate, $dueDate, $amount, $paymentMethod, $status, $reference, $notes, $createdBy]) {
            $this->insertRecord('payments', [
                'rent_id' => $scenario['rents'][$rentKey],
                'payment_date' => $paymentDate,
                'due_date' => $dueDate,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'method' => $paymentMethod,
                'status' => $status,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        }
    }

    private function seedAnnouncements(array $scenario, array $actors): void
    {
        if (! $this->tableExists('announcements')) {
            return;
        }

        $announcements = [
            [$scenario['buildings']['maple'], $actors['manager_1'], 'Water Tank Inspection', 'Water supply will be briefly interrupted for inspection.'],
            [$scenario['buildings']['cedar'], $actors['manager_2'], 'Generator Servicing', 'Generator servicing is scheduled this week.'],
            [$scenario['buildings']['palm'], $actors['manager_1'], 'Security Notice', 'Please update visitor access details with security.'],
            [$scenario['buildings']['sunrise'], $actors['manager_2'], 'Waste Collection', 'Waste collection remains every Monday and Thursday.'],
        ];

        foreach ($announcements as [$buildingId, $managerId, $title, $content]) {
            $this->insertRecord('announcements', [
                'building_id' => $buildingId,
                'manager_id' => $managerId,
                'title' => $title,
                'content' => $content,
            ]);
        }
    }

    private function seedMaintenance(array $scenario, array $actors): void
    {
        if (! $this->tableExists('maintenance_requests')) {
            return;
        }

        $maintenance = [
            ['maple_101', 'ada_current', 'Low Water Pressure', 'Water pressure drops every evening.', 'PLUMBING', 'HIGH', 'IN_PROGRESS', null, 'ada_nwosu'],
            ['maple_103', 'zainab_current', 'AC Not Cooling', 'Bedroom AC is not cooling well.', 'HVAC', 'MEDIUM', 'PENDING', null, 'zainab_yusuf'],
            ['cedar_201', 'miriam_current', 'Kitchen Sink Leak', 'Leak under the sink cabinet.', 'PLUMBING', 'MEDIUM', 'PENDING', null, 'miriam_okafor'],
            ['cedar_203', 'samuel_current', 'Power Tripping', 'Sockets trip under load.', 'ELECTRICAL', 'HIGH', 'IN_PROGRESS', null, 'samuel_danjuma'],
            ['palm_301', 'grace_current', 'Broken Door Lock', 'Main door lock jams intermittently.', 'STRUCTURAL', 'HIGH', 'PENDING', null, 'grace_ibekwe'],
            ['palm_303', 'fatima_current', 'Water Heater Issue', 'Heater stopped working.', 'APPLIANCE', 'MEDIUM', 'RESOLVED', '2026-06-10 12:30:00', 'fatima_sani'],
            ['sunrise_401', 'kenneth_current', 'Window Handle Loose', 'Window handle needs replacement.', 'STRUCTURAL', 'LOW', 'RESOLVED', '2026-05-15 14:00:00', 'kenneth_udo'],
            ['sunrise_402', 'esther_current', 'Light Flickering', 'Corridor light flickers at night.', 'ELECTRICAL', 'MEDIUM', 'PENDING', null, 'esther_obi'],
        ];

        foreach ($maintenance as [$unitKey, $tenantKey, $title, $description, $type, $priority, $status, $resolvedAt, $creatorKey]) {
            $this->insertRecord('maintenance_requests', [
                'unit_id' => $scenario['units'][$unitKey],
                'tenant_id' => $scenario['tenants'][$tenantKey],
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'priority' => $priority,
                'status' => $status,
                'resolved_at' => $resolvedAt,
                'created_by' => $creatorKey ? DB::table('users')->where('email', str_replace('_', '.', $creatorKey).'@tenant.howsey.online')->value('id') : $actors['admin'],
            ]);
        }
    }

    private function seedAccounting(array $scenario, array $actors): void
    {
        if (! $this->tableExists('accounting_entries')) {
            return;
        }

        $entries = [
            [$scenario['buildings']['maple'], 'expense', 'Cleaning', 'Common area cleaning supplies', 45000.00, '2024-06-15', '{"property_id":"MAPLE-COURT"}'],
            [$scenario['buildings']['maple'], 'income', 'Service Charge', 'Collected shared facility contribution', 150000.00, '2025-02-10', '{"tenant_name":"Multiple Tenants","property_id":"MAPLE-COURT"}'],
            [$scenario['buildings']['maple'], 'expense', 'Plumbing Repair', 'Leak repair on second floor', 180000.00, '2026-04-14', '{"property_id":"104"}'],

            [$scenario['buildings']['cedar'], 'expense', 'Security Supplies', 'Gatehouse consumables', 65000.00, '2024-09-10', '{"property_id":"CEDAR-HEIGHTS"}'],
            [$scenario['buildings']['cedar'], 'income', 'Parking Fees', 'Reserved slot income', 90000.00, '2025-01-18', '{"tenant_name":"Multiple Tenants","property_id":"CEDAR-HEIGHTS"}'],
            [$scenario['buildings']['cedar'], 'expense', 'Elevator Maintenance', 'Routine service and parts', 220000.00, '2026-03-05', '{"property_id":"CEDAR-HEIGHTS"}'],

            [$scenario['buildings']['palm'], 'expense', 'Minor Repairs', 'Fittings replacement', 38000.00, '2024-07-09', '{"property_id":"PALM-RESIDENCY"}'],
            [$scenario['buildings']['palm'], 'income', 'Waste Disposal Levy', 'Estate contribution', 70000.00, '2025-02-02', '{"tenant_name":"Multiple Tenants","property_id":"PALM-RESIDENCY"}'],
            [$scenario['buildings']['palm'], 'expense', 'Pump Servicing', 'Borehole pump servicing', 135000.00, '2026-02-26', '{"property_id":"PALM-RESIDENCY"}'],

            [$scenario['buildings']['sunrise'], 'expense', 'Security', 'External security materials', 72000.00, '2024-11-17', '{"property_id":"SUNRISE-PLAZA"}'],
            [$scenario['buildings']['sunrise'], 'income', 'Signage Fee', 'Shopfront signage fee', 125000.00, '2025-03-01', '{"tenant_name":"Commercial Occupants","property_id":"SUNRISE-PLAZA"}'],
            [$scenario['buildings']['sunrise'], 'expense', 'Roof Waterproofing', 'Patch work above top floor', 260000.00, '2026-04-11', '{"property_id":"SUNRISE-PLAZA"}'],
        ];

        foreach ($entries as [$buildingId, $type, $category, $description, $amount, $entryDate, $extraDetails]) {
            $this->insertRecord('accounting_entries', [
                'building_id' => $buildingId,
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'entry_date' => $entryDate,
                'is_locked' => true,
                'finalized_at' => $entryDate.' 12:00:00',
                'created_by' => $actors['accountant'],
                'updated_by' => null,
                'status' => 'finalized',
                'extra_details' => $extraDetails,
            ]);
        }
    }

    private function seedLegacyExpenses(array $scenario, array $actors): void
    {
        $expenseTable = $this->tableExists('expenses')
            ? 'expenses'
            : ($this->tableExists('legacy_expenses') ? 'legacy_expenses' : null);

        if (! $expenseTable) {
            return;
        }

        $expenses = [
            [$scenario['buildings']['maple'], 40000.00, 'Cleaning', 'Cleaning support purchase', '2024-05-20'],
            [$scenario['buildings']['cedar'], 52000.00, 'Utilities', 'Water treatment supplies', '2025-02-11'],
            [$scenario['buildings']['palm'], 47000.00, 'Maintenance', 'Minor maintenance materials', '2025-08-09'],
            [$scenario['buildings']['sunrise'], 68000.00, 'Security', 'Security replacement items', '2026-04-17'],
        ];

        foreach ($expenses as [$buildingId, $amount, $category, $description, $expenseDate]) {
            $this->insertRecord($expenseTable, [
                'building_id' => $buildingId,
                'amount' => $amount,
                'category' => $category,
                'description' => $description,
                'expense_date' => $expenseDate,
                'created_by' => $actors['accountant'],
            ]);
        }
    }

    private function seedPasswordRecovery(array $tenantUsers): void
    {
        if (! $this->tableExists('password_recovery_requests')) {
            return;
        }

        $this->insertRecord('password_recovery_requests', [
            'user_id' => $tenantUsers['fatima_sani'] ?? null,
            'email' => 'fatima.sani@tenant.howsey.online',
            'phone' => '08040000018',
            'status' => 'pending',
            'reviewer_id' => null,
            'reviewer_notes' => null,
            'reviewed_at' => null,
        ]);
    }

    private function firstOrCreateRole(string $name, string $description): int
    {
        $existingId = DB::table('roles')->where('name', $name)->value('id');
        if ($existingId) {
            if ($this->tableHasColumn('roles', 'description')) {
                DB::table('roles')->where('id', $existingId)->update(['description' => $description]);
            }

            return (int) $existingId;
        }

        return (int) $this->insertRecord('roles', [
            'name' => $name,
            'description' => $description,
        ]);
    }

    private function firstUserIdByRole(int $roleId): ?int
    {
        if (! $this->tableExists('users')) {
            return null;
        }

        $id = DB::table('users')->where('role_id', $roleId)->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    private function createUser(array $attributes): int
    {
        return (int) $this->insertRecord('users', $attributes);
    }

    private function insertRecord(string $table, array $attributes): int
    {
        $payload = $this->filterColumns($table, array_merge($attributes, $this->timestampPayload($table)));

        return DB::table($table)->insertGetId($payload);
    }

    private function deleteTableIfExists(string $table): void
    {
        if ($this->tableExists($table)) {
            DB::table($table)->delete();
        }
    }

    private function filterColumns(string $table, array $attributes): array
    {
        $columns = $this->getColumns($table);

        return array_filter(
            $attributes,
            function ($value, $key) use ($columns) {
                return in_array($key, $columns, true);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function timestampPayload(string $table): array
    {
        $payload = [];

        if ($this->tableHasColumn($table, 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->tableHasColumn($table, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, $column);
    }

    private function getColumns(string $table): array
    {
        if (! array_key_exists($table, $this->columnCache)) {
            $this->columnCache[$table] = $this->tableExists($table)
                ? Schema::getColumnListing($table)
                : [];
        }

        return $this->columnCache[$table];
    }
}
