<section aria-labelledby="images-heading">
    <h2 id="images-heading">Imagens do produto</h2>
    <p>{{ $product->images->count() }} de {{ \App\Models\ProductImage::MAX_PER_PRODUCT }} imagens. A primeira imagem é a capa.</p>
    @forelse ($product->images as $image)
        <figure>
            <img src="{{ route('products.images.show', [$product, $image]) }}" alt="Imagem {{ $loop->iteration }} de {{ $product->name }}" width="180" loading="lazy">
            <figcaption>{{ $loop->first ? 'Capa' : 'Imagem '.$loop->iteration }}</figcaption>
        </figure>
    @empty
        <p>Nenhuma imagem cadastrada.</p>
    @endforelse

    @if ($product->images->count() < \App\Models\ProductImage::MAX_PER_PRODUCT)
        <form method="POST" action="{{ route('products.images.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <label for="images">Adicionar imagens</label>
            <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required aria-describedby="images-help">
            <p id="images-help">JPEG, PNG ou WebP, até 2 MB por arquivo. Envie até 3 imagens por vez, com no máximo 6 por produto.</p>
            <button type="submit">Enviar imagens</button>
        </form>
    @endif
</section>
