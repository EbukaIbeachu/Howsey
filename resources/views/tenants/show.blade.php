@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        .mobile-status-active,
        .mobile-status-active > td,
        .mobile-status-active > th {
            background-color: #d1e7dd !important;
            --bs-table-accent-bg: #d1e7dd !important;
            --bs-table-bg: #d1e7dd !important;
        }
        .mobile-status-inactive,
        .mobile-status-inactive > td,
        .mobile-status-inactive > th {
            background-color: #e2e3e5 !important;
            --bs-table-accent-bg: #e2e3e5 !important;
            --bs-table-bg: #e2e3e5 !important;
        }
        .mobile-status-completed,
        .mobile-status-completed > td,
        .mobile-status-completed > th {
            background-color: #d1e7dd !important;
            --bs-table-accent-bg: #d1e7dd !important;
            --bs-table-bg: #d1e7dd !important;
        }
        .mobile-status-pending,
        .mobile-status-pending > td,
        .mobile-status-pending > th {
            background-color: #fff3cd !important;
            --bs-table-accent-bg: #fff3cd !important;
            --bs-table-bg: #fff3cd !important;
        }
        .mobile-status-failed,
        .mobile-status-failed > td,
        .mobile-status-failed > th {
            background-color: #f8d7da !important;
            --bs-table-accent-bg: #f8d7da !important;
            --bs-table-bg: #f8d7da !important;
        }
        .lease-table th:nth-child(2),
        .lease-table td:nth-child(2),
        .lease-table th:nth-child(4),
        .lease-table td:nth-child(4) {
            display: none;
        }
        .lease-table th:nth-child(5),
        .lease-table td:nth-child(5) {
            display: none;
        }
        .payments-table th:nth-child(4),
        .payments-table td:nth-child(4) {
            display: none;
        }
    }
</style>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tenant Profile</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @if(auth()->user()->isManager())
            @include('partials.manager-edit-request-button', [
                'recordType' => 'tenant',
                'recordId' => $tenant->id,
                'recordLabel' => $tenant->full_name,
                'buttonClass' => 'btn btn-sm btn-warning',
                'buttonText' => 'Request Edit',
                'iconClass' => 'bi bi-pencil-square',
            ])
        @else
            <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil"></i> Edit Tenant
            </a>
        @endif
        @if($tenant->active && auth()->user()->isAdmin())
            <form action="{{ route('tenants.evict', $tenant) }}" method="POST" class="ms-2" onsubmit="return confirm('Evict this tenant now? This will export the tenant details, close the active lease, and free the unit for a new tenant.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Evict Tenant
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('eviction_export_url'))
    <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <strong>Eviction export created.</strong>
            <span class="text-muted">Download the archived tenant record file for your documentation.</span>
        </div>
        <a href="{{ session('eviction_export_url') }}" class="btn btn-sm btn-outline-dark" download="{{ session('eviction_export_name', 'tenant-eviction-export.csv') }}">
            <i class="bi bi-download me-1"></i> Download Export
        </a>
    </div>
