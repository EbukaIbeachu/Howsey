<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery as AppPreventRequestForgery;
use App\Models\Building;
use App\Models\Company;
use App\Models\PaymentClaim;
use App\Models\Rent;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as FrameworkPreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Upgrade smoke coverage for the areas the pre-existing feature suite does not
 * exercise: authentication, dashboard access, request-forgery wiring, route
 * registration, the scheduler, private-storage containment and file uploads.
 */
class Laravel13UpgradeSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $suffix): Company
    {
        return Company::create([
            'name' => 'Smoke Company '.$suffix,
            'slug' => 'smoke-company-'.$suffix,
            'status' => 'active',
            'onboarded_at' => now(),
            'subscription_expires_at' => now()->addYear(),
        ]);
    }

    private function makeUser(Company $company, string $roleName, string $email): User
    {
        return User::create([
            'company_id' => $company->id,
            'name' => 'Smoke '.$roleName,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'is_active' => true,
        ]);
    }

    private function makeBuilding(Company $company): Building
    {
        return Building::create([
            'company_id' => $company->id,
            'name' => 'Smoke Towers',
            'address_line1' => '1 Smoke Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'postal_code' => '100001',
            'total_units' => 4,
            'total_floors' => 2,
        ]);
    }

    public function test_login_page_renders_for_an_anonymous_visitor(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_company_administrator_can_authenticate_and_reach_the_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $suffix = Str::lower(Str::random(6));
        $company = $this->makeCompany($suffix);
        $admin = $this->makeUser($company, 'admin', 'smoke-admin+'.$suffix.'@example.com');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_wrong_password_is_rejected_and_the_guest_stays_anonymous(): void
    {
        $this->seed(DatabaseSeeder::class);

        $suffix = Str::lower(Str::random(6));
        $company = $this->makeCompany($suffix);
        $admin = $this->makeUser($company, 'admin', 'smoke-bad+'.$suffix.'@example.com');

        $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'not-the-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $this->seed(DatabaseSeeder::class);

        $suffix = Str::lower(Str::random(6));
        $company = $this->makeCompany($suffix);
        $admin = $this->makeUser($company, 'admin', 'smoke-logout+'.$suffix.'@example.com');

        $this->actingAs($admin)->post(route('logout'))->assertRedirect();

        $this->assertGuest();
    }

    public function test_web_group_uses_the_laravel_13_request_forgery_middleware(): void
    {
        $kernel = app(HttpKernel::class);

        $property = new \ReflectionProperty($kernel, 'middlewareGroups');
        $property->setAccessible(true);
        $groups = $property->getValue($kernel);

        $this->assertContains(AppPreventRequestForgery::class, $groups['web']);
        $this->assertTrue(
            is_subclass_of(AppPreventRequestForgery::class, FrameworkPreventRequestForgery::class),
            'App\\Http\\Middleware\\PreventRequestForgery must extend the Laravel 13 framework middleware.'
        );
        $this->assertFalse(
            class_exists(\App\Http\Middleware\VerifyCsrfToken::class),
            'The removed Laravel 10 VerifyCsrfToken middleware must not be referenced.'
        );

        $defaults = (new \ReflectionClass(AppPreventRequestForgery::class))->getDefaultProperties();
        $this->assertContains('bot/message', $defaults['except']);
    }

    public function test_critical_named_routes_are_still_registered(): void
    {
        $expected = [
            'login',
            'logout',
            'dashboard',
            'buildings.index',
            'buildings.show',
            'units.store',
            'tenants.index',
            'payments.index',
            'payments.pay',
            'payments.proof',
            'payments.claim',
            'payment-claims.index',
            'company-settings.edit',
            'accounting.index',
        ];

        foreach ($expected as $name) {
            $this->assertTrue(Route::has($name), "Expected named route [{$name}] to be registered.");
        }
    }

    public function test_rent_reminder_command_is_registered_on_the_scheduler(): void
    {
        $events = app(Schedule::class)->events();

        $match = collect($events)->contains(function ($event) {
            $command = (string) ($event->command ?? '');
            $description = (string) ($event->description ?? '');

            return str_contains($command, 'rent:send-reminders')
                || str_contains($description, 'rent:send-reminders');
        });

        $this->assertTrue($match, 'The rent:send-reminders command is not registered on the scheduler.');
    }

    public function test_private_disk_is_contained_outside_the_public_web_root(): void
    {
        $this->assertSame(storage_path('app/private'), config('filesystems.disks.private.root'));
        $this->assertStringNotContainsString(
            DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR,
            (string) config('filesystems.disks.private.root')
        );
    }

    public function test_tenant_can_submit_a_payment_claim_uploading_proof_to_private_storage(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('private');

        $suffix = Str::lower(Str::random(6));
        $company = $this->makeCompany($suffix);
        $tenantUser = $this->makeUser($company, 'tenant', 'smoke-tenant+'.$suffix.'@example.com');
        $building = $this->makeBuilding($company);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'S-1',
            'floor' => '1',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'status' => 'OCCUPIED',
        ]);

        $tenant = Tenant::create([
            'company_id' => $company->id,
            'user_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'full_name' => 'Smoke Tenant',
            'move_in_date' => now()->toDateString(),
            'active' => true,
        ]);

        $rent = Rent::create([
            'company_id' => $company->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'annual_amount' => 450000,
            'rent_duration' => 'monthly',
            'due_day' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($tenantUser)->post(route('payments.claim', $rent), [
            'amount' => 37500,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'BANK_TRANSFER',
            'reference' => 'SMOKE-'.$suffix,
            'note' => 'Paid at the bank branch.',
            'proof' => UploadedFile::fake()->create('proof.pdf', 64, 'application/pdf'),
        ]);

        $response->assertRedirect(route('payments.pay', $rent));

        $claim = PaymentClaim::where('rent_id', $rent->id)->first();
        $this->assertNotNull($claim, 'The payment claim was not persisted.');
        $this->assertSame('PENDING', $claim->status);
        $this->assertNotNull($claim->proof_path, 'The uploaded proof was not stored.');
        Storage::disk('private')->assertExists($claim->proof_path);
    }
}
