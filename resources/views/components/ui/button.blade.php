{{--
    x-ui.button — one button vocabulary for the whole app.
    Replaces the varied `btn-*` combinations catalogued in the audit.

    Props
      variant   (string)     primary|secondary|success|warning|danger|info|
                             ghost|outline-primary|outline-secondary|
                             outline-danger|outline-warning
                             — default "primary"
      size      (string|null) null|sm|lg
      icon      (string|null) bootstrap-icons class
      iconRight (bool)        place the icon after the label
      href      (string|null) renders an <a> instead of a <button>
      type      (string)      button|submit|reset — only used when href is empty
      block     (bool)        full-width

    Slot
      $slot  button label

    Contract: when used as a submit control the caller's type/name/value are
    rendered untouched, so existing forms keep submitting the same payload.
--}}
@props([
    'variant' => 'primary',
    'size' => null,
    'icon' => null,
    'iconRight' => false,
    'href' => null,
    'type' => 'button',
    'block' => false,
])

@php
    $classes = ['ui-btn', 'ui-btn--'.$variant];

    if ($size) {
        $classes[] = 'ui-btn--'.$size;
    }

    if ($block) {
        $classes[] = 'ui-btn--block';
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if($icon && ! $iconRight)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
        @if($icon && $iconRight)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if($icon && ! $iconRight)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
        @if($icon && $iconRight)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
    </button>
@endif
