@extends('layouts.app')

@section('content')
<x-ui.page-header title="Add Building" />

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Building Details">
            <form action="{{ route('buildings.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <x-ui.form.input name="name" label="Building Name" required maxlength="150" />

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="total_units" type="number" label="Total Units" required min="1"
                            hint="Total number of units to create automatically." />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input name="total_floors" type="number" label="Total Floors" required min="1" />
                    </div>
                </div>

                <x-ui.form.input name="address_line1" label="Address Line 1" required maxlength="200" />

                <x-ui.form.input name="address_line2" label="Address Line 2" maxlength="200" />

                <x-ui.form.file name="image" label="Building Image" accept="image/*"
                    hint="Supported formats: JPG, PNG, GIF, WEBP. Max size: 4MB." />

                <div class="row">
                    <div class="col-md-5">
                        <x-ui.form.input name="city" label="City" required maxlength="100" />
                    </div>
                    <div class="col-md-4">
                        <x-ui.form.input name="state" label="State" required maxlength="100" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input name="postal_code" label="Postal Code" maxlength="20" />
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="reset" variant="ghost">Reset</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Create Building</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
