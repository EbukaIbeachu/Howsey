@extends('layouts.app')

@section('content')
<x-ui.page-header :title="'Unit '.$unit->unit_number">
    <x-slot:actions>
        <x-ui.button variant="secondary" size="sm" icon="bi bi-arrow-left" :href="route('units.index')">Back to List</x-ui.button>
        @if(auth()->user()->isManager())
            @include('partials.manager-edit-request-button', [
                'recordType' => 'unit',
                'recordId' => $unit->id,
                'recordLabel' => 'Unit '.$unit->unit_number.' in '.$unit->building->name,
                'buttonClass' => 'btn btn-sm btn-warning',
                'buttonText' => 'Request Edit',
                'iconClass' => 'bi bi-pencil-square',
            ])
        @else
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil" :href="route('units.edit', $unit)">Edit Unit</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<div class="row">
    <div class="col-md-5 mb-4">
        <x-ui.card title="Unit Information" class="h-100">
            <dl class="row mb-0">
                <dt class="col-sm-4">Building</dt>
                <dd class="col-sm-8">
                    <a href="{{ route('buildings.show', $unit->building) }}" class="text-decoration-none">
                        {{ $unit->building->name }}
                    </a>
                </dd>

                <dt class="col-sm-4">Address</dt>
                <dd class="col-sm-8">
                    {{ $unit->building->address_line1 }}<br>
                    {{ $unit->building->city }}, {{ $unit->building->state }}
                </dd>

                <dt class="col-sm-4">Floor</dt>
                <dd class="col-sm-8">{{ $unit->floor ?? '-' }}</dd>

                <dt class="col-sm-4">Configuration</dt>
                <dd class="col-sm-8">
                    @if($unit->unit_type === 'WAREHOUSE')
                        <x-ui.status-badge tone="info" label="Warehouse" />
                        <span class="text-muted small ms-1">no bedrooms or bathrooms</span>
                    @else
                        {{ $unit->bedrooms }} Bed / {{ $unit->bathrooms }} Bath
                    @endif
                </dd>

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">
                    @if($unit->status === 'OCCUPIED')
                        <x-ui.status-badge tone="success" label="Occupied" />
                    @elseif($unit->status === 'MAINTENANCE')
                        <x-ui.status-badge tone="warning" label="Maintenance" />
                    @else
                        <x-ui.status-badge tone="neutral" label="Available" />
                    @endif
                </dd>
            </dl>
        </x-ui.card>
    </div>

    <div class="col-md-7 mb-4">
        <x-ui.card title="Current Tenant" class="h-100">
            @php
                // Find active rent or manually check current tenant relationship if available
                // Assuming unit status OCCUPIED implies active tenant
                $currentRent = $unit->rents->where('status', 'ACTIVE')->first();
            @endphp

            @if($unit->status === 'OCCUPIED' && $currentRent)
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-person fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0">{{ $currentRent->tenant->full_name }}</h5>
                        <div class="text-muted small">{{ $currentRent->tenant->email }}</div>
                    </div>
                    <div>
                        <x-ui.button variant="outline-primary" size="sm" :href="route('tenants.show', $currentRent->tenant)">View Profile</x-ui.button>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Lease Start</dt>
                    <dd class="col-sm-8">{{ $currentRent->start_date }}</dd>
                    
                    <dt class="col-sm-4">Lease End</dt>
                    <dd class="col-sm-8">{{ $currentRent->end_date }}</dd>
                    
                    <dt class="col-sm-4">{{ $currentRent->rent_amount_label }}</dt>
                    <dd class="col-sm-8">₦{{ number_format($currentRent->annual_amount, 2) }}</dd>

                    <dt class="col-sm-4">Rent Duration</dt>
                    <dd class="col-sm-8">{{ $currentRent->rent_duration_label }}</dd>

                    <dt class="col-sm-4">Due Schedule</dt>
                    <dd class="col-sm-8">{{ $currentRent->due_schedule_label }}</dd>
                </dl>
            @else
                <x-ui.empty-state
                    icon="bi bi-person-x"
                    title="No active tenant."
                >
                    @if($unit->status === 'AVAILABLE')
                        <x-slot:actions>
                            <x-ui.button variant="primary" size="sm" :href="route('tenants.create', ['unit_id' => $unit->id])">Move In Tenant</x-ui.button>
                        </x-slot:actions>
                    @endif
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    </div>
</div>

<x-ui.card title="Rental History" flush>
    <x-ui.table class="mb-0" caption="Rental history" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col">Tenant</th>
                <th scope="col">Period</th>
                <th scope="col">Rent Amount</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unit->rents->sortByDesc('start_date') as $rent)
            <tr class="@if($rent->status === 'ACTIVE') ui-row--success @else ui-row--neutral @endif">
                <td>
                    <a href="{{ route('tenants.show', $rent->tenant) }}" class="text-decoration-none">
                        {{ $rent->tenant->full_name }}
                    </a>
                </td>
                <td>
                    {{ $rent->start_date }} <span class="text-muted mx-1">to</span> {{ $rent->end_date }}
                </td>
                <td>
                    ₦{{ number_format($rent->annual_amount, 2) }}
                    <div class="small text-muted">{{ $rent->rent_duration_label }} {{ $rent->rent_interval_suffix }}</div>
                </td>
                <td>
                    @if($rent->status === 'ACTIVE')
                        <x-ui.status-badge tone="success" label="Active" />
                    @else
                        <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($rent->status))" />
                    @endif
                </td>
                <td class="text-end">
                    <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('rents.show', $rent)" />
                </td>
            </tr>
            @empty
            <x-ui.table.empty-state
                :colspan="5"
                icon="bi bi-file-earmark-text"
                title="No rental history found"
            />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>
@endsection
