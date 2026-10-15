@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Workspace Subscription</div>
                            <h1 class="h3 mb-1">{{ $subscriptionCompany->name }}</h1>
                            <div class="text-muted">Annual workspace plan for all users under this company.</div>
                        </div>
                        <div class="text-lg-end">
                            @if($subscriptionCompany->hasActiveSubscription())
                                <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger-emphasis px-3 py-2">Expired</span>
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
                                    <a href="{{ $latestSubscriptionPayment->paystack_authorization_url }}" class="btn btn-outline-primary btn-sm">Continue Pending Paystack Payment</a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="alert {{ $subscriptionCompany->hasActiveSubscription() ? 'alert-info' : 'alert-warning' }} mb-4">
                        @if($subscriptionCompany->hasActiveSubscription())
                            This workspace is active. An early renewal keeps the workspace live and extends the next access window from the current expiry date.
                        @else
                            This workspace has expired. Every user under the workspace is blocked from normal access until the annual renewal payment is confirmed.
                        @endif
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @if($paystackConfigured)
                            <form method="POST" action="{{ route('companies.subscription.renew', $subscriptionCompany->slug) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    {{ $subscriptionCompany->hasActiveSubscription() ? 'Renew Early With Paystack' : 'Renew Workspace With Paystack' }}
                                </button>
                            </form>
                        @else
                            <button type="button" class="btn btn-secondary" disabled>Paystack Not Configured Yet</button>
                        @endif

                        @if($subscriptionCompany->hasActiveSubscription())
                            <a href="{{ route('companies.login', $subscriptionCompany->slug) }}" class="btn btn-outline-secondary">Go to Workspace Login</a>
                        @else
                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back Home</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
