@extends('layouts.app')

@php
    $notificationTemplates = $company->notificationTemplates->keyBy('type');
    $documentTemplates = $company->documentTemplates->keyBy('type');
    $brandColorOptions = [
        '#7c3aed' => 'Royal Purple',
        '#2563eb' => 'Ocean Blue',
        '#059669' => 'Emerald Green',
        '#dc2626' => 'Signal Red',
        '#ea580c' => 'Sunset Orange',
        '#d97706' => 'Amber Gold',
        '#db2777' => 'Rose Pink',
        '#0891b2' => 'Teal Cyan',
        '#4f46e5' => 'Indigo',
        '#65a30d' => 'Lime Green',
        '#9333ea' => 'Violet',
        '#475569' => 'Slate Gray',
        '#0f766e' => 'Deep Teal',
        '#1d4ed8' => 'Cobalt Blue',
        '#be123c' => 'Crimson',
        '#7e22ce' => 'Electric Purple',
        '#c2410c' => 'Burnt Orange',
        '#15803d' => 'Forest Green',
        '#b45309' => 'Bronze',
        '#334155' => 'Charcoal',
    ];
@endphp

@section('content')
<style>
    .logo-photo-preview {
        width: 100%;
        max-width: 240px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 1.35rem;
        border: 1px solid rgba(var(--brand-primary-rgb), 0.14);
        box-shadow: 0 1rem 2rem rgba(15, 23, 42, 0.14);
        background: linear-gradient(180deg, rgba(var(--brand-primary-rgb), 0.08), rgba(255, 255, 255, 0.94));
    }
</style>
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-1">Company Settings</h1>
    <p class="text-muted mb-0">Control branding, reminder delivery, and branded document templates for {{ $company->name }}.</p>
</div>

@if($company->isAdminInactive())
    <div class="alert alert-warning">
        <div class="fw-semibold mb-1">Workspace access is currently deactivated</div>
        <div class="small">Staff and tenants are blocked from the workspace until a company admin switches the workspace back to active.</div>
    </div>
@endif

