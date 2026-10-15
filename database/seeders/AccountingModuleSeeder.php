<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccountingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'accountant']
        );

        if (! AuditLog::where('action_type', 'legacy_deprecation')->exists()) {
            AuditLog::create([
                'action_type' => 'legacy_deprecation',
                'record_type' => 'System',
                'old_values' => ['module' => 'expenses'],
                'new_values' => ['module' => 'accounting'],
                'device_type' => 'desktop',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Migration Seeder',
            ]);
        }
    }
}
