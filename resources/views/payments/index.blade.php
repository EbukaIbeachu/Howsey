@extends('layouts.app')

@section('content')
<x-ui.page-header title="Payments" id="payments-header">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
        @permission('payments.create')
            <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('payments.create')" id="add-payment-btn">Record Payment</x-ui.button>
        @endpermission
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <div class="mb-3">
        <input type="text" id="paymentSearch" class="form-control" placeholder="Search payments...">
    </div>

    <x-ui.table id="paymentsTable" :striped="true" caption="Payments" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col" class="d-none d-md-table-cell">Date</th>
                <th scope="col">Tenant</th>
                @permission('payments.delete')
                <th scope="col">Created By</th>
                @endpermission
                <th scope="col">Amount</th>
                <th scope="col" class="d-none d-md-table-cell">Method</th>
                <th scope="col" class="d-none d-md-table-cell">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr class="@if($payment->status === 'COMPLETED') ui-row--success @elseif($payment->status === 'PENDING') ui-row--warning @else ui-row--danger @endif">
                <td class="d-none d-md-table-cell">{{ $payment->payment_date }}</td>
                <td>
                    <a href="{{ route('tenants.show', $payment->rent->tenant) }}" class="text-decoration-none fw-bold">
                        {{ $payment->rent->tenant->full_name }}
                    </a>
                    <br>
                    <small class="text-muted">
                        @if($payment->rent->unit)
                            Unit {{ $payment->rent->unit->unit_number }}
                        @else
                            <span class="fst-italic">Unit Removed</span>
                        @endif
                    </small>
                </td>
                @permission('payments.delete')
                <td>
                    @if($payment->creator)
                        <div class="d-flex align-items-center">
                            <div class="ms-2">
                                <div class="fw-bold small">{{ $payment->creator->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $payment->creator->role->name ?? 'User' }}</div>
                            </div>
                        </div>
                    @else
                        <span class="text-muted small fst-italic">System</span>
                    @endif
                </td>
                @endpermission
                <td>₦{{ number_format($payment->amount, 2) }}</td>
                <td class="d-none d-md-table-cell">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td class="d-none d-md-table-cell">
                    @if($payment->status === 'COMPLETED')
                        <x-ui.status-badge tone="success" label="Completed" />
                    @elseif($payment->status === 'PENDING')
                        <x-ui.status-badge tone="warning" label="Pending" />
                    @else
                        <x-ui.status-badge tone="danger" label="Failed" />
                    @endif
                </td>
                <td class="text-end">
                    <div class="ui-btn-group">
                        <x-ui.icon-button label="View" icon="bi bi-eye" size="sm" :href="route('payments.show', $payment)" />
                        @permission('payments.view')
                        @if(strtoupper((string) $payment->status) === 'COMPLETED')
                            <x-ui.icon-button label="Download Receipt" icon="bi bi-download" variant="success" size="sm" :href="route('payments.receipt', ['payment' => $payment, 'download' => 1])" />
                        @endif
                        @endpermission
                        @if(Auth::user()->isManager() && auth()->user()->hasPermission('payments.edit'))
                            @include('partials.manager-edit-request-button', [
                                'recordType' => 'payment',
                                'recordId' => $payment->id,
                                'recordLabel' => 'Payment #'.$payment->id,
                                'buttonClass' => 'btn btn-sm btn-outline-warning',
                                'buttonText' => 'Request Edit',
                                'iconClass' => 'bi bi-pencil-square',
                            ])
                        @elseif(auth()->user()->hasPermission('payments.edit'))
                            <x-ui.icon-button label="Edit" icon="bi bi-pencil" size="sm" :href="route('payments.edit', $payment)" />
                        @endif
                        @permission('payments.delete')
                        <form action="{{ route('payments.destroy', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.icon-button type="submit" variant="danger" size="sm" icon="bi bi-trash" label="Delete" />
                        </form>
                        @endpermission
                    </div>
                </td>
            </tr>
            @empty
            <x-ui.table.empty-state
                :colspan="auth()->user()->hasPermission('payments.delete') ? 7 : 6"
                icon="bi bi-cash-stack"
                title="No payments found"
                text='Click "Record Payment" to add one.'
            />
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-3">
        {{ $payments->links() }}
    </div>
</x-ui.card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('paymentSearch', 'paymentsTable');
    });
</script>
@endpush
@endsection
