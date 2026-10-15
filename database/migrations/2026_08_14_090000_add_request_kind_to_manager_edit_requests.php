<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manager_edit_requests') || Schema::hasColumn('manager_edit_requests', 'request_kind')) {
            return;
        }

        Schema::table('manager_edit_requests', function (Blueprint $table) {
            $table->string('request_kind', 40)->default('edit')->after('requestable_id');
            $table->index(['request_kind', 'status'], 'manager_edit_requests_kind_status_index');
        });

        DB::table('manager_edit_requests')
            ->whereNull('request_kind')
            ->update([
                'request_kind' => 'edit',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('manager_edit_requests') || ! Schema::hasColumn('manager_edit_requests', 'request_kind')) {
            return;
        }

        Schema::table('manager_edit_requests', function (Blueprint $table) {
            $table->dropIndex('manager_edit_requests_kind_status_index');
            $table->dropColumn('request_kind');
        });
    }
};
