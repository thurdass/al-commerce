@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <div class="auth-layout">
        <aside class="auth-story">
            <p class="eyebrow">AL Commerce / Para lojistas</p>
            <h2>Sua loja.<br>Mais <em>encontros.</em></h2>
            <p>Um espaço para apresentar seus produtos e criar novas conexões com quem está por perto.</p>
        </aside>
        <div class="auth-form">
            <div class="page-intro"><p class="eyebrow">Bem-vindo de volta</p><h1>Entrar na sua conta</h1><p>Cuide da sua vitrine, dos seus produtos e das próximas oportunidades.</p></div>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <x-input name="email" label="E-mail" type="email" :value="old('email')" autocomplete="email" maxlength="255" required autofocus />
                <x-input name="password" label="Senha" type="password" autocomplete="current-password" required />
                <div class="actions">
                    <x-button>Entrar</x-button>
                    <a href="{{ route('register') }}">Criar conta</a>
                </div>
            </form>
        </div>
    </div>
@endsection
