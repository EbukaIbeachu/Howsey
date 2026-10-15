<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'coin_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('coin_balance', 10, 2)->default(0)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'coin_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('coin_balance');
            });
        }
    }
};
