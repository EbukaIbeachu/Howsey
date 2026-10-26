@extends('layouts.app')

@section('content')
<x-ui.page-header
    title="Recent Payments"
    subtitle="Latest completed payments shown in the same format as the dashboard table."
>
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('dashboard')">Back to Dashboard</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card title="Recent Payments" flush>
    <x-ui.table class="mb-0" :headers="['Date', 'Tenant', 'Amount', 'Unit']">
        <tbody>
            @forelse($recentPayments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                    <td>{{ $payment->rent->tenant->full_name }}</td>
                    <td class="text-success">₦{{ number_format($payment->amount, 2) }}</td>
                    <td>
                        @if($payment->rent->unit)
                            {{ $payment->rent->unit->unit_number }}
                        @else
                            <span class="text-muted fst-italic">Unit Removed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="4" icon="bi bi-cash-stack" title="No recent payments found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($recentPayments, 'links'))
        <x-slot:footer>{{ $recentPayments->links() }}</x-slot:footer>
    @endif
</x-ui.card>
@endsection
