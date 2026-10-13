@extends('layouts.app')

@section('title', 'Início')

@section('content')
    <h1>AL Commerce</h1>
    <p>Encontre produtos disponíveis no comércio local e fale diretamente com a loja.</p>
    @auth
        <a href="{{ route('dashboard') }}">Acessar meu painel</a>
    @else
        <p>É lojista? <a href="{{ route('register') }}">Crie sua conta</a> ou <a href="{{ route('login') }}">entre para continuar</a>.</p>
    @endauth
@endsection
