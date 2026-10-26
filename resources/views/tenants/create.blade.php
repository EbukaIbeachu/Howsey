@extends('layouts.app')

@section('content')
@php
    $resolvedSelectedBuildingId = old('building_id', $selectedBuildingId ?? null);
@endphp

<x-ui.page-header title="Register Tenant" />

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Tenant Details">
            <form action="{{ route('tenants.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <x-ui.form.file name="profile_image" label="Profile Image" accept="image/*" />

                <x-ui.form.input name="full_name" label="Full Name" required maxlength="150" />

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="email" type="email" label="Account ID" maxlength="150" readonly
                            hint="Generated automatically from the tenant name. Make sure the tenant keeps this Account ID." />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="phone" label="Phone Number" maxlength="50"
                            hint="Use a login phone number the tenant will remember. Password recovery depends on this number." />
                    </div>
                </div>

                <x-ui.form.select name="building_id" label="Select Building" required placeholder="-- Select Building --">
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
                </x-ui.form.select>

                <x-ui.alert tone="warning" id="occupancyAlert" class="mt-2 mb-0 d-none">
                    This building is currently at 100% occupancy. You cannot add another active tenant until a unit becomes available.
                </x-ui.alert>
                <div id="occupancyInfo" class="form-text text-muted"></div>

                <x-ui.form.input name="unit_number" label="Unit Number" required placeholder="e.g. 101, A2" />

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="move_in_date" type="date" label="Move In Date" required :value="date('Y-m-d')" />
                    </div>
                    <div class="col-md-6">
                        <div class="mt-4 pt-2">
                            <x-ui.form.checkbox name="active" label="Set as Active Tenant" :checked="true" />
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="reset" variant="ghost">Reset</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Register Tenant</x-ui.button>
                </div>
            </form>
        </x-ui.card>
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
