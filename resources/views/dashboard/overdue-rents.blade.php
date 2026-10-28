@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Overdue Rents</h1>
        <p class="text-muted mb-0">Outstanding rent balances shown in the same format as the dashboard table.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">Overdue Rents</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive overdue-rents-detail-wrap">
            <table class="table table-hover mb-0 overdue-rents-table overdue-rents-detail-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Unit</th>
                        <th>Due Amount</th>
                        <th class="text-center overdue-pay-header" style="width: 1%;" title="Pay">
                            <i class="bi bi-play-circle"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdueRents as $rent)
                    <tr>
                        <td data-label="Tenant">{{ $rent->tenant->full_name }}</td>
                        <td data-label="Unit">{{ $rent->unit->building->name }} - {{ $rent->unit->unit_number }}</td>
                        <td data-label="Due Amount" class="text-danger fw-bold overdue-due-amount">₦{{ number_format($rent->balance, 2) }}</td>
                        <td data-label="Action" class="text-center overdue-pay-cell">
                            <a href="{{ route('payments.create', ['rent_id' => $rent->id]) }}" class="btn btn-sm btn-primary overdue-pay-btn" title="Pay overdue rent" aria-label="Pay overdue rent">
                                Pay
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 overdue-rents-empty">No overdue rents.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
