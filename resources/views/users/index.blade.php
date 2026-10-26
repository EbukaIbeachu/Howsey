@extends('layouts.app')

@section('content')
<x-ui.page-header title="Users">
    <x-slot:actions>
        @if((auth()->user()->isAdmin() || auth()->user()->isManager()) && Route::has('password-recovery-requests.index'))
            <x-ui.button variant="outline-warning" size="sm" icon="bi bi-key" :href="route('password-recovery-requests.index')">Password Recovery Requests</x-ui.button>
        @endif
        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('users.create')">Add New User</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card flush>
    <x-ui.table class="mb-0" :headers="['Name', 'Account ID', 'Role', 'Status', 'Actions']" caption="Users" :caption-sr="true">
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <x-ui.status-badge tone="neutral" :label="$user->role->name ?? 'N/A'" />
                </td>
                <td>
                    @if($user->is_active)
                        <x-ui.status-badge tone="success" label="Active" />
                    @else
                        <x-ui.status-badge tone="danger" label="Inactive" />
                    @endif
                </td>
                <td>
                    <div class="ui-btn-group">
                        @if(auth()->id() !== $user->id && (auth()->user()->isAdmin() || (auth()->user()->isManager() && $user->role->name === 'tenant')))
                            <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <x-ui.icon-button
                                    type="submit"
                                    :variant="$user->is_active ? 'warning' : 'success'"
                                    :icon="$user->is_active ? 'bi bi-person-slash' : 'bi bi-person-check'"
                                    :label="$user->is_active ? 'Deactivate' : 'Activate'"
                                />
                            </form>
                        @endif
                        <x-ui.icon-button variant="default" icon="bi bi-pencil" :href="route('users.edit', $user)" label="Edit user" />
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.icon-button type="submit" variant="danger" icon="bi bi-trash" label="Delete user" />
                        </form>
                    </div>
                </td>
            </tr>
            @empty
                <x-ui.table.empty-state :colspan="5" icon="bi bi-people" title="No users found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($users->hasPages())
        <x-slot:footer>{{ $users->links() }}</x-slot:footer>
    @endif
</x-ui.card>
@endsection
