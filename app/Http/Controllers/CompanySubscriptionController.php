<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscriptionPayment;
use App\Support\PaystackService;
use Illuminate\Http\Request;

class CompanySubscriptionController extends Controller
{
    public function __construct(private readonly PaystackService $paystackService)
    {
    }

    public function show(string $company)
    {
        $workspace = $this->resolveCompany($company);

        return view('companies.subscription-status', [
            'subscriptionCompany' => $workspace,
            'subscriptionFee' => $workspace->subscriptionFeeAmount(),
            'subscriptionDurationMonths' => $this->paystackService->subscriptionDurationMonths(),
            'paystackConfigured' => $this->paystackService->enabled(),
            'latestSubscriptionPayment' => $workspace->subscriptionPayments()->latest()->first(),
        ]);
    }

    public function initiateRenewal(Request $request, string $company)
    {
        $workspace = $this->resolveCompany($company);

        $payment = CompanySubscriptionPayment::create([
            'company_id' => $workspace->id,
            'reference' => CompanySubscriptionPayment::generateReference(),
            'amount' => $workspace->subscriptionFeeAmount(),
            'transaction_status' => 'pending',
        ]);

        try {
            $paystackData = $this->paystackService->initializeCompanySubscriptionRenewal(
                $workspace,
                $payment,
                $this->billingEmail($workspace)
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('companies.subscription.show', $workspace->slug)
                ->with('error', $exception->getMessage());
        }

        $payment->update([
            'paystack_access_code' => data_get($paystackData, 'access_code'),
            'paystack_authorization_url' => data_get($paystackData, 'authorization_url'),
        ]);

        return redirect()->away((string) data_get($paystackData, 'authorization_url'));
    }

    public function paystackCallback(Request $request)
    {
        $reference = (string) $request->query('reference', '');

        $payment = CompanySubscriptionPayment::query()
            ->with('company')
            ->where('reference', $reference)
            ->firstOrFail();

        try {
            $paystackData = $this->paystackService->verifyReference($reference);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('companies.subscription.show', $payment->company->slug)
                ->with('error', 'We could not verify the renewal payment yet. Please try again shortly.');
        }

        $status = (string) data_get($paystackData, 'status', 'pending');
        $wasSuccessful = $payment->transaction_status === 'success';

        $payment->update([
            'transaction_status' => $status,
            'paid_at' => $status === 'success'
                ? data_get($paystackData, 'paid_at', now())
                : $payment->paid_at,
            'verified_at' => now(),
            'payload' => $paystackData,
        ]);

        if ($status === 'success' && ! $wasSuccessful) {
            $periodStartsAt = $payment->company->nextRenewalStartAt();
            $periodEndsAt = $periodStartsAt->copy()->addMonths($this->paystackService->subscriptionDurationMonths());

            $payment->update([
                'period_starts_at' => $periodStartsAt,
                'period_ends_at' => $periodEndsAt,
            ]);

            $payment->company->update([
                'subscription_expires_at' => $periodEndsAt,
                'subscription_fee' => $payment->amount,
            ]);
        }

        return redirect()
            ->route('companies.subscription.show', $payment->company->slug)
            ->with('success', $status === 'success'
                ? 'Workspace renewal payment confirmed. Access has been restored for the company.'
                : 'Renewal payment is still pending confirmation.');
    }

    private function resolveCompany(string $company): Company
    {
        return Company::query()
            ->where('slug', $company)
            ->with('settings')
            ->firstOrFail();
    }

    private function billingEmail(Company $company): string
    {
        $supportEmail = trim((string) optional($company->settings)->support_email);
        if ($supportEmail !== '') {
            return $supportEmail;
        }

        $adminEmail = (string) $company->users()
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })
            ->orderBy('id')
            ->value('email');

        if (trim($adminEmail) !== '') {
            return $adminEmail;
        }

        return (string) (config('mail.from.address') ?: 'support@howsey.site');
    }
}
