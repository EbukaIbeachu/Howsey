@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Recent Payments</h1>
        <p class="text-muted mb-0">Latest completed payments shown in the same format as the dashboard table.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Recent Payments</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Amount</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td>{{ $payment->rent->tenant->full_name }}</td>
                        <td class="text-success">₦{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->rent->unit)
                                {{ $payment->rent->unit->unit_number }}
                            @else
                                <span class="text-muted fst-italic">Unit Removed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-3">No recent payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($recentPayments, 'links'))
    <div class="card-footer">
        {{ $recentPayments->links() }}
    </div>
    @endif
</div>
@endsection
