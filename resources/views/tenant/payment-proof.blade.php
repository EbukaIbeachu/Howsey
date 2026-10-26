@extends('layouts.app')

@section('content')
<x-ui.page-header title="Show Proof Of Payment">
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('payments.pay', $rent)">Back To Payment Details</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <x-ui.alert tone="danger" title="Please check the form below.">
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('payments.claim', $rent) }}" enctype="multipart/form-data" data-loader-ignore>
            @csrf
            <x-ui.card title="Payment You Made">
                <p class="text-muted small">
                    This does not record the payment by itself — your landlord or the building manager
                    will confirm it and then it will appear in your payment history.
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="amount"
                            type="number"
                            label="Amount Paid"
                            step="0.01"
                            min="0.01"
                            :value="$rent->balance > 0 ? $rent->balance : $rent->annual_amount"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="payment_date"
                            type="date"
                            label="Date Paid"
                            :value="now()->format('Y-m-d')"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.select name="payment_method" label="Payment Method">
                            <option value="BANK_TRANSFER" @selected(old('payment_method') === 'BANK_TRANSFER')>Bank Transfer</option>
                            <option value="CASH" @selected(old('payment_method') === 'CASH')>Cash</option>
                            <option value="CHEQUE" @selected(old('payment_method') === 'CHEQUE')>Cheque</option>
                            <option value="POS" @selected(old('payment_method') === 'POS')>POS / Card</option>
                            <option value="MOBILE_MONEY" @selected(old('payment_method') === 'MOBILE_MONEY')>Mobile Money</option>
                        </x-ui.form.select>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form.input
                            name="reference"
                            label="Transfer Reference (optional)"
                            maxlength="100"
                            placeholder="e.g. transaction reference"
                        />
                    </div>
                    <div class="col-12">
                        <x-ui.form.file
                            name="proof"
                            label="Proof Of Payment (optional)"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            hint="Upload a screenshot or PDF of the transfer receipt. Max 4MB."
                        />
                    </div>
                    <div class="col-12">
                        <x-ui.form.textarea
                            name="note"
                            label="Note (optional)"
                            :rows="3"
                            maxlength="1000"
                            placeholder="Anything the reviewer should know."
                        />
                    </div>
                </div>

                <x-slot:footer>
                    <div class="d-flex justify-content-end gap-2 w-100">
                        <x-ui.button variant="outline-secondary" :href="route('payments.pay', $rent)">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary" icon="bi bi-send">Submit For Confirmation</x-ui.button>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </form>
    </div>

    <div class="col-lg-5">
        <x-ui.card title="Payment Summary" class="mb-4">
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
        </x-ui.card>

        <x-ui.card title="Accounts You Paid Into">
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
        </x-ui.card>
    </div>
</div>
@endsection
