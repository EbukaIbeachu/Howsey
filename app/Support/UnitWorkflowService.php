<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitWorkflowService
{
    public function createFromUnitForm(Building $building, array $payload, User $actor): array
    {
        return DB::transaction(function () use ($building, $payload, $actor) {
            $workflowEnabled = (bool) ($payload['unit_workflow_enabled'] ?? false);

            $unit = Unit::create([
                'company_id' => $building->company_id,
                'building_id' => $building->id,
                'unit_number' => $payload['unit_number'],
                'floor' => $payload['floor'] ?? null,
                'bedrooms' => $payload['bedrooms'],
                'bathrooms' => $payload['bathrooms'],
                'status' => $workflowEnabled ? 'OCCUPIED' : $payload['status'],
                'created_by' => $actor->id,
            ]);

            return $this->createLinkedRecords($unit, $payload, $actor);
        });
    }

    public function attachToExistingUnit(Unit $unit, array $payload, User $actor): array
    {
        return DB::transaction(function () use ($unit, $payload, $actor) {
            $unit->loadMissing('rents');

            if ($unit->rents->where('status', 'ACTIVE')->isNotEmpty()) {
                throw new \RuntimeException('This unit already has an active lease. Finish or terminate the current lease before creating a new workflow from Edit Unit.');
            }

            return $this->createLinkedRecords($unit, $payload, $actor);
        });
    }

    private function createLinkedRecords(Unit $unit, array $payload, User $actor): array
    {
        $workflowEnabled = (bool) ($payload['unit_workflow_enabled'] ?? false);
        $createFinancialEntry = $workflowEnabled && (bool) ($payload['create_financial_entry'] ?? false);
        $building = $unit->building()->firstOrFail();
        $accountDomain = AutoEmailGenerator::domainForSlug(optional($building->company)->slug);

        $tenant = null;
        $rent = null;
        $payment = null;

        if ($workflowEnabled) {
            $tenant = Tenant::create([
                'company_id' => $building->company_id,
                'unit_id' => $unit->id,
                'full_name' => $payload['tenant_full_name'],
                'phone' => $payload['tenant_phone'] ?? null,
                'email' => AutoEmailGenerator::generate($payload['tenant_full_name'], 'tenant', $accountDomain),
                'move_in_date' => $payload['tenant_move_in_date'],
                'active' => true,
                'created_by' => $actor->id,
            ]);

            $rent = Rent::create([
                'company_id' => $building->company_id,
                'tenant_id' => $tenant->id,
                'unit_id' => $unit->id,
                'annual_amount' => $payload['lease_annual_amount'],
                'rent_duration' => $payload['lease_rent_duration'],
                'due_day' => $this->deriveDueDay($payload['lease_start_date'], $payload['lease_rent_duration']),
                'start_date' => $payload['lease_start_date'],
                'end_date' => $payload['lease_end_date'] ?? null,
                'status' => 'ACTIVE',
                'created_by' => $actor->id,
            ]);

            if ($unit->status !== 'OCCUPIED') {
                $unit->update(['status' => 'OCCUPIED']);
            }

            if ($createFinancialEntry) {
                $paymentDate = Carbon::parse($payload['financial_payment_date'])->toDateString();

                $payment = Payment::create([
                    'company_id' => $building->company_id,
                    'rent_id' => $rent->id,
                    'payment_date' => $paymentDate,
                    'due_date' => $paymentDate,
                    'amount' => $payload['financial_amount'],
                    'payment_method' => $payload['financial_payment_method'] ?? 'BANK_TRANSFER',
                    'status' => $payload['financial_status'] ?? 'PENDING',
                    'reference' => $this->generatePaymentReference($unit, $rent),
                    'notes' => 'Auto-created from the unit onboarding workflow.',
                    'created_by' => $actor->id,
                ]);
            }
        }

        return compact('unit', 'tenant', 'rent', 'payment');
    }

    private function deriveDueDay(string $startDate, string $rentDuration): int
    {
        $start = Carbon::parse($startDate);

        return match ($rentDuration) {
            Rent::DURATION_DAILY => 1,
            Rent::DURATION_WEEKLY => (int) $start->dayOfWeekIso,
            Rent::DURATION_MONTHLY => (int) $start->day,
            default => (int) $start->day,
        };
    }

    private function generatePaymentReference(Unit $unit, Rent $rent): string
    {
        return Str::upper('unit-'.$unit->id.'-rent-'.$rent->id.'-'.now()->format('YmdHis'));
    }
}
