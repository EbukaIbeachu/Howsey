@extends('layouts.app')

@section('content')
<div class="container-fluid pt-3 pb-4" id="reportRoot">
    @php
        $statementYears = $statementYears ?? [(int) now()->year];
        $selectedStatementYear = $selectedStatementYear ?? (int) request('year', $statementYears[0] ?? now()->year);
        $selectedCompanyId = $selectedCompanyId ?? request('company_id');
        $selectedBuildingId = $selectedBuildingId ?? request('building_id');

        // The controller returns raw Bootstrap badge classes (see
        // AccountingController::buildUnifiedRecords). They are mapped to the
        // design-system tones here so the controller stays untouched (§5.4).
        $badgeToneMap = [
            'bg-success' => 'success',
            'bg-danger' => 'danger',
            'bg-warning' => 'warning',
            'bg-warning text-dark' => 'warning',
            'bg-info' => 'info',
            'bg-info text-dark' => 'info',
            'bg-primary' => 'primary',
            'bg-secondary' => 'neutral',
        ];
    @endphp

    <x-ui.page-header
        title="Accounting Workspace"
        :subtitle="auth()->user()->isAccountant() ? auth()->user()->name : null"
    >
        <x-slot:actions>
            @if(auth()->user()->isAccountant())
                <button
                    type="button"
                    class="btn btn-outline-info btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0"
                    style="width: 1.65rem; height: 1.65rem;"
                    data-bs-toggle="collapse"
                    data-bs-target="#accountingNarrativeHelp"
                    aria-expanded="false"
                    aria-controls="accountingNarrativeHelp"
                    title="Why narrative notes matter"
                >
                    ?
                </button>
            @endif

            @if(isset($accessibleCompanies) && $accessibleCompanies->count() > 0 && !auth()->user()->isSuperAdmin())
                <select class="form-select form-select-sm" id="global-company-select" aria-label="Company">
                    @foreach($accessibleCompanies as $company)
                        <option
                            value="{{ $company->id }}"
                            data-url="{{ route('accounting.index', array_filter(['company_id' => $company->id, 'year' => $selectedStatementYear])) }}"
                            {{ (int) $selectedCompanyId === (int) $company->id ? 'selected' : '' }}
                        >
                            Company: {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <select class="form-select form-select-sm" id="global-statement-year" aria-label="Statement year">
                @foreach($statementYears as $statementYear)
                    <option value="{{ $statementYear }}" {{ $selectedStatementYear === $statementYear ? 'selected' : '' }}>
                        Statement Year: {{ $statementYear }}
                    </option>
                @endforeach
            </select>

            @if(auth()->user()->isAdmin())
                <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-journal-text" :href="route('accounting.audit-trail')">Audit Trail</x-ui.button>

                <x-ui.button
                    variant="success"
                    size="sm"
                    icon="bi bi-file-earmark-spreadsheet"
                    class="statement-year-link"
                    id="accounting-export-csv-all"
                    target="_blank"
                    :href="route('accounting.export', array_filter(['year' => $selectedStatementYear, 'company_id' => $selectedCompanyId]))"
                    :data-base-url="route('accounting.export', array_filter(['company_id' => $selectedCompanyId]))"
                    data-year-target="global-statement-year"
                >Export CSV</x-ui.button>

                <x-ui.button
                    variant="danger"
                    size="sm"
                    icon="bi bi-file-earmark-pdf"
                    class="statement-year-link"
                    id="accounting-export-pdf-all"
                    target="_blank"
                    :href="route('accounting.export-pdf', array_filter(['year' => $selectedStatementYear, 'company_id' => $selectedCompanyId]))"
                    :data-base-url="route('accounting.export-pdf', array_filter(['company_id' => $selectedCompanyId]))"
                    data-year-target="global-statement-year"
                >Export PDF</x-ui.button>
            @endif

            @if(auth()->user()->isAccountant() && !auth()->user()->isAdmin())
                <x-ui.button
                    variant="outline-primary"
                    size="sm"
                    icon="bi bi-eye"
                    class="statement-year-link"
                    id="accounting-view-report-all"
                    :href="route('accounting.report', array_filter(['year' => $selectedStatementYear, 'company_id' => $selectedCompanyId]))"
                    :data-base-url="route('accounting.report', array_filter(['company_id' => $selectedCompanyId]))"
                    data-year-target="global-statement-year"
                >View Report</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if(auth()->user()->isAccountant())
        <div class="collapse mb-4" id="accountingNarrativeHelp">
            <x-ui.alert tone="info" icon="bi bi-journal-text" title="Add a clear narrative note to each entry." class="mb-0">
                A short explanation of what happened, why it was posted, and any supporting context makes the record easier to follow later, and it helps approvals, reviews, and corrections happen faster.
            </x-ui.alert>
        </div>
    @endif

    <!-- Admin: Pending Edit Requests -->
    @if(isset($pendingEditRequests) && $pendingEditRequests->count() > 0)
    <x-ui.card
        icon="bi bi-exclamation-circle-fill"
        :title="'Pending Edit Requests ('.$pendingEditRequests->count().')'"
        class="mb-4"
    >
        <x-ui.table :headers="['User', 'Record', 'Reason', 'Date', 'Action']">
            <tbody>
                @foreach($pendingEditRequests as $req)
                <tr>
                    <td class="fw-bold">{{ $req->user->name }}</td>
                    <td>
                        <x-ui.status-badge tone="neutral" icon="bi bi-building" :label="$req->accountingEntry->building->name" />
                        <span class="small text-muted">#{{ $req->accountingEntry->id }}</span>
                    </td>
                    <td class="text-wrap" style="max-width: 300px;">{{ $req->reason }}</td>
                    <td class="small">{{ $req->created_at->diffForHumans() }}</td>
                    <td>
                        <div class="ui-btn-group">
                            <x-ui.button
                                variant="success"
                                size="sm"
                                data-bs-toggle="modal"
                                :data-bs-target="'#approveAccountingEditRequest'.$req->id"
                            >Approve</x-ui.button>
                            <form action="{{ route('accounting.handle-edit-request', $req->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <x-ui.button type="submit" variant="outline-danger" size="sm">Reject</x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
                @push('modals')
                <div class="modal fade" id="approveAccountingEditRequest{{ $req->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('accounting.handle-edit-request', $req->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <div class="modal-header">
                                    <h5 class="modal-title">Approve Accountant Edit Access</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <p class="small text-muted mb-3">
                                        Set how long {{ $req->user->name }} can edit accounting record #{{ $req->accountingEntry->id }} after approval.
                                    </p>
                                    <div class="row g-2">
                                        <div class="col-5">
                                            <x-ui.form.input
                                                name="duration_value"
                                                type="number"
                                                label="Duration"
                                                :id="'approve-duration-value-'.$req->id"
                                                :value="2"
                                                min="1"
                                                max="168"
                                                required
                                            />
                                        </div>
                                        <div class="col-7">
                                            <x-ui.form.select
                                                name="duration_unit"
                                                label="Unit"
                                                :id="'approve-duration-unit-'.$req->id"
                                                :value="'hours'"
                                                :options="['hours' => 'Hours', 'minutes' => 'Minutes', 'days' => 'Days']"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div class="ui-hint mt-2">
                                        Example: 2 hours, 30 minutes, or 1 day.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <x-ui.button type="button" variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                                    <x-ui.button type="submit" variant="success">Approve Access</x-ui.button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endpush
                @endforeach
            </tbody>
        </x-ui.table>
    </x-ui.card>
    @endif

    <!-- Reviewer: My Recent Requests -->
    @if(isset($myEditRequests) && $myEditRequests->count() > 0)
    <x-ui.card class="mb-4">
        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#myRequestsCollapse" style="cursor: pointer;">
            <h6 class="mb-0 text-muted"><i class="bi bi-clock-history me-2" aria-hidden="true"></i> My Recent Edit Requests</h6>
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </div>
        <div class="collapse mt-2" id="myRequestsCollapse">
            <x-ui.table>
                <tbody>
                    @foreach($myEditRequests as $req)
                    <tr>
                        <td>Record #{{ $req->accountingEntry->id }}</td>
                        <td class="text-muted small">{{ Str::limit($req->reason, 40) }}</td>
                        <td>
                            @if($req->status === 'pending')
                                <x-ui.status-badge tone="warning" label="Pending" />
                            @elseif($req->status === 'approved')
                                <x-ui.status-badge tone="success" label="Approved" />
                                @if($req->expires_at && $req->expires_at > now())
                                    <small class="text-success ms-1">(Expires {{ $req->expires_at->diffForHumans() }})</small>
                                    @if($req->accountingEntry && ! $req->accountingEntry->isLocked())
                                        <small class="d-block text-primary mt-1">Record unlocked. After editing, lock it back.</small>
                                    @endif
                                @else
                                    <x-ui.status-badge tone="neutral" label="Timed Out" class="ms-1" />
                                @endif
                            @elseif($req->status === 'timed_out')
                                <x-ui.status-badge tone="neutral" label="Timed Out" />
                                <small class="d-block text-muted mt-1">Submit another edit request to reopen access.</small>
                            @else
                                <x-ui.status-badge tone="danger" label="Rejected" />
                            @endif
                        </td>
                        <td class="text-end small">{{ $req->created_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        </div>
    </x-ui.card>
    @endif

    <!-- Summary Cards (Exact Figures) -->
    <div class="row g-3 mb-4">
        @foreach($buildings as $building)
        @php
            $stats = $summaries->get($building->id);
            $income = $stats['income'] ?? 0;
            $expense = $stats['expense'] ?? 0;
            $cashflow = $stats['cashflow'] ?? 0;
        @endphp
        <div class="col-md-4">
            <x-ui.card :title="$building->name" icon="bi bi-building" class="h-100">
                <x-slot:actions>
                    @if(auth()->user()->isAdmin())
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Export options for {{ $building->name }}">
                                <i class="bi bi-download" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Export {{ $building->name }}</h6></li>
                                <li>
                                    <a
                                        class="dropdown-item statement-year-link"
                                        href="{{ route('accounting.export', array_filter(['building_id' => $building->id, 'year' => $selectedStatementYear, 'company_id' => $selectedCompanyId])) }}"
                                        target="_blank"
                                        data-base-url="{{ route('accounting.export', array_filter(['building_id' => $building->id, 'company_id' => $selectedCompanyId])) }}"
                                        data-year-target="statement-year-{{ $building->id }}"
                                    >
                                        <i class="bi bi-file-earmark-spreadsheet me-2 text-success" aria-hidden="true"></i> CSV Report
                                    </a>
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item statement-year-link"
                                        href="{{ route('accounting.export-pdf', array_filter(['building_id' => $building->id, 'year' => $selectedStatementYear, 'company_id' => $selectedCompanyId])) }}"
                                        target="_blank"
                                        data-base-url="{{ route('accounting.export-pdf', array_filter(['building_id' => $building->id, 'company_id' => $selectedCompanyId])) }}"
                                        data-year-target="statement-year-{{ $building->id }}"
                                    >
                                        <i class="bi bi-file-earmark-pdf me-2 text-danger" aria-hidden="true"></i> PDF Report
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endif
                    @if(auth()->user()->isAccountant() && !auth()->user()->isAdmin())
                        <x-ui.button
                            variant="outline-primary"
                            size="sm"
                            icon="bi bi-eye"
                            class="statement-year-link"
                            :href="route('accounting.report', array_filter(['building_id' => $building->id, 'year' => $selectedStatementYear, 'company_id' => $selectedCompanyId]))"
                            :data-base-url="route('accounting.report', array_filter(['building_id' => $building->id, 'company_id' => $selectedCompanyId]))"
                            data-year-target="statement-year-{{ $building->id }}"
                        >View</x-ui.button>
                    @endif
                </x-slot:actions>

                <div class="mb-3">
                    <label for="statement-year-{{ $building->id }}" class="ui-label small text-muted mb-1">Statement Year</label>
                    <select class="form-select form-select-sm statement-year-select" id="statement-year-{{ $building->id }}">
                        @foreach($statementYears as $statementYear)
                            <option value="{{ $statementYear }}" {{ $selectedStatementYear === $statementYear ? 'selected' : '' }}>
                                {{ $statementYear }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <x-ui.button
                        variant="outline-primary"
                        size="sm"
                        icon="bi bi-eye"
                        class="statement-year-link"
                        :href="route('accounting.report', array_filter(['building_id' => $building->id, 'year' => $selectedStatementYear, 'company_id' => $selectedCompanyId]))"
                        :data-base-url="route('accounting.report', array_filter(['building_id' => $building->id, 'company_id' => $selectedCompanyId]))"
                        data-year-target="statement-year-{{ $building->id }}"
                    >View Statement</x-ui.button>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Income:</span>
                    <span class="fw-bold text-success">₦{{ number_format($income, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Expenses:</span>
                    <span class="fw-bold text-danger">₦{{ number_format($expense, 2) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Net Cash Flow:</span>
                    <span class="fs-5 fw-bold {{ $cashflow >= 0 ? 'text-success' : 'text-danger' }}">
                        ₦{{ number_format($cashflow, 2) }}
                    </span>
                </div>
            </x-ui.card>
        </div>
        @endforeach
    </div>

    <!-- Spreadsheet Interface -->
    <x-ui.card flush>
        <ul class="nav nav-tabs px-3 pt-2" id="accountingTabs" role="tablist">
            <!-- Logic to set active tab based on role -->
            @php
                $defaultTab = 'all';
            @endphp

            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $defaultTab === 'all' ? 'active' : '' }} px-4 py-3" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                    <i class="bi bi-table me-1" aria-hidden="true"></i> All Records
                </button>
            </li>
            @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-3" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Record Income
                </button>
            </li>
            @endif
            @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $defaultTab === 'expense' ? 'active' : '' }} px-4 py-3" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button" role="tab">
                    <i class="bi bi-dash-circle me-1" aria-hidden="true"></i> Record Expense
                </button>
            </li>
            @endif
        </ul>

        <div class="tab-content" id="accountingTabsContent">

            <!-- All Records Tab -->
            <div class="tab-pane fade {{ $defaultTab === 'all' ? 'show active' : '' }} p-3" id="all" role="tabpanel">
                <!-- Search & Filter Bar -->
                <form action="{{ route('accounting.index') }}" method="GET" class="mb-3">
                    @if($selectedCompanyId)
                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                    @endif
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" id="accountingSearch" class="form-control" placeholder="Search payments, expenses, tenants, units, or buildings..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="building_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Buildings</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}" {{ (int) $selectedBuildingId === (int) $building->id ? 'selected' : '' }}>
                                        {{ $building->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <x-ui.button type="submit" variant="primary" block>Filter</x-ui.button>
                        </div>
                        <div class="col-md-2">
                            <x-ui.button block variant="outline-secondary" :href="route('accounting.index', array_filter(['company_id' => $selectedCompanyId, 'year' => $selectedStatementYear]))">Reset</x-ui.button>
                        </div>
                    </div>
                </form>

                <x-ui.table
                    hover
                    class="accounting-records-table mb-0"
                    style="min-width: 1000px;"
                    :headers="[
                        ['label' => 'Date', 'class' => 'col-date'],
                        ['label' => 'Building', 'class' => 'col-building'],
                        ['label' => 'Type', 'class' => 'col-type'],
                        ['label' => 'Category', 'class' => 'col-category'],
                        ['label' => 'Details', 'class' => 'col-details'],
                        ['label' => 'Amount', 'class' => 'col-amount'],
                        ['label' => 'Status', 'class' => 'col-status'],
                        ['label' => 'Actions', 'class' => 'col-actions'],
                    ]"
                >
                    <tbody>
                        @forelse($records as $record)
                        @php
                            $isAccountingRecord = $record['source'] === 'accounting';
                            $entry = $isAccountingRecord ? $record['model'] : null;
                            $payment = $isAccountingRecord ? null : $record['model'];
                            $typeTone = $badgeToneMap[$record['type_badge_class']] ?? 'neutral';
                            $statusTone = $badgeToneMap[$record['status_badge_class']] ?? 'neutral';
                        @endphp
                        <tr class="accounting-entry-row {{ $record['row_class'] }}">
                            <td class="col-date">{{ $record['date']->format('d M Y') }}</td>
                            <td class="col-building">{{ $record['building_name'] }}</td>
                            <td class="col-type">
                                <x-ui.status-badge :tone="$typeTone" :label="$record['type_label']" />
                            </td>
                            <td class="col-category">{{ $record['category'] }}</td>
                            <td class="col-details">
                                <div class="small text-wrap" style="max-width: 300px;">
                                    <div class="mobile-type-indicator-wrap mb-1">
                                        <x-ui.status-badge class="mobile-type-indicator" :tone="$typeTone" :label="$record['type_label']" />
                                        <span class="text-muted small mobile-category-indicator">{{ $record['category'] }}</span>
                                    </div>
                                    @if($isAccountingRecord && $entry->type === 'income')
                                        <strong>Tenant:</strong> {{ $entry->extra_details['tenant_name'] ?? 'N/A' }}<br>
                                        <strong>Unit:</strong> {{ $entry->extra_details['property_id'] ?? 'N/A' }}
                                        @if(!empty($entry->description))
                                            <br><span class="text-muted">{{ $entry->description }}</span>
                                        @endif
                                    @elseif($isAccountingRecord)
                                        @if(!empty($entry->extra_details['property_id']))
                                            <strong>Unit:</strong> {{ $entry->extra_details['property_id'] }}<br>
                                        @endif
                                        @if(!empty($entry->extra_details['tenant_name']))
                                            <strong>Tenant:</strong> {{ $entry->extra_details['tenant_name'] }}<br>
                                        @endif
                                        {{ $entry->description ?? 'No description' }}
                                    @else
                                        <strong>Tenant:</strong> {{ optional(optional($payment->rent)->tenant)->full_name ?? 'N/A' }}<br>
                                        <strong>Unit:</strong>
                                        @if($payment->rent && $payment->rent->unit)
                                            {{ $payment->rent->unit->unit_number }}
                                        @else
                                            N/A
                                        @endif
                                        @if($payment->payment_method)
                                            <br><strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                        @endif
                                        @if($payment->reference)
                                            <br><strong>Ref:</strong> {{ $payment->reference }}
                                        @endif
                                        @if($payment->notes)
                                            <br><span class="text-muted">{{ $payment->notes }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="col-amount fw-bold {{ $record['amount_class'] }}">
                                {{ $record['amount_prefix'] }}₦{{ number_format($record['model']->amount, 2) }}
                            </td>
                            <td class="col-status">
                                @if($isAccountingRecord && $entry->status === 'finalized')
                                    <x-ui.status-badge :tone="$statusTone" icon="bi bi-lock-fill" :label="$record['status_label']" />
                                @else
                                    <x-ui.status-badge :tone="$statusTone" :label="$record['status_label']" />
                                @endif
                            </td>
                            <td class="col-actions">
                                @if($isAccountingRecord)
                                @php
                                    $hasActiveEditPermission = !auth()->user()->isAdmin() && $entry->hasActiveEditPermission(auth()->id());
                                    $canDirectExpenseEdit = !auth()->user()->isAdmin() && $entry->canDirectlyEditByAccountant(auth()->id());
                                    $isAccountantWorkflowEntry = $entry->isInAccountantWorkflow();
                                    $hasPendingEditRequest = !auth()->user()->isAdmin() && $entry->relationLoaded('editRequests')
                                        ? $entry->editRequests->where('status', 'pending')->isNotEmpty()
                                        : false;
                                    $hasApprovedEditHistory = !auth()->user()->isAdmin() && $entry->relationLoaded('editRequests')
                                        ? $entry->editRequests->whereNotNull('approved_at')->isNotEmpty()
                                        : false;
                                    $canAccountantEditNow = $hasActiveEditPermission || $canDirectExpenseEdit;
                                    $canAccountantLockBack = $canAccountantEditNow || $hasApprovedEditHistory;
                                    $isPendingLockedExpenseForAdminReview = auth()->user()->isAdmin()
                                        && $entry->type === 'expense'
                                        && $entry->isLocked()
                                        && $entry->status === 'pending';
                                    $isAwaitingAccountantLockBackForAdmin = auth()->user()->isAdmin()
                                        && $isAccountantWorkflowEntry
                                        && ! $entry->isLocked()
                                        && in_array($entry->status, ['pending', 'rejected'], true);
                                @endphp
                                <div class="ui-btn-group">
                                    @if(!$entry->isLocked())
                                        @if(auth()->user()->isAdmin() && $isAwaitingAccountantLockBackForAdmin)
                                            <x-ui.icon-button label="Awaiting accountant lock back" icon="bi bi-hourglass-split" disabled />
                                        @elseif(auth()->user()->isAdmin() || $canAccountantLockBack)
                                            <form action="{{ route('accounting.finalize', $entry) }}" method="POST" onsubmit="return confirm('{{ auth()->user()->isAdmin() ? 'Are you sure? This will lock the record.' : 'You have finished editing this unlocked record. Lock it back now?' }}')">
                                                @csrf
                                                @method('PATCH')
                                                @if(auth()->user()->isAdmin())
                                                    <x-ui.icon-button type="submit" label="Finalize and lock" icon="bi bi-check-lg" />
                                                @else
                                                    <x-ui.button type="submit" variant="primary" size="sm" icon="bi bi-lock-fill">Lock Back</x-ui.button>
                                                @endif
                                            </form>
                                            @if(auth()->user()->isAdmin() && $entry->type === 'expense')
                                                <form action="{{ route('accounting.reject', $entry) }}" method="POST" onsubmit="return confirm('Reject this expense record?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-ui.icon-button type="submit" label="Reject expense record" icon="bi bi-x-lg" variant="danger" />
                                                </form>
                                            @endif
                                            @if(auth()->user()->isAdmin() || $canAccountantEditNow)
                                                <x-ui.icon-button
                                                    label="Edit entry"
                                                    icon="bi bi-pencil"
                                                    data-bs-toggle="modal"
                                                    :data-bs-target="'#editEntryModal'.$entry->id"
                                                />
                                            @endif
                                        @endif
                                    @elseif($isPendingLockedExpenseForAdminReview)
                                        <form action="{{ route('accounting.finalize', $entry) }}" method="POST" onsubmit="return confirm('Accept this expense entry?')">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.icon-button type="submit" label="Accept expense entry" icon="bi bi-check-lg" variant="success" />
                                        </form>
                                        <form action="{{ route('accounting.reject', $entry) }}" method="POST" onsubmit="return confirm('Reject this expense entry and return it to the accountant for correction?')">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.icon-button type="submit" label="Reject expense entry" icon="bi bi-x-lg" variant="danger" />
                                        </form>
                                    @elseif(auth()->user()->isAdmin())
                                        <form action="{{ route('accounting.unlock', $entry) }}" method="POST" onsubmit="return confirm('Unlock this record?')">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.icon-button type="submit" label="Unlock record" icon="bi bi-unlock-fill" />
                                        </form>
                                        <x-ui.icon-button
                                            label="Edit entry"
                                            icon="bi bi-pencil"
                                            data-bs-toggle="modal"
                                            :data-bs-target="'#editEntryModal'.$entry->id"
                                        />
                                    @elseif(!auth()->user()->isAdmin())
                                        @if($canAccountantEditNow)
                                            <x-ui.button
                                                variant="primary"
                                                size="sm"
                                                icon="bi bi-pencil-fill"
                                                data-bs-toggle="modal"
                                                :data-bs-target="'#editEntryModal'.$entry->id"
                                            >Edit Now</x-ui.button>
                                        @elseif($hasPendingEditRequest)
                                            <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-hourglass-split" disabled>Edit Requested</x-ui.button>
                                        @else
                                            <x-ui.button
                                                variant="outline-secondary"
                                                size="sm"
                                                icon="bi bi-pencil-square"
                                                data-bs-toggle="modal"
                                                :data-bs-target="'#editRequestModal'.$entry->id"
                                            >Request Edit</x-ui.button>
                                        @endif
                                    @endif
                                </div>

                                @if($entry->isLocked() && !auth()->user()->isAdmin() && !$hasPendingEditRequest)
                                    @push('modals')
                                    <div class="modal fade" id="editRequestModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('accounting.request-edit', $entry) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Request Edit Access</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason for edit</label>
                                                            <textarea name="reason" class="form-control" rows="3" required placeholder="Why do you need to edit this?"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <x-ui.button type="button" variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                                                        <x-ui.button type="submit" variant="primary">Submit Request</x-ui.button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endpush
                                @endif

                                @push('modals')
                                <div class="modal fade" id="editEntryModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('accounting.update', $entry) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Entry #{{ $entry->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label">Amount</label>
                                                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ $entry->amount }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Category</label>
                                                        <input type="text" name="category" class="form-control" value="{{ $entry->category }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Date</label>
                                                        <input type="date" name="entry_date" class="form-control" value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" class="form-control" rows="2">{{ $entry->description }}</textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Related Tenant</label>
                                                        <input type="text" name="tenant_name" class="form-control" value="{{ $entry->extra_details['tenant_name'] ?? '' }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Related Unit / Property</label>
                                                        <input type="text" name="property_id" class="form-control" value="{{ $entry->extra_details['property_id'] ?? '' }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <x-ui.button type="button" variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                                                    <x-ui.button type="submit" name="submit_action" value="save" variant="outline-primary">Save Changes</x-ui.button>
                                                    @if(!auth()->user()->isAdmin() && $canAccountantLockBack)
                                                        <x-ui.button type="submit" name="submit_action" value="save_and_lock" variant="primary" icon="bi bi-lock-fill">Save and Lock Back</x-ui.button>
                                                    @endif
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endpush
                                @else
                                <div class="ui-btn-group">
                                    @if(auth()->user()->isAdmin())
                                        <x-ui.icon-button label="View payment" icon="bi bi-eye" :href="route('payments.show', $payment)" />
                                        <x-ui.icon-button label="Edit payment" icon="bi bi-pencil" :href="route('payments.edit', $payment)" />
                                    @else
                                        <span class="text-muted small">Managed in Payments</span>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <x-ui.table.empty-state :colspan="8" icon="bi bi-inbox" title="No accounting records found." />
                        @endforelse
                    </tbody>
                </x-ui.table>
                <div class="mt-4">
                    {{ $records->withQueryString()->links() }}
                </div>
            </div>

            <!-- Record Income Tab -->
            @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
            <div class="tab-pane fade p-4" id="income" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <x-ui.card>
                            <h4 class="mb-4 text-success"><i class="bi bi-plus-circle me-2" aria-hidden="true"></i> Record New Income</h4>
                            <form action="{{ route('accounting.store.income') }}" method="POST" class="row g-3" id="income-record-form">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Building <span class="text-danger">*</span></label>
                                    <select name="building_id" class="form-select building-context-select" id="income-building-select" data-prefix="income" required>
                                        <option value="">Select Building</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Property / Unit</label>
                                    <select name="property_id" class="form-select building-property-select" id="income-property-id" data-prefix="income">
                                        <option value="">Select building first</option>
                                    </select>
                                    <div class="form-text">Auto-populated from the selected building.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tenant</label>
                                    <select name="tenant_name" class="form-select building-tenant-select" id="income-tenant-name" data-prefix="income">
                                        <option value="">Select building first</option>
                                    </select>
                                    <div class="form-text">Updates automatically based on the property selection.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="Rent">Rent</option>
                                        <option value="Service Charge">Service Charge</option>
                                        <option value="Utility Reimbursement">Utility Reimbursement</option>
                                        <option value="Penalty/Fee">Penalty/Fee</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="entry_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Building Address</label>
                                    <input type="text" class="form-control" id="income-building-address" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Property Manager</label>
                                    <input type="text" class="form-control" id="income-manager-name" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Occupancy Summary</label>
                                    <input type="text" class="form-control" id="income-units-summary" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description / Notes</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                                </div>
                                <div class="col-12 mt-4 text-center d-none d-md-block">
                                    <x-ui.button type="submit" variant="success" size="lg" icon="bi bi-save">Save Income Record</x-ui.button>
                                </div>
                                <div class="col-12 mt-4 d-md-none text-center">
                                    <x-ui.alert tone="warning">Desktop Only</x-ui.alert>
                                </div>
                            </form>
                        </x-ui.card>
                    </div>
                </div>
            </div>
            @endif

            <!-- Record Expense Tab -->
            @if(auth()->user()->isAdmin() || auth()->user()->isAccountant())
            <div class="tab-pane fade {{ $defaultTab === 'expense' ? 'show active' : '' }} p-4" id="expense" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <x-ui.card>
                            <h4 class="mb-4 text-danger"><i class="bi bi-dash-circle me-2" aria-hidden="true"></i> Record New Expense</h4>
                            <form action="{{ route('accounting.store.expense') }}" method="POST" class="row g-3" id="expense-record-form">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Building <span class="text-danger">*</span></label>
                                    <select name="building_id" class="form-select building-context-select" id="expense-building-select" data-prefix="expense" required>
                                        <option value="">Select Building</option>
                                        @foreach($buildings as $building)
                                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Related Property / Unit</label>
                                    <select name="property_id" class="form-select building-property-select" id="expense-property-id" data-prefix="expense">
                                        <option value="">Select building first</option>
                                    </select>
                                    <div class="form-text">Context-aware options based on the selected building.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Related Tenant</label>
                                    <select name="tenant_name" class="form-select building-tenant-select" id="expense-tenant-name" data-prefix="expense">
                                        <option value="">Select building first</option>
                                    </select>
                                    <div class="form-text">Useful when an expense is tied to a specific occupied unit.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Cleaning">Cleaning</option>
                                        <option value="Security">Security</option>
                                        <option value="Management Fee">Management Fee</option>
                                        <option value="Taxes">Taxes</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="entry_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Building Address</label>
                                    <input type="text" class="form-control" id="expense-building-address" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Property Manager</label>
                                    <input type="text" class="form-control" id="expense-manager-name" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Occupancy Summary</label>
                                    <input type="text" class="form-control" id="expense-units-summary" readonly placeholder="Auto-filled from selected building">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description / Notes <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="3" required placeholder="Detailed description of the expense..."></textarea>
                                </div>
                                <div class="col-12 mt-4 text-center d-none d-md-block">
                                    <x-ui.button type="submit" variant="danger" size="lg" icon="bi bi-save">Save Expense Record</x-ui.button>
                                </div>
                                <div class="col-12 mt-4 d-md-none text-center">
                                    <x-ui.alert tone="warning">Desktop Only</x-ui.alert>
                                </div>
                            </form>
                        </x-ui.card>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </x-ui-card>
</div>

<style>
    /* The income / expense tabs keep their semantic colour. The palette values
       come from the --tone-* design tokens rather than hard-coded hex codes,
       and the id selectors outrank the global .nav-tabs rules in app-shell.css. */
    #accountingTabs #income-tab { color: var(--tone-success); }
    #accountingTabs #expense-tab { color: var(--tone-danger); }
    #accountingTabs .nav-link { border-bottom: 3px solid transparent; font-weight: 600; }
    #accountingTabs .nav-link.active { background: transparent; }
    #accountingTabs #income-tab.active { border-bottom-color: currentColor; }
    #accountingTabs #expense-tab.active { border-bottom-color: currentColor; }

    .mobile-type-indicator-wrap {
        display: none;
    }
    @media (max-width: 767.98px) and (orientation: portrait) {
        .accounting-records-table {
            min-width: 100% !important;
        }
        .accounting-records-table .col-type,
        .accounting-records-table .col-category,
        .accounting-records-table .col-amount,
        .accounting-records-table .col-status,
        .accounting-records-table .col-actions {
            display: none;
        }
        .accounting-records-table .col-date {
            white-space: nowrap;
            width: 1%;
        }
        .accounting-records-table .col-building {
            min-width: 130px;
        }
        .accounting-records-table .col-details {
            min-width: 220px;
        }
        .accounting-records-table .mobile-type-indicator-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }
        .accounting-records-table .accounting-entry-row.type-income .col-details {
            border-left: 4px solid var(--tone-success);
        }
        .accounting-records-table .accounting-entry-row.type-expense .col-details {
            border-left: 4px solid var(--tone-danger);
        }
        .accounting-records-table .accounting-entry-row.type-payment .col-details {
            border-left: 4px solid var(--tone-success);
        }
    }
    @media (max-width: 991.98px) and (orientation: landscape) {
        .accounting-records-table {
            min-width: 960px !important;
        }
    }
</style>

<style>
.report-protect-overlay{position:fixed;inset:0;background:rgba(0,0,0,.25);backdrop-filter:blur(4px);display:none;z-index:9999}
.report-blurred #reportRoot{filter:blur(6px);pointer-events:none}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buildingContexts = @json($buildingContexts);
        const searchInput = document.getElementById('accountingSearch');
        const table = document.querySelector('.accounting-records-table');
        const rows = table ? table.querySelectorAll('tbody tr') : [];
        const companySelect = document.getElementById('global-company-select');
        const statementYearLinks = document.querySelectorAll('.statement-year-link');
        const statementYearSelects = document.querySelectorAll('.statement-year-select, #global-statement-year');
        const noResultsRow = document.createElement('tr');
        
        // Setup No Results Row
        noResultsRow.innerHTML = '<td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No matching records found.</td>';
        noResultsRow.style.display = 'none';
        if (table) {
            table.querySelector('tbody').appendChild(noResultsRow);
        }

        if (searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase().trim();
                let visibleCount = 0;

                rows.forEach(row => {
                    if (row === noResultsRow) return;

                    const text = row.textContent.toLowerCase();
                    if (text.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
            });
        }

        function updateStatementYearLink(link) {
            const targetId = link.dataset.yearTarget;
            const baseUrl = link.dataset.baseUrl;
            const yearSelect = targetId ? document.getElementById(targetId) : null;
            const year = yearSelect ? yearSelect.value : '';

            if (!baseUrl || !year) {
                return;
            }

            const separator = baseUrl.includes('?') ? '&' : '?';
            link.href = `${baseUrl}${separator}year=${encodeURIComponent(year)}`;
        }

        statementYearLinks.forEach(updateStatementYearLink);
        statementYearSelects.forEach(select => {
            select.addEventListener('change', function() {
                statementYearLinks.forEach(updateStatementYearLink);
            });
        });

        if (companySelect) {
            companySelect.addEventListener('change', function() {
                const nextUrl = this.options[this.selectedIndex]?.dataset.url;
                if (nextUrl) {
                    window.location.href = nextUrl;
                }
            });
        }

        function setReadOnlyValue(id, value, fallback = '') {
            const field = document.getElementById(id);
            if (field) {
                field.value = value || fallback;
            }
        }

        function repopulateSelect(select, options, placeholder, selectedValue = '') {
            if (!select) return;

            select.innerHTML = '';
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);

            options.forEach(option => {
                const el = document.createElement('option');
                el.value = option.value;
                el.textContent = option.label;
                if (selectedValue && selectedValue === option.value) {
                    el.selected = true;
                }
                select.appendChild(el);
            });
        }

        function syncTenantToProperty(prefix) {
            const propertySelect = document.getElementById(`${prefix}-property-id`);
            const tenantSelect = document.getElementById(`${prefix}-tenant-name`);
            if (!propertySelect || !tenantSelect) return;

            const buildingId = document.getElementById(`${prefix}-building-select`)?.value;
            const context = buildingContexts[buildingId];
            if (!context) return;

            const selectedUnit = context.unit_options.find(unit => unit.property_id === propertySelect.value);
            if (selectedUnit && selectedUnit.tenant_name) {
                tenantSelect.value = selectedUnit.tenant_name;
            }
        }

        function syncPropertyToTenant(prefix) {
            const propertySelect = document.getElementById(`${prefix}-property-id`);
            const tenantSelect = document.getElementById(`${prefix}-tenant-name`);
            if (!propertySelect || !tenantSelect) return;

            const buildingId = document.getElementById(`${prefix}-building-select`)?.value;
            const context = buildingContexts[buildingId];
            if (!context) return;

            const selectedTenant = context.tenant_options.find(tenant => tenant.name === tenantSelect.value);
            if (selectedTenant && selectedTenant.property_id) {
                propertySelect.value = selectedTenant.property_id;
            }
        }

        function applyBuildingContext(prefix) {
            const buildingSelect = document.getElementById(`${prefix}-building-select`);
            const propertySelect = document.getElementById(`${prefix}-property-id`);
            const tenantSelect = document.getElementById(`${prefix}-tenant-name`);
            const context = buildingContexts[buildingSelect?.value] || null;

            if (!context) {
                repopulateSelect(propertySelect, [], 'Select building first');
                repopulateSelect(tenantSelect, [], 'Select building first');
                setReadOnlyValue(`${prefix}-building-address`, '');
                setReadOnlyValue(`${prefix}-manager-name`, '');
                setReadOnlyValue(`${prefix}-units-summary`, '');
                return;
            }

            repopulateSelect(
                propertySelect,
                context.unit_options.map(unit => ({
                    value: unit.property_id,
                    label: `${unit.label} (${unit.status})`,
                })),
                'Select property / unit'
            );

            repopulateSelect(
                tenantSelect,
                context.tenant_options.map(tenant => ({
                    value: tenant.name,
                    label: tenant.label,
                })),
                context.tenant_options.length ? 'Select tenant' : 'No active tenants available'
            );

            setReadOnlyValue(`${prefix}-building-address`, context.address, 'No building address');
            setReadOnlyValue(`${prefix}-manager-name`, context.manager_name, 'Unassigned');
            setReadOnlyValue(`${prefix}-units-summary`, `${context.occupied_units} occupied of ${context.total_units} units`);

            if (context.unit_options.length === 1) {
                propertySelect.value = context.unit_options[0].property_id;
                syncTenantToProperty(prefix);
            }
            if (!propertySelect.value && context.tenant_options.length === 1) {
                tenantSelect.value = context.tenant_options[0].name;
                syncPropertyToTenant(prefix);
            }
        }

        ['income', 'expense'].forEach(prefix => {
            const buildingSelect = document.getElementById(`${prefix}-building-select`);
            const propertySelect = document.getElementById(`${prefix}-property-id`);
            const tenantSelect = document.getElementById(`${prefix}-tenant-name`);

            if (!buildingSelect) return;

            buildingSelect.addEventListener('change', function() {
                applyBuildingContext(prefix);
            });

            if (propertySelect) {
                propertySelect.addEventListener('change', function() {
                    syncTenantToProperty(prefix);
                });
            }

            if (tenantSelect) {
                tenantSelect.addEventListener('change', function() {
                    syncPropertyToTenant(prefix);
                });
            }

            applyBuildingContext(prefix);
        });

        const root = document.getElementById('reportRoot') || document.querySelector('.container-fluid');
        const overlay = document.createElement('div');
        overlay.className = 'report-protect-overlay';
        document.body.appendChild(overlay);
        function applyBlur(){document.body.classList.add('report-blurred');overlay.style.display='block'}
        function removeBlur(){document.body.classList.remove('report-blurred');overlay.style.display='none'}
        window.addEventListener('blur', applyBlur);
        document.addEventListener('visibilitychange', function(){if(document.hidden){applyBlur()}else{removeBlur()}});
        window.addEventListener('focus', removeBlur);
        document.addEventListener('contextmenu', function(e){if(root && root.contains(e.target)) e.preventDefault()});
        document.addEventListener('keydown', function(e){
            const k = e.key.toLowerCase();
            if(k==='printscreen') e.preventDefault();
            if(e.ctrlKey && k==='p') e.preventDefault();
            if(e.ctrlKey && e.shiftKey && (k==='s' || k==='i')) e.preventDefault();
        });
    });
</script>
@endsection
