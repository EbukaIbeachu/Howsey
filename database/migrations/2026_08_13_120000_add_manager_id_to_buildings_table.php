<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('buildings', 'manager_id')) {
            Schema::table('buildings', function (Blueprint $table) {
                $table->unsignedBigInteger('manager_id')->nullable()->after('image_path');
            });
        }

        if (! $this->hasForeignKey('buildings', 'buildings_manager_id_fk')) {
            Schema::table('buildings', function (Blueprint $table) {
                $table->foreign('manager_id', 'buildings_manager_id_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('buildings', 'manager_id')) {
            Schema::table('buildings', function (Blueprint $table) {
                if ($this->hasForeignKey('buildings', 'buildings_manager_id_fk')) {
                    $table->dropForeign('buildings_manager_id_fk');
                }
                $table->dropColumn('manager_id');
            });
        }
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
