<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('due_date');
            $table->string('milestone', 40); // e.g. 60_days_before, due_date, 30_days_after
            $table->json('sent_to')->nullable(); // user ids that received the reminder
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->foreign('rent_id')->references('id')->on('rents')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');

            $table->unique(['rent_id', 'due_date', 'milestone']);
            $table->index(['due_date', 'milestone']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_reminders');
    }
};
