@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Payment</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-info text-white">
            <i class="bi bi-eye"></i> View Details
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('payments.update', $payment) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="rent_id" class="form-label">Lease Agreement <span class="text-danger">*</span></label>
                        <select class="form-select @error('rent_id') is-invalid @enderror" id="rent_id" name="rent_id" required>
                            @foreach($rents as $rent)
                                <option value="{{ $rent->id }}" {{ (old('rent_id', $payment->rent_id) == $rent->id) ? 'selected' : '' }}>
                                    {{ $rent->tenant->full_name }} - Unit {{ $rent->unit->unit_number }} (₦{{ number_format($rent->annual_amount, 2) }}{{ $rent->rent_interval_suffix }}, {{ $rent->rent_duration_label }})
                                </option>
                            @endforeach
                        </select>
                        @error('rent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount Paid (₦) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₦</span>
                                <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $payment->amount) }}" required min="0.01">
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date" value="{{ old('payment_date', $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '') }}" required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                <option value="CASH" {{ old('payment_method', $payment->payment_method) == 'CASH' ? 'selected' : '' }}>Cash</option>
                                <option value="BANK_TRANSFER" {{ old('payment_method', $payment->payment_method) == 'BANK_TRANSFER' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="CHECK" {{ old('payment_method', $payment->payment_method) == 'CHECK' ? 'selected' : '' }}>Check</option>
                                <option value="ONLINE" {{ old('payment_method', $payment->payment_method) == 'ONLINE' ? 'selected' : '' }}>Online</option>
                                <option value="OTHER" {{ old('payment_method', $payment->payment_method) == 'OTHER' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="COMPLETED" {{ old('status', $payment->status) == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                                <option value="PENDING" {{ old('status', $payment->status) == 'PENDING' ? 'selected' : '' }}>Pending</option>
                                <option value="FAILED" {{ old('status', $payment->status) == 'FAILED' ? 'selected' : '' }}>Failed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
