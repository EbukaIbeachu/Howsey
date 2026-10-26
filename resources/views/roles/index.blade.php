@extends('layouts.app')

@section('content')
<x-ui.page-header title="Roles">
    <x-slot:actions>
        @permission('roles.create')
            <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('roles.create')">Add New Role</x-ui.button>
        @endpermission
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card flush>
    <x-ui.table class="mb-0" :headers="['Name', 'Description', 'Users Count', 'Permissions', 'Actions']">
        <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ ucfirst($role->name) }}</td>
                    <td>{{ $role->description ?? '-' }}</td>
                    <td>
                        <x-ui.status-badge
                            tone="info"
                            icon="bi bi-people-fill"
                            :label="$role->users->count()"
                        />
                    </td>
                    <td>
                        <div class="small text-muted">
                            {{ $role->permissions->pluck('name')->take(4)->implode(', ') ?: '-' }}
                            @if($role->permissions->count() > 4)
                                <span class="d-block">+{{ $role->permissions->count() - 4 }} more</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="ui-btn-group">
                            @permission('roles.edit')
                                <x-ui.icon-button variant="default" icon="bi bi-pencil" :href="route('roles.edit', $role)" label="Edit role" />
                            @endpermission
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This role cannot be deleted if it has assigned users.')">
                                @csrf
                                @method('DELETE')
                                @permission('roles.delete')
                                    <x-ui.icon-button
                                        type="submit"
                                        variant="danger"
                                        icon="bi bi-trash"
                                        label="Delete role"
                                        :disabled="$role->users->count() > 0 || $role->is_system"
                                    />
                                @endpermission
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="5" icon="bi bi-shield-lock" title="No roles found." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>
@endsection
