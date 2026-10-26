@extends('layouts.app')

@section('content')
@php
    $registrationAccountDomain = \App\Support\AutoEmailGenerator::domainForCompany($registrationCompany ?? null);
    $registrationAccountExample = 'e.lastname@'.$registrationAccountDomain;
@endphp
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <x-ui.card
                :title="$registrationCompany ? $registrationCompany->name.' Staff Registration' : 'Staff Registration'"
                icon="bi bi-person-plus"
            >
                <x-slot:actions>
                    @if(!empty($branding['logo_url']))
                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] ?? 'HOWSEY' }} logo" class="rounded bg-white p-1" style="width: 2rem; height: 2rem; object-fit: contain;">
                    @endif
                    <x-ui.icon-button label="Registration help" icon="bi bi-question-lg" data-bs-toggle="modal" data-bs-target="#registrationEmailHelpModal" />
                </x-slot:actions>

                @if($registrationCompany)
                    <x-ui.alert tone="info">
                        You are registering into the <strong>{{ $registrationCompany->name }}</strong> workspace. Your account will stay attached to this company after approval.
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ $registrationCompany ? route('companies.register.store', $registrationCompany->slug) : route('register') }}">
                    @csrf

                    <x-ui.form.input name="name" label="Full Name" required autofocus />

                    <x-ui.form.input
                        name="email"
                        type="email"
                        label="Account ID"
                        readonly
                        hint="Generated automatically from your full name for this workspace. Do not forget this Account ID."
                    />

                    <x-ui.form.input
                        name="phone"
                        label="Phone Number"
                        hint="Use a login phone number you will remember. Password recovery depends on this number."
                    />

                    <x-ui.form.select name="role" label="Register As" required>
                        <option value="manager" {{ old('role', 'manager') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="accountant" {{ old('role') === 'accountant' ? 'selected' : '' }}>Accountant</option>
                    </x-ui.form.select>

                    <x-ui.form.input name="password" type="password" label="Password" required />

                    <x-ui.form.input name="password_confirmation" type="password" label="Confirm Password" required />

                    <div class="d-grid gap-2">
                        <x-ui.button type="submit" variant="primary">Register</x-ui.button>
                        <x-ui.button variant="outline-secondary" :href="$registrationCompany ? route('companies.portal', $registrationCompany->slug) : url('/')">Home</x-ui.button>
                        <x-ui.button variant="ghost" :href="$registrationCompany ? route('companies.login', $registrationCompany->slug) : route('login')">Already have an account? Login</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>

<x-ui.modal id="registrationEmailHelpModal" title="About the account ID to use">
    <p>You do not have to use a real external email address for this account.</p>
    <p>
        Use an account ID that matches the HOWSEY system,
        such as <strong>{{ $registrationAccountExample }}</strong>.
        This helps keep account profiles organized within the system.
    </p>
    <p>This account ID is safe to use here and is not meant for anything unusual or outside the normal operation of the HOWSEY platform.</p>
    <p class="mb-0">All users are advised to keep their login details safe and well stored.</p>
</x-ui.modal>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const accountDomain = @json($registrationAccountDomain);

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

        function updateGeneratedEmail() {
            emailInput.value = generateEmailFromName(nameInput.value);
        }

        nameInput.addEventListener('input', updateGeneratedEmail);
        updateGeneratedEmail();
    });
</script>
@endpush
