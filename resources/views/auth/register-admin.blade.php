@extends('layouts.app')

@section('content')
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <x-ui.card title="Admin Registration" icon="bi bi-shield-lock">
                <x-slot:actions>
                    @if(!empty($branding['logo_url']))
                        <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] ?? 'HOWSEY' }} logo" class="rounded bg-white p-1" style="width: 2rem; height: 2rem; object-fit: contain;">
                    @endif
                    <x-ui.icon-button label="Registration help" icon="bi bi-question-lg" data-bs-toggle="modal" data-bs-target="#adminRegistrationEmailHelpModal" />
                </x-slot:actions>

                <x-ui.alert tone="warning">
                    This area is restricted for administrative access only.
                </x-ui.alert>

                <form method="POST" action="{{ route('register.admin') }}">
                    @csrf

                    <x-ui.form.input name="name" label="Full Name" required autofocus />

                    <x-ui.form.input
                        name="email"
                        type="email"
                        label="Account ID"
                        readonly
                        hint="Generated automatically from the full name in the `e.lastname@howsey.site` format. Do not forget this Account ID."
                    />

                    <x-ui.form.input
                        name="phone"
                        label="Phone Number"
                        hint="Use a login phone number you will remember. Password recovery depends on this number."
                    />

                    <x-ui.form.input name="password" type="password" label="Password" required />

                    <x-ui.form.input name="password_confirmation" type="password" label="Confirm Password" required />

                    <div class="d-grid gap-2">
                        <x-ui.button type="submit" variant="primary">Register Admin</x-ui.button>
                        <x-ui.button variant="ghost" :href="route('login')">Back to Login</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>

<x-ui.modal id="adminRegistrationEmailHelpModal" title="About the account ID to use">
    <p>You do not have to use a real external email address for this account.</p>
    <p>Use an account ID that matches the system domain, such as <strong>username@howsey.site</strong>, so the account stays clearly tied to the HOWSEY platform.</p>
    <p>This account ID is safe to use here and is not meant for anything unusual or outside the ordinary operation of the system.</p>
    <p class="mb-0">All users are advised to keep their login details safe and well stored.</p>
</x-ui.modal>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');

        function generateEmailFromName(name) {
            const asciiName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
            const parts = asciiName.split(/\s+/).map(part => part.replace(/[^a-z0-9]/g, '')).filter(Boolean);

            if (parts.length === 0) {
                return '';
            }

            if (parts.length === 1) {
                return `${parts[0]}@howsey.site`;
            }

            return `${parts[0].charAt(0)}.${parts[parts.length - 1]}@howsey.site`;
        }

        function syncAccountId() {
            emailInput.value = generateEmailFromName(nameInput.value);
        }

        nameInput.addEventListener('input', syncAccountId);
        syncAccountId();
    });
</script>
@endpush
