@props(['label' => 'Produto sem imagem'])
<div {{ $attributes->class(['image-placeholder']) }} role="img" aria-label="{{ $label }}">
    <svg aria-hidden="true" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1"><rect x="6" y="7" width="28" height="26" rx="2"/><path d="m7 27 8-9 7 7 5-5 6 7"/><circle cx="26" cy="14" r="2"/></svg>
    <span>Sem imagem</span>
</div>
