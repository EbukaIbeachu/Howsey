<?php

namespace App\Http\Controllers;

use App\Models\CompanyApplication;
use App\Support\CompanyApplicationNotifier;
use App\Support\CompanyOnboardingService;
use App\Support\PaystackService;
use Illuminate\Http\Request;

class CompanyApplicationController extends Controller
{
    public function __construct(
        private readonly CompanyOnboardingService $onboardingService,
        private readonly PaystackService $paystackService,
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $applications = CompanyApplication::query()
            ->with(['approver', 'company'])
            ->latest()
            ->paginate(20);

        return view('company-applications.index', compact('applications'));
    }

    public function show(CompanyApplication $companyApplication)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('company-applications.show', compact('companyApplication'));
    }

    public function approve(Request $request, CompanyApplication $companyApplication)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($companyApplication->company_id) {
            return back()->with('error', 'This application has already been approved.');
        }

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string'],
        ]);

        $company = $this->onboardingService->createCompanyFromApplication($companyApplication);

        $companyApplication->update([
            'application_status' => 'approved',
            'approved_at' => now(),
            'reviewed_at' => now(),
            'approved_by' => $request->user()->id,
            'review_notes' => $validated['review_notes'] ?? null,
            'company_id' => $company->id,
        ]);

        return redirect()
            ->route('company-applications.show', $companyApplication)
            ->with('success', 'Application approved and company workspace created successfully.');
    }

    public function reject(Request $request, CompanyApplication $companyApplication)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        $companyApplication->update([
            'application_status' => 'rejected',
            'reviewed_at' => now(),
            'approved_by' => $request->user()->id,
            'review_notes' => $validated['review_notes'],
        ]);

        return redirect()
            ->route('company-applications.show', $companyApplication)
            ->with('success', 'Application rejected.');
    }

    public function refreshPaymentStatus(Request $request, CompanyApplication $companyApplication)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        try {
            $paystackData = $this->paystackService->verifyReference($companyApplication->payment_reference);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Unable to verify this Paystack payment right now.');
        }

        $status = (string) data_get($paystackData, 'status', 'pending');

        $companyApplication->update([
            'paystack_transaction_status' => $status,
            'paystack_paid_at' => $status === 'success' ? data_get($paystackData, 'paid_at', now()) : $companyApplication->paystack_paid_at,
            'paystack_verified_at' => now(),
            'paystack_payload' => $paystackData,
            'application_status' => $status === 'success' && $companyApplication->application_status === 'pending_payment'
                ? 'pending_review'
                : $companyApplication->application_status,
        ]);

        CompanyApplicationNotifier::notifySuperAdmins(
            $companyApplication->fresh(),
            'Company payment verified',
            'Payment confirmation was refreshed for '.$companyApplication->name.' ('.$companyApplication->payment_reference.').'
        );

        return back()->with('success', 'Payment status refreshed from Paystack.');
    }
}
