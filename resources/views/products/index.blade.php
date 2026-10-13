@extends('layouts.app')

@section('title', 'Meus produtos')

@section('content')
    <h1>Produtos de {{ $store->name }}</h1>
    <a href="{{ route('dashboard') }}">Voltar ao painel</a>

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
