@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Tenant</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-info text-white">
            <i class="bi bi-eye"></i> View Profile
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Edit: {{ $tenant->full_name }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tenants.update', $tenant) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image" accept="image/*">
                        @error('profile_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($tenant->profile_image)
                            <div class="mt-2">
                                <small class="text-muted">Current Image:</small><br>
                                <img src="{{ asset('storage/' . $tenant->profile_image) }}" alt="Current Image" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name', $tenant->full_name) }}" required maxlength="150">
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Account ID</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $tenant->email) }}" maxlength="150">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $tenant->phone) }}" maxlength="50">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="unit_id" class="form-label">Assign Unit <span class="text-danger">*</span></label>
                        <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (old('unit_id', $tenant->unit_id) == $unit->id) ? 'selected' : '' }}>
                                    {{ $unit->building->name }} - Unit {{ $unit->unit_number }} ({{ $unit->status }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Changing the unit will update availability status for both old and new units.</div>
                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="move_in_date" class="form-label">Move In Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('move_in_date') is-invalid @enderror" id="move_in_date" name="move_in_date" value="{{ old('move_in_date', $tenant->move_in_date ? $tenant->move_in_date->format('Y-m-d') : '') }}" required>
                            @error('move_in_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="move_out_date" class="form-label">Move Out Date</label>
                            <input type="date" class="form-control @error('move_out_date') is-invalid @enderror" id="move_out_date" name="move_out_date" value="{{ old('move_out_date', $tenant->move_out_date ? $tenant->move_out_date->format('Y-m-d') : '') }}">
                            @error('move_out_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $tenant->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active Tenant
                            </label>
                            <div class="form-text">Unchecking this marks the tenant as former/inactive and frees up the unit.</div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
