<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_subscription_payments')) {
            return;
        }

        Schema::create('company_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 120)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('transaction_status', 40)->default('pending');
            $table->string('paystack_access_code', 120)->nullable();
            $table->text('paystack_authorization_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('period_starts_at')->nullable();
            $table->timestamp('period_ends_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at'], 'company_subscription_payments_company_created_idx');
            $table->index(['transaction_status', 'created_at'], 'company_subscription_payments_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscription_payments');
    }
};
