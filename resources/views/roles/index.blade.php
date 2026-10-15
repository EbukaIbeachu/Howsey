@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Roles</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @permission('roles.create')
            <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Role
            </a>
        @endpermission
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Users Count</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td>{{ ucfirst($role->name) }}</td>
                        <td>{{ $role->description ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info rounded-pill">{{ $role->users->count() }}</span>
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
                            <div class="btn-group">
                                @permission('roles.edit')
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endpermission
                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This role cannot be deleted if it has assigned users.')">
                                    @csrf
                                    @method('DELETE')
                                    @permission('roles.delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" {{ $role->users->count() > 0 || $role->is_system ? 'disabled' : '' }}>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endpermission
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-3">No roles found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
