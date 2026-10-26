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

    // The select component renders [value => label], so the hex suffix the
    // original <option> markup appended is baked into the label here.
    $brandColorChoices = [];
    foreach ($brandColorOptions as $colorValue => $colorLabel) {
        $brandColorChoices[$colorValue] = $colorLabel.' ('.$colorValue.')';
    }
@endphp
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="text-center mb-4">
                <x-ui.status-badge label="Launch Your Company Workspace" tone="primary" icon="bi bi-rocket-takeoff" />
                <h1 class="display-6 fw-bold mt-3 mb-2">Apply for your HOWSEY company workspace</h1>
                <p class="text-muted mb-0">Submit your company setup, pay the annual workspace fee through Paystack, and wait for super admin approval before the live workspace is created.</p>
            </div>

            <form method="POST" action="{{ route('companies.public.store') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <input type="text" name="fake_username" autocomplete="username" class="d-none" tabindex="-1" aria-hidden="true">
                <input type="password" name="fake_password" autocomplete="current-password" class="d-none" tabindex="-1" aria-hidden="true">
                <input type="hidden" id="payment_reference" name="payment_reference" value="{{ old('payment_reference', $paymentReference ?? '') }}">
                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <x-ui.card title="Company Profile" class="h-100">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <x-ui.form.input name="name" id="company_name" label="Company Name" required
                                        oninput="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();"
                                        onchange="document.getElementById('email_header').value = this.value; syncCompanyRegistrationFields();" />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.input name="slug" label="Workspace Slug" placeholder="acme-housing" hint="Used for your distributed company URL." />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.file name="logo" label="Company Logo" accept="image/*" hint="This logo will be reused across your PWA-style desktop and mobile workspace." />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.select name="primary_color" label="Primary Color" :options="$brandColorChoices" value="#7c3aed" hint="Choose one of the preset company brand colors for your workspace." />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.input name="support_email" type="email" label="Support Email" />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.input name="support_phone" label="Support Phone" />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.form.input name="receipt_header" label="Receipt Header" value="Official Payment Receipt" />
                                </div>
                                <div class="col-md-6">
                                    {{-- Paired fields: the visible input deliberately has no name (it is
                                         not submitted) and mirrors the hidden #email_header via JS, so
                                         it stays hand-written rather than using x-ui.form.input. --}}
                                    <div class="ui-field">
                                        <label for="email_header_display" class="ui-label">Email Header</label>
                                        <input type="hidden" id="email_header" name="email_header" value="{{ old('email_header', old('name')) }}">
                                        <input type="text" id="email_header_display" class="ui-input" value="{{ old('email_header', old('name')) }}" autocomplete="off" oninput="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;" onchange="markEmailHeaderEdited(); document.getElementById('email_header').value = this.value;">
                                        <span class="ui-hint">Auto-filled from the company name.</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <x-ui.form.textarea name="address" label="Address" rows="3" />
                                </div>
                            </div>
                        </x-ui.card>
                    </div>

                    <div class="col-12 col-lg-5">
                        <x-ui.card title="First Company Admin" class="mb-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <x-ui.form.input name="admin_name" label="Full Name" required autocomplete="name"
                                        oninput="syncCompanyRegistrationFields();"
                                        onchange="syncCompanyRegistrationFields();" />
                                </div>
                                <div class="col-12">
                                    {{-- Paired fields — see the Email Header note above. --}}
                                    <div class="ui-field">
                                        <label for="admin_email_display" class="ui-label">Account ID</label>
                                        <input type="hidden" id="admin_email" name="admin_email" value="">
                                        <input type="text" id="admin_email_display" class="ui-input" value="" autocomplete="off" readonly>
                                        <span class="ui-hint">Generated automatically from the full name and workspace slug. Do not forget this Account ID.</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <x-ui.form.input name="admin_phone" label="Phone Number" hint="Use a login phone number you will remember. Password recovery depends on this number." />
                                </div>
                                <div class="col-12">
                                    <x-ui.form.input name="admin_password" type="password" label="Password" required />
                                </div>
                                <div class="col-12">
                                    <x-ui.form.input name="admin_password_confirmation" type="password" label="Confirm Password" required />
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card title="Workspace Setup" class="mb-4">
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    {{-- Display-only mirror of the hidden #payment_reference above. --}}
                                    <div class="ui-field">
                                        <label for="payment_reference_display" class="ui-label">Payment Reference</label>
                                        <input type="text" id="payment_reference_display" class="ui-input" value="{{ old('payment_reference', $paymentReference ?? '') }}" readonly>
                                        <span class="ui-hint">This reference is auto-generated for your Paystack company onboarding payment.</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="ui-field">
                                        <label for="annual_workspace_fee" class="ui-label">Annual Workspace Fee</label>
                                        <input type="text" id="annual_workspace_fee" class="ui-input" value="NGN {{ number_format((float) ($registrationFee ?? 0), 2) }}" readonly>
                                        <span class="ui-hint">Paystack will use this amount during checkout for the first {{ $subscriptionDurationMonths ?? 12 }} months of access.</span>
                                    </div>
                                </div>
                            </div>
                            <x-ui.form.checkbox name="sample_dataset" label="Load sample property data for a guided demo" />
                            <p class="small text-muted mt-3 mb-0">After payment, the super admin will review the application, confirm the Paystack reference, and only then create the real company workspace. Workspace access renews annually.</p>
                        </x-ui.card>

                        <div class="d-grid gap-2">
                            @if($paystackConfigured ?? false)
                                <x-ui.button type="submit" variant="primary" size="lg">Continue to Paystack</x-ui.button>
                            @else
                                <x-ui.button type="button" variant="secondary" size="lg" disabled>Paystack Not Configured Yet</x-ui.button>
                            @endif
                            <x-ui.button variant="outline-secondary" :href="url('/')">Back Home</x-ui.button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
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
