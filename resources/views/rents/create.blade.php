@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create Rental Agreement</h1>

</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Agreement Details</h5>
            </div>
            <div class="card-body">
                @if(!($rentDurationSupported ?? false))
                    <div class="alert alert-warning">
                        Rent duration is not yet available on this deployment. Run the `rent_duration` migration before creating agreements so Daily, Weekly, Monthly, and Annually can be saved correctly.
                    </div>
                @endif
                <form action="{{ route('rents.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="building_filter" class="form-label">Filter by Building (Optional)</label>
                            <select class="form-select" id="building_filter">
                                <option value="">-- All Buildings --</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                            <input type="text" id="tenant_search" class="form-control mb-2" placeholder="Type to filter tenants...">
                            <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id" required>
                                <option value="">Select Tenant</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" 
                                            data-unit-id="{{ $tenant->unit_id }}" 
                                            data-building-id="{{ $tenant->unit ? $tenant->unit->building_id : '' }}"
                                            {{ (old('tenant_id') == $tenant->id || (isset($selectedTenantId) && $selectedTenantId == $tenant->id)) ? 'selected' : '' }}>
                                        {{ $tenant->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Select a tenant from the list. Use the search box or building filter to narrow down options.</div>
                            @error('tenant_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="unit_id" class="form-label">Unit <span class="text-danger">*</span></label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" {{ (old('unit_id') == $unit->id || (isset($selectedUnitId) && $selectedUnitId == $unit->id)) ? 'selected' : '' }}>
                                        {{ $unit->building->name }} - Unit {{ $unit->unit_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="annual_amount" class="form-label">Rent Amount (₦) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" class="form-control @error('annual_amount') is-invalid @enderror" id="annual_amount" name="annual_amount" value="{{ old('annual_amount') }}" required min="0">
                            </div>
                            @error('annual_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="rent_duration" class="form-label">Rent Duration <span class="text-danger">*</span></label>
                            <select class="form-select @error('rent_duration') is-invalid @enderror" id="rent_duration" name="rent_duration" required>
                                @foreach($rentDurationOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('rent_duration', 'annually') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Choose whether this rent amount is charged daily, weekly, monthly, or annually.</div>
                            @error('rent_duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                            <div class="form-text">Leave blank for an ongoing agreement.</div>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-light me-md-2">Reset</button>
                        <button type="submit" class="btn btn-primary">Create Agreement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const buildingSelect = document.getElementById('building_filter');
        const unitSelect = document.getElementById('unit_id');
        const tenantSelect = document.getElementById('tenant_id');
        const tenantSearch = document.getElementById('tenant_search');
        
        // Create a reliable source of truth for all units using a JavaScript array
        const allUnits = [
            @foreach($units as $unit)
            {
                id: "{{ $unit->id }}",
                building_id: "{{ $unit->building_id }}",
                name: "{{ $unit->building->name }} - Unit {{ $unit->unit_number }}",
                selected: {{ (old('unit_id') == $unit->id || (isset($selectedUnitId) && $selectedUnitId == $unit->id)) ? 'true' : 'false' }}
            },
            @endforeach
        ];

        function updateUnitOptions(resetValue = true) {
            const selectedBuildingId = buildingSelect.value;
            const currentUnitValue = unitSelect.value;
            
            // Clear current options
            unitSelect.innerHTML = '<option value="">Select Unit</option>';
            
            // Filter units
            const filteredUnits = selectedBuildingId 
                ? allUnits.filter(unit => unit.building_id == selectedBuildingId)
                : allUnits;
            
            // Populate select with filtered units
            filteredUnits.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.name;
                option.setAttribute('data-building-id', unit.building_id);
                
                // Maintain selection logic
                if (!resetValue && (unit.id === currentUnitValue || unit.selected)) {
                    option.selected = true;
                }
                
                unitSelect.appendChild(option);
            });
            
            // If reset is requested or current value is invalid in new list, clear selection
            if (resetValue) {
                unitSelect.value = "";
            } else if (currentUnitValue && !filteredUnits.some(u => u.id === currentUnitValue)) {
                unitSelect.value = "";
            }
        }

        buildingSelect.addEventListener('change', function() {
            updateUnitOptions(true);
            filterTenants();
        });

        // Tenant Filtering Logic
        // Store original options to ensure we can always restore them
        const originalTenantOptions = Array.from(tenantSelect.options);

        function filterTenants() {
            const buildingId = buildingSelect.value;
            const searchText = tenantSearch.value.toLowerCase();
            
            // We can't easily hide options in all browsers using display:none.
            // A more robust way is to remove/add them or use the hidden attribute.
            // Using 'hidden' attribute works in modern browsers.
            
            originalTenantOptions.forEach(option => {
                if (option.value === "") return; // Skip placeholder

                const tenantBuildingId = option.getAttribute('data-building-id');
                // If tenant has no building (no unit), show them only if no building filter is active
                // OR if you want to allow assigning new tenants, maybe show them always? 
                // Requirement: "only display the tenants from the selected building" -> strict.
                
                // Logic: 
                // 1. If building selected: Match building ID. (Tenants without building are hidden).
                // 2. If no building selected: Show all.
                
                const matchesBuilding = !buildingId || (tenantBuildingId == buildingId);
                const matchesText = option.text.toLowerCase().includes(searchText);

                if (matchesBuilding && matchesText) {
                    option.hidden = false;
                    option.disabled = false;
                    option.style.display = "";
                } else {
                    option.hidden = true;
                    option.disabled = true; // Ensure it can't be selected by keyboard
                    option.style.display = "none";
                }
            });
            
            // Check if selected value is now invalid/hidden
            const selectedOption = tenantSelect.options[tenantSelect.selectedIndex];
            if (selectedOption && selectedOption.hidden) {
                tenantSelect.value = "";
            }
        }

        tenantSearch.addEventListener('input', filterTenants);

        // Initialize filtering on load
        filterTenants();

        // Initialize state
        if (unitSelect.value) {
            // If we have a selected unit, find its building
            const initialUnitId = unitSelect.value;
            const initialUnit = allUnits.find(u => u.id == initialUnitId);
            
            if (initialUnit) {
                buildingSelect.value = initialUnit.building_id;
                // Re-run filter to show only this building's units, but keep selection
                updateUnitOptions(false);
                unitSelect.value = initialUnitId;
            }
        }

        // Auto-select Unit when Tenant is selected
        tenantSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const assignedUnitId = selectedOption.getAttribute('data-unit-id');
            
            if (assignedUnitId) {
                // Find the unit in our data
                const assignedUnit = allUnits.find(u => u.id == assignedUnitId);
                
                if (assignedUnit) {
                    console.log('Auto-selecting unit:', assignedUnit);
                    // Set the building filter first
                    buildingSelect.value = assignedUnit.building_id;
                    
                    // Update unit options to match this building
                    // Pass false to prevent clearing the selection logic inside the function
                    updateUnitOptions(false);
                    
                    // Explicitly set the unit value after options are rebuilt
                    unitSelect.value = assignedUnitId;
                } else {
                    console.warn('Assigned unit ID ' + assignedUnitId + ' not found in available units list.');
                }
            }
        });

    });
</script>
@endpush
