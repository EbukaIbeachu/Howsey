{{--
    x-ui.page-header — page title block with optional breadcrumbs and actions.
    Replaces the 5 competing heading conventions found in the audit (§4.3).

    Props
      title       (string)       page title (required)
      subtitle    (string|null)  one-line description under the title
      icon        (string|null)  bootstrap-icons class shown before the title
      id          (string|null)  id placed on the <h1>. Several templates and
                                 tests target these ids (e.g. "tenants-header"),
                                 so pass them through unchanged.
      breadcrumbs (array|null)   forwarded to x-ui.breadcrumbs

    Slots
      $actions  right-aligned controls (buttons)
      $slot     extra content rendered inside the titles column
--}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'id' => null,
    'breadcrumbs' => null,
])

<header {{ $attributes->class(['ui-page-header']) }}>
    <div class="ui-page-header__titles">
        @if($breadcrumbs)
            <x-ui.breadcrumbs :items="$breadcrumbs" />
        @endif

        <h1 class="ui-page-header__title" @if($id) id="{{ $id }}" @endif>
            @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
            {{ $title }}
        </h1>

        @if($subtitle)
            <p class="ui-page-header__subtitle">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>

    @isset($actions)
        <div class="ui-page-header__actions">{{ $actions }}</div>
    @endisset
</header>
