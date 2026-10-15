@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">New Maintenance Request</h1>

</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('maintenance.store') }}" method="POST">
                    @csrf

                    @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                        <div class="mb-3">
                            <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                            <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->full_name }} ({{ $tenant->unit ? $tenant->unit->getFullAddressAttribute() : 'No Unit' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('tenant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit_id" class="form-label">Unit <span class="text-danger">*</span></label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->getFullAddressAttribute() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <div class="alert alert-info">
                            Creating request for your current unit.
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Issue Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="PLUMBING" {{ old('type') == 'PLUMBING' ? 'selected' : '' }}>Plumbing</option>
                                <option value="ELECTRICAL" {{ old('type') == 'ELECTRICAL' ? 'selected' : '' }}>Electrical</option>
                                <option value="HVAC" {{ old('type') == 'HVAC' ? 'selected' : '' }}>Heating/Cooling (HVAC)</option>
                                <option value="STRUCTURAL" {{ old('type') == 'STRUCTURAL' ? 'selected' : '' }}>Structural (Walls/Floors)</option>
                                <option value="APPLIANCE" {{ old('type') == 'APPLIANCE' ? 'selected' : '' }}>Appliance</option>
                                <option value="OTHER" {{ old('type') == 'OTHER' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                <option value="LOW" {{ old('priority') == 'LOW' ? 'selected' : '' }}>Low (Cosmetic/Minor)</option>
                                <option value="MEDIUM" {{ old('priority') == 'MEDIUM' ? 'selected' : '' }}>Medium (Standard)</option>
                                <option value="HIGH" {{ old('priority') == 'HIGH' ? 'selected' : '' }}>High (Urgent)</option>
                                <option value="EMERGENCY" {{ old('priority') == 'EMERGENCY' ? 'selected' : '' }}>Emergency (Safety/Damage Risk)</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title / Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required placeholder="e.g. Leaking faucet in kitchen">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Detailed Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required placeholder="Please describe the issue in detail...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
