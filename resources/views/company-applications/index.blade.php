@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Company Applications</h1>
        <p class="text-muted mb-0">Review incoming company requests, payment references, and Paystack verification before activating a workspace.</p>
    </div>
    <a href="{{ route('companies.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-buildings me-1"></i> Live Companies
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Company</th>
                    <th>Reference</th>
                    <th>Payment</th>
                    <th>Application</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $application->name }}</div>
                            <div class="small text-muted">{{ $application->admin_name }} · {{ $application->admin_email }}</div>
                        </td>
                        <td>
                            <code>{{ $application->payment_reference }}</code>
                        </td>
                        <td>
                            <span class="badge {{ $application->paystack_transaction_status === 'success' ? 'bg-success' : ($application->paystack_transaction_status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">
                                {{ ucfirst($application->paystack_transaction_status) }}
                            </span>
                            <div class="small text-muted mt-1">NGN {{ number_format((float) $application->payment_amount, 2) }}</div>
                        </td>
                        <td>
                            <span class="badge text-bg-light">{{ ucfirst(str_replace('_', ' ', $application->application_status)) }}</span>
                            @if($application->company)
                                <div class="small text-muted mt-1">Created: {{ $application->company->name }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('company-applications.show', $application) }}" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No company applications yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $applications->links() }}
</div>
@endsection
