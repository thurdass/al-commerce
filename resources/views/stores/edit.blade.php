@extends('layouts.app')

@section('title', 'Editar loja')

@section('content')
    <h1>Editar minha loja</h1>
    <p>Slug atual: <strong>{{ $store->slug }}</strong></p>
    <p>Ao alterar o nome, o slug será atualizado automaticamente.</p>
    <form method="POST" action="{{ route('stores.update', $store) }}">
        @csrf
        @method('PUT')
        @include('stores.partials.form', ['store' => $store])
        <div class="actions">
            <button type="submit">Salvar alterações</button>
            <a href="{{ route('home') }}">Voltar</a>
        </div>
    </form>
@endsection
