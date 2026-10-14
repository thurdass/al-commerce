<article class="catalog-card product-card">
    <a class="card-media" href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}" aria-label="Ver {{ $product->name }}">
        @if ($product->coverImage)
            <img class="catalog-image" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $product->coverImage->id]) }}" alt="{{ $product->name }}" width="480" height="360" loading="lazy">
        @else
            <x-image-placeholder />
        @endif
    </a>
    <div class="card-body">
        <p class="card-category">{{ $product->category?->name ?? 'Sem categoria' }}</p>
        <div class="card-title-row">
            <h3><a href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}">{{ $product->name }}</a></h3>
            <p class="card-price">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
        </div>
        <x-availability :status="$product->availability_status" />
        @if ($showStore)
            <p class="card-store"><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a> — {{ $store->city }} / {{ $store->state }}</p>
        @endif
    </div>
    <div class="card-footer"><a href="{{ route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]) }}">Ver produto <span aria-hidden="true">↗</span></a></div>
</article>
