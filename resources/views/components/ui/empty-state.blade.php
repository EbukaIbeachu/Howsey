{{--
    x-ui.empty-state — "no records" / "nothing here yet" block.
    Replaces the ad-hoc empty markup scattered across index pages.

    Props
      icon   (string|null)  bootstrap-icons class — default "bi bi-inbox"
      title  (string)       short headline
      text   (string|null)  one sentence explaining the next step
      inline (bool)         compact variant for use inside an empty table cell

    Slots
      $actions  optional call-to-action buttons
      $slot     any extra content
--}}
@props([
    'icon' => 'bi bi-inbox',
    'title' => 'Nothing here yet',
    'text' => null,
    'inline' => false,
])

<div {{ $attributes->class(['ui-empty', 'ui-empty--inline' => $inline]) }}>
    <span class="ui-empty__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    <p class="ui-empty__title">{{ $title }}</p>

    @if($text)
        <p class="ui-empty__text">{{ $text }}</p>
    @endif

    @isset($actions)
        <div class="ui-empty__actions">{{ $actions }}</div>
    @endisset

    {{ $slot }}
</div>
