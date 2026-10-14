<form method="GET" action="{{ $searchUrl }}" role="search" aria-label="{{ $productSearch ? 'Buscar produtos' : 'Buscar lojas' }}" @if ($compact ?? false) id="{{ $searchFormId ?? 'catalog-search' }}" class="catalog-search" @endif>
    @if ($productSearch && ! $withinStore)
        <input type="hidden" name="tipo" value="produtos">
    @endif
    <div class="search-field">
        <label for="q">{{ $productSearch ? 'Nome do produto' : 'Nome da loja' }}</label>
        <input id="q" name="q" type="search" maxlength="255" value="{{ $filters['q'] ?? '' }}" @if ($compact ?? false) placeholder="{{ $productSearch ? 'Buscar produtos' : 'Buscar lojas' }}" @endif>
    </div>

    @if ($compact ?? false)
        <button type="submit">Buscar</button>
    @else
        @include('partials.public-search-filters')
        <div class="actions">
            <button type="submit">Buscar</button>
            <a href="{{ $productSearch && ! $withinStore ? route('home', ['tipo' => 'produtos']) : $searchUrl }}">Limpar filtros</a>
        </div>
    @endif
</form>
