@extends('layouts.app')

@section('title', 'Comércio local')
@section('body-class', 'public-home')

@push('styles')
    @include('partials.home-styles')
@endpush

@section('header-search')
    <nav class="catalog-navigation" aria-label="Tipo de busca">
        <a href="{{ route('home', \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if (! $productSearch) aria-current="page" @endif>Lojas</a>
        <a href="{{ route('home', ['tipo' => 'produtos'] + \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if ($productSearch) aria-current="page" @endif>Produtos</a>
    </nav>
    @include('partials.public-search', ['searchUrl' => route('home'), 'compact' => true])
@endsection

@section('content')
    <section class="catalog-hero" aria-labelledby="home-heading">
        <h1 id="home-heading">Encontre lojas do comércio local</h1>
        <p>Conheça as lojas da sua região e os produtos que elas oferecem.</p>
    </section>

    @if (! $productSearch && ! $filters && $stores->onFirstPage() && $stores->isNotEmpty())
        <section class="featured-stores" aria-labelledby="featured-heading">
            <h2 id="featured-heading">Lojas em destaque</h2>
            <div class="catalog-grid">
                @foreach ($stores->take(3) as $store)
                    @include('stores.partials.public-card', ['store' => $store])
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="results-heading">
        <div class="catalog-toolbar">
            <div class="catalog-results-heading">
                <h2 id="results-heading">{{ $productSearch ? 'Produtos encontrados' : ($filters ? 'Lojas encontradas' : 'Todas as lojas') }} ({{ $productSearch ? $products->total() : $stores->total() }})</h2>
            </div>
            <details class="catalog-filter-panel" open>
                <summary>Filtros e ordenação</summary>
                <div class="catalog-filters" role="group" aria-label="Filtros de {{ $productSearch ? 'produtos' : 'lojas' }}">
                    @include('partials.public-search-filters', ['formId' => 'catalog-search', 'compact' => true])
                    <div class="filter-actions">
                        <button type="submit" form="catalog-search">Aplicar</button>
                        <a href="{{ $productSearch ? route('home', ['tipo' => 'produtos']) : route('home') }}">Limpar filtros</a>
                    </div>
                </div>
            </details>
        </div>

        @if ($productSearch)
            <div class="catalog-grid">
                @forelse ($products as $product)
                    @include('products.partials.public-card', ['product' => $product, 'store' => $product->store, 'showStore' => true])
                @empty
                    <p class="catalog-empty">Nenhum produto encontrado com os filtros informados.</p>
                @endforelse
            </div>
            @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
        @else
            <div class="catalog-grid">
                @forelse ($stores as $store)
                    @include('stores.partials.public-card', ['store' => $store])
                @empty
                    <p class="catalog-empty">{{ $filters ? 'Nenhuma loja encontrada com os filtros informados.' : 'Nenhuma loja disponível no momento.' }}</p>
                @endforelse
            </div>
            @include('partials.pagination', ['paginator' => $stores, 'label' => 'lojas'])
        @endif
    </section>
    <script>
        (() => {
            const filters = document.querySelector('.catalog-filter-panel');
            const desktop = window.matchMedia('(min-width: 640px)');
            const syncFilters = () => { filters.open = desktop.matches; };

            syncFilters();
            desktop.addEventListener('change', syncFilters);
        })();
    </script>
@endsection
