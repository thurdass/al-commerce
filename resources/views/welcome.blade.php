@extends('layouts.app')

@section('title', 'Início')

@section('content')
    <h1>AL Commerce</h1>
    <p>Encontre produtos disponíveis no comércio local e fale diretamente com a loja.</p>
    @auth
        <p>Olá, {{ auth()->user()->name }}. Você está conectado.</p>
        @can('create', App\Models\Store::class)
            <a href="{{ route('stores.create') }}">Criar minha loja</a>
        @endcan
    @else
        <p>É lojista? <a href="{{ route('register') }}">Crie sua conta</a> ou <a href="{{ route('login') }}">entre para continuar</a>.</p>
    @endauth
@endsection
