{{--
    x-ui.breadcrumbs — navigation trail (new in this redesign; the app had none).

    Props
      items (array)  ordered list of ['label' => string, 'url' => string|null].
                     The last entry is always rendered as the current page and
                     is never linked, regardless of whether it has a url.
                     A plain string entry is also accepted and rendered as text.

    Renders nothing when `items` is empty.
--}}
@props(['items' => []])

@php
    $trail = array_values($items);
    $lastIndex = count($trail) - 1;
@endphp

@if($lastIndex >= 0)
    <nav aria-label="Breadcrumb">
        <ol {{ $attributes->class(['ui-breadcrumbs']) }}>
            @foreach($trail as $index => $item)
                @php
                    $isCurrent = $index === $lastIndex;
                    $label = is_array($item) ? ($item['label'] ?? '') : $item;
                    $url = $isCurrent ? null : (is_array($item) ? ($item['url'] ?? null) : null);
                @endphp
                <li class="ui-breadcrumbs__item @if($isCurrent) ui-breadcrumbs__item--current @endif">
                    @if($url)
                        <a href="{{ $url }}">{{ $label }}</a>
                    @else
                        <span @if($isCurrent) aria-current="page" @endif>{{ $label }}</span>
                    @endif
                    @unless($isCurrent)
                        <span class="ui-breadcrumbs__sep" aria-hidden="true">/</span>
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
