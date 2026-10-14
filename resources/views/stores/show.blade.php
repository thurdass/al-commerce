@extends('layouts.app')

@section('title', $store->name)

@section('content')
    <a href="{{ route('home') }}">Voltar às lojas</a>
    <h1>{{ $store->name }}</h1>
    @include('stores.partials.presentation', ['store' => $store])
    <p>{{ $store->description }}</p>
    <p>{{ $store->city }} / {{ $store->state }}</p>
    @include('partials.whatsapp-contact', ['whatsappUrl' => $whatsappUrl])

    <h2>Produtos</h2>
    @include('partials.public-search', ['searchUrl' => route('public.stores.show', ['store' => $store->slug])])
    @forelse ($products as $product)
        @include('products.partials.public-card', ['product' => $product, 'store' => $store, 'showStore' => false])
    @empty
        <p>{{ $filters ? 'Nenhum produto desta loja encontrado com os filtros informados.' : 'Esta loja ainda não tem produtos disponíveis para exibição.' }}</p>
    @endforelse

    @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
@endsection
