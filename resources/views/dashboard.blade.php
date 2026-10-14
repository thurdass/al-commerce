@extends('layouts.app')

@section('title', 'Painel do lojista')

@section('content')
    @include('partials.merchant-navigation', ['hasStore' => $store !== null])
    <div class="page-intro">
        <p class="eyebrow">Seu espaço no AL Commerce</p>
        <h1>Painel do lojista</h1>
        <p>Olá, {{ auth()->user()->name }}. Cuide da sua loja e mantenha sua vitrine sempre atualizada.</p>
    </div>
    @if ($store)
        <section class="dashboard-store" aria-labelledby="my-store-heading">
            <div class="dashboard-store-heading">
                <div><p class="eyebrow">Minha loja</p><h2 id="my-store-heading">{{ $store->name }}</h2></div>
                @if ($store->active)
                    <x-button :href="route('public.stores.show', ['store' => $store->slug])" variant="secondary">Ver minha vitrine <span aria-hidden="true">↗</span></x-button>
                @endif
            </div>
            <dl class="dashboard-data">
                <div><dt>Slug</dt><dd>{{ $store->slug }}</dd></div>
                <div><dt>Status</dt><dd>{{ $store->active ? 'Ativa' : 'Inativa' }}</dd></div>
                <div><dt>Cidade</dt><dd>{{ $store->city }} / {{ $store->state }}</dd></div>
                <div><dt>Produtos cadastrados</dt><dd>{{ $store->products_count }}</dd></div>
            </dl>
            <div class="actions">
                <x-button :href="route('products.index')">Gerenciar produtos</x-button>
                <a href="{{ route('stores.edit', $store) }}">Editar loja</a>
            </div>
        </section>
    @else
        <section class="dashboard-store">
            <h2>Sua próxima vitrine começa aqui.</h2>
            <p>Você ainda não possui uma loja.</p>
            <div class="actions"><x-button :href="route('stores.create')">Criar minha loja</x-button></div>
        </section>
    @endif
    <section class="account-danger-zone" aria-labelledby="account-settings-heading">
        <div>
            <p class="eyebrow">Sua conta</p>
            <h2 id="account-settings-heading">Exclusão de conta</h2>
            <p>Encerre sua conta e remova permanentemente sua loja, seus produtos e suas imagens.</p>
        </div>
        <a class="danger-link" href="{{ route('account.delete') }}">Excluir minha conta <span aria-hidden="true">↗</span></a>
    </section>
@endsection
