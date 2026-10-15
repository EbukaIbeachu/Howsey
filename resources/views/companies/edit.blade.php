@extends('layouts.app')

@section('content')
<style>
    .company-logo-photo {
        width: 100%;
        max-width: 220px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 1.25rem;
        border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.14);
        background: linear-gradient(180deg, rgba(var(--brand-primary-rgb), 0.08), rgba(255, 255, 255, 0.94));
    }
</style>
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-1">{{ $company->name }}</h1>
    <p class="text-muted mb-0">Review workspace status, white-label identity, and onboarding readiness.</p>
</div>

@if(session('created_admin_account_id'))
    <div class="alert alert-success shadow-sm">
        <div class="fw-semibold mb-2">Workspace created and ready for sign-in</div>
        <div class="mb-1"><strong>First Admin:</strong> {{ session('created_admin_name') ?: 'New admin user' }}</div>
        <div class="mb-1"><strong>Admin Account ID:</strong> <code>{{ session('created_admin_account_id') }}</code></div>
        <div class="mb-1"><strong>Branded Login URL:</strong> <a href="{{ session('created_company_login_url') }}">{{ session('created_company_login_url') }}</a></div>
        <div class="small text-muted mt-2">Use the password that was entered during company creation. The generated Account ID above is the login username for this new workspace admin.</div>
    </div>
@endif

<form method="POST" action="{{ route('companies.update', $company) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">Company Profile</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Workspace Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $company->slug) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Admin Status</label>
                        <div class="form-control bg-light">{{ $company->adminStatusLabel() }}</div>
                        <div class="form-text">This is the company-admin-controlled workspace status from the workspace settings page.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_demo" value="1" id="is_demo" {{ old('is_demo', $company->is_demo) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_demo">Demo Workspace</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <hr class="my-1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Platform Workspace Access</label>
                        <select name="platform_status" class="form-select">
                            <option value="active" {{ old('platform_status', $company->platform_status ?: 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('platform_status', $company->platform_status ?: 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="form-text">This is the super-admin lock. If you set it to inactive, the company admin cannot reverse it from the workspace side.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Platform Action Note</label>
                        <textarea name="platform_status_note" rows="3" class="form-control" placeholder="Optional note for deactivation or reactivation">{{ old('platform_status_note', $company->platform_status_note) }}</textarea>
                        <div class="form-text">Use this note to explain why the platform lock was changed.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">Workspace Snapshot</div>
                <div class="card-body">
                    @if($company->settings?->logo_path)
                        <div class="mb-3">
                            <img src="{{ url('/storage/'.$company->settings->logo_path) }}" alt="{{ $company->name }} logo" class="company-logo-photo">
                            <div class="small text-muted mt-2">Uploaded logo preview</div>
                        </div>
                    @endif
                    <div class="mb-2"><strong>Company Portal:</strong> <a href="{{ route('companies.portal', $company->slug) }}">{{ route('companies.portal', $company->slug) }}</a></div>
                    <div class="mb-2"><strong>Branded Login:</strong> <a href="{{ route('companies.login', $company->slug) }}">{{ route('companies.login', $company->slug) }}</a></div>
                    <div class="mb-2"><strong>Platform Workspace Access:</strong> {{ $company->platformStatusLabel() }}</div>
                    <div class="mb-2"><strong>Company Admin Status:</strong> {{ $company->adminStatusLabel() }}</div>
                    @if($company->platformStatusNoteText())
                        <div class="mb-2"><strong>Latest Platform Note:</strong> {{ $company->platformStatusNoteText() }}</div>
                    @endif
                    @if($company->platform_status_changed_at)
                        <div class="mb-2"><strong>Platform Status Updated:</strong> {{ $company->platform_status_changed_at->format('Y-m-d H:i') }}</div>
                    @endif
                    @if($company->platformStatusChangedBy)
                        <div class="mb-2"><strong>Updated By:</strong> {{ $company->platformStatusChangedBy->name }}</div>
                    @endif
                    <div class="mb-2"><strong>Workspace Admin Account ID:</strong> {{ $workspaceAdmin?->email ?? 'Not found' }}</div>
                    <div class="mb-2"><strong>Staff Registration:</strong> <a href="{{ route('companies.register', $company->slug) }}">{{ route('companies.register', $company->slug) }}</a></div>
                    <div class="mb-2"><strong>Tenant Registration:</strong> <a href="{{ route('companies.register.tenant', $company->slug) }}">{{ route('companies.register.tenant', $company->slug) }}</a></div>
                    <div class="mb-2"><strong>Primary Color:</strong> {{ $company->settings->primary_color ?? '#7c3aed' }}</div>
                    <div class="mb-2"><strong>Support Email:</strong> {{ $company->settings->support_email ?? 'Not set' }}</div>
                    <div class="mb-0"><strong>Onboarded:</strong> {{ optional($company->onboarded_at)->format('Y-m-d H:i') ?? 'Pending' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-primary">Save Company</button>
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Back</a>
        <button type="submit"
                form="delete-company-form"
                class="btn btn-outline-danger ms-md-auto"
                onclick="return confirm('Are you sure you want to delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');">
            Delete Company
        </button>
    </div>
</form>

<form id="delete-company-form" method="POST" action="{{ route('companies.destroy', $company) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection
