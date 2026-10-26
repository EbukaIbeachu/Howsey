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
<x-ui.page-header
    title="Create Company"
    subtitle="Onboard a new housing management company with branding, first admin, and optional imports."
/>

<form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data" autocomplete="off">
    @csrf
    <input type="text" name="fake_username" autocomplete="username" class="d-none" tabindex="-1" aria-hidden="true">
    <input type="password" name="fake_password" autocomplete="current-password" class="d-none" tabindex="-1" aria-hidden="true">
    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <x-ui.card title="Step 1: Company Profile" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="name"
                            id="company_name"
                            label="Company Name"
                            oninput="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();"
                            onchange="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="slug" label="Workspace Slug" placeholder="acme-housing" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.select name="primary_color" label="Primary Color" :value="'#7c3aed'">
                            @foreach($brandColorOptions as $colorValue => $colorLabel)
                                <option value="{{ $colorValue }}" @selected(old('primary_color', '#7c3aed') === $colorValue)>
                                    {{ $colorLabel }} ({{ $colorValue }})
                                </option>
                            @endforeach
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.file name="logo" label="Company Logo" accept="image/*" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="support_email" type="email" label="Support Email" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="support_phone" label="Support Phone" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="receipt_header" label="Receipt Header" :value="'Official Payment Receipt'" />
                    </div>
                    {{-- The hidden #email_header and the visible #email_header_display are
                         kept as hand-written markup: the page script mirrors the visible
                         value into the hidden field and tracks user edits via dataset. --}}
                    <div class="col-md-6">
                        <label class="ui-label" for="email_header_display">Email Header</label>
                        <input type="hidden" id="email_header" name="email_header" value="{{ old('email_header', old('name')) }}">
                        <input type="text" id="email_header_display" class="ui-input" value="{{ old('email_header', old('name')) }}" autocomplete="off" oninput="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;" onchange="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;">
                        <span class="ui-hint">Auto-filled from the company name.</span>
                    </div>
                    <div class="col-12">
                        <x-ui.form.textarea name="address" label="Address" :rows="3" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Step 2: First Admin User" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="admin_name"
                            id="admin_name"
                            label="Admin Name"
                            autocomplete="name"
                            oninput="syncCompanyRegistrationFields();"
                            onchange="syncCompanyRegistrationFields();"
                            required
                        />
                    </div>
                    {{-- The hidden #admin_email and the read-only #admin_email_display are
                         kept as hand-written markup because the page script writes both
                         by id (the display field intentionally submits nothing). --}}
                    <div class="col-md-6">
                        <label class="ui-label" for="admin_email_display">Admin Email</label>
                        <input type="hidden" id="admin_email" name="admin_email" value="">
                        <input type="text" id="admin_email_display" class="ui-input" value="" autocomplete="off" readonly>
                        <span class="ui-hint">Generated automatically from the admin full name and workspace slug. Do not forget this Account ID.</span>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="admin_phone"
                            label="Admin Phone"
                            hint="Use a login phone number the workspace admin will remember. Password recovery depends on this number."
                        />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="admin_password" type="password" label="Password" required />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="admin_password_confirmation" type="password" label="Confirm Password" required />
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-12 col-xl-5">
            <x-ui.card title="Step 3: Optional Imports" class="mb-4">
                <x-ui.form.file name="buildings_csv" label="Buildings CSV" class="mb-3" />
                <x-ui.form.file name="units_csv" label="Units CSV" class="mb-3" />
                <x-ui.form.file name="tenants_csv" label="Tenants CSV" />
            </x-ui.card>

            <x-ui.card title="Step 4: Demo Experience" class="mb-4">
                <x-ui.form.checkbox
                    name="sample_dataset"
                    label="Load sample dataset for demo accounts"
                    :checked="old('sample_dataset')"
                />
                <p class="small text-muted mt-3 mb-0">This creates a ready-to-tour company workspace with a sample building and units.</p>
            </x-ui.card>

            <div class="d-grid">
                <x-ui.button type="submit" variant="primary" size="lg" block>Create Company Workspace</x-ui.button>
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