<form method="POST" action="{{ route('company-settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Company Admin Workspace Access</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Workspace Status</label>
                        <select name="workspace_status" class="form-select">
                            <option value="active" {{ old('workspace_status', $company->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('workspace_status', $company->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current Access Mode</label>
                        <div class="form-control bg-light">{{ $company->adminStatusLabel() }}</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border mb-0 small">
                            <strong>What this does:</strong> This company-admin setting keeps all data in place, blocks staff and tenants from entering the workspace, and still allows company admins to sign in only so they can reactivate the workspace later. A separate super-admin platform lock can override this from the platform side.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">Branding</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primary Color</label>
                        <select name="primary_color" class="form-select">
                            @foreach($brandColorOptions as $colorValue => $colorLabel)
                                <option value="{{ $colorValue }}" {{ old('primary_color', $company->settings->primary_color ?? '#7c3aed') === $colorValue ? 'selected' : '' }}>
                                    {{ $colorLabel }} ({{ $colorValue }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Choose one of the approved workspace colors.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control">
                        @if($company->settings?->logo_path)
                            <div class="mt-3">
                                <img src="{{ url('/storage/'.$company->settings->logo_path) }}" alt="{{ $company->name }} logo preview" class="logo-photo-preview">
                                <div class="form-text mt-2">Current uploaded logo preview.</div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Header</label>
                        <input type="text" name="email_header" class="form-control" value="{{ old('email_header', $company->settings->email_header ?? $company->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Receipt Header</label>
                        <input type="text" name="receipt_header" class="form-control" value="{{ old('receipt_header', $company->settings->receipt_header ?? 'Official Payment Receipt') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Email</label>
                        <input type="email" name="support_email" class="form-control" value="{{ old('support_email', $company->settings->support_email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Phone</label>
                        <input type="text" name="support_phone" class="form-control" value="{{ old('support_phone', $company->settings->support_phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-control">{{ old('address', $company->settings->address) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Notification Settings</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_enabled" value="1" {{ old('email_enabled', $company->notificationSettings->email_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Email Enabled</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="send_overdue_rent_reminders" value="1" {{ old('send_overdue_rent_reminders', $company->notificationSettings->send_overdue_rent_reminders ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Overdue Rent Reminders</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="send_lease_expiry_reminders" value="1" {{ old('send_lease_expiry_reminders', $company->notificationSettings->send_lease_expiry_reminders ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Lease Expiry Reminders</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lease Notice Lead Time</label>
                        <input type="number" min="1" max="365" name="lease_expiry_days_notice" class="form-control" value="{{ old('lease_expiry_days_notice', $company->notificationSettings->lease_expiry_days_notice ?? 30) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $company->notificationSettings->from_name ?? $company->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $company->notificationSettings->from_email) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Notification Templates</div>
                <div class="card-body">
                    @foreach(['overdue_rent' => 'Overdue Rent', 'lease_expiry' => 'Lease Expiry'] as $key => $label)
                        <div class="mb-4">
                            <h6 class="fw-semibold">{{ $label }}</h6>
                            <label class="form-label">Subject</label>
                            <input type="text" name="notification_templates[{{ $key }}][subject]" class="form-control mb-2" value="{{ old("notification_templates.$key.subject", optional($notificationTemplates->get($key))->subject) }}">
                            <label class="form-label">Body</label>
                            <textarea name="notification_templates[{{ $key }}][body]" class="form-control" rows="4">{{ old("notification_templates.$key.body", optional($notificationTemplates->get($key))->body) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">Document Templates</div>
                <div class="card-body">
                    @foreach(['rent_receipt' => 'Rent Receipt', 'arrears_notice' => 'Arrears Notice', 'lease_renewal_notice' => 'Lease Renewal Notice'] as $key => $label)
                        <div class="mb-4">
                            <h6 class="fw-semibold">{{ $label }}</h6>
                            <label class="form-label">Subject</label>
                            <input type="text" name="document_templates[{{ $key }}][subject]" class="form-control mb-2" value="{{ old("document_templates.$key.subject", optional($documentTemplates->get($key))->subject) }}">
                            <label class="form-label">HTML Template</label>
                            <textarea name="document_templates[{{ $key }}][body_html]" class="form-control" rows="5">{{ old("document_templates.$key.body_html", optional($documentTemplates->get($key))->body_html) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary btn-lg">Save Company Settings</button>
    </div>
</form>

<div class="row g-4 mt-1">
    <div class="col-xl-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Designated Receiving Accounts</span>
                <span class="badge bg-secondary">{{ $paymentAccounts->count() }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    When a tenant clicks <strong>Pay Now</strong> on their dashboard, these are the accounts they are shown.
                    Add as many as you need and mark one as primary to have it listed first.
                </p>

                @forelse($paymentAccounts as $account)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $account->display_name }}
                                    @if($account->is_primary)
                                        <span class="badge bg-primary ms-1">Primary</span>
                                    @endif
                                    @unless($account->is_active)
                                        <span class="badge bg-secondary ms-1">Inactive</span>
                                    @endunless
                                </div>
                                <div class="text-muted small">{{ $account->account_number }} &middot; {{ $account->account_name }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('payment-accounts.update', $account) }}">
                            @csrf
                            @method('PATCH')
                            @include('companies.partials.payment-account-fields', ['account' => $account, 'uid' => $account->id])
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('payment-accounts.destroy', $account) }}" class="mt-2" onsubmit="return confirm('Remove {{ $account->display_name }}? Tenants will no longer see it.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Remove Account
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="alert alert-light border small">
                        No receiving account has been added yet. Until you add one, tenants who click <strong>Pay Now</strong> will
                        be told to contact the office for payment details.
                    </div>
                @endforelse

                <hr>

                <h6 class="fw-semibold">Add Another Account</h6>
                <form method="POST" action="{{ route('payment-accounts.store') }}">
                    @csrf
                    @include('companies.partials.payment-account-fields', ['account' => null, 'uid' => 'new'])
                    <button type="submit" class="btn btn-sm btn-primary mt-3">
                        <i class="bi bi-plus-lg"></i> Add Receiving Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
