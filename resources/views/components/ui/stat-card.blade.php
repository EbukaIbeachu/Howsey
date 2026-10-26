{{--
    x-ui.stat-card — dashboard metric tile.
    Replaces the copy-pasted stat block in the three dashboards (§5.4).

    Props
      label (string)           metric name
      value (string|int)       the number itself
      icon  (string|null)      bootstrap-icons class
      tone  (string)           primary|success|warning|danger|info|neutral
      meta  (string|null)      supporting line under the value
      href  (string|null)      when set the tile renders as a link, so the
                               whole card becomes the click target

    Slot
      $slot  optional extra content rendered under the value

    The tone drives both the left accent bar and the icon chip, and the icon is
    aria-hidden, so the metric is never communicated by colour alone.
--}}
@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'primary',
    'meta' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->class(['ui-stat', 'ui-stat--'.$tone]) }}>
    <div>
        <p class="ui-stat__label">{{ $label }}</p>
        <p class="ui-stat__value">{{ $value }}</p>
        @if($meta)
            <p class="ui-stat__meta">{{ $meta }}</p>
        @endif
        {{ $slot }}
    </div>

    @if($icon)
        <span class="ui-stat__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    @endif
</{{ $tag }}>
