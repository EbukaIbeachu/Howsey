@extends('layouts.app')

@section('content')
<x-ui.page-header title="Tenants" id="tenants-header">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
        @permission('tenants.create')
            <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('tenants.create')" id="add-tenant-btn">Register Tenant</x-ui.button>
        @endpermission
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <div class="mb-3">
        <input type="text" id="tenantSearch" class="form-control" placeholder="Search tenants...">
    </div>

    <x-ui.table id="tenantsTable" :striped="true">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Unit</th>
                <th scope="col" class="d-none d-md-table-cell">Contact</th>
                <th scope="col" class="d-none d-md-table-cell">Move In</th>
                @permission('tenants.delete')
                <th scope="col">Created By</th>
                @endpermission
                <th scope="col" class="d-none d-md-table-cell">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
            <tr class="{{ $tenant->active ? 'ui-row--success' : 'ui-row--neutral' }}">
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
                <td class="d-none d-md-table-cell">{{ $tenant->move_in_date }}</td>
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
                        <x-ui.status-badge tone="success" label="Active" />
                    @else
                        <x-ui.status-badge tone="neutral" label="Inactive" />
                    @endif
                    @if($tenant->rent_reminders_exempt)
                        <x-ui.status-badge tone="warning" icon="bi bi-bell-slash" label="Reminders Paused" title="Automated rent reminders are paused for this tenant" />
                    @endif
                </td>
                <td class="text-end">
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('tenants.show', $tenant)" />
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
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" size="sm" :href="route('tenants.edit', $tenant)" />
                        @endif
                        @permission('tenants.delete')
                        <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tenant?');">
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
                :colspan="auth()->user()->hasPermission('tenants.delete') ? 7 : 6"
                icon="bi bi-people"
                title="No tenants found"
                text='Click "Register Tenant" to add one.'
            />
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-3">
        {{ $tenants->links() }}
    </div>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('tenantSearch', 'tenantsTable');
    });
</script>
@endpush
@endsection
