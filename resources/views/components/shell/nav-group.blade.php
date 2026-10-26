{{--
    Sidebar navigation group (frontend redesign Phase 4).

    Wraps a set of <li class="nav-item"> entries under a section heading.

    Props:
      label  (required) — the section heading text.
      icon   (optional) — a Bootstrap Icons class for the heading.

    The group renders only when at least one child survived its permission
    gate. Blade renders the slot to a string before this component runs, so a
    group whose every item was filtered out by @permission / @can / @if
    contains nothing but whitespace — hasActualContent() detects that and the
    heading is suppressed. This is why the child conditions can stay exactly
    as they were: no group ever shows an empty heading, and no condition has
    to be duplicated on the wrapper.
--}}
@props([
    'label',
    'icon' => null,
])

@if ($slot->hasActualContent())
    <div class="nav-group">
        <div class="nav-group-title">
            @if ($icon)
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
            @endif
            <span>{{ $label }}</span>
        </div>
        <ul class="nav flex-column">
            {{ $slot }}
        </ul>
    </div>
@endif
