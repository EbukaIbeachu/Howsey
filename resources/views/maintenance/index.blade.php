@extends('layouts.app')

@section('content')
@php
    $headers = [
        ['label' => 'Date', 'class' => 'd-none d-sm-table-cell'],
        'Issue',
        'Type',
    ];
    if (Auth::user()->isAdmin()) {
        $headers[] = 'Created By';
    }
    $headers[] = 'Priority';
    $headers[] = ['label' => 'Status', 'class' => 'd-none d-md-table-cell'];
    $headers[] = ['label' => 'Location', 'class' => 'd-none d-md-table-cell'];
    $headers[] = ['label' => 'Actions', 'class' => 'text-end'];
@endphp

<x-ui.page-header title="Maintenance Requests" id="maintenance-header">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" id="add-maintenance-btn" :href="route('maintenance.create')">New Request</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('maintenance_export_url'))
    <x-ui.alert tone="warning" title="Maintenance archive created." class="mb-3">
        <p class="mb-2">The resolved or cancelled request was exported into the automatic system folder and removed from the active maintenance page.</p>
        <x-ui.button
            variant="outline-secondary"
            size="sm"
            icon="bi bi-download"
            :href="session('maintenance_export_url')"
            :download="session('maintenance_export_name', 'maintenance-request-archive.csv')"
        >Download Export</x-ui.button>
    </x-ui.alert>
@endif

<x-ui.card flush>
    <div class="p-3 pb-0">
        <input type="text" id="maintenanceSearch" class="form-control" placeholder="Search requests...">
    </div>

    <x-ui.table id="maintenanceTable" :headers="$headers" class="mb-0">
        <tbody>
            @forelse($requests as $request)
            @php
                $rowClass = match ($request->status) {
                    'RESOLVED' => 'ui-row--success',
                    'IN_PROGRESS' => 'ui-row--primary',
                    'CANCELLED' => 'ui-row--neutral',
                    default => 'ui-row--warning',
                };
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="d-none d-sm-table-cell">{{ $request->created_at->format('M d, Y') }}</td>
                <td>
                    <div class="fw-bold">{{ Str::limit($request->title, 40) }}</div>
                    <div class="small text-muted">{{ Str::limit($request->description, 50) }}</div>
                </td>
                <td><x-ui.status-badge tone="neutral" :label="$request->type" /></td>
                @if(Auth::user()->isAdmin())
                <td>
                    @if($request->creator)
                        <div class="fw-bold small">{{ $request->creator->name }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $request->creator->role->name ?? 'User' }}</div>
                    @else
                        <span class="text-muted small fst-italic">System</span>
                    @endif
                </td>
                @endif
                <td>
                    @if($request->priority === 'EMERGENCY')
                        <x-ui.status-badge tone="danger" label="EMERGENCY" />
                    @elseif($request->priority === 'HIGH')
                        <x-ui.status-badge tone="warning" label="High" />
                    @elseif($request->priority === 'MEDIUM')
                        <x-ui.status-badge tone="info" label="Medium" />
                    @else
                        <x-ui.status-badge tone="neutral" label="Low" />
                    @endif
                </td>
                <td class="d-none d-md-table-cell">
                    @if($request->status === 'RESOLVED')
                        <x-ui.status-badge tone="success" label="Resolved" />
                    @elseif($request->status === 'IN_PROGRESS')
                        <x-ui.status-badge tone="primary" label="In Progress" />
                    @elseif($request->status === 'CANCELLED')
                        <x-ui.status-badge tone="neutral" label="Cancelled" />
                    @else
                        <x-ui.status-badge tone="warning" label="Pending" />
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
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="View Details" icon="bi bi-eye" :href="route('maintenance.show', $request)" />
                        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || $request->status === 'PENDING')
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" :href="route('maintenance.edit', $request)" />
                        @endif
                    </div>
                </td>
            </tr>
            @empty
                <x-ui.table.empty-state :colspan="7" icon="bi bi-tools" title="No maintenance requests found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($requests->hasPages())
        <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
    @endif
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('maintenanceSearch', 'maintenanceTable');
    });
</script>
@endpush
@endsection
