@extends('layouts.app')

@section('content')
@php
    $emptyText = ($pageTitle ?? 'Rental Agreements') === 'Expired Leases'
        ? 'No archived expired leases found.'
        : 'No rental agreements found. Click "Create Agreement" to add one.';
@endphp

<x-ui.page-header :title="$pageTitle ?? 'Rental Agreements'" id="rents-header">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('rents.create')" id="add-rent-btn">Create Agreement</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div class="ui-btn-group">
            <x-ui.button size="sm" :variant="$status === 'ACTIVE' ? 'primary' : 'outline-primary'" :href="route('rents.index', ['status' => 'ACTIVE'])">Active</x-ui.button>
            <x-ui.button size="sm" :variant="$status === 'TERMINATED' ? 'primary' : 'outline-primary'" :href="route('rents.index', ['status' => 'TERMINATED'])">Terminated</x-ui.button>
            <x-ui.button size="sm" :variant="$status === 'ALL' ? 'primary' : 'outline-primary'" :href="route('rents.index', ['status' => 'ALL'])">All</x-ui.button>
            <x-ui.button size="sm" :variant="$status === 'EXPIRED_ARCHIVED' ? 'primary' : 'outline-primary'" :href="route('rents.expired-leases')">Expired Leases</x-ui.button>
        </div>
        <input type="text" id="rentSearch" class="form-control w-100 w-md-auto" placeholder="Search agreements...">
    </div>

    <x-ui.table id="rentsTable" :striped="true" caption="Rental agreements" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col">Tenant</th>
                <th scope="col" class="d-none d-md-table-cell">Unit</th>
                <th scope="col" class="d-none d-md-table-cell">Period</th>
                <th scope="col">Amount</th>
                @if(Auth::user()->isAdmin())
                <th scope="col" class="d-none d-md-table-cell">Created By</th>
                @endif
                <th scope="col" class="d-none d-md-table-cell">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rents as $rent)
            <tr class="clickable-row @if($rent->status === 'ACTIVE') ui-row--success @elseif($rent->status === 'EXPIRED') ui-row--warning @else ui-row--neutral @endif" data-href="{{ route('rents.agreement', $rent) }}" style="cursor: pointer;">
                <td>
                    <a href="{{ route('rents.show', $rent) }}" class="text-decoration-none fw-bold">
                        {{ $rent->tenant->full_name }}
                    </a>
                </td>
                <td class="d-none d-md-table-cell">
                    @if($rent->unit)
                        <a href="{{ route('units.show', $rent->unit) }}" class="text-decoration-none text-dark">
                            {{ $rent->unit->unit_number }}
                        </a>
                        <br>
                        <small class="text-muted">{{ $rent->unit->building->name }}</small>
                    @else
                        <span class="fst-italic text-muted">Unit Removed</span>
                    @endif
                </td>
                <td class="d-none d-md-table-cell">
                    {{ $rent->lease_period_label }} <br>
                    <small class="text-muted">{{ $rent->due_schedule_label }}</small>
                </td>
                <td>
                    ₦{{ number_format($rent->annual_amount, 2) }}
                    <br>
                    <small class="text-muted">{{ $rent->rent_duration_label }} {{ $rent->rent_interval_suffix }}</small>
                </td>
                @if(Auth::user()->isAdmin())
                <td class="d-none d-md-table-cell">
                    @if($rent->creator)
                        <div class="d-flex align-items-center">
                            <div class="ms-2">
                                <div class="fw-bold small">{{ $rent->creator->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $rent->creator->role->name ?? 'User' }}</div>
                            </div>
                        </div>
                    @else
                        <span class="text-muted small fst-italic">System</span>
                    @endif
                </td>
                @endif
                <td class="d-none d-md-table-cell">
                    @if($rent->status === 'ACTIVE')
                        <x-ui.status-badge tone="success" label="Active" />
                    @else
                        <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($rent->status))" />
                    @endif
                </td>
                <td class="text-end">
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="Download Agreement" icon="bi bi-file-earmark-pdf" size="sm" :href="route('rents.agreement', $rent)" target="_blank" />
                        <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('rents.show', $rent)" />
                        @if(Auth::user()->isManager())
                            @include('partials.manager-edit-request-button', [
                                'recordType' => 'rent',
                                'recordId' => $rent->id,
                                'recordLabel' => 'Rental Agreement #'.$rent->id,
                                'buttonClass' => 'btn btn-sm btn-outline-warning',
                                'buttonText' => 'Request Edit',
                                'iconClass' => 'bi bi-pencil-square',
                            ])
                        @else
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" size="sm" :href="route('rents.edit', $rent)" />
                        @endif
                        @if(Auth::user()->isAdmin())
                        <form action="{{ route('rents.destroy', $rent) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this agreement?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.icon-button type="submit" variant="danger" size="sm" icon="bi bi-trash" label="Delete" />
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <x-ui.table.empty-state
                :colspan="7"
                icon="bi bi-file-earmark-text"
                title="No rental agreements found"
                :text="$emptyText"
            />
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-3">
        {{ $rents->links() }}
    </div>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        if (typeof setupTableSearch === 'function') {
            setupTableSearch('rentSearch', 'rentsTable');
        }

        // Clickable Row Logic
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Prevent navigation if clicking on interactive elements
                if (e.target.closest('a') || e.target.closest('button') || e.target.closest('form') || e.target.closest('input')) {
                    return;
                }
                
                const url = this.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
@endpush
@endsection
