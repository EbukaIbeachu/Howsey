@extends('layouts.app')

@section('content')
@php
    $headers = [
        'Record',
        'Requested By',
        'Reason',
        'Status',
        'Submitted',
        'Reviewed By',
        'Actions',
    ];
@endphp

<x-ui.page-header title="Manager Requests" />

<x-ui.card flush>
    <x-ui.table :headers="$headers">
        <tbody>
            @forelse($requests as $editRequest)
                @php
                    $target = $editRequest->requestable;
                    $requestKindLabel = \App\Models\ManagerEditRequest::requestKindLabel($editRequest->request_kind);
                    $approvalHint = match ($editRequest->request_kind) {
                        \App\Models\ManagerEditRequest::REQUEST_KIND_AGREEMENT_DOWNLOAD => 'Approving this gives the manager a 2-hour download window for this uploaded agreement.',
                        \App\Models\ManagerEditRequest::REQUEST_KIND_AGREEMENT_DELETE => 'Approving this gives the manager a 2-hour delete window for this uploaded agreement.',
                        default => 'Approving this gives the manager a 2-hour edit window for this exact record.',
                    };
                    $targetLabel = match (true) {
                        $target instanceof \App\Models\Building => 'Building: '.$target->name,
                        $target instanceof \App\Models\Tenant => 'Tenant: '.$target->full_name,
                        $target instanceof \App\Models\Rent => 'Rental Agreement #'.$target->id,
                        $target instanceof \App\Models\Payment => 'Payment #'.$target->id,
                        $target instanceof \App\Models\Unit => 'Unit: '.$target->unit_number.' ('.$target->building->name.')',
                        default => 'Record unavailable',
                    };
                    $targetUrl = match (true) {
                        $target instanceof \App\Models\Building => route('buildings.show', $target),
                        $target instanceof \App\Models\Tenant => route('tenants.show', $target),
                        $target instanceof \App\Models\Rent => route('rents.show', $target),
                        $target instanceof \App\Models\Payment => route('payments.show', $target),
                        $target instanceof \App\Models\Unit => route('units.show', $target),
                        default => null,
                    };
                    $statusTone = match ($editRequest->status) {
                        'approved', 'completed' => 'success',
                        'rejected' => 'danger',
                        'timed_out' => 'neutral',
                        default => 'warning',
                    };
                    $statusLabel = match ($editRequest->status) {
                        'approved', 'completed' => 'Approved',
                        'rejected' => 'Rejected',
                        'timed_out' => 'Timed Out',
                        default => 'Pending',
                    };
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $targetLabel }}</div>
                        <div class="small text-muted">{{ $requestKindLabel }}</div>
                        @if($targetUrl)
                            <a href="{{ $targetUrl }}" class="small text-decoration-none">Open record</a>
                        @endif
                    </td>
                    <td>{{ $editRequest->requester?->name ?? 'Unknown' }}</td>
                    <td>{{ $editRequest->reason }}</td>
                    <td>
                        <x-ui.status-badge :tone="$statusTone" :label="$statusLabel" />
                        @if($editRequest->status === 'approved' && $editRequest->expires_at)
                            <div class="small text-muted mt-1">Until {{ $editRequest->expires_at->format('d M Y, h:i A') }}</div>
                        @elseif($editRequest->status === 'timed_out' && $editRequest->expires_at)
                            <div class="small text-muted mt-1">Expired {{ $editRequest->expires_at->format('d M Y, h:i A') }}</div>
                        @endif
                    </td>
                    <td>{{ $editRequest->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $editRequest->admin?->name ?? 'Pending' }}</td>
                    <td>
                        @if(auth()->user()->isAdmin() && $editRequest->status === 'pending')
                            <div class="ui-btn-group">
                                <x-ui.button variant="outline-success" size="sm"
                                    data-bs-toggle="modal" data-bs-target="#completeManagerEditRequest{{ $editRequest->id }}">Approve</x-ui.button>
                                <x-ui.button variant="outline-danger" size="sm"
                                    data-bs-toggle="modal" data-bs-target="#rejectManagerEditRequest{{ $editRequest->id }}">Reject</x-ui.button>
                            </div>
                        @else
                            <span class="text-muted small">
                                {{ $editRequest->reviewed_at?->format('d M Y, h:i A') ?? 'Waiting for admin' }}
                            </span>
                        @endif
                    </td>
                </tr>
                @if($editRequest->admin_notes)
                    <tr>
                        <td colspan="7" class="bg-light-subtle">
                            <strong>Admin Notes:</strong> {{ $editRequest->admin_notes }}
                        </td>
                    </tr>
                @endif

                @if(auth()->user()->isAdmin() && $editRequest->status === 'pending')
                    @push('modals')
                        <div class="modal fade" id="completeManagerEditRequest{{ $editRequest->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('manager-edit-requests.handle', $editRequest) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="approved">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Approve {{ $requestKindLabel }} Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted">{{ $approvalHint }}</p>
                                            <x-ui.form.textarea name="admin_notes" label="Admin Notes"
                                                :id="'admin_notes_complete_'.$editRequest->id" :rows="3"
                                                placeholder="Optional note for the manager" />
                                        </div>
                                        <div class="modal-footer">
                                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                                            <x-ui.button type="submit" variant="success">Mark Approved</x-ui.button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="rejectManagerEditRequest{{ $editRequest->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('manager-edit-requests.handle', $editRequest) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="rejected">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject {{ $requestKindLabel }} Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <x-ui.form.textarea name="admin_notes" label="Admin Notes"
                                                :id="'admin_notes_reject_'.$editRequest->id" :rows="3"
                                                placeholder="Optional reason for rejection" />
                                        </div>
                                        <div class="modal-footer">
                                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                                            <x-ui.button type="submit" variant="danger">Reject Request</x-ui.button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endpush
                @endif
            @empty
                <x-ui.table.empty-state :colspan="7" icon="bi bi-inbox" title="No edit requests found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($requests->hasPages())
        <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
    @endif
</x-ui.card>
@endsection
