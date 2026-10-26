@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Building" :subtitle="$building->name">
    <x-slot:actions>
        <x-ui.button variant="info" size="sm" icon="bi bi-eye" :href="route('buildings.show', $building)">View Details</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card :title="'Edit: '.$building->name">
            <form action="{{ route('buildings.update', $building) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <x-ui.form.input name="name" label="Building Name" required maxlength="150"
                    :value="$building->name" />

                <x-ui.form.input name="address_line1" label="Address Line 1" required maxlength="200"
                    :value="$building->address_line1" />

                <x-ui.form.input name="address_line2" label="Address Line 2" maxlength="200"
                    :value="$building->address_line2" />

                <div class="row">
                    <div class="col-md-5">
                        <x-ui.form.input name="city" label="City" required maxlength="100" :value="$building->city" />
                    </div>
                    <div class="col-md-4">
                        <x-ui.form.input name="state" label="State" required maxlength="100" :value="$building->state" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input name="postal_code" label="Postal Code" maxlength="20" :value="$building->postal_code" />
                    </div>
                </div>

                <x-ui.form.file name="image" label="Building Image" accept="image/*"
                    hint="Supported formats: JPG, PNG, GIF, WEBP. Max size: 4MB." />
                @if($building->image_path)
                    <div class="mb-3">
                        <small class="text-muted">Current Image:</small><br>
                        <img src="{{ asset('storage/' . $building->image_path) }}" alt="Current Image" class="img-thumbnail" style="max-height: 100px;">
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="total_units" type="number" label="Total Units (Capacity)" required min="1"
                            :value="$building->total_units"
                            hint="Updating this will update the building's capacity. New units will be generated if increased." />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="total_floors" type="number" label="Total Floors" required min="1"
                            :value="$building->total_floors" />
                    </div>
                </div>

                @if(Auth::user()->isAdmin() && $supportsManagerAssignment)
                    <x-ui.form.select name="manager_id" label="Assigned Manager" placeholder="None">
                        @foreach($managers as $manager)
                            <option value="{{ $manager->id }}" @selected(old('manager_id', $building->manager_id) == $manager->id)>
                                {{ $manager->name }} ({{ $manager->email }})
                            </option>
                        @endforeach
                    </x-ui.form.select>
                @elseif(Auth::user()->isAdmin())
                    <x-ui.alert tone="warning">
                        Manager assignment is not available on this server yet. Run the latest building manager migration, then this field will appear here.
                    </x-ui.alert>
                @endif

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="submit" variant="primary">Update Building</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
