{{--
    x-ui.card — content panel.
    Replaces the hand-built `.card` + `.card-header` blocks duplicated across the app.

    Props
      title    (string|null)  header title text
      subtitle (string|null)  small line under the title
      icon     (string|null)  bootstrap-icons class, e.g. "bi bi-building"
      flush    (bool)         remove body padding (use when the body is a table) — default false
      tag      (string)       wrapper element name — default "div"

    Slots
      $actions  header right-hand controls
      $slot     card body
      $footer   optional footer row

    Any extra attribute (id, class, data-*) is passed through to the wrapper element.
--}}
@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'flush' => false,
    'tag' => 'div',
])

<{{ $tag }} {{ $attributes->class(['ui-card']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="ui-card__header">
            <div class="ui-card__heading">
                @if($title)
                    <h2 class="ui-card__title">
                        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
                        {{ $title }}
                    </h2>
                @endif
                @if($subtitle)
                    <p class="ui-card__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="ui-card__actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="ui-card__body @if($flush) ui-card__body--flush @endif">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="ui-card__footer">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
