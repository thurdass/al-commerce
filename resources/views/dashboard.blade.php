@extends('layouts.app')

@section('title', 'Painel do lojista')

@section('content')
    <h1>Painel do lojista</h1>
    <p>Olá, {{ auth()->user()->name }}.</p>
    @if ($store)
        <h2>Minha loja</h2>
        <dl>
            <dt>Nome</dt>
            <dd>{{ $store->name }}</dd>
            <dt>Slug</dt>
            <dd>{{ $store->slug }}</dd>
            <dt>Status</dt>
            <dd>{{ $store->active ? 'Ativa' : 'Inativa' }}</dd>
            <dt>Cidade</dt>
            <dd>{{ $store->city }} / {{ $store->state }}</dd>
            <dt>Produtos cadastrados</dt>
            <dd>{{ $store->products_count }}</dd>
        </dl>
        <div class="actions">
            <a href="{{ route('stores.edit', $store) }}">Editar loja</a>
        </div>
    @else
        <p>Você ainda não possui uma loja.</p>
        <a href="{{ route('stores.create') }}">Criar minha loja</a>
    @endif
@endsection
