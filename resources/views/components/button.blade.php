@props(['href' => null, 'variant' => 'primary', 'type' => 'submit'])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['button', 'button-'.$variant]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['button', 'button-'.$variant]) }}>{{ $slot }}</button>
@endif
