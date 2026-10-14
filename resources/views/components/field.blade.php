@props(['name', 'label', 'hint' => null, 'id' => null])
<div {{ $attributes->class(['field', 'field-invalid' => $errors->has($name)]) }}>
    <label for="{{ $id ?? $name }}">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="field-hint" id="{{ $id ?? $name }}-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field-error" id="{{ $id ?? $name }}-error">{{ $message }}</p>
    @enderror
</div>
