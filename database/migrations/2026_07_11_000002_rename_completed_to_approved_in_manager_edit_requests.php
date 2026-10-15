<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manager_edit_requests')) {
            return;
        }

        DB::table('manager_edit_requests')
            ->where('status', 'completed')
            ->update(['status' => 'approved']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE manager_edit_requests MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('manager_edit_requests')) {
            return;
        }

        DB::table('manager_edit_requests')
            ->where('status', 'approved')
            ->update(['status' => 'completed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE manager_edit_requests MODIFY status ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
