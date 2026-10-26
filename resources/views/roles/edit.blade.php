@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Role: {{ ucfirst($role->name) }}" />

<div class="row">
    <div class="col-lg-8">
        <x-ui.card>
            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                <x-ui.form.input name="name" label="Role Name" :value="$role->name" required />

                <x-ui.form.textarea name="description" label="Description" :value="$role->description" :rows="3" />

                <div class="mb-4">
                    <label class="ui-label">Permissions</label>
                    <div class="border rounded p-3">
                        @foreach($permissions as $module => $items)
                            <div class="mb-3">
                                <div class="fw-semibold text-capitalize mb-2">{{ str_replace('_', ' ', $module) }}</div>
                                <div class="row g-2">
                                    @foreach($items as $permission)
                                        <div class="col-md-6">
                                            <label class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input me-2" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permission_ids', $role->permissions->pluck('id')->all())) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $permission->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <x-ui.button type="submit" variant="primary">Update Role</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
