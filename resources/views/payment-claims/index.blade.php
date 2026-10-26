@extends('layouts.app')

@section('content')
<x-ui.page-header title="Payment Claims">
    <x-slot:actions>
        <x-ui.status-badge tone="warning" :label="$pendingCount.' awaiting confirmation'" />
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
    <x-ui.alert tone="success">{{ session('success') }}</x-ui.alert>
@endif
@if(session('error'))
    <x-ui.alert tone="danger">{{ session('error') }}</x-ui.alert>
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

<x-ui.card flush>
    <x-ui.table caption="Payment claims" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col">Tenant</th>
                <th scope="col">Property</th>
                <th scope="col" class="text-end">Amount</th>
                <th scope="col">Date Paid</th>
                <th scope="col">Reference</th>
                <th scope="col">Proof</th>
                <th scope="col">Submitted</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($claims as $claim)
                <tr class="@if($claim->is_pending) ui-row--warning @elseif($claim->status === \App\Models\PaymentClaim::STATUS_CONFIRMED) ui-row--success @else ui-row--danger @endif">
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
                            <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-paperclip"
                                :href="route('payment-claims.proof', $claim)" target="_blank">View</x-ui.button>
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
                            <div class="ui-btn-group">
                                <x-ui.button variant="primary" size="sm" data-bs-toggle="modal" data-bs-target="#confirmClaim{{ $claim->id }}">Confirm</x-ui.button>
                                <x-ui.button variant="outline-danger" size="sm" data-bs-toggle="modal" data-bs-target="#rejectClaim{{ $claim->id }}">Reject</x-ui.button>
                            </div>
                        @else
                            @if($claim->status === \App\Models\PaymentClaim::STATUS_CONFIRMED)
                                <x-ui.status-badge tone="success" label="Confirmed" />
                            @else
                                <x-ui.status-badge tone="danger" label="Not Confirmed" />
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
                <x-ui.table.empty-state
                    :colspan="8"
                    icon="bi bi-inbox"
                    title="No payment claims in this view."
                />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($claims->hasPages())
        <x-slot:footer>{{ $claims->links() }}</x-slot:footer>
    @endif
</x-ui.card>

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

                            <x-ui.form.input name="amount" type="number" label="Amount" required
                                :id="'confirm-amount-'.$claim->id" step="0.01" min="0.01" :value="(float) $claim->amount" />
                            <x-ui.form.input name="payment_date" type="date" label="Payment Date" required
                                :id="'confirm-payment-date-'.$claim->id"
                                :value="$claim->payment_date ? $claim->payment_date->format('Y-m-d') : now()->format('Y-m-d')" />
                            <x-ui.form.select name="payment_method" label="Payment Method"
                                :id="'confirm-payment-method-'.$claim->id"
                                :value="$claim->payment_method ?: 'BANK_TRANSFER'"
                                :options="['BANK_TRANSFER' => 'Bank Transfer', 'CASH' => 'Cash', 'CHEQUE' => 'Cheque', 'POS' => 'POS / Card', 'MOBILE_MONEY' => 'Mobile Money']" />
                            <x-ui.form.textarea name="review_note" label="Reviewer Note (optional)"
                                :id="'confirm-review-note-'.$claim->id" rows="2" maxlength="1000" />
                        </div>
                        <div class="modal-footer">
                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="primary">Confirm &amp; Record Payment</x-ui.button>
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
                            <x-ui.form.textarea name="review_note" label="Reason" required
                                :id="'reject-review-note-'.$claim->id" rows="3" maxlength="1000" />
                        </div>
                        <div class="modal-footer">
                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="danger">Reject Claim</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
