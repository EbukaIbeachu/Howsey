{{--
    x-ui.modal — Bootstrap 5 modal with tokenised chrome.
    Replaces the repeated modal scaffolding found across the templates.

    Props
      id         (string)       required — used by data-bs-target on the trigger
      title      (string)       header title (required)
      size       (string|null)  sm|lg|xl
      scrollable (bool)         long bodies scroll inside the dialog
      centered   (bool)         vertically centre the dialog — default true

    Slots
      $slot    modal body
      $footer  modal footer (buttons)

    Bootstrap's modal JS is already loaded by the app shell, so no additional
    JavaScript is required.
--}}
@props([
    'id',
    'title',
    'size' => null,
    'scrollable' => false,
    'centered' => true,
])

@php
    $dialogClasses = ['modal-dialog'];

    if ($size) {
        $dialogClasses[] = 'modal-'.$size;
    }

    if ($scrollable) {
        $dialogClasses[] = 'modal-dialog-scrollable';
    }

    if ($centered) {
        $dialogClasses[] = 'modal-dialog-centered';
    }
@endphp

<div {{ $attributes->class(['modal', 'fade', 'ui-modal'])->merge([
    'id' => $id,
    'tabindex' => '-1',
    'aria-labelledby' => $id.'-label',
    'aria-hidden' => 'true',
]) }}>
    <div class="{{ implode(' ', $dialogClasses) }}">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="{{ $id }}-label">{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
