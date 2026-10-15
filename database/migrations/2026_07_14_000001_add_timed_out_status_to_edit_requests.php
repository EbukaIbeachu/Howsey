<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edit_requests')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE edit_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'timed_out') NOT NULL DEFAULT 'pending'");
        }

        DB::table('edit_requests')
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'timed_out',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('edit_requests')) {
            return;
        }

        DB::table('edit_requests')
            ->where('status', 'timed_out')
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE edit_requests MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
