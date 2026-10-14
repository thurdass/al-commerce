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
    @if ($showStore)
        <p><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a> — {{ $store->city }} / {{ $store->state }}</p>
    @endif
    <a href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}">Ver produto</a>
</article>
