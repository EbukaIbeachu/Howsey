@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manager Requests</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Record</th>
                        <th>Requested By</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
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
                            $statusClass = match ($editRequest->status) {
                                'approved', 'completed' => 'bg-success',
                                'rejected' => 'bg-danger',
                                'timed_out' => 'bg-secondary',
                                default => 'bg-warning text-dark',
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
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
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
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeManagerEditRequest{{ $editRequest->id }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectManagerEditRequest{{ $editRequest->id }}">
                                            Reject
                                        </button>
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
                                                    <div class="mb-0">
                                                        <label for="admin_notes_complete_{{ $editRequest->id }}" class="form-label">Admin Notes</label>
                                                        <textarea class="form-control" id="admin_notes_complete_{{ $editRequest->id }}" name="admin_notes" rows="3" placeholder="Optional note for the manager"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Mark Approved</button>
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
                                                    <div class="mb-0">
                                                        <label for="admin_notes_reject_{{ $editRequest->id }}" class="form-label">Admin Notes</label>
                                                        <textarea class="form-control" id="admin_notes_reject_{{ $editRequest->id }}" name="admin_notes" rows="3" placeholder="Optional reason for rejection"></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject Request</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endpush
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No edit requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $requests->links() }}
    </div>
</div>
@endsection
