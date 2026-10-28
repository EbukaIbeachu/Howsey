@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pay Rent</h1>
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">Amount Due</h5>
            </div>
            <div class="card-body">
                <label class="small text-muted text-uppercase">Outstanding Balance</label>
                <div class="display-6 fw-bold mb-3">₦{{ number_format($rent->balance, 2) }}</div>

                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Tenant</dt>
                    <dd class="col-6 text-end">{{ $rent->tenant->full_name ?? auth()->user()->name }}</dd>

                    <dt class="col-6 text-muted">Building</dt>
                    <dd class="col-6 text-end">{{ $rent->unit->building->name ?? 'N/A' }}</dd>

                    <dt class="col-6 text-muted">Unit</dt>
                    <dd class="col-6 text-end">{{ $rent->unit->unit_number ?? 'N/A' }}</dd>

                    <dt class="col-6 text-muted">{{ $rent->rent_amount_label }}</dt>
                    <dd class="col-6 text-end">₦{{ number_format($rent->annual_amount, 2) }}</dd>

                    <dt class="col-6 text-muted">Next Due Date</dt>
                    <dd class="col-6 text-end">{{ $rent->next_due_date ? $rent->next_due_date->format('M d, Y') : 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Where To Pay</h5>
                <a href="{{ route('payments.proof', $rent) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-check2-circle"></i> I have made payment
                </a>
            </div>
            <div class="card-body">
                @forelse($accounts as $account)
                    <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">{{ $account->display_name }}</div>
                                <div class="text-muted small">{{ $account->bank_name }}@if($account->branch) &middot; {{ $account->branch }}@endif</div>
                            </div>
                            @if($account->is_primary)
                                <span class="badge bg-primary">Primary</span>
                            @endif
                        </div>

                        <dl class="row mb-0 mt-3 small">
                            <dt class="col-sm-4 text-muted">Account Name</dt>
                            <dd class="col-sm-8 fw-semibold">{{ $account->account_name }}</dd>

                            <dt class="col-sm-4 text-muted">Account Number</dt>
                            <dd class="col-sm-8">
                                <span class="fw-bold fs-6">{{ $account->account_number }}</span>
                                <button type="button"
                                        class="btn btn-sm btn-link p-0 ms-2 align-baseline copy-account"
                                        data-copy="{{ $account->account_number }}">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </dd>
                        </dl>

                        @if($account->instructions)
                            <div class="alert alert-light border small mt-3 mb-0">{{ $account->instructions }}</div>
                        @endif
                    </div>
                @empty
                    <div class="alert alert-warning mb-0">
                        <div class="fw-semibold mb-1">No receiving account has been published yet</div>
                        <div class="small">Please contact the office for payment details before making a transfer.</div>
                    </div>
                @endforelse

                <div class="alert alert-light border small mt-3 mb-0">
                    Once you have made the transfer, click <strong>I have made payment</strong> above, tell us the details
                    and attach your proof. Your landlord and the building manager will be notified so they can confirm it.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">My Payment Notifications</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date Paid</th>
                        <th class="text-end">Amount</th>
                        <th>Reference</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Reviewer Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($claims as $claim)
                        <tr>
                            <td>{{ $claim->payment_date ? $claim->payment_date->format('M d, Y') : 'N/A' }}</td>
                            <td class="text-end fw-semibold">₦{{ number_format((float) $claim->amount, 2) }}</td>
                            <td>{{ $claim->reference ?: '—' }}</td>
                            <td class="text-muted small">{{ $claim->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($claim->status === \App\Models\PaymentClaim::STATUS_CONFIRMED)
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($claim->status === \App\Models\PaymentClaim::STATUS_REJECTED)
                                    <span class="badge bg-danger">Not Confirmed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Awaiting Confirmation</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $claim->review_note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">You have not submitted any payment notification yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.copy-account');
        if (!button) {
            return;
        }

        var value = button.getAttribute('data-copy') || '';
        var done = function () {
            var original = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check2"></i> Copied';
            setTimeout(function () { button.innerHTML = original; }, 1800);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(done).catch(function () {});
            return;
        }

        var input = document.createElement('input');
        input.value = value;
        document.body.appendChild(input);
        input.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(input);
    });
</script>
@endsection
