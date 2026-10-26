@extends('layouts.app')

@section('content')
<style>
    @media (max-width: 767.98px) {
        .payment-history-table th:nth-child(5),
        .payment-history-table td:nth-child(5) {
            display: none;
        }
    }
</style>

<x-ui.page-header title="Rental Agreement Details">
    <x-slot:actions>
        @if(auth()->user()->isManager())
            @include('partials.manager-edit-request-button', [
                'recordType' => 'rent',
                'recordId' => $rent->id,
                'recordLabel' => 'Rental Agreement #'.$rent->id,
                'buttonClass' => 'btn btn-sm btn-warning',
                'buttonText' => 'Request Edit',
                'iconClass' => 'bi bi-pencil-square',
            ])
        @elseif(auth()->user()->isAdmin())
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil" :href="route('rents.edit', $rent)">Edit Agreement</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<div class="row">
    <div class="col-md-6 mb-4">
        <x-ui.card title="Agreement Summary" class="h-100">
            @if(auth()->user()->isAdmin() && $rent->creator)
                <div class="mb-3 p-2 bg-light rounded text-muted small">
                    <i class="bi bi-person-check me-1"></i> Agreement created by: <strong>{{ $rent->creator->name }}</strong>
                </div>
            @endif
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th scope="row" class="text-muted" style="width: 40%">Status</th>
                        <td>
                            @if($rent->status === 'ACTIVE')
                                <x-ui.status-badge tone="success" label="Active" />
                            @else
                                <x-ui.status-badge tone="neutral" :label="ucfirst(strtolower($rent->status))" />
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">{{ $rent->rent_amount_label }}</th>
                        <td class="fs-5 fw-bold">
                            ₦{{ number_format($rent->annual_amount, 2) }}
                            <div class="small text-muted fw-normal">{{ $rent->rent_interval_suffix }}</div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Rent Duration</th>
                        <td>{{ $rent->rent_duration_label }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Due Schedule</th>
                        <td>{{ $rent->due_schedule_label }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Lease Period</th>
                        <td>{{ $rent->lease_period_label }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Signed Agreement</th>
                        <td>
                            @php
                                $managerCanRequestAgreementDownload = auth()->user()->isManager()
                                    && \App\Models\ManagerEditRequest::supportsRequestKinds()
                                    && filled($rent->signed_agreement_path);
                                $managerCanRequestAgreementDelete = $managerCanRequestAgreementDownload;
                                $managerCanDownloadAgreement = auth()->user()->isManager()
                                    && \App\Models\ManagerEditRequest::hasActivePermissionFor(auth()->id(), $rent, \App\Models\ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD);
                                $managerCanDeleteAgreement = auth()->user()->isManager()
                                    && \App\Models\ManagerEditRequest::hasActivePermissionFor(auth()->id(), $rent, \App\Models\ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE);
                            @endphp
                            @if(\Illuminate\Support\Facades\Schema::hasColumn('rents', 'signed_agreement_path'))
                                @if($rent->signed_agreement_path)
                                    <x-ui.button variant="outline-primary" size="sm" icon="bi bi-file-earmark-pdf" :href="route('rents.view-uploaded-agreement', $rent)" target="_blank" class="mb-2">View Signed Copy</x-ui.button>
                                    @if(auth()->user()->isAdmin() || $managerCanDownloadAgreement)
                                        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-download" :href="route('rents.download-agreement', $rent)" class="mb-2">Download Signed Copy</x-ui.button>
                                    @elseif($managerCanRequestAgreementDownload)
                                        <form action="{{ route('manager-edit-requests.store') }}" method="POST" class="d-inline-block mb-2">
                                            @csrf
                                            <input type="hidden" name="record_type" value="rent">
                                            <input type="hidden" name="record_id" value="{{ $rent->id }}">
                                            <input type="hidden" name="request_kind" value="agreement_download">
                                            <input type="hidden" name="reason" value="Manager requested permission to download the uploaded agreement file.">
                                            <x-ui.button type="submit" variant="outline-warning" size="sm" icon="bi bi-send">Request Download</x-ui.button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->isAdmin() || $managerCanDeleteAgreement)
                                        <form action="{{ route('rents.delete-uploaded-agreement', $rent) }}" method="POST" class="d-inline-block mb-2" onsubmit="return confirm('Delete the uploaded agreement file from this lease record?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-trash">Delete Signed Copy</x-ui.button>
                                        </form>
                                    @elseif($managerCanRequestAgreementDelete)
                                        <form action="{{ route('manager-edit-requests.store') }}" method="POST" class="d-inline-block mb-2">
                                            @csrf
                                            <input type="hidden" name="record_type" value="rent">
                                            <input type="hidden" name="record_id" value="{{ $rent->id }}">
                                            <input type="hidden" name="request_kind" value="agreement_delete">
                                            <input type="hidden" name="reason" value="Manager requested permission to delete the uploaded agreement file.">
                                            <x-ui.button type="submit" variant="outline-danger" size="sm" icon="bi bi-shield-lock">Request Delete</x-ui.button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">Not uploaded</span>
                                @endif

                                <form action="{{ route('rents.upload-agreement', $rent) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <input type="file" class="form-control" name="signed_agreement" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <button class="btn btn-primary" type="submit">Upload</button>
                                    </div>
                                    <div class="form-text small">Max 10MB (PDF/Image)</div>
                                </form>
                            @else
                                <div class="small text-danger">
                                    Signed agreement upload is not available on this deployment yet. Run the signed agreement migration first.
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-ui.card>
    </div>

    <div class="col-md-6 mb-4">
        <x-ui.card title="Parties Involved" class="h-100">
            <h6 class="text-muted mb-2">Tenant</h6>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <a href="{{ route('tenants.show', $rent->tenant) }}" class="text-decoration-none fw-bold">
                        {{ $rent->tenant->full_name }}
                    </a>
                    <div class="text-muted small">{{ $rent->tenant->email }}</div>
                </div>
            </div>

            <hr class="my-3">

            <h6 class="text-muted mb-2">Property</h6>
            <div class="d-flex align-items-center">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="bi bi-house"></i>
                </div>
                <div>
                    <a href="{{ route('units.show', $rent->unit) }}" class="text-decoration-none fw-bold">
                        Unit {{ $rent->unit->unit_number }}
                    </a>
                    <div class="text-muted small">{{ $rent->unit->building->name }}</div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<x-ui.card title="Payment History" flush>
    <x-slot:actions>
        <x-ui.button variant="primary" size="sm" icon="bi bi-plus-lg" :href="route('payments.create', ['rent_id' => $rent->id])">Record Payment</x-ui.button>
    </x-slot:actions>

    <x-ui.table class="mb-0 payment-history-table">
        <thead>
            <tr>
                <th scope="col">Date</th>
                <th scope="col">Amount</th>
                <th scope="col">Method</th>
                <th scope="col" class="text-end">Actions</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rent->payments->sortByDesc('payment_date') as $payment)
            <tr class="{{ $payment->status === 'COMPLETED' ? 'ui-row--success' : ($payment->status === 'PENDING' ? 'ui-row--warning' : 'ui-row--danger') }}">
                <td>{{ $payment->payment_date }}</td>
                <td>₦{{ number_format($payment->amount, 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                <td class="text-end">
                    <x-ui.icon-button label="View payment" icon="bi bi-eye" size="sm" :href="route('payments.show', $payment)" />
                </td>
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
                :colspan="5"
                icon="bi bi-cash-stack"
                title="No payments recorded for this agreement"
            />
            @endforelse
        </tbody>
    </x-ui.table>
</x-ui.card>
@endsection
