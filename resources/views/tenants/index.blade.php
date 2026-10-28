@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        /* Force background color on cells to override Bootstrap's striped/hover styles */
        .mobile-status-active, 
        .mobile-status-active > td, 
        .mobile-status-active > th { 
            background-color: #d1e7dd !important; 
            --bs-table-accent-bg: #d1e7dd !important;
            --bs-table-bg: #d1e7dd !important;
        }
        .mobile-status-inactive, 
        .mobile-status-inactive > td, 
        .mobile-status-inactive > th { 
            background-color: #e2e3e5 !important; 
            --bs-table-accent-bg: #e2e3e5 !important;
            --bs-table-bg: #e2e3e5 !important;
        }

        #tenantsTable .tenant-move-in-col {
            display: none;
        }

        #tenantsTable td,
        #tenantsTable td a,
        #tenantsTable td .text-dark,
        #tenantsTable td .fw-bold,
        #tenantsTable td .text-muted,
        #tenantsTable td small.text-muted,
        #tenantsTable td .fst-italic,
        #tenantsTable td .bi.text-muted {
            color: #212529 !important;
        }

        #tenantsTable td .badge.bg-secondary {
            color: #ffffff !important;
        }
    }
</style>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="tenants-header">Tenants</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button class="btn btn-sm btn-outline-info me-2" onclick="startTour()">
            <i class="bi bi-question-circle"></i> Help
        </button>
        @permission('tenants.create')
            <a href="{{ route('tenants.create') }}" class="btn btn-sm btn-primary" id="add-tenant-btn">
                <i class="bi bi-plus-lg"></i> Register Tenant
            </a>
        @endpermission
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="tenantSearch" class="form-control" placeholder="Search tenants...">
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="tenantsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Unit</th>
                        <th class="d-none d-md-table-cell">Contact</th>
                        <th class="tenant-move-in-col">Move In</th>
                        @permission('tenants.delete')
                        <th>Created By</th>
                        @endpermission
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr class="{{ $tenant->active ? 'mobile-status-active' : 'mobile-status-inactive' }}">
                        <td>
                            <a href="{{ route('tenants.show', $tenant) }}" class="text-decoration-none fw-bold">
                                {{ $tenant->full_name }}
                            </a>
                        </td>
                        <td>
                            @if($tenant->unit)
                                <a href="{{ route('units.show', $tenant->unit) }}" class="text-decoration-none text-dark">
                                    {{ $tenant->unit->unit_number }}
                                </a>
                                <br>
                                <small class="text-muted">{{ $tenant->unit->building->name }}</small>
                            @else
                                <span class="text-muted fst-italic">No Unit Assigned</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            @if($tenant->email)
                                <div><i class="bi bi-envelope me-1 text-muted"></i> {{ $tenant->email }}</div>
                            @endif
                            @if($tenant->phone)
                                <div><i class="bi bi-telephone me-1 text-muted"></i> {{ $tenant->phone }}</div>
                            @endif
                        </td>
                        <td class="tenant-move-in-col">{{ $tenant->move_in_date }}</td>
                        @permission('tenants.delete')
                        <td>
                            @if($tenant->creator)
                                <div class="d-flex align-items-center">
                                    <div class="ms-2">
                                        <div class="fw-bold small">{{ $tenant->creator->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $tenant->creator->role->name ?? 'User' }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">System</span>
                            @endif
                        </td>
                        @endpermission
                        <td class="d-none d-md-table-cell">
                            @if($tenant->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                            @if($tenant->rent_reminders_exempt)
                                <span class="badge bg-warning text-dark" title="Automated rent reminders are paused for this tenant">
                                    <i class="bi bi-bell-slash me-1"></i>Reminders Paused
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->isManager() && auth()->user()->hasPermission('tenants.edit'))
                                    @include('partials.manager-edit-request-button', [
                                        'recordType' => 'tenant',
                                        'recordId' => $tenant->id,
                                        'recordLabel' => $tenant->full_name,
                                        'buttonClass' => 'btn btn-sm btn-outline-warning',
                                        'buttonText' => 'Request Edit',
                                        'iconClass' => 'bi bi-pencil-square',
                                    ])
                                @elseif(auth()->user()->hasPermission('tenants.edit'))
                                    <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                @permission('tenants.delete')
                                <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tenant?');">
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
                    <tr>
                        <td colspan="{{ auth()->user()->hasPermission('tenants.delete') ? 7 : 6 }}" class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                            No tenants found. Click "Register Tenant" to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $tenants->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('tenantSearch', 'tenantsTable');
    });
</script>
@endpush
@endsection
