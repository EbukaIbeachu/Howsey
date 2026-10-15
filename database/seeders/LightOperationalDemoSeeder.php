<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LightOperationalDemoSeeder extends Seeder
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
            $scenario = $this->seedCoreScenario($actors, $tenantUsers);

            $this->seedPayments($scenario, $actors);
            $this->seedAnnouncements($scenario, $actors);
            $this->seedMaintenance($scenario, $tenantUsers);
            $this->seedAccounting($scenario, $actors);
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
            if ($this->tableExists($table)) {
                DB::table($table)->delete();
            }
        }

        if ($this->tableExists('users')) {
            DB::table('users')->where('role_id', $tenantRoleId)->delete();
        }
    }

    private function ensureCoreUsers(array $roles): array
    {
        $adminId = $this->firstUserIdByRole($roles['admin'])
            ?? $this->createUser('Super Admin', 'admin@howsey.local', '08030000001', 'ChangeMe123!', $roles['admin'], 5000.00);

        $managerIds = DB::table('users')
            ->where('role_id', $roles['manager'])
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        if (count($managerIds) < 1) {
            $managerIds[] = $this->createUser('Daniel Manager', 'daniel.manager@howsey.online', '08030000002', 'password', $roles['manager'], 1200.00);
        }

        if (count($managerIds) < 2) {
            $managerIds[] = $this->createUser('Sandra Manager', 'sandra.manager@howsey.online', '08030000003', 'password', $roles['manager'], 950.00);
        }

        $accountantId = $this->firstUserIdByRole($roles['accountant'])
            ?? $this->createUser('Ada Accountant', 'ada.accountant@howsey.online', '08030000004', 'password', $roles['accountant'], 800.00);

        return [
            'admin' => $adminId,
            'manager_1' => $managerIds[0],
            'manager_2' => $managerIds[1],
            'accountant' => $accountantId,
        ];
    }

    private function createTenantUsers(int $tenantRoleId): array
    {
        return [
            'ada' => $this->createUser('Ada Nwosu', 'ada.nwosu@tenant.howsey.online', '08040000010', 'password', $tenantRoleId, 120.00),
            'tunde' => $this->createUser('Tunde Bello', 'tunde.bello@tenant.howsey.online', '08040000011', 'password', $tenantRoleId, 90.00),
            'miriam' => $this->createUser('Miriam Okafor', 'miriam.okafor@tenant.howsey.online', '08040000013', 'password', $tenantRoleId, 110.00),
            'kenneth' => $this->createUser('Kenneth Udo', 'kenneth.udo@tenant.howsey.online', '08040000019', 'password', $tenantRoleId, 88.00),
        ];
    }

    private function seedCoreScenario(array $actors, array $tenantUsers): array
    {
        $scenario = ['buildings' => [], 'units' => [], 'tenants' => [], 'rents' => []];

        $scenario['buildings']['maple'] = $this->insertRecord('buildings', [
            'name' => 'Maple Court',
            'address_line1' => '12 Admiralty Way',
            'address_line2' => null,
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '101001',
            'total_units' => 4,
            'total_floors' => 2,
            'image_path' => null,
            'manager_id' => $actors['manager_1'],
            'created_by' => $actors['admin'],
        ]);

        $scenario['buildings']['cedar'] = $this->insertRecord('buildings', [
            'name' => 'Cedar Heights',
            'address_line1' => '24 GRA Extension',
            'address_line2' => null,
            'city' => 'Port Harcourt',
            'state' => 'Rivers',
            'postal_code' => '500001',
            'total_units' => 4,
            'total_floors' => 3,
            'image_path' => null,
            'manager_id' => $actors['manager_2'],
            'created_by' => $actors['admin'],
        ]);

        $unitDefinitions = [
            'maple_101' => ['maple', '101', '1', 1, 1, 'OCCUPIED', $actors['manager_1']],
            'maple_102' => ['maple', '102', '1', 2, 2, 'OCCUPIED', $actors['manager_1']],
            'maple_103' => ['maple', '103', '2', 2, 2, 'AVAILABLE', $actors['manager_1']],
            'maple_104' => ['maple', '104', '2', 1, 1, 'MAINTENANCE', $actors['manager_1']],
            'cedar_201' => ['cedar', '201', '1', 1, 1, 'OCCUPIED', $actors['manager_2']],
            'cedar_202' => ['cedar', '202', '2', 3, 2, 'OCCUPIED', $actors['manager_2']],
            'cedar_203' => ['cedar', '203', '2', 2, 2, 'AVAILABLE', $actors['manager_2']],
            'cedar_204' => ['cedar', '204', '3', 1, 1, 'AVAILABLE', $actors['manager_2']],
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
            'ada_current' => [$tenantUsers['ada'], 'maple_101', 'Ada Nwosu', '08040000010', 'ada.nwosu@tenant.howsey.online', '101', '2025-02-01', null, true, $actors['manager_1']],
            'tunde_current' => [$tenantUsers['tunde'], 'maple_102', 'Tunde Bello', '08040000011', 'tunde.bello@tenant.howsey.online', '102', '2024-03-15', null, true, $actors['manager_1']],
            'john_old' => [null, 'maple_101', 'John Peters', '08050000013', 'john.peters@old.howsey.online', '101', '2024-01-01', '2025-01-15', false, $actors['manager_1']],
            'miriam_current' => [$tenantUsers['miriam'], 'cedar_201', 'Miriam Okafor', '08040000013', 'miriam.okafor@tenant.howsey.online', '201', '2024-07-01', null, true, $actors['manager_2']],
            'kenneth_current' => [$tenantUsers['kenneth'], 'cedar_202', 'Kenneth Udo', '08040000019', 'kenneth.udo@tenant.howsey.online', '202', '2026-01-15', null, true, $actors['manager_2']],
            'ngozi_old' => [null, 'cedar_201', 'Ngozi Afolabi', '08050000014', 'ngozi.afolabi@old.howsey.online', '201', '2024-01-10', '2024-06-25', false, $actors['manager_2']],
        ];

        foreach ($tenantDefinitions as $key => [$userId, $unitKey, $fullName, $phone, $email, $roomNumber, $moveInDate, $moveOutDate, $active, $createdBy]) {
            $scenario['tenants'][$key] = $this->insertRecord('tenants', [
                'user_id' => $userId,
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
            'maple_102_current' => ['tunde_current', 'maple_102', 1550000.00, 15, '2024-03-15', null, 'ACTIVE', $actors['manager_1']],
            'cedar_201_old' => ['ngozi_old', 'cedar_201', 1100000.00, 10, '2024-01-10', '2024-06-25', 'EXPIRED', $actors['manager_2']],
            'cedar_201_current' => ['miriam_current', 'cedar_201', 1400000.00, 1, '2024-07-01', null, 'ACTIVE', $actors['manager_2']],
            'cedar_202_current' => ['kenneth_current', 'cedar_202', 1650000.00, 15, '2026-01-15', null, 'ACTIVE', $actors['manager_2']],
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
            ['maple_101_old', '2024-01-05', '2024-01-01', 1050000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R1-2024', 'Old agreement paid', $actors['manager_1']],
            ['maple_101_current', '2025-02-05', '2025-02-01', 1250000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R2-2025', 'Paid in full', $actors['manager_1']],
            ['maple_101_current', '2026-02-10', '2026-02-01', 900000.00, 'CARD', 'COMPLETED', 'L-R2-2026', 'Partial payment leaves arrears', $actors['manager_1']],
            ['maple_102_current', '2024-03-20', '2024-03-15', 1550000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R3-2024', 'Initial payment', $actors['manager_1']],
            ['maple_102_current', '2025-03-20', '2025-03-15', 1550000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R3-2025', 'Paid', $actors['manager_1']],
            ['maple_102_current', '2026-03-25', '2026-03-15', 1000000.00, 'CASH', 'COMPLETED', 'L-R3-2026', 'Partial payment', $actors['manager_1']],

            ['cedar_201_old', '2024-01-15', '2024-01-10', 1100000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R4-2024', 'Former tenant payment', $actors['manager_2']],
            ['cedar_201_current', '2024-07-05', '2024-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R5-2024', 'Paid', $actors['manager_2']],
            ['cedar_201_current', '2025-07-07', '2025-07-01', 1400000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R5-2025', 'Paid', $actors['manager_2']],
            ['cedar_201_current', '2026-07-08', '2026-07-01', 700000.00, 'CARD', 'COMPLETED', 'L-R5-2026', 'Partial payment', $actors['manager_2']],
            ['cedar_202_current', '2026-01-20', '2026-01-15', 800000.00, 'BANK_TRANSFER', 'COMPLETED', 'L-R6-2026', 'Partial first payment', $actors['manager_2']],
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

        $this->insertRecord('announcements', [
            'building_id' => $scenario['buildings']['maple'],
            'manager_id' => $actors['manager_1'],
            'title' => 'Meter Reading',
            'content' => 'Monthly meter reading will happen on Saturday morning.',
        ]);

        $this->insertRecord('announcements', [
            'building_id' => $scenario['buildings']['cedar'],
            'manager_id' => $actors['manager_2'],
            'title' => 'Gate Maintenance',
            'content' => 'Main gate servicing is scheduled for Friday.',
        ]);
    }

    private function seedMaintenance(array $scenario, array $tenantUsers): void
    {
        if (! $this->tableExists('maintenance_requests')) {
            return;
        }

        $this->insertRecord('maintenance_requests', [
            'unit_id' => $scenario['units']['maple_101'],
            'tenant_id' => $scenario['tenants']['ada_current'],
            'title' => 'Low Water Pressure',
            'description' => 'Water pressure drops every evening.',
            'type' => 'PLUMBING',
            'priority' => 'HIGH',
            'status' => 'IN_PROGRESS',
            'resolved_at' => null,
            'created_by' => $tenantUsers['ada'],
        ]);

        $this->insertRecord('maintenance_requests', [
            'unit_id' => $scenario['units']['cedar_202'],
            'tenant_id' => $scenario['tenants']['kenneth_current'],
            'title' => 'Flickering Lights',
            'description' => 'Bedroom lights flicker at night.',
            'type' => 'ELECTRICAL',
            'priority' => 'MEDIUM',
            'status' => 'PENDING',
            'resolved_at' => null,
            'created_by' => $tenantUsers['kenneth'],
        ]);
    }

    private function seedAccounting(array $scenario, array $actors): void
    {
        if ($this->tableExists('accounting_entries')) {
            $this->insertRecord('accounting_entries', [
                'building_id' => $scenario['buildings']['maple'],
                'type' => 'expense',
                'category' => 'Cleaning',
                'description' => 'Common area cleaning supplies',
                'amount' => 45000.00,
                'entry_date' => '2024-06-15',
                'is_locked' => true,
                'finalized_at' => '2024-06-15 11:00:00',
                'created_by' => $actors['accountant'],
                'updated_by' => null,
                'status' => 'finalized',
                'extra_details' => '{"property_id":"MAPLE-COURT"}',
            ]);

            $this->insertRecord('accounting_entries', [
                'building_id' => $scenario['buildings']['cedar'],
                'type' => 'expense',
                'category' => 'Security Supplies',
                'description' => 'Gatehouse consumables',
                'amount' => 65000.00,
                'entry_date' => '2025-09-10',
                'is_locked' => true,
                'finalized_at' => '2025-09-10 16:00:00',
                'created_by' => $actors['accountant'],
                'updated_by' => null,
                'status' => 'finalized',
                'extra_details' => '{"property_id":"CEDAR-HEIGHTS"}',
            ]);
        }

        $expenseTable = $this->tableExists('expenses')
            ? 'expenses'
            : ($this->tableExists('legacy_expenses') ? 'legacy_expenses' : null);

        if ($expenseTable) {
            $this->insertRecord($expenseTable, [
                'building_id' => $scenario['buildings']['maple'],
                'amount' => 30000.00,
                'category' => 'Cleaning',
                'description' => 'Quick demo cleaning expense',
                'expense_date' => '2026-01-20',
                'created_by' => $actors['accountant'],
            ]);
        }
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
        $id = $this->tableExists('users')
            ? DB::table('users')->where('role_id', $roleId)->orderBy('id')->value('id')
            : null;

        return $id ? (int) $id : null;
    }

    private function createUser(string $name, string $email, string $phone, string $password, int $roleId, float $coinBalance): int
    {
        return (int) $this->insertRecord('users', [
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

        return DB::table($table)->insertGetId($payload);
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
