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
<x-ui.page-header
    title="Company Settings"
    :subtitle="'Control branding, reminder delivery, and branded document templates for '.$company->name.'.'"
/>

@if($company->isAdminInactive())
    <x-ui.alert tone="warning" title="Workspace access is currently deactivated" class="mb-3">
        Staff and tenants are blocked from the workspace until a company admin switches the workspace back to active.
    </x-ui.alert>
@endif

<form method="POST" action="{{ route('company-settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')

    <div class="row g-4">
        <div class="col-xl-6">
            <x-ui.card title="Company Admin Workspace Access" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.select name="workspace_status" label="Workspace Status" :value="$company->status">
                            <option value="active" @selected(old('workspace_status', $company->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('workspace_status', $company->status) === 'inactive')>Inactive</option>
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-6">
                        <label class="ui-label">Current Access Mode</label>
                        <div class="form-control bg-light">{{ $company->adminStatusLabel() }}</div>
                    </div>
                    <div class="col-12">
                        <x-ui.alert tone="info" class="mb-0 small">
                            <strong>What this does:</strong> This company-admin setting keeps all data in place, blocks staff and tenants from entering the workspace, and still allows company admins to sign in only so they can reactivate the workspace later. A separate super-admin platform lock can override this from the platform side.
                        </x-ui.alert>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Branding" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.input name="name" label="Company Name" :value="$company->name" required />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.select
                            name="primary_color"
                            label="Primary Color"
                            :value="$company->settings->primary_color ?? '#7c3aed'"
                            hint="Choose one of the approved workspace colors."
                        >
                            @foreach($brandColorOptions as $colorValue => $colorLabel)
                                <option value="{{ $colorValue }}" @selected(old('primary_color', $company->settings->primary_color ?? '#7c3aed') === $colorValue)>
                                    {{ $colorLabel }} ({{ $colorValue }})
                                </option>
                            @endforeach
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.file name="logo" label="Logo" />
                        @if($company->settings?->logo_path)
                            <div class="mt-3">
                                <img src="{{ url('/storage/'.$company->settings->logo_path) }}" alt="{{ $company->name }} logo preview" class="logo-photo-preview">
                                <div class="form-text mt-2">Current uploaded logo preview.</div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="email_header" label="Email Header" :value="$company->settings->email_header ?? $company->name" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="receipt_header" label="Receipt Header" :value="$company->settings->receipt_header ?? 'Official Payment Receipt'" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="support_email" type="email" label="Support Email" :value="$company->settings->support_email" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="support_phone" label="Support Phone" :value="$company->settings->support_phone" />
                    </div>
                    <div class="col-12">
                        <x-ui.form.textarea name="address" label="Address" :value="$company->settings->address" :rows="3" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Notification Settings">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.checkbox name="email_enabled" label="Email Enabled" :checked="$company->notificationSettings->email_enabled ?? true" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.checkbox name="send_overdue_rent_reminders" label="Overdue Rent Reminders" :checked="$company->notificationSettings->send_overdue_rent_reminders ?? true" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.checkbox name="send_lease_expiry_reminders" label="Lease Expiry Reminders" :checked="$company->notificationSettings->send_lease_expiry_reminders ?? true" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="lease_expiry_days_notice"
                            type="number"
                            label="Lease Notice Lead Time"
                            :value="$company->notificationSettings->lease_expiry_days_notice ?? 30"
                            min="1"
                            max="365"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="from_name" label="From Name" :value="$company->notificationSettings->from_name ?? $company->name" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="from_email" type="email" label="From Email" :value="$company->notificationSettings->from_email" />
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-xl-6">
            <x-ui.card title="Notification Templates" class="mb-4">
                @foreach(['overdue_rent' => 'Overdue Rent', 'lease_expiry' => 'Lease Expiry'] as $key => $label)
                    <div class="mb-4">
                        <h6 class="fw-semibold">{{ $label }}</h6>
                        <x-ui.form.input
                            name="notification_templates[{{ $key }}][subject]"
                            label="Subject"
                            :value="optional($notificationTemplates->get($key))->subject"
                            class="mb-2"
                        />
                        <x-ui.form.textarea
                            name="notification_templates[{{ $key }}][body]"
                            label="Body"
                            :value="optional($notificationTemplates->get($key))->body"
                            :rows="4"
                        />
                    </div>
                @endforeach
            </x-ui.card>

            <x-ui.card title="Document Templates">
                @foreach(['rent_receipt' => 'Rent Receipt', 'arrears_notice' => 'Arrears Notice', 'lease_renewal_notice' => 'Lease Renewal Notice'] as $key => $label)
                    <div class="mb-4">
                        <h6 class="fw-semibold">{{ $label }}</h6>
                        <x-ui.form.input
                            name="document_templates[{{ $key }}][subject]"
                            label="Subject"
                            :value="optional($documentTemplates->get($key))->subject"
                            class="mb-2"
                        />
                        <x-ui.form.textarea
                            name="document_templates[{{ $key }}][body_html]"
                            label="HTML Template"
                            :value="optional($documentTemplates->get($key))->body_html"
                            :rows="5"
                        />
                    </div>
                @endforeach
            </x-ui.card>
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" size="lg">Save Company Settings</x-ui.button>
    </div>
</form>

<div class="row g-4 mt-1">
    <div class="col-xl-6">
        <x-ui.card title="Designated Receiving Accounts">
            <x-slot:actions>
                <x-ui.status-badge tone="neutral" icon="bi bi-wallet2" :label="$paymentAccounts->count()" />
            </x-slot:actions>

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
                                    <x-ui.status-badge tone="primary" label="Primary" class="ms-1" />
                                @endif
                                @unless($account->is_active)
                                    <x-ui.status-badge tone="neutral" label="Inactive" class="ms-1" />
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
                            <x-ui.button type="submit" variant="primary" size="sm">Save Changes</x-ui.button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('payment-accounts.destroy', $account) }}" class="mt-2" onsubmit="return confirm('Remove {{ $account->display_name }}? Tenants will no longer see it.');">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-trash">Remove Account</x-ui.button>
                    </form>
                </div>
            @empty
                <x-ui.alert tone="neutral" class="small">
                    No receiving account has been added yet. Until you add one, tenants who click <strong>Pay Now</strong> will
                    be told to contact the office for payment details.
                </x-ui.alert>
            @endforelse

            <hr>

            <h6 class="fw-semibold">Add Another Account</h6>
            <form method="POST" action="{{ route('payment-accounts.store') }}">
                @csrf
                @include('companies.partials.payment-account-fields', ['account' => null, 'uid' => 'new'])
                <x-ui.button type="submit" variant="primary" size="sm" icon="bi bi-plus-lg" class="mt-3">Add Receiving Account</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
