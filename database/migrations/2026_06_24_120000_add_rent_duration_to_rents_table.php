<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rents', 'rent_duration')) {
            Schema::table('rents', function (Blueprint $table) {
                $table->string('rent_duration', 20)->default('annually')->after('annual_amount');
            });
        }

        DB::table('rents')
            ->whereNull('rent_duration')
            ->update(['rent_duration' => 'annually']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('rents', 'rent_duration')) {
            Schema::table('rents', function (Blueprint $table) {
                $table->dropColumn('rent_duration');
            });
        }
    }
};
