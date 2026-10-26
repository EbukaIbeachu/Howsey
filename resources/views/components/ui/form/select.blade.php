{{--
    x-ui.form.select — select field with label, inline error and hint.

    Props
      name        (string)        field name (required)
      label       (string|null)
      id          (string|null)   defaults to $name
      options     (array|null)    [value => label] convenience list. Omit and use
                                  the slot when the options need optgroups or
                                  data attributes.
      value       (string|int|null) currently selected value; old($name) wins
      placeholder (string|null)   renders a leading empty option
      required    (bool)
      hint        (string|null)

    Slots
      $slot  manual <option> markup (used when `options` is not supplied)

    Contract: name, id, old() and any extra attribute are rendered unchanged.
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'options' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
])

@php
    $fieldId = $id ?? $name;
    $hasError = $errors->has($name);
    $current = old($name, $value);
    $current = $current === null ? null : (string) $current;
@endphp

<div class="ui-field">
    @if($label)
        <label for="{{ $fieldId }}" class="ui-label">
            {{ $label }}@if($required)<span class="ui-label__required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if($required) required @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->class(['ui-select']) }}>
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if($options)
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($current !== null && (string) $optionValue === $current)>{{ $optionLabel }}</option>
            @endforeach
        @endif

        {{ $slot }}
    </select>

    @if($hint)
        <span class="ui-hint">{{ $hint }}</span>
    @endif

    @error($name)
        <span class="ui-error" id="{{ $fieldId }}-error" role="alert">{{ $message }}</span>
    @enderror
</div>
