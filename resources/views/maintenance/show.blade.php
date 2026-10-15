@extends('layouts.app')

@section('content')
@php
    $submittedAt = $maintenance->created_at instanceof \Carbon\CarbonInterface
        ? $maintenance->created_at
        : \Illuminate\Support\Carbon::parse($maintenance->created_at);
    $resolvedAt = $maintenance->resolved_at
        ? ($maintenance->resolved_at instanceof \Carbon\CarbonInterface
            ? $maintenance->resolved_at
            : \Illuminate\Support\Carbon::parse($maintenance->resolved_at))
        : null;
@endphp
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Request Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">

        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || $maintenance->status === 'PENDING')
            <a href="{{ route('maintenance.edit', $maintenance) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $maintenance->title }}</h5>
                <div>
                    @if($maintenance->status === 'RESOLVED')
                        <span class="badge bg-success">Resolved</span>
                    @elseif($maintenance->status === 'IN_PROGRESS')
                        <span class="badge bg-primary">In Progress</span>
                    @elseif($maintenance->status === 'CANCELLED')
                        <span class="badge bg-secondary">Cancelled</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small">Description</h6>
                    <p class="card-text">{{ $maintenance->description }}</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted text-uppercase small">Issue Type</h6>
                        <p class="fw-bold">{{ $maintenance->type }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted text-uppercase small">Priority</h6>
                        <p>
                            @if($maintenance->priority === 'EMERGENCY')
                                <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> EMERGENCY</span>
                            @elseif($maintenance->priority === 'HIGH')
                                <span class="text-warning fw-bold">High</span>
                            @else
                                {{ ucfirst(strtolower($maintenance->priority)) }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small">Submitted By</h6>
                            <p>
                                {{ $maintenance->tenant->full_name }}<br>
                                <small class="text-muted">{{ $submittedAt->format('M d, Y h:i A') }}</small>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small">Location</h6>
                            <p>
                                {{ $maintenance->unit->building->name }}<br>
                                Unit {{ $maintenance->unit->unit_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @if($resolvedAt)
            <div class="card-footer bg-success text-white">
                <i class="bi bi-check-circle-fill me-2"></i> Resolved on {{ $resolvedAt->format('M d, Y h:i A') }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Status Actions</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('maintenance.update', $maintenance) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <input type="hidden" name="title" value="{{ $maintenance->title }}">
                    <input type="hidden" name="description" value="{{ $maintenance->description }}">
                    <input type="hidden" name="type" value="{{ $maintenance->type }}">
                    <input type="hidden" name="priority" value="{{ $maintenance->priority }}">
                    
                    <div class="d-grid gap-2">
                        @if($maintenance->status !== 'IN_PROGRESS' && $maintenance->status !== 'RESOLVED' && $maintenance->status !== 'CANCELLED')
                            <button type="submit" name="status" value="IN_PROGRESS" class="btn btn-primary">
                                Mark In Progress
                            </button>
                        @endif

                        @if($maintenance->status !== 'RESOLVED' && $maintenance->status !== 'CANCELLED')
                            <button type="submit" name="status" value="RESOLVED" class="btn btn-success">
                                Mark Resolved
                            </button>
                        @endif

                        @if($maintenance->status !== 'CANCELLED')
                            <button type="submit" name="status" value="CANCELLED" class="btn btn-outline-secondary">
                                Cancel Request
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
