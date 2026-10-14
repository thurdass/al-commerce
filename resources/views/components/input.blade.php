@props(['name', 'label', 'hint' => null, 'id' => null, 'type' => 'text'])
<x-field :name="$name" :label="$label" :hint="$hint" :id="$id">
    <input id="{{ $id ?? $name }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes }} @if ($errors->has($name)) aria-invalid="true" @endif @if ($hint || $errors->has($name)) aria-describedby="{{ trim(($hint ? ($id ?? $name).'-hint ' : '').($errors->has($name) ? ($id ?? $name).'-error' : '')) }}" @endif>
</x-field>
