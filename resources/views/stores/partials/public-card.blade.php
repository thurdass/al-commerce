<article class="catalog-card">
    @include('stores.partials.presentation', ['store' => $store])
    <h3><a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">{{ $store->name }}</a></h3>
    <p>{{ \Illuminate\Support\Str::limit($store->description, 160) }}</p>
    <p>{{ $store->city }} / {{ $store->state }}</p>
    <p>{{ $store->active_products_count }} {{ $store->active_products_count === 1 ? 'produto ativo' : 'produtos ativos' }}</p>
    <a href="{{ route('public.stores.show', ['store' => $store->slug]) }}">Ver loja</a>
</article>
