@extends('layouts.app')

@section('content')
<x-ui.page-header id="dashboard-title" title="My Dashboard">
    <x-slot:actions>
        <x-ui.button variant="outline-info" size="sm" icon="bi bi-question-circle" onclick="startTour()">Help</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mb-4">
    <div class="d-flex flex-column flex-md-row align-items-center gap-3">
        @if($tenant?->profile_image)
            <img src="{{ asset('storage/' . $tenant->profile_image) }}" alt="{{ $tenant->full_name }}" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 120px; height: 120px;">
        @else
            <div class="border rounded-circle d-flex align-items-center justify-content-center bg-light text-primary flex-shrink-0" style="width: 120px; height: 120px;">
                <i class="bi bi-person fs-1"></i>
            </div>
        @endif
        <div class="text-center text-md-start">
            <div class="text-muted small text-uppercase">Tenant Dashboard</div>
            <h2 class="h3 mb-1">{{ $tenant?->full_name ?? auth()->user()->name }}</h2>
            <p class="text-muted mb-0">Your tenant profile and rent information appear here.</p>
        </div>
    </div>
</x-ui.card>

<div class="row">
    <!-- Current Lease Info -->
    <div class="col-md-6 mb-4" id="lease-card">
        <x-ui.card title="My Home" class="h-100">
            @if($rent)
                <h3 class="fs-4 fw-semibold mb-2">{{ $rent->unit->building->name }}</h3>
                <p class="text-muted mb-2">
                    {{ $rent->unit->building->address }}
                </p>
                <p class="text-muted mb-4">Unit {{ $rent->unit->unit_number }}</p>

                <div class="row g-3">
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">{{ $rent->rent_amount_label }}</label>
                        <div class="fs-5 fw-bold">₦{{ number_format($rent->annual_amount, 2) }}</div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Rent Duration</label>
                        <div class="fs-5">{{ $rent->rent_duration_label }}</div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Next Due Date</label>
                        <div class="fs-5">{{ $rent->next_due_date ? $rent->next_due_date->format('M d, Y') : 'N/A' }}</div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Due Schedule</label>
                        <div class="fs-5">{{ $rent->due_schedule_label }}</div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Lease Status</label>
                        <div><x-ui.status-badge tone="success" label="Active" /></div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Current Outstanding</label>
                        <div class="fs-5 {{ $rent->balance > 0 ? 'text-danger' : 'text-success' }}">
                            @if($rent->balance < 0)
                                ₦{{ number_format(abs($rent->balance), 2) }} Credit
                            @else
                                ₦{{ number_format($rent->balance, 2) }}
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted text-uppercase">Total Paid</label>
                        <div class="fs-5 fw-bold text-success">₦{{ number_format($totalPaid, 2) }}</div>
                    </div>
                </div>

                <hr>

                <div>
                    <label class="small text-muted text-uppercase">Assigned Manager</label>
                    @if($buildingManager)
                        <div class="fw-bold">{{ $buildingManager->name }}</div>
                        <div class="text-muted small">{{ $buildingManager->email ?? 'No email available' }}</div>
                        <div class="text-muted small">{{ $buildingManager->phone ?? 'No phone number available' }}</div>
                    @else
                        <div class="text-muted">No manager is currently assigned to this building.</div>
                    @endif
                </div>
            @else
                <p>No active rental agreement found.</p>
            @endif

            @if($rent)
                <x-slot:footer>
                    <div class="d-grid gap-2 w-100">
                        @if($rent->balance > 0)
                            <x-ui.button variant="primary" :href="route('payments.pay', $rent)">Pay Now</x-ui.button>
                        @endif
                        <x-ui.button variant="outline-secondary" icon="bi bi-file-text" :href="route('rents.agreement', $rent)">View Rental Agreement</x-ui.button>
                    </div>
                </x-slot:footer>
            @endif
        </x-ui.card>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6 mb-4" id="payment-history">
        <x-ui.card title="Payment History" class="h-100" flush>
            <x-ui.table class="mb-0" :headers="['Date', 'Amount', 'Method', 'Receipt']">
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                        <td class="text-success fw-bold">₦{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                        <td>
                            @if(strtoupper((string) $payment->status) === 'COMPLETED')
                                <x-ui.button variant="outline-success" size="sm" icon="bi bi-download" :href="route('payments.receipt', $payment)">Receipt</x-ui.button>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-ui.table.empty-state :colspan="4" icon="bi bi-cash-stack" title="No payments recorded." />
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4" id="announcements">
        <x-ui.card title="Announcements" icon="bi bi-megaphone">
            <ul class="list-group">
                @forelse($announcements as $ann)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $ann->title }}</strong>
                                <div class="text-muted small">{{ $ann->created_at->format('M d, Y H:i') }}</div>
                                <div>{{ $ann->content }}</div>
                            </div>
                            <form action="{{ route('buildings.announcements.dismiss', ['building' => $rent ? $rent->unit->building_id : optional(optional($tenant->unit))->building_id, 'announcement' => $ann->id]) }}" method="POST" onsubmit="return confirm('Remove this announcement from your page?');">
                                @csrf
                                <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-x-circle">Remove</x-ui.button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No announcements yet.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
</div>
@endsection
