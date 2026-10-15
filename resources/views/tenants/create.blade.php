@extends('layouts.app')

@section('content')
@php
    $resolvedSelectedBuildingId = old('building_id', $selectedBuildingId ?? null);
@endphp
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Register Tenant</h1>

</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Tenant Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tenants.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image" accept="image/*">
                        @error('profile_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="150">
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Account ID</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" maxlength="150" readonly>
                            <div class="form-text">Generated automatically from the tenant name. Make sure the tenant keeps this Account ID.</div>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" maxlength="50">
                            <div class="form-text">Use a login phone number the tenant will remember. Password recovery depends on this number.</div>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="building_id" class="form-label">Select Building <span class="text-danger">*</span></label>
                        <select class="form-select @error('building_id') is-invalid @enderror" id="building_id" name="building_id" required>
                            <option value="">-- Select Building --</option>
                            @foreach($buildings as $building)
                                <option
                                    value="{{ $building->id }}"
                                    data-units="{{ $building->units_count }}"
                                    data-occupied="{{ $building->occupied_units_count }}"
                                    {{ (string) $resolvedSelectedBuildingId === (string) $building->id ? 'selected' : '' }}
                                >
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="occupancyAlert" class="alert alert-warning mt-2 mb-0 d-none" role="alert">
                            This building is currently at 100% occupancy. You cannot add another active tenant until a unit becomes available.
                        </div>
                        <div id="occupancyInfo" class="form-text text-muted"></div>
                        @error('building_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="unit_number" class="form-label">Unit Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('unit_number') is-invalid @enderror" id="unit_number" name="unit_number" value="{{ old('unit_number') }}" placeholder="e.g. 101, A2" required>
                        @error('unit_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="move_in_date" class="form-label">Move In Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('move_in_date') is-invalid @enderror" id="move_in_date" name="move_in_date" value="{{ old('move_in_date', date('Y-m-d')) }}" required>
                            @error('move_in_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4 pt-2">
                                <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    Set as Active Tenant
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-light me-md-2">Reset</button>
                        <button type="submit" class="btn btn-primary">Register Tenant</button>
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
        const accountDomain = @json(\App\Support\AutoEmailGenerator::domainForCompany($currentCompany ?? null));
        const fullNameInput = document.getElementById('full_name');
        const emailInput = document.getElementById('email');
        const buildingSelect = document.getElementById('building_id');
        const occupancyAlert = document.getElementById('occupancyAlert');
        const occupancyInfo = document.getElementById('occupancyInfo');

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
            if (!emailInput) {
                return;
            }

            emailInput.value = generateEmailFromName(fullNameInput.value);
        }

        function updateOccupancyNotice() {
            const option = buildingSelect.options[buildingSelect.selectedIndex];
            const units = Number(option?.dataset.units || 0);
            const occupied = Number(option?.dataset.occupied || 0);

            if (! option || ! option.value) {
                occupancyAlert.classList.add('d-none');
                occupancyInfo.textContent = '';
                return;
            }

            occupancyInfo.textContent = `Occupancy: ${occupied} of ${units} unit${units === 1 ? '' : 's'} occupied.`;

            if (units > 0 && occupied >= units) {
                occupancyAlert.classList.remove('d-none');
            } else {
                occupancyAlert.classList.add('d-none');
            }
        }

        if (fullNameInput) {
            fullNameInput.addEventListener('input', updateGeneratedEmail);
            updateGeneratedEmail();
        }

        buildingSelect.addEventListener('change', updateOccupancyNotice);
        updateOccupancyNotice();
    });
</script>
@endpush
