@extends('layouts.app')

@section('content')
@php
    $headers = [
        'Company',
        'Reference',
        'Payment',
        'Application',
        ['label' => 'Action', 'class' => 'text-end'],
    ];
@endphp

<x-ui.page-header
    title="Company Applications"
    subtitle="Review incoming company requests, payment references, and Paystack verification before activating a workspace.">
    <x-slot:actions>
        <x-ui.button variant="outline-primary" icon="bi bi-buildings" :href="route('companies.index')">Live Companies</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card flush>
    <x-ui.table :headers="$headers" caption="Company applications" :caption-sr="true">
        <tbody>
            @forelse($applications as $application)
                @php
                    $paymentTone = match ($application->paystack_transaction_status) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'neutral',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $application->name }}</div>
                        <div class="small text-muted">{{ $application->admin_name }} · {{ $application->admin_email }}</div>
                    </td>
                    <td>
                        <code>{{ $application->payment_reference }}</code>
                    </td>
                    <td>
                        <x-ui.status-badge :tone="$paymentTone" :label="ucfirst($application->paystack_transaction_status)" />
                        <div class="small text-muted mt-1">NGN {{ number_format((float) $application->payment_amount, 2) }}</div>
                    </td>
                    <td>
                        <x-ui.status-badge tone="neutral" :label="ucfirst(str_replace('_', ' ', $application->application_status))" />
                        @if($application->company)
                            <div class="small text-muted mt-1">Created: {{ $application->company->name }}</div>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-ui.button variant="primary" size="sm" :href="route('company-applications.show', $application)">Review</x-ui.button>
                    </td>
                </tr>
            @empty
                <x-ui.table.empty-state :colspan="5" icon="bi bi-buildings" title="No company applications yet." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($applications->hasPages())
        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    @endif
</x-ui.card>
@endsection
