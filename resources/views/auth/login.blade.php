@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <h1>Entrar na sua conta</h1>
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required autofocus>
        <label for="password">Senha</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <div class="actions">
            <button type="submit">Entrar</button>
            <a href="{{ route('register') }}">Criar conta</a>
        </div>
    </form>
@endsection
