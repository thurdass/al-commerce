@props(['name', 'label', 'hint' => null])
<x-field :name="$name" :label="$label" :hint="$hint">
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes }} @if ($errors->has($name)) aria-invalid="true" @endif @if ($hint || $errors->has($name)) aria-describedby="{{ trim(($hint ? $name.'-hint ' : '').($errors->has($name) ? $name.'-error' : '')) }}" @endif>{{ $slot }}</select>
</x-field>
