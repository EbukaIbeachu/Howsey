{{--
    Notification bell + dropdown (frontend redesign Phase 4).

    Previously this markup was copy-pasted twice in layouts/app.blade.php — once
    for the desktop header and once for the mobile bar — so the two copies could
    drift apart. It now lives here and both call sites render this component.

    Props:
      count         (int)        — unread notification count. Drives the badge
                                   and the "Mark all as read" button.
      notifications (Collection) — unread notifications to list. Each is expected
                                   to expose ->id, ->data['title'],
                                   ->data['message'] and ->created_at.
      align         (string)     — dropdown alignment: 'end' (default) or 'start'.
      id            (optional)   — DOM id for the wrapper, when a page needs one.
--}}
@props([
    'count' => 0,
    'notifications' => null,
    'align' => 'end',
])

@php
    $notifications = $notifications ?? collect();
    $count = (int) $count;
@endphp

<div class="dropdown" @if (! empty($id)) id="{{ $id }}" @endif>
    <button
        class="btn btn-outline-secondary btn-sm position-relative notification-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        aria-haspopup="true"
        title="Notifications"
        aria-label="Notifications{{ $count > 0 ? ' (' . $count . ' unread)' : '' }}"
    >
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if ($count > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $count }}</span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-{{ $align }} p-0 notification-dropdown-menu">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-semibold">Notifications</span>
            @if ($count > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0">Mark all as read</button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.open', $notification->id) }}" class="dropdown-item px-3 py-2">
                <div class="fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                <div class="small text-muted notification-message">{{ $notification->data['message'] ?? 'A new item needs your attention.' }}</div>
                <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
            </a>
        @empty
            <div class="px-3 py-3 text-muted small">No unread notifications.</div>
        @endforelse
    </div>
</div>
