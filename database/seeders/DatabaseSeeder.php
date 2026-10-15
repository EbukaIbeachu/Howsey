<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $tenantRole = Role::firstOrCreate(['name' => 'tenant']);
        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);

        // Create Super Admin User
        User::updateOrCreate(
            ['email' => 'admin@howsey.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('ChangeMe123!'),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
            ]
        );

        // Run manually when you want the realistic 2024-2026 demo dataset:
        // php artisan db:seed --class=ThreeYearOperationalDemoSeeder
        // Run manually when you want a smaller quick-test dataset:
        // php artisan db:seed --class=LightOperationalDemoSeeder
        // $this->call(ThreeYearOperationalDemoSeeder::class);
    }
}
