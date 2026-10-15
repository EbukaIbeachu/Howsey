<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE accounting_entries MODIFY COLUMN status ENUM('pending', 'finalized', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE accounting_entries SET status = 'pending' WHERE status = 'rejected'");
            DB::statement("ALTER TABLE accounting_entries MODIFY COLUMN status ENUM('pending', 'finalized') NOT NULL DEFAULT 'pending'");
        }
    }
};
