@extends('layouts.app')

@section('title', $store->name)

@section('content')
    <a href="{{ route('home') }}">Voltar às lojas</a>
    <h1>{{ $store->name }}</h1>
    @include('stores.partials.presentation', ['store' => $store])
    <p>{{ $store->description }}</p>
    <p>{{ $store->city }} / {{ $store->state }}</p>

    <h2>Produtos</h2>
    @forelse ($products as $product)
        <article class="catalog-card">
            @if ($product->coverImage)
                <img class="catalog-image" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $product->coverImage->id]) }}" alt="{{ $product->name }}" width="180" loading="lazy">
            @else
                <div class="image-placeholder" role="img" aria-label="Produto sem imagem">Sem imagem</div>
            @endif
            <h3><a href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}">{{ $product->name }}</a></h3>
            <p>{{ $product->category?->name ?? 'Sem categoria' }}</p>
            <p>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
            <p>{{ $product->availability_status->label() }}</p>
            <a href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}">Ver produto</a>
        </article>
    @empty
        <p>Esta loja ainda não tem produtos disponíveis para exibição.</p>
    @endforelse

    @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
@endsection
