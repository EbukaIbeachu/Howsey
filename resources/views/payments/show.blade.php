@extends('layouts.app')

@section('content')
<x-ui.page-header title="Payment Details">
    <x-slot:actions>
        @if(auth()->user()->isManager())
            @include('partials.manager-edit-request-button', [
                'recordType' => 'payment',
                'recordId' => $payment->id,
                'recordLabel' => 'Payment #'.$payment->id,
                'buttonClass' => 'btn btn-sm btn-warning',
                'buttonText' => 'Request Edit',
                'iconClass' => 'bi bi-pencil-square',
            ])
        @else
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil" :href="route('payments.edit', $payment)">Edit Payment</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-6">
        <x-ui.card title="Transaction Summary">
            <div class="text-center mb-4">
                <h2 class="display-4 fw-bold text-primary">₦{{ number_format($payment->amount, 2) }}</h2>
                <p class="text-muted">{{ $payment->payment_date }}</p>

                @if($payment->status === 'COMPLETED')
                    <x-ui.status-badge tone="success" label="Completed" />
                @elseif($payment->status === 'PENDING')
                    <x-ui.status-badge tone="warning" label="Pending" />
                @else
                    <x-ui.status-badge tone="danger" label="Failed" />
                @endif
            </div>

            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">Payment Method</span>
                    <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">Transaction ID</span>
                    <span class="font-monospace small">{{ $payment->id }}</span>
                </li>
            </ul>
            @if(auth()->user()->isAdmin() && $payment->creator)
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="bi bi-person-check me-1"></i> Payment recorded by: {{ $payment->creator->name }}
                    </small>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="col-md-6">
        <x-ui.card title="Associated Lease" class="h-100">
            <div class="mb-4">
                <h6 class="text-muted mb-2">Payer (Tenant)</h6>
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <a href="{{ route('tenants.show', $payment->rent->tenant) }}" class="text-decoration-none fw-bold">
                            {{ $payment->rent->tenant->full_name }}
                        </a>
                        <div class="text-muted small">{{ $payment->rent->tenant->email }}</div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-muted mb-2">Property Unit</h6>
                <div class="d-flex align-items-center">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="bi bi-house"></i>
                    </div>
                    <div>
                        <a href="{{ route('units.show', $payment->rent->unit) }}" class="text-decoration-none fw-bold">
                            Unit {{ $payment->rent->unit->unit_number }}
                        </a>
                        <div class="text-muted small">{{ $payment->rent->unit->building->name }}</div>
                    </div>
                </div>
            </div>

            <div>
                <h6 class="text-muted mb-2">Lease Details</h6>
                <div class="d-grid gap-2">
                    <x-ui.button variant="outline-primary" size="sm" :href="route('rents.show', $payment->rent)">View Lease Agreement</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
