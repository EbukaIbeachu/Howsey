<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('expenses', 'legacy_expenses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('legacy_expenses', 'expenses');
    }
};
