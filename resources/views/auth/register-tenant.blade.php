@extends('layouts.app')

@section('content')
@php
    $registrationAccountDomain = \App\Support\AutoEmailGenerator::domainForCompany($registrationCompany ?? null);
    $registrationAccountExample = 'e.lastname@'.$registrationAccountDomain;
    $accountIdHint = 'Generated automatically from your full name in the `'.$registrationAccountExample.'` format. Do not forget this Account ID.';
@endphp
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <x-ui.card
                :title="$registrationCompany ? $registrationCompany->name.' Tenant Registration' : 'Tenant Registration'"
                icon="bi bi-person-plus"
            >
                <x-slot:actions>
                    @if(!empty($branding['logo_url']))
                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] ?? 'HOWSEY' }} logo" class="rounded bg-white p-1" style="width: 2rem; height: 2rem; object-fit: contain;">
                    @endif
                    <x-ui.icon-button label="Registration help" icon="bi bi-question-lg" data-bs-toggle="modal" data-bs-target="#tenantRegistrationEmailHelpModal" />
                </x-slot:actions>

                <x-ui.alert tone="info">
                    Only tenants who have already been registered to a building and unit by management can create a login account here{{ $registrationCompany ? ' for '.$registrationCompany->name : '' }}.
                </x-ui.alert>

                <form method="POST" action="{{ $registrationCompany ? route('companies.register.tenant.store', $registrationCompany->slug) : url('/register-tenant') }}">
                    @csrf

                    <x-ui.form.input name="name" label="Full Name" required autofocus />

                    <x-ui.form.input
                        name="email"
                        type="email"
                        label="Account ID"
                        readonly
                        :hint="$accountIdHint"
                    />

                    <x-ui.form.input
                        name="phone"
                        label="Phone Number"
                        hint="Use a login phone number you will remember. Password recovery depends on this number."
                    />

                    {{-- Role is automatically set to Tenant --}}

                    <x-ui.form.input name="password" type="password" label="Password" required />

                    <x-ui.form.input name="password_confirmation" type="password" label="Confirm Password" required />

                    <div class="d-grid gap-2">
                        <x-ui.button type="submit" variant="primary">Start</x-ui.button>
                        <x-ui.button variant="outline-secondary" :href="$registrationCompany ? route('companies.portal', $registrationCompany->slug) : url('/')">Home</x-ui.button>
                        <x-ui.button variant="ghost" :href="$registrationCompany ? route('companies.login', $registrationCompany->slug) : route('login')">Already have an account? Login</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>

<x-ui.modal id="tenantRegistrationEmailHelpModal" title="About the account ID to use">
    <p>You do not have to use a real external email address for this account.</p>
    <p>
        Use an account ID that fits the HOWSEY system,
        such as <strong>{{ $registrationAccountExample }}</strong>,
        so the account remains properly identified inside the platform.
    </p>
    <p>This account ID is safe to use here and is not used for anything unusual or outside the normal operation of the system.</p>
    <p class="mb-0">All users are advised to keep their login details safe and well stored.</p>
</x-ui.modal>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const accountDomain = @json($registrationAccountDomain);
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');

        function generateEmailFromName(name) {
            const asciiName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
            const parts = asciiName.split(/\s+/).map(part => part.replace(/[^a-z0-9]/g, '')).filter(Boolean);

            if (parts.length === 0) {
                return '';
            }

            if (parts.length === 1) {
                return `${parts[0]}@${accountDomain}`;
            }

            return `${parts[0].charAt(0)}.${parts[parts.length - 1]}@${accountDomain}`;
        }

        function syncAccountId() {
            emailInput.value = generateEmailFromName(nameInput.value);
        }

        nameInput.addEventListener('input', syncAccountId);
        syncAccountId();
    });
</script>
@endpush
