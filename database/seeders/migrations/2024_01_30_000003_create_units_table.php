<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('building_id');
            $table->string('unit_number', 50);
            $table->string('floor', 20)->nullable();
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedTinyInteger('bathrooms')->default(0);
            $table->enum('status', ['AVAILABLE', 'OCCUPIED', 'MAINTENANCE'])->default('AVAILABLE');
            $table->timestamps();

            $table->foreign('building_id')->references('id')->on('buildings')->onUpdate('cascade')->onDelete('restrict');
            $table->unique(['building_id', 'unit_number', 'floor']);
            $table->index('building_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
