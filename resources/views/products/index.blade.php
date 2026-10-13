@extends('layouts.app')

@section('title', 'Meus produtos')

@section('content')
    <h1>Produtos de {{ $store->name }}</h1>
    <div class="actions">
        <a href="{{ route('products.create') }}">Cadastrar produto</a>
        <a href="{{ route('dashboard') }}">Voltar ao painel</a>
    </div>

    @forelse ($products as $product)
        <article>
            <h2>{{ $product->name }}</h2>
            <dl>
                <dt>Preço</dt>
                <dd>R$ {{ number_format((float) $product->price, 2, ',', '.') }}</dd>
                <dt>Estoque</dt>
                <dd>{{ $product->stock_quantity ?? 'Não informado' }}</dd>
                <dt>Disponibilidade</dt>
                <dd>{{ $product->availability_status->label() }}</dd>
                <dt>Status</dt>
                <dd>{{ $product->active ? 'Ativo' : 'Inativo' }}</dd>
            </dl>
            <div class="actions">
                <a href="{{ route('products.edit', $product) }}">Editar</a>
                <form method="POST" action="{{ route('products.status', $product) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="active" value="{{ $product->active ? '0' : '1' }}">
                    <button type="submit">{{ $product->active ? 'Desativar' : 'Ativar' }}</button>
                </form>
            </div>
        </article>
    @empty
        <p>Nenhum produto cadastrado.</p>
    @endforelse

    @if ($products->hasPages())
        <nav class="actions" aria-label="Paginação dos produtos">
            @if (! $products->onFirstPage())
                <a href="{{ $products->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif
            <span>Página {{ $products->currentPage() }} de {{ $products->lastPage() }} ({{ $products->total() }} produtos)</span>
            @if ($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" rel="next">Próxima</a>
            @endif
        </nav>
    @endif
@endsection
