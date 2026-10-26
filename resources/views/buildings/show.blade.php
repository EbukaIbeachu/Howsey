@extends('layouts.app')

@section('content')
<style>
    .building-image {
        width: 100px;
        height: 100px;
        flex-shrink: 0;
    }

    .building-info-badge,
    .building-stat-badge {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        white-space: normal;
        text-align: left;
    }

    .building-actions {
        width: auto;
    }

    .building-action-btn {
        min-width: 110px;
        padding: 0.35rem 0.85rem;
        font-size: 0.9rem;
    }

    @media (max-width: 767.98px) {
        /* Table Compactness */
        .table-responsive .table td,
        .table-responsive .table th {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.85rem;
        }

        /* Reduce button padding in tables */
        .table-responsive .btn-sm {
            padding: 0.1rem 0.3rem;
            font-size: 0.75rem;
        }

        /* Adjust badges for mobile */
        .table-responsive .badge {
            font-size: 0.7rem;
            padding: 0.2em 0.4em;
        }

        .building-header-layout {
            flex-direction: column;
            align-items: stretch !important;
        }

        .building-summary {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.85rem;
        }

        .building-image {
            width: 88px;
            height: 88px;
            margin-right: 0 !important;
        }

        .building-details {
            width: 100%;
        }

        .building-title {
            font-size: 1.15rem;
            line-height: 1.25;
        }

        .building-address {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .building-badges {
            gap: 0.35rem !important;
        }

        .building-info-badge,
        .building-stat-badge {
            font-size: 0.72rem;
            padding: 0.35em 0.55em;
        }

        .building-actions {
            width: 100%;
            justify-content: stretch;
            margin-top: 0 !important;
        }

        .building-action-btn {
            flex: 1 1 0;
            min-width: 0;
            padding: 0.4rem 0.7rem;
            font-size: 0.85rem;
        }
    }
</style>
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="row mb-4" id="building-announcements-section">
        <div class="col-12">
            <x-ui.card class="border-0 bg-white">
                <div class="p-3 p-md-4">
                    <div class="d-flex flex-wrap flex-md-nowrap justify-content-between align-items-start gap-3 building-header-layout">
                        <div class="d-flex align-items-start w-100 building-summary">
                            @if($building->image_path)
                                <img src="{{ Storage::url($building->image_path) }}" alt="{{ $building->name }}" class="rounded object-fit-cover building-image">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted building-image">
                                    <i class="bi bi-building fs-1"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1 building-details">
                                <h1 class="h2 mb-1 building-title">{{ $building->name }}</h1>
                                <p class="text-muted mb-2 building-address">
                                    <i class="bi bi-geo-alt me-1"></i> {{ $building->address }}
                                </p>
                                <div class="mb-2">
                                    @if($building->manager)
                                        <span class="badge bg-secondary building-info-badge"><i class="bi bi-person-badge me-1"></i> Manager: {{ $building->manager->name }}</span>
                                    @else
                                        <span class="badge bg-secondary building-info-badge"><i class="bi bi-person-badge me-1"></i> Manager: Unassigned</span>
                                    @endif
                                </div>
                                @if(auth()->user()->isAdmin() && $building->creator)
                                    <div class="mb-2 text-muted small d-none d-md-block">
                                        <i class="bi bi-person-check me-1"></i> Registered by: {{ $building->creator->name }}
                                    </div>
                                @endif
                                <div class="d-flex gap-2 flex-wrap building-badges">
                                    <span class="badge bg-primary building-stat-badge">{{ $stats['total_units'] }} Units</span>
                                    <span class="badge bg-success building-stat-badge">{{ $stats['occupied_units'] }} Occupied</span>
                                    <span class="badge bg-info text-dark building-stat-badge">{{ $stats['total_tenants'] }} Tenants</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0 building-actions">
                            @if(auth()->user()->isManager())
                                @include('partials.manager-edit-request-button', [
                                    'recordType' => 'building',
                                    'recordId' => $building->id,
                                    'recordLabel' => $building->name,
                                    'buttonClass' => 'btn btn-sm btn-outline-warning building-action-btn',
                                    'buttonText' => 'Request Edit',
                                    'iconClass' => 'bi bi-pencil-square',
                                ])
                            @else
                                <x-ui.button variant="outline-primary" size="sm" icon="bi bi-pencil" :href="route('buildings.edit', $building)" class="building-action-btn">Edit</x-ui.button>
                            @endif
                            <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('buildings.index')" class="building-action-btn">Back</x-ui.button>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    @php
        $canPostAnnouncement = Auth::user()->isAdmin() || (Auth::user()->isManager() && $building->manager_id === Auth::id());
        $isTenantOfBuilding = Auth::user()->isTenant() && optional(optional(Auth::user()->tenant)->unit)->building_id === $building->id;
    @endphp
    @if($canPostAnnouncement || $isTenantOfBuilding)
    <div class="row mb-4">
        <div class="col-12">
            <x-ui.card title="Announcements" icon="bi bi-megaphone">
                @if($canPostAnnouncement)
                    <form action="{{ route('buildings.announce', $building) }}" method="POST" class="mb-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="title" class="form-control" placeholder="Title" required maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="content" class="form-control" placeholder="Message to tenants..." required maxlength="5000">
                            </div>
                            <div class="col-md-2 d-grid">
                                <x-ui.button type="submit" variant="primary" icon="bi bi-send">Post</x-ui.button>
                            </div>
                        </div>
                    </form>
                @endif
                <ul class="list-group">
                    @forelse($announcements as $ann)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $ann->title }}</strong>
                                    <div class="text-muted small">{{ $ann->created_at->format('M d, Y H:i') }} — {{ $ann->manager->name }}</div>
                                    <div>{{ $ann->content }}</div>
                                </div>
                                <form action="{{ route('buildings.announcements.dismiss', ['building' => $building->id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your view only?');">
                                    @csrf
                                    <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-x-circle">Remove</x-ui.button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No announcements yet.</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>
    @endif

    <!-- Quick Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <x-ui.stat-card
                label="YTD Total Revenue"
                tone="primary"
                :value="'₦'.number_format($stats['ytd_total_revenue'] ?? 0, 2)"
            />
        </div>
        <div class="col-6 col-md-3">
            <x-ui.stat-card
                label="Total Expenses"
                tone="danger"
                :value="'₦'.number_format($stats['total_expenses'] ?? 0, 2)"
            />
        </div>
        <div class="col-6 col-md-3">
            <x-ui.stat-card
                label="Total Income"
                tone="success"
                :value="'₦'.number_format($stats['total_income'] ?? 0, 2)"
            />
        </div>
        <div class="col-6 col-md-3">
            <x-ui.stat-card
                label="Occupancy"
                tone="info"
                :value="($stats['total_units'] > 0 ? round(($stats['occupied_units'] / $stats['total_units']) * 100) : 0).'%'"
            />
        </div>
    </div>

    <!-- Main Content Tabs -->
    <x-ui.card class="border-0">
        <ul class="nav nav-tabs card-header-tabs mb-3" id="buildingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" type="button" role="tab">
                    <i class="bi bi-door-closed me-1"></i> Units
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tenants-tab" data-bs-toggle="tab" data-bs-target="#tenants" type="button" role="tab">
                    <i class="bi bi-people me-1"></i> Tenants
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rents-tab" data-bs-toggle="tab" data-bs-target="#rents" type="button" role="tab">
                    <i class="bi bi-file-text me-1"></i> Leases <span class="d-none d-md-inline">& Agreements</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                    <i class="bi bi-cash-stack me-1"></i> Financials
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="bi bi-clock-history me-1"></i> History
                </button>
            </li>
        </ul>

        <div class="tab-content" id="buildingTabsContent">

            <!-- Units Tab -->
            <div class="tab-pane fade show active" id="units" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Unit Directory</h5>
                    <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('units.create', ['building_id' => $building->id])"><span class="d-none d-md-inline">Add New Unit</span><span class="d-inline d-md-none">Add</span></x-ui.button>
                </div>
                <x-ui.table caption="Units in this building" :caption-sr="true">
                    <thead>
                        <tr>
                            <th scope="col">Unit #</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="d-none d-md-table-cell">Status</th>
                            <th scope="col">Current Tenant</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($building->units as $unit)
                        <tr class="@if($unit->status === 'OCCUPIED') ui-row--success @elseif($unit->status === 'MAINTENANCE') ui-row--warning @else ui-row--neutral @endif">
                            <td class="fw-bold text-nowrap">{{ $unit->unit_number }}</td>
                            <td>
                                <div class="small">{{ $unit->bedrooms }} Bed, {{ $unit->bathrooms }} Bath</div>
                                <div class="text-muted small">Floor {{ $unit->floor ?? 'G' }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @if($unit->status === 'OCCUPIED')
                                    <x-ui.status-badge tone="success" label="Occupied" />
                                @elseif($unit->status === 'MAINTENANCE')
                                    <x-ui.status-badge tone="warning" label="Maintenance" />
                                @else
                                    <x-ui.status-badge tone="neutral" label="Available" />
                                @endif
                            </td>
                            <td>
                                @php
                                    $tenant = $unit->currentTenant;
                                    if (!$tenant) {
                                        // Fallback: check building's loaded rents for this unit
                                        $activeRent = $building->rents->where('unit_id', $unit->id)->where('status', 'ACTIVE')->first();
                                        if ($activeRent) {
                                            $tenant = $activeRent->tenant;
                                        }
                                    }
                                @endphp
                                @if($tenant)
                                    <a href="{{ route('tenants.show', $tenant) }}" class="text-decoration-none fw-bold">
                                        {{ $tenant->full_name }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if(auth()->user()->isManager())
                                        @include('partials.manager-edit-request-button', [
                                            'recordType' => 'unit',
                                            'recordId' => $unit->id,
                                            'recordLabel' => 'Unit '.$unit->unit_number.' in '.$building->name,
                                            'buttonClass' => 'btn btn-sm btn-outline-warning',
                                            'buttonText' => 'Request Edit',
                                            'iconClass' => 'bi bi-pencil-square',
                                        ])
                                    @elseif(auth()->user()->isAdmin())
                                        <x-ui.icon-button label="Edit Unit" icon="bi bi-pencil" size="sm" :href="route('units.edit', $unit)" />
                                        <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf @method('DELETE')
                                            <x-ui.icon-button type="submit" variant="danger" size="sm" icon="bi bi-trash" label="Delete Unit" />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-ui.table.empty-state :colspan="5" icon="bi bi-door-closed" title="No units found in this building" />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>

            <!-- Tenants Tab -->
            <div class="tab-pane fade" id="tenants" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Active Tenants</h5>
                    <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('tenants.create', ['building_id' => $building->id])"><span class="d-none d-md-inline">Register Tenant</span><span class="d-inline d-md-none">Register</span></x-ui.button>
                </div>
                <x-ui.table caption="Active tenants in this building" :caption-sr="true">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Unit</th>
                            <th scope="col" class="d-none d-md-table-cell">Contact</th>
                            <th scope="col" class="d-none d-md-table-cell">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildingTenants as $tenant)
                        <tr class="ui-row--success">
                            <td>
                                <div class="fw-bold">{{ $tenant->full_name }}</div>
                                <div class="small text-muted">{{ $tenant->email }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ optional($tenant->unit)->unit_number ?? '-' }}
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $tenant->phone_number }}</td>
                            <td class="d-none d-md-table-cell">
                                <x-ui.status-badge tone="success" label="Active" />
                            </td>
                            <td>
                                <x-ui.button variant="outline-primary" size="sm" :href="route('tenants.show', $tenant)"><span class="d-none d-md-inline">View Profile</span><span class="d-inline d-md-none">View</span></x-ui.button>
                            </td>
                        </tr>
                        @empty
                        <x-ui.table.empty-state :colspan="5" icon="bi bi-people" title="No active tenants found" />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>

            <!-- Leases Tab -->
            <div class="tab-pane fade" id="rents" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Active Rental Agreements</h5>
                    <div class="d-flex gap-2">
                        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-archive" :href="route('rents.expired-leases')"><span class="d-none d-md-inline">Expired Leases</span><span class="d-inline d-md-none">Expired</span></x-ui.button>
                        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('rents.create', ['building_id' => $building->id])"><span class="d-none d-md-inline">Create Lease</span><span class="d-inline d-md-none">Create</span></x-ui.button>
                    </div>
                </div>
                <x-ui.table caption="Active rental agreements in this building" :caption-sr="true">
                    <thead>
                        <tr>
                            <th scope="col">Tenant</th>
                            <th scope="col">Unit</th>
                            <th scope="col" class="d-none d-md-table-cell">Lease Period</th>
                            <th scope="col">Rent Amount</th>
                            <th scope="col" class="d-none d-md-table-cell">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildingRents as $rent)
                        <tr class="ui-row--success"
                            style="cursor: pointer;"
                            onclick="window.open('{{ route('rents.agreement', $rent) }}', '_blank')"
                            title="Click to view PDF Agreement">
                            <td>
                                {{ $rent->tenant->full_name }}
                                <i class="bi bi-file-earmark-pdf text-danger ms-1 small" title="PDF Agreement"></i>
                            </td>
                            <td>{{ $rent->unit->unit_number }}</td>
                            <td class="d-none d-md-table-cell">
                                <div class="small">{{ $rent->start_date->format('M d, Y') }}</div>
                                <div class="small text-muted">to {{ $rent->end_date ? $rent->end_date->format('M d, Y') : 'Indefinite' }}</div>
                            </td>
                            <td class="fw-bold text-nowrap">
                                ₦{{ number_format($rent->annual_amount, 2) }}
                                <div class="small text-muted fw-normal">{{ $rent->rent_duration_label }} {{ $rent->rent_interval_suffix }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <x-ui.status-badge tone="success" label="Active" />
                            </td>
                            <td>
                                <div class="ui-btn-group" onclick="event.stopPropagation()">
                                    <x-ui.icon-button label="Details" icon="bi bi-eye" size="sm" :href="route('rents.show', $rent)" />
                                    <x-ui.icon-button label="PDF Agreement" icon="bi bi-file-pdf" size="sm" :href="route('rents.agreement', $rent)" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <x-ui.table.empty-state :colspan="6" icon="bi bi-file-text" title="No active agreements found" />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>

            <!-- Financials Tab -->
            <div class="tab-pane fade" id="payments" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Payment History</h5>
                    <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('payments.create', ['building_id' => $building->id])">Record Payment</x-ui.button>
                </div>
                <x-ui.table caption="Payment history for this building" :caption-sr="true">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Tenant</th>
                            <th scope="col" class="d-none d-md-table-cell">Reference</th>
                            <th scope="col">Amount</th>
                            <th scope="col" class="d-none d-md-table-cell">Method</th>
                            <th scope="col" class="d-none d-md-table-cell">Status</th>
                            <th scope="col">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $payments = $building->rents->flatMap->payments->sortByDesc('payment_date');
                        @endphp
                        @forelse($payments as $payment)
                        <tr class="{{ $payment->status === 'COMPLETED' ? 'ui-row--success' : ($payment->status === 'PENDING' ? 'ui-row--warning' : ($payment->status === 'FAILED' ? 'ui-row--danger' : 'ui-row--neutral')) }}">
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->rent->tenant->full_name }}</td>
                            <td class="font-monospace small d-none d-md-table-cell">{{ Str::limit($payment->rent->unit->unit_number . '-' . $payment->id, 15) }}</td>
                            <td class="fw-bold text-success">+₦{{ number_format($payment->amount, 2) }}</td>
                            <td class="d-none d-md-table-cell">{{ ucfirst($payment->payment_method) }}</td>
                            <td class="d-none d-md-table-cell">
                                @if($payment->status === 'COMPLETED')
                                    <x-ui.status-badge tone="success" label="Completed" />
                                @elseif($payment->status === 'PENDING')
                                    <x-ui.status-badge tone="warning" label="Pending" />
                                @elseif($payment->status === 'FAILED')
                                    <x-ui.status-badge tone="danger" label="Failed" />
                                @else
                                    <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($payment->status))" />
                                @endif
                            </td>
                            <td>
                                @if((auth()->user()->isAdmin() || auth()->user()->isManager()) && strtoupper((string) $payment->status) === 'COMPLETED')
                                    <x-ui.button variant="outline-success" size="sm" icon="bi bi-download" :href="route('payments.receipt', ['payment' => $payment, 'download' => 1])"><span class="d-none d-md-inline">Receipt</span></x-ui.button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <x-ui.table.empty-state :colspan="7" icon="bi bi-cash-stack" title="No payments recorded yet" />
                        @endforelse
                    </tbody>
                </x-ui.table>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Eviction History</h5>
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <x-ui.card title="Former Tenants" flush>
                            <x-ui.table class="mb-0" caption="Former tenants" :caption-sr="true">
                                <thead>
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Last Unit</th>
                                        <th scope="col" class="d-none d-md-table-cell">Contact</th>
                                        <th scope="col">Move-Out Date</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($buildingHistoryTenants as $tenant)
                                    <tr class="ui-row--neutral">
                                        <td>
                                            <div class="fw-bold">{{ $tenant->full_name }}</div>
                                            <div class="small text-muted">{{ $tenant->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ optional($tenant->unit)->unit_number ?? 'Archived' }}
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell">{{ $tenant->phone_number }}</td>
                                        <td>
                                            {{ $tenant->move_out_date ? \Illuminate\Support\Carbon::parse($tenant->move_out_date)->format('Y-m-d') : '-' }}
                                        </td>
                                        <td>
                                            <x-ui.button variant="outline-primary" size="sm" :href="route('tenants.show', $tenant)"><span class="d-none d-md-inline">View Profile</span><span class="d-inline d-md-none">View</span></x-ui.button>
                                        </td>
                                    </tr>
                                    @empty
                                    <x-ui.table.empty-state :colspan="5" icon="bi bi-clock-history" title="No evicted tenant history yet" />
                                    @endforelse
                                </tbody>
                            </x-ui.table>
                        </x-ui.card>
                    </div>

                    <div class="col-12">
                        <x-ui.card title="Closed Lease Agreements" flush>
                            <x-ui.table class="mb-0" caption="Closed lease agreements" :caption-sr="true">
                                <thead>
                                    <tr>
                                        <th scope="col">Tenant</th>
                                        <th scope="col">Unit</th>
                                        <th scope="col" class="d-none d-md-table-cell">Lease Period</th>
                                        <th scope="col">Rent Amount</th>
                                        <th scope="col" class="d-none d-md-table-cell">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($buildingHistoryRents as $rent)
                                    <tr class="ui-row--neutral">
                                        <td>{{ $rent->tenant->full_name }}</td>
                                        <td>{{ optional($rent->unit)->unit_number ?? '-' }}</td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="small">{{ $rent->start_date->format('M d, Y') }}</div>
                                            <div class="small text-muted">to {{ $rent->end_date ? $rent->end_date->format('M d, Y') : 'Indefinite' }}</div>
                                        </td>
                                        <td class="fw-bold text-nowrap">
                                            ₦{{ number_format($rent->annual_amount, 2) }}
                                            <div class="small text-muted fw-normal">{{ $rent->rent_duration_label }} {{ $rent->rent_interval_suffix }}</div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($rent->status))" />
                                        </td>
                                        <td>
                                            <div class="ui-btn-group">
                                                <x-ui.icon-button label="Details" icon="bi bi-eye" size="sm" :href="route('rents.show', $rent)" />
                                                <x-ui.icon-button label="PDF Agreement" icon="bi bi-file-pdf" size="sm" :href="route('rents.agreement', $rent)" />
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <x-ui.table.empty-state :colspan="6" icon="bi bi-file-text" title="No closed lease history yet" />
                                    @endforelse
                                </tbody>
                            </x-ui.table>
                        </x-ui.card>
                    </div>
                </div>
            </div>

        </div>
    </x-ui.card>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const requestedTab = new URLSearchParams(window.location.search).get('tab');

    if (!requestedTab) {
        return;
    }

    const trigger = document.getElementById(requestedTab + '-tab');

    if (!trigger || typeof bootstrap === 'undefined' || !bootstrap.Tab) {
        return;
    }

    bootstrap.Tab.getOrCreateInstance(trigger).show();
});
</script>
@endpush
