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

<x-ui.page-header title="Request Details">
    <x-slot:actions>
        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || $maintenance->status === 'PENDING')
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil" :href="route('maintenance.edit', $maintenance)">Edit</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<div class="row">
    <div class="col-md-8">
        <x-ui.card :title="$maintenance->title" class="mb-4">
            <x-slot:actions>
                @if($maintenance->status === 'RESOLVED')
                    <x-ui.status-badge tone="success" label="Resolved" />
                @elseif($maintenance->status === 'IN_PROGRESS')
                    <x-ui.status-badge tone="primary" label="In Progress" />
                @elseif($maintenance->status === 'CANCELLED')
                    <x-ui.status-badge tone="neutral" label="Cancelled" />
                @else
                    <x-ui.status-badge tone="warning" label="Pending" />
                @endif
            </x-slot:actions>

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
                            <x-ui.status-badge tone="danger" label="EMERGENCY" />
                        @elseif($maintenance->priority === 'HIGH')
                            <x-ui.status-badge tone="warning" label="High" />
                        @elseif($maintenance->priority === 'MEDIUM')
                            <x-ui.status-badge tone="info" label="Medium" />
                        @else
                            <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($maintenance->priority))" />
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

            @if($resolvedAt)
                <x-slot:footer>
                    <p class="mb-0 text-success fw-semibold">
                        <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>Resolved on {{ $resolvedAt->format('M d, Y h:i A') }}
                    </p>
                </x-slot:footer>
            @endif
        </x-ui.card>
    </div>

    <div class="col-md-4">
        <x-ui.card title="Status Actions">
            <form action="{{ route('maintenance.update', $maintenance) }}" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="title" value="{{ $maintenance->title }}">
                <input type="hidden" name="description" value="{{ $maintenance->description }}">
                <input type="hidden" name="type" value="{{ $maintenance->type }}">
                <input type="hidden" name="priority" value="{{ $maintenance->priority }}">

                <div class="d-grid gap-2">
                    @if($maintenance->status !== 'IN_PROGRESS' && $maintenance->status !== 'RESOLVED' && $maintenance->status !== 'CANCELLED')
                        <x-ui.button type="submit" name="status" value="IN_PROGRESS" variant="primary">Mark In Progress</x-ui.button>
                    @endif

                    @if($maintenance->status !== 'RESOLVED' && $maintenance->status !== 'CANCELLED')
                        <x-ui.button type="submit" name="status" value="RESOLVED" variant="success">Mark Resolved</x-ui.button>
                    @endif

                    @if($maintenance->status !== 'CANCELLED')
                        <x-ui.button type="submit" name="status" value="CANCELLED" variant="outline-secondary">Cancel Request</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
