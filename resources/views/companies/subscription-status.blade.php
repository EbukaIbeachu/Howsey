@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <x-ui.card class="shadow-sm">
                <div class="p-3 p-lg-4">

                    @if (session('success'))
                        <x-ui.alert tone="success" class="mb-3">{{ session('success') }}</x-ui.alert>
                    @endif

                    @if (session('error'))
                        <x-ui.alert tone="danger" class="mb-3">{{ session('error') }}</x-ui.alert>
                    @endif

                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Workspace Subscription</div>
                            <h1 class="h3 mb-1">{{ $subscriptionCompany->name }}</h1>
                            <div class="text-muted">Annual workspace plan for all users under this company.</div>
                        </div>
                        <div class="text-lg-end">
                            @if($subscriptionCompany->hasActiveSubscription())
                                <x-ui.status-badge tone="success" label="Active" />
                            @else
                                <x-ui.status-badge tone="danger" label="Expired" />
                            @endif
                            <div class="small text-muted mt-2">Workspace: {{ route('companies.portal', $subscriptionCompany->slug) }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Annual Fee</div>
                                <div class="fw-semibold">NGN {{ number_format((float) $subscriptionFee, 2) }}</div>
                                <div class="small text-muted mt-1">Charged through Paystack.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Current Access Ends</div>
                                <div class="fw-semibold">
                                    {{ optional($subscriptionCompany->subscription_expires_at)->format('M d, Y h:i A') ?: 'Not set' }}
                                </div>
                                <div class="small text-muted mt-1">
                                    @if($subscriptionCompany->hasActiveSubscription())
                                        Renewing now extends from the current expiry date.
                                    @else
                                        Access is paused for every non-super-admin user until renewal is confirmed.
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Renewal Term</div>
                                <div class="fw-semibold">{{ $subscriptionDurationMonths }} months</div>
                                <div class="small text-muted mt-1">Each confirmed renewal adds one full annual term.</div>
                            </div>
                        </div>
                    </div>

                    @if($latestSubscriptionPayment)
                        <div class="border rounded-3 p-3 mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                <div>
                                    <div class="small text-uppercase text-muted fw-semibold mb-1">Latest Renewal Payment</div>
                                    <div class="fw-semibold">{{ ucfirst($latestSubscriptionPayment->transaction_status) }}</div>
                                    <div class="small text-muted">Reference: {{ $latestSubscriptionPayment->reference }}</div>
                                </div>
                                <div class="text-md-end small text-muted">
                                    @if($latestSubscriptionPayment->paid_at)
                                        <div>Paid: {{ $latestSubscriptionPayment->paid_at->format('M d, Y h:i A') }}</div>
                                    @endif
                                    @if($latestSubscriptionPayment->period_ends_at)
                                        <div>Extended to: {{ $latestSubscriptionPayment->period_ends_at->format('M d, Y h:i A') }}</div>
                                    @endif
                                </div>
                            </div>

                            @if($paystackConfigured && $latestSubscriptionPayment->paystack_authorization_url && $latestSubscriptionPayment->transaction_status !== 'success')
                                <div class="mt-3">
                                    <x-ui.button variant="outline-primary" size="sm" :href="$latestSubscriptionPayment->paystack_authorization_url">Continue Pending Paystack Payment</x-ui.button>
                                </div>
                            @endif
                        </div>
                    @endif

                    <x-ui.alert :tone="$subscriptionCompany->hasActiveSubscription() ? 'info' : 'warning'" class="mb-4">
                        @if($subscriptionCompany->hasActiveSubscription())
                            This workspace is active. An early renewal keeps the workspace live and extends the next access window from the current expiry date.
                        @else
                            This workspace has expired. Every user under the workspace is blocked from normal access until the annual renewal payment is confirmed.
                        @endif
                    </x-ui.alert>

                    <div class="d-flex flex-wrap gap-2">
                        @if($paystackConfigured)
                            <form method="POST" action="{{ route('companies.subscription.renew', $subscriptionCompany->slug) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary">
                                    {{ $subscriptionCompany->hasActiveSubscription() ? 'Renew Early With Paystack' : 'Renew Workspace With Paystack' }}
                                </x-ui.button>
                            </form>
                        @else
                            <x-ui.button type="button" variant="secondary" disabled>Paystack Not Configured Yet</x-ui.button>
                        @endif

                        @if($subscriptionCompany->hasActiveSubscription())
                            <x-ui.button variant="outline-secondary" :href="route('companies.login', $subscriptionCompany->slug)">Go to Workspace Login</x-ui.button>
                        @else
                            <x-ui.button variant="outline-secondary" :href="url('/')">Back Home</x-ui.button>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
