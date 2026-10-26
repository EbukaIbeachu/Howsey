@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        #buildingsTable .mobile-portrait-hide {
            display: none;
        }
    }
</style>

<x-ui.page-header title="Buildings" id="buildings-header">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
        @permission('buildings.create')
            <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('buildings.create')" id="add-building-btn">Add Building</x-ui.button>
        @endpermission
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <div class="mb-3">
        <input type="text" id="buildingSearch" class="form-control" placeholder="Search buildings...">
    </div>

    <x-ui.table id="buildingsTable" :striped="true">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Address</th>
                <th scope="col" class="mobile-portrait-hide">Location</th>
                @permission('buildings.delete')
                <th scope="col" class="mobile-portrait-hide">Created By</th>
                @endpermission
                <th scope="col" class="text-center mobile-portrait-hide">Units</th>
                <th scope="col" class="text-end mobile-portrait-hide">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buildings as $building)
            <tr>
                <td>
                    <a href="{{ route('buildings.show', $building) }}" class="text-decoration-none fw-bold">
                        {{ $building->name }}
                    </a>
                </td>
                <td>
                    {{ $building->address_line1 }}
                    @if($building->address_line2)
                        <br><small class="text-muted">{{ $building->address_line2 }}</small>
                    @endif
                </td>
                <td class="mobile-portrait-hide">
                    {{ $building->city }}, {{ $building->state }} {{ $building->postal_code }}
                </td>
                @permission('buildings.delete')
                <td class="mobile-portrait-hide">
                    @if($building->creator)
                        <div class="d-flex align-items-center">
                            <div class="ms-2">
                                <div class="fw-bold small">{{ $building->creator->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $building->creator->role->name ?? 'User' }}</div>
                            </div>
                        </div>
                    @else
                        <span class="text-muted small fst-italic">System</span>
                    @endif
                </td>
                @endpermission
                <td class="text-center mobile-portrait-hide">
                    <span class="badge bg-secondary">{{ $building->total_units }} Total</span>
                    @if($building->active_units_count > 0)
                        <span class="badge bg-success">{{ $building->active_units_count }} Occupied</span>
                    @endif
                </td>
                <td class="text-end mobile-portrait-hide">
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('buildings.show', $building)" />
                        @if(Auth::user()->isManager() && auth()->user()->hasPermission('buildings.edit'))
                            @include('partials.manager-edit-request-button', [
                                'recordType' => 'building',
                                'recordId' => $building->id,
                                'recordLabel' => $building->name,
                                'buttonClass' => 'btn btn-sm btn-outline-warning',
                                'buttonText' => 'Request Edit',
                                'iconClass' => 'bi bi-pencil-square',
                            ])
                        @elseif(auth()->user()->hasPermission('buildings.edit'))
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" size="sm" :href="route('buildings.edit', $building)" />
                        @endif
                        @permission('buildings.delete')
                            <form action="{{ route('buildings.destroy', $building) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this building?');">
                                @csrf
                                @method('DELETE')
                                <x-ui.icon-button type="submit" variant="danger" size="sm" icon="bi bi-trash" label="Delete" />
                            </form>
                        @endpermission
                    </div>
                </td>
            </tr>
            @empty
            <x-ui.table.empty-state
                :colspan="auth()->user()->hasPermission('buildings.delete') ? 6 : 5"
                icon="bi bi-building"
                title="No buildings found"
                text='Click "Add Building" to create one.'
            />
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-3">
        {{ $buildings->links() }}
    </div>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('buildingSearch', 'buildingsTable');
    });
</script>
@endpush

@endsection
