@extends('layouts.app')

@section('content')
<x-ui.page-header
    title="Overdue Rents"
    subtitle="Outstanding rent balances shown in the same format as the dashboard table."
>
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('dashboard')">Back to Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card title="Overdue Rents" flush>
    <x-ui.table class="mb-0 overdue-rents-table overdue-rents-detail-table" caption="Overdue rents" :caption-sr="true">
        <thead>
            <tr>
                <th scope="col">Tenant</th>
                <th scope="col">Unit</th>
                <th scope="col">Due Amount</th>
                <th scope="col" class="text-center overdue-pay-header" style="width: 1%;" title="Pay">
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
                        <x-ui.button
                            variant="primary"
                            size="sm"
                            class="overdue-pay-btn"
                            :href="route('payments.create', ['rent_id' => $rent->id])"
                            title="Pay overdue rent"
                            aria-label="Pay overdue rent"
                        >Pay</x-ui.button>
                    </td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="4" icon="bi bi-check2-circle" title="No overdue rents." />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>
@endsection
