@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Messages</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
            <a href="{{ route('messages.create') }}" class="btn btn-sm" style="background-color: var(--primary-color); color: #fff;">
                <i class="bi bi-pencil-square"></i> Compose
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'inbox' ? 'active' : '' }}" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox-pane" type="button" role="tab">
            <i class="bi bi-inbox me-1"></i> Inbox
            @if($unreadCount > 0)
                <span class="badge bg-danger ms-1">{{ $unreadCount }}</span>
            @endif
        </button>
    </li>
    @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $tab === 'sent' ? 'active' : '' }}" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent-pane" type="button" role="tab">
            <i class="bi bi-send me-1"></i> Sent
        </button>
    </li>
    @endif
</ul>

<div class="tab-content" id="messageTabContent">
    {{-- Inbox --}}
    <div class="tab-pane fade {{ $tab === 'inbox' ? 'show active' : '' }}" id="inbox-pane" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                @if($inbox->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No messages in your inbox.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($inbox as $message)
                            <a href="{{ route('messages.show', $message) }}" class="list-group-item list-group-item-action {{ $message->read_at ? '' : 'bg-light' }}">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="d-flex align-items-center">
                                        @if(!$message->read_at)
                                            <span class="badge rounded-circle me-2" style="background-color: var(--primary-color); min-width: 10px; height: 10px; padding: 0;"></span>
                                        @else
                                            <span class="me-2" style="width: 10px; height: 10px; display: inline-block;"></span>
                                        @endif
                                        <h6 class="mb-1">
                                            {{ $message->sender->name ?? 'Unknown' }}
                                            <small class="text-muted">({{ optional($message->sender->role)->name }})</small>
                                        </h6>
                                    </div>
                                    <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 text-muted text-truncate" style="max-width: 100%;">{{ Str::limit($message->body, 120) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sent --}}
    @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
    <div class="tab-pane fade {{ $tab === 'sent' ? 'show active' : '' }}" id="sent-pane" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                @if($sent->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-send fs-1"></i>
                        <p class="mt-2">No sent messages.</p>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($sent as $message)
                            <a href="{{ route('messages.show', $message) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <h6 class="mb-1">
                                        To: {{ $message->recipient->name ?? 'Unknown' }}
                                        <small class="text-muted">({{ optional($message->recipient->role)->name }})</small>
                                    </h6>
                                    <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1 text-muted text-truncate" style="max-width: 100%;">{{ Str::limit($message->body, 120) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
