@extends('layouts.app')

@section('content')
<style>
    .platform-hero {
        border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
        background:
            radial-gradient(circle at top right, rgba(var(--brand-primary-rgb), 0.14), transparent 34%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(var(--brand-primary-rgb), 0.04));
    }

    .platform-stat {
        border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
        background: rgba(255, 255, 255, 0.92);
    }

    .platform-company-card {
        border: 1px solid rgba(var(--brand-primary-rgb), 0.12);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .platform-company-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.65rem 1.4rem rgba(15, 23, 42, 0.08);
    }

    .company-logo-chip {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--brand-primary-rgb), 0.08);
        overflow: hidden;
        flex-shrink: 0;
    }

    .company-logo-chip img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<div class="d-flex justify-content-between flex-wrap align-items-center pt-2 pb-1 mb-3 border-bottom">
    <div>
        <h1 class="h3 mb-1" id="dashboard-title">HOWSEY Platform Dashboard</h1>
        <p class="text-muted mb-0">Platform control for registered companies with safe oversight counts only, without workspace financial activity.</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0 align-items-center">
        <a href="{{ route('company-applications.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-credit-card-2-front me-1"></i> Review Applications
        </a>
        <a href="{{ route('companies.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-buildings me-1"></i> Manage Companies
        </a>
        <a href="{{ route('companies.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Company
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 platform-hero mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-lg-8">
                <div class="text-uppercase small fw-bold text-muted mb-2">Platform Overview</div>
                <p class="text-muted mb-0">
                    Use it to create companies, review onboarding progress, and monitor high-level workspace counts without seeing operational financial dealings.
                </p>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="small text-uppercase fw-bold text-muted mb-2">Current Viewing Workspace</div>
                        @if($selectedCompany)
                            <div class="fw-semibold mb-1">{{ $selectedCompany->name }}</div>
                            <div class="text-muted small mb-3">Slug: {{ $selectedCompany->slug }}</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-light">Status: {{ ucfirst($selectedCompany->status) }}</span>
                                <span class="badge text-bg-light">Users: {{ $selectedCompany->users_count ?? $selectedCompany->users()->count() }}</span>
                                <span class="badge text-bg-light">Buildings: {{ $selectedCompany->buildings_count ?? $selectedCompany->buildings()->count() }}</span>
                            </div>
                        @else
                            <div class="text-muted">No company is currently selected.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="card platform-stat shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase fw-bold text-muted mb-2">Buildings</div>
                <div class="display-6 fw-bold mb-0">{{ number_format($stats['building_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card platform-stat shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase fw-bold text-muted mb-2">Managers</div>
                <div class="display-6 fw-bold mb-0">{{ number_format($stats['manager_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card platform-stat shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase fw-bold text-muted mb-2">Accountants</div>
                <div class="display-6 fw-bold mb-0">{{ number_format($stats['accountant_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card platform-stat shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase fw-bold text-muted mb-2">Tenants</div>
                <div class="display-6 fw-bold mb-0">{{ number_format($stats['tenant_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card platform-stat shadow-sm h-100">
            <div class="card-body">
                <div class="small text-uppercase fw-bold text-muted mb-2">User Log Entries</div>
                <div class="display-6 fw-bold mb-0">{{ number_format($stats['user_log_count']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1">Recent Applications</h5>
            <div class="small text-muted">New company requests waiting for payment review or approval.</div>
        </div>
        <a href="{{ route('company-applications.index') }}" class="btn btn-sm btn-outline-primary">View Applications</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($recentApplications as $application)
                <div class="col-md-6 col-xl-4">
                    <div class="card platform-company-card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">{{ $application->name }}</div>
                            <div class="small text-muted mb-2">{{ $application->admin_name }} · {{ $application->admin_email }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge text-bg-light">{{ ucfirst(str_replace('_', ' ', $application->application_status)) }}</span>
                                <span class="badge {{ $application->paystack_transaction_status === 'success' ? 'bg-success' : ($application->paystack_transaction_status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ ucfirst($application->paystack_transaction_status) }}
                                </span>
                            </div>
                            <div class="small text-muted mb-3"><code>{{ $application->payment_reference }}</code></div>
                            <a href="{{ route('company-applications.show', $application) }}" class="btn btn-sm btn-primary">Review Application</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-4 text-muted">No pending applications right now.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1">Recent Companies</h5>
            <div class="small text-muted">Newest company workspaces registered under HOWSEY.</div>
        </div>
        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-primary">View All Companies</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @forelse($recentCompanies as $company)
                @php
                    $logoPath = $company->settings?->logo_path;
                    $logoUrl = $logoPath ? url('/storage/'.$logoPath) : null;
                    $isSelectedCompany = $selectedCompany && (int) $selectedCompany->id === (int) $company->id;
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card platform-company-card h-100 shadow-sm {{ $isSelectedCompany ? 'border-primary' : '' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="company-logo-chip">
                                    @if($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $company->name }} logo">
                                    @else
                                        <i class="bi bi-buildings fs-5 text-primary"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-break">{{ $company->name }}</div>
                                    <div class="small text-muted text-break">/{{ $company->slug }}</div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge text-bg-light">{{ ucfirst($company->status) }}</span>
                                @if($isSelectedCompany)
                                    <span class="badge text-bg-primary">Currently Viewing</span>
                                @endif
                            </div>

                            <div class="small text-muted mb-3">
                                Users: <strong>{{ $company->users_count }}</strong> |
                                Buildings: <strong>{{ $company->buildings_count }}</strong>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i> Open Company
                                </a>
                                <form method="POST" action="{{ route('super-admin.view-company') }}">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $company->id }}">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i> View Workspace
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 text-muted">
                        No companies have been registered yet.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
