@extends('layouts.app')

@section('content')
@php
    $workflowEnabled = old('unit_workflow_enabled');
    $financialWorkflowEnabled = old('create_financial_entry', $canCreatePaymentWorkflow ? '1' : null);
    $activeRent = $unit->rents->where('status', 'ACTIVE')->first();
@endphp
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Unit</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('units.show', $unit) }}" class="btn btn-sm btn-info text-white">
            <i class="bi bi-eye"></i> View Details
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Edit: {{ $unit->unit_number }} ({{ $unit->building->name }})</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('units.update', $unit) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="building_id" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select @error('building_id') is-invalid @enderror" id="building_id" name="building_id" required>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" {{ (old('building_id', $unit->building_id) == $building->id) ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unit_number" class="form-label">Unit Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit_number') is-invalid @enderror" id="unit_number" name="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" required maxlength="50">
                            @error('unit_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="floor" class="form-label">Floor</label>
                            <input type="text" class="form-control @error('floor') is-invalid @enderror" id="floor" name="floor" value="{{ old('floor', $unit->floor) }}" maxlength="20">
                            @error('floor')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="bedrooms" class="form-label">Bedrooms <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('bedrooms') is-invalid @enderror" id="bedrooms" name="bedrooms" value="{{ old('bedrooms', $unit->bedrooms) }}" required min="0">
                            @error('bedrooms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="bathrooms" class="form-label">Bathrooms <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('bathrooms') is-invalid @enderror" id="bathrooms" name="bathrooms" value="{{ old('bathrooms', $unit->bathrooms) }}" required min="0">
                            @error('bathrooms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="AVAILABLE" {{ old('status', $unit->status) == 'AVAILABLE' ? 'selected' : '' }}>Available</option>
                                <option value="OCCUPIED" {{ old('status', $unit->status) == 'OCCUPIED' ? 'selected' : '' }}>Occupied</option>
                                <option value="MAINTENANCE" {{ old('status', $unit->status) == 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if($canCreateTenantWorkflow)
                        <div class="border rounded-3 p-3 p-md-4 mt-3 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h5 class="mb-1">Fill Tenant, Lease, and Financial Tabs from Edit Unit</h5>
                                    <p class="text-muted mb-0 small">
                                        Reuse this existing unit record and populate the linked Tenant, Lease Agreement, and Financials tabs from this page.
                                    </p>
                                </div>
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="unit_workflow_enabled"
                                        name="unit_workflow_enabled"
                                        value="1"
                                        {{ $workflowEnabled ? 'checked' : '' }}
                                        {{ $hasActiveLease ? 'disabled' : '' }}
                                    >
                                    <label class="form-check-label fw-semibold" for="unit_workflow_enabled">Enable workflow</label>
                                </div>
                            </div>

                            @if(!($rentDurationSupported ?? false))
                                <div class="alert alert-warning mt-3 mb-0">
                                    Automated unit onboarding needs the rent duration migration to be available before tenant and lease records can be created from this screen.
                                </div>
                            @endif

                            @if($hasActiveLease && $activeRent)
                                <div class="alert alert-info mt-3 mb-0">
                                    This unit already has an active lease for <strong>{{ $activeRent->tenant->full_name }}</strong>. Finish or terminate that lease before using Edit Unit to create a new tenant workflow for this unit.
                                </div>
                            @endif

                            <div id="unitWorkflowFields" class="mt-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h6 class="text-uppercase text-muted mb-1">Tenant Details</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tenant_full_name" class="form-label">Tenant Full Name</label>
                                        <input type="text" class="form-control @error('tenant_full_name') is-invalid @enderror" id="tenant_full_name" name="tenant_full_name" value="{{ old('tenant_full_name') }}" maxlength="150" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        <div class="form-text">The tenant account ID will be auto-generated from this full name.</div>
                                        @error('tenant_full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tenant_phone" class="form-label">Tenant Phone</label>
                                        <input type="text" class="form-control @error('tenant_phone') is-invalid @enderror" id="tenant_phone" name="tenant_phone" value="{{ old('tenant_phone') }}" maxlength="50" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        @error('tenant_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tenant_move_in_date" class="form-label">Move-In Date</label>
                                        <input type="date" class="form-control @error('tenant_move_in_date') is-invalid @enderror" id="tenant_move_in_date" name="tenant_move_in_date" value="{{ old('tenant_move_in_date', date('Y-m-d')) }}" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        @error('tenant_move_in_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 pt-2">
                                        <h6 class="text-uppercase text-muted mb-1">Lease Agreement</h6>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="lease_annual_amount" class="form-label">Rent Amount (₦)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₦</span>
                                            <input type="number" step="0.01" class="form-control @error('lease_annual_amount') is-invalid @enderror" id="lease_annual_amount" name="lease_annual_amount" value="{{ old('lease_annual_amount') }}" min="0" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        </div>
                                        @error('lease_annual_amount')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="lease_rent_duration" class="form-label">Rent Duration</label>
                                        <select class="form-select @error('lease_rent_duration') is-invalid @enderror" id="lease_rent_duration" name="lease_rent_duration" {{ $hasActiveLease ? 'disabled' : '' }}>
                                            @foreach($rentDurationOptions as $value => $label)
                                                <option value="{{ $value }}" {{ old('lease_rent_duration', 'annually') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('lease_rent_duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="lease_start_date" class="form-label">Lease Start Date</label>
                                        <input type="date" class="form-control @error('lease_start_date') is-invalid @enderror" id="lease_start_date" name="lease_start_date" value="{{ old('lease_start_date', date('Y-m-d')) }}" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        @error('lease_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="lease_end_date" class="form-label">Lease End Date</label>
                                        <input type="date" class="form-control @error('lease_end_date') is-invalid @enderror" id="lease_end_date" name="lease_end_date" value="{{ old('lease_end_date') }}" {{ $hasActiveLease ? 'disabled' : '' }}>
                                        <div class="form-text">Leave blank for an ongoing lease.</div>
                                        @error('lease_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @if($canCreatePaymentWorkflow)
                                        <div class="col-12 pt-2">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                                <div>
                                                    <h6 class="text-uppercase text-muted mb-1">Financials</h6>
                                                    <p class="text-muted small mb-0">Optionally create the first financial record so the Financials tab is populated immediately.</p>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        role="switch"
                                                        id="create_financial_entry"
                                                        name="create_financial_entry"
                                                        value="1"
                                                        {{ $financialWorkflowEnabled ? 'checked' : '' }}
                                                        {{ $hasActiveLease ? 'disabled' : '' }}
                                                    >
                                                    <label class="form-check-label fw-semibold" for="create_financial_entry">Create financial record</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="financialWorkflowFields" class="contents">
                                            <div class="col-md-4">
                                                <label for="financial_amount" class="form-label">Amount (₦)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₦</span>
                                                    <input type="number" step="0.01" class="form-control @error('financial_amount') is-invalid @enderror" id="financial_amount" name="financial_amount" value="{{ old('financial_amount') }}" min="0.01" {{ $hasActiveLease ? 'disabled' : '' }}>
                                                </div>
                                                @error('financial_amount')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="financial_status" class="form-label">Financial Status</label>
                                                <select class="form-select @error('financial_status') is-invalid @enderror" id="financial_status" name="financial_status" {{ $hasActiveLease ? 'disabled' : '' }}>
                                                    @foreach($paymentStatusOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ old('financial_status', 'PENDING') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('financial_status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="financial_payment_date" class="form-label">Financial Date</label>
                                                <input type="date" class="form-control @error('financial_payment_date') is-invalid @enderror" id="financial_payment_date" name="financial_payment_date" value="{{ old('financial_payment_date', date('Y-m-d')) }}" {{ $hasActiveLease ? 'disabled' : '' }}>
                                                @error('financial_payment_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label for="financial_payment_method" class="form-label">Payment Method</label>
                                                <select class="form-select @error('financial_payment_method') is-invalid @enderror" id="financial_payment_method" name="financial_payment_method" {{ $hasActiveLease ? 'disabled' : '' }}>
                                                    @foreach($paymentMethodOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ old('financial_payment_method', 'BANK_TRANSFER') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('financial_payment_method')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const workflowToggle = document.getElementById('unit_workflow_enabled');
    const workflowFields = document.getElementById('unitWorkflowFields');
    const financialToggle = document.getElementById('create_financial_entry');
    const financialFields = document.getElementById('financialWorkflowFields');
    const statusSelect = document.getElementById('status');
    const tenantMoveInDate = document.getElementById('tenant_move_in_date');
    const leaseStartDate = document.getElementById('lease_start_date');

    function toggleWorkflowFields() {
        if (!workflowToggle || !workflowFields) {
            return;
        }

        const enabled = workflowToggle.checked && !workflowToggle.disabled;
        workflowFields.style.display = enabled ? '' : 'none';

        if (statusSelect && enabled) {
            statusSelect.value = 'OCCUPIED';
        }

        toggleFinancialFields();
    }

    function toggleFinancialFields() {
        if (!financialToggle || !financialFields) {
            return;
        }

        const enabled = workflowToggle && workflowToggle.checked && !workflowToggle.disabled && financialToggle.checked && !financialToggle.disabled;
        financialFields.style.display = enabled ? '' : 'none';
    }

    function syncMoveInDate() {
        if (!tenantMoveInDate || !leaseStartDate) {
            return;
        }

        if (!tenantMoveInDate.value || tenantMoveInDate.dataset.autoSync !== 'manual') {
            tenantMoveInDate.value = leaseStartDate.value;
        }
    }

    if (tenantMoveInDate) {
        tenantMoveInDate.addEventListener('input', function () {
            tenantMoveInDate.dataset.autoSync = 'manual';
        });
    }

    if (leaseStartDate) {
        leaseStartDate.addEventListener('change', syncMoveInDate);
    }

    if (workflowToggle) {
        workflowToggle.addEventListener('change', toggleWorkflowFields);
    }

    if (financialToggle) {
        financialToggle.addEventListener('change', toggleFinancialFields);
    }

    syncMoveInDate();
    toggleWorkflowFields();
});
</script>
@endpush
