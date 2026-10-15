@extends('layouts.app')

@section('content')
@php
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
    ];
@endphp
<div class="pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-1">Create Company</h1>
    <p class="text-muted mb-0">Onboard a new housing management company with branding, first admin, and optional imports.</p>
</div>

<form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="text" name="fake_username" autocomplete="username" class="d-none" tabindex="-1" aria-hidden="true">
    <input type="password" name="fake_password" autocomplete="current-password" class="d-none" tabindex="-1" aria-hidden="true">
    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Step 1: Company Profile</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" id="company_name" name="name" class="form-control" value="{{ old('name') }}" oninput="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();" onchange="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Workspace Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="acme-housing">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primary Color</label>
                        <select name="primary_color" class="form-select">
                            @foreach($brandColorOptions as $colorValue => $colorLabel)
                                <option value="{{ $colorValue }}" {{ old('primary_color', '#7c3aed') === $colorValue ? 'selected' : '' }}>
                                    {{ $colorLabel }} ({{ $colorValue }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Email</label>
                        <input type="email" name="support_email" class="form-control" value="{{ old('support_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Phone</label>
                        <input type="text" name="support_phone" class="form-control" value="{{ old('support_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Receipt Header</label>
                        <input type="text" name="receipt_header" class="form-control" value="{{ old('receipt_header', 'Official Payment Receipt') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Header</label>
                        <input type="hidden" id="email_header" name="email_header" value="{{ old('email_header', old('name')) }}">
                        <input type="text" id="email_header_display" class="form-control" value="{{ old('email_header', old('name')) }}" autocomplete="off" oninput="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;" onchange="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;">
                        <div class="form-text">Auto-filled from the company name.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">Step 2: First Admin User</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Admin Name</label>
                        <input type="text" id="admin_name" name="admin_name" class="form-control" value="{{ old('admin_name') }}" autocomplete="name" oninput="syncCompanyRegistrationFields();" onchange="syncCompanyRegistrationFields();" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admin Email</label>
                        <input type="hidden" id="admin_email" name="admin_email" value="">
                        <input type="text" id="admin_email_display" class="form-control" value="" autocomplete="off" readonly>
                        <div class="form-text">Generated automatically from the admin full name and workspace slug. Do not forget this Account ID.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admin Phone</label>
                        <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone') }}">
                        <div class="form-text">Use a login phone number the workspace admin will remember. Password recovery depends on this number.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="admin_password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header">Step 3: Optional Imports</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Buildings CSV</label>
                        <input type="file" name="buildings_csv" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Units CSV</label>
                        <input type="file" name="units_csv" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Tenants CSV</label>
                        <input type="file" name="tenants_csv" class="form-control">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">Step 4: Demo Experience</div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sample_dataset" value="1" id="sample_dataset" {{ old('sample_dataset') ? 'checked' : '' }}>
                        <label class="form-check-label" for="sample_dataset">Load sample dataset for demo accounts</label>
                    </div>
                    <p class="small text-muted mt-3 mb-0">This creates a ready-to-tour company workspace with a sample building and units.</p>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Create Company Workspace</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    window.syncCompanyRegistrationFields = function () {
        const companyNameInput = document.getElementById('company_name');
        const companySlugInput = document.querySelector('input[name="slug"]');
        const emailHeaderInput = document.getElementById('email_header');
        const emailHeaderDisplayInput = document.getElementById('email_header_display');
        const nameInput = document.getElementById('admin_name');
        const emailInput = document.getElementById('admin_email');
        const emailDisplayInput = document.getElementById('admin_email_display');

        if (!nameInput || !emailInput || !emailDisplayInput) {
            return;
        }

        if (emailHeaderDisplayInput && !emailHeaderDisplayInput.dataset.lastAutoValue) {
            emailHeaderDisplayInput.dataset.lastAutoValue = emailHeaderDisplayInput.value.trim();
        }

        function resolveAccountDomain() {
            const slug = String(companySlugInput?.value || '').trim().toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            return slug ? `${slug}.howsey.site` : 'howsey.site';
        }

        function generateEmailFromName(name) {
            const rawName = String(name || '').trim().toLowerCase();
            const asciiName = typeof rawName.normalize === 'function'
                ? rawName.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : rawName;
            const parts = asciiName.split(/\s+/).map(function (part) {
                return part.replace(/[^a-z0-9]/g, '');
            }).filter(Boolean);

            if (parts.length === 0) {
                return '';
            }

            if (parts.length === 1) {
                return `${parts[0]}@${resolveAccountDomain()}`;
            }

            return `${parts[0].charAt(0)}.${parts[parts.length - 1]}@${resolveAccountDomain()}`;
        }

        function syncAccountId() {
            const generatedEmail = generateEmailFromName(nameInput.value);
            emailInput.value = generatedEmail;
            emailDisplayInput.value = generatedEmail;
        }

        function syncEmailHeader() {
            if (!companyNameInput || !emailHeaderInput || !emailHeaderDisplayInput) {
                return;
            }

            const companyName = companyNameInput.value.trim();
            const currentHeader = emailHeaderDisplayInput.value.trim();
            const lastAutoEmailHeader = emailHeaderDisplayInput.dataset.lastAutoValue || '';
            const userModified = emailHeaderDisplayInput.dataset.userModified === '1';

            if (!userModified || currentHeader === '' || currentHeader === lastAutoEmailHeader) {
                emailHeaderDisplayInput.value = companyName;
            }

            emailHeaderInput.value = emailHeaderDisplayInput.value;
            emailHeaderDisplayInput.dataset.lastAutoValue = companyName;
        }

        syncEmailHeader();
        syncAccountId();
    };

    window.markEmailHeaderEdited = function () {
        const emailHeaderInput = document.getElementById('email_header');
        const emailHeaderDisplayInput = document.getElementById('email_header_display');

        if (!emailHeaderInput || !emailHeaderDisplayInput) {
            return;
        }

        const currentHeader = emailHeaderDisplayInput.value.trim();
        const lastAutoEmailHeader = emailHeaderDisplayInput.dataset.lastAutoValue || '';

        emailHeaderDisplayInput.dataset.userModified = currentHeader !== '' && currentHeader !== lastAutoEmailHeader ? '1' : '0';
        emailHeaderInput.value = currentHeader;
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.syncCompanyRegistrationFields();
        document.querySelector('input[name="slug"]')?.addEventListener('input', window.syncCompanyRegistrationFields);
    });
</script>
@endpush
