<?php

use App\Models\ManagerEditRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manager_edit_requests')) {
            return;
        }

        Schema::table('manager_edit_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('manager_edit_requests', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('reviewed_at');
                $table->index(['status', 'expires_at'], 'manager_edit_requests_status_expires_at_index');
            }
        });

        $isMysql = DB::getDriverName() === 'mysql';

        // Some deployments may still have legacy `completed` values if the previous
        // rename migration was not run there yet. Widen first, normalize data, then narrow.
        if ($isMysql) {
            DB::statement("ALTER TABLE manager_edit_requests MODIFY status ENUM('pending', 'completed', 'approved', 'rejected', 'timed_out') NOT NULL DEFAULT 'pending'");
        }

        DB::table('manager_edit_requests')
            ->where('status', 'completed')
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);

        if ($isMysql) {
            DB::table('manager_edit_requests')
                ->where('status', 'approved')
                ->whereNull('expires_at')
                ->update([
                    'expires_at' => DB::raw('DATE_ADD(COALESCE(reviewed_at, updated_at, created_at), INTERVAL '.ManagerEditRequest::EDIT_WINDOW_HOURS.' HOUR)'),
                ]);
        } else {
            DB::table('manager_edit_requests')
                ->where('status', 'approved')
                ->whereNull('expires_at')
                ->update([
                    'expires_at' => DB::raw("datetime(COALESCE(reviewed_at, updated_at, created_at), '+" . ManagerEditRequest::EDIT_WINDOW_HOURS . " hours')"),
                ]);
        }

        DB::table('manager_edit_requests')
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'timed_out',
                'updated_at' => now(),
            ]);

        if ($isMysql) {
            DB::statement("ALTER TABLE manager_edit_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'timed_out') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('manager_edit_requests')) {
            return;
        }

        DB::table('manager_edit_requests')
            ->where('status', 'timed_out')
            ->update([
                'status' => 'approved',
                'updated_at' => now(),
            ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE manager_edit_requests MODIFY status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('manager_edit_requests', function (Blueprint $table) {
            if (Schema::hasColumn('manager_edit_requests', 'expires_at')) {
                $table->dropIndex('manager_edit_requests_status_expires_at_index');
                $table->dropColumn('expires_at');
            }
        });
    }
};
