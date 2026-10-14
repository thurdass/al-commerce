@extends('layouts.app')

@section('title', $store->name)

@section('content')
    <a class="breadcrumb" href="{{ route('home') }}"><span aria-hidden="true">←</span> Voltar às lojas</a>
    <section class="store-hero" aria-labelledby="store-heading">
        <div class="store-hero-media">@include('stores.partials.presentation', ['store' => $store])</div>
        <div class="store-hero-copy">
            <p class="eyebrow">{{ $store->city }} / {{ $store->state }}</p>
            <h1 id="store-heading">{{ $store->name }}</h1>
            <p class="store-description">{{ $store->description }}</p>
            @include('partials.whatsapp-contact', ['whatsappUrl' => $whatsappUrl])
        </div>
    </section>
    <section aria-labelledby="products-heading">
        <div class="section-heading">
            <div><p class="eyebrow">Explore a vitrine</p><h2 id="products-heading">Produtos</h2></div>
            <p class="muted">{{ $products->total() }} {{ $products->total() === 1 ? 'produto' : 'produtos' }}</p>
        </div>
        <div class="store-search">
            @include('partials.public-search', ['searchUrl' => route('public.stores.show', ['store' => $store->slug]), 'compact' => true, 'searchFormId' => 'store-search'])
        </div>
        <div class="catalog-toolbar">
            @include('partials.catalog-filters', ['searchUrl' => route('public.stores.show', ['store' => $store->slug]), 'searchFormId' => 'store-search'])
        </div>
        <div class="catalog-grid">
            @forelse ($products as $product)
                @include('products.partials.public-card', ['product' => $product, 'store' => $store, 'showStore' => false])
            @empty
                <x-empty-state>{{ $filters ? 'Nenhum produto desta loja encontrado com os filtros informados.' : 'Esta loja ainda não tem produtos disponíveis para exibição.' }}</x-empty-state>
            @endforelse
        </div>
        @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
    </section>
@endsection
