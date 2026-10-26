{{--
    x-ui.alert — flash / inline message.
    Replaces the flash markup duplicated in ~10 templates (§4.2).

    Props
      tone        (string)       success|warning|danger|info|neutral
                                 — default "info"
      title       (string|null)  optional bold first line
      dismissible (bool)         adds a close button. Bootstrap's alert
                                 dismissing is already wired by app-shell.js.
      icon        (string|null)  overrides the tone default icon

    Slot
      $slot  message body

    role is "alert" for danger/warning (announced immediately) and "status" for
    informational tones, so routine confirmations do not interrupt a screen
    reader.
--}}
@props([
    'tone' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => null,
])

@php
    $toneIcons = [
        'success' => 'bi bi-check-circle-fill',
        'warning' => 'bi bi-exclamation-triangle-fill',
        'danger'  => 'bi bi-exclamation-octagon-fill',
        'info'    => 'bi bi-info-circle-fill',
        'neutral' => 'bi bi-bell-fill',
    ];

    $iconClass = $icon ?? ($toneIcons[$tone] ?? $toneIcons['info']);
    $role = in_array($tone, ['danger', 'warning'], true) ? 'alert' : 'status';
@endphp

<div {{ $attributes->class(['ui-alert', 'ui-alert--'.$tone]) }} role="{{ $role }}">
    <i class="{{ $iconClass }} ui-alert__icon" aria-hidden="true"></i>

    <div class="ui-alert__body">
        @if($title)
            <p class="ui-alert__title">{{ $title }}</p>
        @endif
        <div class="ui-alert__text">{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button type="button" class="ui-alert__close" data-bs-dismiss="alert" aria-label="Dismiss this message">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    @endif
</div>
