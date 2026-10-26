@extends('layouts.app')

@section('content')
<x-ui.page-header title="Units">
    <x-slot:actions>
        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('units.create')">Add Unit</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <x-ui.table :striped="true" caption="Units" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col">Unit</th>
                <th scope="col">Building</th>
                <th scope="col">Floor</th>
                <th scope="col">Type</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($units as $unit)
            <tr class="@if($unit->status === 'OCCUPIED') ui-row--success @elseif($unit->status === 'MAINTENANCE') ui-row--warning @else ui-row--neutral @endif">
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
                        <x-ui.status-badge tone="info" label="Warehouse" />
                    @else
                        {{ $unit->bedrooms }} Bed / {{ $unit->bathrooms }} Bath
                    @endif
                </td>
                <td>
                    @if($unit->status === 'OCCUPIED')
                        <x-ui.status-badge tone="success" label="Occupied" />
                    @elseif($unit->status === 'MAINTENANCE')
                        <x-ui.status-badge tone="warning" label="Maintenance" />
                    @else
                        <x-ui.status-badge tone="neutral" label="Available" />
                    @endif
                </td>
                <td class="text-end">
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('units.show', $unit)" />
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
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" size="sm" :href="route('units.edit', $unit)" />
                        @endif
                        @if(Auth::user()->isAdmin())
                            <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this unit?');">
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
                :colspan="6"
                icon="bi bi-door-closed"
                title="No units found"
                text='Click "Add Unit" to create one.'
            />
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-3">
        {{ $units->links() }}
    </div>
</x-ui.card>
@endsection
