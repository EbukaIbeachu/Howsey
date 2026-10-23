@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Payment Claims</h1>
    <span class="badge bg-warning text-dark fs-6">{{ $pendingCount }} awaiting confirmation</span>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<ul class="nav nav-tabs mb-3">
    @foreach([
        \App\Models\PaymentClaim::STATUS_PENDING => 'Awaiting Confirmation',
        \App\Models\PaymentClaim::STATUS_CONFIRMED => 'Confirmed',
        \App\Models\PaymentClaim::STATUS_REJECTED => 'Not Confirmed',
    ] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('payment-claims.index', ['status' => $key]) }}">
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Property</th>
                        <th class="text-end">Amount</th>
                        <th>Date Paid</th>
                        <th>Reference</th>
                        <th>Proof</th>
                        <th>Submitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $claim->tenant->full_name ?? 'Unknown tenant' }}</div>
                                @if($claim->note)
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($claim->note, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $claim->rent->unit->building->name ?? 'N/A' }}</div>
                                <div class="text-muted small">Unit {{ $claim->rent->unit->unit_number ?? 'N/A' }}</div>
                            </td>
                            <td class="text-end fw-semibold">₦{{ number_format((float) $claim->amount, 2) }}</td>
                            <td>{{ $claim->payment_date ? $claim->payment_date->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <div>{{ $claim->reference ?: '—' }}</div>
                                <div class="text-muted small">{{ $claim->payment_method ?: '' }}</div>
                            </td>
                            <td>
                                @if($claim->proof_path)
                                    <a href="{{ route('payment-claims.proof', $claim) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-paperclip"></i> View
                                    </a>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $claim->created_at->format('M d, Y H:i') }}
                                @if($claim->submitter)
                                    <div>by {{ $claim->submitter->name }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($claim->is_pending)
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#confirmClaim{{ $claim->id }}">
                                        Confirm
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectClaim{{ $claim->id }}">
                                        Reject
                                    </button>
                                @else
                                    @if($claim->status === \App\Models\PaymentClaim::STATUS_CONFIRMED)
                                        <span class="badge bg-success">Confirmed</span>
                                    @else
                                        <span class="badge bg-danger">Not Confirmed</span>
                                    @endif
                                    <div class="text-muted small mt-1">
                                        {{ $claim->confirmer->name ?? 'N/A' }}
                                        @if($claim->confirmed_at)
                                            <div>{{ $claim->confirmed_at->format('M d, Y') }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">There are no payment claims in this view.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($claims->hasPages())
        <div class="card-footer bg-white">
            {{ $claims->links() }}
        </div>
    @endif
</div>

@foreach($claims as $claim)
    @if($claim->is_pending)
        <div class="modal fade" id="confirmClaim{{ $claim->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('payment-claims.confirm', $claim) }}" data-loader-ignore>
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                Confirming records a completed payment for
                                <strong>{{ $claim->tenant->full_name ?? 'this tenant' }}</strong> and updates their balance.
                            </p>

                            <div class="mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ (float) $claim->amount }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ $claim->payment_date ? $claim->payment_date->format('Y-m-d') : now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    @foreach(['BANK_TRANSFER' => 'Bank Transfer', 'CASH' => 'Cash', 'CHEQUE' => 'Cheque', 'POS' => 'POS / Card', 'MOBILE_MONEY' => 'Mobile Money'] as $value => $label)
                                        <option value="{{ $value }}" {{ ($claim->payment_method ?: 'BANK_TRANSFER') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Reviewer Note <span class="text-muted small">(optional)</span></label>
                                <textarea name="review_note" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm &amp; Record Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectClaim{{ $claim->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('payment-claims.reject', $claim) }}" data-loader-ignore>
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Payment Claim</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">The tenant will be told why this claim could not be confirmed.</p>
                            <div class="mb-0">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <textarea name="review_note" class="form-control" rows="3" maxlength="1000" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject Claim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
