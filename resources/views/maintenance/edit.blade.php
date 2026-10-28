@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Request</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('maintenance.show', $maintenance) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Cancel
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form action="{{ route('maintenance.update', $maintenance) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="PENDING" {{ (old('status') ?? $maintenance->status) == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="IN_PROGRESS" {{ (old('status') ?? $maintenance->status) == 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                                <option value="RESOLVED" {{ (old('status') ?? $maintenance->status) == 'RESOLVED' ? 'selected' : '' }}>Resolved</option>
                                <option value="CANCELLED" {{ (old('status') ?? $maintenance->status) == 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                <option value="LOW" {{ (old('priority') ?? $maintenance->priority) == 'LOW' ? 'selected' : '' }}>Low</option>
                                <option value="MEDIUM" {{ (old('priority') ?? $maintenance->priority) == 'MEDIUM' ? 'selected' : '' }}>Medium</option>
                                <option value="HIGH" {{ (old('priority') ?? $maintenance->priority) == 'HIGH' ? 'selected' : '' }}>High</option>
                                <option value="EMERGENCY" {{ (old('priority') ?? $maintenance->priority) == 'EMERGENCY' ? 'selected' : '' }}>Emergency</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Issue Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="PLUMBING" {{ (old('type') ?? $maintenance->type) == 'PLUMBING' ? 'selected' : '' }}>Plumbing</option>
                            <option value="ELECTRICAL" {{ (old('type') ?? $maintenance->type) == 'ELECTRICAL' ? 'selected' : '' }}>Electrical</option>
                            <option value="HVAC" {{ (old('type') ?? $maintenance->type) == 'HVAC' ? 'selected' : '' }}>HVAC</option>
                            <option value="STRUCTURAL" {{ (old('type') ?? $maintenance->type) == 'STRUCTURAL' ? 'selected' : '' }}>Structural</option>
                            <option value="APPLIANCE" {{ (old('type') ?? $maintenance->type) == 'APPLIANCE' ? 'selected' : '' }}>Appliance</option>
                            <option value="OTHER" {{ (old('type') ?? $maintenance->type) == 'OTHER' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') ?? $maintenance->title }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5" required>{{ old('description') ?? $maintenance->description }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
