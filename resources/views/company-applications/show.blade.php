@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">{{ $companyApplication->name }}</h1>
        <p class="text-muted mb-0">Review the application, confirm the Paystack payment, and decide whether to create the real company workspace with its first annual subscription term.</p>
    </div>
    <a href="{{ route('company-applications.index') }}" class="btn btn-outline-secondary">Back to Applications</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Application Details</div>
            <div class="card-body">
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
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Paystack Payment</div>
            <div class="card-body">
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
                        <button type="submit" class="btn btn-outline-primary">Refresh Paystack Status</button>
                    </form>
                    @if($companyApplication->paystack_authorization_url && $companyApplication->paystack_transaction_status !== 'success')
                        <a href="{{ $companyApplication->paystack_authorization_url }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">Open Paystack Checkout</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Decision</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Application Status</div>
                    <span class="badge text-bg-light">{{ ucfirst(str_replace('_', ' ', $companyApplication->application_status)) }}</span>
                </div>

                @if($companyApplication->company)
                    <div class="alert alert-success mb-0">
                        This application has already been approved. The live workspace is available at
                        <a href="{{ route('companies.portal', $companyApplication->company->slug) }}">{{ route('companies.portal', $companyApplication->company->slug) }}</a>.
                    </div>
                @else
                    <form method="POST" action="{{ route('company-applications.approve', $companyApplication) }}" class="mb-3">
                        @csrf
                        <label class="form-label">Approval Note</label>
                        <textarea name="review_notes" class="form-control mb-3" rows="3" placeholder="Optional approval context for this application.">{{ old('review_notes', $companyApplication->review_notes) }}</textarea>
                        <button type="submit" class="btn btn-success w-100" {{ $companyApplication->paystack_transaction_status !== 'success' ? 'disabled' : '' }}>
                            Approve and Create Company
                        </button>
                        @if($companyApplication->paystack_transaction_status !== 'success')
                            <div class="form-text">Approve only after Paystack reports a successful payment.</div>
                        @endif
                    </form>

                    <form method="POST" action="{{ route('company-applications.reject', $companyApplication) }}">
                        @csrf
                        <label class="form-label">Rejection Note</label>
                        <textarea name="review_notes" class="form-control mb-3" rows="3" placeholder="Explain why this request is being rejected." required>{{ old('review_notes') }}</textarea>
                        <button type="submit" class="btn btn-outline-danger w-100">Reject Application</button>
                    </form>
                @endif
            </div>
        </div>

        @if($companyApplication->logo_path)
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Uploaded Logo</div>
                <div class="card-body text-center">
                    <img src="{{ url('/storage/'.$companyApplication->logo_path) }}" alt="{{ $companyApplication->name }} logo" class="img-fluid rounded-3 border">
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
