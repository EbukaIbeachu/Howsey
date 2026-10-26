@extends('layouts.app')

@section('content')
<style>
    .platform-hero {
        border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
        background:
            radial-gradient(circle at top right, rgba(var(--brand-primary-rgb), 0.14), transparent 34%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(var(--brand-primary-rgb), 0.04));
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

<x-ui.page-header
    id="dashboard-title"
    title="HOWSEY Platform Dashboard"
    subtitle="Platform control for registered companies with safe oversight counts only, without workspace financial activity."
>
    <x-slot:actions>
        <x-ui.button variant="outline-primary" size="sm" icon="bi bi-credit-card-2-front" :href="route('company-applications.index')">Review Applications</x-ui.button>
        <x-ui.button variant="primary" size="sm" icon="bi bi-buildings" :href="route('companies.index')">Manage Companies</x-ui.button>
        <x-ui.button variant="outline-primary" size="sm" icon="bi bi-plus-circle" :href="route('companies.create')">Add Company</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="shadow-sm border-0 platform-hero mb-4">
    <div class="row g-3 align-items-center">
        <div class="col-lg-8">
            <div class="text-uppercase small fw-bold text-muted mb-2">Platform Overview</div>
            <p class="text-muted mb-0">
                Use it to create companies, review onboarding progress, and monitor high-level workspace counts without seeing operational financial dealings.
            </p>
        </div>
        <div class="col-lg-4">
            <x-ui.card class="border-0 shadow-sm">
                <div class="small text-uppercase fw-bold text-muted mb-2">Current Viewing Workspace</div>
                @if($selectedCompany)
                    <div class="fw-semibold mb-1">{{ $selectedCompany->name }}</div>
                    <div class="text-muted small mb-3">Slug: {{ $selectedCompany->slug }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <x-ui.status-badge tone="neutral" :label="'Status: '.ucfirst($selectedCompany->status)" />
                        <x-ui.status-badge tone="neutral" :label="'Users: '.($selectedCompany->users_count ?? $selectedCompany->users()->count())" />
                        <x-ui.status-badge tone="neutral" :label="'Buildings: '.($selectedCompany->buildings_count ?? $selectedCompany->buildings()->count())" />
                    </div>
                @else
                    <div class="text-muted">No company is currently selected.</div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-ui.card>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-4">
        <x-ui.stat-card label="Buildings" :value="number_format($stats['building_count'])" icon="bi bi-buildings" tone="primary" />
    </div>
    <div class="col-md-6 col-xl-4">
        <x-ui.stat-card label="Managers" :value="number_format($stats['manager_count'])" icon="bi bi-person-badge" tone="info" />
    </div>
    <div class="col-md-6 col-xl-4">
        <x-ui.stat-card label="Accountants" :value="number_format($stats['accountant_count'])" icon="bi bi-calculator" tone="success" />
    </div>
    <div class="col-md-6 col-xl-4">
        <x-ui.stat-card label="Tenants" :value="number_format($stats['tenant_count'])" icon="bi bi-people-fill" tone="warning" />
    </div>
    <div class="col-md-6 col-xl-4">
        <x-ui.stat-card label="User Log Entries" :value="number_format($stats['user_log_count'])" icon="bi bi-journal-text" tone="neutral" />
    </div>
</div>

<x-ui.card
    title="Recent Applications"
    subtitle="New company requests waiting for payment review or approval."
    class="mb-4"
>
    <x-slot:actions>
        <x-ui.button variant="outline-primary" size="sm" :href="route('company-applications.index')">View Applications</x-ui.button>
    </x-slot:actions>

    <div class="row g-3">
        @forelse($recentApplications as $application)
            <div class="col-md-6 col-xl-4">
                <x-ui.card class="platform-company-card h-100 shadow-sm">
                    <div class="fw-semibold mb-1">{{ $application->name }}</div>
                    <div class="small text-muted mb-2">{{ $application->admin_name }} · {{ $application->admin_email }}</div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <x-ui.status-badge tone="neutral" :label="ucfirst(str_replace('_', ' ', $application->application_status))" />
                        <x-ui.status-badge
                            :tone="$application->paystack_transaction_status === 'success' ? 'success' : ($application->paystack_transaction_status === 'failed' ? 'danger' : 'neutral')"
                            :label="ucfirst($application->paystack_transaction_status)"
                        />
                    </div>
                    <div class="small text-muted mb-3"><code>{{ $application->payment_reference }}</code></div>
                    <x-ui.button variant="primary" size="sm" :href="route('company-applications.show', $application)">Review Application</x-ui.button>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-4 text-muted">No pending applications right now.</div>
            </div>
        @endforelse
    </div>
</x-ui.card>

<x-ui.card
    title="Recent Companies"
    subtitle="Newest company workspaces registered under HOWSEY."
    class="mb-4"
>
    <x-slot:actions>
        <x-ui.button variant="outline-primary" size="sm" :href="route('companies.index')">View All Companies</x-ui.button>
    </x-slot:actions>

    <div class="row g-3">
        @forelse($recentCompanies as $company)
            @php
                $logoPath = $company->settings?->logo_path;
                $logoUrl = $logoPath ? url('/storage/'.$logoPath) : null;
                $isSelectedCompany = $selectedCompany && (int) $selectedCompany->id === (int) $company->id;
            @endphp
            <div class="col-md-6 col-xl-4">
                <x-ui.card class="platform-company-card h-100 shadow-sm {{ $isSelectedCompany ? 'border-primary' : '' }}">
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
                        <x-ui.status-badge tone="neutral" :label="ucfirst($company->status)" />
                        @if($isSelectedCompany)
                            <x-ui.status-badge tone="primary" label="Currently Viewing" solid />
                        @endif
                    </div>

                    <div class="small text-muted mb-3">
                        Users: <strong>{{ $company->users_count }}</strong> |
                        Buildings: <strong>{{ $company->buildings_count }}</strong>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <x-ui.button variant="outline-primary" size="sm" icon="bi bi-pencil-square" :href="route('companies.edit', $company)">Open Company</x-ui.button>
                        <form method="POST" action="{{ route('super-admin.view-company') }}">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                            <x-ui.button type="submit" variant="primary" size="sm" icon="bi bi-eye">View Workspace</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-trash">Delete</x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    No companies have been registered yet.
                </div>
            </div>
        @endforelse
    </div>
</x-ui.card>
@endsection
