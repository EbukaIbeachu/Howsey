@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="maintenance-header">Maintenance Requests</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-info me-2" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
        <a href="{{ route('maintenance.create') }}" class="btn btn-sm btn-primary" id="add-maintenance-btn">
            <i class="bi bi-plus-lg"></i> New Request
        </a>
    </div>
</div>

@if(session('maintenance_export_url'))
    <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <strong>Maintenance archive created.</strong>
            <span class="text-muted">The resolved or cancelled request was exported into the automatic system folder and removed from the active maintenance page.</span>
        </div>
        <a href="{{ session('maintenance_export_url') }}" class="btn btn-sm btn-outline-dark" download="{{ session('maintenance_export_name', 'maintenance-request-archive.csv') }}">
            <i class="bi bi-download me-1"></i> Download Export
        </a>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="p-3 pb-0">
            <input type="text" id="maintenanceSearch" class="form-control" placeholder="Search requests...">
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="maintenanceTable">
                <thead class="table-light">
                    <tr>
                        <th class="d-none d-sm-table-cell" style="white-space: nowrap; width: 1%;">Date</th>
                        <th>Issue</th>
                        <th style="white-space: nowrap; width: 1%;">Type</th>
                        @if(Auth::user()->isAdmin())
                        <th style="white-space: nowrap; width: 1%;">Created By</th>
                        @endif
                        <th style="white-space: nowrap; width: 1%;">Priority</th>
                        <th class="d-none d-md-table-cell" style="white-space: nowrap; width: 1%;">Status</th>
                        <th class="d-none d-md-table-cell" style="white-space: nowrap; width: 1%;">Location</th>
                        <th class="text-end" style="white-space: nowrap; width: 1%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    @php
                        $rowClass = '';
                        if ($request->status === 'RESOLVED') {
                            $rowClass = 'table-success';
                        } elseif ($request->status === 'IN_PROGRESS') {
                            $rowClass = 'table-primary';
                        } elseif ($request->status === 'CANCELLED') {
                            $rowClass = 'table-secondary';
                        } else {
                            $rowClass = 'table-warning';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="d-none d-sm-table-cell">{{ $request->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="fw-bold">{{ Str::limit($request->title, 40) }}</div>
                            <div class="small text-muted">{{ Str::limit($request->description, 50) }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $request->type }}</span></td>
                        @if(Auth::user()->isAdmin())
                        <td>
                            @if($request->creator)
                                <div class="d-flex align-items-center">
                                    <div class="ms-2">
                                        <div class="fw-bold small">{{ $request->creator->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $request->creator->role->name ?? 'User' }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">System</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            @if($request->priority === 'EMERGENCY')
                                <span class="badge bg-danger">EMERGENCY</span>
                            @elseif($request->priority === 'HIGH')
                                <span class="badge bg-warning text-dark">High</span>
                            @elseif($request->priority === 'MEDIUM')
                                <span class="badge bg-info text-dark">Medium</span>
                            @else
                                <span class="badge bg-secondary">Low</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            @if($request->status === 'RESOLVED')
                                <span class="badge bg-success">Resolved</span>
                            @elseif($request->status === 'IN_PROGRESS')
                                <span class="badge bg-primary">In Progress</span>
                            @elseif($request->status === 'CANCELLED')
                                <span class="badge bg-secondary">Cancelled</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            @if($request->unit)
                                <div class="small fw-bold">{{ $request->unit->building->name }}</div>
                                <div class="small text-muted">Unit {{ $request->unit->unit_number }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('maintenance.show', $request) }}" class="btn btn-outline-secondary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->isAdmin() || auth()->user()->isManager() || $request->status === 'PENDING')
                                    <a href="{{ route('maintenance.edit', $request) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-tools fs-1 d-block mb-2"></i>
                            No maintenance requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white">
            {{ $requests->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('maintenanceSearch', 'maintenanceTable');
    });
</script>
@endpush
@endsection
