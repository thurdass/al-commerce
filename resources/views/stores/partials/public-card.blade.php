<article class="catalog-card store-card">
    <a class="card-media" href="{{ route('public.stores.show', ['store' => $store->slug]) }}" aria-label="Conhecer {{ $store->name }}">
        @include('stores.partials.presentation', ['store' => $store])
    </a>
    <div class="card-body">
        <p class="card-location">{{ $store->city }} / {{ $store->state }}</p>
        <h3><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a></h3>
        <p class="card-description">{{ \Illuminate\Support\Str::limit($store->description, 160) }}</p>
    </div>
    <div class="card-footer">
        <p>{{ $store->active_products_count }} {{ $store->active_products_count === 1 ? 'produto ativo' : 'produtos ativos' }}</p>
        <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">Ver loja <span aria-hidden="true">↗</span></a>
    </div>
</article>
