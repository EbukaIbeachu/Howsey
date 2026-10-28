@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Role</h1>

</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Role Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        <div class="form-text">Must be unique (e.g., admin, manager, tenant).</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Permissions</label>
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
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
