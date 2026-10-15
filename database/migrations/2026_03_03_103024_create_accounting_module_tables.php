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
        // 1. accounting_entries table
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense']);
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'finalized'])->default('pending');
            $table->json('extra_details')->nullable(); // For fields like Tenant Name, Property ID, etc.
            $table->timestamps();

            // Indexing for faster building-level summaries
            $table->index(['building_id', 'type', 'entry_date']);
        });

        // 2. audit_logs table (Immutable)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role')->nullable();
            $table->string('action_type'); // e.g., create, edit, finalize, lock, unlock, request_edit, approve_edit, reject_edit
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_type')->nullable(); // e.g., AccountingEntry
            $table->unsignedBigInteger('building_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('device_type', ['desktop', 'mobile'])->nullable();
            $table->timestamp('created_at')->useCurrent(); // Immutable log, only created_at needed
        });

        // 3. edit_requests table
        Schema::create('edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_entry_id')->constrained('accounting_entries')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Who requested
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete(); // Who approved/rejected
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For temporary edit permission
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edit_requests');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('accounting_entries');
    }
};
