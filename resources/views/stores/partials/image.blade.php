<section class="form-section" aria-labelledby="store-image-heading">
    <h2 id="store-image-heading">Imagem da loja</h2>
    <p>Use um logo ou uma foto para apresentar sua loja.</p>
    @if ($store->logo)
        <div class="store-image-preview">
            <img class="catalog-image" src="{{ route('stores.image.show', $store) }}" alt="Imagem de {{ $store->name }}" width="240">
            <form method="POST" action="{{ route('stores.image.destroy', $store) }}">
                @csrf
                @method('DELETE')
                <label for="remove-store-image" class="check-field">
                    <input id="remove-store-image" type="checkbox" name="confirmed" value="1" required>
                    Confirmo a remoção da imagem da loja.
                </label>
                <x-button variant="secondary">Remover imagem</x-button>
            </form>
        </div>
    @else
        <x-empty-state>Sua loja ainda não tem imagem própria.</x-empty-state>
    @endif
    <form class="upload-form" method="POST" action="{{ route('stores.image.store', $store) }}" enctype="multipart/form-data">
        @csrf
        <label for="store-image">{{ $store->logo ? 'Substituir imagem' : 'Adicionar imagem' }}</label>
        <input id="store-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required aria-describedby="store-image-help">
        <p id="store-image-help">JPEG, PNG ou WebP, até 2 MB. A nova imagem substituirá a anterior.</p>
        <button type="submit">Salvar imagem</button>
    </form>
</section>
