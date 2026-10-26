@extends('layouts.app')

@section('content')
<x-ui.page-header title="Create Rental Agreement" />

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Agreement Details">
            @if(!($rentDurationSupported ?? false))
                <x-ui.alert tone="warning" class="mb-3">
                    Rent duration is not yet available on this deployment. Run the `rent_duration` migration before creating agreements so Daily, Weekly, Monthly, and Annually can be saved correctly.
                </x-ui.alert>
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
                        <x-ui.form.select name="tenant_id" required placeholder="Select Tenant"
                            hint="Select a tenant from the list. Use the search box or building filter to narrow down options.">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                        data-unit-id="{{ $tenant->unit_id }}"
                                        data-building-id="{{ $tenant->unit ? $tenant->unit->building_id : '' }}"
                                        {{ (old('tenant_id') == $tenant->id || (isset($selectedTenantId) && $selectedTenantId == $tenant->id)) ? 'selected' : '' }}>
                                    {{ $tenant->full_name }}
                                </option>
                            @endforeach
                        </x-ui.form.select>
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="unit_id" label="Unit" required placeholder="Select Unit">
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" {{ (old('unit_id') == $unit->id || (isset($selectedUnitId) && $selectedUnitId == $unit->id)) ? 'selected' : '' }}>
                                    {{ $unit->building->name }} - Unit {{ $unit->unit_number }}
                                </option>
                            @endforeach
                        </x-ui.form.select>
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

                    <div class="col-md-6">
                        <x-ui.form.select name="rent_duration" label="Rent Duration" required :value="'annually'"
                            :options="$rentDurationOptions"
                            hint="Choose whether this rent amount is charged daily, weekly, monthly, or annually." />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="start_date" type="date" label="Start Date" required :value="date('Y-m-d')" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="end_date" type="date" label="End Date" hint="Leave blank for an ongoing agreement." />
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="reset" variant="ghost">Reset</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Create Agreement</x-ui.button>
                </div>
            </form>
        </x-ui.card>
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
