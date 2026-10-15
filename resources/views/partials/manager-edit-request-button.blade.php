@php
    $modalId = 'managerEditRequest'.\Illuminate\Support\Str::studly($recordType).$recordId;
    $resolvedButtonClass = $buttonClass ?? 'btn btn-sm btn-outline-warning';
    $resolvedIconClass = $iconClass ?? 'bi bi-pencil-square';
    $resolvedButtonText = $buttonText ?? 'Request Edit';
    $requestableClass = match ($recordType) {
        'building' => \App\Models\Building::class,
        'tenant' => \App\Models\Tenant::class,
        'rent' => \App\Models\Rent::class,
        'payment' => \App\Models\Payment::class,
        'unit' => \App\Models\Unit::class,
    };
    $editUrl = match ($recordType) {
        'building' => route('buildings.edit', $recordId),
        'tenant' => route('tenants.edit', $recordId),
        'rent' => route('rents.edit', $recordId),
        'payment' => route('payments.edit', $recordId),
        'unit' => route('units.edit', $recordId),
    };
    $latestEditRequest = auth()->check()
        ? \App\Models\ManagerEditRequest::latestForRecord(auth()->id(), $requestableClass, (int) $recordId)
        : null;
    $hasActiveEditWindow = $latestEditRequest?->hasActiveEditWindow() ?? false;
@endphp

@if(auth()->check() && auth()->user()->isManager())
    @if($hasActiveEditWindow)
        <div class="d-flex flex-column align-items-start gap-1">
            <a href="{{ $editUrl }}" class="{{ $resolvedButtonClass }}">
                <i class="bi bi-pencil"></i> Edit Now
            </a>
            <small class="text-success">
                Approved until {{ $latestEditRequest->expires_at->format('d M, h:i A') }}
            </small>
        </div>
    @elseif($latestEditRequest?->status === 'pending')
        <div class="d-flex flex-column align-items-start gap-1">
            <button type="button" class="{{ $resolvedButtonClass }}" disabled>
                <i class="bi bi-hourglass-split"></i> Pending Review
            </button>
            <small class="text-muted">Admin review is still pending for this record.</small>
        </div>
    @else
        <div class="d-flex flex-column align-items-start gap-1">
            <button type="button" class="{{ $resolvedButtonClass }}" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                <i class="{{ $resolvedIconClass }}"></i> {{ $resolvedButtonText }}
            </button>
            @if($latestEditRequest?->status === 'timed_out')
                <small class="text-danger">Previous approval timed out. Submit a new request.</small>
            @elseif($latestEditRequest?->status === 'rejected')
                <small class="text-muted">Last request was rejected. Submit a fresh request if needed.</small>
            @endif
        </div>
    @endif

    @push('modals')
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('manager-edit-requests.store') }}">
                        @csrf
                        <input type="hidden" name="record_type" value="{{ $recordType }}">
                        <input type="hidden" name="record_id" value="{{ $recordId }}">
                        <div class="modal-header">
                            <h5 class="modal-title">Request Admin Edit</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">Send an edit request to admin for <strong>{{ $recordLabel }}</strong>.</p>
                            <div class="mb-0">
                                <label for="{{ $modalId }}Reason" class="form-label">Reason for edit</label>
                                <textarea class="form-control" id="{{ $modalId }}Reason" name="reason" rows="4" required placeholder="Explain what should be changed and why."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Send Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endif
