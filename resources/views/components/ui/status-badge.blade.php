{{--
    x-ui.status-badge — semantic status pill.
    Replaces the colour-only `badge bg-*` markup (§4.4/§4.5): every badge renders
    an icon plus a text label, so status remains readable without colour.

    Props
      label (string)        visible text (required)
      tone  (string)        success|warning|danger|info|primary|neutral
                            — see the status vocabularies in the plan §5.2
      icon  (string|null)   bootstrap-icons class. When omitted a sensible
                            default per tone is used.
      title (string|null)   tooltip for longer explanations
      solid (bool)          filled brand variant (uses --primary-color)

    The tone classes map onto the --tone-* design tokens, never raw Bootstrap
    `bg-*` colours.
--}}
@props([
    'label',
    'tone' => 'neutral',
    'icon' => null,
    'title' => null,
    'solid' => false,
])

@php
    $toneIcons = [
        'success' => 'bi bi-check-circle-fill',
        'warning' => 'bi bi-exclamation-triangle-fill',
        'danger'  => 'bi bi-x-octagon-fill',
        'info'    => 'bi bi-info-circle-fill',
        'primary' => 'bi bi-dot',
        'neutral' => 'bi bi-dash-circle',
    ];

    $iconClass = $icon ?? ($toneIcons[$tone] ?? $toneIcons['neutral']);
    $classes = ['ui-badge', 'ui-badge--'.$tone];

    if ($solid) {
        $classes[] = 'ui-badge--solid';
    }
@endphp

<span {{ $attributes->class($classes) }} @if($title) title="{{ $title }}" @endif>
    <i class="{{ $iconClass }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</span>
