{{--
    x-ui.icon-button — icon-only control.
    `label` is REQUIRED by the signature (§5.4): it is rendered as both
    aria-label and title, so an icon-only control can never ship unlabelled.
    This is the fix for the accessibility gap in §4.5.

    Props
      label   (string)        accessible name — also shown as the tooltip (required)
      icon    (string)        bootstrap-icons class (required)
      href    (string|null)   renders an <a> instead of a <button>
      type    (string)        button|submit|reset — only used when href is empty
      variant (string)        default|danger|success|warning|ghost
                              — default "default"
      size    (string|null)   null|sm|lg

    Any other attribute is passed through (e.g. data-bs-toggle for modals).
--}}
@props([
    'label',
    'icon',
    'href' => null,
    'type' => 'button',
    'variant' => 'default',
    'size' => null,
])

@php
    $classes = ['ui-icon-btn'];

    if ($variant !== 'default') {
        $classes[] = 'ui-icon-btn--'.$variant;
    }

    if ($size) {
        $classes[] = 'ui-icon-btn--'.$size;
    }
@endphp

@if($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </button>
@endif
