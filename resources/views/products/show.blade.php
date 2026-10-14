@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <a class="breadcrumb" href="{{ route('public.stores.show', ['store' => $store->slug]) }}"><span aria-hidden="true">←</span> Voltar à loja {{ $store->name }}</a>
    <div class="product-layout">
        <section class="product-gallery" data-product-gallery aria-label="Imagens de {{ $product->name }}">
            @if ($product->images->isNotEmpty())
                <div class="gallery-main">
                    <img data-gallery-main src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $product->images->first()->id]) }}" alt="Imagem principal de {{ $product->name }}" width="720" height="720" fetchpriority="high">
                </div>
                @if ($product->images->count() > 1)
                    <div class="gallery-thumbnails">
                        @foreach ($product->images as $image)
                            <button type="button" class="gallery-thumbnail" data-gallery-thumbnail data-src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]) }}" data-alt="Imagem {{ $loop->iteration }} de {{ $product->name }}" aria-label="Ver imagem {{ $loop->iteration }} de {{ $product->name }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                <img src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]) }}" alt="" width="120" height="120" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                    <noscript>
                        @foreach ($product->images->skip(1) as $image)
                            <img class="catalog-image" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]) }}" alt="Outra imagem de {{ $product->name }}" width="720" loading="lazy">
                        @endforeach
                    </noscript>
                @endif
            @else
                <div class="gallery-main"><x-image-placeholder /></div>
            @endif
        </section>
        <section class="product-info" aria-labelledby="product-heading">
            <p class="eyebrow">{{ $product->category?->name ?? 'Sem categoria' }}</p>
            <h1 id="product-heading">{{ $product->name }}</h1>
            <p class="product-price"><span class="visually-hidden">Preço: </span>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
            <p class="card-store">Vendido por <a class="text-link" href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a> — {{ $store->city }} / {{ $store->state }}</p>
            <dl class="product-details">
                <div><dt>Disponibilidade</dt><dd><x-availability :status="$product->availability_status" /></dd></div>
                @if ($product->stock_quantity !== null)
                    <div><dt>Quantidade em estoque</dt><dd>{{ $product->stock_quantity }}</dd></div>
                @endif
            </dl>
            <div class="product-contact">
                @include('partials.whatsapp-contact', ['whatsappUrl' => $whatsappUrl])
                @if ($whatsappUrl)
                    <p class="contact-note">Converse com a loja para combinar os detalhes da compra.</p>
                @endif
            </div>
            <div><h2 class="eyebrow">Sobre o produto</h2><p class="product-description">{{ $product->description }}</p></div>
        </section>
    </div>
@endsection
