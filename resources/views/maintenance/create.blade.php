@extends('layouts.app')

@section('content')
<x-ui.page-header title="New Maintenance Request" />

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card>
            <form action="{{ route('maintenance.store') }}" method="POST">
                @csrf

                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                    <x-ui.form.select name="tenant_id" label="Tenant" required placeholder="Select Tenant">
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                {{ $tenant->full_name }} ({{ $tenant->unit ? $tenant->unit->getFullAddressAttribute() : 'No Unit' }})
                            </option>
                        @endforeach
                    </x-ui.form.select>

                    <x-ui.form.select name="unit_id" label="Unit" required placeholder="Select Unit">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                {{ $unit->getFullAddressAttribute() }}
                            </option>
                        @endforeach
                    </x-ui.form.select>
                @else
                    <x-ui.alert tone="info" class="mb-3">
                        Creating request for your current unit.
                    </x-ui.alert>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.select name="type" label="Issue Type" required :value="old('type')" :options="[
                            'PLUMBING' => 'Plumbing',
                            'ELECTRICAL' => 'Electrical',
                            'HVAC' => 'Heating/Cooling (HVAC)',
                            'STRUCTURAL' => 'Structural (Walls/Floors)',
                            'APPLIANCE' => 'Appliance',
                            'OTHER' => 'Other',
                        ]" />
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="priority" label="Priority" required :value="old('priority')" :options="[
                            'LOW' => 'Low (Cosmetic/Minor)',
                            'MEDIUM' => 'Medium (Standard)',
                            'HIGH' => 'High (Urgent)',
                            'EMERGENCY' => 'Emergency (Safety/Damage Risk)',
                        ]" />
                    </div>
                </div>

                <x-ui.form.input
                    name="title"
                    label="Title / Subject"
                    required
                    placeholder="e.g. Leaking faucet in kitchen"
                />

                <x-ui.form.textarea
                    name="description"
                    label="Detailed Description"
                    :rows="5"
                    required
                    placeholder="Please describe the issue in detail..."
                />

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <x-ui.button type="submit" variant="primary">Submit Request</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
