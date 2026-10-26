<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('slug', 160)->unique();
                $table->string('status', 30)->default('active');
                $table->boolean('is_demo')->default(false);
                $table->timestamp('onboarded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('logo_path')->nullable();
                $table->string('primary_color', 20)->default('#7c3aed');
                $table->string('email_header')->nullable();
                $table->string('receipt_header')->nullable();
                $table->text('address')->nullable();
                $table->string('support_email')->nullable();
                $table->string('support_phone', 40)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('module', 50);
                $table->string('action', 50);
                $table->string('name', 120)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('role_id');
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['role_id', 'permission_id']);
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('notification_settings')) {
            Schema::create('notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('email_enabled')->default(true);
                $table->boolean('send_overdue_rent_reminders')->default(true);
                $table->boolean('send_lease_expiry_reminders')->default(true);
                $table->unsignedSmallInteger('lease_expiry_days_notice')->default(30);
                $table->string('from_name')->nullable();
                $table->string('from_email')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('type', 60);
                $table->string('channel', 30)->default('email');
                $table->string('subject', 190)->nullable();
                $table->longText('body')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'type', 'channel']);
            });
        }

        if (! Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('type', 60);
                $table->string('subject', 190)->nullable();
                $table->longText('body_html')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'type']);
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'slug')) {
                    $table->string('slug', 60)->nullable()->after('name');
                }
                if (! Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(true)->after('description');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('role_id')->constrained('companies')->nullOnDelete();
                    $table->index(['company_id', 'role_id']);
                }
            });
        }

        $companyTables = [
            'buildings',
            'units',
            'tenants',
            'rents',
            'payments',
            'maintenance_requests',
            'accounting_entries',
            'audit_logs',
            'edit_requests',
            'manager_edit_requests',
            'announcements',
        ];

        foreach ($companyTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName === 'audit_logs') {
                    $table->unsignedBigInteger('company_id')->nullable()->after('building_id');
                    $table->index(['company_id', 'created_at']);
                } else {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
                    $table->index(['company_id', 'created_at']);
                }
            });
        }

        $defaultCompanyId = $this->ensureDefaultCompany();

        if (Schema::hasTable('roles')) {
            $this->seedRolesAndPermissions();
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            DB::table('users')
                ->whereNull('company_id')
                ->update(['company_id' => $defaultCompanyId]);
        }

        foreach ($companyTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            DB::table($tableName)
                ->whereNull('company_id')
                ->update(['company_id' => $defaultCompanyId]);
        }

        if (Schema::hasTable('companies')) {
            DB::table('companies')
                ->where('id', $defaultCompanyId)
                ->update(['onboarded_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $companyTables = [
            'announcements',
            'manager_edit_requests',
            'edit_requests',
            'audit_logs',
            'accounting_entries',
            'maintenance_requests',
            'payments',
            'rents',
            'tenants',
            'units',
            'buildings',
        ];

        foreach ($companyTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if ($tableName !== 'audit_logs') {
                    $table->dropConstrainedForeignId('company_id');
                } else {
                    $table->dropIndex([$tableName === 'audit_logs' ? 'company_id' : 'company_id', 'created_at']);
                    $table->dropColumn('company_id');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (Schema::hasColumn('roles', 'slug')) {
                    $table->dropColumn('slug');
                }
                if (Schema::hasColumn('roles', 'is_system')) {
                    $table->dropColumn('is_system');
                }
            });
        }

        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('companies');
    }

    private function ensureDefaultCompany(): int
    {
        $existing = DB::table('companies')->where('slug', 'default-company')->first();

        if ($existing) {
            $companyId = (int) $existing->id;
        } else {
            $name = config('app.name', 'HOWSEY Property Management');
            $companyId = DB::table('companies')->insertGetId([
                'name' => $name,
                'slug' => 'default-company',
                'status' => 'active',
                'is_demo' => false,
                'onboarded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('company_settings')->updateOrInsert(
            ['company_id' => $companyId],
            [
                'primary_color' => '#7c3aed',
                'email_header' => config('app.name', 'HOWSEY Property Management'),
                'receipt_header' => 'Official Payment Receipt',
                'support_email' => config('mail.from.address'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('notification_settings')->updateOrInsert(
            ['company_id' => $companyId],
            [
                'email_enabled' => true,
                'send_overdue_rent_reminders' => true,
                'send_lease_expiry_reminders' => true,
                'lease_expiry_days_notice' => 30,
                'from_name' => config('app.name', 'HOWSEY Property Management'),
                'from_email' => config('mail.from.address'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        foreach ($this->defaultNotificationTemplates() as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['company_id' => $companyId, 'type' => $template['type'], 'channel' => 'email'],
                $template + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        foreach ($this->defaultDocumentTemplates() as $template) {
            DB::table('document_templates')->updateOrInsert(
                ['company_id' => $companyId, 'type' => $template['type']],
                $template + ['created_at' => now(), 'updated_at' => now()]
            );
        }

        return $companyId;
    }

    private function seedRolesAndPermissions(): void
    {
        $permissionNames = collect($this->permissionMatrix())
            ->map(function (array $permission) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permission['name']],
                    $permission + ['created_at' => now(), 'updated_at' => now()]
                );

                return $permission['name'];
            });

        $rolePermissions = [
            'super_admin' => $permissionNames->all(),
            'admin' => $permissionNames->reject(fn (string $name) => str_starts_with($name, 'companies.'))->all(),
            'manager' => [
                'dashboard.view',
                'buildings.view', 'buildings.create', 'buildings.edit',
                'tenants.view', 'tenants.create', 'tenants.edit',
                'rentals.view', 'rentals.create', 'rentals.edit',
                'payments.view', 'payments.create', 'payments.edit',
                'maintenance.view', 'maintenance.create', 'maintenance.edit',
                'users.view', 'users.create',
                'accounting.view',
                'exports.view',
            ],
            'accountant' => [
                'dashboard.view',
                'payments.view',
                'accounting.view', 'accounting.create', 'accounting.edit',
                'exports.view', 'exports.run',
            ],
            'tenant' => [
                'dashboard.view',
                'maintenance.view', 'maintenance.create',
                'payments.view',
                'rentals.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $roleId = DB::table('roles')->updateOrInsert(
                ['name' => $roleName],
                [
                    'slug' => Str::slug($roleName),
                    'description' => Str::headline(str_replace('_', ' ', $roleName)),
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $role = DB::table('roles')->where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        if (! $superAdminRole || ! Schema::hasTable('users')) {
            return;
        }

        $hasSuperAdmin = DB::table('users')->where('role_id', $superAdminRole->id)->exists();
        if ($hasSuperAdmin) {
            return;
        }

        $existingAdminRole = DB::table('roles')->where('name', 'admin')->first();
        if (! $existingAdminRole) {
            return;
        }

        $candidate = DB::table('users')
            ->where('role_id', $existingAdminRole->id)
            ->orderBy('id')
            ->first();

        if ($candidate) {
            DB::table('users')
                ->where('id', $candidate->id)
                ->update(['role_id' => $superAdminRole->id, 'updated_at' => now()]);
        }
    }

    private function permissionMatrix(): array
    {
        $modules = [
            'dashboard' => ['view'],
            'companies' => ['view', 'create', 'edit', 'delete'],
            'settings' => ['view', 'edit'],
            'buildings' => ['view', 'create', 'edit', 'delete'],
            'tenants' => ['view', 'create', 'edit', 'delete'],
            'rentals' => ['view', 'create', 'edit', 'delete'],
            'payments' => ['view', 'create', 'edit', 'delete'],
            'maintenance' => ['view', 'create', 'edit', 'delete'],
            'users' => ['view', 'create', 'edit', 'delete'],
            'roles' => ['view', 'create', 'edit', 'delete'],
            'accounting' => ['view', 'create', 'edit', 'delete'],
            'exports' => ['view', 'run'],
            'notifications' => ['view', 'edit'],
        ];

        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'module' => $module,
                    'action' => $action,
                    'name' => $module.'.'.$action,
                ];
            }
        }

        return $permissions;
    }

    private function defaultNotificationTemplates(): array
    {
        return [
            [
                'type' => 'overdue_rent',
                'subject' => 'Rent reminder for {{tenant_name}}',
                'body' => '<p>Hello {{tenant_name}},</p><p>This is a reminder that your rent for {{building_name}} / unit {{unit_number}} is overdue. Current outstanding balance: {{balance}}.</p><p>Please contact {{company_name}} if you need support.</p>',
            ],
            [
                'type' => 'lease_expiry',
                'subject' => 'Lease expiry notice for {{tenant_name}}',
                'body' => '<p>Hello {{tenant_name}},</p><p>Your lease for {{building_name}} / unit {{unit_number}} is due to expire on {{lease_end_date}}.</p><p>Please contact {{company_name}} to discuss renewal.</p>',
            ],
        ];
    }

    private function defaultDocumentTemplates(): array
    {
        return [
            [
                'type' => 'rent_receipt',
                'subject' => 'Rent Receipt',
                'body_html' => '<h2>{{company_name}}</h2><p>{{receipt_header}}</p><p>Received from {{tenant_name}} the sum of {{amount}} for unit {{unit_number}} in {{building_name}}.</p>',
            ],
            [
                'type' => 'arrears_notice',
                'subject' => 'Arrears Notice',
                'body_html' => '<h2>{{company_name}}</h2><p>Dear {{tenant_name}}, your account is in arrears by {{balance}} for unit {{unit_number}} at {{building_name}}.</p>',
            ],
            [
                'type' => 'lease_renewal_notice',
                'subject' => 'Lease Renewal Notice',
                'body_html' => '<h2>{{company_name}}</h2><p>Dear {{tenant_name}}, your lease for {{unit_number}} at {{building_name}} ends on {{lease_end_date}}. Please contact us to renew.</p>',
            ],
        ];
    }
};
