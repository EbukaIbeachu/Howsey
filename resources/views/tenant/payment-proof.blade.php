@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Show Proof Of Payment</h1>
    <a href="{{ route('payments.pay', $rent) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back To Payment Details
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Please check the form below.</div>
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">Payment You Made</h5>
            </div>
            <form method="POST" action="{{ route('payments.claim', $rent) }}" enctype="multipart/form-data" data-loader-ignore>
                @csrf
                <div class="card-body">
                    <p class="text-muted small">
                        This does not record the payment by itself — your landlord or the building manager
                        will confirm it and then it will appear in your payment history.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                                   value="{{ old('amount', $rent->balance > 0 ? $rent->balance : $rent->annual_amount) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Paid <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="BANK_TRANSFER" {{ old('payment_method') === 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="CASH" {{ old('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                                <option value="CHEQUE" {{ old('payment_method') === 'CHEQUE' ? 'selected' : '' }}>Cheque</option>
                                <option value="POS" {{ old('payment_method') === 'POS' ? 'selected' : '' }}>POS / Card</option>
                                <option value="MOBILE_MONEY" {{ old('payment_method') === 'MOBILE_MONEY' ? 'selected' : '' }}>Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Transfer Reference <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="reference" class="form-control" maxlength="100" value="{{ old('reference') }}" placeholder="e.g. transaction reference">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Proof Of Payment <span class="text-muted small">(optional)</span></label>
                            <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                            <div class="form-text">Upload a screenshot or PDF of the transfer receipt. Max 4MB.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note <span class="text-muted small">(optional)</span></label>
                            <textarea name="note" class="form-control" rows="3" maxlength="1000" placeholder="Anything the reviewer should know.">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('payments.pay', $rent) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Submit For Confirmation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment Summary</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Tenant</dt>
                    <dd class="col-6 text-end">{{ $rent->tenant->full_name ?? auth()->user()->name }}</dd>

                    <dt class="col-6 text-muted">Building</dt>
                    <dd class="col-6 text-end">{{ $rent->unit->building->name ?? 'N/A' }}</dd>

                    <dt class="col-6 text-muted">Unit</dt>
                    <dd class="col-6 text-end">{{ $rent->unit->unit_number ?? 'N/A' }}</dd>

                    <dt class="col-6 text-muted">Outstanding Balance</dt>
                    <dd class="col-6 text-end fw-bold">₦{{ number_format($rent->balance, 2) }}</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Accounts You Paid Into</h5>
            </div>
            <div class="card-body">
                @forelse($accounts as $account)
                    <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                        <div class="fw-semibold">{{ $account->display_name }}</div>
                        <div class="text-muted small">{{ $account->bank_name }}@if($account->branch) &middot; {{ $account->branch }}@endif</div>
                        <div class="mt-2 small">
                            <div>{{ $account->account_name }}</div>
                            <div class="fw-bold">{{ $account->account_number }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small mb-0">No receiving account has been published yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
