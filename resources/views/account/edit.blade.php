@extends('layouts.app')

@section('content')
@php
    $accountIssuedTimestamp = \App\Support\AccountIdentityService::timestampFor($user);
@endphp
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Account Settings</h1>
</div>

@if($user->isTenant())
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
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
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Profile Details</h5>
            </div>
            <div class="card-body">
                @if($user->isTenant())
                    <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
                        <label for="profile_image" class="form-label fw-semibold">Upload Profile Image</label>
                        @if($tenantProfileImageAvailable)
                            <form method="POST" action="{{ route('account.update-profile-image') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <input type="file" class="form-control @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image" accept="image/*" required>
                                <div class="form-text">Choose a clear passport-style image for your tenant account.</div>
                                @error('profile_image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <button type="submit" class="btn btn-primary mt-3">{{ $tenant?->profile_image ? 'Change Image' : 'Upload Image' }}</button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                Profile image uploads will work after the latest database update is installed.
                            </div>
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
                        <span class="badge bg-secondary">{{ ucfirst($user->role->name ?? 'user') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Change Phone Number</h5>
            </div>
            <div class="card-body">
                @if(! $phoneColumnAvailable)
                    <div class="alert alert-warning mb-0">
                        Phone number updates will work after the latest database migration is installed.
                    </div>
                @else
                    <form method="POST" action="{{ route('account.update-phone') }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Update Phone Number</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('account.update-password') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
