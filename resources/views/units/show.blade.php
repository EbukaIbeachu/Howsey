@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Unit {{ $unit->unit_number }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('units.index') }}" class="btn btn-sm btn-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
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
            <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil"></i> Edit Unit
            </a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                Unit Information
            </div>
            <div class="card-body">
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
                            <span class="badge bg-info text-dark">Warehouse</span>
                            <span class="text-muted small ms-1">no bedrooms or bathrooms</span>
                        @else
                            {{ $unit->bedrooms }} Bed / {{ $unit->bathrooms }} Bath
                        @endif
                    </dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        @if($unit->status === 'OCCUPIED')
                            <span class="badge bg-success">Occupied</span>
                        @elseif($unit->status === 'MAINTENANCE')
                            <span class="badge bg-warning text-dark">Maintenance</span>
                        @else
                            <span class="badge bg-secondary">Available</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                Current Tenant
            </div>
            <div class="card-body">
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
                            <a href="{{ route('tenants.show', $currentRent->tenant) }}" class="btn btn-sm btn-outline-primary">View Profile</a>
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
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                        No active tenant.
                        @if($unit->status === 'AVAILABLE')
                            <div class="mt-2">
                                <a href="{{ route('tenants.create', ['unit_id' => $unit->id]) }}" class="btn btn-sm btn-primary">Move In Tenant</a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">
        Rental History
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tenant</th>
                        <th>Period</th>
                        <th>Rent Amount</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unit->rents->sortByDesc('start_date') as $rent)
                    <tr>
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
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst(strtolower($rent->status)) }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('rents.show', $rent) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No rental history found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
