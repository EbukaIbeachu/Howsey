<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update buildings table
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedInteger('total_units')->default(0)->after('postal_code');
            $table->unsignedInteger('total_floors')->default(1)->after('total_units');
        });

        // Create maintenance_requests table
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['PLUMBING', 'ELECTRICAL', 'HVAC', 'STRUCTURAL', 'APPLIANCE', 'OTHER'])->default('OTHER');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH', 'EMERGENCY'])->default('LOW');
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'RESOLVED', 'CANCELLED'])->default('PENDING');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['total_units', 'total_floors']);
        });
    }
};
