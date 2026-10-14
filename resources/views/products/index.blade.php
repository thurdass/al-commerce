@extends('layouts.app')

@section('title', 'Meus produtos')

@section('content')
    @include('partials.merchant-navigation')
    <div class="management-header">
        <div class="page-intro"><p class="eyebrow">Sua coleção</p><h1>Produtos de {{ $store->name }}</h1><p>Organize sua vitrine e mantenha as informações em dia.</p></div>
        <div class="actions"><x-button :href="route('products.create')">Cadastrar produto <span aria-hidden="true">+</span></x-button><a href="{{ route('dashboard') }}">Voltar ao painel</a></div>
    </div>
    <div class="management-list">
        @forelse ($products as $product)
            <article class="management-product">
                <div class="management-media">
                    @if ($product->coverImage)
                        <img src="{{ route('products.images.show', [$product, $product->coverImage]) }}" alt="Capa de {{ $product->name }}" width="96" height="112" loading="lazy">
                    @else
                        <x-image-placeholder />
                    @endif
                </div>
                <div>
                    <h2>{{ $product->name }}</h2>
                    <dl>
                        <div><dt>Categoria</dt><dd>{{ $product->category?->name ?? 'Sem categoria' }}</dd></div>
                        <div><dt>Preço</dt><dd>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</dd></div>
                        <div><dt>Estoque</dt><dd>{{ $product->stock_quantity ?? 'Não informado' }}</dd></div>
                        <div><dt>Disponibilidade</dt><dd><x-availability :status="$product->availability_status" /></dd></div>
                        <div><dt>Status</dt><dd>{{ $product->active ? 'Ativo' : 'Inativo' }}</dd></div>
                    </dl>
                </div>
                <div class="actions">
                    <a href="{{ route('products.edit', $product) }}">Editar</a>
                    <a href="{{ route('products.delete', $product) }}">Excluir</a>
                    <form method="POST" action="{{ route('products.status', $product) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="active" value="{{ $product->active ? '0' : '1' }}">
                        <button type="submit">{{ $product->active ? 'Desativar' : 'Ativar' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <x-empty-state>Nenhum produto cadastrado.</x-empty-state>
        @endforelse
    </div>
    @include('partials.pagination', ['paginator' => $products, 'label' => 'produtos'])
@endsection
