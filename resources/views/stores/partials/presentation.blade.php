@if ($store->presentationProduct?->coverImage)
    <img class="catalog-image" src="{{ route('public.products.images.show', ['store' => $store->slug, 'product' => $store->presentationProduct->slug, 'image' => $store->presentationProduct->coverImage->id]) }}" alt="{{ $store->presentationProduct->name }} — {{ $store->name }}" width="240" loading="lazy">
@else
    <div class="image-placeholder" role="img" aria-label="Loja sem imagem">Sem imagem</div>
@endif
