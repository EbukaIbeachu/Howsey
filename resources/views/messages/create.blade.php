@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">{{ isset($message) ? 'Reply' : 'Compose Message' }}</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($recipients->isEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                There are no users you can message at this time.
            </div>
        @else
            <form method="POST" action="{{ route('messages.store') }}">
                @csrf
                @if(isset($message))
                    <input type="hidden" name="reply_to" value="{{ $message->id }}">
                @endif
                <div class="mb-3">
                    <label for="recipient_id" class="form-label fw-bold">Recipient</label>
                    <select name="recipient_id" id="recipient_id" class="form-select @error('recipient_id') is-invalid @enderror" required>
                        <option value="">-- Select a recipient --</option>
                        @foreach($recipients as $recipient)
                            <option value="{{ $recipient->id }}" {{ (isset($message) && $message->sender_id == $recipient->id) || old('recipient_id') == $recipient->id ? 'selected' : '' }}>
                                {{ $recipient->name }}
                                ({{ optional($recipient->role)->name }})
                                @if($recipient->company)
                                    — {{ $recipient->company->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('recipient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        @if(Auth::user()->isSuperAdmin())
                            You can message admins of any workspace.
                        @elseif(Auth::user()->isAdmin())
                            You can message managers, accountants, and tenants in your company.
                        @elseif(Auth::user()->isManager())
                            You can message tenants of buildings assigned to you.
                        @else
                            You can reply to the sender of this message.
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="body" class="form-label fw-bold">Message</label>
                    <textarea name="body" id="body" rows="8" class="form-control @error('body') is-invalid @enderror" placeholder="Type your message here..." required>{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn" style="background-color: var(--primary-color); color: #fff;">
                        <i class="bi bi-send me-1"></i> Send
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
