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
                    $table->renameColumn('method', 'payment_method');
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
                $table->renameColumn('payment_method', 'method');
            }
        });
    }
};
