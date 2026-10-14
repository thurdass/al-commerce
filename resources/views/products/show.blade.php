@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">Voltar à loja {{ $store->name }}</a>
    <h1>{{ $product->name }}</h1>
    <p>Vendido por <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a> — {{ $store->city }} / {{ $store->state }}</p>

    @forelse ($product->images as $image)
        <figure>
            <img class="catalog-image" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]) }}" alt="{{ $loop->first ? 'Imagem principal' : 'Imagem '.$loop->iteration }} de {{ $product->name }}" width="{{ $loop->first ? 420 : 180 }}" @if (! $loop->first) loading="lazy" @endif>
            <figcaption>{{ $loop->first ? 'Imagem principal' : 'Imagem '.$loop->iteration }}</figcaption>
        </figure>
    @empty
        <div class="image-placeholder" role="img" aria-label="Produto sem imagem">Sem imagem</div>
    @endforelse

    <p>{{ $product->description }}</p>
    <dl>
        <dt>Categoria</dt>
        <dd>{{ $product->category?->name ?? 'Sem categoria' }}</dd>
        <dt>Preço</dt>
        <dd>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</dd>
        <dt>Disponibilidade</dt>
        <dd>{{ $product->availability_status->label() }}</dd>
        @if ($product->stock_quantity !== null)
            <dt>Quantidade em estoque</dt>
            <dd>{{ $product->stock_quantity }}</dd>
        @endif
    </dl>
@endsection
