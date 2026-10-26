@extends('layouts.app')

@section('content')
<x-ui.page-header title="Record Payment" />

<div class="row justify-content-center">
    <div class="col-md-8">
        <x-ui.card title="Payment Details">
            <form action="{{ route('payments.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="rent_id" class="form-label">Lease Agreement <span class="text-danger">*</span></label>
                    <input type="text" id="lease_search" class="form-control mb-2" placeholder="Type to filter leases...">
                    <select class="form-select @error('rent_id') is-invalid @enderror" id="rent_id" name="rent_id" required>
                        <option value="">Select Lease</option>
                        @foreach($rents as $rent)
                            <option value="{{ $rent->id }}"
                                    data-balance="{{ $rent->balance }}"
                                    {{ (old('rent_id') == $rent->id || (isset($selectedRent) && $selectedRent && $selectedRent->id == $rent->id)) ? 'selected' : '' }}>
                                {{ $rent->tenant->full_name }} - Unit {{ $rent->unit ? $rent->unit->unit_number : 'Removed' }} (₦{{ number_format($rent->annual_amount, 2) }}{{ $rent->rent_interval_suffix }}, {{ $rent->rent_duration_label }})
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
                            <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required min="0.01">
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="outstanding_display" class="form-label">Outstanding Due (₦)</label>
                        <input type="text" class="form-control bg-light" id="outstanding_display" readonly value="0.00">
                        <div class="form-text text-muted" id="remaining_hint"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <x-ui.form.input name="payment_date" type="date" label="Payment Date" required :value="date('Y-m-d')" />
                    </div>

                    <div class="col-md-6">
                        <x-ui.form.select name="payment_method" label="Payment Method" :options="[
                            'CASH' => 'Cash',
                            'BANK_TRANSFER' => 'Bank Transfer',
                            'CHECK' => 'Check',
                            'ONLINE' => 'Online',
                            'OTHER' => 'Other',
                        ]" />
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <x-ui.button type="reset" variant="ghost" class="me-md-2">Reset</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Record Payment</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rentSelect = document.getElementById('rent_id');
        const outstandingInput = document.getElementById('outstanding_display');
        const remainingHint = document.getElementById('remaining_hint');
        const amountInput = document.getElementById('amount');

        function updateOutstanding() {
            const selectedOption = rentSelect.options[rentSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                outstandingInput.value = "0.00";
                remainingHint.textContent = "";
                return;
            }

            const balance = parseFloat(selectedOption.getAttribute('data-balance'));
            
            if (!isNaN(balance)) {
                if (balance > 0) {
                    outstandingInput.value = balance.toFixed(2);
                    outstandingInput.style.fontWeight = "bold";
                    outstandingInput.style.color = "#dc3545"; // Bootstrap danger color
                } else if (balance < 0) {
                    outstandingInput.value = Math.abs(balance).toFixed(2) + " (Credit)";
                    outstandingInput.style.fontWeight = "bold";
                    outstandingInput.style.color = "#198754"; // Bootstrap success color
                } else {
                    outstandingInput.value = "0.00 (Caught Up)";
                    outstandingInput.style.fontWeight = "bold";
                    outstandingInput.style.color = "#198754";
                }
                updateRemaining();
            }
        }

        function updateRemaining() {
            const selectedOption = rentSelect.options[rentSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                remainingHint.textContent = "";
                return;
            }

            const balance = parseFloat(selectedOption.getAttribute('data-balance'));
            const payingAmount = parseFloat(amountInput.value) || 0;

            if (balance > 0) {
                const remaining = balance - payingAmount;
                if (remaining > 0) {
                    remainingHint.innerHTML = "Remaining after payment: <strong>₦" + remaining.toLocaleString('en-NG', {minimumFractionDigits: 2}) + "</strong>";
                    remainingHint.className = "form-text text-danger";
                } else if (remaining < 0) {
                    remainingHint.innerHTML = "New Credit Balance: <strong>₦" + Math.abs(remaining).toLocaleString('en-NG', {minimumFractionDigits: 2}) + "</strong>";
                    remainingHint.className = "form-text text-success";
                } else {
                    remainingHint.innerHTML = "<strong>Fully Paid!</strong>";
                    remainingHint.className = "form-text text-success";
                }
            } else {
                remainingHint.textContent = "";
            }
        }

        rentSelect.addEventListener('change', updateOutstanding);
        amountInput.addEventListener('input', updateRemaining);
        
        if (rentSelect.value) {
            updateOutstanding();
        }

        // Search Filter Logic
        const leaseSearch = document.getElementById('lease_search');
        const leaseOptions = Array.from(rentSelect.options);

        leaseSearch.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            
            leaseOptions.forEach(option => {
                if (option.value === "") return;
                
                const text = option.text.toLowerCase();
                if (text.includes(searchText)) {
                    option.hidden = false;
                    option.disabled = false;
                    option.style.display = "";
                } else {
                    option.hidden = true;
                    option.disabled = true;
                    option.style.display = "none";
                }
            });
            
            // Check if selected value is now hidden
            const selectedOption = rentSelect.options[rentSelect.selectedIndex];
            if (selectedOption && selectedOption.hidden) {
                rentSelect.value = "";
                updateOutstanding();
            }
        });
    });
</script>
@endpush
