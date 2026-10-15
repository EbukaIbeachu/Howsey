<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'platform_status')) {
                $table->string('platform_status', 30)->default('active')->after('status');
            }

            if (! Schema::hasColumn('companies', 'platform_status_note')) {
                $table->text('platform_status_note')->nullable()->after('platform_status');
            }

            if (! Schema::hasColumn('companies', 'platform_status_changed_at')) {
                $table->timestamp('platform_status_changed_at')->nullable()->after('platform_status_note');
            }

            if (! Schema::hasColumn('companies', 'platform_status_changed_by')) {
                $table->foreignId('platform_status_changed_by')->nullable()->after('platform_status_changed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'platform_status_changed_by')) {
                $table->dropConstrainedForeignId('platform_status_changed_by');
            }

            foreach (['platform_status_changed_at', 'platform_status_note', 'platform_status'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
