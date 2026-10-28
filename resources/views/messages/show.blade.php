@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Message</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('messages.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong>{{ $message->sender->name ?? 'Unknown' }}</strong>
                <span class="text-muted small">({{ optional($message->sender->role)->name }})</span>
                <div class="text-muted small">
                    {{ $message->created_at->format('M d, Y \a\t g:i A') }}
                </div>
            </div>
            <div class="text-end">
                <span class="text-muted small">To:</span>
                <div><strong>{{ $message->recipient->name ?? 'Unknown' }}</strong></div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="message-body" style="white-space: pre-wrap; line-height: 1.6;">
            {{ $message->body }}
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            @if($message->read_at)
                <i class="bi bi-check2-all text-success"></i> Read {{ $message->read_at->diffForHumans() }}
            @else
                <i class="bi bi-check2"></i> Unread
            @endif
        </span>
        @if((int) Auth::id() === (int) $message->recipient_id)
            <div class="d-flex gap-2">
                @if(!Auth::user()->isTenant())
                    <a href="{{ route('messages.reply', $message->id) }}" class="btn btn-sm" style="background-color: var(--primary-color); color: #fff;">
                        <i class="bi bi-reply me-1"></i> Reply
                    </a>
                @endif
                @if(Auth::user()->isTenant() || Auth::user()->isAccountant())
                    <form method="POST" action="{{ route('messages.acknowledge', $message->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-check2-circle me-1"></i> Acknowledge
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
