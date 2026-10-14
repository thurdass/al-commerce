@extends('layouts.app')

@section('title', 'Criar conta')

@section('content')
    <div class="auth-layout">
        <aside class="auth-story">
            <p class="eyebrow">AL Commerce / Para lojistas</p>
            <h2>O próximo<br>capítulo da<br><em>sua loja.</em></h2>
            <p>Apresente seus produtos e facilite o contato de novos clientes com o seu negócio.</p>
        </aside>
        <div class="auth-form">
            <div class="page-intro"><p class="eyebrow">Vamos começar</p><h1>Criar conta de lojista</h1><p>Sua vitrine começa aqui.</p></div>
            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <x-input name="name" label="Nome" :value="old('name')" autocomplete="name" maxlength="255" required autofocus />
                <x-input name="email" label="E-mail" type="email" :value="old('email')" autocomplete="email" maxlength="255" required />
                <x-input name="password" label="Senha (mínimo de 8 caracteres)" type="password" autocomplete="new-password" minlength="8" maxlength="72" required />
                <x-input name="password_confirmation" label="Confirme a senha" type="password" autocomplete="new-password" required />
                <div class="actions">
                    <x-button>Criar conta</x-button>
                    <a href="{{ route('login') }}">Já tenho uma conta</a>
                </div>
            </form>
        </div>
    </div>
@endsection
