@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Tenant" :subtitle="$tenant->full_name">
    <x-slot:actions>
        <x-ui.button variant="info" size="sm" icon="bi bi-eye" :href="route('tenants.show', $tenant)">View Profile</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Edit Tenant">
            <form action="{{ route('tenants.update', $tenant) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <x-ui.form.file name="profile_image" label="Profile Image" accept="image/*" />
                @if($tenant->profile_image)
                    <div class="mb-3">
                        <small class="text-muted">Current Image:</small><br>
                        <img src="{{ asset('storage/' . $tenant->profile_image) }}" alt="Current Image" class="img-thumbnail" style="max-height: 100px;">
                    </div>
                @endif

                <x-ui.form.input name="full_name" label="Full Name" required maxlength="150" :value="$tenant->full_name" />

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="email" type="email" label="Account ID" maxlength="150" :value="$tenant->email" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="phone" label="Phone Number" maxlength="50" :value="$tenant->phone" />
                    </div>
                </div>

                <x-ui.form.select name="unit_id" label="Assign Unit" required
                    hint="Changing the unit will update availability status for both old and new units.">
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id', $tenant->unit_id) == $unit->id)>
                            {{ $unit->building->name }} - Unit {{ $unit->unit_number }} ({{ $unit->status }})
                        </option>
                    @endforeach
                </x-ui.form.select>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="move_in_date" type="date" label="Move In Date" required
                            :value="$tenant->move_in_date ? $tenant->move_in_date->format('Y-m-d') : ''" />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="move_out_date" type="date" label="Move Out Date"
                            :value="$tenant->move_out_date ? $tenant->move_out_date->format('Y-m-d') : ''" />
                    </div>
                </div>

                <x-ui.form.checkbox name="active" label="Active Tenant" :checked="$tenant->active"
                    hint="Unchecking this marks the tenant as former/inactive and frees up the unit." />

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="submit" variant="primary">Update Tenant</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
