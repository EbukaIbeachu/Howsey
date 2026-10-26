{{--
    x-ui.form.input — text-like field with label, inline error and hint.
    Replaces the repeated `form-label` + `is-invalid` + `@error` + `old()` block.

    Props
      name        (string)        field name (required) — also drives old() and @error
      label       (string|null)   label text; omit for a bare input
      id          (string|null)   id / for target. Defaults to $name, which is the
                                  convention the existing templates already use.
      type        (string)        text|email|number|password|date|… — default "text"
      value       (string|null)   initial value; old($name) always takes precedence
      required    (bool)          adds the required attribute and an asterisk
      placeholder (string|null)
      hint        (string|null)   help text rendered under the input

    Any other attribute (min, max, maxlength, step, autocomplete, readonly, …) is
    passed straight through to the <input>.

    Contract (§5.4): the rendered <input> keeps the caller's name, id, value,
    old() fallback and validation attributes — this is a presentational wrapper.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ old($name, $value) }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($required) required @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class(['ui-input']) }}
    >

    @if($hint)
        <span class="ui-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="ui-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
