@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="buildings-header">Buildings</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-info me-2" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
        @permission('buildings.create')
            <a href="{{ route('buildings.create') }}" class="btn btn-sm btn-primary" id="add-building-btn">
                <i class="bi bi-plus-lg"></i> Add Building
            </a>
        @endpermission
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <style>
            @media (max-width: 767.98px) {
                #buildingsTable .mobile-portrait-hide {
                    display: none;
                }
            }
        </style>
        <div class="mb-3">
            <input type="text" id="buildingSearch" class="form-control" placeholder="Search buildings...">
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="buildingsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th class="mobile-portrait-hide">Location</th>
                        @permission('buildings.delete')
                        <th class="mobile-portrait-hide">Created By</th>
                        @endpermission
                        <th class="text-center mobile-portrait-hide">Units</th>
                        <th class="text-end mobile-portrait-hide">Actions</th>
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
                            <div class="btn-group">
                                <a href="{{ route('buildings.show', $building) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
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
                                    <a href="{{ route('buildings.edit', $building) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @permission('buildings.delete')
                                    <form action="{{ route('buildings.destroy', $building) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this building?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endpermission
                            </div>
                        </td>
                    </tr>
                    @empty
                    @php($colspan = auth()->user()->hasPermission('buildings.delete') ? 6 : 5)
                    <tr>
                        <td colspan="{{ $colspan }}" class="text-center py-4 text-muted">
                            <i class="bi bi-building fs-1 d-block mb-2"></i>
                            No buildings found. Click "Add Building" to create one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $buildings->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('buildingSearch', 'buildingsTable');
    });
</script>
@endpush

@endsection
