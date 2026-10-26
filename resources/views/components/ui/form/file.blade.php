{{--
    x-ui.form.file — file upload field with label, inline error and hint.

    Props
      name     (string)        field name (required). Keep the existing name —
                               controllers read it directly from the request.
      label    (string|null)
      id       (string|null)   defaults to $name
      accept   (string|null)   e.g. "image/*,.pdf"
      multiple (bool)
      required (bool)
      hint     (string|null)   e.g. "Supported formats: JPG, PNG. Max size: 4MB."

    No value is ever rendered — browsers do not allow file inputs to be
    repopulated, and `old()` does not apply.

    Extra attributes are passed through to the <input>.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'accept' => null,
    'multiple' => false,
    'required' => false,
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
        type="file"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($multiple) multiple @endif
        @if($required) required @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class(['ui-file']) }}
    >

    @if($hint)
        <span class="ui-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="ui-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
