@extends('layouts.app')

@section('content')
@php
    $headers = [
        'Matched Account ID',
        'Phone',
        'Linked User',
        'Status',
        'Submitted',
        'Reviewed By',
        'Actions',
    ];
@endphp

<x-ui.page-header title="Password Recovery Requests" />

<x-ui.card flush>
    <x-ui.table :headers="$headers" caption="Password recovery requests" :caption-sr="true">
        <tbody>
            @forelse($requests as $recoveryRequest)
                @php
                    $statusTone = match ($recoveryRequest->status) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    };
                @endphp
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
                        <x-ui.status-badge :tone="$statusTone" :label="ucfirst($recoveryRequest->status)" />
                    </td>
                    <td>{{ $recoveryRequest->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $recoveryRequest->reviewer?->name ?? 'Pending' }}</td>
                    <td>
                        @if($recoveryRequest->status === 'pending')
                            <div class="ui-btn-group">
                                <x-ui.button variant="outline-success" size="sm"
                                    data-bs-toggle="modal" data-bs-target="#approveRecoveryRequest{{ $recoveryRequest->id }}">Approve</x-ui.button>
                                <x-ui.button variant="outline-danger" size="sm"
                                    data-bs-toggle="modal" data-bs-target="#rejectRecoveryRequest{{ $recoveryRequest->id }}">Reject</x-ui.button>
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
                <x-ui.table.empty-state :colspan="7" icon="bi bi-shield-lock" title="No password recovery requests found." />
            @endforelse
        </tbody>
    </x-ui.table>

    @if($requests->hasPages())
        <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
    @endif
</x-ui.card>

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
                            <x-ui.form.input name="temporary_password" type="password" label="New Temporary Password"
                                :id="'temporary_password_'.$recoveryRequest->id" required />
                            <x-ui.form.input name="temporary_password_confirmation" type="password" label="Confirm Temporary Password"
                                :id="'temporary_password_confirmation_'.$recoveryRequest->id" required />
                            <x-ui.form.textarea name="reviewer_notes" label="Reviewer Notes"
                                :id="'reviewer_notes_approve_'.$recoveryRequest->id" :rows="3"
                                placeholder="Optional approval note" />
                        </div>
                        <div class="modal-footer">
                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="success">Approve and Reset Password</x-ui.button>
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
                            <x-ui.form.textarea name="reviewer_notes" label="Reviewer Notes"
                                :id="'reviewer_notes_reject_'.$recoveryRequest->id" :rows="4" required />
                        </div>
                        <div class="modal-footer">
                            <x-ui.button variant="outline-secondary" data-bs-dismiss="modal">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="danger">Reject Request</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