@endif

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                @if($tenant->profile_image)
                    <img src="{{ asset('storage/' . $tenant->profile_image) }}" alt="{{ $tenant->full_name }}" class="rounded-circle object-fit-cover mx-auto mb-3" style="width: 120px; height: 120px;">
                @else
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-person fs-1"></i>
                    </div>
                @endif
                <h4 class="card-title">{{ $tenant->full_name }}</h4>
                @if(auth()->user()->isAdmin() && $tenant->creator)
                    <div class="mb-2 text-muted small">
                        <i class="bi bi-person-check me-1"></i> Registered by: {{ $tenant->creator->name }}
                    </div>
                @endif
                <p class="text-muted mb-2">
                    @if($tenant->active)
                        <span class="badge bg-success">Active Tenant</span>
                    @else
                        <span class="badge bg-secondary">Former Tenant</span>
                    @endif
                </p>
                
                <hr>
                
                <div class="text-start">
                    <p class="mb-1"><i class="bi bi-envelope me-2 text-muted"></i> {{ $tenant->email ?? 'N/A' }}</p>
                    <p class="mb-1"><i class="bi bi-telephone me-2 text-muted"></i> {{ $tenant->phone ?? 'N/A' }}</p>
                    <p class="mb-1"><i class="bi bi-calendar-event me-2 text-muted"></i> Since {{ $tenant->move_in_date }}</p>
                    @if($tenant->move_out_date)
                        <p class="mb-1"><i class="bi bi-calendar-x me-2 text-muted"></i> Left {{ $tenant->move_out_date }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">
                Current Residence
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">{{ $tenant->unit->building->name }}</h5>
                        <p class="mb-0 text-muted">
                            Unit {{ $tenant->unit->unit_number }}<br>
                            {{ $tenant->unit->building->address_line1 }}, {{ $tenant->unit->building->city }}
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="{{ route('units.show', $tenant->unit) }}" class="btn btn-outline-primary btn-sm">View Unit</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-1">Total Paid</div>
                        <div class="fs-4 fw-bold text-success">₦{{ number_format($totalPaid, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-1">Outstanding Balance</div>
                        @if($outstandingBalance > 0)
                            <div class="fs-4 fw-bold text-danger">₦{{ number_format($outstandingBalance, 2) }}</div>
                        @elseif($outstandingBalance < 0)
                            <div class="fs-4 fw-bold text-success">₦{{ number_format(abs($outstandingBalance), 2) }} Credit</div>
                        @else
                            <div class="fs-4 fw-bold text-success">₦0.00</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php
            $canManageReminders = auth()->user()->isAdmin()
                || (auth()->user()->isManager() && optional(optional($tenant->unit)->building)->manager_id === auth()->id());
            $startLabel = $tenant->rent_reminders_start_milestone
                ? collect($reminderMilestones)->firstWhere('key', $tenant->rent_reminders_start_milestone)['label'] ?? $tenant->rent_reminders_start_milestone
                : null;
        @endphp
        @if($canManageReminders)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-bell me-1"></i> Rent Reminders</span>
                @if($tenant->rent_reminders_exempt)
                    <span class="badge bg-warning text-dark"><i class="bi bi-pause-circle me-1"></i> Paused</span>
                @else
                    <span class="badge bg-success"><i class="bi bi-play-circle me-1"></i> Active</span>
                @endif
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Automated rent renewal and overdue reminders are sent to this tenant, their building manager, and company admins.
                    @if($startLabel)
                        <br><strong>Current sequence floor:</strong> {{ $startLabel }}
                    @endif
                </p>

                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="rent_reminders_start_milestone" class="form-label fw-semibold small">Resume reminders from level</label>
                        <select class="form-select form-select-sm" id="rent_reminders_start_milestone" name="rent_reminders_start_milestone" form="reminder-settings-form">
                            <option value="">Full sequence (from the earliest applicable reminder)</option>
                            @foreach($reminderMilestones as $m)
                                <option value="{{ $m['key'] }}" @selected($tenant->rent_reminders_start_milestone === $m['key'])>{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Reminders before this level will be skipped for this tenant.</div>
                    </div>
                    <div class="col-md-4">
                        <form id="reminder-settings-form" action="{{ route('tenants.reminder-settings', $tenant) }}" method="POST">
                            @csrf
                            @if($tenant->rent_reminders_exempt)
                                <input type="hidden" name="rent_reminders_exempt" value="0">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-play-circle me-1"></i> Resume Reminders
                                </button>
                            @else
                                <input type="hidden" name="rent_reminders_exempt" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                                    <i class="bi bi-pause-circle me-1"></i> Pause Reminders
                                </button>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Pausing stops <strong>all</strong> automated reminders for this tenant (including manager and admin copies).
                    Resuming applies the selected level; reminders earlier in the sequence will not be sent retroactively.
                </div>
            </div>
        </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Lease Agreements</span>
                <a href="{{ route('rents.create', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-lg"></i> New Lease
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 lease-table">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Rent Amount</th>
                                <th>Rent Duration</th>
                                <th>Paid</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->rents->sortByDesc('start_date') as $rent)
                            <tr class="{{ $rent->status === 'ACTIVE' ? 'mobile-status-active' : 'mobile-status-inactive' }}">
                                <td>
                                    <div>{{ $rent->lease_period_label }}</div>
                                    <div class="small text-muted">{{ $rent->due_schedule_label }}</div>
                                </td>
                                <td>
                                    <div>₦{{ number_format($rent->annual_amount, 2) }}</div>
                                    <div class="small text-muted">{{ $rent->rent_interval_suffix }}</div>
                                </td>
                                <td>{{ $rent->rent_duration_label }}</td>
                                <td class="text-success">₦{{ number_format($rent->total_paid, 2) }}</td>
                                <td class="{{ $rent->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    @if($rent->balance < 0)
                                        ₦{{ number_format(abs($rent->balance), 2) }} Credit
                                    @else
                                        ₦{{ number_format($rent->balance, 2) }}
                                    @endif
                                </td>
                                <td>
                                    @if($rent->status === 'ACTIVE')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst(strtolower($rent->status)) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('rents.show', $rent) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No lease agreements found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold">Payment History</span>
                <a href="{{ route('payments.create', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Record Payment
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 payments-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->payments->sortByDesc('payment_date')->take(5) as $payment)
                            <tr class="
                                @if($payment->status === 'COMPLETED') mobile-status-completed
                                @elseif($payment->status === 'PENDING') mobile-status-pending
                                @else mobile-status-failed
                                @endif
                            ">
                                <td>{{ $payment->payment_date }}</td>
                                <td>₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>
                                    @if($payment->status === 'COMPLETED')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'PENDING')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    No payments recorded.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tenant->payments->count() > 5)
                <div class="card-footer bg-white text-center">
                    <a href="{{ route('payments.index', ['tenant_id' => $tenant->id]) }}" class="text-decoration-none">View All Payments</a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
