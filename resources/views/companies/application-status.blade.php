@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Company Application Status</div>
                            <h1 class="h3 mb-1">{{ $companyApplication->name }}</h1>
                            <div class="text-muted">{{ $companyApplication->admin_name }} · {{ $companyApplication->admin_email }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="badge text-bg-light mb-2">{{ ucfirst(str_replace('_', ' ', $companyApplication->application_status)) }}</div>
                            <div class="small text-muted">Reference: {{ $companyApplication->payment_reference }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Access</div>
                                <div class="fw-semibold mb-1">Free</div>
                                <div class="small text-muted">HOWSEY access is free. There is no fee to pay.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-uppercase text-muted fw-semibold mb-2">Approval</div>
                                <div class="fw-semibold mb-1">
                                    @if($companyApplication->company_id)
                                        Approved and created
                                    @elseif($companyApplication->application_status === 'rejected')
                                        Rejected
                                    @else
                                        Waiting for super admin review
                                    @endif
                                </div>
                                @if($companyApplication->company)
                                    <div class="small text-muted">Workspace URL: <a href="{{ route('companies.portal', $companyApplication->company->slug) }}">{{ route('companies.portal', $companyApplication->company->slug) }}</a></div>
                                @else
                                    <div class="small text-muted">The company workspace is created automatically as soon as the application is submitted. HOWSEY access is free.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($companyApplication->review_notes)
                        <div class="alert alert-secondary">
                            <div class="fw-semibold mb-1">Review Note</div>
                            <div>{{ $companyApplication->review_notes }}</div>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('companies.public.create') }}" class="btn btn-primary">Create Another Workspace</a>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
