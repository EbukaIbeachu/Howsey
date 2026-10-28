<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Representative demo data for reviewing the redesigned frontend (local only).
 *
 * Creates two company-scoped workspaces so the dashboard cards, data tables,
 * charts, status badges and empty states can all be reviewed with realistic
 * rows instead of empty tables.
 *
 * Manual invocation only — it is deliberately NOT wired into DatabaseSeeder:
 *
 *     php artisan db:seed --class=FrontendDemoSeeder
 *
 * This seeder writes rows only. No schema, migration, model, controller,
 * route or business rule is touched.
 *
 * DESTRUCTIVE: the cleanup pass removes every row in buildings, units, tenants,
 * rents, payments, maintenance_requests, announcements, accounting_entries and
 * related tables for the companies it manages — and one of those companies is
 * the pre-existing `default-company`. It therefore refuses to run outside the
 * `local` environment. Never relax this guard on a deployed host.
 */
class FrontendDemoSeeder extends Seeder
{
    public const SUPER_ADMIN_EMAIL = 'admin@howsey.local';
    public const SUPER_ADMIN_PASSWORD = 'ChangeMe123!';
    public const ADMIN_PASSWORD = 'ChangeMe123!';
    public const STAFF_PASSWORD = 'password';

    private array $columnCache = [];

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException(
                'FrontendDemoSeeder is local-only: its cleanup pass would delete real data '
                . '(current environment: ' . app()->environment() . ').'
            );
        }

        $roles = $this->ensureRoles();
        $superAdminId = $this->ensureSuperAdmin($roles['super_admin']);
        $companyOneId = $this->resolveCompanyOneId();
        $companyTwoId = $this->ensureCompanyTwoId();

        $this->cleanPreviousDemoData([$companyOneId, $companyTwoId]);

        $one = $this->seedCompanyOne($companyOneId, $roles);
        $two = $this->seedCompanyTwo($companyTwoId, $roles);

        $this->seedCompanyApplications($superAdminId);
        $this->seedMessages($one, $two);
        $this->seedSupportQueues($one);

        $this->report($companyOneId, $companyTwoId);
    }

    // ---------------------------------------------------------------------
    // Roles / accounts / companies
    // ---------------------------------------------------------------------

    private function ensureRoles(): array
    {
        return [
            'super_admin' => $this->firstOrCreateRole('super_admin', 'Super Admin'),
            'admin' => $this->firstOrCreateRole('admin', 'Admin'),
            'manager' => $this->firstOrCreateRole('manager', 'Manager'),
            'accountant' => $this->firstOrCreateRole('accountant', 'Accountant'),
            'tenant' => $this->firstOrCreateRole('tenant', 'Tenant'),
        ];
    }

    private function ensureSuperAdmin(int $roleId): int
    {
        $existingId = DB::table('users')->where('email', self::SUPER_ADMIN_EMAIL)->value('id');

        if ($existingId) {
            DB::table('users')->where('id', $existingId)->update($this->filterColumns('users', [
                'name' => 'Super Admin',
                'role_id' => $roleId,
                'company_id' => null,
                'is_active' => true,
                'password' => Hash::make(self::SUPER_ADMIN_PASSWORD),
                'updated_at' => now(),
            ]));

            return (int) $existingId;
        }

        return $this->createUser(null, 'Super Admin', self::SUPER_ADMIN_EMAIL, '08030000000', self::SUPER_ADMIN_PASSWORD, $roleId, 5000.00);
    }

    /**
     * Company 1 is the existing platform workspace created at install time.
     * It is reused as-is so the super-admin switcher has a default workspace.
     */
    private function resolveCompanyOneId(): int
    {
        $id = DB::table('companies')->where('slug', 'default-company')->value('id')
            ?? DB::table('companies')->orderBy('id')->value('id');

        if ($id) {
            return (int) $id;
        }

        return (int) $this->insertRecord('companies', [
            'name' => 'HOWSEY',
            'slug' => 'default-company',
            'status' => 'active',
            'is_demo' => false,
            'onboarded_at' => now(),
            'platform_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addYear(),
            'subscription_fee' => 50000,
        ]);
    }

    /**
     * A second workspace, used to exercise the super-admin workspace switcher
     * and per-company branding (its primary colour differs from company 1).
     */
    private function ensureCompanyTwoId(): int
    {
        $existingId = DB::table('companies')->where('slug', 'palm-grove-estates')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) $this->insertRecord('companies', [
            'name' => 'Palm Grove Estates',
            'slug' => 'palm-grove-estates',
            'status' => 'active',
            'is_demo' => false,
            'onboarded_at' => now(),
            'platform_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addYear(),
            'subscription_fee' => 50000,
        ]);
    }

    // ---------------------------------------------------------------------
    // Company 1 — HOWSEY
    // ---------------------------------------------------------------------

    private function seedCompanyOne(int $companyId, array $roles): array
    {
        $this->ensureCompanySettings($companyId, [
            'primary_color' => '#7c3aed',
            'email_header' => 'HOWSEY',
            'receipt_header' => 'Official Payment Receipt',
            'address' => '1 Howsey Plaza, Victoria Island, Lagos',
            'support_email' => 'support@howsey.site',
            'support_phone' => '08030000000',
        ], 'HOWSEY');

        $admin = $this->createUser($companyId, 'Estate Admin', 'estate.admin@howsey.local', '08030000001', self::ADMIN_PASSWORD, $roles['admin'], 5000.00);
        $managerOne = $this->createUser($companyId, 'Daniel Manager', 'daniel.manager@howsey.local', '08030000002', self::STAFF_PASSWORD, $roles['manager'], 1200.00);
        $managerTwo = $this->createUser($companyId, 'Sandra Manager', 'sandra.manager@howsey.local', '08030000003', self::STAFF_PASSWORD, $roles['manager'], 950.00);
        $accountant = $this->createUser($companyId, 'Ada Accountant', 'ada.accountant@howsey.local', '08030000004', self::STAFF_PASSWORD, $roles['accountant'], 800.00);

        $tenantUserIds = [];
        foreach ($this->companyOneTenantProfiles() as $key => [$name, $email, $phone]) {
            $tenantUserIds[$key] = $this->createUser($companyId, $name, $email, $phone, self::STAFF_PASSWORD, $roles['tenant'], 100.00);
        }

        $buildings = [];
        foreach ([
            'maple' => ['Maple Court', '12 Admiralty Way', 'Lagos', 'Lagos', '101001', 5, 3, $managerOne],
            'cedar' => ['Cedar Heights', '24 GRA Extension', 'Port Harcourt', 'Rivers', '500001', 5, 4, $managerTwo],
            'palm' => ['Palm Residency', '8 New Haven Road', 'Enugu', 'Enugu', '400001', 5, 2, $managerOne],
            'sunrise' => ['Sunrise Plaza', '31 Ring Road', 'Ibadan', 'Oyo', '200001', 5, 3, $managerTwo],
        ] as $key => [$name, $line1, $city, $state, $postal, $units, $floors, $managerId]) {
            $buildings[$key] = $this->insertRecord('buildings', [
                'company_id' => $companyId,
                'name' => $name,
                'address_line1' => $line1,
                'address_line2' => null,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postal,
                'image_path' => null,
                'total_units' => $units,
                'total_floors' => $floors,
                'manager_id' => $managerId,
                'created_by' => $admin,
            ]);
        }

        $units = [];
        foreach ([
            'maple_101' => ['maple', '101', '1', 1, 1, 'OCCUPIED', $managerOne],
            'maple_102' => ['maple', '102', '1', 2, 2, 'OCCUPIED', $managerOne],
            'maple_103' => ['maple', '103', '2', 3, 3, 'OCCUPIED', $managerOne],
            'maple_104' => ['maple', '104', '2', 2, 2, 'AVAILABLE', $managerOne],
            'maple_105' => ['maple', '105', '3', 1, 1, 'MAINTENANCE', $managerOne],

            'cedar_201' => ['cedar', '201', '1', 1, 1, 'OCCUPIED', $managerTwo],
            'cedar_202' => ['cedar', '202', '2', 2, 2, 'OCCUPIED', $managerTwo],
            'cedar_203' => ['cedar', '203', '3', 3, 3, 'OCCUPIED', $managerTwo],
            'cedar_204' => ['cedar', '204', '3', 2, 2, 'AVAILABLE', $managerTwo],
            'cedar_205' => ['cedar', '205', '4', 1, 1, 'AVAILABLE', $managerTwo],

            'palm_301' => ['palm', '301', '1', 1, 1, 'OCCUPIED', $managerOne],
            'palm_302' => ['palm', '302', '1', 2, 2, 'OCCUPIED', $managerOne],
            'palm_303' => ['palm', '303', '2', 3, 2, 'OCCUPIED', $managerOne],
            'palm_304' => ['palm', '304', '2', 2, 2, 'AVAILABLE', $managerOne],
            'palm_305' => ['palm', '305', '2', 1, 1, 'MAINTENANCE', $managerOne],

            'sunrise_401' => ['sunrise', '401', '1', 1, 1, 'OCCUPIED', $managerTwo],
            'sunrise_402' => ['sunrise', '402', '2', 2, 2, 'OCCUPIED', $managerTwo],
            'sunrise_403' => ['sunrise', '403', '3', 3, 3, 'OCCUPIED', $managerTwo],
            'sunrise_404' => ['sunrise', '404', '3', 2, 2, 'AVAILABLE', $managerTwo],
            'sunrise_405' => ['sunrise', '405', '3', 1, 1, 'AVAILABLE', $managerTwo],
        ] as $key => [$buildingKey, $number, $floor, $bedrooms, $bathrooms, $status, $createdBy]) {
            $units[$key] = $this->insertRecord('units', [
                'company_id' => $companyId,
                'building_id' => $buildings[$buildingKey],
                'unit_number' => $number,
                'floor' => $floor,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'status' => $status,
                'created_by' => $createdBy,
            ]);
        }

        $tenants = [];
        foreach ([
            'ada' => ['ada', 'maple_101', 'Ada Nwosu', '08040000010', 'ada.nwosu@tenant.howsey.local', '101', '2025-02-01', null, true, $managerOne],
            'tunde' => ['tunde', 'maple_102', 'Tunde Bello', '08040000011', 'tunde.bello@tenant.howsey.local', '102', '2024-03-15', null, true, $managerOne],
            'zainab' => ['zainab', 'maple_103', 'Zainab Yusuf', '08040000012', 'zainab.yusuf@tenant.howsey.local', '103', '2026-01-10', null, true, $managerOne],
            'miriam' => ['miriam', 'cedar_201', 'Miriam Okafor', '08040000013', 'miriam.okafor@tenant.howsey.local', '201', '2024-07-01', null, true, $managerTwo],
            'chika' => ['chika', 'cedar_202', 'Chika Eze', '08040000014', 'chika.eze@tenant.howsey.local', '202', '2025-09-01', '2026-08-31', true, $managerTwo],
            'samuel' => ['samuel', 'cedar_203', 'Samuel Danjuma', '08040000015', 'samuel.danjuma@tenant.howsey.local', '203', '2026-02-12', null, true, $managerTwo],
            'grace' => ['grace', 'palm_301', 'Grace Ibekwe', '08040000016', 'grace.ibekwe@tenant.howsey.local', '301', '2024-01-05', null, true, $managerOne],
            'david' => ['david', 'palm_302', 'David Lawal', '08040000017', 'david.lawal@tenant.howsey.local', '302', '2025-04-20', '2026-04-19', true, $managerOne],
            'fatima' => ['fatima', 'palm_303', 'Fatima Sani', '08040000018', 'fatima.sani@tenant.howsey.local', '303', '2026-06-01', null, true, $managerOne],
            'kenneth' => ['kenneth', 'sunrise_401', 'Kenneth Udo', '08040000019', 'kenneth.udo@tenant.howsey.local', '401', '2024-02-11', null, true, $managerTwo],
            'esther' => ['esther', 'sunrise_402', 'Esther Obi', '08040000020', 'esther.obi@tenant.howsey.local', '402', '2025-01-15', null, true, $managerTwo],
            'helen' => ['helen', 'sunrise_403', 'Helen Opara', '08040000021', 'helen.opara@tenant.howsey.local', '403', '2026-03-03', null, true, $managerTwo],
            'john' => [null, 'maple_101', 'John Peters', '08050000013', 'john.peters@old.howsey.local', '101', '2024-01-01', '2025-01-15', false, $managerOne],
            'ngozi' => [null, 'cedar_201', 'Ngozi Afolabi', '08050000014', 'ngozi.afolabi@old.howsey.local', '201', '2024-01-10', '2024-06-25', false, $managerTwo],
            'ibrahim' => [null, 'palm_301', 'Ibrahim Musa', '08050000015', 'ibrahim.musa@old.howsey.local', '301', '2024-01-01', '2024-12-20', false, $managerOne],
            'rose' => [null, 'sunrise_401', 'Rose Kalu', '08050000016', 'rose.kalu@old.howsey.local', '401', '2024-01-01', '2024-12-31', false, $managerTwo],
        ] as $key => [$userKey, $unitKey, $fullName, $phone, $email, $room, $moveIn, $moveOut, $active, $createdBy]) {
            $tenants[$key] = $this->insertRecord('tenants', [
                'company_id' => $companyId,
                'user_id' => $userKey ? $tenantUserIds[$userKey] : null,
                'unit_id' => $units[$unitKey],
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'profile_image' => null,
                'room_number' => $room,
                'move_in_date' => $moveIn,
                'move_out_date' => $moveOut,
                'active' => $active,
                'rent_reminders_exempt' => false,
                'created_by' => $createdBy,
            ]);
        }

        $rents = [];
        foreach ([
            'maple_101_old' => ['john', 'maple_101', 1050000.00, 1, '2024-01-01', '2025-01-15', 'EXPIRED', $managerOne],
            'maple_101_current' => ['ada', 'maple_101', 1250000.00, 1, '2025-02-01', null, 'ACTIVE', $managerOne],
            'maple_102_old' => ['tunde', 'maple_102', 1350000.00, 15, '2024-03-15', '2025-03-14', 'EXPIRED', $managerOne],
            'maple_102_current' => ['tunde', 'maple_102', 1550000.00, 15, '2025-03-15', null, 'ACTIVE', $managerOne],
            'maple_103_current' => ['zainab', 'maple_103', 1900000.00, 10, '2026-01-10', null, 'ACTIVE', $managerOne],

            'cedar_201_old' => ['ngozi', 'cedar_201', 1100000.00, 10, '2024-01-10', '2024-06-25', 'EXPIRED', $managerTwo],
            'cedar_201_current' => ['miriam', 'cedar_201', 1400000.00, 1, '2024-07-01', null, 'ACTIVE', $managerTwo],
            'cedar_202_current' => ['chika', 'cedar_202', 1300000.00, 1, '2025-09-01', '2026-08-31', 'ACTIVE', $managerTwo],
            'cedar_203_current' => ['samuel', 'cedar_203', 1850000.00, 12, '2026-02-12', null, 'ACTIVE', $managerTwo],

            'palm_301_old' => ['ibrahim', 'palm_301', 950000.00, 5, '2024-01-01', '2024-12-20', 'EXPIRED', $managerOne],
            'palm_301_current' => ['grace', 'palm_301', 1200000.00, 5, '2024-01-05', null, 'ACTIVE', $managerOne],
            'palm_302_current' => ['david', 'palm_302', 1250000.00, 20, '2025-04-20', '2026-04-19', 'ACTIVE', $managerOne],
            'palm_303_current' => ['fatima', 'palm_303', 1750000.00, 1, '2026-06-01', null, 'ACTIVE', $managerOne],

            'sunrise_401_old' => ['rose', 'sunrise_401', 1000000.00, 11, '2024-01-01', '2024-12-31', 'EXPIRED', $managerTwo],
            'sunrise_401_current' => ['kenneth', 'sunrise_401', 1350000.00, 11, '2024-02-11', null, 'ACTIVE', $managerTwo],
            'sunrise_402_old' => ['esther', 'sunrise_402', 1400000.00, 15, '2025-01-15', '2026-01-14', 'EXPIRED', $managerTwo],
            'sunrise_402_current' => ['esther', 'sunrise_402', 1650000.00, 15, '2026-01-15', null, 'ACTIVE', $managerTwo],
            'sunrise_403_current' => ['helen', 'sunrise_403', 2050000.00, 3, '2026-03-03', null, 'ACTIVE', $managerTwo],
        ] as $key => [$tenantKey, $unitKey, $amount, $dueDay, $start, $end, $status, $createdBy]) {
            $rents[$key] = $this->insertRecord('rents', [
                'company_id' => $companyId,
                'tenant_id' => $tenants[$tenantKey],
                'unit_id' => $units[$unitKey],
                'annual_amount' => $amount,
                'rent_duration' => 'annually',
                'due_day' => $dueDay,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
                'signed_agreement_path' => null,
                'created_by' => $createdBy,
            ]);
        }

        foreach ([
            ['maple_101_old', '2024-01-05', '2024-01-01', 1050000.00, 'BANK_TRANSFER', 'COMPLETED', 'R1-2024', 'Old agreement paid in full.', $managerOne],
            ['maple_101_current', '2025-02-05', '2025-02-01', 1250000.00, 'BANK_TRANSFER', 'COMPLETED', 'R2-2025', 'Paid in full.', $managerOne],
            ['maple_101_current', '2026-02-10', '2026-02-01', 900000.00, 'CARD', 'COMPLETED', 'R2-2026', 'Partial payment — balance outstanding.', $managerOne],
            ['maple_102_old', '2024-03-20', '2024-03-15', 1350000.00, 'BANK_TRANSFER', 'COMPLETED', 'R3-2024', 'Old rent settled.', $managerOne],
            ['maple_102_current', '2025-03-20', '2025-03-15', 1550000.00, 'BANK_TRANSFER', 'COMPLETED', 'R4-2025', 'Renewed agreement paid.', $managerOne],
            ['maple_102_current', '2026-03-25', '2026-03-15', 1200000.00, 'CASH', 'COMPLETED', 'R4-2026', 'Part payment.', $managerOne],
            ['maple_102_current', '2026-10-06', '2026-10-15', 350000.00, 'BANK_TRANSFER', 'COMPLETED', 'R4-2026-T', 'Arrears top-up.', $managerOne],
            ['maple_103_current', '2026-01-15', '2026-01-10', 500000.00, 'BANK_TRANSFER', 'COMPLETED', 'R5-2026', 'Move-in part payment.', $managerOne],
            ['maple_103_current', '2026-09-28', '2026-09-10', 200000.00, 'CASH', 'COMPLETED', 'R5-2026-S', 'Small arrears payment.', $managerOne],

            ['cedar_201_old', '2024-01-15', '2024-01-10', 1100000.00, 'BANK_TRANSFER', 'COMPLETED', 'R6-2024', 'Former tenant paid before exit.', $managerTwo],
            ['cedar_201_current', '2024-07-05', '2024-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R7-2024', 'Year 2024 paid.', $managerTwo],
            ['cedar_201_current', '2025-07-07', '2025-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R7-2025', 'Year 2025 paid.', $managerTwo],
            ['cedar_201_current', '2026-07-08', '2026-07-01', 700000.00, 'CARD', 'COMPLETED', 'R7-2026', 'Partial 2026 payment.', $managerTwo],
            ['cedar_202_current', '2025-09-05', '2025-09-01', 1300000.00, 'BANK_TRANSFER', 'COMPLETED', 'R8-2025', 'Paid in first cycle.', $managerTwo],
            ['cedar_202_current', '2026-02-01', '2026-09-01', 250000.00, 'BANK_TRANSFER', 'PENDING', 'R8-2026-P', 'Advance payment pending confirmation.', $managerTwo],
            ['cedar_203_current', '2026-02-20', '2026-02-12', 1000000.00, 'CASH', 'COMPLETED', 'R9-2026', 'Partial payment.', $managerTwo],
            ['cedar_203_current', '2026-10-12', '2026-10-12', 300000.00, 'BANK_TRANSFER', 'COMPLETED', 'R9-2026-O', 'October instalment.', $managerTwo],

            ['palm_301_old', '2024-01-06', '2024-01-05', 950000.00, 'BANK_TRANSFER', 'COMPLETED', 'R10-2024', 'Old tenant paid.', $managerOne],
            ['palm_301_current', '2024-01-10', '2024-01-05', 1200000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2024', 'Paid.', $managerOne],
            ['palm_301_current', '2025-01-08', '2025-01-05', 1200000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2025', 'Paid.', $managerOne],
            ['palm_301_current', '2026-01-09', '2026-01-05', 800000.00, 'BANK_TRANSFER', 'COMPLETED', 'R11-2026', 'Partial payment.', $managerOne],
            ['palm_302_current', '2025-04-25', '2025-04-20', 1250000.00, 'BANK_TRANSFER', 'COMPLETED', 'R12-2025', 'Paid.', $managerOne],
            ['palm_302_current', '2026-04-28', '2026-04-20', 700000.00, 'CARD', 'COMPLETED', 'R12-2026', 'Part payment.', $managerOne],
            ['palm_303_current', '2026-06-05', '2026-06-01', 500000.00, 'CASH', 'COMPLETED', 'R13-2026', 'Partial first payment.', $managerOne],
            ['palm_303_current', '2026-10-02', '2026-10-01', 400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R13-2026-O', 'October part payment.', $managerOne],

            ['sunrise_401_old', '2024-01-05', '2024-01-01', 1000000.00, 'BANK_TRANSFER', 'COMPLETED', 'R14-2024', 'Former tenant paid.', $managerTwo],
            ['sunrise_401_current', '2024-02-15', '2024-02-11', 1350000.00, 'BANK_TRANSFER', 'COMPLETED', 'R15-2024', 'Paid.', $managerTwo],
            ['sunrise_401_current', '2025-02-15', '2025-02-11', 1000000.00, 'CARD', 'COMPLETED', 'R15-2025', 'Partial payment.', $managerTwo],
            ['sunrise_401_current', '2026-03-01', '2026-02-11', 300000.00, 'BANK_TRANSFER', 'COMPLETED', 'R15-2026', 'Small top-up.', $managerTwo],
            ['sunrise_402_old', '2025-01-20', '2025-01-15', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'R16-2025', 'Old agreement settled.', $managerTwo],
            ['sunrise_402_current', '2026-01-20', '2026-01-15', 900000.00, 'BANK_TRANSFER', 'COMPLETED', 'R17-2026', 'Increased rent partially paid.', $managerTwo],
            ['sunrise_403_current', '2026-03-08', '2026-03-03', 2050000.00, 'BANK_TRANSFER', 'COMPLETED', 'R18-2026', 'Paid in full.', $managerTwo],
            ['sunrise_403_current', '2026-10-08', '2026-10-03', 150000.00, 'CARD', 'FAILED', 'R18-2026-F', 'Card declined — tenant to retry.', $managerTwo],
        ] as [$rentKey, $paymentDate, $dueDate, $amount, $method, $status, $reference, $notes, $createdBy]) {
            $this->insertRecord('payments', [
                'company_id' => $companyId,
                'rent_id' => $rents[$rentKey],
                'payment_date' => $paymentDate,
                'due_date' => $dueDate,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => $status,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        }

        foreach ([
            ['maple', $managerOne, 'Water Tank Inspection', 'Water supply will be briefly interrupted on Saturday for routine tank inspection.'],
            ['cedar', $managerTwo, 'Generator Servicing', 'Generator servicing is scheduled for Thursday between 09:00 and 13:00.'],
            ['palm', $managerOne, 'Security Notice', 'Please update your visitor access details with the gatehouse.'],
            ['sunrise', $managerTwo, 'Waste Collection', 'Waste collection continues every Monday and Thursday morning.'],
        ] as [$buildingKey, $managerId, $title, $content]) {
            $this->insertRecord('announcements', [
                'company_id' => $companyId,
                'building_id' => $buildings[$buildingKey],
                'manager_id' => $managerId,
                'title' => $title,
                'content' => $content,
            ]);
        }

        foreach ([
            ['maple_101', 'ada', 'Low Water Pressure', 'Water pressure drops every evening after 18:00.', 'PLUMBING', 'HIGH', 'IN_PROGRESS', null, $tenantUserIds['ada']],
            ['maple_103', 'zainab', 'Air Conditioner Not Cooling', 'Bedroom air conditioner is running but not cooling.', 'HVAC', 'MEDIUM', 'PENDING', null, $tenantUserIds['zainab']],
            ['cedar_201', 'miriam', 'Kitchen Sink Leak', 'There is a leak under the kitchen sink cabinet.', 'PLUMBING', 'MEDIUM', 'PENDING', null, $tenantUserIds['miriam']],
            ['cedar_203', 'samuel', 'Power Tripping', 'Sockets trip whenever the microwave and kettle run together.', 'ELECTRICAL', 'HIGH', 'IN_PROGRESS', null, $tenantUserIds['samuel']],
            ['palm_301', 'grace', 'Broken Door Lock', 'Main door lock jams intermittently and needs replacement.', 'STRUCTURAL', 'HIGH', 'PENDING', null, $tenantUserIds['grace']],
            ['palm_303', 'fatima', 'Water Heater Issue', 'Water heater stopped working completely.', 'APPLIANCE', 'MEDIUM', 'RESOLVED', '2026-06-10 12:30:00', $tenantUserIds['fatima']],
            ['sunrise_401', 'kenneth', 'Window Handle Loose', 'Bedroom window handle is loose and needs refitting.', 'STRUCTURAL', 'LOW', 'RESOLVED', '2026-05-15 14:00:00', $tenantUserIds['kenneth']],
            ['sunrise_402', 'esther', 'Corridor Light Flickering', 'Corridor light flickers throughout the night.', 'ELECTRICAL', 'MEDIUM', 'CANCELLED', null, $tenantUserIds['esther']],
        ] as [$unitKey, $tenantKey, $title, $description, $type, $priority, $status, $resolvedAt, $createdBy]) {
            $this->insertRecord('maintenance_requests', [
                'company_id' => $companyId,
                'unit_id' => $units[$unitKey],
                'tenant_id' => $tenants[$tenantKey],
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'priority' => $priority,
                'status' => $status,
                'resolved_at' => $resolvedAt,
                'created_by' => $createdBy,
            ]);
        }

        foreach ([
            ['maple', 'expense', 'Cleaning', 'Common area cleaning supplies', 45000.00, '2024-06-15'],
            ['maple', 'income', 'Service Charge', 'Shared facility contribution collected', 150000.00, '2025-02-10'],
            ['maple', 'expense', 'Plumbing Repair', 'Leak repair on the second floor', 180000.00, '2026-04-14'],
            ['maple', 'expense', 'Cleaning', 'Monthly cleaning contract', 60000.00, '2026-09-05'],

            ['cedar', 'expense', 'Security Supplies', 'Gatehouse consumables', 65000.00, '2024-09-10'],
            ['cedar', 'income', 'Parking Fees', 'Reserved parking slot income', 90000.00, '2025-01-18'],
            ['cedar', 'expense', 'Elevator Maintenance', 'Routine service and replacement parts', 220000.00, '2026-03-05'],

            ['palm', 'expense', 'Minor Repairs', 'Fittings replacement', 38000.00, '2024-07-09'],
            ['palm', 'income', 'Waste Disposal Levy', 'Estate contribution', 70000.00, '2025-02-02'],
            ['palm', 'expense', 'Pump Servicing', 'Borehole pump servicing', 135000.00, '2026-02-26'],

            ['sunrise', 'expense', 'Security', 'External security materials', 72000.00, '2024-11-17'],
            ['sunrise', 'income', 'Signage Fee', 'Shopfront signage fee', 125000.00, '2025-03-01'],
            ['sunrise', 'expense', 'Roof Waterproofing', 'Patch work above the top floor', 260000.00, '2026-04-11'],
        ] as [$buildingKey, $type, $category, $description, $amount, $entryDate]) {
            $this->insertRecord('accounting_entries', [
                'company_id' => $companyId,
                'building_id' => $buildings[$buildingKey],
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'entry_date' => $entryDate,
                'is_locked' => true,
                'finalized_at' => $entryDate.' 12:00:00',
                'created_by' => $accountant,
                'updated_by' => null,
                'status' => 'finalized',
                'extra_details' => json_encode(['property_id' => strtoupper(str_replace(' ', '-', $buildingKey))]),
            ]);
        }

        foreach ([
            ['Corporate Collections', 'Zenith Bank', 'HOWSEY Property Ltd', '1012345678', 'Victoria Island', true, 1],
            ['Reserve Account', 'GTBank', 'HOWSEY Property Ltd', '0123456789', 'Lekki', false, 2],
        ] as [$label, $bank, $accountName, $accountNumber, $branch, $primary, $sort]) {
            $this->insertRecord('payment_accounts', [
                'company_id' => $companyId,
                'label' => $label,
                'bank_name' => $bank,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'branch' => $branch,
                'instructions' => 'Use your unit number as the transfer narration and upload the proof of payment.',
                'is_primary' => $primary,
                'is_active' => true,
                'sort_order' => $sort,
            ]);
        }

        $this->insertRecord('payment_claims', [
            'company_id' => $companyId,
            'rent_id' => $rents['maple_103_current'],
            'tenant_id' => $tenants['zainab'],
            'submitted_by' => $tenantUserIds['zainab'],
            'amount' => 200000.00,
            'payment_date' => '2026-09-28',
            'payment_method' => 'CASH',
            'reference' => 'CLAIM-M103-001',
            'proof_path' => null,
            'note' => 'Paid at the estate office — awaiting confirmation.',
            'status' => 'PENDING',
            'confirmed_by' => null,
            'confirmed_at' => null,
            'review_note' => null,
            'payment_id' => null,
        ]);

        $this->insertRecord('payment_claims', [
            'company_id' => $companyId,
            'rent_id' => $rents['palm_303_current'],
            'tenant_id' => $tenants['fatima'],
            'submitted_by' => $tenantUserIds['fatima'],
            'amount' => 400000.00,
            'payment_date' => '2026-10-02',
            'payment_method' => 'BANK_TRANSFER',
            'reference' => 'CLAIM-P303-001',
            'proof_path' => null,
            'note' => 'Transfer made from the tenant savings account.',
            'status' => 'CONFIRMED',
            'confirmed_by' => $accountant,
            'confirmed_at' => '2026-10-03 09:15:00',
            'review_note' => 'Bank statement matched.',
            'payment_id' => null,
        ]);

        return [
            'company_id' => $companyId,
            'admin' => $admin,
            'manager_one' => $managerOne,
            'manager_two' => $managerTwo,
            'accountant' => $accountant,
            'tenants' => $tenantUserIds,
            'buildings' => $buildings,
            'units' => $units,
            'tenants_rows' => $tenants,
            'rents' => $rents,
        ];
    }

    private function companyOneTenantProfiles(): array
    {
        return [
            'ada' => ['Ada Nwosu', 'ada.nwosu@tenant.howsey.local', '08040000010'],
            'tunde' => ['Tunde Bello', 'tunde.bello@tenant.howsey.local', '08040000011'],
            'zainab' => ['Zainab Yusuf', 'zainab.yusuf@tenant.howsey.local', '08040000012'],
            'miriam' => ['Miriam Okafor', 'miriam.okafor@tenant.howsey.local', '08040000013'],
            'chika' => ['Chika Eze', 'chika.eze@tenant.howsey.local', '08040000014'],
            'samuel' => ['Samuel Danjuma', 'samuel.danjuma@tenant.howsey.local', '08040000015'],
            'grace' => ['Grace Ibekwe', 'grace.ibekwe@tenant.howsey.local', '08040000016'],
            'david' => ['David Lawal', 'david.lawal@tenant.howsey.local', '08040000017'],
            'fatima' => ['Fatima Sani', 'fatima.sani@tenant.howsey.local', '08040000018'],
            'kenneth' => ['Kenneth Udo', 'kenneth.udo@tenant.howsey.local', '08040000019'],
            'esther' => ['Esther Obi', 'esther.obi@tenant.howsey.local', '08040000020'],
            'helen' => ['Helen Opara', 'helen.opara@tenant.howsey.local', '08040000021'],
        ];
    }

    // ---------------------------------------------------------------------
    // Company 2 — Palm Grove Estates
    // ---------------------------------------------------------------------

    private function seedCompanyTwo(int $companyId, array $roles): array
    {
        $this->ensureCompanySettings($companyId, [
            'primary_color' => '#0f766e',
            'email_header' => 'Palm Grove Estates',
            'receipt_header' => 'Official Palm Grove Estates Receipt',
            'address' => '18 Palm Grove Avenue, Lekki, Lagos',
            'support_email' => 'support@palmgrove.howsey.local',
            'support_phone' => '08090000000',
        ], 'Palm Grove Estates');

        $admin = $this->createUser($companyId, 'Palm Grove Admin', 'palm.admin@howsey.local', '08090000001', self::ADMIN_PASSWORD, $roles['admin'], 2500.00);
        $manager = $this->createUser($companyId, 'Peter Manager', 'peter.manager@palmgrove.howsey.local', '08090000002', self::STAFF_PASSWORD, $roles['manager'], 900.00);
        $accountant = $this->createUser($companyId, 'Ngozi Accountant', 'ngozi.accountant@palmgrove.howsey.local', '08090000003', self::STAFF_PASSWORD, $roles['accountant'], 650.00);

        $tenantProfiles = [
            'bola' => ['Bola Adeyemi', 'bola.adeyemi@palmgrove.howsey.local', '08091000001'],
            'chinedu' => ['Chinedu Okeke', 'chinedu.okeke@palmgrove.howsey.local', '08091000002'],
            'amaka' => ['Amaka Nnaji', 'amaka.nnaji@palmgrove.howsey.local', '08091000003'],
            'yusuf' => ['Yusuf Bala', 'yusuf.bala@palmgrove.howsey.local', '08091000004'],
        ];

        $tenantUserIds = [];
        foreach ($tenantProfiles as $key => [$name, $email, $phone]) {
            $tenantUserIds[$key] = $this->createUser($companyId, $name, $email, $phone, self::STAFF_PASSWORD, $roles['tenant'], 60.00);
        }

        $buildings = [];
        foreach ([
            'towers' => ['Palm Grove Towers', '18 Palm Grove Avenue', 'Lekki', 'Lagos', '106104', 4, 4, $manager],
            'court' => ['Palm Grove Court', '7 Bourdillon Road', 'Ikoyi', 'Lagos', '101233', 4, 3, $manager],
        ] as $key => [$name, $line1, $city, $state, $postal, $units, $floors, $managerId]) {
            $buildings[$key] = $this->insertRecord('buildings', [
                'company_id' => $companyId,
                'name' => $name,
                'address_line1' => $line1,
                'address_line2' => null,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postal,
                'image_path' => null,
                'total_units' => $units,
                'total_floors' => $floors,
                'manager_id' => $managerId,
                'created_by' => $admin,
            ]);
        }

        $units = [];
        foreach ([
            't_101' => ['towers', '101', '1', 1, 1, 'OCCUPIED'],
            't_102' => ['towers', '102', '1', 2, 2, 'OCCUPIED'],
            't_201' => ['towers', '201', '2', 3, 3, 'OCCUPIED'],
            't_202' => ['towers', '202', '2', 2, 2, 'AVAILABLE'],
            'c_301' => ['court', '301', '1', 2, 2, 'OCCUPIED'],
            'c_302' => ['court', '302', '2', 3, 2, 'MAINTENANCE'],
            'c_303' => ['court', '303', '3', 1, 1, 'AVAILABLE'],
            'c_304' => ['court', '304', '3', 2, 2, 'AVAILABLE'],
        ] as $key => [$buildingKey, $number, $floor, $bedrooms, $bathrooms, $status]) {
            $units[$key] = $this->insertRecord('units', [
                'company_id' => $companyId,
                'building_id' => $buildings[$buildingKey],
                'unit_number' => $number,
                'floor' => $floor,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'status' => $status,
                'created_by' => $manager,
            ]);
        }

        $tenants = [];
        foreach ([
            'bola' => ['bola', 't_101', 'Bola Adeyemi', '08091000001', 'bola.adeyemi@palmgrove.howsey.local', '101', '2025-05-01', null, true],
            'chinedu' => ['chinedu', 't_102', 'Chinedu Okeke', '08091000002', 'chinedu.okeke@palmgrove.howsey.local', '102', '2024-11-12', null, true],
            'amaka' => ['amaka', 't_201', 'Amaka Nnaji', '08091000003', 'amaka.nnaji@palmgrove.howsey.local', '201', '2026-04-01', null, true],
            'yusuf' => ['yusuf', 'c_301', 'Yusuf Bala', '08091000004', 'yusuf.bala@palmgrove.howsey.local', '301', '2026-06-15', null, true],
            'past' => [null, 'c_301', 'Ifeoma Bassey', '08092000001', 'ifeoma.bassey@old.palmgrove.local', '301', '2024-01-05', '2026-05-31', false],
        ] as $key => [$userKey, $unitKey, $fullName, $phone, $email, $room, $moveIn, $moveOut, $active]) {
            $tenants[$key] = $this->insertRecord('tenants', [
                'company_id' => $companyId,
                'user_id' => $userKey ? $tenantUserIds[$userKey] : null,
                'unit_id' => $units[$unitKey],
                'full_name' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'profile_image' => null,
                'room_number' => $room,
                'move_in_date' => $moveIn,
                'move_out_date' => $moveOut,
                'active' => $active,
                'rent_reminders_exempt' => false,
                'created_by' => $manager,
            ]);
        }

        $rents = [];
        foreach ([
            'bola' => ['bola', 't_101', 1400000.00, 1, '2025-05-01', null, 'ACTIVE'],
            'chinedu' => ['chinedu', 't_102', 1650000.00, 12, '2024-11-12', null, 'ACTIVE'],
            'amaka' => ['amaka', 't_201', 1900000.00, 1, '2026-04-01', null, 'ACTIVE'],
            'yusuf' => ['yusuf', 'c_301', 1200000.00, 15, '2026-06-15', null, 'ACTIVE'],
            'past' => ['past', 'c_301', 1000000.00, 5, '2024-01-05', '2026-05-31', 'EXPIRED'],
        ] as $key => [$tenantKey, $unitKey, $amount, $dueDay, $start, $end, $status]) {
            $rents[$key] = $this->insertRecord('rents', [
                'company_id' => $companyId,
                'tenant_id' => $tenants[$tenantKey],
                'unit_id' => $units[$unitKey],
                'annual_amount' => $amount,
                'rent_duration' => 'annually',
                'due_day' => $dueDay,
                'start_date' => $start,
                'end_date' => $end,
                'status' => $status,
                'signed_agreement_path' => null,
                'created_by' => $manager,
            ]);
        }

        foreach ([
            ['bola', '2025-05-04', '2025-05-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2025-01', 'Paid in full.'],
            ['bola', '2026-05-06', '2026-05-01', 900000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2026-01', 'Partial renewal payment.'],
            ['chinedu', '2024-11-15', '2024-11-12', 1650000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2024-02', 'Paid in full.'],
            ['chinedu', '2025-11-14', '2025-11-12', 1650000.00, 'CARD', 'COMPLETED', 'PG-2025-02', 'Paid in full.'],
            ['amaka', '2026-04-03', '2026-04-01', 1900000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2026-03', 'Paid in full.'],
            ['yusuf', '2026-06-18', '2026-06-15', 600000.00, 'CASH', 'COMPLETED', 'PG-2026-04', 'Partial payment.'],
            ['yusuf', '2026-10-10', '2026-10-15', 300000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2026-05', 'October instalment.'],
            ['past', '2024-01-08', '2024-01-05', 1000000.00, 'BANK_TRANSFER', 'COMPLETED', 'PG-2024-05', 'Former tenant payment.'],
        ] as [$rentKey, $paymentDate, $dueDate, $amount, $method, $status, $reference, $notes]) {
            $this->insertRecord('payments', [
                'company_id' => $companyId,
                'rent_id' => $rents[$rentKey],
                'payment_date' => $paymentDate,
                'due_date' => $dueDate,
                'amount' => $amount,
                'payment_method' => $method,
                'status' => $status,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $manager,
            ]);
        }

        $this->insertRecord('announcements', [
            'company_id' => $companyId,
            'building_id' => $buildings['towers'],
            'manager_id' => $manager,
            'title' => 'Lift Servicing',
            'content' => 'The passenger lift will be out of service on Tuesday morning for scheduled servicing.',
        ]);

        foreach ([
            ['t_102', 'chinedu', 'Water Leak in Bathroom', 'Slow leak from the bathroom pipe joint.', 'PLUMBING', 'HIGH', 'IN_PROGRESS', null],
            ['c_301', 'yusuf', 'Gate Remote Not Working', 'Vehicle gate remote stopped responding.', 'OTHER', 'LOW', 'PENDING', null],
        ] as [$unitKey, $tenantKey, $title, $description, $type, $priority, $status, $resolvedAt]) {
            $this->insertRecord('maintenance_requests', [
                'company_id' => $companyId,
                'unit_id' => $units[$unitKey],
                'tenant_id' => $tenants[$tenantKey],
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'priority' => $priority,
                'status' => $status,
                'resolved_at' => $resolvedAt,
                'created_by' => $tenantUserIds[$tenantKey],
            ]);
        }

        foreach ([
            ['towers', 'expense', 'Utilities', 'Diesel for the standby generator', 320000.00, '2026-05-18'],
            ['towers', 'income', 'Service Charge', 'Facility contribution collected', 210000.00, '2026-06-02'],
            ['court', 'expense', 'Security', 'Gatehouse uniforms and consumables', 95000.00, '2026-07-11'],
        ] as [$buildingKey, $type, $category, $description, $amount, $entryDate]) {
            $this->insertRecord('accounting_entries', [
                'company_id' => $companyId,
                'building_id' => $buildings[$buildingKey],
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
                'entry_date' => $entryDate,
                'is_locked' => true,
                'finalized_at' => $entryDate.' 12:00:00',
                'created_by' => $accountant,
                'updated_by' => null,
                'status' => 'finalized',
                'extra_details' => json_encode(['property_id' => strtoupper($buildingKey)]),
            ]);
        }

        $this->insertRecord('payment_accounts', [
            'company_id' => $companyId,
            'label' => 'Palm Grove Collections',
            'bank_name' => 'Access Bank',
            'account_name' => 'Palm Grove Estates Ltd',
            'account_number' => '0881234567',
            'branch' => 'Lekki Phase 1',
            'instructions' => 'Quote your unit number in the transfer narration and upload the payment proof.',
            'is_primary' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->insertRecord('payment_claims', [
            'company_id' => $companyId,
            'rent_id' => $rents['yusuf'],
            'tenant_id' => $tenants['yusuf'],
            'submitted_by' => $tenantUserIds['yusuf'],
            'amount' => 300000.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'BANK_TRANSFER',
            'reference' => 'PG-CLAIM-001',
            'proof_path' => null,
            'note' => 'October instalment transferred from my salary account.',
            'status' => 'PENDING',
            'confirmed_by' => null,
            'confirmed_at' => null,
            'review_note' => null,
            'payment_id' => null,
        ]);

        return [
            'company_id' => $companyId,
            'admin' => $admin,
            'manager_one' => $manager,
            'manager_two' => $manager,
            'accountant' => $accountant,
        ];
    }

    // ---------------------------------------------------------------------
    // Cross-company extras
    // ---------------------------------------------------------------------

    private function seedMessages(array $one, array $two): void
    {
        if (! $this->tableExists('messages')) {
            return;
        }

        $conversations = [
            [$one['manager_one'], $one['admin'], 'The October meter readings for Maple Court have been uploaded.', '2026-10-20 09:12:00'],
            [$one['admin'], $one['manager_one'], 'Thanks Daniel. Please also chase the outstanding balance on unit 103.', '2026-10-20 09:40:00', true],
            [$one['tenants']['ada'], $one['manager_one'], 'Good afternoon, is the plumber still coming for the water pressure issue?', '2026-10-22 14:05:00'],
            [$one['manager_one'], $one['tenants']['ada'], 'Yes — the plumber is booked for Thursday morning between 09:00 and 11:00.', '2026-10-22 15:20:00', true],
            [$one['accountant'], $one['admin'], 'The September accounting entries have been finalised and locked.', '2026-10-05 11:30:00', true],
            [$two['manager_one'], $two['admin'], 'Palm Grove Court lift servicing is confirmed for Tuesday.', '2026-10-18 08:15:00'],
        ];

        foreach ($conversations as $message) {
            $this->insertRecord('messages', [
                'sender_id' => $message[0],
                'recipient_id' => $message[1],
                'body' => $message[2],
                'read_at' => $message[3] ?? null,
                'created_at' => $message[3] ?? now(),
            ]);
        }
    }

    private function seedSupportQueues(array $one): void
    {
        if ($this->tableExists('password_recovery_requests')) {
            $this->insertRecord('password_recovery_requests', [
                'user_id' => $one['tenants']['fatima'] ?? null,
                'email' => 'fatima.sani@tenant.howsey.local',
                'phone' => '08040000018',
                'status' => 'pending',
                'reviewer_id' => null,
                'reviewer_notes' => null,
                'reviewed_at' => null,
            ]);
        }

        if ($this->tableExists('manager_edit_requests')) {
            $this->insertRecord('manager_edit_requests', [
                'company_id' => $one['company_id'],
                'user_id' => $one['manager_one'],
                'requestable_type' => \App\Models\Building::class,
                'requestable_id' => $one['buildings']['maple'],
                'reason' => 'Please allow me to correct the floor count for Maple Court.',
                'status' => 'pending',
                'admin_id' => null,
                'admin_notes' => null,
                'reviewed_at' => null,
                'expires_at' => null,
                'request_kind' => 'edit',
            ]);
        }
    }

    private function seedCompanyApplications(int $superAdminId): void
    {
        if (! $this->tableExists('company_applications')) {
            return;
        }

        $applications = [
            ['Sapphire Homes', 'sapphire-homes', 'Grace Umeh', 'grace.umeh@sapphire.howsey.local', '08070000001', 'pending_payment'],
            ['Northgate Realty', 'northgate-realty', 'Ibrahim Danladi', 'ibrahim.danladi@northgate.howsey.local', '08070000002', 'approved'],
            ['Coral Bay Apartments', 'coral-bay-apartments', 'Tolu Adebayo', 'tolu.adebayo@coralbay.howsey.local', '08070000003', 'rejected'],
        ];

        foreach ($applications as [$name, $slug, $adminName, $adminEmail, $adminPhone, $status]) {
            $this->insertRecord('company_applications', [
                'name' => $name,
                'slug' => $slug,
                'primary_color' => '#1d4ed8',
                'address' => 'Plot 12, Business District, Lagos',
                'support_email' => 'support@'.$slug.'.howsey.local',
                'support_phone' => '08060000000',
                'receipt_header' => 'Official '.$name.' Receipt',
                'email_header' => $name,
                'admin_name' => $adminName,
                'admin_email' => $adminEmail,
                'admin_phone' => $adminPhone,
                'admin_password' => Hash::make(self::ADMIN_PASSWORD),
                'sample_dataset' => true,
                'payment_reference' => 'HOWSEY-DEMO-'.strtoupper($slug),
                'payment_amount' => 50000,
                'paystack_transaction_status' => $status === 'approved' ? 'success' : 'pending',
                'application_status' => $status,
                'submitted_at' => now()->subDays(5),
                'reviewed_at' => $status === 'pending_payment' ? null : now()->subDays(2),
                'approved_at' => $status === 'approved' ? now()->subDays(1) : null,
                'approved_by' => $status === 'approved' ? $superAdminId : null,
                'review_notes' => $status === 'rejected' ? 'Duplicate of an existing company workspace.' : null,
                'company_id' => null,
            ]);
        }
    }

    // ---------------------------------------------------------------------
    // Cleanup
    // ---------------------------------------------------------------------

    /**
     * Removes rows written by a previous run so the seeder is re-runnable.
     * Children are deleted before parents because SQLite enforces foreign keys.
     */
    private function cleanPreviousDemoData(array $companyIds): void
    {
        $userIds = DB::table('users')->whereIn('company_id', $companyIds)->pluck('id')->all();

        if ($userIds && $this->tableExists('messages')) {
            DB::table('messages')
                ->whereIn('sender_id', $userIds)
                ->orWhereIn('recipient_id', $userIds)
                ->delete();
        }

        if ($userIds && $this->tableHasColumn('password_recovery_requests', 'user_id')) {
            DB::table('password_recovery_requests')->whereIn('user_id', $userIds)->delete();
        }

        foreach ([
            'payment_claims',
            'manager_edit_requests',
            'accounting_entries',
            'maintenance_requests',
            'payments',
            'rents',
            'tenants',
            'announcements',
            'units',
            'buildings',
            'payment_accounts',
        ] as $table) {
            if ($this->tableHasColumn($table, 'company_id')) {
                DB::table($table)->whereIn('company_id', $companyIds)->delete();
            }
        }

        if ($this->tableExists('company_applications')) {
            DB::table('company_applications')
                ->whereIn('slug', ['sapphire-homes', 'northgate-realty', 'coral-bay-apartments'])
                ->delete();
        }

        DB::table('users')->whereIn('company_id', $companyIds)->delete();
    }

    private function ensureCompanySettings(int $companyId, array $settings, string $fromName): void
    {
        DB::table('company_settings')->updateOrInsert(
            ['company_id' => $companyId],
            $this->filterColumns('company_settings', $settings + ['updated_at' => now(), 'created_at' => now()])
        );

        if ($this->tableExists('notification_settings')) {
            DB::table('notification_settings')->updateOrInsert(
                ['company_id' => $companyId],
                $this->filterColumns('notification_settings', [
                    'email_enabled' => true,
                    'send_overdue_rent_reminders' => true,
                    'send_lease_expiry_reminders' => true,
                    'lease_expiry_days_notice' => 30,
                    'from_name' => $fromName,
                    'from_email' => $settings['support_email'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    // ---------------------------------------------------------------------
    // Low-level helpers
    // ---------------------------------------------------------------------

    private function report(int $companyOneId, int $companyTwoId): void
    {
        $this->command->info('');
        $this->command->info('Frontend demo data seeded.');
        $this->command->info('---------------------------------------------');
        $this->command->info('Super Admin  : '.self::SUPER_ADMIN_EMAIL.' / '.self::SUPER_ADMIN_PASSWORD.'   (all workspaces)');
        $this->command->info('HOWSEY Admin : estate.admin@howsey.local / '.self::ADMIN_PASSWORD.'   (company '.$companyOneId.')');
        $this->command->info('Palm Grove   : palm.admin@howsey.local / '.self::ADMIN_PASSWORD.'   (company '.$companyTwoId.')');
        $this->command->info('Managers/Accountants/Tenants : password');
        $this->command->info('---------------------------------------------');
        $this->command->info('Buildings: '.DB::table('buildings')->whereIn('company_id', [$companyOneId, $companyTwoId])->count()
            .' | Units: '.DB::table('units')->whereIn('company_id', [$companyOneId, $companyTwoId])->count()
            .' | Tenants: '.DB::table('tenants')->whereIn('company_id', [$companyOneId, $companyTwoId])->count());
        $this->command->info('Rents: '.DB::table('rents')->whereIn('company_id', [$companyOneId, $companyTwoId])->count()
            .' | Payments: '.DB::table('payments')->whereIn('company_id', [$companyOneId, $companyTwoId])->count()
            .' | Maintenance: '.DB::table('maintenance_requests')->whereIn('company_id', [$companyOneId, $companyTwoId])->count());
    }

    private function firstOrCreateRole(string $name, string $description): int
    {
        $existingId = DB::table('roles')->where('name', $name)->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) $this->insertRecord('roles', [
            'name' => $name,
            'description' => $description,
        ]);
    }

    private function createUser(?int $companyId, string $name, string $email, string $phone, string $password, int $roleId, float $coinBalance): int
    {
        return (int) $this->insertRecord('users', [
            'company_id' => $companyId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role_id' => $roleId,
            'is_active' => true,
            'coin_balance' => $coinBalance,
        ]);
    }

    private function insertRecord(string $table, array $attributes): int
    {
        $payload = $this->filterColumns($table, array_merge($attributes, $this->timestampPayload($table)));

        return (int) DB::table($table)->insertGetId($payload);
    }

    private function filterColumns(string $table, array $attributes): array
    {
        $columns = $this->getColumns($table);

        return array_filter(
            $attributes,
            fn ($value, $key) => in_array($key, $columns, true),
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
