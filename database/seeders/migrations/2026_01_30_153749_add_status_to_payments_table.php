<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_method')) {
                if (Schema::hasColumn('payments', 'method')) {
                    DB::statement('ALTER TABLE payments CHANGE method payment_method VARCHAR(50) NULL');
                } else {
                    $table->string('payment_method', 50)->nullable();
                }
            }

            if (! Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('COMPLETED')->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('payments', 'payment_method')) {
                DB::statement('ALTER TABLE payments CHANGE payment_method method VARCHAR(50) NULL');
            }
        });
    }
};
