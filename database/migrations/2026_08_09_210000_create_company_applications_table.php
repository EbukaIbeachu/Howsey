<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_applications')) {
            return;
        }

        Schema::create('company_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 20)->default('#7c3aed');
            $table->text('address')->nullable();
            $table->string('support_email', 150)->nullable();
            $table->string('support_phone', 40)->nullable();
            $table->string('receipt_header', 150)->nullable();
            $table->string('email_header', 150)->nullable();
            $table->string('admin_name', 150);
            $table->string('admin_email', 150)->unique();
            $table->string('admin_phone', 40)->nullable();
            $table->string('admin_password');
            $table->boolean('sample_dataset')->default(false);
            $table->string('payment_reference', 120)->unique();
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->string('paystack_access_code')->nullable();
            $table->text('paystack_authorization_url')->nullable();
            $table->string('paystack_transaction_status', 40)->default('pending');
            $table->timestamp('paystack_paid_at')->nullable();
            $table->timestamp('paystack_verified_at')->nullable();
            $table->longText('paystack_payload')->nullable();
            $table->string('application_status', 40)->default('pending_payment');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_status', 'created_at'], 'company_apps_status_created_idx');
            $table->index(['paystack_transaction_status', 'created_at'], 'company_apps_paystack_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_applications');
    }
};
