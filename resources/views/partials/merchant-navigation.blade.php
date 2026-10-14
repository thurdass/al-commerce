<nav class="merchant-navigation" aria-label="Área do lojista">
    <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Visão geral</a>
    @if ($hasStore ?? true)
        <a href="{{ route('products.index') }}" @if (request()->routeIs('products.*')) aria-current="page" @endif>Meus produtos</a>
    @endif
</nav>
