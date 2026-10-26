@extends('layouts.app')

@section('content')
<x-ui.page-header title="Edit Payment">
    <x-slot:actions>
        <x-ui.button variant="info" size="sm" icon="bi bi-eye" :href="route('payments.show', $payment)">View Details</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Payment Details">
            <form action="{{ route('payments.update', $payment) }}" method="POST">
                @csrf
                @method('PUT')

                <x-ui.form.select name="rent_id" label="Lease Agreement" required>
                    @foreach($rents as $rent)
                        <option value="{{ $rent->id }}" {{ (old('rent_id', $payment->rent_id) == $rent->id) ? 'selected' : '' }}>
                            {{ $rent->tenant->full_name }} - Unit {{ $rent->unit->unit_number }} (₦{{ number_format($rent->annual_amount, 2) }}{{ $rent->rent_interval_suffix }}, {{ $rent->rent_duration_label }})
                        </option>
                    @endforeach
                </x-ui.form.select>

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

                    <div class="col-md-6">
                        <x-ui.form.input name="payment_date" type="date" label="Payment Date" required
                            :value="$payment->payment_date ? $payment->payment_date->format('Y-m-d') : ''" />
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.select name="payment_method" label="Payment Method" :value="$payment->payment_method" :options="[
                            'CASH' => 'Cash',
                            'BANK_TRANSFER' => 'Bank Transfer',
                            'CHECK' => 'Check',
                            'ONLINE' => 'Online',
                            'OTHER' => 'Other',
                        ]" />
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="status" label="Status" :value="$payment->status" :options="[
                            'COMPLETED' => 'Completed',
                            'PENDING' => 'Pending',
                            'FAILED' => 'Failed',
                        ]" />
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="submit" variant="primary">Update Payment</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
