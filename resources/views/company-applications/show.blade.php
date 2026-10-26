@extends('layouts.app')

@section('content')
<x-ui.page-header
    :title="$companyApplication->name"
    subtitle="Review the application, confirm the Paystack payment, and decide whether to create the real company workspace with its first annual subscription term.">
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" :href="route('company-applications.index')">Back to Applications</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-ui.card title="Application Details" class="mb-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Company Name</dt>
                <dd class="col-sm-8">{{ $companyApplication->name }}</dd>
                <dt class="col-sm-4">Workspace Slug</dt>
                <dd class="col-sm-8">{{ $companyApplication->slug }}</dd>
                <dt class="col-sm-4">Admin</dt>
                <dd class="col-sm-8">{{ $companyApplication->admin_name }}<br><span class="text-muted">{{ $companyApplication->admin_email }}</span></dd>
                <dt class="col-sm-4">Support</dt>
                <dd class="col-sm-8">{{ $companyApplication->support_email ?: 'Not provided' }} / {{ $companyApplication->support_phone ?: 'Not provided' }}</dd>
                <dt class="col-sm-4">Email Header</dt>
                <dd class="col-sm-8">{{ $companyApplication->email_header ?: 'Not provided' }}</dd>
                <dt class="col-sm-4">Receipt Header</dt>
                <dd class="col-sm-8">{{ $companyApplication->receipt_header ?: 'Not provided' }}</dd>
                <dt class="col-sm-4">Address</dt>
                <dd class="col-sm-8">{{ $companyApplication->address ?: 'Not provided' }}</dd>
            </dl>
        </x-ui.card>

        <x-ui.card title="Paystack Payment">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Reference</div>
                        <div class="fw-semibold"><code>{{ $companyApplication->payment_reference }}</code></div>
                        <div class="small text-muted mt-2">Annual workspace fee: NGN {{ number_format((float) $companyApplication->payment_amount, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="small text-uppercase text-muted fw-semibold mb-2">Status</div>
                        <div class="fw-semibold mb-1">{{ ucfirst($companyApplication->paystack_transaction_status) }}</div>
                        @if($companyApplication->paystack_paid_at)
                            <div class="small text-muted">Paid at {{ $companyApplication->paystack_paid_at->format('M d, Y h:i A') }}</div>
                        @endif
                        @if($companyApplication->paystack_verified_at)
                            <div class="small text-muted">Last checked {{ $companyApplication->paystack_verified_at->diffForHumans() }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <form method="POST" action="{{ route('company-applications.refresh-payment', $companyApplication) }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline-primary">Refresh Paystack Status</x-ui.button>
                </form>
                @if($companyApplication->paystack_authorization_url && $companyApplication->paystack_transaction_status !== 'success')
                    <x-ui.button variant="outline-secondary" :href="$companyApplication->paystack_authorization_url" target="_blank" rel="noopener">Open Paystack Checkout</x-ui.button>
                @endif
            </div>
        </x-ui.card>
    </div>

    <div class="col-lg-5">
        <x-ui.card title="Decision" class="mb-4">
            <div class="mb-3">
                <div class="small text-uppercase text-muted fw-semibold mb-2">Application Status</div>
                <x-ui.status-badge tone="neutral" :label="ucfirst(str_replace('_', ' ', $companyApplication->application_status))" />
            </div>

            @if($companyApplication->company)
                <x-ui.alert tone="success" class="mb-0">
                    This application has already been approved. The live workspace is available at
                    <a href="{{ route('companies.portal', $companyApplication->company->slug) }}">{{ route('companies.portal', $companyApplication->company->slug) }}</a>.
                </x-ui.alert>
            @else
                <form method="POST" action="{{ route('company-applications.approve', $companyApplication) }}" class="mb-3">
                    @csrf
                    <x-ui.form.textarea name="review_notes" label="Approval Note" :rows="3"
                        placeholder="Optional approval context for this application."
                        :value="$companyApplication->review_notes" />
                    <x-ui.button type="submit" variant="success" block
                        :disabled="$companyApplication->paystack_transaction_status !== 'success'">Approve and Create Company</x-ui.button>
                    @if($companyApplication->paystack_transaction_status !== 'success')
                        <div class="form-text">Approve only after Paystack reports a successful payment.</div>
                    @endif
                </form>

                <form method="POST" action="{{ route('company-applications.reject', $companyApplication) }}">
                    @csrf
                    <x-ui.form.textarea name="review_notes" label="Rejection Note" :rows="3" required
                        placeholder="Explain why this request is being rejected." />
                    <x-ui.button type="submit" variant="outline-danger" block>Reject Application</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        @if($companyApplication->logo_path)
            <x-ui.card title="Uploaded Logo">
                <div class="text-center">
                    <img src="{{ url('/storage/'.$companyApplication->logo_path) }}" alt="{{ $companyApplication->name }} logo" class="img-fluid rounded-3 border">
                </div>
            </x-ui.card>
        @endif
    </div>
</div>
@endsection
