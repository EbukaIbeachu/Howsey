<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'subscription_started_at')) {
                $table->timestamp('subscription_started_at')->nullable()->after('onboarded_at');
            }
            if (! Schema::hasColumn('companies', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
                $table->index('subscription_expires_at', 'companies_subscription_expires_at_index');
            }
            if (! Schema::hasColumn('companies', 'subscription_fee')) {
                $table->decimal('subscription_fee', 12, 2)->default(50000)->after('subscription_expires_at');
            }
        });

        $defaultFee = (float) config('services.paystack.company_subscription_fee', 50000);
        $durationMonths = max((int) config('services.paystack.subscription_duration_months', 12), 1);

        DB::table('companies')
            ->orderBy('id')
            ->select(['id', 'created_at', 'onboarded_at', 'subscription_started_at', 'subscription_expires_at', 'subscription_fee'])
            ->chunkById(100, function ($companies) use ($defaultFee, $durationMonths) {
                foreach ($companies as $company) {
                    $startedAt = $company->subscription_started_at
                        ? Carbon::parse($company->subscription_started_at)
                        : Carbon::parse($company->onboarded_at ?: $company->created_at ?: now());

                    $expiresAt = $company->subscription_expires_at
                        ? Carbon::parse($company->subscription_expires_at)
                        : $startedAt->copy()->addMonths($durationMonths);

                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update([
                            'subscription_started_at' => $startedAt,
                            'subscription_expires_at' => $expiresAt,
                            'subscription_fee' => $company->subscription_fee ?: $defaultFee,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'subscription_expires_at')) {
                $table->dropIndex('companies_subscription_expires_at_index');
                $table->dropColumn('subscription_expires_at');
            }
            if (Schema::hasColumn('companies', 'subscription_started_at')) {
                $table->dropColumn('subscription_started_at');
            }
            if (Schema::hasColumn('companies', 'subscription_fee')) {
                $table->dropColumn('subscription_fee');
            }
        });
    }
};
