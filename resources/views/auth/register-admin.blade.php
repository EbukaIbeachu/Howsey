@extends('layouts.app')

@section('content')
<div class="container">
    <style>
        .auth-surface .card {
            border: 1px solid rgba(var(--brand-primary-rgb), 0.16);
            box-shadow: 0 1rem 2rem rgba(var(--brand-primary-rgb), 0.10) !important;
        }
        .auth-surface .form-control:focus,
        .auth-surface .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.18);
        }
        .auth-surface .btn-link {
            color: var(--primary-color);
        }
        .auth-surface .btn-link:hover,
        .auth-surface .btn-link:focus {
            color: var(--primary-hover);
        }
    </style>
    <div class="row justify-content-center mt-5 auth-surface">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 d-flex align-items-center gap-2">
                        @if(!empty($branding['logo_url']))
                            <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] ?? 'HOWSEY' }} logo" class="rounded bg-white p-1" style="width: 2rem; height: 2rem; object-fit: contain;">
                        @else
                            <i class="bi bi-shield-lock me-2"></i>
                        @endif
                        Admin Registration
                    </h4>
                    <button type="button" class="btn btn-sm btn-light rounded-circle fw-bold" data-bs-toggle="modal" data-bs-target="#adminRegistrationEmailHelpModal" aria-label="Registration help">?</button>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <small>This area is restricted for administrative access only.</small>
                    </div>

                    <form method="POST" action="{{ route('register.admin') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Account ID</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" readonly>
                            <div class="form-text">Generated automatically from the full name in the `e.lastname@howsey.site` format. Do not forget this Account ID.</div>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                            <div class="form-text">Use a login phone number you will remember. Password recovery depends on this number.</div>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register Admin</button>
                            <a href="{{ route('login') }}" class="btn btn-link text-center">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminRegistrationEmailHelpModal" tabindex="-1" aria-labelledby="adminRegistrationEmailHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminRegistrationEmailHelpModalLabel">About the account ID to use</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You do not have to use a real external email address for this account.</p>
                <p>Use an account ID that matches the system domain, such as <strong>username@howsey.site</strong>, so the account stays clearly tied to the HOWSEY platform.</p>
                <p>This account ID is safe to use here and is not meant for anything unusual or outside the ordinary operation of the system.</p>
                <p class="mb-0">All users are advised to keep their login details safe and well stored.</p>
            </div>
        </div>
    </div>
</div>
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
