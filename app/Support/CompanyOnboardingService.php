<?php

namespace App\Support;

use App\Models\Building;
use App\Models\Company;
use App\Models\CompanyApplication;
use App\Models\CompanySetting;
use App\Models\DocumentTemplate;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyOnboardingService
{
    public function createCompany(array $payload): Company
    {
        return DB::transaction(function () use ($payload) {
            $emailHeader = trim((string) ($payload['email_header'] ?? '')) ?: $payload['name'];
            $subscriptionStartedAt = now();
            $subscriptionDurationMonths = max((int) config('services.paystack.subscription_duration_months', 12), 1);

            $company = Company::create([
                'name' => $payload['name'],
                'slug' => $payload['slug'] ?: Str::slug($payload['name']),
                'status' => 'active',
                'is_demo' => (bool) ($payload['sample_dataset'] ?? false),
                'onboarded_at' => now(),
                'subscription_started_at' => $subscriptionStartedAt,
                'subscription_expires_at' => $subscriptionStartedAt->copy()->addMonths($subscriptionDurationMonths),
                'subscription_fee' => (float) config('services.paystack.company_subscription_fee', config('services.paystack.company_registration_fee', 50000)),
            ]);

            CompanySetting::create([
                'company_id' => $company->id,
                'logo_path' => $payload['logo_path'] ?? $this->storeLogo($payload['logo'] ?? null),
                'primary_color' => $payload['primary_color'] ?? '#7c3aed',
                'address' => $payload['address'] ?? null,
                'support_email' => $payload['support_email'] ?? null,
                'support_phone' => $payload['support_phone'] ?? null,
                'receipt_header' => $payload['receipt_header'] ?? 'Official Payment Receipt',
                'email_header' => $emailHeader,
            ]);

            NotificationSetting::create([
                'company_id' => $company->id,
                'email_enabled' => true,
                'send_overdue_rent_reminders' => true,
                'send_lease_expiry_reminders' => true,
                'lease_expiry_days_notice' => 30,
                'from_name' => $company->name,
                'from_email' => $payload['support_email'] ?? env('MAIL_FROM_ADDRESS'),
            ]);

            $adminRole = Role::where('name', 'admin')->firstOrFail();
            $adminAccountId = AutoEmailGenerator::generate(
                $payload['admin_name'],
                'admin',
                AutoEmailGenerator::domainForCompany($company)
            );

            User::create([
                'company_id' => $company->id,
                'name' => $payload['admin_name'],
                'email' => $payload['admin_email'] ?? $adminAccountId,
                'phone' => $payload['admin_phone'] ?? null,
                'password' => $payload['admin_password_hash'] ?? Hash::make($payload['admin_password']),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]);

            $this->seedTemplates($company);

            if (! empty($payload['sample_dataset'])) {
                $this->seedSampleDataset($company);
            }

            $this->importCsv($company, $payload['buildings_csv'] ?? null, 'buildings');
            $this->importCsv($company, $payload['units_csv'] ?? null, 'units');
            $this->importCsv($company, $payload['tenants_csv'] ?? null, 'tenants');

            return $company;
        });
    }

    public function createCompanyFromApplication(CompanyApplication $application): Company
    {
        return $this->createCompany([
            'name' => $application->name,
            'slug' => $application->slug,
            'logo_path' => $application->logo_path,
            'primary_color' => $application->primary_color,
            'address' => $application->address,
            'support_email' => $application->support_email,
            'support_phone' => $application->support_phone,
            'receipt_header' => $application->receipt_header,
            'email_header' => $application->email_header,
            'admin_name' => $application->admin_name,
            'admin_email' => $application->admin_email,
            'admin_phone' => $application->admin_phone,
            'admin_password_hash' => $application->admin_password,
            'sample_dataset' => $application->sample_dataset,
        ]);
    }

    private function seedTemplates(Company $company): void
    {
        NotificationTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'overdue_rent', 'channel' => 'email'],
            ['subject' => 'Rent reminder for {{tenant_name}}', 'body' => '<p>Hello {{tenant_name}}, your rent is overdue by {{balance}}.</p>']
        );

        NotificationTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'lease_expiry', 'channel' => 'email'],
            ['subject' => 'Lease expiry notice for {{tenant_name}}', 'body' => '<p>Hello {{tenant_name}}, your lease expires on {{lease_end_date}}.</p>']
        );

        DocumentTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'rent_receipt'],
            ['subject' => 'Rent Receipt', 'body_html' => '<h2>{{company_name}}</h2><p>{{receipt_header}}</p>']
        );

        DocumentTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'arrears_notice'],
            ['subject' => 'Arrears Notice', 'body_html' => '<h2>{{company_name}}</h2><p>Outstanding balance: {{balance}}</p>']
        );

        DocumentTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => 'lease_renewal_notice'],
            ['subject' => 'Lease Renewal Notice', 'body_html' => '<h2>{{company_name}}</h2><p>Lease end date: {{lease_end_date}}</p>']
        );
    }

    private function seedSampleDataset(Company $company): void
    {
        $building = Building::create([
            'company_id' => $company->id,
            'name' => $company->name.' Residences',
            'address_line1' => '1 Demo Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '100001',
            'total_units' => 4,
            'total_floors' => 2,
        ]);

        foreach (['A1', 'A2', 'B1', 'B2'] as $index => $unitNumber) {
            Unit::create([
                'company_id' => $company->id,
                'building_id' => $building->id,
                'unit_number' => $unitNumber,
                'floor' => $index < 2 ? 1 : 2,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'status' => $index === 0 ? 'OCCUPIED' : 'AVAILABLE',
            ]);
        }
    }

    private function importCsv(Company $company, mixed $file, string $type): void
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return;
        }

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (count($rows) < 2) {
            return;
        }

        $headers = array_map(fn ($value) => Str::snake(trim((string) $value)), array_shift($rows));

        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            if (! is_array($data)) {
                continue;
            }

            if ($type === 'buildings') {
                Building::firstOrCreate(
                    ['company_id' => $company->id, 'name' => trim((string) ($data['name'] ?? ''))],
                    [
                        'address_line1' => $data['address_line1'] ?? 'Unknown address',
                        'address_line2' => $data['address_line2'] ?? null,
                        'city' => $data['city'] ?? 'Unknown city',
                        'state' => $data['state'] ?? 'Unknown state',
                        'postal_code' => $data['postal_code'] ?? '000000',
                        'total_units' => (int) ($data['total_units'] ?? 1),
                        'total_floors' => (int) ($data['total_floors'] ?? 1),
                    ]
                );
            }

            if ($type === 'units') {
                $building = Building::where('company_id', $company->id)
                    ->where('name', $data['building_name'] ?? '')
                    ->first();

                if (! $building) {
                    continue;
                }

                Unit::firstOrCreate(
                    ['company_id' => $company->id, 'building_id' => $building->id, 'unit_number' => $data['unit_number'] ?? ''],
                    [
                        'floor' => (int) ($data['floor'] ?? 1),
                        'bedrooms' => (int) ($data['bedrooms'] ?? 1),
                        'bathrooms' => (int) ($data['bathrooms'] ?? 1),
                        'status' => $data['status'] ?? 'AVAILABLE',
                    ]
                );
            }

            if ($type === 'tenants') {
                $building = Building::where('company_id', $company->id)
                    ->where('name', $data['building_name'] ?? '')
                    ->first();

                $unit = $building
                    ? Unit::where('company_id', $company->id)
                        ->where('building_id', $building->id)
                        ->where('unit_number', $data['unit_number'] ?? '')
                        ->first()
                    : null;

                if (! $unit) {
                    continue;
                }

                Tenant::firstOrCreate(
                    ['company_id' => $company->id, 'email' => ($data['email'] ?? null) ?: AutoEmailGenerator::generate(
                        (string) ($data['full_name'] ?? 'Unknown Tenant'),
                        'tenant',
                        AutoEmailGenerator::domainForCompany($company)
                    )],
                    [
                        'unit_id' => $unit->id,
                        'full_name' => $data['full_name'] ?? 'Unknown Tenant',
                        'phone' => $data['phone'] ?? null,
                        'move_in_date' => $data['move_in_date'] ?? now()->toDateString(),
                        'move_out_date' => $data['move_out_date'] ?? null,
                        'active' => filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOL),
                    ]
                );
            }
        }
    }

    public function storeLogo(mixed $logo): ?string
    {
        if (! $logo instanceof UploadedFile || ! $logo->isValid()) {
            return null;
        }

        return Storage::disk('public')->putFile('company-logos', $logo);
    }
}
