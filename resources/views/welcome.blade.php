@extends('layouts.app')

@section('title', 'Comércio local')

@section('content')
    <h1>Encontre lojas do comércio local</h1>
    <p>Conheça as lojas da sua região e os produtos que elas oferecem.</p>

    <nav aria-label="Tipo de busca">
        <a href="{{ route('home', \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if (! $productSearch) aria-current="page" @endif>Lojas</a>
        <a href="{{ route('home', ['tipo' => 'produtos'] + \Illuminate\Support\Arr::only($filters, ['q', 'cidade', 'estado'])) }}" @if ($productSearch) aria-current="page" @endif>Produtos</a>
    </nav>
    @include('partials.public-search', ['searchUrl' => route('home')])

    @if ($productSearch)
        <h2>Produtos encontrados ({{ $products->total() }})</h2>
        @forelse ($products as $product)
            @include('products.partials.public-card', ['product' => $product, 'store' => $product->store, 'showStore' => true])
        @empty
            <p>Nenhum produto encontrado com os filtros informados.</p>
        @endforelse
        @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
    @else
        <h2>Lojas encontradas ({{ $stores->total() }})</h2>
        @forelse ($stores as $store)
            <article class="catalog-card">
                @include('stores.partials.presentation', ['store' => $store])
                <h3><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a></h3>
                <p>{{ \Illuminate\Support\Str::limit($store->description, 160) }}</p>
                <p>{{ $store->city }} / {{ $store->state }}</p>
                <p>{{ $store->active_products_count }} {{ $store->active_products_count === 1 ? 'produto ativo' : 'produtos ativos' }}</p>
                <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">Ver loja</a>
            </article>
        @empty
            <p>{{ $filters ? 'Nenhuma loja encontrada com os filtros informados.' : 'Nenhuma loja disponível no momento.' }}</p>
        @endforelse

        @include('partials.pagination', ['paginator' => $stores, 'label' => 'lojas'])
    @endif
@endsection
