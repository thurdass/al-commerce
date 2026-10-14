<section class="form-section" aria-labelledby="images-heading">
    <h2 id="images-heading">Imagens do produto</h2>
    <p>{{ $product->images->count() }} de {{ \App\Models\ProductImage::MAX_PER_PRODUCT }} imagens. A primeira imagem é a capa.</p>
    <div class="image-management-grid">
        @forelse ($product->images as $image)
            <figure>
                <img src="{{ route('products.images.show', [$product, $image]) }}" alt="Imagem {{ $loop->iteration }} de {{ $product->name }}" width="240" height="240" loading="lazy">
                <figcaption>{{ $loop->first ? 'Capa' : 'Imagem '.$loop->iteration }}</figcaption>
                @if (! $loop->first)
                    <form method="POST" action="{{ route('products.images.cover', [$product, $image]) }}">
                        @csrf
                        @method('PATCH')
                        <x-button variant="secondary">Definir como capa</x-button>
                    </form>
                @endif
                <form method="POST" action="{{ route('products.images.destroy', [$product, $image]) }}">
                    @csrf
                    @method('DELETE')
                    <label for="remove-image-{{ $image->id }}">
                        <input id="remove-image-{{ $image->id }}" type="checkbox" name="confirmed" value="1" required>
                        Confirmo a remoção desta imagem.
                    </label>
                    <x-button variant="secondary">Remover imagem</x-button>
                </form>
            </figure>
        @empty
            <x-empty-state>Nenhuma imagem cadastrada.</x-empty-state>
        @endforelse
    </div>
    @if ($product->images->count() < \App\Models\ProductImage::MAX_PER_PRODUCT)
        <form class="upload-form" method="POST" action="{{ route('products.images.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <label for="images">Adicionar imagens</label>
            <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required aria-describedby="images-help">
            <p id="images-help">JPEG, PNG ou WebP, até 2 MB por arquivo. Envie até 3 imagens por vez, com no máximo 6 por produto.</p>
            <button type="submit">Enviar imagens</button>
        </form>
    @endif
</section>
