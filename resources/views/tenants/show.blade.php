@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
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

<x-ui.page-header title="Tenant Profile">
    <x-slot:actions>
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
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil" :href="route('tenants.edit', $tenant)">Edit Tenant</x-ui.button>
        @endif
        @if($tenant->active && auth()->user()->isAdmin())
            <form action="{{ route('tenants.evict', $tenant) }}" method="POST" onsubmit="return confirm('Evict this tenant now? This will export the tenant details, close the active lease, and free the unit for a new tenant.');">
                @csrf
                <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-box-arrow-right">Evict Tenant</x-ui.button>
            </form>
        @endif
    </x-slot:actions>
</x-ui.page-header>

@if(session('eviction_export_url'))
    <x-ui.alert tone="warning" title="Eviction export created." class="mb-3">
        Download the archived tenant record file for your documentation.
        <div class="mt-2">
            <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-download" :href="session('eviction_export_url')" download="{{ session('eviction_export_name', 'tenant-eviction-export.csv') }}">Download Export</x-ui.button>
        </div>
    </x-ui.alert>
@endif

<div class="row">
    <div class="col-md-4 mb-4">
        <x-ui.card class="h-100">
            <div class="text-center">
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
                        <x-ui.status-badge tone="success" label="Active Tenant" />
                    @else
                        <x-ui.status-badge tone="neutral" label="Former Tenant" />
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
        </x-ui.card>
    </div>

    <div class="col-md-8 mb-4">
        <x-ui.card title="Current Residence" class="mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-1">{{ $tenant->unit->building->name }}</h5>
                    <p class="mb-0 text-muted">
                        Unit {{ $tenant->unit->unit_number }}<br>
                        {{ $tenant->unit->building->address_line1 }}, {{ $tenant->unit->building->city }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <x-ui.button variant="outline-primary" size="sm" :href="route('units.show', $tenant->unit)">View Unit</x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <x-ui.stat-card
                    label="Total Paid"
                    tone="success"
                    :value="'₦'.number_format($totalPaid, 2)"
                />
            </div>
            <div class="col-md-6">
                @if($outstandingBalance > 0)
                    <x-ui.stat-card
                        label="Outstanding Balance"
                        tone="danger"
                        :value="'₦'.number_format($outstandingBalance, 2)"
                    />
                @elseif($outstandingBalance < 0)
                    <x-ui.stat-card
                        label="Outstanding Balance"
                        tone="success"
                        :value="'₦'.number_format(abs($outstandingBalance), 2).' Credit'"
                    />
                @else
                    <x-ui.stat-card
                        label="Outstanding Balance"
                        tone="success"
                        value="₦0.00"
                    />
                @endif
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
        <x-ui.card title="Rent Reminders" icon="bi bi-bell" class="mb-4">
            <x-slot:actions>
                @if($tenant->rent_reminders_exempt)
                    <x-ui.status-badge tone="warning" icon="bi bi-pause-circle" label="Paused" />
                @else
                    <x-ui.status-badge tone="success" icon="bi bi-play-circle" label="Active" />
                @endif
            </x-slot:actions>

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
                            <x-ui.button type="submit" variant="success" size="sm" icon="bi bi-play-circle" block>Resume Reminders</x-ui.button>
                        @else
                            <input type="hidden" name="rent_reminders_exempt" value="1">
                            <x-ui.button type="submit" variant="outline-warning" size="sm" icon="bi bi-pause-circle" block>Pause Reminders</x-ui.button>
                        @endif
                    </form>
                </div>
            </div>

            <x-ui.alert tone="info" class="mt-3 mb-0">
                Pausing stops <strong>all</strong> automated reminders for this tenant (including manager and admin copies).
                Resuming applies the selected level; reminders earlier in the sequence will not be sent retroactively.
            </x-ui.alert>
        </x-ui.card>
        @endif

        <x-ui.card title="Lease Agreements" flush class="mb-4">
            <x-slot:actions>
                <x-ui.button variant="success" size="sm" icon="bi bi-plus-lg" :href="route('rents.create', ['tenant_id' => $tenant->id])">New Lease</x-ui.button>
            </x-slot:actions>

            <x-ui.table class="mb-0 lease-table" caption="Lease agreements" :caption-sr="true">
                <thead>
                    <tr>
                        <th scope="col">Period</th>
                        <th scope="col">Rent Amount</th>
                        <th scope="col">Rent Duration</th>
                        <th scope="col">Paid</th>
                        <th scope="col">Outstanding</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenant->rents->sortByDesc('start_date') as $rent)
                    <tr class="{{ $rent->status === 'ACTIVE' ? 'ui-row--success' : 'ui-row--neutral' }}">
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
                                <x-ui.status-badge tone="success" label="Active" />
                            @else
                                <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($rent->status))" />
                            @endif
                        </td>
                        <td class="text-end">
                            <x-ui.icon-button label="View lease" icon="bi bi-eye" size="sm" :href="route('rents.show', $rent)" />
                        </td>
                    </tr>
                    @empty
                    <x-ui.table.empty-state
                        :colspan="7"
                        icon="bi bi-file-earmark-text"
                        title="No lease agreements found"
                    />
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Payment History" flush>
            <x-slot:actions>
                <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('payments.create', ['tenant_id' => $tenant->id])">Record Payment</x-ui.button>
            </x-slot:actions>

            <x-ui.table class="mb-0 payments-table" caption="Payment history" :caption-sr="true">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Amount</th>
                        <th scope="col">Method</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenant->payments->sortByDesc('payment_date')->take(5) as $payment)
                    <tr class="{{ $payment->status === 'COMPLETED' ? 'ui-row--success' : ($payment->status === 'PENDING' ? 'ui-row--warning' : 'ui-row--danger') }}">
                        <td>{{ $payment->payment_date }}</td>
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                        <td>
                            @if($payment->status === 'COMPLETED')
                                <x-ui.status-badge tone="success" label="Completed" />
                            @elseif($payment->status === 'PENDING')
                                <x-ui.status-badge tone="warning" label="Pending" />
                            @else
                                <x-ui.status-badge tone="danger" label="Failed" />
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-ui.table.empty-state
                        :colspan="4"
                        icon="bi bi-cash-stack"
                        title="No payments recorded"
                    />
                    @endforelse
                </tbody>
            </x-ui.table>

            @if($tenant->payments->count() > 5)
                <x-slot:footer>
                    <div class="text-center">
                        <x-ui.button variant="ghost" :href="route('payments.index', ['tenant_id' => $tenant->id])">View All Payments</x-ui.button>
                    </div>
                </x-slot:footer>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
