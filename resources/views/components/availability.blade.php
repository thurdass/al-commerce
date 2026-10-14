@props(['status'])
<span {{ $attributes->class(['availability']) }} data-status="{{ $status->value }}">{{ $status->label() }}</span>
