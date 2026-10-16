@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Units</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('units.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Add Unit
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Building</th>
                        <th>Floor</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr>
                        <td>
                            <a href="{{ route('units.show', $unit) }}" class="text-decoration-none fw-bold">
                                {{ $unit->unit_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('buildings.show', $unit->building) }}" class="text-decoration-none text-dark">
                                {{ $unit->building->name }}
                            </a>
                        </td>
                        <td>{{ $unit->floor ?? '-' }}</td>
                        <td>
                            @if($unit->unit_type === 'WAREHOUSE')
                                <span class="badge bg-info text-dark">Warehouse</span>
                            @else
                                {{ $unit->bedrooms }} Bed / {{ $unit->bathrooms }} Bath
                            @endif
                        </td>
                        <td>
                            @if($unit->status === 'OCCUPIED')
                                <span class="badge bg-success">Occupied</span>
                            @elseif($unit->status === 'MAINTENANCE')
                                <span class="badge bg-warning text-dark">Maintenance</span>
                            @else
                                <span class="badge bg-secondary">Available</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end flex-wrap gap-1">
                                <a href="{{ route('units.show', $unit) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->isManager())
                                    @include('partials.manager-edit-request-button', [
                                        'recordType' => 'unit',
                                        'recordId' => $unit->id,
                                        'recordLabel' => 'Unit '.$unit->unit_number.' in '.$unit->building->name,
                                        'buttonClass' => 'btn btn-sm btn-outline-warning',
                                        'buttonText' => 'Request Edit',
                                        'iconClass' => 'bi bi-pencil-square',
                                    ])
                                @else
                                    <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @if(Auth::user()->isAdmin())
                                    <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this unit?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-door-closed fs-1 d-block mb-2"></i>
                            No units found. Click "Add Unit" to create one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $units->links() }}
        </div>
    </div>
</div>
@endsection
