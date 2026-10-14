<details class="catalog-filter-panel" data-responsive-filters open>
    <summary>Filtros e ordenação</summary>
    <div class="catalog-filters" role="group" aria-label="Filtros de {{ $productSearch ? 'produtos' : 'lojas' }}">
        @include('partials.public-search-filters', ['formId' => $searchFormId ?? 'catalog-search', 'compact' => true])
        <div class="filter-actions">
            <button type="submit" form="{{ $searchFormId ?? 'catalog-search' }}">Aplicar</button>
            <a href="{{ $productSearch && ! $withinStore ? route('home', ['tipo' => 'produtos']) : $searchUrl }}">Limpar filtros</a>
        </div>
    </div>
</details>
