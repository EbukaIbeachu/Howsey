@extends('layouts.app')

@section('content')
<x-ui.page-header title="Add New Role" />

<div class="row">
    <div class="col-lg-8">
        <x-ui.card>
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf

                <x-ui.form.input
                    name="name"
                    label="Role Name"
                    hint="Must be unique (e.g., admin, manager, tenant)."
                    required
                />

                <x-ui.form.textarea name="description" label="Description" :rows="3" />

                <div class="mb-4">
                    <label class="ui-label">Permissions</label>
                    <div class="border rounded p-3">
                        @forelse($permissions as $module => $items)
                            <div class="mb-3">
                                <div class="fw-semibold text-capitalize mb-2">{{ str_replace('_', ' ', $module) }}</div>
                                <div class="row g-2">
                                    @foreach($items as $permission)
                                        <div class="col-md-6">
                                            <label class="form-check border rounded p-2 h-100">
                                                <input class="form-check-input me-2" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permission_ids', [])) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $permission->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No permissions seeded yet. Run the SaaS migration first.</p>
                        @endforelse
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <x-ui.button type="submit" variant="primary">Create Role</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
