<?php

namespace App\Support;

use App\Models\CompanyApplication;
use App\Models\CompanySubscriptionPayment;
use App\Models\Company;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class PaystackService
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function enabled(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    public function registrationFee(): float
    {
        return $this->companySubscriptionFee();
    }

    public function companySubscriptionFee(): float
    {
        return (float) config('services.paystack.company_subscription_fee', config('services.paystack.company_registration_fee', 50000));
    }

    public function subscriptionDurationMonths(): int
    {
        return max((int) config('services.paystack.subscription_duration_months', 12), 1);
    }

    public function initializeCompanyApplicationPayment(CompanyApplication $application): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Paystack is not configured yet. Add PAYSTACK_SECRET_KEY before accepting company applications.');
        }

        $response = $this->paystackClient()
            ->post('/transaction/initialize', [
                'email' => $application->support_email ?: $application->admin_email,
                'amount' => (int) round($application->payment_amount * 100),
                'reference' => $application->payment_reference,
                'callback_url' => route('companies.public.paystack.callback'),
                'metadata' => [
                    'application_id' => $application->id,
                    'company_name' => $application->name,
                    'admin_name' => $application->admin_name,
                    'admin_email' => $application->admin_email,
                ],
            ])
            ->throw()
            ->json();

        if (! data_get($response, 'status')) {
            throw new RuntimeException((string) data_get($response, 'message', 'Paystack payment initialization failed.'));
        }

        return (array) data_get($response, 'data', []);
    }

    public function initializeCompanySubscriptionRenewal(Company $company, CompanySubscriptionPayment $payment, string $email): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Paystack is not configured yet. Add PAYSTACK_SECRET_KEY before accepting renewals.');
        }

        $response = $this->paystackClient()
            ->post('/transaction/initialize', [
                'email' => $email,
                'amount' => (int) round($payment->amount * 100),
                'reference' => $payment->reference,
                'callback_url' => route('companies.subscription.paystack.callback'),
                'metadata' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'company_slug' => $company->slug,
                    'payment_id' => $payment->id,
                    'payment_type' => 'company_subscription_renewal',
                ],
            ])
            ->throw()
            ->json();

        if (! data_get($response, 'status')) {
            throw new RuntimeException((string) data_get($response, 'message', 'Paystack renewal payment initialization failed.'));
        }

        return (array) data_get($response, 'data', []);
    }

    public function verifyReference(string $reference): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Paystack is not configured yet. Add PAYSTACK_SECRET_KEY before verifying payments.');
        }

        $response = $this->paystackClient()
            ->get('/transaction/verify/'.$reference)
            ->throw()
            ->json();

        if (! data_get($response, 'status')) {
            throw new RuntimeException((string) data_get($response, 'message', 'Paystack payment verification failed.'));
        }

        return (array) data_get($response, 'data', []);
    }

    private function paystackClient()
    {
        return $this->http
            ->baseUrl('https://api.paystack.co')
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson();
    }
}
