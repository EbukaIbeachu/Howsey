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
                            <div class="small text-uppercase text-muted fw-semibold mb-2">Workspace Access</div>
                            <h1 class="h3 mb-1">{{ $subscriptionCompany->name }}</h1>
                            <div class="text-muted">HOWSEY access is free for every user under this company.</div>
                        </div>
                        <div class="text-lg-end">
                            <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">Active</span>
                            <div class="small text-muted mt-2">Workspace: {{ route('companies.portal', $subscriptionCompany->slug) }}</div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4">
                        This workspace has free, unlimited access. There is no annual fee to pay and no renewal is required.
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('companies.login', $subscriptionCompany->slug) }}" class="btn btn-primary">Go to Workspace Login</a>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
