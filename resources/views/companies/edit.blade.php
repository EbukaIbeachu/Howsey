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
<x-ui.page-header
    :title="$company->name"
    subtitle="Review workspace status, white-label identity, and onboarding readiness."
/>

@if(session('created_admin_account_id'))
    <x-ui.alert tone="success" title="Workspace created and ready for sign-in" class="shadow-sm">
        <div class="mb-1"><strong>First Admin:</strong> {{ session('created_admin_name') ?: 'New admin user' }}</div>
        <div class="mb-1"><strong>Admin Account ID:</strong> <code>{{ session('created_admin_account_id') }}</code></div>
        <div class="mb-1"><strong>Branded Login URL:</strong> <a href="{{ session('created_company_login_url') }}">{{ session('created_company_login_url') }}</a></div>
        <div class="small text-muted mt-2">Use the password that was entered during company creation. The generated Account ID above is the login username for this new workspace admin.</div>
    </x-ui.alert>
@endif

<form method="POST" action="{{ route('companies.update', $company) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-8">
            <x-ui.card title="Company Profile">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.input name="name" label="Company Name" :value="$company->name" required />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="slug" label="Workspace Slug" :value="$company->slug" required />
                    </div>
                    <div class="col-md-6">
                        <label class="ui-label">Company Admin Status</label>
                        <div class="form-control bg-light">{{ $company->adminStatusLabel() }}</div>
                        <div class="form-text">This is the company-admin-controlled workspace status from the workspace settings page.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <x-ui.form.checkbox name="is_demo" label="Demo Workspace" :checked="$company->is_demo" />
                    </div>
                    <div class="col-12">
                        <hr class="my-1">
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.select name="platform_status" label="Platform Workspace Access" :value="$company->platform_status ?: 'active'">
                            <option value="active" @selected(old('platform_status', $company->platform_status ?: 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('platform_status', $company->platform_status ?: 'active') === 'inactive')>Inactive</option>
                        </x-ui.form.select>
                        <div class="form-text">This is the super-admin lock. If you set it to inactive, the company admin cannot reverse it from the workspace side.</div>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.textarea
                            name="platform_status_note"
                            label="Platform Action Note"
                            :value="$company->platform_status_note"
                            :rows="3"
                            placeholder="Optional note for deactivation or reactivation"
                        />
                        <div class="form-text">Use this note to explain why the platform lock was changed.</div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card title="Workspace Snapshot">
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
            </x-ui.card>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
        <x-ui.button type="submit" variant="primary">Save Company</x-ui.button>
        <x-ui.button variant="outline-secondary" :href="route('companies.index')">Back</x-ui.button>
        <x-ui.button
            type="submit"
            variant="outline-danger"
            form="delete-company-form"
            class="ms-md-auto"
            onclick="return confirm('Are you sure you want to delete {{ addslashes($company->name) }} and all of its company data? This cannot be undone.');"
        >Delete Company</x-ui.button>
    </div>
</form>

<form id="delete-company-form" method="POST" action="{{ route('companies.destroy', $company) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection
