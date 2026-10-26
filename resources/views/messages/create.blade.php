@extends('layouts.app')

@section('content')
<x-ui.page-header :title="isset($message) ? 'Reply' : 'Compose Message'">
    <x-slot:actions>
        <x-ui.button variant="outline-secondary" size="sm" icon="bi bi-arrow-left" :href="route('messages.index')">Back</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@php
    if (Auth::user()->isSuperAdmin()) {
        $recipientHint = 'You can message admins of any workspace.';
    } elseif (Auth::user()->isAdmin()) {
        $recipientHint = 'You can message managers, accountants, and tenants in your company.';
    } elseif (Auth::user()->isManager()) {
        $recipientHint = 'You can message tenants of buildings assigned to you.';
    } else {
        $recipientHint = 'You can reply to the sender of this message.';
    }
@endphp

<x-ui.card>
    @if($recipients->isEmpty())
        <x-ui.alert tone="warning">There are no users you can message at this time.</x-ui.alert>
    @else
        <form method="POST" action="{{ route('messages.store') }}">
            @csrf
            @if(isset($message))
                <input type="hidden" name="reply_to" value="{{ $message->id }}">
            @endif

            <x-ui.form.select name="recipient_id" label="Recipient" required
                placeholder="-- Select a recipient --"
                :hint="$recipientHint">
                @foreach($recipients as $recipient)
                    <option value="{{ $recipient->id }}" @selected((isset($message) && $message->sender_id == $recipient->id) || old('recipient_id') == $recipient->id)>
                        {{ $recipient->name }}
                        ({{ optional($recipient->role)->name }})
                        @if($recipient->company)
                            — {{ $recipient->company->name }}
                        @endif
                    </option>
                @endforeach
            </x-ui.form.select>

            <x-ui.form.textarea name="body" label="Message" :rows="8" required
                placeholder="Type your message here..." />

            <div class="d-flex justify-content-end gap-2">
                <x-ui.button variant="outline-secondary" :href="route('messages.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="bi bi-send">Send</x-ui.button>
            </div>
        </form>
    @endif
</x-ui.card>
@endsection
