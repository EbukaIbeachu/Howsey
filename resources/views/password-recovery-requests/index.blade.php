@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Password Recovery Requests</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Matched Account ID</th>
                        <th>Phone</th>
                        <th>Linked User</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Reviewed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $recoveryRequest)
                        <tr>
                            <td>{{ $recoveryRequest->email }}</td>
                            <td>{{ $recoveryRequest->phone }}</td>
                            <td>
                                {{ $recoveryRequest->user?->name ?? 'No matching user yet' }}
                                @if($recoveryRequest->user?->company)
                                    <div class="small text-muted">{{ $recoveryRequest->user->company->name }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $recoveryRequest->status === 'approved' ? 'bg-success' : ($recoveryRequest->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ ucfirst($recoveryRequest->status) }}
                                </span>
                            </td>
                            <td>{{ $recoveryRequest->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $recoveryRequest->reviewer?->name ?? 'Pending' }}</td>
                            <td>
                                @if($recoveryRequest->status === 'pending')
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveRecoveryRequest{{ $recoveryRequest->id }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectRecoveryRequest{{ $recoveryRequest->id }}">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted small">{{ $recoveryRequest->reviewed_at?->format('d M Y, h:i A') }}</span>
                                @endif
                            </td>
                        </tr>
                        @if($recoveryRequest->reviewer_notes)
                            <tr>
                                <td colspan="7" class="bg-light-subtle">
                                    <strong>Reviewer Notes:</strong> {{ $recoveryRequest->reviewer_notes }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No password recovery requests found.</td>
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

@foreach($requests as $recoveryRequest)
    @if($recoveryRequest->status === 'pending')
        <div class="modal fade" id="approveRecoveryRequest{{ $recoveryRequest->id }}" tabindex="-1" aria-labelledby="approveRecoveryRequestLabel{{ $recoveryRequest->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveRecoveryRequestLabel{{ $recoveryRequest->id }}">Approve Recovery Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('password-recovery-requests.approve', $recoveryRequest) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Matched Account ID</label>
                                <input type="text" class="form-control" value="{{ $recoveryRequest->email }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Submitted Login Phone</label>
                                <input type="text" class="form-control" value="{{ $recoveryRequest->phone }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="temporary_password_{{ $recoveryRequest->id }}" class="form-label">New Temporary Password</label>
                                <input type="password" class="form-control" id="temporary_password_{{ $recoveryRequest->id }}" name="temporary_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="temporary_password_confirmation_{{ $recoveryRequest->id }}" class="form-label">Confirm Temporary Password</label>
                                <input type="password" class="form-control" id="temporary_password_confirmation_{{ $recoveryRequest->id }}" name="temporary_password_confirmation" required>
                            </div>
                            <div class="mb-0">
                                <label for="reviewer_notes_approve_{{ $recoveryRequest->id }}" class="form-label">Reviewer Notes</label>
                                <textarea class="form-control" id="reviewer_notes_approve_{{ $recoveryRequest->id }}" name="reviewer_notes" rows="3" placeholder="Optional approval note"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Approve and Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectRecoveryRequest{{ $recoveryRequest->id }}" tabindex="-1" aria-labelledby="rejectRecoveryRequestLabel{{ $recoveryRequest->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectRecoveryRequestLabel{{ $recoveryRequest->id }}">Reject Recovery Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('password-recovery-requests.reject', $recoveryRequest) }}">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <p class="text-muted">Add a reason for rejecting this request.</p>
                            <div class="mb-0">
                                <label for="reviewer_notes_reject_{{ $recoveryRequest->id }}" class="form-label">Reviewer Notes</label>
                                <textarea class="form-control" id="reviewer_notes_reject_{{ $recoveryRequest->id }}" name="reviewer_notes" rows="4" required></textarea>
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
    @endif
@endforeach
@endsection
