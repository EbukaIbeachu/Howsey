@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Rental Agreement">
    <x-slot:actions>
        <x-ui.button variant="info" size="sm" icon="bi bi-eye" :href="route('rents.show', $rent)">View Details</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Agreement Details">
            @if(!($rentDurationSupported ?? false))
                <x-ui.alert tone="warning" class="mb-3">
                    Rent duration is not yet available on this deployment. Run the `rent_duration` migration before updating agreements so Daily, Weekly, Monthly, and Annually can be saved correctly.
                </x-ui.alert>
            @endif
            <form action="{{ route('rents.update', $rent) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.select name="tenant_id" label="Tenant" required placeholder="Select Tenant">
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $rent->tenant_id) == $tenant->id)>
                                    {{ $tenant->full_name }}
                                </option>
                            @endforeach
                        </x-ui.form.select>
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="unit_id" label="Unit" required placeholder="Select Unit">
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id', $rent->unit_id) == $unit->id)>
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
                            <input type="number" step="0.01" class="form-control @error('annual_amount') is-invalid @enderror" id="annual_amount" name="annual_amount" value="{{ old('annual_amount', $rent->annual_amount) }}" required min="0">
                        </div>
                        @error('annual_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="rent_duration" label="Rent Duration" required :value="$rent->rent_duration_value"
                            :options="$rentDurationOptions"
                            hint="Choose whether this rent amount is charged daily, weekly, monthly, or annually." />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="start_date" type="date" label="Start Date" required
                            :value="$rent->start_date ? $rent->start_date->format('Y-m-d') : ''" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="end_date" type="date" label="End Date"
                            :value="$rent->end_date ? $rent->end_date->format('Y-m-d') : ''"
                            hint="Leave blank for an ongoing agreement." />
                    </div>
                </div>

                <x-ui.form.select name="status" label="Status" :value="$rent->status"
                    :options="['ACTIVE' => 'Active', 'TERMINATED' => 'Terminated', 'EXPIRED' => 'Expired']" />

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="submit" variant="primary">Update Agreement</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
