<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('rent_reminders_exempt')->default(false)->after('active');
            $table->string('rent_reminders_start_milestone', 40)->nullable()->after('rent_reminders_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['rent_reminders_exempt', 'rent_reminders_start_milestone']);
        });
    }
};
