<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyApplication;
use App\Support\AutoEmailGenerator;
use App\Support\CompanyApplicationNotifier;
use App\Support\CompanyOnboardingService;
use App\Support\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicCompanyRegistrationController extends Controller
{
    public function __construct(
        private readonly CompanyOnboardingService $onboardingService,
        private readonly PaystackService $paystackService,
    ) {
    }

    public function create()
    {
        return view('companies.public-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'unique:companies,slug', 'unique:company_applications,slug'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'receipt_header' => ['nullable', 'string', 'max:150'],
            'email_header' => ['nullable', 'string', 'max:150'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['nullable', 'email', 'max:150', 'unique:users,email', 'unique:company_applications,admin_email'],
            'admin_phone' => ['nullable', 'string', 'max:40'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'sample_dataset' => ['nullable', 'boolean'],
            'payment_reference' => ['nullable', 'string', 'max:120', 'unique:company_applications,payment_reference'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['sample_dataset'] = $request->boolean('sample_dataset');
        $validated['email_header'] = trim((string) ($validated['email_header'] ?? '')) ?: $validated['name'];
        $validated['admin_email'] = AutoEmailGenerator::generate(
            $validated['admin_name'],
            'admin',
            AutoEmailGenerator::domainForSlug($validated['slug'])
        );
        $validated['payment_reference'] = trim((string) ($validated['payment_reference'] ?? '')) ?: CompanyApplication::generatePaymentReference();

        if (
            Company::query()->where('slug', $validated['slug'])->exists() ||
            CompanyApplication::query()->where('slug', $validated['slug'])->exists()
        ) {
            throw ValidationException::withMessages([
                'slug' => 'That workspace slug is already in use. Please choose another one.',
            ]);
        }

        if (
            CompanyApplication::query()->where('admin_email', $validated['admin_email'])->exists() ||
            \App\Models\User::query()->where('email', $validated['admin_email'])->exists()
        ) {
            throw ValidationException::withMessages([
                'admin_name' => 'That full name generated an account ID that is already in use. Adjust the full name slightly and try again.',
            ]);
        }

        [$application, $company] = DB::transaction(function () use ($validated, $request) {
            $application = CompanyApplication::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'logo_path' => $this->onboardingService->storeLogo($request->file('logo')),
                'primary_color' => $validated['primary_color'] ?? '#7c3aed',
                'address' => $validated['address'] ?? null,
                'support_email' => $validated['support_email'] ?? null,
                'support_phone' => $validated['support_phone'] ?? null,
                'receipt_header' => $validated['receipt_header'] ?? 'Official Payment Receipt',
                'email_header' => $validated['email_header'],
                'admin_name' => $validated['admin_name'],
                'admin_email' => $validated['admin_email'],
                'admin_phone' => $validated['admin_phone'] ?? null,
                'admin_password' => Hash::make($validated['admin_password']),
                'sample_dataset' => $validated['sample_dataset'],
                'payment_reference' => $validated['payment_reference'],
                'payment_amount' => 0,
                'application_status' => 'approved',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'approved_at' => now(),
            ]);

            // HOWSEY access is free: create the live workspace immediately so the
            // admin can sign in right away with no payment and no approval queue.
            $company = $this->onboardingService->createCompanyFromApplication($application);

            $application->update(['company_id' => $company->id]);

            return [$application->fresh(), $company];
        });

        CompanyApplicationNotifier::notifySuperAdmins(
            $application,
            'New company workspace created',
            $application->name.' created a new free workspace ('.$company->slug.'). Admin account: '.$application->admin_email.'.'
        );

        return redirect()
            ->route('companies.login', $company->slug)
            ->with('success', 'Your workspace is ready. Sign in with your Account ID and password to get started.');
    }

    public function paystackCallback(Request $request)
    {
        $reference = (string) $request->query('reference', '');

        $application = CompanyApplication::query()
            ->where('payment_reference', $reference)
            ->firstOrFail();

        try {
            $paystackData = $this->paystackService->verifyReference($reference);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('companies.public.application-status', $application)
                ->with('error', 'We could not verify your Paystack payment yet. Please check again shortly.');
        }

        $status = (string) data_get($paystackData, 'status', 'pending');

        $application->update([
            'paystack_transaction_status' => $status,
            'paystack_paid_at' => data_get($paystackData, 'paid_at'),
            'paystack_verified_at' => now(),
            'paystack_payload' => $paystackData,
            'application_status' => $status === 'success' ? 'pending_review' : 'pending_payment',
        ]);

        CompanyApplicationNotifier::notifySuperAdmins(
            $application->fresh(),
            $status === 'success' ? 'Company payment received' : 'Company payment needs review',
            $status === 'success'
                ? $application->name.' completed payment with reference '.$reference.'. Review and approve the application.'
                : 'Payment callback returned '.$status.' for '.$application->name.' ('.$reference.').'
        );

        return redirect()
            ->route('companies.public.application-status', $application)
            ->with('success', $status === 'success'
                ? 'Payment received. Your company application is now waiting for super admin approval.'
                : 'We received your application, but the payment is still pending.');
    }

    public function applicationStatus(CompanyApplication $companyApplication)
    {
        return view('companies.application-status', [
            'companyApplication' => $companyApplication,
            'paystackConfigured' => $this->paystackService->enabled(),
        ]);
    }

    public function showPortal(string $company)
    {
        $portalCompany = Company::query()
            ->where('slug', $company)
            ->with('settings')
            ->firstOrFail();

        return view('welcome', compact('portalCompany'));
    }
}
