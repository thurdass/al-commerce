@extends('layouts.app')

@section('title', 'Criar conta')

@section('content')
    <h1>Criar conta de lojista</h1>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label for="name">Nome</label>
        <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus>
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
        <label for="password">Senha (mínimo de 8 caracteres)</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <label for="password_confirmation">Confirme a senha</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <div class="actions">
            <button type="submit">Criar conta</button>
            <a href="{{ route('login') }}">Já tenho uma conta</a>
        </div>
    </form>
@endsection
