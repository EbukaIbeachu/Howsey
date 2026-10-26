{{--
    x-ui.form.textarea — multi-line field with label, inline error and hint.

    Props
      name        (string)        field name (required)
      label       (string|null)
      id          (string|null)   defaults to $name
      value       (string|null)   initial content; old($name) always wins
      rows        (int)           default 4
      required    (bool)
      placeholder (string|null)
      hint        (string|null)

    Extra attributes are passed through to the <textarea>.

    Contract: name, id, old() fallback and validation attributes are unchanged.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'placeholder' => null,
    'hint' => null,
])

@php
    $fieldId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div class="ui-field">
    @if($label)
        <label for="{{ $fieldId }}" class="ui-label">
            {{ $label }}@if($required)<span class="ui-label__required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $fieldId }}"
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class(['ui-textarea']) }}>{{ old($name, $value) }}</textarea>

    @if($hint)
        <span class="ui-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="ui-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
