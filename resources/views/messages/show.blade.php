@extends('layouts.app')

@section('content')
@php
    $senderMeta = '(' . optional($message->sender->role)->name . ') · ' . $message->created_at->format('M d, Y \a\t g:i A');
@endphp

<x-ui.page-header title="Message">
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('messages.index')">Back</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card :title="$message->sender->name ?? 'Unknown'" :subtitle="$senderMeta">
    <x-slot:actions>
        <div class="text-end">
            <span class="text-muted small">To:</span>
            <div><strong>{{ $message->recipient->name ?? 'Unknown' }}</strong></div>
        </div>
    </x-slot:actions>

    <div class="message-body" style="white-space: pre-wrap; line-height: 1.6;">
        {{ $message->body }}
    </div>

    <x-slot:footer>
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
                    <x-ui.button variant="primary" size="sm" icon="bi bi-reply"
                        :href="route('messages.reply', $message->id)">Reply</x-ui.button>
                @endif
                @if(Auth::user()->isTenant() || Auth::user()->isAccountant())
                    <form method="POST" action="{{ route('messages.acknowledge', $message->id) }}" class="d-inline">
                        @csrf
                        <x-ui.button type="submit" variant="outline-success" size="sm" icon="bi bi-check2-circle">Acknowledge</x-ui.button>
                    </form>
                @endif
            </div>
        @endif
    </x-slot:footer>
</x-ui.card>
@endsection
