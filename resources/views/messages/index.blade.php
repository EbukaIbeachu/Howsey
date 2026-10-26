@extends('layouts.app')

@section('content')
<x-ui.page-header title="Messages">
    <x-slot:actions>
        @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
            <x-ui.button variant="primary" size="sm" icon="bi bi-pencil-square" :href="route('messages.create')">Compose</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))
    <x-ui.alert tone="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
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
        <x-ui.card flush>
            @if($inbox->isEmpty())
                <x-ui.empty-state icon="bi bi-inbox" title="No messages in your inbox." class="py-4" />
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
        </x-ui.card>
    </div>

    {{-- Sent --}}
    @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
    <div class="tab-pane fade {{ $tab === 'sent' ? 'show active' : '' }}" id="sent-pane" role="tabpanel">
        <x-ui.card flush>
            @if($sent->isEmpty())
                <x-ui.empty-state icon="bi bi-send" title="No sent messages." class="py-4" />
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
        </x-ui.card>
    </div>
    @endif
</div>
@endsection
