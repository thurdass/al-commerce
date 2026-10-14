@extends('layouts.app')

@section('title', 'Comércio local')

@section('content')
    <h1>Encontre lojas do comércio local</h1>
    <p>Conheça as lojas da sua região e os produtos que elas oferecem.</p>

    @forelse ($stores as $store)
        <article class="catalog-card">
            @include('stores.partials.presentation', ['store' => $store])
            <h2><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a></h2>
            <p>{{ \Illuminate\Support\Str::limit($store->description, 160) }}</p>
            <p>{{ $store->city }} / {{ $store->state }}</p>
            <p>{{ $store->active_products_count }} {{ $store->active_products_count === 1 ? 'produto ativo' : 'produtos ativos' }}</p>
            <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">Ver loja</a>
        </article>
    @empty
        <p>Nenhuma loja disponível no momento.</p>
    @endforelse

    @include('partials.pagination', ['paginator' => $stores, 'label' => 'lojas'])
@endsection
