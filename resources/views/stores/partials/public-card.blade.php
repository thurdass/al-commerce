<article class="catalog-card store-card">
    <a class="store-card-link" href="{{ route('public.stores.show', ['store' => $store->slug]) }}" aria-label="Ver loja {{ $store->name }}">
        <div class="card-media">
            @include('stores.partials.presentation', ['store' => $store])
        </div>
        <div class="card-body">
            <h3>{{ $store->name }}</h3>
            <p class="card-location">{{ $store->city }} / {{ $store->state }}</p>
            <p class="card-description">{{ \Illuminate\Support\Str::limit($store->description, 160) }}</p>
        </div>
        <div class="card-footer">
            <p>{{ $store->active_products_count }} {{ $store->active_products_count === 1 ? 'produto ativo' : 'produtos ativos' }}</p>
            <span class="store-card-cta">Ver loja <span aria-hidden="true">↗</span></span>
        </div>
    </a>
</article>
