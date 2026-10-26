{{--
    x-ui.form.checkbox — single checkbox with an inline label.

    Props
      name    (string)      field name (required)
      label   (string)      visible AND accessible label (required — the label
                            is the only accessible name for this control)
      value   (string)      value submitted when checked — default "1"
      checked (bool)        default state; old($name) takes precedence on a
                            re-render after validation, matching the existing
                            `old('field', $model->field)` convention
      id      (string|null) defaults to $name
      hint    (string|null)

    Not a toggle switch and not a group — use one component per checkbox.

    Contract: name, id, value and old() repopulation are unchanged.
--}}
@props([
    'name',
    'label',
    'value' => '1',
    'checked' => false,
    'id' => null,
    'hint' => null,
])

@php
    $fieldId = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div class="ui-field">
    <div class="ui-check">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            value="{{ $value }}"
            @checked(old($name, $checked))
            @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes->class(['ui-check__input']) }}
        >
        <label for="{{ $fieldId }}" class="ui-check__label">{{ $label }}</label>
    </div>

    @if($hint)
        <span class="ui-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="ui-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
