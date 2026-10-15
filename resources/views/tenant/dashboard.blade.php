@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2" id="dashboard-title">My Dashboard</h1>
    <button class="btn btn-outline-info btn-sm" onclick="startTour()">
        <i class="bi bi-question-circle"></i> Help
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
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
</div>

<div class="row">
    <!-- Current Lease Info -->
    <div class="col-md-6 mb-4" id="lease-card">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">My Home</h5>
            </div>
            <div class="card-body">
                @if($rent)
                    <h3 class="card-title">{{ $rent->unit->building->name }}</h3>
                    <p class="card-text text-muted mb-2">
                        {{ $rent->unit->building->address }}
                    </p>
                    <p class="card-text text-muted mb-4">Unit {{ $rent->unit->unit_number }}</p>

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
                            <div><span class="badge bg-success">Active</span></div>
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
            </div>
            @if($rent)
            <div class="card-footer d-grid gap-2">
                @if($rent->balance > 0)
                    <a href="{{ route('payments.create', ['rent_id' => $rent->id]) }}" class="btn btn-primary">Pay Now</a>
                @endif
                <a href="{{ route('rents.agreement', $rent) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-text"></i> View Rental Agreement
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6 mb-4" id="payment-history">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Method</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="text-success fw-bold">₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->due_date ? $payment->due_date->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                                <td>
                                    @if(strtoupper((string) $payment->status) === 'COMPLETED')
                                        <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-download me-1"></i>Receipt
                                        </a>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3">No payments recorded.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4" id="announcements">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-megaphone me-1"></i> Announcements</h5>
            </div>
            <div class="card-body">
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
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No announcements yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
