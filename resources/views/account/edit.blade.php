@extends('layouts.app')

@section('content')
@php
    $accountIssuedTimestamp = \App\Support\AccountIdentityService::timestampFor($user);
@endphp

<x-ui.page-header title="Account Settings" />

@if($user->isTenant())
    <x-ui.card class="mb-4">
        <div class="d-flex flex-column flex-md-row align-items-center gap-3">
            @if($tenant?->profile_image)
                <img src="{{ asset('storage/' . $tenant->profile_image) }}" alt="{{ $tenant->full_name }}" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 120px; height: 120px;">
            @else
                <div class="border rounded-circle d-flex align-items-center justify-content-center bg-light text-primary flex-shrink-0" style="width: 120px; height: 120px;">
                    <i class="bi bi-person fs-1"></i>
                </div>
            @endif
            <div class="text-center text-md-start">
                <div class="text-muted small text-uppercase">Tenant Account</div>
                <h2 class="h3 mb-1">{{ $tenant?->full_name ?? $user->name }}</h2>
                <p class="text-muted mb-0">Your tenant profile image and account details appear here.</p>
            </div>
        </div>
    </x-ui.card>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <x-ui.card title="Profile Details" class="h-100">
            @if($user->isTenant())
                <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
                    @if($tenantProfileImageAvailable)
                        <form method="POST" action="{{ route('account.update-profile-image') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            <x-ui.form.file
                                name="profile_image"
                                id="profile_image"
                                label="Upload Profile Image"
                                accept="image/*"
                                hint="Choose a clear passport-style image for your tenant account."
                                required
                            />
                            <x-ui.button type="submit" variant="primary" class="mt-3">{{ $tenant?->profile_image ? 'Change Image' : 'Upload Image' }}</x-ui.button>
                        </form>
                    @else
                        <x-ui.alert tone="warning" class="mb-0">
                            Profile image uploads will work after the latest database update is installed.
                        </x-ui.alert>
                    @endif
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label text-muted">Name</label>
                <div class="fw-semibold">{{ $tenant?->full_name ?? $user->name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Account ID</label>
                <div class="fw-semibold">{{ $user->email }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label text-muted">Timestamp</label>
                <div class="fw-semibold">{{ $accountIssuedTimestamp }}</div>
            </div>
            <div class="mb-0">
                <label class="form-label text-muted">Role</label>
                <div>
                    <x-ui.status-badge tone="neutral" :label="ucfirst($user->role->name ?? 'user')" />
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="col-lg-7">
        <x-ui.card title="Change Phone Number" class="mb-4">
            @if(! $phoneColumnAvailable)
                <x-ui.alert tone="warning" class="mb-0">
                    Phone number updates will work after the latest database migration is installed.
                </x-ui.alert>
            @else
                <form method="POST" action="{{ route('account.update-phone') }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.form.input name="phone" label="Phone Number" :value="$user->phone" required />
                    <x-ui.button type="submit" variant="primary">Update Phone Number</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        <x-ui.card title="Change Password">
            <form method="POST" action="{{ route('account.update-password') }}">
                @csrf
                @method('PATCH')
                <x-ui.form.input name="current_password" type="password" label="Current Password" required />
                <x-ui.form.input name="password" type="password" label="New Password" required />
                <x-ui.form.input name="password_confirmation" type="password" label="Confirm New Password" required />
                <x-ui.button type="submit" variant="primary">Update Password</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
