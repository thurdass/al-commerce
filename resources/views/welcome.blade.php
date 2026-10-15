@extends('layouts.app')

@section('title', 'Comércio local')
@section('body-class', 'public-home')

@section('header-search')
    <nav class="catalog-navigation" aria-label="Tipo de busca">
        <a href="{{ route('home', \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if (! $productSearch) aria-current="page" @endif>Lojas</a>
        <a href="{{ route('home', ['tipo' => 'produtos'] + \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if ($productSearch) aria-current="page" @endif>Produtos</a>
    </nav>
    @include('partials.public-search', ['searchUrl' => route('home'), 'compact' => true])
@endsection

@section('content')
    <section class="catalog-hero" aria-labelledby="home-heading">
        <div class="hero-heading">
            <p class="eyebrow">Descubra o comércio local</p>
            <h1 id="home-heading" class="hero-title">Boas descobertas.<br><em>Perto de você.</em></h1>
        </div>
        <div class="hero-copy">
            <p>Encontre produtos, descubra lojas e compre direto com o vendedor.</p>
            <a class="text-link" href="#results-heading">{{ $productSearch ? 'Explorar produtos' : 'Explorar lojas' }} <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    @php
        $featuredStores = ! $productSearch && ! $filters && $stores->onFirstPage() && $stores->count() >= 6
            ? $stores->getCollection()->take(3)
            : collect();
        $listedStores = $productSearch ? collect() : $stores->getCollection()->skip($featuredStores->count());
    @endphp

    @if ($featuredStores->isNotEmpty())
        <section class="featured-stores" aria-labelledby="featured-heading">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Conheça de perto</p>
                    <h2 id="featured-heading">Lojas em destaque</h2>
                </div>
                <a class="text-link" href="#results-heading">Ver mais lojas <span aria-hidden="true">↗</span></a>
            </div>
            <div class="catalog-grid store-grid">
                @foreach ($featuredStores as $store)
                    @include('stores.partials.public-card', ['store' => $store])
                @endforeach
            </div>
        </section>
    @endif

    <section aria-labelledby="results-heading">
        <div class="catalog-toolbar">
            <div class="catalog-results-heading">
                <h2 id="results-heading">{{ $productSearch ? 'Produtos encontrados' : ($filters ? 'Lojas encontradas' : ($featuredStores->isNotEmpty() ? 'Mais lojas' : 'Todas as lojas')) }} ({{ $productSearch ? $products->total() : $stores->total() - $featuredStores->count() }})</h2>
            </div>
            @include('partials.catalog-filters', ['searchUrl' => route('home')])
        </div>
        @if ($productSearch)
            <div class="catalog-grid">
                @forelse ($products as $product)
                    @include('products.partials.public-card', ['product' => $product, 'store' => $product->store, 'showStore' => true])
                @empty
                    <x-empty-state>Nenhum produto encontrado com os filtros informados.</x-empty-state>
                @endforelse
            </div>
            @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
        @else
            <div class="catalog-grid store-grid">
                @forelse ($listedStores as $store)
                    @include('stores.partials.public-card', ['store' => $store])
                @empty
                    <x-empty-state>{{ $filters ? 'Nenhuma loja encontrada com os filtros informados.' : 'Nenhuma loja disponível no momento.' }}</x-empty-state>
                @endforelse
            </div>
            @include('partials.pagination', ['paginator' => $stores, 'label' => 'lojas'])
        @endif
    </section>
@endsection
