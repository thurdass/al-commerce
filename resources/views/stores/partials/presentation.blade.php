@if ($store->logo)
    <img class="catalog-image catalog-image--logo" src="{{ route('public.stores.image', ['store' => $store->slug]) }}" alt="{{ $store->name }}" width="720" height="540" loading="lazy">
@elseif ($store->presentationProduct?->coverImage)
    <img class="catalog-image catalog-image--photo" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $store->presentationProduct->slug, 'image' => $store->presentationProduct->coverImage->id]) }}" alt="{{ $store->presentationProduct->name }} — {{ $store->name }}" width="720" height="540" loading="lazy">
@else
    <x-image-placeholder label="Loja sem imagem" />
@endif
