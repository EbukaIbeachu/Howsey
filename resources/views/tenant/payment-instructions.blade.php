@extends('layouts.app')

@section('content')
<x-ui.page-header title="Pay Rent">
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('dashboard')">Back to Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
    <x-ui.alert tone="success">{{ session('success') }}</x-ui.alert>
@endif
@if(session('error'))
    <x-ui.alert tone="danger">{{ session('error') }}</x-ui.alert>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <x-ui.card title="Amount Due" class="h-100">
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
        </x-ui.card>
    </div>

    <div class="col-lg-7">
        <x-ui.card title="Where To Pay" class="h-100">
            <x-slot:actions>
                <x-ui.button variant="primary" size="sm" icon="bi bi-check2-circle" :href="route('payments.proof', $rent)">I have made payment</x-ui.button>
            </x-slot:actions>

            @forelse($accounts as $account)
                <div class="border rounded p-3 {{ $loop->last ? '' : 'mb-3' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $account->display_name }}</div>
                            <div class="text-muted small">{{ $account->bank_name }}@if($account->branch) &middot; {{ $account->branch }}@endif</div>
                        </div>
                        @if($account->is_primary)
                            <x-ui.status-badge tone="primary" label="Primary" />
                        @endif
                    </div>

                    <dl class="row mb-0 mt-3 small">
                        <dt class="col-sm-4 text-muted">Account Name</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $account->account_name }}</dd>

                        <dt class="col-sm-4 text-muted">Account Number</dt>
                        <dd class="col-sm-8">
                            <span class="fw-bold fs-6">{{ $account->account_number }}</span>
                            <button type="button"
                                    class="ui-btn ui-btn--ghost ui-btn--sm p-0 ms-2 align-baseline copy-account"
                                    data-copy="{{ $account->account_number }}">
                                <i class="bi bi-clipboard" aria-hidden="true"></i> Copy
                            </button>
                        </dd>
                    </dl>

                    @if($account->instructions)
                        <x-ui.alert tone="neutral" class="small mt-3 mb-0">{{ $account->instructions }}</x-ui.alert>
                    @endif
                </div>
            @empty
                <x-ui.alert tone="warning" title="No receiving account has been published yet" class="mb-0">
                    <div class="small">Please contact the office for payment details before making a transfer.</div>
                </x-ui.alert>
            @endforelse

            <x-ui.alert tone="neutral" class="small mt-3 mb-0">
                Once you have made the transfer, click <strong>I have made payment</strong> above, tell us the details
                and attach your proof. Your landlord and the building manager will be notified so they can confirm it.
            </x-ui.alert>
        </x-ui.card>
    </div>
</div>

<x-ui.card title="My Payment Notifications" class="mt-4" flush>
    <x-ui.table class="mb-0" :headers="['Date Paid', ['label' => 'Amount', 'class' => 'text-end'], 'Reference', 'Submitted', 'Status', 'Reviewer Note']">
        <tbody>
            @forelse($claims as $claim)
                <tr>
                    <td>{{ $claim->payment_date ? $claim->payment_date->format('M d, Y') : 'N/A' }}</td>
                    <td class="text-end fw-semibold">₦{{ number_format((float) $claim->amount, 2) }}</td>
                    <td>{{ $claim->reference ?: '—' }}</td>
                    <td class="text-muted small">{{ $claim->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        @if($claim->status === \App\Models\PaymentClaim::STATUS_CONFIRMED)
                            <x-ui.status-badge tone="success" label="Confirmed" />
                        @elseif($claim->status === \App\Models\PaymentClaim::STATUS_REJECTED)
                            <x-ui.status-badge tone="danger" label="Not Confirmed" />
                        @else
                            <x-ui.status-badge tone="warning" label="Awaiting Confirmation" />
                        @endif
                    </td>
                    <td class="small text-muted">{{ $claim->review_note ?: '—' }}</td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="6" title="You have not submitted any payment notification yet." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>

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
